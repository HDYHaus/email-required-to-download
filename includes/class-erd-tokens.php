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
		return base64_encode( $payload . '|' . $sig ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	public static function parse( $token ) {
		$decoded = base64_decode( sanitize_text_field( wp_unslash( $token ) ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( false === $decoded ) {
			return false;
		}

		$parts = explode( '|', $decoded );
		if ( 5 !== count( $parts ) ) {
			return false;
		}

		list( $lead_id, $offer_id, $expires, $nonce, $signature ) = $parts;
		$payload  = implode( '|', array( $lead_id, $offer_id, $expires, $nonce ) );
		$expected = hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );

		if ( ! hash_equals( $expected, $signature ) || time() > absint( $expires ) ) {
			return false;
		}

		return array(
			'lead_id'  => absint( $lead_id ),
			'offer_id' => absint( $offer_id ),
			'expires'  => absint( $expires ),
		);
	}
}
