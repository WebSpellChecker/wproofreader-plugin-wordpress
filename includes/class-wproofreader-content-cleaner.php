<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Removes WProofreader editing artifacts before post content is saved.
 *
 * Artifact spans are spliced out of the original byte stream with
 * WP_HTML_Tag_Processor offsets, so content outside the removed tags is
 * never re-serialized or normalized.
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
	 * @param array $data Post data (slashed, as provided by wp_insert_post_data).
	 * @param array $postarr Raw post array.
	 * @return array
	 */
	public static function clean_post_data( $data, $postarr ) {
		if ( empty( $data['post_content'] ) ) {
			return $data;
		}

		// Data on wp_insert_post_data is slashed; clean unslashed HTML and re-slash.
		$original_content = wp_unslash( (string) $data['post_content'] );
		$clean_content    = self::clean_content( $original_content );

		// Cleanup only removes bytes; anything else means a bug, keep the original.
		if ( strlen( $clean_content ) > strlen( $original_content ) ) {
			do_action( 'wproofreader_cleanup_failed', $original_content, $clean_content, $postarr );
			$clean_content = $original_content;
		}

		$data['post_content'] = wp_slash( $clean_content );

		return $data;
	}

	/**
	 * Remove artifact span tags (open and close), keeping their inner content.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public static function clean_content( string $content ): string {
		$has_artifacts = false;
		foreach ( self::$artifact_classes as $class ) {
			if ( false !== strpos( $content, $class ) ) {
				$has_artifacts = true;
				break;
			}
		}

		if ( ! $has_artifacts || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			return $content;
		}

		$scanner = new WProofreader_HTML_Scanner( $content );
		$ranges  = $scanner->artifact_span_ranges( self::$artifact_classes );

		if ( empty( $ranges ) ) {
			return $content;
		}

		// Splice from the end so earlier offsets stay valid.
		usort(
			$ranges,
			static function ( $a, $b ) {
				return $b['start'] - $a['start'];
			}
		);

		foreach ( $ranges as $range ) {
			$content = substr_replace( $content, '', $range['start'], $range['length'] );
		}

		return $content;
	}
}

if ( class_exists( 'WP_HTML_Tag_Processor' ) ) {
	/**
	 * Read-only scanner exposing byte ranges of artifact span tags.
	 */
	class WProofreader_HTML_Scanner extends WP_HTML_Tag_Processor {

		const BOOKMARK = 'wproofreader_token';

		/**
		 * Collect byte ranges of every artifact span opener and its matching closer.
		 *
		 * @param array $artifact_classes Class names marking artifact spans.
		 * @return array[] List of array{start: int, length: int}.
		 */
		public function artifact_span_ranges( array $artifact_classes ): array {
			$ranges = array();
			$stack  = array();

			while ( $this->next_tag( array( 'tag_closers' => 'visit' ) ) ) {
				if ( 'SPAN' !== $this->get_tag() ) {
					continue;
				}

				if ( $this->is_tag_closer() ) {
					$opener = array_pop( $stack );
					if ( null !== $opener ) {
						$ranges[] = $opener;
						$ranges[] = $this->current_token_range();
					}
					continue;
				}

				$is_artifact = false;
				foreach ( $artifact_classes as $class ) {
					if ( true === $this->has_class( $class ) ) {
						$is_artifact = true;
						break;
					}
				}

				$stack[] = $is_artifact ? $this->current_token_range() : null;
			}

			// Unclosed artifact openers: drop the opener tag alone.
			foreach ( $stack as $entry ) {
				if ( null !== $entry ) {
					$ranges[] = $entry;
				}
			}

			return $ranges;
		}

		/**
		 * Byte range of the token the processor is currently stopped on.
		 *
		 * @return array{start: int, length: int}
		 */
		private function current_token_range(): array {
			$this->set_bookmark( self::BOOKMARK );
			$span = $this->bookmarks[ self::BOOKMARK ];
			$this->release_bookmark( self::BOOKMARK );

			return array(
				'start'  => $span->start,
				'length' => $span->length,
			);
		}
	}
}
