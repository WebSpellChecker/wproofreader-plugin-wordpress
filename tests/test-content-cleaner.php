<?php
/**
 * Standalone test runner for WProofreader_Content_Cleaner.
 *
 * Usage (inside a WP container): php tests/test-content-cleaner.php /path/to/wp-load.php
 * Exits non-zero on failure.
 */

$wp_load = $argv[1] ?? '/var/www/web/wp/wp-load.php';
require $wp_load;

if ( ! class_exists( 'WProofreader_Content_Cleaner' ) ) {
	require dirname( __DIR__ ) . '/includes/class-wproofreader-content-cleaner.php';
}

$failures = 0;

function check( string $name, bool $condition, string $detail = '' ) {
	global $failures;
	if ( $condition ) {
		echo "PASS  {$name}\n";
	} else {
		$failures++;
		echo "FAIL  {$name}" . ( $detail ? " — {$detail}" : '' ) . "\n";
	}
}

function clean( string $html ): string {
	return WProofreader_Content_Cleaner::clean_content( $html );
}

function hook_path( string $html ): string {
	$data = WProofreader_Content_Cleaner::clean_post_data(
		array( 'post_content' => wp_slash( $html ), 'post_status' => 'private' ),
		array()
	);
	return wp_unslash( $data['post_content'] );
}

// 1. Clean content without artifact markers is untouched (wsc- URLs included).
$plain = '<p>Hello <a href="https://webspellchecker.com/wsc-proofreader/">link</a></p>';
check( 'clean content untouched', clean( $plain ) === $plain );

// 2. Simple artifact span is unwrapped.
check(
	'simple span unwrapped',
	clean( '<p><span class="wsc-spelling-problem">word</span> rest</p>' ) === '<p>word rest</p>'
);

// 3. Multi-class artifact span with extra attributes.
check(
	'multi-class span with attributes',
	clean( '<p><span data-wsc-id="9" class="wsc-spelling-problem wsc-problem-42" style="background:red">word</span></p>' ) === '<p>word</p>'
);

// 4. Nested artifact spans.
check(
	'nested artifact spans',
	clean( '<p><span class="wsc-grammar-problem">a <span class="wsc-spelling-problem">b</span> c</span></p>' ) === '<p>a b c</p>'
);

// 5. Artifact span nested inside a regular span: regular span preserved byte-identical.
check(
	'regular span preserved',
	clean( "<p><span class='keep' data-x='1'>a <span class=\"wsc-spelling-problem\">b</span></span></p>" ) === "<p><span class='keep' data-x='1'>a b</span></p>"
);

// 6. rangySelectionBoundary removal.
check(
	'rangy boundary removed',
	clean( "<p>a<span class=\"rangySelectionBoundary\">\xE2\x80\x8B</span>b</p>" ) === "<p>a\xE2\x80\x8Bb</p>"
);

// 7. Unclosed artifact opener: opener dropped, rest intact.
check(
	'unclosed artifact opener',
	clean( '<p><span class="wsc-spelling-problem">word</p>' ) === '<p>word</p>'
);

// 8. Quoting / entities / boolean attributes outside artifacts stay byte-identical.
$tricky = '<figure class=\'"wp-block-table\' custom><a href=\'"https://x"\' rel=\'"nofollow"\'>x</a>'
	. '<span class="wsc-spelling-problem">w</span>&nbsp;&amp;  <br><svg viewBox="0 0 1 1"><rect/></svg></figure>';
$expected = '<figure class=\'"wp-block-table\' custom><a href=\'"https://x"\' rel=\'"nofollow"\'>x</a>'
	. 'w&nbsp;&amp;  <br><svg viewBox="0 0 1 1"><rect/></svg></figure>';
check( 'byte-identical outside splices', clean( $tricky ) === $expected );

// 9. Gutenberg block comments survive.
$blocks = "<!-- wp:paragraph -->\n<p><span class=\"wsc-grammar-problem\">Текст українською</span> 🚀</p>\n<!-- /wp:paragraph -->";
check(
	'block comments and unicode survive',
	clean( $blocks ) === "<!-- wp:paragraph -->\n<p>Текст українською 🚀</p>\n<!-- /wp:paragraph -->"
);

// 10. Slashed hook path: regression for the 3.1.1 corruption bug.
// Realistic Gutenberg fixture: quoted block attributes, existing &quot; entities,
// and a wsc- URL that used to false-positive the old substring guard.
$baseline = '<!-- wp:heading {"level":2,"anchor":"h-custom"} -->' . "\n"
	. '<h2 class="wp-block-heading" id="h-custom">Say &quot;hi&quot;</h2>' . "\n"
	. '<!-- /wp:heading -->' . "\n"
	. '<!-- wp:image {"id":42,"sizeSlug":"large","linkDestination":"custom"} -->' . "\n"
	. '<figure class="wp-block-image size-large"><a href="https://webspellchecker.com/wsc-proofreader/">'
	. '<img src="https://x.test/a.png" alt="alt with &quot;quotes&quot;"/></a></figure>' . "\n"
	. '<!-- /wp:image -->' . "\n"
	. '<!-- wp:paragraph -->' . "\n"
	. '<p>Plain <a href="https://webspellchecker.com/wsc-proofreader/" rel="noopener">link</a> text.</p>' . "\n"
	. '<!-- /wp:paragraph -->';
if ( file_exists( '/tmp/before_editor_save.html' ) ) {
	$real = file_get_contents( '/tmp/before_editor_save.html' );
	check( 'slashed hook path lossless on real post baseline', hook_path( $real ) === $real );
}
check( 'slashed hook path lossless on clean content', hook_path( $baseline ) === $baseline );

// 11. Slashed hook path with artifacts: cleaned, no &quot; inflation.
$dirty = str_replace( '<p>', '<p><span class="wsc-spelling-problem">x</span> ', $baseline );
$cleaned = hook_path( $dirty );
check( 'slashed hook path removes artifacts', strpos( $cleaned, 'wsc-spelling-problem' ) === false );
check(
	'slashed hook path adds no quot entities',
	substr_count( $cleaned, '&quot;' ) === substr_count( $baseline, '&quot;' )
);

// 11b. Double invocation (Gutenberg REST save + meta-box-loader re-save) is idempotent.
$pass1 = hook_path( $dirty );
$pass2 = hook_path( $pass1 );
check( 'double hook run idempotent', $pass2 === $pass1 );
check( 'double hook run adds no quot entities', substr_count( $pass2, '&quot;' ) === substr_count( $baseline, '&quot;' ) );

// 12. Autosave/revision statuses are no longer skipped.
$autosave = WProofreader_Content_Cleaner::clean_post_data(
	array( 'post_content' => wp_slash( '<p><span class="wsc-spelling-problem">w</span></p>' ), 'post_status' => 'inherit' ),
	array()
);
check( 'inherit status cleaned', wp_unslash( $autosave['post_content'] ) === '<p>w</p>' );

// 13. Empty content untouched.
$empty = WProofreader_Content_Cleaner::clean_post_data( array( 'post_content' => '' ), array() );
check( 'empty content untouched', '' === $empty['post_content'] );

// 14. Span with artifact class substring in another class name must NOT match.
$lookalike = '<p><span class="wsc-spelling-problem-note">keep wrapper</span></p>';
check( 'class lookalike preserved', clean( $lookalike ) === $lookalike );

echo $failures ? "\n{$failures} FAILURE(S)\n" : "\nALL PASS\n";
exit( $failures ? 1 : 0 );
