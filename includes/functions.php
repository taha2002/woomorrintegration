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


add_filter(
	'rest_product_collection_params',
	function( $params ) {
		$params['per_page']['maximum'] = 1000;
		return $params;
	}
);

add_filter(
	'rest_shop_order_collection_params',
	function( $params ) {
		$params['per_page']['maximum'] = 1000;
		return $params;
	}
);

function hide_admin_elements_in_admin() {
	// Check if the GET parameter 'nextapp' is set and true
	if ( isset( $_GET['nextapp'] ) && $_GET['nextapp'] === 'true' ) {
		// Output inline CSS to hide #wpadminbar and #adminmenumain
		echo '<style>
            #wpadminbar,
            #adminmenumain {
                display: none !important;
            }
			
			html.wp-toolbar{
				padding-top: 0px !important;
			}
			
			.interface-interface-skeleton__header, .interface-interface-skeleton{
				top: 0px !important;
			}
			
			#wpcontent, #wpfooter {
				margin-left: 0px !important;
			}
			
			.interface-interface-skeleton{
				left: 0px !important;
			}
        </style>';
	}
}
add_action( 'admin_head', 'hide_admin_elements_in_admin' );

// WordPress Core remove x frame option disallow.
remove_action( 'admin_init', 'send_frame_options_header' );
remove_action( 'login_init', 'send_frame_options_header' );
remove_action( 'init', 'send_frame_options_header' );
function fasal_basha_send_frame_options_header() {
	header_remove( 'X-Frame-Options' );
}
add_action( 'admin_init', 'fasal_basha_send_frame_options_header', 11, 0 );
add_action( 'login_init', 'fasal_basha_send_frame_options_header', 11, 0 );

add_filter( 'woocommerce_store_api_disable_nonce_check', '__return_true' );
