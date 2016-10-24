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

		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );
		do_action( 'wsc_loaded' );
	}

	public function includes() {
		require_once dirname( __FILE__ ) . '/vendor/class.settings-api.php';
		require_once dirname( __FILE__ ) . '/includes/class-wsc-settings.php';
	}

	public function register_scripts() {
		$spellchecker_options = get_option( WSC_Settings::OPTION_NAME );
		$hosted_src           = ''; // todo wsc url
		$spellchecker_init    = 'wsc.js';
		if ( 'scayt' == $spellchecker_options['type'] ) {
			$hosted_src        = 'http://svc.webspellchecker.net/spellcheck31/lf/scayt3/scayt/scayt.js';
			$spellchecker_init = 'scayt.js';
		}
		wp_enqueue_script( 'webspellchecker_hosted', $hosted_src );
		wp_enqueue_script( 'webspellchecker', plugin_dir_url( __FILE__ ) . '/assets/' . $spellchecker_init, array(), '', true );

		wp_localize_script( 'webspellchecker', 'webSpellChecker',
			array(
				'options' => $spellchecker_options
			)
		);
	}
}

function WSC() {
	return WebSpellChecker::instance();
}

WSC();
