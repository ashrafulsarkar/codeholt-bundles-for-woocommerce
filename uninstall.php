<?php
/**
 * Uninstall cleanup.
 *
 * Bundle products themselves are NOT deleted — they are the store owner's
 * content. Only plugin options and cached data are removed.
 *
 * @package CBFW
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Remove this plugin's options and cached analytics for the current site.
 */
function cbfw_uninstall_site() {
	global $wpdb;

	delete_option( 'cbfw_version' );
	delete_option( 'cbfw_settings' );

	// Analytics transients are keyed by reporting period. Delete them through
	// the API so a persistent object cache is cleared too.
	$periods = $wpdb->get_col(
		"SELECT option_name FROM {$wpdb->options}
		 WHERE option_name LIKE '\\_transient\\_cbfw\\_%'
		    OR option_name LIKE '\\_transient\\_timeout\\_cbfw\\_%'"
	); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

	foreach ( (array) $periods as $option_name ) {
		$key = preg_replace( '/^_transient_(timeout_)?/', '', $option_name );
		delete_transient( $key );
	}

	// Sweep up anything the object cache did not account for.
	$wpdb->query(
		"DELETE FROM {$wpdb->options}
		 WHERE option_name LIKE '\\_transient\\_cbfw\\_%'
		    OR option_name LIKE '\\_transient\\_timeout\\_cbfw\\_%'"
	); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

if ( is_multisite() ) {
	$cbfw_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $cbfw_site_ids as $cbfw_site_id ) {
		switch_to_blog( $cbfw_site_id );
		cbfw_uninstall_site();
		restore_current_blog();
	}
} else {
	cbfw_uninstall_site();
}
