<?php
/**
 * Signed download token helpers.
 *
 * @package EmailRequiredDownload
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ERD_Tokens {
	public static function create( $lead_id, $offer_id, $expires ) {
		$payload = implode( '|', array( absint( $lead_id ), absint( $offer_id ), absint( $expires ), wp_generate_password( 16, false ) ) );
		$sig     = hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );
		$token   = base64_encode( $payload . '|' . $sig ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return rtrim( strtr( $token, '+/', '-_' ), '=' );
	}

	public static function parse( $token, &$failure_reason = null ) {
		$token   = sanitize_text_field( wp_unslash( $token ) );
		$base64  = strtr( $token, '-_', '+/' );
		$padding = strlen( $base64 ) % 4;
		if ( 1 === $padding ) {
			$failure_reason = 'invalid_base64_length';
			return false;
		}
		if ( $padding ) {
			$base64 .= str_repeat( '=', 4 - $padding );
		}

		$decoded = base64_decode( $base64, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( false === $decoded ) {
			$failure_reason = 'invalid_base64';
			return false;
		}

		$parts = explode( '|', $decoded );
		if ( 5 !== count( $parts ) ) {
			$failure_reason = 'payload_parts_mismatch';
			return false;
		}

		list( $lead_id, $offer_id, $expires, $nonce, $signature ) = $parts;
		$payload  = implode( '|', array( $lead_id, $offer_id, $expires, $nonce ) );
		$expected = hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );

		if ( ! hash_equals( $expected, $signature ) ) {
			$failure_reason = 'signature_mismatch';
			return false;
		}

		if ( time() > absint( $expires ) ) {
			$failure_reason = 'expired';
			return false;
		}

		return array(
			'lead_id'  => absint( $lead_id ),
			'offer_id' => absint( $offer_id ),
			'expires'  => absint( $expires ),
		);
	}

	public static function storage_candidates( $token ) {
		$token    = sanitize_text_field( wp_unslash( $token ) );
		$base64   = strtr( $token, '-_', '+/' );
		$padding  = strlen( $base64 ) % 4;
		if ( 1 === $padding ) {
			return array( $token );
		}
		if ( $padding ) {
			$base64 .= str_repeat( '=', 4 - $padding );
		}

		$decoded = base64_decode( $base64, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( false === $decoded ) {
			return array( $token );
		}

		$standard = base64_encode( $decoded ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$url_safe = rtrim( strtr( $standard, '+/', '-_' ), '=' );
		return array_values( array_unique( array( $token, $standard, $url_safe ) ) );
	}

	public static function log_validation_failure( $reason ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( '[Email Required to Download] Download token validation failed: ' . sanitize_key( $reason ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}
