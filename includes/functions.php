<?php
/**
 * Woomorrintegration
 *
 * @package WOOMORRINTEGRATION
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

function enqueue_custom_css() {
	if ( is_shop() ) {
		wp_enqueue_style( 'custom-woo-shop-css', WOOMORRINTEGRATION_PLUGIN_URL . '/assets/css/shop.css', array(), time() );
	}
	wp_enqueue_style( 'custom-style-css', WOOMORRINTEGRATION_PLUGIN_URL . '/assets/css/style.css', array(), time() );
}
add_action( 'wp_enqueue_scripts', 'enqueue_custom_css' );
