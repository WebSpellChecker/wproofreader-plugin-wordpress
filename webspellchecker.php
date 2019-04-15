<?php
/**
 * Plugin Name: WProofreader
 * Plugin URI: https://webspellchecker.com/
 * Description: Check spelling and grammar on your site automatically with multilingual WProofreader plugin.
 * Version:     2.3
 * Author:      WebSpellChecker
 * Author URI:  https://webspellchecker.com/
 * Text Domain: webspellchecker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

final class WProofreader {
	const TRIAL_CUSTOMER_ID = '1:cma3h3-HTiyU3-JL08g4-SRyuS1-a9c0F3-kH6Cu-OlMHS-thcSV2-HlGmv3-YzRCN2-qrKY42-uPc';
	const SLANG = 'en_US';
	const BADGE_BUTTON = 'off';
	const PLUGIN_VERSION = "2.3";
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
		$this->settings = new WSC_Settings(
			__( 'WProofreader', 'webspellchecker' ),
			__( 'WProofreader', 'webspellchecker' ),
			'spell-checker-settings'
		);

		$this->options = ! empty( get_option( WSC_Settings::OPTION_NAME ) ) ? get_option( WSC_Settings::OPTION_NAME ) : array();

		if ( empty( $this->options ) ) {
			//set default setting
			$this->options['enable_on_posts']      = 'on';
			$this->options['enable_on_pages']      = 'on';
			$this->options['enable_on_products']   = 'off';
			$this->options['enable_on_categories'] = 'on';
			$this->options['enable_on_tags']       = 'on';
			update_option( 'wsc_proofreader_version', self::PLUGIN_VERSION );
		}

		$this->options['customer_id'] = $this->get_customer_id();

		add_action( 'admin_enqueue_scripts', array( $this, 'register_proofreader_scripts' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_action_links' ) );
		add_action( 'admin_head', array( $this, 'init_proofreader' ) );

		$this->check_version();

		add_action( 'wp_ajax_get_proofreader_info_callback', array( $this, 'get_proofreader_info_callback' ) );
		do_action( 'wsc_loaded' );
	}

	public function check_version() {
		//todo add Class for upgrade plugin
		if ( get_option( 'wsc_proofreader_version' ) !== self::PLUGIN_VERSION ) {
			//Clear old options in versions below 2
			delete_option( 'wsc' );
			update_option( 'wsc_proofreader_version', self::PLUGIN_VERSION );
		}
	}

	public function init_proofreader() {
		$editable_post_type = $this->get_editable_post_type();
		$screen             = get_current_screen();
		if ( $screen->base === 'settings_page_spell-checker-settings' ) {
			$this->api_proofreader_info();
		}

		foreach ( $this->options as $option => $on ) {
			if ( $option === 'enable_on_categories' && $on === 'on' ) {
				if ( $screen->id === 'edit-category'
				     || $screen->id === 'edit-product_cat'
				     || $screen->id === 'edit-wpsc_product_category' ) {
					$this->init_proofreader_js();
				}
			}
		}

		foreach ( $this->options as $option => $on ) {
			if ( $option === 'enable_on_tags' && $on === 'on' ) {
				if ( $screen->id === 'edit-product_tag'
				     || $screen->id === 'edit-post_tag' ) {
					$this->init_proofreader_js();
				}
			}
		}

		if ( false !== $editable_post_type ) {

			foreach ( $this->options as $option => $on ) {

				if ( $option === 'enable_on_posts' && $on === 'on' ) {

					if ( 0 === strcasecmp( 'post', $editable_post_type ) ) {
						$this->init_proofreader_js();
					}
					break;
				}
			}

			foreach ( $this->options as $option => $on ) {

				if ( $option === 'enable_on_pages' && $on === 'on' ) {

					if ( 0 === strcasecmp( 'page', $editable_post_type ) ) {
						$this->init_proofreader_js();
					}
					break;
				}
			}

			foreach ( $this->options as $option => $on ) {

				if ( $option === 'enable_on_products' && $on === 'on' ) {
					if ( $screen->id === 'wpsc-product' ) {
						$this->init_proofreader_js();
					}

					if ( $screen->id === 'edit-product_cat' ) {
						$this->init_proofreader_js();
					}
					if ( $screen->id === 'edit-product_tag' ) {

						$this->init_proofreader_js();
					}
					if ( 0 === strcasecmp( 'product', $editable_post_type ) ) {
						$this->init_proofreader_js();
					}

					break;

				}

			}

			return $this->js_added = true;
		}

		return $this->js_added = false;
	}

	public function includes() {
		require_once dirname( __FILE__ ) . '/vendor/class.settings-api.php';
		require_once dirname( __FILE__ ) . '/includes/class-wsc-settings.php';
	}

	public function get_editable_post_type() {
		$screen = get_current_screen();

		if ( $screen->post_type === $screen->id ) {
			$post_type = $screen->post_type;

			return $post_type;
		}

		return false;
	}

	function add_action_links( $links ) {
		$mylinks = array(
			'<a href="' . admin_url( 'options-general.php?page=spell-checker-settings' ) . '">' . __( 'Settings', 'webspellchecker' ) . '</a>',
		);

		return array_merge( $links, $mylinks );
	}

	function register_proofreader_scripts() {
		wp_register_script( 'wscbundle', 'https://svc.webspellchecker.net/spellcheck31/wscbundle/wscbundle.js', array(), '081120181109', false );
		wp_register_script( 'ProofreaderConfig', plugin_dir_url( __FILE__ ) . '/assets/proofreaderConfig.js', array( 'wscbundle' ), '081120181110', true );
		wp_register_script( 'ProofreaderInstance', plugin_dir_url( __FILE__ ) . '/assets/instance.js', array( 'wscbundle' ), '171220181251', true );
	}

	function init_proofreader_js() {
		$key_for_proofreader    = $this->get_customer_id();
		$slang                  = $this->get_slang();
		$settingsSections       = ( $this->get_customer_id() === self::TRIAL_CUSTOMER_ID ) ?
			[ 'options', 'languages', 'about' ]
			: [ 'options', 'languages', 'dictionaries', 'about' ];
		$enableGrammar          = ( $this->get_customer_id() === self::TRIAL_CUSTOMER_ID ) ? 'false' : 'true';
		$badge_button_optinon   = ( $this->get_badge_button_optinon() === self::BADGE_BUTTON ) ? 'true' : 'false';
		$wsc_proofreader_config = array(
			'key_for_proofreader' => $key_for_proofreader,
			'slang'               => $slang,
			'settingsSections'    => $settingsSections,
			'enableGrammar'       => $enableGrammar,
			'disableBadgeButton' => $badge_button_optinon,
		);
		wp_enqueue_script( 'wscbundle' );
		wp_enqueue_script( 'ProofreaderConfig' );
		wp_localize_script( 'ProofreaderConfig', 'WSCProofreaderConfig', $wsc_proofreader_config );
	}

	/**
	 * Get customer ID or trial ID if not set
	 *
	 * @return string customer ID
	 */
	public function get_customer_id() {
		$customer_id = $this->get_option( 'customer_id', self::TRIAL_CUSTOMER_ID );

		if ( empty( $customer_id ) ) {
			return self::TRIAL_CUSTOMER_ID;
		}

		return $customer_id;
	}

	public function get_slang() {
		$slang = $this->get_option( 'slang', self::SLANG );

		if ( empty( $slang ) ) {
			return self::SLANG;
		}

		return $slang;
	}

	public function get_badge_button_optinon() {
		$badge_button_optinon = $this->get_option( 'disable_badge_button', self::BADGE_BUTTON );

		return $badge_button_optinon;
	}

	public function get_option( $name, $default = '' ) {
		return ( isset( $this->options[ $name ] ) ) ? $this->options[ $name ] : $default;
	}

	function api_proofreader_info() {
		$ajax_nonce             = wp_create_nonce( "webspellchecker-proofreader" );
		$key_for_proofreader    = $this->get_customer_id();
		$slang                  = $this->get_slang();
		$enableGrammar          = ( $this->get_customer_id() === self::TRIAL_CUSTOMER_ID ) ? 'false' : 'true';
		$wsc_proofreader_config = array(
			'key_for_proofreader' => $key_for_proofreader,
			'slang'               => $slang,
			'ajax_nonce'          => $ajax_nonce,
			'enableGrammar'       => $enableGrammar
		);
		wp_enqueue_script( 'wscbundle' );
		wp_enqueue_script( 'ProofreaderInstance' );
		wp_localize_script( 'ProofreaderInstance', 'ProofreaderInstance', $wsc_proofreader_config );
	}


	function get_proofreader_info_callback() {
		$current_lang = $this->get_slang();
		check_ajax_referer( 'webspellchecker-proofreader', 'security' );
		$proofreader_info = $_POST['getInfoResult'];
		update_option( 'wsc_proofreader_info', $proofreader_info );
		ob_start();
		?>
        <select class="regular" name="wsc_proofreader[slang]" id="wsc_proofreader[slang]">
			<?php foreach ( $proofreader_info['langList']['ltr'] as $key => $value ): ?>
                <option <?php if ( $current_lang === $key ) {
					echo 'selected';
				} ?> value="<?php echo $key; ?>"><?php echo $value; ?></option>
			<?php endforeach; ?>
        </select>
		<?php
		wp_send_json( ob_get_clean() );
		wp_die();
	}

	public static function fix_for_gutenberg() {
		add_action( 'wp_insert_post_data', function ( $data, $postarr ) {
			if ( 'publish' == $data['post_status'] ) {
				$string               = $data['post_content'];
				$new_string           = preg_replace( '#(<span class=."wsc-spelling-problem." .*?>)(.*?)(</span>)#', '$2', $string );
				$new_string           = preg_replace( '#(<span class=."wsc-grammar-problem." .*?>)(.*?)(</span>)#', '$2', $new_string );
				$new_string           = preg_replace( '#(<span class=."rangySelectionBoundary." .*?>)(.*?)(</span>)#', '$2', $new_string );
				$new_string           = preg_replace( '#(<span class="wsc-spelling-problem".*?>)(.*?)(</span>)#', '$2', $new_string );
				$new_string           = preg_replace( '#(<span class="wsc-grammar-problem" .*?>)(.*?)(</span>)#', '$2', $new_string );
				$new_string           = preg_replace( '#(<span class="rangySelectionBoundary" .*?>)(.*?)(</span>)#', '$2', $new_string );
				$new_string           = preg_replace( '#(<span class=..rangySelectionBoundary.. .*?>)(.*?)(</span>)#', '$2', $new_string );
				$data['post_content'] = $new_string;
			}

			return $data;
		}, 100, 2 );
	}
}

function WSC() {
	return WProofreader::instance();
}

if ( is_admin() ) {
	WSC();
}
/**
 * fix for Gutenberg
 * todo write more cleaner
 */
if ( true === is_gutenberg_active() ) {
	WProofreader::fix_for_gutenberg();
};

function is_gutenberg_active() {
	$gutenberg    = false;
	$block_editor = false;

	if ( has_filter( 'replace_editor', 'gutenberg_init' ) ) {
		$gutenberg = true;
	}

	if ( version_compare( $GLOBALS['wp_version'], '5.0', '>' ) ) {
		$block_editor = true;
	}

	if ( ! $gutenberg && ! $block_editor ) {
		return false;
	}

	include_once ABSPATH . 'wp-admin/includes/plugin.php';

	if ( ! is_plugin_active( 'classic-editor/classic-editor.php' ) ) {
		return true;
	}

	$use_block_editor = ( get_option( 'classic-editor-replace' ) === 'no-replace' );

	return $use_block_editor;
}
