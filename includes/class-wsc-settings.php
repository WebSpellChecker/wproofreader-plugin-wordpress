<?php

/**
 * WordPress settings API class
 *
 */
if ( ! class_exists( 'WeDevs_Settings_API_Test' ) ):
	class WSC_Settings {

		const OPTION_NAME = 'wsc';
		const TRIAL_CUSTOMER_ID = '1:nduHS3-xuxDG1-jCbTv4-mn7Il4-kRrAg3-nT2s44-3bH6Q3-LlSLf-ALWjs3-xBM9g2-EEfe53-Yv9';

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
						'desc'              => __( 'For 30-day free trial', 'webspellchecker' ),
						'type'              => 'text',
						'default'           => self::TRIAL_CUSTOMER_ID,
						'sanitize_callback' => 'sanitize_text_field'
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
				)
			);

			return $settings_fields;
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
	}
endif;
