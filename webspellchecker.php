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

	protected $options;

	public static function instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function __construct() {
		$this->includes();

		$this->options = get_option( WSC_Settings::OPTION_NAME );

		$this->settings = new WSC_Settings(
			__( 'Spell Shecker Settings', 'webspellchecker' ),
			__( 'Spell Shecker', 'webspellchecker' ),
			'spell-checker-settings'
		);

		if ( 'on' == $this->options['text_editor'] ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );
		}

		if ( 'on' == $this->options['visual_editor'] ) {
			add_filter( 'tiny_mce_before_init', array( $this, 'scayt_init' ) );
		}

		do_action( 'wsc_loaded' );
	}

	public function includes() {
		require_once dirname( __FILE__ ) . '/vendor/class.settings-api.php';
		require_once dirname( __FILE__ ) . '/includes/class-wsc-settings.php';
	}

	public function register_scripts() {
		wp_enqueue_script( 'webspellchecker_hosted', 'http://svc.webspellchecker.net/spellcheck31/lf/scayt3/scayt/scayt.js' );
		wp_enqueue_script( 'webspellchecker', plugin_dir_url( __FILE__ ) . '/assets/scayt.js', array(), '', true );
		wp_localize_script( 'webspellchecker', 'webSpellChecker',
			array(
				'options' => $this->options
			)
		);
	}

	public function scayt_init( $init ) {
		$scayt_init = array(
			'plugins'                     => $init['plugins'] . ',' . 'scayt link image table contextmenu',
			'toolbar5'                    => 'scayt undo redo | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist | link image',
			'scayt_autoStartup'           => true,
			'scayt_customerId'            => WSC_Settings::TRIAL_CUSTOMER_ID,
			'scayt_moreSuggestions'       => 'on',
			'scayt_contextCommands'       => "add,ignore",
			'scayt_contextMenuItemsOrder' => "control,moresuggest,suggest",
			'scayt_maxSuggestions'        => 6,
			'scayt_minWordLength'         => 4,
			'scayt_slang'                 => "en_US",
			'scayt_uiTabs'                => "1,0,1",
			'scayt_customDictionaryIds'   => "1,3001",
			'scayt_userDictionaryName'    => "test_dic",
			'scayt_context_mode'          => "default",
			'scayt_elementsToIgnore'      => "del,pre"
		);
		$new_init   = array_merge( $init, $scayt_init );

		return array_merge( $init, $scayt_init );
	}
}

function WSC() {
	return WebSpellChecker::instance();
}

WSC();
