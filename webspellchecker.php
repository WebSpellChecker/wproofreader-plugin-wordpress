<?php
/**
 * Plugin Name:  WProofreader
 * Plugin URI:   https://webspellchecker.com/
 * Description:  WProofreader checks spelling, grammar, and style in real-time while editing in WordPress.
 * Version:      3.2.0
 * Author:       WebSpellChecker
 * Author URI:   https://webspellchecker.com/
 * Text Domain:  webspellchecker
 * Domain Path:  /languages
 * Requires PHP: 7.4
 * Requires at least: 6.3
 * Tested up to: 6.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WProofreader', false ) ) {
	final class WProofreader {

		const TRIAL_CUSTOMER_ID           = '1:cma3h3-HTiyU3-JL08g4-SRyuS1-a9c0F3-kH6Cu-OlMHS-thcSV2-HlGmv3-YzRCN2-qrKY42-uPc';
		const DEFAULT_LANGUAGE            = 'en_US';
		const DEFAULT_BADGE_TOGGLE_OPTION = 'on';
		const PLUGIN_VERSION              = '3.2.0';

		const SCRIPT_HANDLE_BUNDLE        = 'wsc_bundle';
		const SCRIPT_HANDLE_CONFIG        = 'wsc_config';
		const SCRIPT_HANDLE_ENV_CHECKER   = 'wsc_environment_checker';
		const SCRIPT_HANDLE_INSTANCE      = 'wsc_instance';
		const SCRIPT_HANDLE_GUTENBERG_ENV = 'wsc_gutenberg_environment';
		const SCRIPT_HANDLE_CLASSIC_ENV   = 'wsc_classic_environment';

		const L10N_OBJECT_CONFIG   = 'WSCProofreaderConfig';
		const L10N_OBJECT_INSTANCE = 'ProofreaderInstance';
		const L10N_OBJECT_SERVICE  = 'WSCServiceConfig';

		const SETTING_ENABLE_POSTS      = 'enable_on_posts';
		const SETTING_ENABLE_PAGES      = 'enable_on_pages';
		const SETTING_ENABLE_PRODUCTS   = 'enable_on_products';
		const SETTING_ENABLE_CATEGORIES = 'enable_on_categories';
		const SETTING_ENABLE_TAGS       = 'enable_on_tags';
		const SETTING_ENABLE_ADMIN      = 'enable_in_admin';
		const SETTING_ENABLE_FRONTEND   = 'enable_on_frontend';
		const SETTING_LANGUAGE          = 'slang';
		const SETTING_BADGE_BUTTON      = 'disable_badge_button';
		const SETTING_CUSTOMER_ID       = 'customer_id';

		const OPTION_ON  = 'on';
		const OPTION_OFF = 'off';

		const POST_TYPE_POST         = 'post';
		const POST_TYPE_PAGE         = 'page';
		const POST_TYPE_PRODUCT      = 'product';
		const POST_TYPE_WPSC_PRODUCT = 'wpsc-product';

		/** @var self|null */
		private static $instance = null;

		/** @var WSC_Settings */
		private $settings;

		/** @var array */
		private $options = array();

		/** @var string */
		private $plugin_url;

		/** @var string */
		private $plugin_dir;

		/** @var WProofreader_Context_Detector */
		private $context_detector;

		/** @var WProofreader_Context_Evaluator */
		private $context_evaluator;

		/** @var WProofreader_Config_Builder */
		private $config_builder;

		/** @var WProofreader_Assets */
		private $assets;

		/**
		 * Retrieve singleton instance.
		 *
		 * @return self
		 */
		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		private function __construct() {
			$this->plugin_url = plugin_dir_url( __FILE__ );
			$this->plugin_dir = plugin_dir_path( __FILE__ );

			$this->include_dependencies();
			$this->bootstrap_settings();
			$this->maybe_migrate_version();
			$this->bootstrap_services();
			$this->register_hooks();

			do_action( 'wsc_loaded' );
		}

		private function __clone() {}

		public function __wakeup() {
			throw new \RuntimeException( 'WProofreader cannot be unserialized.' );
		}

		/**
		 * Include required dependencies.
		 */
		private function include_dependencies() {
			require_once __DIR__ . '/vendor/class.settings-api.php';
			require_once __DIR__ . '/includes/class-wsc-settings.php';
			require_once __DIR__ . '/includes/class-wproofreader-context-detector.php';
			require_once __DIR__ . '/includes/class-wproofreader-context-evaluator.php';
			require_once __DIR__ . '/includes/class-wproofreader-config-builder.php';
			require_once __DIR__ . '/includes/class-wproofreader-assets.php';
			require_once __DIR__ . '/includes/class-wproofreader-ajax.php';
			require_once __DIR__ . '/includes/class-wproofreader-content-cleaner.php';
		}

		/**
		 * Initialize settings and merge with defaults.
		 */
		private function bootstrap_settings() {
			$this->settings = new WSC_Settings(
				__( 'WProofreader', 'webspellchecker' ),
				__( 'WProofreader', 'webspellchecker' ),
				'spell-checker-settings'
			);

			$default_options = array(
				self::SETTING_ENABLE_POSTS      => self::OPTION_ON,
				self::SETTING_ENABLE_PAGES      => self::OPTION_ON,
				self::SETTING_ENABLE_PRODUCTS   => self::OPTION_ON,
				self::SETTING_ENABLE_CATEGORIES => self::OPTION_ON,
				self::SETTING_ENABLE_TAGS       => self::OPTION_ON,
				self::SETTING_ENABLE_ADMIN      => self::OPTION_OFF,
				self::SETTING_ENABLE_FRONTEND   => self::OPTION_OFF,
				self::SETTING_LANGUAGE          => self::DEFAULT_LANGUAGE,
				self::SETTING_BADGE_BUTTON      => self::DEFAULT_BADGE_TOGGLE_OPTION,
			);

			$stored_options = get_option( WSC_Settings::OPTION_NAME, array() );
			$this->options  = wp_parse_args( $stored_options, $default_options );
			$this->options[ self::SETTING_CUSTOMER_ID ] = $this->get_customer_id();
		}

		/**
		 * Build collaborator objects.
		 */
		private function bootstrap_services() {
			$this->context_detector  = new WProofreader_Context_Detector();
			$this->context_evaluator = new WProofreader_Context_Evaluator( $this );
			$this->config_builder    = new WProofreader_Config_Builder( $this );
			$this->assets            = new WProofreader_Assets(
				$this,
				$this->context_detector,
				$this->context_evaluator,
				$this->config_builder,
				$this->plugin_url,
				$this->plugin_dir
			);
		}

		/**
		 * Register WordPress hooks.
		 */
		private function register_hooks() {
			add_action( 'init', array( $this, 'load_textdomain' ) );
			add_action( 'init', array( $this->assets, 'register_scripts' ) );
			add_action( 'enqueue_block_assets', array( $this->assets, 'enqueue_for_block_editor' ), 110 );
			add_action( 'admin_enqueue_scripts', array( $this->assets, 'enqueue_for_admin' ), 110 );
			add_action( 'elementor/editor/after_enqueue_scripts', array( $this->assets, 'enqueue_for_elementor_editor' ), 110 );
			add_action( 'wp_enqueue_scripts', array( $this->assets, 'enqueue_for_frontend' ), 10 );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_action_links' ) );
			add_action( 'wp_ajax_get_proofreader_info_callback', array( 'WProofreader_Ajax', 'get_proofreader_info' ) );
			add_action( 'init', array( 'WProofreader_Content_Cleaner', 'register' ) );
		}

		/**
		 * Load translations.
		 */
		public function load_textdomain() {
			load_plugin_textdomain( 'webspellchecker', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}

		/**
		 * Handle version migration and legacy cleanup.
		 *
		 * Runs in admin requests only so REST/cron/front-end loads never write options.
		 */
		private function maybe_migrate_version() {
			if ( ! is_admin() ) {
				return;
			}

			if ( get_option( 'wsc_proofreader_version' ) !== self::PLUGIN_VERSION ) {
				delete_option( 'wsc' );
				update_option( 'wsc_proofreader_version', self::PLUGIN_VERSION );
			}
		}

		/**
		 * Whether the current installation uses the Free trial service id.
		 *
		 * @return bool
		 */
		public function is_free_edition(): bool {
			return $this->get_customer_id() === self::TRIAL_CUSTOMER_ID;
		}

		/**
		 * Get customer ID or fall back to trial ID.
		 *
		 * @return string
		 */
		public function get_customer_id(): string {
			$customer_id = (string) $this->get_option( self::SETTING_CUSTOMER_ID, self::TRIAL_CUSTOMER_ID );

			return ( '' === trim( $customer_id ) ) ? self::TRIAL_CUSTOMER_ID : $customer_id;
		}

		/**
		 * Get selected language with fallback.
		 *
		 * @return string
		 */
		public function get_language(): string {
			$language = (string) $this->get_option( self::SETTING_LANGUAGE, self::DEFAULT_LANGUAGE );

			return ( '' === trim( $language ) ) ? self::DEFAULT_LANGUAGE : $language;
		}

		/**
		 * Get badge button option.
		 *
		 * @return string
		 */
		public function get_badge_button_option(): string {
			return (string) $this->get_option( self::SETTING_BADGE_BUTTON, self::DEFAULT_BADGE_TOGGLE_OPTION );
		}

		/**
		 * Get option value.
		 *
		 * @param string $name Option key.
		 * @param mixed  $default Default value.
		 * @return mixed
		 */
		public function get_option( $name, $default = '' ) {
			return isset( $this->options[ $name ] ) ? $this->options[ $name ] : $default;
		}

		/**
		 * Get all normalized options.
		 *
		 * @return array
		 */
		public function get_options(): array {
			return $this->options;
		}

		/**
		 * Add settings link in plugins list.
		 *
		 * @param array $links Existing action links.
		 * @return array
		 */
		public function add_action_links( $links ) {
			$url = esc_url( add_query_arg( 'page', 'spell-checker-settings', admin_url( 'options-general.php' ) ) );

			$settings_link = sprintf(
				'<a href="%s">%s</a>',
				$url,
				esc_html__( 'Settings', 'webspellchecker' )
			);

			return array_merge( $links, array( $settings_link ) );
		}
	}
}

if ( ! function_exists( 'wsc_proofreader' ) ) {
	/**
	 * Get singleton instance.
	 *
	 * @return WProofreader
	 */
	function wsc_proofreader() {
		return WProofreader::instance();
	}
}

wsc_proofreader();
