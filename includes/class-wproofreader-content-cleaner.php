<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Removes WProofreader editing artifacts before post content is saved.
 */
class WProofreader_Content_Cleaner {

	/** @var array */
	private static $artifact_classes = array(
		'wsc-spelling-problem',
		'wsc-grammar-problem',
		'rangySelectionBoundary',
	);

	/**
	 * Register content cleanup filter.
	 */
	public static function register() {
		add_filter( 'wp_insert_post_data', array( __CLASS__, 'clean_post_data' ), 100, 2 );
	}

	/**
	 * @param array $data Post data.
	 * @param array $postarr Raw post array.
	 * @return array
	 */
	public static function clean_post_data( $data, $postarr ) {
		if ( empty( $data['post_content'] ) ) {
			return $data;
		}

		if ( isset( $data['post_status'] ) && in_array( $data['post_status'], array( 'auto-draft', 'inherit' ), true ) ) {
			return $data;
		}

		$original_content = (string) $data['post_content'];
		$clean_content    = self::clean_content( $original_content );

		// Defensive guard: never let cleanup unexpectedly inflate saved content.
		if ( strlen( $clean_content ) > strlen( $original_content ) * 1.25 ) {
			$clean_content = wp_kses_post( $original_content );
		}

		$data['post_content'] = $clean_content;

		return $data;
	}

	/**
	 * @param string $content Post content.
	 * @return string
	 */
	public static function clean_content( string $content ): string {
		if ( false === strpos( $content, 'wsc-' ) && false === strpos( $content, 'rangySelectionBoundary' ) ) {
			return $content;
		}

		if ( class_exists( 'DOMDocument' ) ) {
			$cleaned = self::clean_with_dom_document( $content );
			if ( null !== $cleaned ) {
				return $cleaned;
			}
		}

		return self::clean_with_regex_fallback( $content );
	}

	/**
	 * @param string $content Post content.
	 * @return string|null
	 */
	private static function clean_with_dom_document( string $content ) {
		$previous = libxml_use_internal_errors( true );
		$document = new DOMDocument();
		$wrapper_id = 'wproofreader-cleanup-root';
		$html = '<div id="' . $wrapper_id . '">' . $content . '</div>';

		$loaded = $document->loadHTML(
			'<?xml encoding="utf-8" ?>' . $html,
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		if ( ! $loaded ) {
			return null;
		}

		$xpath = new DOMXPath( $document );
		$query_parts = array();
		foreach ( self::$artifact_classes as $class ) {
			$query_parts[] = 'contains(concat(" ", normalize-space(@class), " "), " ' . $class . ' ")';
		}

		$nodes = $xpath->query( '//*[self::span and (' . implode( ' or ', $query_parts ) . ')]' );
		if ( ! $nodes ) {
			return null;
		}

		for ( $index = $nodes->length - 1; $index >= 0; $index-- ) {
			$node = $nodes->item( $index );
			if ( ! $node || ! $node->parentNode ) {
				continue;
			}

			while ( $node->firstChild ) {
				$node->parentNode->insertBefore( $node->firstChild, $node );
			}
			$node->parentNode->removeChild( $node );
		}

		$wrapper = $document->getElementById( $wrapper_id );
		if ( ! $wrapper ) {
			return null;
		}

		$output = '';
		foreach ( $wrapper->childNodes as $child ) {
			$output .= $document->saveHTML( $child );
		}

		return $output;
	}

	/**
	 * @param string $content Post content.
	 * @return string
	 */
	private static function clean_with_regex_fallback( string $content ): string {
		$cleanup_patterns = array(
			'#<span\s+class=(["\'])wsc-spelling-problem\1[^>]*>(.*?)</span>#si'   => '$2',
			'#<span\s+class=(["\'])wsc-grammar-problem\1[^>]*>(.*?)</span>#si'    => '$2',
			'#<span\s+class=(["\'])rangySelectionBoundary\1[^>]*>(.*?)</span>#si' => '$2',
		);

		foreach ( $cleanup_patterns as $pattern => $replacement ) {
			$content = preg_replace( $pattern, $replacement, $content );
		}

		return (string) $content;
	}
}
