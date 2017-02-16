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

		const OPTION_NAME = 'wsc';		

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
			// plugins support
			$this->yoast_support();
			$this->acf_support();
			
			//set the settings
			$this->settings_api->set_sections( $this->get_settings_sections() );
			$this->settings_api->set_fields( $this->get_settings_fields() );

			//initialize settings
			$this->settings_api->admin_init();
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
				'wsc' => array(
					array(
						'name'              => 'customer_id',
						'label'             => __( 'Customer ID', 'webspellchecker' ),
						'desc'              => __( 'Upgrade to WebSpellChecker Pro to get rid of banner ad and spell checking across list of your websites with no usage limitations. Subscribe for paid version of the WebSpellChecker service on <a href="https://www.webspellchecker.net/signup/hosted-signup.html#scayt-paid" target="_blank">here</a>.', 'webspellchecker' ),
						'type'              => 'text',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field'
					),
					array(
						'name'  => 'slang',
						'label' => __( 'Default language', 'webspellchecker' ),
						'type'  => 'select',
						'options' => array(
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
							'nl_NL' => 'Dutch',
							'fi_FI' => 'Finnish',
							'el_GR' => 'Greek',
							'nb_NO' => 'Norwegian Bokmal',
							'es_ES' => 'Spanish',
							'sv_SE' => 'Swedish',
						),
						'default' => 'en_US'
					),
					array(
						'name'  => 'visual_editor',
						'label' => __( 'Enable on visual editor', 'webspellchecker' ),
						'type'  => 'checkbox'
					),
					array(
						'name'  => 'excerpt_field',
						'label' => __( 'Enable on excerpt field', 'webspellchecker' ),
						'type'  => 'checkbox'
					),
					array(
						'name'  => 'title_field',
						'label' => __( 'Enable on title field', 'webspellchecker' ),
						'type'  => 'checkbox'
					)
				)
			);

			return apply_filters( 'wsc_admin_fields', $settings_fields ) ;
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

		
		// todo: create class wsc_acf
		public function acf_support() {
			if( class_exists( 'acf' ) ) {
				add_filter('wsc_admin_fields', array( $this, 'acf_settings_fields') );
			}
		}
		
		public function acf_settings_fields( $fields ) {
			$fields['wsc'][] = array(
				'name'  => 'acf_fields',
				'label' => __( 'Enable on ACF fields', 'webspellchecker' ),
				'type'  => 'checkbox'
			);
			return $fields;
		}
		
		// todo: create class wsc_yoast
		public function yoast_support() {
			if( is_plugin_active( 'wordpress-seo/wp-seo.php' ) 
				OR is_plugin_active( 'wordpress-seo-premium/wp-seo-premium.php' ) ) 
			{
				add_filter('wsc_admin_fields', array( $this, 'yoast_settings_fields') );
			}
		}
		
		public function yoast_settings_fields( $fields ) {
			$fields['wsc'][] = array(
				'name'  => 'yoast_title_field',
				'label' => __( 'Enable on Yoast SEO title field', 'webspellchecker' ),
				'type'  => 'checkbox'
			);
			$fields['wsc'][] = array(
				'name'  => 'yoast_description_field',
				'label' => __( 'Enable on Yoast SEO description field', 'webspellchecker' ),
				'type'  => 'checkbox'
			);
			return $fields;
		}

	}

}