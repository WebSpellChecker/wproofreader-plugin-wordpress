<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and enqueues scripts for admin, editor, frontend, and settings demo.
 */
class WProofreader_Assets {

	/** @var WProofreader */
	private $plugin;

	/** @var WProofreader_Context_Detector */
	private $context_detector;

	/** @var WProofreader_Context_Evaluator */
	private $context_evaluator;

	/** @var WProofreader_Config_Builder */
	private $config_builder;

	/** @var string */
	private $plugin_url;

	/** @var string */
	private $plugin_dir;

	/**
	 * @param WProofreader                   $plugin Plugin facade.
	 * @param WProofreader_Context_Detector  $context_detector Context detector.
	 * @param WProofreader_Context_Evaluator $context_evaluator Context evaluator.
	 * @param WProofreader_Config_Builder    $config_builder Config builder.
	 * @param string                         $plugin_url Plugin URL.
	 * @param string                         $plugin_dir Plugin directory.
	 */
	public function __construct(
		WProofreader $plugin,
		WProofreader_Context_Detector $context_detector,
		WProofreader_Context_Evaluator $context_evaluator,
		WProofreader_Config_Builder $config_builder,
		string $plugin_url,
		string $plugin_dir
	) {
		$this->plugin            = $plugin;
		$this->context_detector  = $context_detector;
		$this->context_evaluator = $context_evaluator;
		$this->config_builder    = $config_builder;
		$this->plugin_url        = $plugin_url;
		$this->plugin_dir        = $plugin_dir;
	}

	/**
	 * Register all plugin scripts.
	 */
	public function register_scripts() {
		wp_register_script(
			WProofreader::SCRIPT_HANDLE_BUNDLE,
			$this->config_builder->bundle_url(),
			array(),
			WProofreader::PLUGIN_VERSION,
			true
		);

		$this->register_local_script( WProofreader::SCRIPT_HANDLE_CONFIG, 'assets/proofreaderConfig.js', array(), false );
		$this->register_local_script( WProofreader::SCRIPT_HANDLE_ENV_CHECKER, 'assets/environmentChecker.js', array(), false );
		$this->register_local_script( WProofreader::SCRIPT_HANDLE_INSTANCE, 'assets/instance.js', array( 'jquery' ), true );
		$this->register_local_script( WProofreader::SCRIPT_HANDLE_GUTENBERG_ENV, 'assets/gutenberg-environment.js', array(), true );
		$this->register_local_script( WProofreader::SCRIPT_HANDLE_CLASSIC_ENV, 'assets/classic-environment.js', array( WProofreader::SCRIPT_HANDLE_BUNDLE ), true );
	}

	/**
	 * Enqueue assets for the block editor.
	 */
	public function enqueue_for_block_editor() {
		if ( ! is_admin() ) {
			return;
		}

		$screen = $this->context_detector->get_screen();
		if ( ! $this->context_detector->is_block_editor_screen( $screen ) || $this->context_detector->is_site_editor_screen( $screen ) ) {
			return;
		}

		$context = $this->context_detector->detect();
		if ( ! $this->context_evaluator->should_enable_filtered( $context ) ) {
			return;
		}

		$this->enqueue_runtime_config();
		wp_enqueue_script( WProofreader::SCRIPT_HANDLE_ENV_CHECKER );
		wp_enqueue_script( WProofreader::SCRIPT_HANDLE_GUTENBERG_ENV );
	}

	/**
	 * Enqueue assets for classic admin pages.
	 *
	 * @param string $hook_suffix Hook suffix.
	 */
	public function enqueue_for_admin( $hook_suffix ) {
		if ( ! is_admin() ) {
			return;
		}

		$screen = $this->context_detector->get_screen();
		if ( ! $this->is_eligible_admin_screen( $screen ) ) {
			return;
		}

		if ( WProofreader_Context_Detector::SCREEN_SETTINGS === ( $screen->base ?? '' ) ) {
			$this->enqueue_settings_demo();
			return;
		}

		$context = $this->context_detector->detect();
		if ( ! $this->context_evaluator->should_enable_filtered( $context ) ) {
			return;
		}

		$badge_config = $this->classic_editor_badge_config( $screen, $context );

		$this->enqueue_runtime_config( $badge_config );
		wp_enqueue_script( WProofreader::SCRIPT_HANDLE_BUNDLE );

		if ( ! empty( $badge_config['globalBadge'] ) ) {
			wp_enqueue_script( WProofreader::SCRIPT_HANDLE_CLASSIC_ENV );
		}
	}

	/**
	 * Enqueue assets on the frontend.
	 */
	public function enqueue_for_frontend() {
		if ( WProofreader::OPTION_ON !== $this->plugin->get_option( WProofreader::SETTING_ENABLE_FRONTEND, WProofreader::OPTION_OFF ) ) {
			return;
		}

		$this->enqueue_runtime_config( array( 'globalBadge' => false ) );
		wp_enqueue_script( WProofreader::SCRIPT_HANDLE_BUNDLE );
	}

