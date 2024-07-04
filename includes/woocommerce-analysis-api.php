<?php
/**
 * Woomorrintegration woo analysis api.
 *
 * @package WOOMORRINTEGRATION
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

function get_customer_order_data( $customer_id, $date_start, $date_end ) {
	global $wpdb;
	$order_table = $wpdb->prefix . 'posts';
	$meta_table  = $wpdb->prefix . 'postmeta';

	// SQL query to get aggregated order data grouped by day
	$results = $wpdb->get_results($wpdb->prepare("
        SELECT 
            DATE(p.post_date) as order_date,
            SUM(CASE WHEN oim.meta_key = '_line_total' THEN oim.meta_value ELSE 0 END) as total_sales,
            SUM(CASE WHEN oim.meta_key = '_line_tax' THEN oim.meta_value ELSE 0 END) as taxes,
            SUM(CASE WHEN oim.meta_key = '_line_total' THEN oim.meta_value ELSE 0 END) - 
            SUM(CASE WHEN oim.meta_key = '_line_tax' THEN oim.meta_value ELSE 0 END) as net_revenue,
            SUM(CASE WHEN oim.meta_key = '_qty' THEN oim.meta_value ELSE 0 END) as num_items_sold,
            COUNT(DISTINCT p.ID) as orders_count,
            ABS(SUM(CASE WHEN p.post_type = 'shop_order_refund' THEN oim.meta_value ELSE 0 END)) as refunds,
            SUM(CASE WHEN oim.meta_key = '_cart_discount' THEN oim.meta_value ELSE 0 END) as coupons
        FROM $order_table p
        LEFT JOIN $meta_table pm ON p.ID = pm.post_id
        LEFT JOIN $order_items_table oi ON p.ID = oi.order_id
        LEFT JOIN $order_itemmeta_table oim ON oi.order_item_id = oim.order_item_id
        WHERE p.post_type IN ('shop_order', 'shop_order_refund')
        AND p.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold', 'wc-refunded')
        AND pm.meta_key = '_customer_user'
        AND pm.meta_value = %d
        GROUP BY order_date
    ", $customer_id), ARRAY_A);

	var_dump($results);
	// Initialize totals
	$totals = array(
		'gross_sales'    => 0,
		'refunds'        => 0,
		'coupons'        => 0,
		'net_revenue'    => 0,
		'taxes'          => 0,
		'shipping'       => 0,  // Assuming shipping is 0 for simplicity
		'total_sales'    => 0,
		'num_items_sold' => 0,
		'orders_count'   => 0,
		'products'       => 0,  // Static value, modify if necessary
		'coupons_count'  => 0,
		'segments'       => array(),
	);

	// Initialize intervals
	$intervals = array();

	// Process each day's results
	foreach ( $results as $result ) {
		$intervals[] = array(
			'interval'       => $result['order_date'],
			'date_start'     => $result['order_date'] . ' 00:00:00',
			'date_start_gmt' => $result['order_date'] . ' 00:00:00',
			'date_end'       => $result['order_date'] . ' 23:59:59',
			'date_end_gmt'   => $result['order_date'] . ' 23:59:59',
			'subtotals'      => array(
				'gross_sales' => floatval( $result['total_sales'] ),
				'refunds'     => floatval( $result['refunds'] ),
				'coupons'     => floatval( $result['coupons'] ),
				'net_revenue' => floatval( $result['net_revenue'] ),
				'taxes'       => floatval( $result['taxes'] ),
				'shipping'    => 0,  // Assuming shipping is 0 for simplicity
			),
		);

		// Aggregate totals
		$totals['gross_sales']    += floatval( $result['total_sales'] );
		$totals['refunds']        += floatval( $result['refunds'] );
		$totals['coupons']        += floatval( $result['coupons'] );
		$totals['net_revenue']    += floatval( $result['net_revenue'] );
		$totals['taxes']          += floatval( $result['taxes'] );
		$totals['total_sales']    += floatval( $result['total_sales'] );
		$totals['num_items_sold'] += intval( $result['num_items_sold'] );
		$totals['orders_count']   += intval( $result['orders_count'] );
	}

	// Calculate averages after totals are aggregated
	$totals['avg_order_value']     = $totals['orders_count'] > 0 ? $totals['gross_sales'] / $totals['orders_count'] : 0;
	$totals['avg_items_per_order'] = $totals['orders_count'] > 0 ? $totals['num_items_sold'] / $totals['orders_count'] : 0;

	// Prepare the final response
	$data = array(
		'totals'    => $totals,
		'intervals' => $intervals,
	);

	return $data;
}

/**
 * Filter hook for modifying revenue query arguments in WooCommerce analytics.
 *
 * This function filters the revenue query arguments used in WooCommerce analytics
 * to include only products associated with a specific business number.
 *
 * @param array $query_vars The revenue query arguments.
 * @return array Modified revenue query arguments.
 */
