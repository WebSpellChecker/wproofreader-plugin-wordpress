<?php

/**
 * The code in this file runs when a plugin is uninstalled from the WordPress dashboard.
 */

/* If uninstall is not called from WordPress exit. */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove all plugin options for the current site.
 */
function wsc_proofreader_uninstall_site() {
	delete_option( 'wsc' );
	delete_option( 'wsc_proofreader_version' );
	delete_option( 'wsc_proofreader_info' );
	delete_option( 'wsc_proofreader' );
	delete_transient( 'wsc_proofreader_info_cache' );
}

if ( is_multisite() ) {
	$wsc_site_ids = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );
	foreach ( $wsc_site_ids as $wsc_site_id ) {
		switch_to_blog( $wsc_site_id );
		wsc_proofreader_uninstall_site();
		restore_current_blog();
	}
} else {
	wsc_proofreader_uninstall_site();
}
