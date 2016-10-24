<?php

/**
 * WordPress settings API class
 *
 */
if ( ! class_exists( 'WeDevs_Settings_API_Test' ) ):
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
						'desc'              => __( 'Leave empty for 30-day free trial', 'webspellchecker' ),
						'type'              => 'text',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field'
					),
					array(
						'name'  => 'visual_editor',
						'label' => __( 'Enable on visual editor', 'webspellchecker' ),
						'type'  => 'checkbox'
					),
					array(
						'name'  => 'text_editor',
						'label' => __( 'Enable on text editor', 'webspellchecker' ),
						'type'  => 'checkbox'
					),
					array(
						'name'  => 'excerpt_field',
						'label' => __( 'Enable on excerpt field', 'webspellchecker' ),
						'type'  => 'checkbox'
					),
					array(
						'name'    => 'type',
						'label'   => __( 'Spell Check Type', 'webspellchecker' ),
						'type'    => 'radio',
						'default' => 'scayt',
						'options' => array(
							'scayt' => 'SCAYT (SpellCheckAsYouType)',
							'wsc'   => 'WSC (WebSpellChecker)'
						)
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
