<?php
/**
 * Activation and schema setup.
 *
 * @package EmailRequiredDownload
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ERD_Activator {
	/**
	 * Create or update plugin tables and default options.
	 */
	public static function activate() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$offers_table    = $wpdb->prefix . 'erd_offers';
		$files_table     = $wpdb->prefix . 'erd_offer_files';
		$leads_table     = $wpdb->prefix . 'erd_leads';

		$sql_offers = "CREATE TABLE {$offers_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			title varchar(255) NOT NULL,
			file_id bigint(20) unsigned NOT NULL DEFAULT 0,
			file_url text NOT NULL,
			button_text varchar(120) NOT NULL DEFAULT '',
			success_message text NOT NULL,
			name_required tinyint(1) NOT NULL DEFAULT 0,
			require_confirmation tinyint(1) NOT NULL DEFAULT 0,
			consent_text text NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id)
		) {$charset_collate};";

		$sql_leads = "CREATE TABLE {$leads_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			offer_id bigint(20) unsigned NOT NULL,
			email varchar(190) NOT NULL,
			name varchar(190) NOT NULL DEFAULT '',
			token varchar(255) NOT NULL,
			token_expires_at datetime NOT NULL,
			consent_given tinyint(1) NOT NULL DEFAULT 0,
			consent_text text NOT NULL,
			consent_at datetime DEFAULT NULL,
			submitted_at datetime NOT NULL,
			downloaded_at datetime DEFAULT NULL,
			download_count int(11) unsigned NOT NULL DEFAULT 0,
			email_status varchar(20) NOT NULL DEFAULT 'not_required',
			email_sent_at datetime DEFAULT NULL,
			referrer text NOT NULL,
			ip_hash varchar(128) NOT NULL DEFAULT '',
			user_agent text NOT NULL,
			PRIMARY KEY  (id),
			KEY offer_id (offer_id),
			KEY email (email),
			KEY token (token(191))
		) {$charset_collate};";

		$sql_files = "CREATE TABLE {$files_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			offer_id bigint(20) unsigned NOT NULL,
			file_id bigint(20) unsigned NOT NULL DEFAULT 0,
			file_url text NOT NULL,
			file_label varchar(255) NOT NULL DEFAULT '',
			sort_order int(11) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY offer_id (offer_id)
		) {$charset_collate};";

		dbDelta( $sql_offers );
		dbDelta( $sql_files );
		dbDelta( $sql_leads );

		$settings = get_option( 'erd_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$settings = wp_parse_args(
			$settings,
			array(
				'token_expiry_hours' => 24,
				'default_button_text' => __( 'Download', 'email-required-to-download' ),
				'ip_hash_salt'       => wp_generate_password( 32, true, true ),
			)
		);

		update_option( 'erd_settings', $settings, false );
		self::migrate_offer_files( $offers_table, $files_table );
		update_option( 'erd_db_version', ERD_DB_VERSION, false );
	}

	private static function migrate_offer_files( $offers_table, $files_table ) {
		global $wpdb;

		$offers = $wpdb->get_results( "SELECT id, file_id, file_url, title FROM {$offers_table} WHERE file_url <> ''" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		foreach ( $offers as $offer ) {
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$files_table} WHERE offer_id = %d LIMIT 1", absint( $offer->id ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( $exists ) {
				continue;
			}

			$wpdb->insert(
				$files_table,
				array(
					'offer_id'   => absint( $offer->id ),
					'file_id'    => absint( $offer->file_id ),
					'file_url'   => esc_url_raw( $offer->file_url ),
					'file_label' => sanitize_text_field( $offer->title ),
					'sort_order' => 0,
					'created_at' => current_time( 'mysql' ),
				)
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		}
	}
}
