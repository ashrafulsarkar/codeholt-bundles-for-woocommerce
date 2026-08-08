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
	delete_option( 'cbfw_installed' );
	delete_option( 'cbfw_review_dismissed' );
	delete_option( 'cbfw_review_later' );

	// Analytics transients are keyed by reporting period, so their names can't
	// be enumerated up front. A direct, uncached query is the only way to find
	// them; the results are consumed once via delete_transient() below, so no
	// caching applies.
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$periods = $wpdb->get_col(
		"SELECT option_name FROM {$wpdb->options}
		 WHERE option_name LIKE '\\_transient\\_cbfw\\_%'
		    OR option_name LIKE '\\_transient\\_timeout\\_cbfw\\_%'"
	);
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	foreach ( (array) $periods as $option_name ) {
		$key = preg_replace( '/^_transient_(timeout_)?/', '', $option_name );
		delete_transient( $key );
	}

	// Sweep up anything the object cache did not account for. Uninstall runs
	// once and nothing is cached afterwards, so no caching applies here either.
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		"DELETE FROM {$wpdb->options}
		 WHERE option_name LIKE '\\_transient\\_cbfw\\_%'
		    OR option_name LIKE '\\_transient\\_timeout\\_cbfw\\_%'"
	);
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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
