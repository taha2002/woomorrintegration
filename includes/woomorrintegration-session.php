<?php
/**
 * Woomorrintegration session
 *
 * @package WOOMORRINTEGRATION
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Removes X-Frame-Options header and sets it to ALLOWALL in the footer.
 */
add_action(
	'wp_footer',
	function() {
		header_remove( 'X-Frame-Options' );
		header( 'X-Frame-Options: ALLOWALL' );
	}
);

/**
 * Custom authentication function for user login.
 */
function custom_authenticate_user() {
	if ( isset( $_GET['email'] ) && isset( $_GET['password'] ) ) {
		$email    = sanitize_email( $_GET['email'] ); // Sanitize email input.
		$password = wp_unslash( $_GET['password'] ); // Unslash password input.

		$user = get_user_by( 'email', $email );

		if ( $user && wp_check_password( $password, $user->user_pass, $user->ID ) ) {
			wp_set_current_user( $user->ID, $user->user_login );
			wp_set_auth_cookie( $user->ID );

			modify_cookie_headers();
		}
	}

	if ( isset( $_GET['fb_jwt'] ) && function_exists( 'firebase_jwt_token_validate' ) ) {
		$email = firebase_jwt_token_validate( wp_unslash( $_GET['fb_jwt'] ) );

		if ( ! $email ) {
			return;
		}

		$user = get_user_by( 'email', $email );

		if ( $user ) {
			wp_set_current_user( $user->ID, $user->user_login );
			wp_set_auth_cookie( $user->ID );

			modify_cookie_headers();
		}
	}
}
add_action( 'init', 'custom_authenticate_user', 9999 );


add_action(
	'woocommerce_set_cart_cookies',
	function() {
		modify_cookie_headers();
	},
	9999
);


/**
 * Modifies cookie headers to include SameSite=None.
 */
function modify_cookie_headers() {
	$all_headers        = headers_list();
	$set_cookie_headers = array();
	$new_cookie_headers = array();

	// Filter out Set-Cookie headers.
	foreach ( $all_headers  as $header ) {
		if ( strpos( $header, 'Set-Cookie:' ) === 0 ) {
			$set_cookie_headers[] = $header;
		}
	}

	// Modify each Set-Cookie header to include SameSite=None, or add it if not present.
	foreach ( $set_cookie_headers as &$cookie ) {
		// Add SameSite=None if it doesn't exist.
		if ( strpos( $cookie, 'SameSite=' ) === false ) {
			$cookie .= '; SameSite=None';
		} else {
			// Replace existing SameSite attribute with None.
			$cookie = preg_replace( '/; *SameSite=[^;]+/', '; SameSite=None', $cookie );
		}

		$new_cookie_headers[] = $cookie;
	}

	// Set the modified Set-Cookie headers.
	header_remove( 'Set-Cookie' ); // Remove existing Set-Cookie headers.
	foreach ( $new_cookie_headers as $cookie ) {
		header( $cookie, false );
	}

	// $cookies = headers_list();
	// var_dump($cookies);
	// wp_die("hi");
}
