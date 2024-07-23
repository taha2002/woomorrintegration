<?php
/**
 * Woomorrintegration woo custom api fields.
 *
 * @package WOOMORRINTEGRATION
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Registers custom fields for WooCommerce products, orders, coupons, and product categories.
 */
function register_ffintegration_custom_field() {
	register_rest_field(
		'product',
		'business_name',
		array(
			'get_callback'    => 'get_ffintegration_custom_api_field',
			'update_callback' => 'update_ffintegration_custom_api_field',
			'schema'          => null,
		)
	);
	register_rest_field(
		'product',
		'business_number',
		array(
			'get_callback'    => 'get_ffintegration_custom_api_field',
			'update_callback' => 'update_ffintegration_custom_api_field',
			'schema'          => null,
		)
	);

	register_rest_field(
		'shop_order',
		'business_name',
		array(
			'get_callback'    => 'get_ffintegration_custom_api_field',
			'update_callback' => 'update_ffintegration_custom_api_field',
			'schema'          => null,
		)
	);
	register_rest_field(
		'shop_order',
		'business_number',
		array(
			'get_callback'    => 'get_ffintegration_custom_api_field',
			'update_callback' => 'update_ffintegration_custom_api_field',
			'schema'          => null,
		)
	);

	register_rest_field(
		'shop_order',
		'from_business_name',
		array(
			'get_callback'    => 'get_ffintegration_custom_api_field',
			'update_callback' => 'update_ffintegration_custom_api_field',
			'schema'          => null,
		)
	);
	register_rest_field(
		'shop_order',
		'from_business_number',
		array(
			'get_callback'    => 'get_ffintegration_custom_api_field',
			'update_callback' => 'update_ffintegration_custom_api_field',
			'schema'          => null,
		)
	);

	register_rest_field(
		'shop_order',
		'for_business_name',
		array(
			'get_callback'    => 'get_ffintegration_custom_api_field',
			'update_callback' => 'update_ffintegration_custom_api_field',
			'schema'          => null,
		)
	);
	register_rest_field(
		'shop_order',
		'for_business_number',
		array(
			'get_callback'    => 'get_ffintegration_custom_api_field',
			'update_callback' => 'update_ffintegration_custom_api_field',
			'schema'          => null,
		)
	);

	register_rest_field(
		'shop_order',
		'status_logs',
		array(
			'get_callback' => 'get_order_status_logs',
			'schema'       => null,
		)
	);

	register_rest_field(
		'shop_order',
		'customer_email',
		array(
			'get_callback' => function ( $object, $field_name, $request ) {
					$customer_id = $object['customer_id'];
				if ( $customer_id ) {
					$customer_data = get_userdata( $customer_id );
					if ( $customer_data ) {
						return $customer_data->user_email;
					}
				}
					return null;
			},
			'schema'       => null,
		)
	);

	register_rest_field(
		'shop_coupon',
		'business_name',
		array(
			'get_callback'    => 'get_ffintegration_custom_api_field',
			'update_callback' => 'update_ffintegration_custom_api_field',
			'schema'          => null,
		)
	);
	register_rest_field(
		'shop_coupon	',
		'business_number',
		array(
			'get_callback'    => 'get_ffintegration_custom_api_field',
			'update_callback' => 'update_ffintegration_custom_api_field',
			'schema'          => null,
		)
	);

	register_rest_field(
		'product_cat',
		'business_name',
		array(
			'get_callback'    => 'get_ffintegration_term_custom_api_field',
			'update_callback' => 'update_ffintegration_term_custom_api_field',
			'schema'          => null,
		)
	);
	register_rest_field(
		'product_cat',
		'business_number',
		array(
			'get_callback'    => 'get_ffintegration_term_custom_api_field',
			'update_callback' => 'update_ffintegration_term_custom_api_field',
			'schema'          => null,
		)
	);

	register_rest_route(
		'ffintegration/v1',
		'/orders/status-history',
		array(
			'methods'             => 'GET',
			'callback'            => 'ffintegration_get_order_status_history',
			'permission_callback' => '__return_true',
		)
	);

}
add_action( 'rest_api_init', 'register_ffintegration_custom_field' );

/**
 * Retrieves the value of a custom API field for products and terms.
 *
 * @param array           $object     The object being acted upon.
 * @param string          $field_name Name of the field.
 * @param WP_REST_Request $request    Current request object.
 * @return mixed The value of the custom field.
 */
function get_ffintegration_custom_api_field( $object, $field_name, $request ) {
	return get_post_meta( $object['id'], $field_name, true );
}

/**
 * Updates the value of a custom API field for products and terms.
 *
 * @param mixed  $value      The value of the field.
 * @param object $object     The object being acted upon.
 * @param string $field_name Name of the field.
 * @return bool True on success, false on failure.
 */
