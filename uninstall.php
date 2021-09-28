<?php

/**
 * The code in this file runs when a plugin is uninstalled from the WordPress dashboard.
 */

/* If uninstall is not called from WordPress exit. */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit ();
}

/* Place uninstall code below here. */

delete_option( 'wsc' );
delete_option( 'wsc_proofreader_version' );
delete_option( 'wsc_proofreader_info' );
delete_option( 'wsc_proofreader' );