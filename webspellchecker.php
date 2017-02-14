<?php
/**
 * Plugin Name: WebSpellChecker
 * Description: WebSpellChecker extension for Wordpress
 * Version:     1.0
 * Author:      TeamDev Ltd
 * Author URI:  https://www.teamdev.com/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

final class WebSpellChecker {

        const TRIAL_CUSTOMER_ID = '1:nduHS3-xuxDG1-jCbTv4-mn7Il4-kRrAg3-nT2s44-3bH6Q3-LlSLf-ALWjs3-xBM9g2-EEfe53-Yv9';
    
	private static $instance = null;

	private $js_added = false;
	
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
			__( 'WebSpellChecker', 'webspellchecker' ),
			__( 'WebSpellChecker', 'webspellchecker' ),
			'spell-checker-settings'
		);
		
		// text and textarea fields
		foreach ($this->options as $option => $on ) {
			if( $option !== 'visual_editor' && $on === 'on' ){
				add_action( 'admin_enqueue_scripts', array( $this, 'register_textarea_scayt' ) );
				$this->init_scayt_js();
				break;
			}
		}	

		// WYSIWYG
		if ( 'on' == $this->get_option('visual_editor') ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'register_textarea_scayt' ) );
			$this->init_tinymce_scayt();
		}
		
		// ACF
		if ( 'on' == $this->get_option('acf_fields') ) {
			add_action( 'acf/create_field', array( $this, 'create_field_for_js' ) );
		}
		
		do_action( 'wsc_loaded' );
	}

	public function includes() {
		require_once dirname( __FILE__ ) . '/vendor/class.settings-api.php';
		require_once dirname( __FILE__ ) . '/includes/class-wsc-settings.php';
	}

	public function register_textarea_scayt() {
		wp_enqueue_script( 'webspellchecker_hosted', 'http://svc.webspellchecker.net/spellcheck31/lf/scayt3/scayt/scayt.js' );
		wp_localize_script( 'webspellchecker_hosted', 'webSpellChecker',
			array(
				'options' => apply_filters( 'wsc_options_in_js', $this->options )
			)
		);
	}

	public function init_scayt_js() {
		add_action( 'after_wp_tiny_mce', array( $this, 'add_scayt_js' ) );
	}
	
	public function init_tinymce_scayt() {
		add_action( 'after_wp_tiny_mce', array( $this, 'register_tinymce_plugins' ) );
		add_filter( 'tiny_mce_before_init', array( $this, 'add_scayt_init_settings' ) );
	}

	public function register_tinymce_plugins() {
		printf( '<script type="text/javascript" src="%s"></script>', plugin_dir_url( __FILE__ ) . '/assets/tinymce/scayt/plugin.js' );
		printf( '<script type="text/javascript" src="%s"></script>', plugin_dir_url( __FILE__ ) . '/assets/tinymce/contextmenu/plugin.js' );
		$this->add_scayt_js();
	}
	
	public function add_scayt_js() {
		if( !$this->js_added ){
			printf( '<script type="text/javascript" src="%s"></script>', plugin_dir_url( __FILE__ ) . '/assets/scayt_textarea.js' );
		}
		$this->js_added = true;
	}

	public function add_scayt_init_settings( $init ) {
		$scayt_settings = array(
			'plugins'                     => $init['plugins'] . ',' . 'scayt,contextmenu',
			'toolbar4'                    => "scayt",
			'scayt_autoStartup'           => true,
			'scayt_customerId'            => $this->get_customer_id(),
			'scayt_moreSuggestions'       => 'on',
			'scayt_contextCommands'       => "add,ignore",
			'scayt_contextMenuItemsOrder' => "control,moresuggest,suggest",
			'scayt_maxSuggestions'        => 6,
			'scayt_minWordLength'         => 4,
			'scayt_slang'                 => $this->get_option('slang'),
			'scayt_uiTabs'                => "1,0,1",
			'scayt_customDictionaryIds'   => "1,3001",
			'scayt_userDictionaryName'    => "test_dic",
			'scayt_context_mode'          => "default",
			'scayt_elementsToIgnore'      => "del,pre",
			'browser_spellcheck'          => false
		);

		return array_merge( $init, $scayt_settings );
	}

	/**
         * Get customer ID or trial ID if not set
         * 
         * @return string customer ID
         */
        public function get_customer_id() {
            $customer_id = $this->get_option( 'customer_id', self::TRIAL_CUSTOMER_ID );
            if( empty($customer_id) ) {
                return self::TRIAL_CUSTOMER_ID;
            }
	    return $customer_id;
	}
        
	public function get_option( $name, $default = '' ) {
		return ( isset( $this->options[ $name ] ) ) ? $this->options[ $name ] : $default;
	}
	
	/**
	 * Create element with data-id equal acf field id
	 * @param array $options
	 */
	public function create_field_for_js( $options ) {
		if ( $options['type'] == 'text'  || $options['type'] == 'textarea' ) {
			echo '<div class="wsc_field" data-id="'.$options['id'].'"></div>';
		}
	}
	
}

function WSC() {
	return WebSpellChecker::instance();
}

WSC();
