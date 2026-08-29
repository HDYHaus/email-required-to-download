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

	public function files_table() {
		global $wpdb;
		return $wpdb->prefix . 'erd_offer_files';
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
		$files = $this->normalize_files( isset( $data['files'] ) && is_array( $data['files'] ) ? $data['files'] : array() );
		$first = $files ? $files[0] : array( 'file_id' => 0, 'file_url' => '' );

		$now     = current_time( 'mysql' );
		$payload = array(
			'title'                => $data['title'],
			'display_title'        => $data['display_title'],
			'description'          => $data['description'],
			'file_id'              => absint( $first['file_id'] ),
			'file_url'             => esc_url_raw( $first['file_url'] ),
			'button_text'          => $data['button_text'],
			'success_message'      => $data['success_message'],
			'name_required'        => ! empty( $data['name_required'] ) ? 1 : 0,
			'require_confirmation' => ! empty( $data['require_confirmation'] ) ? 1 : 0,
			'consent_text'         => $data['consent_text'],
			'updated_at'           => $now,
		);

		if ( $id ) {
			$wpdb->update( $this->offers_table(), $payload, array( 'id' => absint( $id ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$this->save_offer_files( absint( $id ), $files );
			return absint( $id );
		}

		$payload['created_at'] = $now;
		$wpdb->insert( $this->offers_table(), $payload ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$offer_id = absint( $wpdb->insert_id );
		$this->save_offer_files( $offer_id, $files );
		return $offer_id;
	}

	public function delete_offer( $id ) {
		global $wpdb;
		$wpdb->delete( $this->files_table(), array( 'offer_id' => absint( $id ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->delete( $this->offers_table(), array( 'id' => absint( $id ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	public function get_offer_files( $offer_id ) {
		global $wpdb;

		$files = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->files_table()} WHERE offer_id = %d ORDER BY sort_order ASC, id ASC", absint( $offer_id ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $files ) {
			return $files;
		}

		$offer = $this->get_offer( $offer_id );
		if ( ! $offer || empty( $offer->file_url ) ) {
			return array();
		}

		return array(
			(object) array(
				'id'         => 0,
				'offer_id'   => absint( $offer->id ),
				'file_id'    => absint( $offer->file_id ),
				'file_url'   => $offer->file_url,
				'file_label' => $offer->title,
				'sort_order' => 0,
			),
		);
	}

	public function get_offer_file( $offer_id, $file_row_id = 0 ) {
		$files = $this->get_offer_files( $offer_id );
		if ( ! $files ) {
			return null;
		}

		if ( ! $file_row_id ) {
			return $files[0];
		}

		foreach ( $files as $file ) {
			if ( absint( $file->id ) === absint( $file_row_id ) ) {
				return $file;
			}
		}

		return null;
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
				'email_status'     => isset( $data['email_status'] ) ? sanitize_key( $data['email_status'] ) : 'not_required',
				'email_sent_at'    => null,
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

	public function update_lead_email_status( $lead_id, $status ) {
		global $wpdb;

		$status  = in_array( $status, array( 'not_required', 'pending', 'sent', 'failed' ), true ) ? $status : 'pending';
		$payload = array( 'email_status' => $status );
		if ( 'sent' === $status ) {
			$payload['email_sent_at'] = current_time( 'mysql' );
		}

		$wpdb->update( $this->leads_table(), $payload, array( 'id' => absint( $lead_id ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
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

	private function save_offer_files( $offer_id, array $files ) {
		global $wpdb;

		$wpdb->delete( $this->files_table(), array( 'offer_id' => absint( $offer_id ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		foreach ( $files as $index => $file ) {
			$wpdb->insert(
				$this->files_table(),
				array(
					'offer_id'   => absint( $offer_id ),
					'file_id'    => absint( $file['file_id'] ),
					'file_url'   => esc_url_raw( $file['file_url'] ),
					'file_label' => sanitize_text_field( $file['file_label'] ),
					'sort_order' => absint( $index ),
					'created_at' => current_time( 'mysql' ),
				)
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		}
	}

	private function normalize_files( array $files ) {
		$normalized = array();
		foreach ( $files as $file ) {
			$file_url = isset( $file['file_url'] ) ? esc_url_raw( $file['file_url'] ) : '';
			if ( '' === $file_url ) {
				continue;
			}

			$normalized[] = array(
				'file_id'    => isset( $file['file_id'] ) ? absint( $file['file_id'] ) : 0,
				'file_url'   => $file_url,
				'file_label' => isset( $file['file_label'] ) && '' !== trim( $file['file_label'] ) ? sanitize_text_field( $file['file_label'] ) : $this->file_label_from_url( $file_url ),
			);
		}

		return $normalized;
	}

	private function file_label_from_url( $file_url ) {
		$path = wp_parse_url( $file_url, PHP_URL_PATH );
		if ( $path ) {
			return basename( $path );
		}

		return __( 'Download file', 'email-required-to-download' );
	}
}
