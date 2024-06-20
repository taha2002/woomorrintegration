<?php
/**
 * Woomorrintegration media upload
 *
 * @package WOOMORRINTEGRATION
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Filters the REST request before callbacks.
 *
 * @param mixed          $response The response to send.
 * @param array          $handler  Route handler used in the request.
 * @param \WP_REST_Request $request  Request used to generate the response.
 * @return mixed Response.
 */
add_filter(
	'rest_request_before_callbacks',
	function( $response, array $handler, \WP_REST_Request $request ) {

		if ( is_media_create_wp_request( $request ) ) {
			$api_key      = get_option( 'woomorrintegration_api_secret_key' );
			$provided_key = $request->get_header( 'auth' );
			// $provided_key = isset( $_SERVER['HTTP_X_FFINTEGRATION_API_KEY'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FFINTEGRATION_API_KEY'] ) ) : '';
			if ( $provided_key === $api_key ) {
				wp_set_current_user( 1 );
			}
		}
		return $response;
	},
	10,
	3
);

/**
 * Checks if the REST request is for media creation in WordPress.
 *
 * @param \WP_REST_Request $request The request object.
 * @return bool Whether the request is for media creation.
 */
function is_media_create_wp_request( \WP_REST_Request $request ) {
	$route = $request->get_route();

	return (
		'/wp/v2/media' === $route
		&& ( 'POST' === $request->get_method() || 'DELETE' === $request->get_method() || 'GET' === $request->get_method() )
	) || (
		preg_match( '/^\/wp\/v2\/media\/\d+$/', $route )
		&& ( 'GET' === $request->get_method() || 'DELETE' === $request->get_method() )
	);
}

/**
 * Validates WooCommerce authentication parameters.
 *
 * @param \WP_REST_Request $request The REST request object.
 * @return int|false User ID if authentication parameters are valid, false otherwise.
 */
function is_woo_auth_params_valid( $request ) {
	global $wpdb;

	$params = $request->get_params();

	if ( isset( $params['consumer_key'] ) && isset( $params['consumer_secret'] ) ) {
		$consumer_key_param    = $params['consumer_key'];
		$consumer_secret_param = $params['consumer_secret'];
	} else {
		$auth_header = $request->get_header( 'Authorization' );

		if ( empty( $auth_header ) || strpos( $auth_header, 'Basic ' ) !== 0 ) {
			// No Basic Authentication header present.
			return false;
		}

		// Decode Basic Authentication credentials.
		$credentials = base64_decode( substr( $auth_header, 6 ) );
		if ( ! $credentials ) {
			// Unable to decode credentials.
			return false;
		}

		list( $consumer_key_param, $consumer_secret_param ) = explode( ':', $credentials, 2 );
	}

	$consumer_key = wc_api_hash( sanitize_text_field( $consumer_key_param ) );

	$keys = $wpdb->get_row(
		$wpdb->prepare(
			"
				SELECT key_id, user_id, permissions, consumer_key, consumer_secret, nonces
				FROM {$wpdb->prefix}woocommerce_api_keys
				WHERE consumer_key = '%s'
			",
			$consumer_key
		),
		ARRAY_A
	);

	if ( empty( $keys ) ) {
		return false;
	}

	if ( ! hash_equals( $keys['consumer_secret'], $consumer_secret_param ) ) {
		return false;
	}

	return $keys['user_id'];
}
