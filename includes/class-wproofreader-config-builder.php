<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds data passed from WordPress to the WProofreader JavaScript bundle.
 */
class WProofreader_Config_Builder {

	/** @var WProofreader */
	private $plugin;

	/**
	 * @param WProofreader $plugin Plugin facade.
	 */
	public function __construct( WProofreader $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Build localization payload for vendor scripts.
	 *
	 * @param array $overrides Config overrides.
	 * @return array
	 */
	public function build( array $overrides = array() ): array {
		$is_free          = $this->plugin->is_free_edition();
		$is_badge_enabled = ( $this->plugin->get_badge_button_option() === WProofreader::DEFAULT_BADGE_TOGGLE_OPTION );
		$base_config = array(
			'key_for_proofreader'            => $this->plugin->get_customer_id(),
			'slang'                          => $this->plugin->get_language(),
			'settingsSections'               => $is_free
				? array( 'dictionaries', 'languages', 'general', 'options' )
				: array( 'options', 'languages', 'dictionaries', 'about', 'general' ),
			'enableGrammar'                  => ! $is_free,
			'aiWritingAssistant'             => ! $is_free,
			'enableBadgeButton'              => $is_badge_enabled,
			'globalBadge'                    => true,
			'compactBadge'                   => true,
			'autocomplete'                   => false,
			'disableOptionsStorage'          => $is_free ? array( 'autocomplete' ) : array(),
			'disableDictionariesPreferences' => $is_free,
		);

		if ( $is_free ) {
			$base_config['generalOptions'] = array( 'spellingSuggestions', 'grammarSuggestions', 'styleGuideSuggestions', 'autocorrect' );
		}

		$config = wp_parse_args( $overrides, $base_config );

		if ( ! is_array( $config['disableOptionsStorage'] ) ) {
			$config['disableOptionsStorage'] = array();
		}

		return $this->normalize_booleans( $config );
	}

	/**
	 * Build config for the settings-page Web API instance.
	 *
	 * @param string $ajax_nonce AJAX nonce.
	 * @return array
	 */
	public function build_instance( string $ajax_nonce ): array {
		return $this->normalize_booleans(
			array(
				'key_for_proofreader' => $this->plugin->get_customer_id(),
				'slang'               => $this->plugin->get_language(),
				'ajax_nonce'          => $ajax_nonce,
				'enableGrammar'       => ! $this->plugin->is_free_edition(),
			)
		);
	}

	/**
	 * Shared WSC service configuration. Filterable via `wsc_service_config`.
	 *
	 * @return array
	 */
	public function service_config(): array {
		$defaults = array(
			'serviceProtocol' => 'https',
			'serviceHost'     => 'svc.webspellchecker.net',
			'servicePath'     => 'api',
			'servicePort'     => '443',
			'bundleUrl'       => 'https://svc.webspellchecker.net/spellcheck31/wscbundle/wscbundle.js',
		);

		$filtered = apply_filters( 'wsc_service_config', $defaults );

		return wp_parse_args( is_array( $filtered ) ? $filtered : array(), $defaults );
	}

	/**
	 * The remote bundle URL for script registration.
	 *
	 * @return string
	 */
	public function bundle_url(): string {
		$service_config = $this->service_config();

		return esc_url_raw( (string) $service_config['bundleUrl'] );
	}

	/**
	 * Keep legacy JS compatibility by passing booleans as "true"/"false" strings.
	 *
	 * @param array $config Raw config.
	 * @return array
	 */
	private function normalize_booleans( array $config ): array {
		foreach ( $config as $key => $value ) {
			if ( is_bool( $value ) ) {
				$config[ $key ] = $value ? 'true' : 'false';
			}
		}

		return $config;
	}
}
