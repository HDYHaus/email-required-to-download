<?php
/**
 * Database access.
 *
 * @package EmailRequiredDownload
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ERD_Repository {
	public function offers_table() {
		global $wpdb;
		return $wpdb->prefix . 'erd_offers';
	}

	public function leads_table() {
		global $wpdb;
		return $wpdb->prefix . 'erd_leads';
	}

	public function get_settings() {
		$settings = get_option( 'erd_settings', array() );
		return wp_parse_args(
			is_array( $settings ) ? $settings : array(),
			array(
				'token_expiry_hours' => 24,
				'default_button_text' => __( 'Download', 'email-required-to-download' ),
				'ip_hash_salt'       => '',
			)
		);
	}

	public function save_settings( array $settings ) {
		$current = $this->get_settings();
		update_option( 'erd_settings', wp_parse_args( $settings, $current ), false );
	}

	public function get_offer( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->offers_table()} WHERE id = %d", absint( $id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public function get_offers() {
		global $wpdb;
		return $wpdb->get_results( "SELECT * FROM {$this->offers_table()} ORDER BY created_at DESC" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public function save_offer( array $data, $id = 0 ) {
		global $wpdb;

		$now     = current_time( 'mysql' );
		$payload = array(
			'title'                => $data['title'],
			'file_id'              => absint( $data['file_id'] ),
			'file_url'             => esc_url_raw( $data['file_url'] ),
			'button_text'          => $data['button_text'],
			'success_message'      => $data['success_message'],
			'name_required'        => ! empty( $data['name_required'] ) ? 1 : 0,
			'require_confirmation' => ! empty( $data['require_confirmation'] ) ? 1 : 0,
			'consent_text'         => $data['consent_text'],
			'updated_at'           => $now,
		);

		if ( $id ) {
			$wpdb->update( $this->offers_table(), $payload, array( 'id' => absint( $id ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			return absint( $id );
		}

		$payload['created_at'] = $now;
		$wpdb->insert( $this->offers_table(), $payload ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return absint( $wpdb->insert_id );
	}

	public function delete_offer( $id ) {
		global $wpdb;
		$wpdb->delete( $this->offers_table(), array( 'id' => absint( $id ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	public function create_lead( array $data ) {
		global $wpdb;

		$wpdb->insert(
			$this->leads_table(),
			array(
				'offer_id'         => absint( $data['offer_id'] ),
				'email'            => sanitize_email( $data['email'] ),
				'name'             => sanitize_text_field( $data['name'] ),
				'token'            => sanitize_text_field( $data['token'] ),
				'token_expires_at' => $data['token_expires_at'],
				'consent_given'    => 1,
				'consent_text'     => wp_kses_post( $data['consent_text'] ),
				'consent_at'       => current_time( 'mysql' ),
				'submitted_at'     => current_time( 'mysql' ),
				'referrer'         => esc_url_raw( $data['referrer'] ),
				'ip_hash'          => sanitize_text_field( $data['ip_hash'] ),
				'user_agent'       => sanitize_textarea_field( $data['user_agent'] ),
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		return absint( $wpdb->insert_id );
	}

	public function update_lead_token( $lead_id, $token ) {
		global $wpdb;
		$wpdb->update( $this->leads_table(), array( 'token' => sanitize_text_field( $token ) ), array( 'id' => absint( $lead_id ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	public function get_lead_by_token( $token ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->leads_table()} WHERE token = %s", sanitize_text_field( $token ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public function mark_downloaded( $lead_id ) {
		global $wpdb;
		$lead = $wpdb->get_row( $wpdb->prepare( "SELECT downloaded_at, download_count FROM {$this->leads_table()} WHERE id = %d", absint( $lead_id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $lead ) {
			return;
		}

		$wpdb->update(
			$this->leads_table(),
			array(
				'downloaded_at'  => $lead->downloaded_at ? $lead->downloaded_at : current_time( 'mysql' ),
				'download_count' => absint( $lead->download_count ) + 1,
			),
			array( 'id' => absint( $lead_id ) )
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	public function get_leads( $offer_id = 0, $limit = 50 ) {
		global $wpdb;
		if ( $offer_id ) {
			return $wpdb->get_results( $wpdb->prepare( "SELECT l.*, o.title AS offer_title FROM {$this->leads_table()} l LEFT JOIN {$this->offers_table()} o ON l.offer_id = o.id WHERE l.offer_id = %d ORDER BY l.submitted_at DESC LIMIT %d", absint( $offer_id ), absint( $limit ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		return $wpdb->get_results( $wpdb->prepare( "SELECT l.*, o.title AS offer_title FROM {$this->leads_table()} l LEFT JOIN {$this->offers_table()} o ON l.offer_id = o.id ORDER BY l.submitted_at DESC LIMIT %d", absint( $limit ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public function get_offer_stats( $offer_id = 0 ) {
		global $wpdb;
		$where = $offer_id ? $wpdb->prepare( 'WHERE offer_id = %d', absint( $offer_id ) ) : '';
		return array(
			'submissions'   => absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->leads_table()} {$where}" ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'unique_emails' => absint( $wpdb->get_var( "SELECT COUNT(DISTINCT email) FROM {$this->leads_table()} {$where}" ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'downloads'     => absint( $wpdb->get_var( "SELECT COALESCE(SUM(download_count), 0) FROM {$this->leads_table()} {$where}" ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}
}