function update_ffintegration_custom_api_field( $value, $object, $field_name ) {
	return update_post_meta( $object->id, $field_name, $value );
}

/**
 * Retrieves the value of a custom API field for terms.
 *
 * @param array           $object     The object being acted upon.
 * @param string          $field_name Name of the field.
 * @param WP_REST_Request $request    Current request object.
 * @return mixed The value of the custom field.
 */
function get_ffintegration_term_custom_api_field( $object, $field_name, $request ) {
	return get_term_meta( $object['id'], $field_name, true );
}

/**
 * Updates the value of a custom API field for terms.
 *
 * @param mixed  $value      The value of the field.
 * @param object $object     The object being acted upon.
 * @param string $field_name Name of the field.
 * @return bool True on success, false on failure.
 */
function update_ffintegration_term_custom_api_field( $value, $object, $field_name ) {
	return update_term_meta( $object->term_id, $field_name, $value );
}

/**
 * Filters product or orders by metadata.
 *
 * @param array           $args    Arguments for the query.
 * @param WP_REST_Request $request Current request object.
 * @return array Filtered arguments for the query.
 */
function ffintegration_filter_product_or_orders_by_metadata( $args, $request ) {
	$params = $request->get_query_params();

	if ( isset( $params['business_number'] ) && ! empty( $params['business_number'] ) ) {
		$business_number = sanitize_text_field( $params['business_number'] );

		$source_meta_query = array(
			'key'   => 'business_number',
			'value' => $business_number,
		);

		if ( isset( $args['meta_query'] ) ) {
			$args['meta_query']['relation'] = 'AND';
			$args['meta_query'][]           = $source_meta_query;
		} else {
			$args['meta_query']   = array();
			$args['meta_query'][] = $source_meta_query;
		}

		return $args;
	}

	if ( isset( $params['store_name'] ) && ! empty( $params['store_name'] ) ) {
		$store_name = sanitize_text_field( $params['store_name'] );
		$user       = get_user_by_meta( 'store_name', $store_name );
		if ( $user ) {
			$business_number = get_user_meta( $user->ID, 'business_number', true );

			if ( $business_number ) {
				$source_meta_query = array(
					'key'   => 'business_number',
					'value' => $business_number,
				);

				if ( isset( $args['meta_query'] ) ) {
					$args['meta_query']['relation'] = 'AND';
					$args['meta_query'][]           = $source_meta_query;
				} else {
					$args['meta_query']   = array();
					$args['meta_query'][] = $source_meta_query;
				}

				return $args;
			}
		}
	}

	$fields = array(
		'business_name',
		'from_business_number',
		'from_business_name',
		'for_business_name',
		'for_business_number',
	);

	foreach ( $fields as $field ) {
		if ( isset( $params[ $field ] ) && ! empty( $params[ $field ] ) ) {
			$sanitized_value = sanitize_text_field( $params[ $field ] );

			$source_meta_query = array(
				'key'   => $field,
				'value' => $sanitized_value,
			);

			if ( isset( $args['meta_query'] ) ) {
				$args['meta_query']['relation'] = 'AND';
				$args['meta_query'][]           = $source_meta_query;
			} else {
				$args['meta_query']   = array();
				$args['meta_query'][] = $source_meta_query;
			}
			break;
		}
	}

	return $args;
};
add_filter( 'woocommerce_rest_product_object_query', 'ffintegration_filter_product_or_orders_by_metadata', 10, 2 );
add_filter( 'woocommerce_rest_orders_prepare_object_query', 'ffintegration_filter_product_or_orders_by_metadata', 10, 2 );
add_filter( 'woocommerce_rest_shop_coupon_object_query', 'ffintegration_filter_product_or_orders_by_metadata', 10, 2 );

/**
 * Filters product categories by metadata.
 *
 * @param WP_Term_Query $query The term query.
 */
function ffintegration_filter_product_categories_by_metadata( $query ) {
	if ( is_admin() ) {
		return;
	}
	if ( ! is_this_rest( 'wc/v3/products/categories' ) && ! is_this_rest( 'wc/v3/products/categories/' ) ) {
		return;
	}

	$query_vars = $query->query_vars;

	// Check if we are on a product category page and the 'business_number' parameter is set.
	if ( isset( $_GET['business_number'] ) && ! empty( $_GET['business_number'] ) ) {
		$business_number = sanitize_text_field( $_GET['business_number'] );

		// Add a meta query to filter product categories by 'business_number'.
		$query->query_vars['meta_query'] = array(
			array(
				'key'   => 'business_number',
				'value' => $business_number,
			),
		);
	}
}
add_action( 'pre_get_terms', 'ffintegration_filter_product_categories_by_metadata', 10, 1 );