function woocommerce_analytics_revenue_query_args_filter( $query_vars ) {

	if ( isset( $_GET['business_number'] ) && ! empty( $_GET['business_number'] ) ) {
		// @ini_set( 'display_errors', 1 );
		$business_number = sanitize_text_field( $_GET['business_number'] );

		$ids = get_posts(
			array(
				'posts_per_page' => -1,
				'post_type'      => array( 'product' ),
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'   => 'business_number',
						'value' => $business_number,
					),
				),
			)
		);

		$query_vars['product_includes'] = $ids;
	}

	// $res = get_customer_order_data(38,"2024-04-01","2024-06-05");
	// var_dump($res);
	// wp_die("dd");
	return $query_vars;
}
add_filter( 'woocommerce_analytics_revenue_query_args', 'woocommerce_analytics_revenue_query_args_filter' );

/**
 * Filter users by business number for reports related to sales.
 *
 * This function filters users by their business number for reports related to sales, such as
 * the sales report endpoint and order report data arguments.
 *
 * @param WP_Query $query The WP_Query object.
 */
function filter_users_by_business_number_reports_sales( $query ) {
	$allowed_endpoints = array(
		'wc/v3/reports/sales',
		'wc/v3/reports/sales/',
	);
	if ( is_admin() || ! is_wc_reports_rest_endpoint( $allowed_endpoints ) ) {
		return;
	}

	// Check if we are on ... and the 'business_number' parameter is set.
	if ( isset( $_GET['business_number'] ) && ! empty( $_GET['business_number'] ) ) {
		$business_number = sanitize_text_field( $_GET['business_number'] );

		$query->query_vars['meta_query'] = array(
			array(
				'key'   => 'for_business_number',
				'value' => $business_number,
			),
		);
	}

	return $query;
}
add_filter( 'pre_get_users', 'filter_users_by_business_number_reports_sales' );

/**
 * Filter WooCommerce reports to get order report data.
 *
 * This function filters WooCommerce reports to get order report data by business number.
 *
 * @param array $args The arguments for the order report data.
 * @return array Modified arguments for order report data.
 */
function filter_woocommerce_reports_get_order_report( $args ) {
	if ( is_admin() ) {
		return;
	}
	if ( ! is_this_rest_endpoint( 'wc/v3/reports/sales' ) && ! is_this_rest_endpoint( 'wc/v3/reports/sales/' ) ) {
		return;
	}

	if ( isset( $_GET['business_number'] ) && ! empty( $_GET['business_number'] ) ) {
		$business_number      = sanitize_text_field( $_GET['business_number'] );
		$args['where_meta'][] = array(
			'meta_key'   => 'business_number',
			'operator'   => '=',
			'meta_value' => $business_number,
		);
	}
	return $args;
}
add_filter( 'woocommerce_reports_get_order_report_data_args', 'filter_woocommerce_reports_get_order_report' );

/**
 * Filter users by business number for reports related to customers' totals.
 *
 * This function filters users by their business number for reports related to customers' totals,
 * such as the customers' totals report endpoint.
 *
 * @param WP_REST_Response $response The REST response object.
 * @param array            $handler  The handler array.
 * @param WP_REST_Request  $request  The REST request object.
 * @return WP_REST_Response Modified REST response object.
 */
