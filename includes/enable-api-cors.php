<?php
/**
 * Woomorrintegration enable api cors
 *
 * @package WOOMORRINTEGRATION
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


function handle_preflight() {
	$origin = get_http_origin();
	header( 'Access-Control-Allow-Origin: * ' );
	// header( 'Access-Control-Allow-Origin: ' . $origin_url );
	header( 'Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE' );
	header( 'Access-Control-Allow-Credentials: true' );
	header( 'Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, x_ffintegration_api_key, Authorization' );

	if ( 'OPTIONS' == $_SERVER['REQUEST_METHOD'] ) {
		status_header( 200 );
		exit();
	}

	if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
		$requesting_domain = $_SERVER['HTTP_REFERER'];
		header( "X-Frame-Options: ALLOW-FROM $requesting_domain" );

	}
}
add_action( 'init', 'handle_preflight' );
// add_action( 'rest_api_init', 'handle_preflight', 15 );

/**
 * Filters incoming REST API connections.
 *
 * @param mixed $errors The error object.
 * @return mixed The modified error object.
 */
function rest_filter_incoming_connections( $errors ) {
	$request_server = $_SERVER['REMOTE_ADDR'];
	$origin         = get_http_origin();
	return $errors;
	if ( $origin !== 'https://mabuildbeta.flutterflow.app' && ! empty( $errors ) ) {
		return new WP_Error(
			'forbidden_access',
			$origin,
			array(
				'status' => 403,
			)
		);
	}
	return $errors;
}
add_filter( 'rest_authentication_errors', 'rest_filter_incoming_connections' );