/**
 * Checks if the current request is a REST API request.
 *
 * @param string $endpoint The endpoint to check.
 * @return bool True if the current request is a REST API request, false otherwise.
 */
function is_this_rest( $endpoint = '' ) {
	if ( ! empty( $endpoint ) ) {
		$rest_url    = wp_parse_url( trailingslashit( rest_url() ) );
		$current_url = wp_parse_url( add_query_arg( array() ) );

		return str_replace( $rest_url['path'], '', $current_url['path'] ) === $endpoint;
	}
	$rest_url    = wp_parse_url( trailingslashit( rest_url() ) );
	$current_url = wp_parse_url( add_query_arg( array() ) );

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST // (#1)
		|| isset( $_GET['rest_route'] ) // (#2)
		&& strpos( $_GET['rest_route'], '/', 0 ) === 0 ) {
		return true;
	}

	global $wp_rewrite;
	if ( $wp_rewrite === null ) {
		$wp_rewrite = new WP_Rewrite();
	}

	$rest_url    = wp_parse_url( trailingslashit( rest_url() ) );
	$current_url = wp_parse_url( add_query_arg( array() ) );
	return strpos( $current_url['path'] ?? '/', $rest_url['path'], 0 ) === 0;
}

/**
 * Retrieves the current user based on the request parameters.
 *
 * @return WP_User|false The user object if found, false otherwise.
 */
function get_request_user() {
	if ( isset( $_REQUEST['action_user_id'] ) && ! empty( $_REQUEST['action_user_id'] ) ) {
		$user_id = $_REQUEST['action_user_id'];
	} elseif ( isset( $_REQUEST['action_user_email'] ) && ! empty( $_REQUEST['action_user_email'] ) ) {
		return get_user_by_email( $_REQUEST['action_user_email'] );
	} else {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return false;
	}
	return get_userdata( $user_id );
}

/**
 * Initiates the order status history by registering pending status on order creation.
 *
 * @param int $order_id The ID of the order.
 */
function init_order_status_history( $order_id ) {
	// Set the default time zone (http://php.net/manual/en/timezones.php).
	// date_default_timezone_set('Europe/Paris').
	$order          = wc_get_order( $order_id );
	$initial_status = $order->get_status();

	$change_log = array();

	$change_log['status'] = $initial_status;

	if ( in_array( $initial_status, array( 'auto-draft' ) ) ) {
		$change_log['price'] = $order->get_total();
	}

	$request_user = get_request_user();
	if ( $request_user ) {
		$change_log['user_id']           = $request_user->ID;
		$change_log['user_display_name'] = $request_user->display_name;
	}
	if ( isset( $_REQUEST['change_message'] ) && ! empty( $_REQUEST['change_message'] ) ) {
		$change_log['message'] = $_REQUEST['change_message'];
	}

	// Init order status history on order creation.
	$order->update_meta_data( '_status_history', array( time() => $change_log ) );
	$order->save();
}
add_action( 'woocommerce_new_order', 'init_order_status_history', 20, 1 );

/**
 * Gets each status change history and saves the data.
 *
 * This function is hooked into the 'woocommerce_order_status_changed' action to record the status change history
 * whenever an order status is changed.
 *
 * @param int    $order_id   The ID of the order.
 * @param string $old_status The old status of the order.
 * @param string $new_status The new status of the order.
 * @param object $order      The WooCommerce order object.
 */
function order_status_history( $order_id, $old_status, $new_status, $order ) {
	// date_default_timezone_set('Europe/Paris').
	// Get order status history.
	$order_status_history = $order->get_meta( '_status_history' ) ? $order->get_meta( '_status_history' ) : array();

	$change_log = array();

	$change_log['status'] = $new_status;

	$request_user = get_request_user();
	if ( $request_user ) {
		$change_log['user_id']           = $request_user->ID;
		$change_log['user_display_name'] = $request_user->display_name;
	}
	if ( isset( $_REQUEST['change_message'] ) && ! empty( $_REQUEST['change_message'] ) ) {
		$change_log['message'] = $_REQUEST['change_message'];
	}

	// Add the current timestamp with the new order status to the history array.
	$order_status_history[ time() ] = $change_log;

	// Update the order status history (as order meta data).
	$order->update_meta_data( '_status_history', $order_status_history );
	$order->save(); // Save.
}
add_action( 'woocommerce_order_status_changed', 'order_status_history', 20, 4 );

/**
 * Retrieves the status change logs for the given order.
 *
 * This function fetches the status change history logs for a specific order.
 *
 * @param object $object     The response object from the REST API.
 * @param string $field_name The name of the field being requested.
 * @param object $request    The request object.
 * @return array            The status change logs for the order.
 */
