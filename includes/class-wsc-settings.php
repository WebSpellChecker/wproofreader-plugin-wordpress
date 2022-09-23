<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WordPress settings API class
 *
 */
if ( ! class_exists( 'WeDevs_Settings_API_Test' ) ) {
	class WSC_Settings {
		const OPTION_NAME = 'wsc_proofreader';

		/**
		 * @var WeDevs_Settings_API
		 */
		private $settings_api;
		private $page_title;
		private $menu_title;
		private $menu_slug;

		function __construct( $page_title, $menu_title, $menu_slug ) {
			$this->settings_api = new WeDevs_Settings_API;
			$this->menu_title   = $menu_title;
			$this->menu_slug    = $menu_slug;
			$this->page_title   = $page_title;
			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		}

		function admin_init() {
			//enable if woocommerce active
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			if ( class_exists( 'WooCommerce' ) && is_plugin_active( 'woocommerce/woocommerce.php' ) || class_exists( 'WP_eCommerce' ) ) {
				add_filter( 'wsc_admin_fields', array( $this, 'enable_woocommerce' ), 1 );
			}

			//set the settings
			$this->settings_api->set_sections( $this->get_settings_sections() );
			$this->settings_api->set_fields( $this->get_settings_fields() );

			//initialize settings
			$this->settings_api->admin_init();
//			$this->set_default_settings();
		}

		function enable_woocommerce( $settings_fields ) {
			$enable_on_products = array(
				'name'    => 'enable_on_products',
				'label'   => __( 'Check Products', 'webspellchecker' ),
				'type'    => 'checkbox',
				'default' => 'on'
			);
			array_push( $settings_fields['wsc_proofreader'], $enable_on_products );

			return $settings_fields;
		}

		public function set_default_settings( $settings ) {
			$this->settings_api->set_fields( $settings );
		}

		function admin_menu() {
			add_options_page( $this->page_title, $this->menu_title, 'delete_posts', $this->menu_slug, array(
				$this,
				'plugin_page'
			) );
		}

		function get_settings_sections() {
			$sections = array(
				array(
					'id'    => self::OPTION_NAME,
					'title' => ''
				)
			);

			return $sections;
		}

		/**
		 * Returns all the settings fields
		 *
		 * @return array settings fields
		 */
		function get_settings_fields() {
			$settings_fields = array(
				'wsc_proofreader' => array(
					array(
						'name'              => 'customer_id',
						'label'             => __( 'License Key', 'webspellchecker' ),
						'desc'              => __( 'Upgrade to WProofreader Pro to access the grammar/style checking, text autocomplete capabilities and lift the usage limits for your websites. Leave it empty if you prefer to use the Free version. <br><a href="https://webspellchecker.com/free-trial/" target="_blank">Try Pro for 14-days free ></a>.', 'webspellchecker' ),
						'type'              => 'text',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field'
					),
					array(
						'name'    => 'slang',
						'label'   => __( 'Default Language', 'webspellchecker' ),
						'type'    => 'select',
						'options' => ! empty( $this->get_lang_list() ) ? $this->get_lang_list() : array(
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
						),
						'default' => 'en_US'
					),
					array(
						'name'    => 'disable_badge_button',
						'label'   => __( 'Enable Badge', 'webspellchecker' ),
						'type'    => 'checkbox',
						'default' => 'on'
					),
					array(
						'name'    => 'enable_on_posts',
						'label'   => __( 'Check Posts', 'webspellchecker' ),
						'type'    => 'checkbox',
						'default' => 'on'
					),
					array(
						'name'    => 'enable_on_pages',
						'label'   => __( 'Check Pages', 'webspellchecker' ),
						'type'    => 'checkbox',
						'default' => 'on'
					),
					array(
						'name'    => 'enable_on_categories',
						'label'   => __( 'Check Categories', 'webspellchecker' ),
						'type'    => 'checkbox',
						'default' => 'on'
					),
					array(
						'name'    => 'enable_on_tags',
						'label'   => __( 'Check Tags', 'webspellchecker' ),
						'type'    => 'checkbox',
						'default' => 'on'
					)

				)
			);

			return apply_filters( 'wsc_admin_fields', $settings_fields );
		}

		function plugin_page() {
			echo '<div class="wrap">';
			echo "<h1>$this->page_title</h1>";
			$this->settings_api->show_navigation();
			$this->settings_api->show_forms();
			echo '</div>';
		}

		/**
		 * Get all the pages
		 *
		 * @return array page names with key value pairs
		 */
		function get_pages() {
			$pages         = get_pages();
			$pages_options = array();
			if ( $pages ) {
				foreach ( $pages as $page ) {
					$pages_options[ $page->ID ] = $page->post_title;
				}
			}

			return $pages_options;
		}

		function get_lang_list() {
			$get_info = get_option( 'wsc_proofreader_info' );

            return !empty($get_info['langList']['ltr']) ? $get_info['langList']['ltr'] : array();
		}

	}

}
