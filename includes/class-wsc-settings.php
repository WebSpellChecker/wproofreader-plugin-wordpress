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
						'desc'              => __( 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s', 'webspellchecker' ),
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
						'name'  => 'excerpt_field',
						'label' => __( 'Enable on excerpt field', 'webspellchecker' ),
						'type'  => 'checkbox'
					),
					array(
						'name'  => 'title_field',
						'label' => __( 'Enable on title field', 'webspellchecker' ),
						'type'  => 'checkbox'
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
                                        )
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
