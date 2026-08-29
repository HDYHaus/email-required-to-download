<?php
/**
 * Uninstall handler.
 *
 * @package EmailRequiredDownload
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Plugin-owned custom tables must be removed directly on uninstall.
// phpcs:disable WordPress.DB.DirectDatabaseQuery

global $wpdb;

$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}erd_leads" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}erd_offer_files" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}erd_offers" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

delete_option( 'erd_db_version' );
delete_option( 'erd_settings' );

$protected_dir = trailingslashit( WP_CONTENT_DIR ) . 'erd-protected';
if ( is_dir( $protected_dir ) ) {
	$visible_files = glob( trailingslashit( $protected_dir ) . '*' );
	$hidden_files  = glob( trailingslashit( $protected_dir ) . '.*' );
	$files         = array_merge( is_array( $visible_files ) ? $visible_files : array(), is_array( $hidden_files ) ? $hidden_files : array() );
	if ( is_array( $files ) ) {
		foreach ( $files as $file ) {
			if ( is_file( $file ) && ! in_array( basename( $file ), array( '.', '..' ), true ) ) {
				unlink( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			}
		}
	}
	rmdir( $protected_dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
}