function get_order_status_logs( $object, $field_name, $request ) {
	// Get an instance of the WC_Order object from the order ID.
	$order        = wc_get_order( $object['id'] );
	$history_logs = array();

	// Get the history data.
	$status_history = $order->get_meta( '_status_history' );
	return $status_history;
}

/**
 * Customize the response for WooCommerce customers.
 *
 * This function modifies the response data for WooCommerce customers, adding custom fields such as
 * business name, business number, for business name, and for business number.
 *
 * @param WP_REST_Response $response The response object from the REST API.
 * @param WP_User          $user_data The user data object.
 * @param WP_REST_Request  $request   The request object.
 * @return WP_REST_Response Modified response object with custom fields.
 */
function custom_product_response( $response, $user_data, $request ) {
	$user_id                               = $user_data->ID;
	$response->data['business_name']       = get_user_meta( $user_id, 'business_name', true );
	$response->data['business_number']     = get_user_meta( $user_id, 'business_number', true );
	$response->data['for_business_name']   = get_user_meta( $user_id, 'for_business_name', true );
	$response->data['for_business_number'] = get_user_meta( $user_id, 'for_business_number', true );
	return $response;
}
add_filter( 'woocommerce_rest_prepare_customer', 'custom_product_response', 10, 3 );

/**
 * Filter customers by for_business_number.
 *
 * This function filters customers by their business number.
 *
 * @param array           $args    The query arguments for the REST API request.
 * @param WP_REST_Request $request The request object.
 * @return array Modified query arguments.
 */
function filter_customers_by_for_business_number( $args, $request ) {

	$params = $request->get_query_params();

	if ( isset( $params['business_number'] ) && ! empty( $params['business_number'] ) ) {
		$business_number   = sanitize_text_field( $params['business_number'] );
		$source_meta_query = array(
			'key'   => 'for_business_number',
			'value' => $business_number,
		);

		if ( isset( $args['meta_query'] ) ) {
			$args['meta_query']['relation'] = 'AND';
			$args['meta_query'][]           = $source_meta_query;
		} else {
			$args['meta_query']   = array();
			$args['meta_query'][] = $source_meta_query;
		}
	}

	return $args;
}
add_filter( 'woocommerce_rest_customer_query', 'filter_customers_by_for_business_number', 999, 2 );


/**
 * Handles the request to get order status history.
 *
 * @param WP_REST_Request $request The request data.
 * @return WP_REST_Response The REST response.
 */
function ffintegration_get_order_status_history( $request ) {
	$response        = array();
	$email           = sanitize_email( $request->get_param( 'email' ) );
	$business_number = $request->get_param( 'business_number' );

	if ( empty( $email ) && empty( $business_number ) ) {
		$response['message'] = 'Email and business number are required.';
		return new WP_REST_Response( $response, 400 );
	}

	if ( $business_number ) {
		$query = array(
			'meta_key'   => 'business_number',
			'meta_value' => $business_number,
			// 'customer_id' => $user->ID,
		);
	} elseif ( $email ) {
		// Get user by email.
		$user = get_user_by( 'email', $email );

		if ( ! $user ) {
			return new WP_REST_Response( array( 'message' => 'User not found' ), 404 );
		}

		$query = array(
			// 'meta_key'    => 'business_number',
			// 'meta_value'  => $business_number,
			'customer_id' => $user->ID,
		);
	}

	$orders = wc_get_orders(
		$query
	);

	foreach ( $orders as $order ) {
		$order_id       = $order->get_id();
		$status_changes = get_post_meta( $order_id, '_status_history', true );
		$prev_status    = '';

		$date_created  = $order->get_date_created();
		$date_modified = $order->get_date_modified();

		if ( ! empty( $status_changes ) && is_array( $status_changes ) ) {
			foreach ( $status_changes as $change_date => $change ) {
				$response[]  = array(
					'order_id'     => $order_id,
					'status'       => 'sent',
					'message_type' => 'customer_order_update_notifier',
					'sender_id'    => 0,
					'message'      => '#' . $order_id . ' order status updates to completed',
					'change_date'  => $change_date * 1000,
					'created_at'   => $change_date * 1000,
					'data'         => array(
						'id'              => $order_id,
						'customer_email'  => null,
						'date_created'    => $date_created->date( 'F j, Y, g:i:s A T' ),
						'date_modified'   => $date_modified->date( 'F j, Y, g:i:s A T' ),
						'prev_status'     => 'processing',
						'status'          => $order->get_status(),
						'business_number' => $business_number,
						'customer_name'   => '',
						'next_page'       => 'my_messages',
					),
				);
				$prev_status = $order->get_status();
			}
		}
	}

	usort(
		$response,
		function( $a, $b ) {
			return $a['created_at'] - $b['created_at'];
		}
	);

	return new WP_REST_Response( $response, 200 );
}
