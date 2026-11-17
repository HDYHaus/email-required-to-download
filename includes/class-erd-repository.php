<?php
/**
 * Database access.
 *
 * @package EmailRequiredDownload
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// This repository owns the plugin's custom tables, so direct database access is intentional.
// phpcs:disable WordPress.DB.DirectDatabaseQuery

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
		$table = esc_sql( $this->offers_table() );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public function get_offers() {
		global $wpdb;
		$table = esc_sql( $this->offers_table() );
		return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public function save_offer( array $data, $id = 0 ) {
		global $wpdb;
		$files             = $this->normalize_files( isset( $data['files'] ) && is_array( $data['files'] ) ? $data['files'] : array() );
		$first             = $files ? $files[0] : array( 'file_id' => 0, 'file_url' => '' );
		$button_bg_color   = isset( $data['button_bg_color'] ) ? sanitize_hex_color( $data['button_bg_color'] ) : '';
		$button_text_color = isset( $data['button_text_color'] ) ? sanitize_hex_color( $data['button_text_color'] ) : '';

		$now     = current_time( 'mysql' );
		$payload = array(
			'title'                => $data['title'],
			'show_title'           => ! empty( $data['show_title'] ) ? 1 : 0,
			'description'          => $data['description'],
			'file_id'              => absint( $first['file_id'] ),
			'file_url'             => $this->sanitize_file_reference( $first['file_url'] ),
			'button_text'          => $data['button_text'],
			'delivery_mode'        => isset( $data['delivery_mode'] ) && 'protected' === $data['delivery_mode'] ? 'protected' : 'public',
			'force_download'       => ! empty( $data['force_download'] ) ? 1 : 0,
			'use_button_colors'    => ! empty( $data['use_button_colors'] ) ? 1 : 0,
			'button_bg_color'      => $button_bg_color ? $button_bg_color : '',
			'button_text_color'    => $button_text_color ? $button_text_color : '',
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
		$this->delete_protected_files( $id );
		$wpdb->delete( $this->files_table(), array( 'offer_id' => absint( $id ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->delete( $this->offers_table(), array( 'id' => absint( $id ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	public function get_offer_files( $offer_id ) {
		global $wpdb;

		$table = esc_sql( $this->files_table() );
		$files = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE offer_id = %d ORDER BY sort_order ASC, id ASC", absint( $offer_id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $files ) {
			return $files;
		}

		$offer = $this->get_offer( $offer_id );
		if ( ! $offer || empty( $offer->file_url ) ) {
			return array();
		}

		return array(
			(object) array(
				'id'             => 0,
				'offer_id'       => absint( $offer->id ),
				'file_id'        => absint( $offer->file_id ),
				'file_url'       => $offer->file_url,
				'file_label'     => $offer->title,
				'protected_path' => '',
				'mime_type'      => '',
				'sort_order'     => 0,
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
		$result = $wpdb->update( $this->leads_table(), array( 'token' => sanitize_text_field( $token ) ), array( 'id' => absint( $lead_id ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return false !== $result;
	}

	public function update_lead_download_token( $lead_id, $token, $expires_at ) {
		global $wpdb;
		$result = $wpdb->update(
			$this->leads_table(),
			array(
				'token'            => sanitize_text_field( $token ),
				'token_expires_at' => sanitize_text_field( $expires_at ),
			),
			array( 'id' => absint( $lead_id ) )
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		return false !== $result;
	}

	public function get_lead( $lead_id ) {
		global $wpdb;
		$table = esc_sql( $this->leads_table() );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $lead_id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public function get_active_leads_by_email( $offer_id, $email ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE offer_id = %d AND email = %s AND token_expires_at > %s ORDER BY id DESC LIMIT 20',
				$this->leads_table(),
				absint( $offer_id ),
				sanitize_email( $email ),
				gmdate( 'Y-m-d H:i:s' )
			)
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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
		$table = esc_sql( $this->leads_table() );
		foreach ( ERD_Tokens::storage_candidates( $token ) as $candidate ) {
			$lead = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE token = %s", $candidate ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( $lead ) {
				return $lead;
			}
		}

		return null;
	}

	public function validate_token( $token, $offer_id = 0 ) {
		$failure_reason = '';
		$parts = ERD_Tokens::parse( $token, $failure_reason );
		if ( ! $parts ) {
			ERD_Tokens::log_validation_failure( $failure_reason );
			return false;
		}

		if ( $offer_id && absint( $offer_id ) !== absint( $parts['offer_id'] ) ) {
			ERD_Tokens::log_validation_failure( 'offer_mismatch' );
			return false;
		}

		$lead = $this->get_lead_by_token( $token );
		if ( ! $lead ) {
			ERD_Tokens::log_validation_failure( 'lead_not_found_for_token' );
			return false;
		}
		if ( absint( $lead->id ) !== $parts['lead_id'] || absint( $lead->offer_id ) !== $parts['offer_id'] ) {
			ERD_Tokens::log_validation_failure( 'lead_or_offer_record_mismatch' );
			return false;
		}

		return array(
			'parts' => $parts,
			'lead'  => $lead,
		);
	}

	public function mark_downloaded( $lead_id ) {
		global $wpdb;
		$table = esc_sql( $this->leads_table() );
		$lead  = $wpdb->get_row( $wpdb->prepare( "SELECT downloaded_at, download_count FROM {$table} WHERE id = %d", absint( $lead_id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $lead ) {
			return;
		}

		$wpdb->update(
			$this->leads_table(),
			array(
				'downloaded_at'  => $lead->downloaded_at ? $lead->downloaded_at : current_time( 'mysql' ),
				'download_count' => $lead->downloaded_at ? absint( $lead->download_count ) : 1,
			),
			array( 'id' => absint( $lead_id ) )
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	public function get_leads( $offer_id = 0, $limit = 50, $offset = 0 ) {
		global $wpdb;
		$leads_table  = esc_sql( $this->leads_table() );
		$offers_table = esc_sql( $this->offers_table() );
		if ( $offer_id ) {
			return $wpdb->get_results( $wpdb->prepare( "SELECT l.*, o.title AS offer_title FROM {$leads_table} l LEFT JOIN {$offers_table} o ON l.offer_id = o.id WHERE l.offer_id = %d ORDER BY l.submitted_at DESC, l.id DESC LIMIT %d OFFSET %d", absint( $offer_id ), absint( $limit ), absint( $offset ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		return $wpdb->get_results( $wpdb->prepare( "SELECT l.*, o.title AS offer_title FROM {$leads_table} l LEFT JOIN {$offers_table} o ON l.offer_id = o.id ORDER BY l.submitted_at DESC, l.id DESC LIMIT %d OFFSET %d", absint( $limit ), absint( $offset ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public function get_offer_stats( $offer_id = 0 ) {
		global $wpdb;
		$table = esc_sql( $this->leads_table() );
		$where = $offer_id ? $wpdb->prepare( 'WHERE offer_id = %d', absint( $offer_id ) ) : '';
		return array(
			'submissions'   => absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$where}" ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'unique_emails' => absint( $wpdb->get_var( "SELECT COUNT(DISTINCT email) FROM {$table} {$where}" ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'downloads'     => absint( $wpdb->get_var( "SELECT COALESCE(SUM(download_count), 0) FROM {$table} {$where}" ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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
					'file_url'   => $this->sanitize_file_reference( $file['file_url'] ),
					'file_label' => sanitize_text_field( $file['file_label'] ),
					'protected_path' => isset( $file['protected_path'] ) ? sanitize_text_field( $file['protected_path'] ) : '',
					'mime_type'  => isset( $file['mime_type'] ) ? sanitize_mime_type( $file['mime_type'] ) : '',
					'sort_order' => absint( $index ),
					'created_at' => current_time( 'mysql' ),
				)
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		}
	}

	private function normalize_files( array $files ) {
		$normalized = array();
		foreach ( $files as $file ) {
			$file_url = isset( $file['file_url'] ) ? $this->sanitize_file_reference( $file['file_url'] ) : '';
			if ( '' === $file_url ) {
				continue;
			}

			$normalized[] = array(
				'file_id'        => isset( $file['file_id'] ) ? absint( $file['file_id'] ) : 0,
				'file_url'       => $file_url,
				'file_label'     => isset( $file['file_label'] ) && '' !== trim( $file['file_label'] ) ? sanitize_text_field( $file['file_label'] ) : $this->file_label_from_url( $file_url ),
				'protected_path' => isset( $file['protected_path'] ) ? sanitize_text_field( $file['protected_path'] ) : '',
				'mime_type'      => isset( $file['mime_type'] ) ? sanitize_mime_type( $file['mime_type'] ) : '',
			);
		}

		return $normalized;
	}

	public function prepare_protected_files( array $files ) {
		$dir = ERD_Activator::ensure_protected_directory();
		if ( ! $dir ) {
			return false;
		}

		foreach ( $files as $index => $file ) {
			$upload = isset( $file['protected_upload'] ) && is_array( $file['protected_upload'] ) ? $file['protected_upload'] : array();
			if ( $upload && UPLOAD_ERR_OK === absint( $upload['error'] ) ) {
				$prepared = $this->move_protected_upload( $upload, $dir );
				if ( ! $prepared ) {
					return false;
				}

				$files[ $index ]['file_id']        = 0;
				$files[ $index ]['file_url']       = 'protected://' . basename( $prepared['path'] );
				$files[ $index ]['file_label']     = ! empty( $file['file_label'] ) ? $file['file_label'] : $prepared['filename'];
				$files[ $index ]['protected_path'] = $prepared['path'];
				$files[ $index ]['mime_type']      = $prepared['mime_type'];
				continue;
			}

			$protected = isset( $file['protected_path'] ) ? $file['protected_path'] : '';
			if ( $protected && $this->is_protected_path( $protected, $dir ) && file_exists( $protected ) ) {
				$files[ $index ]['file_url']       = ! empty( $file['file_url'] ) ? $file['file_url'] : 'protected://' . basename( $protected );
				$files[ $index ]['protected_path'] = $protected;
				$files[ $index ]['mime_type']      = ! empty( $file['mime_type'] ) ? $file['mime_type'] : 'application/octet-stream';
				continue;
			}

			$file_id = isset( $file['file_id'] ) ? absint( $file['file_id'] ) : 0;
			if ( ! $file_id ) {
				return false;
			}

			$source = get_attached_file( $file_id );
			if ( ! $source || ! file_exists( $source ) || ! is_readable( $source ) ) {
				return false;
			}

			$filename  = sanitize_file_name( basename( $source ) );
			$target    = trailingslashit( $dir ) . wp_hash( $file_id . '|' . $source ) . '-' . $filename;
			if ( $protected && $this->is_protected_path( $protected, $dir ) && file_exists( $protected ) && basename( $protected ) === basename( $target ) ) {
				$target = $protected;
			} elseif ( ! copy( $source, $target ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy
				return false;
			}

			$files[ $index ]['protected_path'] = $target;
			$files[ $index ]['mime_type']      = get_post_mime_type( $file_id );
		}

		return $files;
	}

	private function move_protected_upload( array $upload, $dir ) {
		if ( empty( $upload['tmp_name'] ) || empty( $upload['name'] ) || ! is_uploaded_file( $upload['tmp_name'] ) ) {
			return false;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';

		$protected_upload_dir = static function ( $uploads ) use ( $dir ) {
			$uploads['path']    = $dir;
			$uploads['url']     = content_url( 'erd-protected' );
			$uploads['subdir']  = '';
			$uploads['basedir'] = $dir;
			$uploads['baseurl'] = content_url( 'erd-protected' );
			return $uploads;
		};

		add_filter( 'upload_dir', $protected_upload_dir );
		$result = wp_handle_upload( $upload, array( 'test_form' => false ) );
		remove_filter( 'upload_dir', $protected_upload_dir );

		if ( empty( $result['file'] ) || ! empty( $result['error'] ) ) {
			return false;
		}

		$filename = basename( $result['file'] );
		$filetype = wp_check_filetype( $filename );
		return array(
			'filename'  => $filename,
			'path'      => $result['file'],
			'mime_type' => ! empty( $filetype['type'] ) ? $filetype['type'] : 'application/octet-stream',
		);
	}

	private function delete_protected_files( $offer_id ) {
		global $wpdb;
		$table = esc_sql( $this->files_table() );
		foreach ( $this->get_offer_files( $offer_id ) as $file ) {
			if ( ! empty( $file->protected_path ) && $this->is_protected_path( $file->protected_path, ERD_Activator::protected_dir() ) && file_exists( $file->protected_path ) ) {
				$shared = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE protected_path = %s AND offer_id <> %d LIMIT 1", $file->protected_path, absint( $offer_id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				if ( $shared ) {
					continue;
				}
				unlink( $file->protected_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			}
		}
	}

	private function is_protected_path( $path, $dir ) {
		$real_path = realpath( $path );
		$real_dir  = realpath( $dir );
		return $real_path && $real_dir && 0 === strpos( $real_path, trailingslashit( $real_dir ) );
	}

	private function file_label_from_url( $file_url ) {
		$path = wp_parse_url( $file_url, PHP_URL_PATH );
		if ( $path ) {
			return basename( $path );
		}

		return __( 'Download file', 'email-required-to-download' );
	}

	private function sanitize_file_reference( $file_url ) {
		$file_url = trim( (string) $file_url );
		if ( 0 === strpos( $file_url, 'protected://' ) ) {
			return 'protected://' . sanitize_file_name( substr( $file_url, 12 ) );
		}

		return esc_url_raw( $file_url );
	}
}
