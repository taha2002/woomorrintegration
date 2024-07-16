<?php
/**
 * Functions for extra apis.
 *
 * @package WOOMORRINTEGRATION
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Register REST API routes for order items endpoint.
 */
function order_items_api_init() {
	register_rest_route(
		'woomorrintegration/v1',
		'/orderproducts/(?P<id>\d+)',
		array(
			'methods'             => 'GET',
			'callback'            => 'get_order_items',
			'permission_callback' => function ( WP_REST_Request $request ) {
				$api_key = get_option( 'woomorrintegration_api_secret_key' );
				$provided_key = $request->get_header( 'auth' );
				return $provided_key === $api_key;
			},
			'args'                => array(
				'id' => array(
					'description' => 'Unique identifier for the order.',
					'required'    => true,
					'type'        => 'integer',
				),
			),
		)
	);

	register_rest_route(
		'woomorrintegration/v1',
		'/orderproducts',
		array(
			'methods'             => 'GET',
			'callback'            => 'get_recent_order_items',
			'permission_callback' => function ( WP_REST_Request $request ) {
				$api_key = get_option( 'woomorrintegration_api_secret_key' );
				$provided_key = $request->get_header( 'auth' );
				return $provided_key === $api_key;
			},
			'args'                => array(
				'order_per_page' => array(
					'description' => 'Number of orders to retrieve.',
					'required'    => false,
					'type'        => 'integer',
					'default'     => 10,
				),
			),
		)
	);

}
add_action( 'rest_api_init', 'order_items_api_init' );

/**
 * Get order items for a specific order.
 *
 * @param WP_REST_Request $request Full data about the request.
 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
 */
function get_order_items( WP_REST_Request $request ) {
	$order_id = $request['id'];

	// Get the order data.
	$order = wc_get_order( $order_id );

	if ( ! $order ) {
		return new WP_Error( 'order_not_found', __( 'Order not found.' ), array( 'status' => 404 ) );
	}

	// Get the order items.
	$order_items = $order->get_items();

	// Prepare the response data.
	$response_data = array();
	foreach ( $order_items as $item_id => $item ) {
		// $product   = $item->get_product();
		$variation = $item->get_product_variation();

		$response_data[] = array(
			'order_id'        => $order->get_id(),
			'item_id'         => $item_id,
			'product_id'      => $item->get_product_id(),
			'variation_id'    => $variation ? $variation->get_id() : 0,
			'business_number' => get_post_meta( $order_id, 'business_number', true ),
			'business_name'   => get_post_meta( $order_id, 'business_name', true ),
			'order_status'    => $order->get_status(),
			'quantity'        => $item->get_quantity(),
			'subtotal'        => $item->get_subtotal(),
			'subtotal_tax'    => $item->get_subtotal_tax(),
			'total'           => $item->get_total(),
			'total_tax'       => $item->get_total_tax(),
			'taxes'           => $item->get_taxes(),
			// 'SKU'             => $product->get_sku(),
			// 'price'           => $product->get_price(),
			// 'image'           => $product->get_image(),
		);
	}

	return new WP_REST_Response( $response_data, 200 );
}

/**
 * Get the last modified orders and their items.
 *
 * @param WP_REST_Request $request Full data about the request.
 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
 */
function get_recent_order_items( WP_REST_Request $request ) {
	$order_per_page = $request->get_param( 'order_per_page' );

	// Retrieve the last modified orders.
	$orders_query = new WP_Query(
		array(
			'post_type'      => 'shop_order',
			'posts_per_page' => $order_per_page,
			'orderby'        => 'modified',
			'order'          => 'DESC',
		)
	);

	if ( $orders_query->have_posts() ) {
		$response_data = array();

		while ( $orders_query->have_posts() ) {
			$orders_query->the_post();
			$order = wc_get_order( get_the_ID() );

			// Get the order items.
			$order_items = $order->get_items();

			foreach ( $order_items as $item_id => $item ) {
				$variation = $item->get_product_variation();

				$response_data[] = array(
					'order_id'        => $order->get_id(),
					'item_id'         => $item_id,
					'product_id'      => $item->get_product_id(),
					'variation_id'    => $variation ? $variation->get_id() : 0,
					'business_number' => get_post_meta( $order->get_id(), 'business_number', true ),
					'business_name'   => get_post_meta( $order->get_id(), 'business_name', true ),
					'order_status'    => $order->get_status(),
					'quantity'        => $item->get_quantity(),
					'subtotal'        => $item->get_subtotal(),
					'subtotal_tax'    => $item->get_subtotal_tax(),
					'total'           => $item->get_total(),
					'total_tax'       => $item->get_total_tax(),
					'taxes'           => $item->get_taxes(),
				);
			}
		}

		wp_reset_postdata();

		return new WP_REST_Response( $response_data, $response_data );
	} else {
		return new WP_Error( 'no_orders_found', __( 'No orders found.' ), array( 'status' => 404 ) );
	}
}
