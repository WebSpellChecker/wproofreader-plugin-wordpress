<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX endpoints for the settings page.
 */
class WProofreader_Ajax {

	const INFO_OPTION    = 'wsc_proofreader_info';
	const INFO_TRANSIENT = 'wsc_proofreader_info_cache';
	const INFO_TTL       = DAY_IN_SECONDS;

	/**
	 * AJAX handler to fetch and render language list.
	 */
	public static function get_proofreader_info() {
		check_ajax_referer( 'webspellchecker-proofreader', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'webspellchecker' ) ), 403 );
		}

		$proofreader_info = self::parse_payload( $_POST['getInfoResult'] ?? '' );
		if ( ! is_array( $proofreader_info ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid payload', 'webspellchecker' ) ), 400 );
		}

		$sanitized = self::sanitize_info( $proofreader_info );

		update_option( self::INFO_OPTION, $sanitized, false );
		set_transient( self::INFO_TRANSIENT, $sanitized, self::INFO_TTL );

		wp_send_json_success(
			array(
				'html' => self::render_language_select( $sanitized ),
			)
		);
	}

	/** Upper bound for the getInfo JSON payload (a language list is a few KB). */
	const MAX_PAYLOAD_BYTES = 65536;

	/**
	 * The SDK getInfo result arrives either as a JSON string or, when jQuery
	 * serializes the result object into form fields, as a nested array.
	 *
	 * @param mixed $payload Raw POST payload.
	 * @return array|null
	 */
	private static function parse_payload( $payload ) {
		if ( is_string( $payload ) ) {
			$payload = wp_unslash( $payload );
			if ( '' === $payload || strlen( $payload ) > self::MAX_PAYLOAD_BYTES ) {
				return null;
			}

			$decoded = json_decode( $payload, true );

			return ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) ? $decoded : null;
		}

		if ( is_array( $payload ) ) {
			$payload = wp_unslash( $payload );

			// jQuery may submit getInfoResult as nested form fields. Measure the
			// normalized representation so this path cannot bypass the size cap.
			$encoded = wp_json_encode( $payload );
			if ( false === $encoded || strlen( $encoded ) > self::MAX_PAYLOAD_BYTES ) {
				return null;
			}

			return $payload;
		}

		return null;
	}

	/**
	 * @param array $proofreader_info Raw info.
	 * @return array
	 */
	private static function sanitize_info( array $proofreader_info ): array {
		$sanitized = array(
			'langList' => array(
				'ltr' => array(),
				'rtl' => array(),
			),
		);

		if ( empty( $proofreader_info['langList'] ) || ! is_array( $proofreader_info['langList'] ) ) {
			return $sanitized;
		}

		foreach ( array( 'ltr', 'rtl' ) as $text_direction ) {
			if ( empty( $proofreader_info['langList'][ $text_direction ] ) || ! is_array( $proofreader_info['langList'][ $text_direction ] ) ) {
				continue;
			}

			foreach ( $proofreader_info['langList'][ $text_direction ] as $code => $label ) {
				$code  = sanitize_text_field( (string) $code );
				$label = sanitize_text_field( (string) $label );
				if ( '' !== $code && '' !== $label ) {
					$sanitized['langList'][ $text_direction ][ $code ] = $label;
				}
			}
		}

		return $sanitized;
	}

	/**
	 * @param array $info Sanitized proofreader info.
	 * @return string
	 */
	private static function render_language_select( array $info ): string {
		$current_language = WProofreader::instance()->get_language();
		$all_languages    = array_merge( $info['langList']['ltr'], $info['langList']['rtl'] );

		ob_start();
		?>
		<select class="regular" name="wsc_proofreader[slang]" id="wsc_proofreader[slang]">
			<?php foreach ( $all_languages as $code => $label ) : ?>
				<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $current_language, $code ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php

		return (string) ob_get_clean();
	}
}