function filter_users_by_business_number_reports_customers_totals( $response, array $handler, \WP_REST_Request $request ) {

	if ( ! is_request_method_endpoint( $request, '/wc/v3/reports/customers/totals', 'GET' ) ) {
		return $response;
	}

	$request_params = $request->get_params();

	// Check if we are on ... and the 'business_number' parameter is set
	if ( isset( $request_params['business_number'] ) && ! empty( $request_params['business_number'] ) ) {
		$business_number = sanitize_text_field( $_GET['business_number'] );

		$users_count = new WP_User_Query(
			array(
				'role__not_in' => array( 'administrator', 'shop_manager' ),
				'number'       => 0,
				'fields'       => 'ID',
				'count_total'  => true,
				'meta_query'   => array(
					array(
						'key'   => 'for_business_number',
						'value' => $business_number,
					),
				),
			)
		);

		$total_customers = (int) $users_count->get_total();

		$customers_query = new WP_User_Query(
			array(
				'role__not_in' => array( 'administrator', 'shop_manager' ),
				'number'       => 0,
				'fields'       => 'ID',
				'count_total'  => true,
				'meta_query'   => array(
					array(
						'key'     => 'paying_customer',
						'value'   => 1,
						'compare' => '=',
					),
					array(
						'key'   => 'for_business_number',
						'value' => $business_number,
					),
				),
			)
		);

		$total_paying = (int) $customers_query->get_total();

		$response->data = array(
			array(
				'slug'  => 'paying',
				'name'  => __( 'Paying customer', 'woocommerce' ),
				'total' => $total_paying,
			),
			array(
				'slug'  => 'non_paying',
				'name'  => __( 'Non-paying customer', 'woocommerce' ),
				'total' => $total_customers - $total_paying,
			),
		);
	}

	return $response;
}
add_filter( 'rest_request_after_callbacks', 'filter_users_by_business_number_reports_customers_totals', 10, 3 );

/**
 * Filter users by business number for reports related to orders' totals.
 *
 * This function filters users by their business number for reports related to orders' totals,
 * such as the orders' totals report endpoint.
 *
 * @param WP_REST_Response $response The REST response object.
 * @param array            $handler  The handler array.
 * @param WP_REST_Request  $request  The REST request object.
 * @return WP_REST_Response Modified REST response object.
 */
function filter_users_by_business_number_reports_orders_totals( $response, array $handler, $request ) {

	if ( ! is_request_method_endpoint( $request, '/wc/v3/reports/orders/totals', 'GET' ) ) {
		return $response;
	}

	$request_params = $request->get_params();

	// Check if we are on ... and the 'business_number' parameter is set.
	if ( isset( $request_params['business_number'] ) && ! empty( $request_params['business_number'] ) ) {
		global $wpdb;

		$business_number = sanitize_text_field( $_GET['business_number'] );

		$query = $wpdb->prepare(
			"SELECT COUNT(p.ID) as count, p.post_status
				FROM {$wpdb->posts} p
				LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				WHERE p.post_type = %s
				AND pm.meta_key = 'business_number'
				AND pm.meta_value = %s
				GROUP BY p.post_status",
			'shop_order',
			'456789'
		);

		$results = $wpdb->get_results( $query, ARRAY_A );

		$totals = array_fill_keys( get_post_stati(), 0 );

		foreach ( $results as $row ) {
			$totals[ $row['post_status'] ] = $row['count'];
		}

		$data = array();

		foreach ( wc_get_order_statuses() as $slug => $name ) {
			if ( ! isset( $totals[ $slug ] ) ) {
				continue;
			}

			$data[] = array(
				'slug'  => str_replace( 'wc-', '', $slug ),
				'name'  => $name,
				'total' => (int) $totals[ $slug ],
			);
		}

		$response->data = $data;
	}

	return $response;
}
add_filter( 'rest_request_after_callbacks', 'filter_users_by_business_number_reports_orders_totals', 10, 3 );

/**
 * Filter users by business number for reports related to products' totals.
 *
 * This function filters users by their business number for reports related to products' totals,
 * such as the products' totals report endpoint.
 *
 * @param WP_REST_Response $response The REST response object.
 * @param array            $handler  The handler array.
 * @param WP_REST_Request  $request  The REST request object.
 * @return WP_REST_Response Modified REST response object.
 */
