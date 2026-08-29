<?php
/**
 * Activation and schema setup.
 *
 * @package EmailRequiredDownload
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Custom plugin tables require direct schema queries during activation and repair.
// phpcs:disable WordPress.DB.DirectDatabaseQuery

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
			show_title tinyint(1) NOT NULL DEFAULT 0,
			description text NOT NULL,
			file_id bigint(20) unsigned NOT NULL DEFAULT 0,
			file_url text NOT NULL,
			button_text varchar(120) NOT NULL DEFAULT '',
			delivery_mode varchar(20) NOT NULL DEFAULT 'public',
			force_download tinyint(1) NOT NULL DEFAULT 0,
			use_button_colors tinyint(1) NOT NULL DEFAULT 0,
			button_bg_color varchar(20) NOT NULL DEFAULT '',
			button_text_color varchar(20) NOT NULL DEFAULT '',
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
			protected_path text NOT NULL,
			mime_type varchar(100) NOT NULL DEFAULT '',
			sort_order int(11) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY offer_id (offer_id)
		) {$charset_collate};";

		dbDelta( $sql_offers );
		dbDelta( $sql_files );
		dbDelta( $sql_leads );
		self::ensure_required_columns( $offers_table, $files_table, $leads_table );

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
		self::ensure_protected_directory();
		self::migrate_offer_files( $offers_table, $files_table );
		update_option( 'erd_db_version', ERD_DB_VERSION, false );
	}

	public static function protected_dir() {
		return trailingslashit( WP_CONTENT_DIR ) . 'erd-protected';
	}

	public static function ensure_protected_directory() {
		$dir = self::protected_dir();
		if ( ! wp_mkdir_p( $dir ) ) {
			return false;
		}

		if ( ! file_exists( trailingslashit( $dir ) . 'index.php' ) ) {
			file_put_contents( trailingslashit( $dir ) . 'index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}

		if ( ! file_exists( trailingslashit( $dir ) . '.htaccess' ) ) {
			file_put_contents( trailingslashit( $dir ) . '.htaccess', "Deny from all\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}

		return $dir;
	}

	public static function is_schema_current() {
		global $wpdb;

		$offers_table = $wpdb->prefix . 'erd_offers';
		$files_table  = $wpdb->prefix . 'erd_offer_files';
		$leads_table  = $wpdb->prefix . 'erd_leads';

		return self::has_columns( $offers_table, array( 'delivery_mode', 'force_download' ) )
			&& self::has_columns( $files_table, array( 'protected_path', 'mime_type' ) )
			&& self::has_columns( $leads_table, array( 'email_status', 'email_sent_at' ) );
	}

	private static function ensure_required_columns( $offers_table, $files_table, $leads_table ) {
		global $wpdb;

		$offers_table = esc_sql( $offers_table );
		$files_table  = esc_sql( $files_table );
		$leads_table  = esc_sql( $leads_table );

		$columns = self::table_columns( $offers_table );
		if ( $columns && ! in_array( 'delivery_mode', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE {$offers_table} ADD delivery_mode varchar(20) NOT NULL DEFAULT 'public' AFTER button_text" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
		if ( $columns && ! in_array( 'force_download', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE {$offers_table} ADD force_download tinyint(1) NOT NULL DEFAULT 0 AFTER delivery_mode" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		$columns = self::table_columns( $files_table );
		if ( $columns && ! in_array( 'protected_path', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE {$files_table} ADD protected_path text NOT NULL AFTER file_label" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
		if ( $columns && ! in_array( 'mime_type', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE {$files_table} ADD mime_type varchar(100) NOT NULL DEFAULT '' AFTER protected_path" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		$columns = self::table_columns( $leads_table );
		if ( $columns && ! in_array( 'email_status', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE {$leads_table} ADD email_status varchar(20) NOT NULL DEFAULT 'not_required' AFTER download_count" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
		if ( $columns && ! in_array( 'email_sent_at', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE {$leads_table} ADD email_sent_at datetime DEFAULT NULL AFTER email_status" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}

	private static function has_columns( $table, array $required ) {
		$columns = self::table_columns( $table );
		if ( ! $columns ) {
			return false;
		}

		foreach ( $required as $column ) {
			if ( ! in_array( $column, $columns, true ) ) {
				return false;
			}
		}

		return true;
	}

	private static function table_columns( $table ) {
		global $wpdb;

		$table   = esc_sql( $table );
		$columns = $wpdb->get_col( "DESC {$table}", 0 ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return is_array( $columns ) ? $columns : array();
	}

	private static function migrate_offer_files( $offers_table, $files_table ) {
		global $wpdb;

		$offers_table = esc_sql( $offers_table );
		$files_table  = esc_sql( $files_table );

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
