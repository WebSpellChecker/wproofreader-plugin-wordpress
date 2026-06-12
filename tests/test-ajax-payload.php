<?php
/**
 * Standalone regression tests for WProofreader_Ajax payload parsing.
 *
 * Usage: php tests/test-ajax-payload.php
 */

define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'DAY_IN_SECONDS', 86400 );

function wp_unslash( $value ) {
	return is_array( $value ) ? array_map( 'wp_unslash', $value ) : stripslashes( $value );
}

function wp_json_encode( $value ) {
	return json_encode( $value );
}

require dirname( __DIR__ ) . '/includes/class-wproofreader-ajax.php';

$failures = 0;
$method   = new ReflectionMethod( 'WProofreader_Ajax', 'parse_payload' );

function check_ajax_payload( string $name, bool $condition ) {
	global $failures;

	if ( $condition ) {
		echo "PASS  {$name}\n";
		return;
	}

	$failures++;
	echo "FAIL  {$name}\n";
}

$valid = array(
	'langList' => array(
		'ltr' => array( 'en_US' => 'English' ),
		'rtl' => array(),
	),
);

check_ajax_payload(
	'valid JSON payload accepted',
	$method->invoke( null, wp_json_encode( $valid ) ) === $valid
);
check_ajax_payload(
	'valid array payload accepted',
	$method->invoke( null, $valid ) === $valid
);
check_ajax_payload(
	'oversized JSON payload rejected',
	null === $method->invoke( null, wp_json_encode( array( 'data' => str_repeat( 'x', 65536 ) ) ) )
);
check_ajax_payload(
	'oversized array payload rejected',
	null === $method->invoke( null, array( 'data' => str_repeat( 'x', 65536 ) ) )
);
check_ajax_payload(
	'invalid JSON payload rejected',
	null === $method->invoke( null, '{"langList":' )
);

echo $failures ? "\n{$failures} FAILURE(S)\n" : "\nALL PASS\n";
exit( $failures ? 1 : 0 );