	/**
	 * Enqueue assets inside the Elementor editor shell.
	 */
	public function enqueue_for_elementor_editor() {
		// Elementor resets the global WP_Scripts registry before this hook.
		$this->register_scripts();

		$context = $this->elementor_context();
		if ( ! $this->context_evaluator->should_enable_filtered( $context ) ) {
			return;
		}

		$this->enqueue_runtime_config( array( 'globalBadge' => false ) );
		wp_enqueue_script( WProofreader::SCRIPT_HANDLE_BUNDLE );
	}

	/**
	 * @param string $handle Script handle.
	 * @param string $relative_path Relative asset path.
	 * @param array  $deps Dependencies.
	 * @param bool   $in_footer Whether to load in footer.
	 */
	private function register_local_script( string $handle, string $relative_path, array $deps, bool $in_footer ) {
		wp_register_script(
			$handle,
			$this->plugin_url . $relative_path,
			$deps,
			$this->asset_version_for( $relative_path ),
			$in_footer
		);
	}

	/**
	 * @param string $relative_path Relative asset path.
	 * @return string
	 */
	private function asset_version_for( string $relative_path ): string {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			return (string) time();
		}

		$file_path = $this->plugin_dir . ltrim( $relative_path, '/\\' );

		if ( ! is_readable( $file_path ) ) {
			return WProofreader::PLUGIN_VERSION;
		}

		$mtime = filemtime( $file_path );

		return $mtime ? (string) $mtime : WProofreader::PLUGIN_VERSION;
	}

	/**
	 * @param WP_Screen|null $screen Screen object.
	 * @return bool
	 */
	private function is_eligible_admin_screen( $screen ): bool {
		if ( ! $screen ) {
			return false;
		}

		if ( $this->context_detector->is_block_editor_screen( $screen ) || $this->context_detector->is_site_editor_screen( $screen ) ) {
			return false;
		}

		if ( 'plugins' === ( $screen->base ?? '' ) ) {
			return false;
		}

		return ! $this->context_detector->is_permalinks_settings_screen( $screen );
	}

	/**
	 * @param WP_Screen|null $screen Screen object.
	 * @param array          $context Context.
	 * @return array
	 */
	private function classic_editor_badge_config( $screen, array $context ): array {
		$is_single_editor_screen = ( isset( $screen->base ) && 'post' === $screen->base );
		$post_type               = $context['post_type'] ?? '';
		$is_post_or_page         = in_array( $post_type, array( WProofreader::POST_TYPE_POST, WProofreader::POST_TYPE_PAGE ), true );

		return array( 'globalBadge' => (bool) ( $is_single_editor_screen && $is_post_or_page ) );
	}


	/**
	 * Build an editor context for Elementor's custom admin app.
	 *
	 * @return array
	 */
	private function elementor_context(): array {
		$post_id   = isset( $_GET['post'] ) ? absint( wp_unslash( $_GET['post'] ) ) : 0;
		$post_type = $post_id ? get_post_type( $post_id ) : '';

		return array(
			'place'       => WProofreader_Context_Detector::PLACE_POST_TYPE,
			'screen_id'   => 'elementor',
			'screen_base' => 'elementor',
			'post_type'   => $post_type ? (string) $post_type : WProofreader::POST_TYPE_POST,
			'taxonomy'    => '',
		);
	}

	/**
	 * @param array $overrides Config overrides.
	 */
	private function enqueue_runtime_config( array $overrides = array() ) {
		wp_enqueue_script( WProofreader::SCRIPT_HANDLE_CONFIG );
		wp_localize_script( WProofreader::SCRIPT_HANDLE_CONFIG, WProofreader::L10N_OBJECT_CONFIG, $this->config_builder->build( $overrides ) );
		wp_localize_script( WProofreader::SCRIPT_HANDLE_CONFIG, WProofreader::L10N_OBJECT_SERVICE, $this->config_builder->service_config() );
	}

	/**
	 * Enqueue the settings page demo instance.
	 */
	private function enqueue_settings_demo() {
		wp_enqueue_script( WProofreader::SCRIPT_HANDLE_INSTANCE );
		wp_localize_script(
			WProofreader::SCRIPT_HANDLE_INSTANCE,
			WProofreader::L10N_OBJECT_INSTANCE,
			$this->config_builder->build_instance( wp_create_nonce( 'webspellchecker-proofreader' ) )
		);
		wp_localize_script( WProofreader::SCRIPT_HANDLE_INSTANCE, WProofreader::L10N_OBJECT_SERVICE, $this->config_builder->service_config() );
		wp_enqueue_script( WProofreader::SCRIPT_HANDLE_BUNDLE );
	}
}
