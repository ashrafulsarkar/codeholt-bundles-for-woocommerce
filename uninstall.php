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

delete_option( 'cbfw_version' );
delete_option( 'cbfw_settings' );

// Remove cached analytics transients.
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_cbfw\\_%' OR option_name LIKE '\\_transient\\_timeout\\_cbfw\\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
