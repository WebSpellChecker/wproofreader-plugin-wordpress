<?php
/**
 * Plugin Name:  WProofreader
 * Plugin URI:   https://webspellchecker.com/
 * Description:  WProofreader checks spelling, grammar, and style in real-time while editing in WordPress.
 * Version:      3.0.0
 * Author:       WebSpellChecker
 * Author URI:   https://webspellchecker.com/
 * Text Domain:  webspellchecker
 * Domain Path:  /languages
 * Requires PHP: 7.4
 * Requires at least: 6.3
 * Tested up to: 6.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WProofreader {

	const TRIAL_CUSTOMER_ID           = '1:cma3h3-HTiyU3-JL08g4-SRyuS1-a9c0F3-kH6Cu-OlMHS-thcSV2-HlGmv3-YzRCN2-qrKY42-uPc';
	const DEFAULT_LANGUAGE            = 'en_US';
	const DEFAULT_BADGE_TOGGLE_OPTION = 'on';
	const PLUGIN_VERSION              = '3.0.0';

	const SCRIPT_HANDLE_BUNDLE        = 'wsc_bundle';
	const SCRIPT_HANDLE_CONFIG        = 'wsc_config';
	const SCRIPT_HANDLE_ENV_CHECKER   = 'wsc_environment_checker';
	const SCRIPT_HANDLE_INSTANCE      = 'wsc_instance';
	const SCRIPT_HANDLE_GUTENBERG_ENV = 'wsc_gutenberg_environment';

	const L10N_OBJECT_CONFIG   = 'WSCProofreaderConfig';
	const L10N_OBJECT_INSTANCE = 'ProofreaderInstance';

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

	const SCREEN_EDIT_CATEGORY         = 'edit-category';
	const SCREEN_EDIT_PRODUCT_CATEGORY = 'edit-product_cat';
	const SCREEN_EDIT_WPSC_PRODUCT_CAT = 'edit-wpsc_product_category';
	const SCREEN_EDIT_PRODUCT_TAG      = 'edit-product_tag';
	const SCREEN_EDIT_POST_TAG         = 'edit-post_tag';

	const POST_TYPE_POST         = 'post';
	const POST_TYPE_PAGE         = 'page';
	const POST_TYPE_PRODUCT      = 'product';
	const POST_TYPE_WPSC_PRODUCT = 'wpsc-product';

	/** @var self|null */
	private static $instance = null;

	/** @var WSC_Settings */
	private $settings;

	/** @var array */
	protected $options = array();

	/** @var string */
	private $plugin_url;

	/** @var string */
	private $plugin_dir;

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

	public function __construct() {
		$this->plugin_url = plugin_dir_url( __FILE__ );
		$this->plugin_dir = plugin_dir_path( __FILE__ );

		add_action( 'init', array( $this, 'load_textdomain' ) );

		$this->include_dependencies();
		$this->bootstrap_settings();
		$this->maybe_migrate_version();

		add_action( 'init', array( $this, 'register_scripts' ) );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_for_block_editor' ), 110 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_for_admin' ), 110 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_for_frontend' ), 10 );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_action_links' ) );
		add_action( 'wp_ajax_get_proofreader_info_callback', array( $this, 'ajax_get_proofreader_info' ) );
		add_action( 'init', array( __CLASS__, 'register_content_cleanup_filter' ) );

		do_action( 'wsc_loaded' );
	}

	/**
	 * Include required dependencies.
	 */
	private function include_dependencies() {
		require_once __DIR__ . '/vendor/class.settings-api.php';
		require_once __DIR__ . '/includes/class-wsc-settings.php';
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
			self::SETTING_ENABLE_PRODUCTS   => self::OPTION_OFF,
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

		if ( ! get_option( 'wsc_proofreader_version' ) ) {
			update_option( 'wsc_proofreader_version', self::PLUGIN_VERSION );
		}
	}

	/**
	 * Load translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'webspellchecker', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Handle version migration and legacy cleanup.
	 */
	private function maybe_migrate_version() {
		if ( get_option( 'wsc_proofreader_version' ) !== self::PLUGIN_VERSION ) {
			delete_option( 'wsc' );
			update_option( 'wsc_proofreader_version', self::PLUGIN_VERSION );
		}
	}

	/**
	 * Register all plugin scripts.
	 */
	public function register_scripts() {
		$asset_version_for = function ( $relative_path ) {
			if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
				return time();
			}
			$file_path = $this->plugin_dir . ltrim( $relative_path, '/\\' );
			$mtime     = @filemtime( $file_path );
			return $mtime ? (string) $mtime : self::PLUGIN_VERSION;
		};

		wp_register_script(
			self::SCRIPT_HANDLE_BUNDLE,
			'https://svc.webspellchecker.net/spellcheck31/wscbundle/wscbundle.js',
			array(),
			null,
			true
		);

		wp_register_script(
			self::SCRIPT_HANDLE_CONFIG,
			$this->plugin_url . 'assets/proofreaderConfig.js',
			array(),
			$asset_version_for( 'assets/proofreaderConfig.js' ),
			false
		);

		wp_register_script(
			self::SCRIPT_HANDLE_ENV_CHECKER,
			$this->plugin_url . 'assets/environmentChecker.js',
			array(),
			$asset_version_for( 'assets/environmentChecker.js' ),
			false
		);

		wp_register_script(
			self::SCRIPT_HANDLE_INSTANCE,
			$this->plugin_url . 'assets/instance.js',
			array(),
			$asset_version_for( 'assets/instance.js' ),
			true
		);

		wp_register_script(
			self::SCRIPT_HANDLE_GUTENBERG_ENV,
			$this->plugin_url . 'assets/gutenberg-environment.js',
			array(),
			$asset_version_for( 'assets/gutenberg-environment.js' ),
			true
		);
	}

	/**
	 * Get current admin screen if available.
	 *
	 * @return WP_Screen|null
	 */
	private function get_current_admin_screen() {
		return function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	}

	/**
	 * Check if current screen is the block editor.
	 *
	 * @param WP_Screen|null $screen
	 * @return bool
	 */
	private function is_block_editor_screen( $screen ): bool {
		return ( $screen && method_exists( $screen, 'is_block_editor' ) && $screen->is_block_editor() );
	}

	/**
	 * Check if current screen is the site editor.
	 *
	 * @param WP_Screen|null $screen
	 * @return bool
	 */
	private function is_site_editor_screen( $screen ): bool {
		if ( ! $screen ) {
			return false;
		}

		if ( ( isset( $screen->id ) && 'site-editor' === $screen->id ) ||
		     ( isset( $screen->base ) && 'site-editor' === $screen->base ) ) {
			return true;
		}

		if ( isset( $screen->base ) && false !== strpos( $screen->base, 'gutenberg-edit-site' ) ) {
			return true;
		}

		if ( isset( $_SERVER['PHP_SELF'] ) && false !== strpos( $_SERVER['PHP_SELF'], 'site-editor.php' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if current screen is the permalinks settings page.
	 *
	 * @param WP_Screen|null $screen
	 * @return bool
	 */
	private function is_permalinks_settings_screen( $screen ): bool {
		if ( ! $screen ) {
			return false;
		}

		if ( ( isset( $screen->id ) && 'options-permalink' === $screen->id ) ||
		     ( isset( $screen->base ) && 'options-permalink' === $screen->base ) ) {
			return true;
		}

		if ( isset( $_SERVER['PHP_SELF'] ) && false !== strpos( $_SERVER['PHP_SELF'], 'options-permalink.php' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Detect current admin context.
	 *
	 * @return array
	 */
	private function detect_current_context(): array {
		$screen = $this->get_current_admin_screen();

		$ctx = array(
			'place'       => 'other',
			'screen_id'   => $screen->id ?? '',
			'screen_base' => $screen->base ?? '',
			'post_type'   => $screen->post_type ?? '',
		);

		if ( 'settings_page_spell-checker-settings' === $ctx['screen_base'] ) {
			$ctx['place'] = 'settings';
			return $ctx;
		}

		if ( in_array(
			$ctx['screen_id'],
			array(
				self::SCREEN_EDIT_CATEGORY,
				self::SCREEN_EDIT_PRODUCT_CATEGORY,
				self::SCREEN_EDIT_WPSC_PRODUCT_CAT,
				self::SCREEN_EDIT_PRODUCT_TAG,
				self::SCREEN_EDIT_POST_TAG,
			),
			true
		) ) {
			$ctx['place'] = 'taxonomy';
			return $ctx;
		}

		if ( ! empty( $ctx['post_type'] ) ) {
			$ctx['place'] = 'post_type';
			return $ctx;
		}

		return $ctx;
	}

	/**
	 * Decide whether WProofreader should be enabled in current context.
	 *
	 * @param array $context
	 * @return bool
	 */
	private function should_enable_in_context( array $context ): bool {
		$place     = $context['place'] ?? 'other';
		$screen_id = $context['screen_id'] ?? '';
		$post_type = $context['post_type'] ?? '';

		if ( 'settings' === $place ) {
			return true;
		}

		$admin_on       = ( self::OPTION_ON === $this->get_option( self::SETTING_ENABLE_ADMIN, self::OPTION_OFF ) );
		$admin_behavior = apply_filters( 'wproofreader_admin_behavior', 'with_exclusions' );

		if ( $admin_on ) {
			if ( 'override' === $admin_behavior ) {
				return true;
			}

			if ( 'taxonomy' === $place ) {
				if ( in_array(
					$screen_id,
					array(
						self::SCREEN_EDIT_CATEGORY,
						self::SCREEN_EDIT_PRODUCT_CATEGORY,
						self::SCREEN_EDIT_WPSC_PRODUCT_CAT,
					),
					true
				) ) {
					return ( self::OPTION_ON === $this->get_option( self::SETTING_ENABLE_CATEGORIES, self::OPTION_ON ) );
				}
				if ( in_array(
					$screen_id,
					array(
						self::SCREEN_EDIT_PRODUCT_TAG,
						self::SCREEN_EDIT_POST_TAG,
					),
					true
				) ) {
					return ( self::OPTION_ON === $this->get_option( self::SETTING_ENABLE_TAGS, self::OPTION_ON ) );
				}
				return true;
			}

			if ( 'post_type' === $place ) {
				if ( self::POST_TYPE_POST === $post_type ) {
					return ( self::OPTION_ON === $this->get_option( self::SETTING_ENABLE_POSTS, self::OPTION_ON ) );
				}
				if ( self::POST_TYPE_PAGE === $post_type ) {
					return ( self::OPTION_ON === $this->get_option( self::SETTING_ENABLE_PAGES, self::OPTION_ON ) );
				}
				if ( in_array( $post_type, array( self::POST_TYPE_PRODUCT, self::POST_TYPE_WPSC_PRODUCT ), true ) ) {
					return ( self::OPTION_ON === $this->get_option( self::SETTING_ENABLE_PRODUCTS, self::OPTION_OFF ) );
				}

				$additional = apply_filters( 'wproofreader_add_cpt', array() );
				if ( is_array( $additional ) ) {
					foreach ( $additional as $cpt ) {
						if ( 0 === strcasecmp( (string) $cpt, (string) $post_type ) ) {
							return true;
						}
					}
				}
				return true;
			}

			return true;
		}

		if ( 'taxonomy' === $place ) {
			if ( in_array(
				$screen_id,
				array(
					self::SCREEN_EDIT_CATEGORY,
					self::SCREEN_EDIT_PRODUCT_CATEGORY,
					self::SCREEN_EDIT_WPSC_PRODUCT_CAT,
				),
				true
			) ) {
				return ( self::OPTION_ON === $this->get_option( self::SETTING_ENABLE_CATEGORIES, self::OPTION_ON ) );
			}
			if ( in_array(
				$screen_id,
				array(
					self::SCREEN_EDIT_PRODUCT_TAG,
					self::SCREEN_EDIT_POST_TAG,
				),
				true
			) ) {
				return ( self::OPTION_ON === $this->get_option( self::SETTING_ENABLE_TAGS, self::OPTION_ON ) );
			}
			return false;
		}

		if ( 'post_type' === $place ) {
			if ( self::POST_TYPE_POST === $post_type ) {
				return ( self::OPTION_ON === $this->get_option( self::SETTING_ENABLE_POSTS, self::OPTION_ON ) );
			}
			if ( self::POST_TYPE_PAGE === $post_type ) {
				return ( self::OPTION_ON === $this->get_option( self::SETTING_ENABLE_PAGES, self::OPTION_ON ) );
			}
			if ( in_array( $post_type, array( self::POST_TYPE_PRODUCT, self::POST_TYPE_WPSC_PRODUCT ), true ) ) {
				return ( self::OPTION_ON === $this->get_option( self::SETTING_ENABLE_PRODUCTS, self::OPTION_OFF ) );
			}

			$additional = apply_filters( 'wproofreader_add_cpt', array() );
			if ( is_array( $additional ) ) {
				foreach ( $additional as $cpt ) {
					if ( 0 === strcasecmp( (string) $cpt, (string) $post_type ) ) {
						return true;
					}
				}
			}
			return false;
		}

		return false;
	}

	/**
	 * Convert booleans to string for JS.
	 *
	 * @param mixed $value
	 * @return string
	 */
	private function bool_to_js_string( $value ): string {
		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}
		return (string) $value;
	}

	/**
	 * Build localization payload for vendor scripts.
	 *
	 * @param array $overrides
	 * @return array
	 */
	private function build_localized_config( array $overrides = array() ): array {
		$customer_id       = $this->get_customer_id();
		$is_trial          = ( $customer_id === self::TRIAL_CUSTOMER_ID );
		$is_badge_disabled = ( $this->get_badge_button_option() === self::DEFAULT_BADGE_TOGGLE_OPTION );

		$base_config = array(
			'key_for_proofreader' => $customer_id,
			'slang'               => $this->get_language(),
			'settingsSections'    => $is_trial
				? array( 'options', 'languages', 'about', 'general' )
				: array( 'options', 'languages', 'dictionaries', 'about', 'general' ),
			'enableGrammar'       => $this->bool_to_js_string( ! $is_trial ),
			'disableBadgeButton'  => $this->bool_to_js_string( $is_badge_disabled ),
			'enableBadgeButton'   => $this->bool_to_js_string( ! $is_badge_disabled ),
			'globalBadge'         => $this->bool_to_js_string( true ),
			'compactBadge'        => $this->bool_to_js_string( true ),
		);

		$config = wp_parse_args( $overrides, $base_config );

		foreach ( $config as $key => $val ) {
			if ( is_bool( $val ) ) {
				$config[ $key ] = $this->bool_to_js_string( $val );
			}
		}

		return $config;
	}

	/**
	 * Enqueue assets for the block editor.
	 */
	public function enqueue_for_block_editor() {
		if ( ! is_admin() ) {
			return;
		}

		$screen = $this->get_current_admin_screen();

		if ( ! $this->is_block_editor_screen( $screen ) || $this->is_site_editor_screen( $screen ) ) {
			return;
		}

		$context = $this->detect_current_context();
		if ( ! apply_filters( 'wproofreader_should_enable_here', $this->should_enable_in_context( $context ), $context, $this->options ) ) {
			return;
		}

		wp_enqueue_script( self::SCRIPT_HANDLE_CONFIG );
		wp_enqueue_script( self::SCRIPT_HANDLE_ENV_CHECKER );
		wp_enqueue_script( self::SCRIPT_HANDLE_GUTENBERG_ENV );

		wp_localize_script(
			self::SCRIPT_HANDLE_CONFIG,
			self::L10N_OBJECT_CONFIG,
			$this->build_localized_config()
		);
	}

	/**
	 * Enqueue assets for classic admin pages.
	 *
	 * @param string $hook_suffix
	 */
	public function enqueue_for_admin( $hook_suffix ) {
		if ( ! is_admin() ) {
			return;
		}

		$screen = $this->get_current_admin_screen();
		if ( ! $screen ) {
			return;
		}

		if ( $this->is_block_editor_screen( $screen ) || $this->is_site_editor_screen( $screen ) ) {
			return;
		}

		if ( 'plugins' === ( $screen->base ?? '' ) ) {
			return;
		}

		if ( $this->is_permalinks_settings_screen( $screen ) ) {
			return;
		}

		if ( 'settings_page_spell-checker-settings' === ( $screen->base ?? '' ) ) {
			$this->enqueue_settings_demo();
			return;
		}

		$context = $this->detect_current_context();

		if ( ! apply_filters( 'wproofreader_should_enable_here', $this->should_enable_in_context( $context ), $context, $this->options ) ) {
			return;
		}

		$wsc_bundle_conf = array( 'globalBadge' => $this->bool_to_js_string( false ) );

		$is_single_editor_screen = ( isset( $screen->base ) && 'post' === $screen->base );
		$post_type               = $context['post_type'] ?? '';

		if ( $is_single_editor_screen && in_array(
			$post_type,
			array(
				self::POST_TYPE_POST,
				self::POST_TYPE_PAGE,
			),
			true
		) ) {
			$wsc_bundle_conf = array( 'globalBadge' => $this->bool_to_js_string( true ) );
		}

		wp_enqueue_script( self::SCRIPT_HANDLE_CONFIG );
		wp_localize_script(
			self::SCRIPT_HANDLE_CONFIG,
			self::L10N_OBJECT_CONFIG,
			$this->build_localized_config( $wsc_bundle_conf )
		);
		wp_enqueue_script( self::SCRIPT_HANDLE_BUNDLE );
	}

	/**
	 * Enqueue assets on the frontend.
	 */
	public function enqueue_for_frontend() {
		if ( self::OPTION_ON !== $this->get_option( self::SETTING_ENABLE_FRONTEND, self::OPTION_OFF ) ) {
			return;
		}

		wp_enqueue_script( self::SCRIPT_HANDLE_CONFIG );
		wp_localize_script(
			self::SCRIPT_HANDLE_CONFIG,
			self::L10N_OBJECT_CONFIG,
			$this->build_localized_config( array( 'globalBadge' => $this->bool_to_js_string( false ) ) )
		);
		wp_enqueue_script( self::SCRIPT_HANDLE_BUNDLE );
	}

	/**
	 * Enqueue the settings page demo instance.
	 */
	private function enqueue_settings_demo() {
		$ajax_nonce         = wp_create_nonce( 'webspellchecker-proofreader' );
		$customer_id        = $this->get_customer_id();
		$is_grammar_enabled = ( $customer_id !== self::TRIAL_CUSTOMER_ID );

		$demo_payload = array(
			'key_for_proofreader' => $customer_id,
			'slang'               => $this->get_language(),
			'ajax_nonce'          => $ajax_nonce,
			'enableGrammar'       => $this->bool_to_js_string( $is_grammar_enabled ),
		);

		wp_enqueue_script( self::SCRIPT_HANDLE_INSTANCE );
		wp_localize_script( self::SCRIPT_HANDLE_INSTANCE, self::L10N_OBJECT_INSTANCE, $demo_payload );
		wp_enqueue_script( self::SCRIPT_HANDLE_BUNDLE );
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
	 * @param string $name
	 * @param mixed  $default
	 * @return mixed
	 */
	public function get_option( $name, $default = '' ) {
		return isset( $this->options[ $name ] ) ? $this->options[ $name ] : $default;
	}

	/**
	 * Add settings link in plugins list.
	 *
	 * @param array $links
	 * @return array
	 */
	public function add_action_links( $links ) {
		$url     = esc_url( admin_url( 'options-general.php?page=spell-checker-settings' ) );
		$mylinks = array(
			'<a href="' . $url . '">' . esc_html__( 'Settings', 'webspellchecker' ) . '</a>',
		);
		return array_merge( $links, $mylinks );
	}

	/**
	 * AJAX handler to fetch and render language list.
	 */
	public function ajax_get_proofreader_info() {
		check_ajax_referer( 'webspellchecker-proofreader', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'webspellchecker' ) ), 403 );
		}

		$proofreader_info_raw = isset( $_POST['getInfoResult'] ) ? wp_unslash( $_POST['getInfoResult'] ) : '';

		if ( is_string( $proofreader_info_raw ) && '' !== $proofreader_info_raw ) {
			$decoded = json_decode( $proofreader_info_raw, true );
			if ( JSON_ERROR_NONE === json_last_error() ) {
				$proofreader_info_raw = $decoded;
			}
		}

		if ( ! is_array( $proofreader_info_raw ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid payload', 'webspellchecker' ) ), 400 );
		}

		$sanitized = array(
			'langList' => array(
				'ltr' => array(),
				'rtl' => array(),
			),
		);

		if ( isset( $proofreader_info_raw['langList'] ) && is_array( $proofreader_info_raw['langList'] ) ) {
			foreach ( array( 'ltr', 'rtl' ) as $text_direction ) {
				if ( isset( $proofreader_info_raw['langList'][ $text_direction ] ) && is_array( $proofreader_info_raw['langList'][ $text_direction ] ) ) {
					foreach ( $proofreader_info_raw['langList'][ $text_direction ] as $code => $label ) {
						$code  = sanitize_text_field( (string) $code );
						$label = sanitize_text_field( (string) $label );
						if ( '' !== $code && '' !== $label ) {
							$sanitized['langList'][ $text_direction ][ $code ] = $label;
						}
					}
				}
			}
		}

		update_option( 'wsc_proofreader_info', $sanitized, false );

		$current_language = $this->get_language();
		$all_languages    = array_merge( $sanitized['langList']['ltr'], $sanitized['langList']['rtl'] );

		ob_start();
		?>
		<select class="regular" name="wsc_proofreader[slang]" id="wsc_proofreader[slang]">
			<?php foreach ( $all_languages as $code => $label ) : ?>
				<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $current_language, $code ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
		wp_send_json_success( ob_get_clean() );
	}

	/**
	 * Register filter to remove WProofreader artifacts from post content on save.
	 */
	public static function register_content_cleanup_filter() {
		add_filter(
			'wp_insert_post_data',
			static function ( $data, $postarr ) {
				if ( empty( $data['post_content'] ) ) {
					return $data;
				}

				if ( isset( $data['post_status'] ) && in_array( $data['post_status'], array( 'auto-draft', 'inherit' ), true ) ) {
					return $data;
				}

				$original_content = $data['post_content'];

				$cleanup_patterns = array(
					'#<span\s+class=(["\'])wsc-spelling-problem\1[^>]*>(.*?)</span>#si'   => '$2',
					'#<span\s+class=(["\'])wsc-grammar-problem\1[^>]*>(.*?)</span>#si'    => '$2',
					'#<span\s+class=(["\'])rangySelectionBoundary\1[^>]*>(.*?)</span>#si' => '$2',
				);

				$clean_content = $original_content;
				foreach ( $cleanup_patterns as $pattern => $replacement ) {
					$clean_content = preg_replace( $pattern, $replacement, $clean_content );
				}

				if ( strlen( (string) $clean_content ) > strlen( (string) $original_content ) * 1.25 ) {
					$clean_content = wp_kses_post( $original_content );
				}

				$data['post_content'] = $clean_content;
				return $data;
			},
			100,
			2
		);
	}
}

/**
 * Get singleton instance.
 *
 * @return WProofreader
 */
function wsc_proofreader() {
	return WProofreader::instance();
}

wsc_proofreader();