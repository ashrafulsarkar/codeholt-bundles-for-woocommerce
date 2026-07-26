<?php
/**
 * Uninstall cleanup.
 *
 * Bundle products themselves are NOT deleted — they are the store owner's
 * content. Only plugin options and cached data are removed.
 *
 * @package BPFW
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'bpfw_version' );
delete_option( 'bpfw_settings' );

// Remove cached analytics transients.
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_bpfw\\_%' OR option_name LIKE '\\_transient\\_timeout\\_bpfw\\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
