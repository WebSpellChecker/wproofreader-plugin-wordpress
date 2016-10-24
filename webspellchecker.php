<?php
/**
* Plugin Name: WebSpellChecker
* Description: WebSpellChecker extension for Wordpress
* Version:     1.0
* Author:      WebSpellChecker LLC
* Author URI:  http://www.webspellchecker.net/
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

final class WebSpellChecker {

	private static $instance = null;

	private $settings;

	public static function instance() {
		// If an instance hasn't been created and set to $instance create an instance and set it to $instance.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function __construct() {
		$this->includes();
		$this->settings = new WSC_Settings(
			__( 'Spell Shecker Settings', 'webspellchecker' ),
			__( 'Spell Shecker', 'webspellchecker' ),
			'spell-checker-settings'
		);

		do_action( 'wsc_loaded' );
	}

	public function includes() {
		require_once dirname( __FILE__ ) . '/vendor/class.settings-api.php';
		require_once dirname( __FILE__ ) . '/includes/class-wsc-settings.php';
	}
}

function WSC() {
	return WebSpellChecker::instance();
}
WSC();
