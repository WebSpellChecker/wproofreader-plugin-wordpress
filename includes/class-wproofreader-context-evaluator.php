<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Converts a detected context and settings into an enable/disable decision.
 */
class WProofreader_Context_Evaluator {

	/** @var WProofreader */
	private $plugin;

	/** @var array */
	private $post_type_options;

	/** @var array */
	private $taxonomy_options;

	/**
	 * @param WProofreader $plugin Plugin facade.
	 */
	public function __construct( WProofreader $plugin ) {
		$this->plugin = $plugin;

		$this->post_type_options = array(
			WProofreader::POST_TYPE_POST         => WProofreader::SETTING_ENABLE_POSTS,
			WProofreader::POST_TYPE_PAGE         => WProofreader::SETTING_ENABLE_PAGES,
			WProofreader::POST_TYPE_PRODUCT      => WProofreader::SETTING_ENABLE_PRODUCTS,
			WProofreader::POST_TYPE_WPSC_PRODUCT => WProofreader::SETTING_ENABLE_PRODUCTS,
		);

		$this->taxonomy_options = array(
			WProofreader_Context_Detector::TAXONOMY_CATEGORY => WProofreader::SETTING_ENABLE_CATEGORIES,
			WProofreader_Context_Detector::TAXONOMY_TAG      => WProofreader::SETTING_ENABLE_TAGS,
		);
	}

	/**
	 * Decide whether WProofreader should be enabled in current context.
	 *
	 * @param array $context Detected context.
	 * @return bool
	 */
	public function should_enable( array $context ): bool {
		$place = isset( $context['place'] ) ? (string) $context['place'] : WProofreader_Context_Detector::PLACE_OTHER;

		if ( WProofreader_Context_Detector::PLACE_SETTINGS === $place ) {
			return true;
		}

		$admin_enabled  = $this->is_option_enabled( WProofreader::SETTING_ENABLE_ADMIN, WProofreader::OPTION_OFF );
		$admin_behavior = apply_filters( 'wproofreader_admin_behavior', 'with_exclusions' );

		if ( $admin_enabled && 'override' === $admin_behavior ) {
			return true;
		}

		if ( WProofreader_Context_Detector::PLACE_TAXONOMY === $place ) {
			return $this->should_enable_for_taxonomy( $context, $admin_enabled );
		}

		if ( WProofreader_Context_Detector::PLACE_POST_TYPE === $place ) {
			return $this->should_enable_for_post_type( $context, $admin_enabled );
		}

		return $admin_enabled;
	}

	/**
	 * Apply filters around the raw context decision.
	 *
	 * @param array $context Detected context.
	 * @return bool
	 */
	public function should_enable_filtered( array $context ): bool {
		return (bool) apply_filters(
			'wproofreader_should_enable_here',
			$this->should_enable( $context ),
			$context,
			$this->plugin->get_options()
		);
	}

	/**
	 * @param array $context Context.
	 * @param bool  $admin_enabled Whether broad admin mode is enabled.
	 * @return bool
	 */
	private function should_enable_for_taxonomy( array $context, bool $admin_enabled ): bool {
		$taxonomy   = isset( $context['taxonomy'] ) ? (string) $context['taxonomy'] : '';
		$option_key = isset( $this->taxonomy_options[ $taxonomy ] ) ? $this->taxonomy_options[ $taxonomy ] : '';

		if ( '' === $option_key ) {
			return $admin_enabled;
		}

		return $this->is_option_enabled( $option_key, WProofreader::OPTION_ON );
	}

	/**
	 * @param array $context Context.
	 * @param bool  $admin_enabled Whether broad admin mode is enabled.
	 * @return bool
	 */
	private function should_enable_for_post_type( array $context, bool $admin_enabled ): bool {
		$post_type  = isset( $context['post_type'] ) ? (string) $context['post_type'] : '';
		$option_key = isset( $this->post_type_options[ $post_type ] ) ? $this->post_type_options[ $post_type ] : '';

		if ( '' !== $option_key ) {
			return $this->is_option_enabled( $option_key, WProofreader::OPTION_ON );
		}

		if ( $this->is_additional_post_type( $post_type ) ) {
			return true;
		}

		return $admin_enabled;
	}

	/**
	 * @param string $post_type Post type.
	 * @return bool
	 */
	private function is_additional_post_type( string $post_type ): bool {
		$additional = apply_filters( 'wproofreader_add_cpt', array() );
		if ( ! is_array( $additional ) ) {
			return false;
		}

		foreach ( $additional as $cpt ) {
			if ( 0 === strcasecmp( (string) $cpt, $post_type ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $key Option key.
	 * @param string $default Default value.
	 * @return bool
	 */
	private function is_option_enabled( string $key, string $default ): bool {
		return WProofreader::OPTION_ON === $this->plugin->get_option( $key, $default );
	}
}