function filter_users_by_business_number_reports_products_totals( $response, array $handler, $request ) {

	if ( ! is_request_method_endpoint( $request, '/wc/v3/reports/products/totals', 'GET' ) ) {
		return $response;
	}

	$request_params = $request->get_params();

	// Check if we are on ... and the 'business_number' parameter is set.
	if ( isset( $request_params['business_number'] ) && ! empty( $request_params['business_number'] ) ) {
		global $wpdb;

		$business_number = sanitize_text_field( $_GET['business_number'] );

		$product_types = wc_get_product_types();

		$totals = array();

		foreach ( $product_types as $slug => $name ) {
			$term = get_term_by( 'slug', $slug, 'product_type' );

			if ( $term ) {
				$query_args = array(
					'post_type'   => 'product',
					'post_status' => 'publish',
					'tax_query'   => array(
						array(
							'taxonomy' => 'product_type',
							'field'    => 'term_id',
							'terms'    => $term->term_id,
						),
					),
					'meta_query'  => array(
						array(
							'key'   => 'business_number',
							'value' => $business_number,
						),
					),
				);

				$product_count = new WP_Query( $query_args );

				$totals[] = array(
					'slug'  => $slug,
					'name'  => $name,
					'total' => $product_count->found_posts,
				);
			}
		}

		$response->data = $totals;
	}

	return $response;
}
add_filter( 'rest_request_after_callbacks', 'filter_users_by_business_number_reports_products_totals', 10, 3 );

/**
 * Check if the current endpoint is one of the specified WooCommerce reports REST endpoints.
 *
 * @param array $endpoints The array of allowed endpoints.
 * @return bool Whether the current endpoint is one of the specified endpoints.
 */
