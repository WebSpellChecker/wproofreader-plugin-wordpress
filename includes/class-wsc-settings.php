<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Admin settings controller for WProofreader.
 */
if ( ! class_exists( 'WSC_Settings' ) ) {

	class WSC_Settings {
		/** Option bag name for all plugin settings (single section). */
		const OPTION_NAME = 'wsc_proofreader';

		/** @var WSC_Settings_API */
		private $settings_api;

		/** @var string */
		private $page_title;

		/** @var string */
		private $menu_title;

		/** @var string */
		private $menu_slug;

		/**
		 * @param string $page_title Settings page title (H1).
		 * @param string $menu_title Menu label under Settings.
		 * @param string $menu_slug Menu slug.
		 */
		public function __construct( $page_title, $menu_title, $menu_slug ) {
			$this->settings_api = new WSC_Settings_API();
			$this->menu_title   = (string) $menu_title;
			$this->menu_slug    = (string) $menu_slug;
			$this->page_title   = (string) $page_title;

			add_action( 'admin_init', array( $this, 'on_admin_init' ) );
			add_action( 'admin_menu', array( $this, 'on_admin_menu' ) );
		}

		/**
		 * Register sections/fields and augment fields if e-commerce is active.
		 */
		public function on_admin_init() {
			// Field/section setup is only needed when rendering the settings page
			// or when options.php is persisting this option group.
			if ( ! $this->is_settings_request() ) {
				return;
			}

			if ( $this->is_ecommerce_active() ) {
				add_filter( 'wsc_admin_fields', array( $this, 'add_products_toggle_field' ), 1 );
			}

			$this->settings_api->set_sections( $this->get_settings_sections() );
			$this->settings_api->set_fields( $this->get_settings_fields() );

			$this->settings_api->admin_init();
		}

		/**
		 * Add the settings page under "Settings".
		 */
		public function on_admin_menu() {
			add_options_page(
				$this->page_title,
				$this->menu_title,
				'manage_options',
				$this->menu_slug,
				array( $this, 'render_settings_page' )
			);
		}

		/**
		 * Whether the current request renders or saves this settings page.
		 *
		 * @return bool
		 */
		protected function is_settings_request(): bool {
			global $pagenow;

			if ( 'options.php' === $pagenow ) {
				return true;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only routing check.
			$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

			return $this->menu_slug === $page;
		}

		/**
		 * Detect if WooCommerce or WP eCommerce is active.
		 *
		 * @return bool
		 */
		protected function is_ecommerce_active(): bool {
			return class_exists( 'WooCommerce' ) || class_exists( 'WP_eCommerce' );
		}

		/**
		 * Filter callback: insert "Check Products" field after "Check Pages".
		 *
		 * @param array $settings_fields
		 *
		 * @return array
		 */
		public function add_products_toggle_field( array $settings_fields ): array {
			if ( empty( $settings_fields[ self::OPTION_NAME ] ) || ! is_array( $settings_fields[ self::OPTION_NAME ] ) ) {
				return $settings_fields;
			}

			$products_field = array(
				'name'    => 'enable_on_products',
				'label'   => __( 'Check Products', 'webspellchecker' ),
				'type'    => 'checkbox',
				'default' => 'on',
			);

			$fields     = $settings_fields[ self::OPTION_NAME ];
			$new_fields = array();
			$inserted   = false;

			foreach ( $fields as $field ) {
				$new_fields[] = $field;

				if ( ! $inserted && isset( $field['name'] ) && 'enable_on_pages' === $field['name'] ) {
					$new_fields[] = $products_field;
					$inserted     = true;
				}
			}

			// If we did not find "enable_on_pages", append before the last two tail fields (admin/frontend).
			if ( ! $inserted ) {
				$tail_count = 2;
				if ( count( $new_fields ) > $tail_count ) {
					$tail         = array_splice( $new_fields, - $tail_count );
					$new_fields[] = $products_field;
					$new_fields   = array_merge( $new_fields, $tail );
				} else {
					$new_fields[] = $products_field;
				}
			}

			$settings_fields[ self::OPTION_NAME ] = $new_fields;

			return $settings_fields;
		}

		/**
		 * Define the single settings section.
		 *
		 * @return array[]
		 */
		protected function get_settings_sections(): array {
			return array(
				array(
					'id'    => self::OPTION_NAME,
					'title' => '',
				),
			);
		}

		/**
		 * Define all settings fields.
		 *
		 * @return array
		 */
		protected function get_settings_fields(): array {
			$language_options = $this->get_language_options();
			if ( empty( $language_options ) ) {
				$language_options = array(
					'en_US' => 'English',
					'en_GB' => 'British English',
					'en_CA' => 'Canadian English',
					'fr_FR' => 'French',
					'fr_CA' => 'Canadian French',
					'de_DE' => 'German',
					'it_IT' => 'Italian',
					'pt_PT' => 'Portuguese',
					'pt_BR' => 'Brazilian Portuguese',
					'da_DK' => 'Danish',
				);
			}

			$fields = array(
				self::OPTION_NAME => array(
					array(
						'name'              => 'customer_id',
						'label'             => __( 'License Key', 'webspellchecker' ),
						'desc'              => __( 'Upgrade to WProofreader Pro to access the grammar/style checking, text autocomplete capabilities and lift the usage limits for your websites. Leave it empty if you prefer to use the Free version. <br><a href="https://webspellchecker.com/free-trial/" target="_blank">Try Pro for 14-days free ></a>.', 'webspellchecker' ),
						'type'              => 'text',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					array(
						'name'              => 'slang',
						'label'             => __( 'Default Language', 'webspellchecker' ),
						'type'              => 'select',
						'options'           => $language_options,
						'default'           => 'en_US',
						'sanitize_callback' => 'sanitize_text_field',
					),
					array(
						// Historical toggle: 'on' means "badge shown".
						'name'    => 'disable_badge_button',
						'label'   => __( 'Enable Badge', 'webspellchecker' ),
						'type'    => 'checkbox',
						'default' => 'on',
					),
					array(
						'name'    => 'enable_on_posts',
						'label'   => __( 'Check Posts', 'webspellchecker' ),
						'type'    => 'checkbox',
						'default' => 'on',
					),
					array(
						'name'    => 'enable_on_pages',
						'label'   => __( 'Check Pages', 'webspellchecker' ),
						'type'    => 'checkbox',
						'default' => 'on',
					),
					// "Check Products" may be injected here if e-commerce is active.
					array(
						'name'    => 'enable_on_categories',
						'label'   => __( 'Check Categories', 'webspellchecker' ),
						'type'    => 'checkbox',
						'default' => 'on',
					),
					array(
						'name'    => 'enable_on_tags',
						'label'   => __( 'Check Tags', 'webspellchecker' ),
						'type'    => 'checkbox',
						'default' => 'on',
					),
					// Keep these at the end for predictable UI.
					array(
						'name'    => 'enable_in_admin',
						'label'   => __( 'Enable in admin area', 'webspellchecker' ),
						'desc'    => __( 'Enable WProofreader in editable fields across the WordPress dashboard.', 'webspellchecker' ),
						'type'    => 'checkbox',
						'default' => 'off',
					),
					array(
						'name'    => 'enable_on_frontend',
						'label'   => __( 'Enable on public site', 'webspellchecker' ),
						'desc'    => __( 'Enable WProofreader in editable fields on the public site (e.g., contact forms, forums).', 'webspellchecker' ),
						'type'    => 'checkbox',
						'default' => 'off',
					),
				),
			);

			return apply_filters( 'wsc_admin_fields', $fields );
		}

		/**
		 * Render the settings page.
		 */
		public function render_settings_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'webspellchecker' ) );
			}

			echo '<div class="wrap">';
			echo '<h1>' . esc_html( $this->page_title ) . '</h1>';
			$this->settings_api->show_navigation();
			$this->settings_api->show_forms();
			echo '</div>';
		}

		/**
		 * Utility: return available languages from cached option.
		 *
		 * @return array<string,string> key => label
		 */
		protected function get_language_options(): array {
			$info = get_transient( 'wsc_proofreader_info_cache' );
			if ( false === $info ) {
				$info = get_option( 'wsc_proofreader_info' );
			}

			if ( ! is_array( $info ) || empty( $info['langList'] ) || ! is_array( $info['langList'] ) ) {
				return array();
			}

			$lang_list = $info['langList'];

			$ltr = array();
			$rtl = array();

			if ( isset( $lang_list['ltr'] ) && is_array( $lang_list['ltr'] ) ) {
				$ltr = $lang_list['ltr'];
			}
			if ( isset( $lang_list['rtl'] ) && is_array( $lang_list['rtl'] ) ) {
				$rtl = $lang_list['rtl'];
			}

			// Ensure strings and remove empties.
			$sanitize_map = static function ( $arr ) {
				$out = array();
				foreach ( $arr as $code => $label ) {
					$code  = (string) $code;
					$label = (string) $label;
					if ( '' !== $code && '' !== $label ) {
						$out[ $code ] = $label;
					}
				}

				return $out;
			};

			return array_merge( $sanitize_map( $ltr ), $sanitize_map( $rtl ) );
		}
	}
}
