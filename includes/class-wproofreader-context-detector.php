<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detects WordPress admin/editor context without deciding plugin eligibility.
 */
class WProofreader_Context_Detector {

	const SCREEN_SETTINGS              = 'settings_page_spell-checker-settings';
	const SCREEN_SITE_EDITOR           = 'site-editor';
	const SCREEN_PERMALINKS            = 'options-permalink';
	const SCREEN_EDIT_CATEGORY         = 'edit-category';
	const SCREEN_EDIT_PRODUCT_CATEGORY = 'edit-product_cat';
	const SCREEN_EDIT_WPSC_PRODUCT_CAT = 'edit-wpsc_product_category';
	const SCREEN_EDIT_PRODUCT_TAG      = 'edit-product_tag';
	const SCREEN_EDIT_POST_TAG         = 'edit-post_tag';

	const PLACE_SETTINGS  = 'settings';
	const PLACE_TAXONOMY  = 'taxonomy';
	const PLACE_POST_TYPE = 'post_type';
	const PLACE_OTHER     = 'other';

	const TAXONOMY_CATEGORY = 'category';
	const TAXONOMY_TAG      = 'tag';

	/**
	 * Get current admin screen if available.
	 *
	 * @return WP_Screen|null
	 */
	public function get_screen() {
		return function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	}

	/**
	 * Check if current screen is the block editor.
	 *
	 * @param WP_Screen|null $screen Screen object.
	 * @return bool
	 */
	public function is_block_editor_screen( $screen ): bool {
		return ( $screen && method_exists( $screen, 'is_block_editor' ) && $screen->is_block_editor() );
	}

	/**
	 * Check if current screen is the site editor.
	 *
	 * @param WP_Screen|null $screen Screen object.
	 * @return bool
	 */
	public function is_site_editor_screen( $screen ): bool {
		if ( $this->screen_matches(
			$screen,
			array( self::SCREEN_SITE_EDITOR ),
			array( 'gutenberg-edit-site' )
		) ) {
			return true;
		}

		return $this->request_path_contains( 'site-editor.php' );
	}

	/**
	 * Check if current screen is the permalinks settings page.
	 *
	 * @param WP_Screen|null $screen Screen object.
	 * @return bool
	 */
	public function is_permalinks_settings_screen( $screen ): bool {
		if ( $this->screen_matches( $screen, array( self::SCREEN_PERMALINKS ), array() ) ) {
			return true;
		}

		return $this->request_path_contains( 'options-permalink.php' );
	}

	/**
	 * Fallback when get_current_screen() is not yet available.
	 *
	 * @param string $needle Filename to look for in PHP_SELF.
	 * @return bool
	 */
	private function request_path_contains( string $needle ): bool {
		if ( empty( $_SERVER['PHP_SELF'] ) ) {
			return false;
		}

		return false !== strpos( (string) $_SERVER['PHP_SELF'], $needle );
	}

	/**
	 * Detect current admin context.
	 *
	 * @return array
	 */
	public function detect(): array {
		$screen = $this->get_screen();

		$context = array(
			'place'       => self::PLACE_OTHER,
			'screen_id'   => $screen->id ?? '',
			'screen_base' => $screen->base ?? '',
			'post_type'   => $screen->post_type ?? '',
			'taxonomy'    => '',
		);

		if ( self::SCREEN_SETTINGS === $context['screen_base'] ) {
			$context['place'] = self::PLACE_SETTINGS;
			return $context;
		}

		$taxonomy = $this->taxonomy_for_screen( $context['screen_id'] );
		if ( '' !== $taxonomy ) {
			$context['place']    = self::PLACE_TAXONOMY;
			$context['taxonomy'] = $taxonomy;
			return $context;
		}

		if ( ! empty( $context['post_type'] ) ) {
			$context['place'] = self::PLACE_POST_TYPE;
			return $context;
		}

		return $context;
	}

	/**
	 * Match by screen id/base and optional base substrings.
	 *
	 * @param WP_Screen|null $screen Screen object.
	 * @param array          $ids Exact ids/bases.
	 * @param array          $base_substrings Base substrings.
	 * @return bool
	 */
	private function screen_matches( $screen, array $ids, array $base_substrings ): bool {
		if ( ! $screen ) {
			return false;
		}

		$screen_id   = isset( $screen->id ) ? (string) $screen->id : '';
		$screen_base = isset( $screen->base ) ? (string) $screen->base : '';

		if ( in_array( $screen_id, $ids, true ) || in_array( $screen_base, $ids, true ) ) {
			return true;
		}

		foreach ( $base_substrings as $substring ) {
			if ( '' !== $screen_base && false !== strpos( $screen_base, (string) $substring ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Map edit taxonomy screens to option groups.
	 *
	 * @param string $screen_id Screen id.
	 * @return string
	 */
	private function taxonomy_for_screen( string $screen_id ): string {
		$map = array(
			self::SCREEN_EDIT_CATEGORY         => self::TAXONOMY_CATEGORY,
			self::SCREEN_EDIT_PRODUCT_CATEGORY => self::TAXONOMY_CATEGORY,
			self::SCREEN_EDIT_WPSC_PRODUCT_CAT => self::TAXONOMY_CATEGORY,
			self::SCREEN_EDIT_PRODUCT_TAG      => self::TAXONOMY_TAG,
			self::SCREEN_EDIT_POST_TAG         => self::TAXONOMY_TAG,
		);

		return isset( $map[ $screen_id ] ) ? $map[ $screen_id ] : '';
	}
}