function is_wc_reports_rest_endpoint( $endpoints ) {
	foreach ( $endpoints as $endpoint ) {
		if ( is_this_rest_endpoint( $endpoint ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Check if the current endpoint matches the specified endpoint.
 *
 * @param string $endpoint The specified endpoint.
 * @return bool Whether the current endpoint matches the specified endpoint.
 */
function is_this_rest_endpoint( $endpoint = '' ) {
	$rest_url    = wp_parse_url( trailingslashit( rest_url() ) );
	$current_url = wp_parse_url( add_query_arg( array() ) );

	return str_replace( $rest_url['path'], '', $current_url['path'] ) === $endpoint;
}

/**
 * Check if the request method and endpoint match the specified method and endpoint.
 *
 * @param \WP_REST_Request $request  The REST request object.
 * @param string           $endpoint The specified endpoint.
 * @param string           $method   The specified method.
 * @return bool Whether the request method and endpoint match the specified method and endpoint.
 */
function is_request_method_endpoint( \WP_REST_Request $request, $endpoint = '', $method = 'GET' ) {
	return $endpoint === $request->get_route()
		&& $method === $request->get_method();
}

/**
 * Register WooCommerce analysis endpoint.
 */
function register_woocommerce_analysis_endpoint() {
	register_rest_route(
		'ffintegration/v1',
		'/woocommerce-analysis',
		array(
			'methods'             => 'GET',
			'callback'            => 'get_woocommerce_analysis',
			'permission_callback' => 'check_api_key',
			'args'                => array(
				'time_range' => array(
					'default'           => 'today',
					'validate_callback' => 'validate_time_range',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'register_woocommerce_analysis_endpoint' );

/**
 * Check API key for access to WooCommerce analysis endpoint.
 *
 * @param WP_REST_Request $request The request object.
 * @return bool Whether the API key is valid.
 */
function check_api_key( WP_REST_Request $request ) {
	$api_key      = get_option( 'woomorrintegration_api_secret_key' );
	$provided_key = $request->get_header( 'auth' );
	// $provided_key = isset( $_SERVER['HTTP_X_FFINTEGRATION_API_KEY'] ) ? sanitize_text_field( $_SERVER['HTTP_X_FFINTEGRATION_API_KEY'] ) : '';
	return $provided_key === $api_key;
}

/**
 * Validate time range parameter for WooCommerce analysis endpoint.
 *
 * @param string          $param  The parameter value.
 * @param WP_REST_Request $request The REST request object.
 * @param string          $key    The parameter key.
 * @return bool Whether the time range parameter is valid.
 */
function validate_time_range( $param, $request, $key ) {
	$allowed_time_ranges = array( 'today', 'this_week', 'this_month' );
	return in_array( $param, $allowed_time_ranges, true );
}

/**
 * Get date range based on the specified range.
 *
 * @param string $range The specified range.
 * @return array Date range array.
 */
function get_date_range( $range ) {
	// Get current date.
	$current_date = date( 'Y-m-d' );

	// Set up date query based on the range.
	switch ( $range ) {
		case 'today':
			$args =
					array(
						'after'  => $current_date . ' 00:00:00',
						'before' => $current_date . ' 23:59:59',

					);
			break;
		case 'yesterday':
			$yesterday = date( 'Y-m-d', strtotime( '-1 day' ) );
			$args      = array(
				'after'  => $yesterday . ' 00:00:00',
				'before' => $yesterday . ' 23:59:59',

			);
			break;
		case 'this_week':
			$args =
					array(
						'after'  => date( 'Y-m-d', strtotime( 'last Monday' ) ) . ' 00:00:00',
						'before' => date( 'Y-m-d', strtotime( 'next Sunday' ) ) . ' 23:59:59',

					);
			break;
		case 'this_month':
			$args = array(
				'after'  => date( 'Y-m-01' ) . ' 00:00:00',
				'before' => date( 'Y-m-t' ) . ' 23:59:59',

			);
			break;
		default:
			$args =
					array(
						'after'  => $current_date . ' 00:00:00',
						'before' => $current_date . ' 23:59:59',
					);
	}

	return $args;
}

/**
 * Get WooCommerce analysis data.
 *
 * @param WP_REST_Request $request The REST request object.
 * @return WP_REST_Response WooCommerce analysis data.
 */
function get_woocommerce_analysis( $request ) {
	$time_range = $request->get_param( 'time_range' );

	$date_range = get_date_range( $time_range );

	$orders = wc_get_orders(
		array(
			'limit'      => -1,
			'date_query' => array( $date_range ),
		)
	);

	// Initialize variables.
	$total_order_amount = 0;
	$customer_ids       = array();
	$order_amount_paid  = 0;
	$order_amounts_due  = 0;
	$orders_completed   = 0;

	// Loop through orders to calculate metrics.
	foreach ( $orders as $order ) {
		// Calculate total order amount.
		$total_order_amount += $order->get_total();

		// Get customer ID.
		$customer_id = $order->get_customer_id();

		// Track unique customer IDs.
		if ( ! in_array( $customer_id, $customer_ids ) ) {
			$customer_ids[] = $customer_id;
		}

		// Calculate order amount paid.
		if ( $order->is_paid() ) {
			$order_amount_paid += $order->get_total();
		}

		// Calculate order amounts due.
		if ( $order->has_status( 'pending' ) ) {
			$order_amounts_due += $order->get_total();
		}

		// Count completed orders.
		if ( $order->has_status( 'completed' ) ) {
			$orders_completed++;
		}
	}

	$total_customers = count( $customer_ids );

	$analysis_data = array(
		'time_range'             => $time_range,
		'total_orders'           => count( $orders ),
		'total_order_amount'     => $total_order_amount,
		'order_amount_paid'      => $order_amount_paid,
		'order_amounts_due'      => $order_amounts_due,
		'orders_completed'       => $orders_completed,
		'customers_placed_order' => $total_customers,
	);
	return new WP_REST_Response( $analysis_data, 200 );
}



/**
 * Leaderboards custom_products_query_args.
 *
 * @param  $query_vars
 *
 * @return
 */
function leaderboards_custom_products_query_args( $query_args ) {
	if ( isset( $_GET['business_number'] ) && ! empty( $_GET['business_number'] ) ) {
		// $meta_query = array(
		// array(
		// 'key'     => 'business_number',
		// 'value'   => $_GET['business_number'],
		// 'compare' => '=',
		// ),
		// );

		// $query_args['meta_query'] = $meta_query;

		$business_number = sanitize_text_field( $_GET['business_number'] );

		$ids = get_posts(
			array(
				'posts_per_page' => -1,
				'post_type'      => array( 'product' ),
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'   => 'business_number',
						'value' => $business_number,
					),
				),
			)
		);

		$query_args['product_includes'] = $ids;
	}
	return $query_args;
}
add_filter( 'woocommerce_analytics_products_query_args', 'leaderboards_custom_products_query_args', 999 );


/**
 * Leaderboards custom_customers_query_args.
 *
 * @param  $query_vars
 *
 * @return
 */
function leaderboards_custom_customers_query_args( $query_args ) {
	if ( isset( $_GET['business_number'] ) && ! empty( $_GET['business_number'] ) ) {

		$business_number = sanitize_text_field( $_GET['business_number'] );

		$meta_query = array(
			array(
				'key'     => 'for_business_number',
				'value'   => $business_number,
				'compare' => '=',
			),
		);

		$users = get_users(
			array(
				'meta_query' => $meta_query,
				'fields'     => 'ID',
			)
		);

		$query_args['users'] = $users;
	}
	return $query_args;
}
add_filter( 'woocommerce_analytics_customers_query_args', 'leaderboards_custom_customers_query_args', 999 );


/**
 * Leaderboards custom_customers_query_args.
 *
 * @param  $query_vars
 *
 * @return
 */
function leaderboards_custom_coupons_query_args( $query_args ) {
	if ( isset( $_GET['business_number'] ) && ! empty( $_GET['business_number'] ) ) {

		$business_number = sanitize_text_field( $_GET['business_number'] );

		$meta_query = array(
			'key'     => 'business_number',
			'value'   => $business_number,
			'compare' => '=',
		);

		$args = array(
			'post_type'      => 'shop_coupon',
			'posts_per_page' => -1,
			'meta_query'     => array( $meta_query ),
		);

		// Get coupon posts
		$coupons = get_posts( $args );

		// Get the coupon IDs
		$coupon_ids = wp_list_pluck( $coupons, 'ID' );

		$query_args['coupons'] = $coupon_ids;
	}
	return $query_args;
}
add_filter( 'woocommerce_analytics_coupons_query_args', 'leaderboards_custom_coupons_query_args', 999 );


/**
 * Leaderboards custom_customers_query_args.
 *
 * @param  $query_vars
 *
 * @return
 */
function leaderboards_custom_categories_query_args( $query_args ) {
	if ( isset( $_GET['business_number'] ) && ! empty( $_GET['business_number'] ) ) {

		$business_number = sanitize_text_field( $_GET['business_number'] );

		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT tm.term_id
			FROM {$wpdb->termmeta} tm
			INNER JOIN {$wpdb->term_taxonomy} tt ON tm.term_id = tt.term_id
			WHERE tm.meta_key = %s
			AND tm.meta_value = %s
			AND tt.taxonomy = %s",
			'business_number',
			$business_number,
			'product_cat'
		);

		$term_ids                        = $wpdb->get_col( $query );
		$query_args['category_includes'] = $term_ids;
	}
	return $query_args;
}
add_filter( 'woocommerce_analytics_categories_query_args', 'leaderboards_custom_categories_query_args', 999 );


function filter_products_by_business_number( $query ) {
	if ( isset( $_GET['business_number'] ) && ! empty( $_GET['business_number'] ) ) {

		$business_number = sanitize_text_field( $_GET['business_number'] );
		$post_types      = $query->get( 'post_type' );

		// Skip the code if $post_types is not an array.
		if ( ! is_array( $post_types ) ) {
			return;
		}

		if ( in_array( 'product', $post_types ) || in_array( 'product_variation', $post_types ) ) {

			$meta_query = array(
				array(
					'key'     => 'business_number',
					'value'   => $business_number,
					'compare' => '=',
				),
			);

			$query->set( 'meta_query', $meta_query );
		}
	}
}

add_action( 'pre_get_posts', 'filter_products_by_business_number' );
