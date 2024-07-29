<?php
/**
 * Inventory CRUD API for Woomorrintegration plugin.
 *
 * @package WOOMORRINTEGRATION
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Register REST API endpoints for inventory voucher and voucher detail.
 */
function woomorrintegration_inventory_api_init() {

	// Register inventory voucher endpoints.
	register_rest_route(
		'woomorrintegration/v1',
		'/inventoryvoucher(?:/(?P<id>\d+))?',
		array(
			'methods'             => 'GET, POST, PUT, DELETE',
			'callback'            => 'woomorrintegration_inventory_voucher_handler',
			'permission_callback' => 'woomorrintegration_inv_permission_check',
		)
	);

	// Register inventory voucher detail endpoints.
	register_rest_route(
		'woomorrintegration/v1',
		'/inventoryvoucherdetail(?:/(?P<id>\d+))?',
		array(
			'methods'             => array( 'GET', 'POST', 'PUT', 'DELETE' ),
			'callback'            => 'woomorrintegration_inventory_voucher_detail_handler',
			'permission_callback' => 'woomorrintegration_inv_permission_check',
		)
	);

}
add_action( 'rest_api_init', 'woomorrintegration_inventory_api_init' );

/**
 * Permission check for API requests.
 *
 * @param WP_REST_Request $request The REST API request.
 * @return bool True if the request is authorized, false otherwise.
 */
function woomorrintegration_inv_permission_check( WP_REST_Request $request ) {
	$api_key      = get_option( 'woomorrintegration_api_secret_key' );
	$provided_key = $request->get_header( 'auth' );
	return $provided_key === $api_key;
}

/**
 * Main handler for inventory voucher API requests.
 *
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_inventory_voucher_handler( WP_REST_Request $request ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'inventory_voucher';
	switch ( $request->get_method() ) {
		case 'POST':
			return woomorrintegration_create_inventory_voucher( $wpdb, $table_name, $request );
		case 'GET':
			return woomorrintegration_get_inventory_vouchers( $wpdb, $table_name, $request );
		case 'PUT':
			return woomorrintegration_update_inventory_voucher( $wpdb, $table_name, $request );
		case 'DELETE':
			return woomorrintegration_delete_inventory_voucher( $wpdb, $table_name, $request );
		default:
			return new WP_REST_Response( array( 'message' => 'Invalid request method' ), 405 );
	}
}

/**
 * Sanitize and retrieve data from the request.
 *
 * @param WP_REST_Request $request The REST API request.
 * @return array The sanitized data.
 */
function woomorrintegration_inventory_get_sanitized_data( WP_REST_Request $request ) {
	return array(
		'datetime'              => sanitize_text_field( $request->get_param( 'datetime' ) ),
		'voucher_group'         => sanitize_text_field( $request->get_param( 'voucher_group' ) ),
		'voucher_category'      => sanitize_text_field( $request->get_param( 'voucher_category' ) ),
		'voucher_narration'     => sanitize_textarea_field( $request->get_param( 'voucher_narration' ) ),
		'voucher_number'        => sanitize_text_field( $request->get_param( 'voucher_number' ) ),
		'ref_voucher_no'        => sanitize_text_field( $request->get_param( 'ref_voucher_no' ) ),
		'for_voucher_no'        => sanitize_text_field( $request->get_param( 'for_voucher_no' ) ),
		'voucher_status'        => sanitize_text_field( $request->get_param( 'voucher_status' ) ),
		'ref_order_no'          => sanitize_text_field( $request->get_param( 'ref_order_no' ) ),
		'for_order_no'          => sanitize_text_field( $request->get_param( 'for_order_no' ) ),
		'order_date'            => sanitize_text_field( $request->get_param( 'order_date' ) ),
		'purchase_order_no'     => sanitize_text_field( $request->get_param( 'purchase_order_no' ) ),
		'ref_purchase_order_no' => sanitize_text_field( $request->get_param( 'ref_purchase_order_no' ) ),
		'purchase_order_date'   => sanitize_text_field( $request->get_param( 'purchase_order_date' ) ),
		'sales_channel_type'    => sanitize_text_field( $request->get_param( 'sales_channel_type' ) ),
		'returnable_status'     => sanitize_text_field( $request->get_param( 'returnable_status' ) ),
		'from_location'         => sanitize_text_field( $request->get_param( 'from_location' ) ),
		'to_location'           => sanitize_text_field( $request->get_param( 'to_location' ) ),
		'from_storage_area'     => sanitize_text_field( $request->get_param( 'from_storage_area' ) ),
		'to_storage_area'       => sanitize_text_field( $request->get_param( 'to_storage_area' ) ),
		'from_datetime'         => sanitize_text_field( $request->get_param( 'from_datetime' ) ),
		'to_datetime'           => sanitize_text_field( $request->get_param( 'to_datetime' ) ),
		'from_business_number'  => sanitize_text_field( $request->get_param( 'from_business_number' ) ),
		'from_business_name'    => sanitize_text_field( $request->get_param( 'from_business_name' ) ),
		'to_business_number'    => sanitize_text_field( $request->get_param( 'to_business_number' ) ),
		'to_business_name'      => sanitize_text_field( $request->get_param( 'to_business_name' ) ),
		'for_business_number'   => sanitize_text_field( $request->get_param( 'for_business_number' ) ),
		'for_business_name'     => sanitize_text_field( $request->get_param( 'for_business_name' ) ),
		'business_number'       => sanitize_text_field( $request->get_param( 'business_number' ) ),
		'business_name'         => sanitize_text_field( $request->get_param( 'business_name' ) ),
		'from_user_id'          => intval( $request->get_param( 'from_user_id' ) ),
		'from_user_name'        => sanitize_text_field( $request->get_param( 'from_user_name' ) ),
		'to_user_id'            => intval( $request->get_param( 'to_user_id' ) ),
		'to_user_name'          => sanitize_text_field( $request->get_param( 'to_user_name' ) ),
		'meta_fields'           => sanitize_textarea_field( $request->get_param( 'meta_fields' ) ),
		'remarks'               => sanitize_textarea_field( $request->get_param( 'remarks' ) ),
		'store_meta'            => sanitize_textarea_field( $request->get_param( 'store_meta' ) ),
		'workflow_meta'         => sanitize_textarea_field( $request->get_param( 'workflow_meta' ) ),
		'share_url'             => esc_url_raw( $request->get_param( 'share_url' ) ),
		'share_status'          => sanitize_text_field( $request->get_param( 'share_status' ) ),
		'created_date'          => sanitize_text_field( $request->get_param( 'created_date' ) ),
		'created_user_id'       => intval( $request->get_param( 'created_user_id' ) ),
	);
}

/**
 * Retrieve an inventory voucher by ID.
 *
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_get_inventory_voucher_by_id( WP_REST_Request $request ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'inventory_voucher';
	$voucher_id = isset( $request['id'] ) ? intval( $request['id'] ) : 0;

	if ( empty( $voucher_id ) ) {
		return new WP_REST_Response( array( 'message' => 'Voucher ID is required' ), 400 );
	}

	$voucher = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM $table_name WHERE inventory_voucher_id = %d",
			$voucher_id
		),
		ARRAY_A
	);

	if ( null === $voucher ) {
		return new WP_REST_Response(
			array( 'message' => 'Voucher not found' ),
			404
		);
	}

	return new WP_REST_Response(
		$voucher,
		200
	);
}

/**
 * Create a new inventory voucher.
 *
 * @param wpdb            $wpdb The WordPress database object.
 * @param string          $table_name The table name.
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_create_inventory_voucher( $wpdb, $table_name, $request ) {
	$data       = woomorrintegration_inventory_get_sanitized_data( $request );
	$inserted   = $wpdb->insert( $table_name, $data );
	$voucher_id = $wpdb->insert_id;

	if ( false === $inserted ) {
		error_log( 'Failed to insert voucher: ' . $wpdb->last_error );
		return new WP_REST_Response(
			array(
				'message' => 'Failed to create voucher.',
				'error'   => $wpdb->last_error,
			),
			500
		);
	}

	$voucher = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM $table_name WHERE inventory_voucher_id = %d",
			$voucher_id
		),
		ARRAY_A
	);

	return new WP_REST_Response(
		array(
			'message'      => 'Voucher created',
			'voucher_id'   => $voucher_id,
			'voucher_data' => $voucher,
		),
		201
	);
}

/**
 * Retrieve inventory vouchers with optional filters.
 *
 * @param wpdb            $wpdb The WordPress database object.
 * @param string          $table_name The table name.
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_get_inventory_vouchers( $wpdb, $table_name, $request ) {
	$id = isset( $request['id'] ) ? intval( $request['id'] ) : 0;
	if ( ! empty( $id ) ) {
		return woomorrintegration_get_inventory_voucher_by_id( $request );
	}

	$filters = array(
		'from_business_number' => sanitize_text_field( $request->get_param( 'from_business_number' ) ),
		'from_business_name'   => sanitize_text_field( $request->get_param( 'from_business_name' ) ),
		'to_business_number'   => sanitize_text_field( $request->get_param( 'to_business_number' ) ),
		'to_business_name'     => sanitize_text_field( $request->get_param( 'to_business_name' ) ),
	);

	$query    = "SELECT * FROM $table_name WHERE 1=1";
	$bindings = array();

	foreach ( $filters as $key => $value ) {
		if ( ! empty( $value ) ) {
			$query     .= $wpdb->prepare( " AND $key = %s", $value );
			$bindings[] = $value;
		}
	}

	$results = $wpdb->get_results( $wpdb->prepare( $query, $bindings ), ARRAY_A );

	if ( null === $results ) {
		error_log( 'Failed to retrieve vouchers: ' . $wpdb->last_error );
		return new WP_REST_Response(
			array(
				'message' => 'Error retrieving vouchers',
				'error'   => $wpdb->last_error,
			),
			500
		);
	}

	return new WP_REST_Response(
		$results,
		200
	);
}

/**
 * Update an existing inventory voucher.
 *
 * @param wpdb            $wpdb The WordPress database object.
 * @param string          $table_name The table name.
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_update_inventory_voucher( $wpdb, $table_name, $request ) {
	$voucher_id = isset( $request['id'] ) ? intval( $request['id'] ) : 0;

	if ( empty( $voucher_id ) ) {
		return new WP_REST_Response( array( 'message' => 'Voucher ID is required' ), 400 );
	}

	$data    = woomorrintegration_inventory_get_sanitized_data( $request );
	$updated = $wpdb->update( $table_name, $data, array( 'inventory_voucher_id' => $voucher_id ) );

	if ( false === $updated ) {
		error_log( 'Failed to update voucher: ' . $wpdb->last_error );
		return new WP_REST_Response(
			array(
				'message' => 'Failed to update voucher',
				'error'   => $wpdb->last_error,
			),
			500
		);
	}

	$voucher = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM $table_name WHERE inventory_voucher_id = %d",
			$voucher_id
		),
		ARRAY_A
	);

	return new WP_REST_Response(
		$voucher,
		200
	);
}

/**
 * Delete an inventory voucher.
 *
 * @param wpdb            $wpdb The WordPress database object.
 * @param string          $table_name The table name.
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_delete_inventory_voucher( $wpdb, $table_name, $request ) {
	$voucher_id = isset( $request['id'] ) ? intval( $request['id'] ) : 0;

	if ( empty( $voucher_id ) ) {
		return new WP_REST_Response( array( 'message' => 'Voucher ID is required' ), 400 );
	}

	$deleted = $wpdb->delete( $table_name, array( 'inventory_voucher_id' => $voucher_id ) );

	if ( false === $deleted ) {
		error_log( 'Failed to delete voucher: ' . $wpdb->last_error );
		return new WP_REST_Response(
			array(
				'message' => 'Failed to delete voucher',
				'error'   => $wpdb->last_error,
			),
			500
		);
	}

	return new WP_REST_Response(
		array(
			'message'    => 'Voucher deleted',
			'voucher_id' => $voucher_id,
		),
		200
	);
}


// inventoryvoucherdetail.

/**
 * Main handler for inventory voucher detail API requests.
 *
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_inventory_voucher_detail_handler( WP_REST_Request $request ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'inventory_voucher_detail';

	switch ( $request->get_method() ) {
		case 'POST':
			return woomorrintegration_create_inventory_voucher_detail( $wpdb, $table_name, $request );
		case 'GET':
			return woomorrintegration_get_inventory_voucher_details( $wpdb, $table_name, $request );
		case 'PUT':
			return woomorrintegration_update_inventory_voucher_detail( $wpdb, $table_name, $request );
		case 'DELETE':
			return woomorrintegration_delete_inventory_voucher_detail( $wpdb, $table_name, $request );
		default:
			return new WP_REST_Response( array( 'message' => 'Invalid request method' ), 405 );
	}
}

/**
 * Sanitize and retrieve data from the request for inventory voucher detail.
 *
 * @param WP_REST_Request $request The REST API request.
 * @return array The sanitized data.
 */
function woomorrintegration_inventory_voucher_detail_get_sanitized_data( WP_REST_Request $request ) {
	return array(
		'inventory_voucher_id' => intval( $request->get_param( 'inventory_voucher_id' ) ),
		'detail_serial'        => sanitize_text_field( $request->get_param( 'detail_serial' ) ),
		'product_number'       => sanitize_text_field( $request->get_param( 'product_number' ) ),
		'product_name'         => sanitize_text_field( $request->get_param( 'product_name' ) ),
		'supplier_code'        => sanitize_text_field( $request->get_param( 'supplier_code' ) ),
		'serial_no'            => sanitize_text_field( $request->get_param( 'serial_no' ) ),
		'description'          => sanitize_textarea_field( $request->get_param( 'description' ) ),
		'short_description'    => sanitize_textarea_field( $request->get_param( 'short_description' ) ),
		'product_image'        => esc_url_raw( $request->get_param( 'product_image' ) ),
		'sku'                  => sanitize_text_field( $request->get_param( 'sku' ) ),
		'qty'                  => floatval( $request->get_param( 'qty' ) ),
		'uom'                  => sanitize_text_field( $request->get_param( 'uom' ) ),
		'in_qty'               => floatval( $request->get_param( 'in_qty' ) ),
		'out_qty'              => floatval( $request->get_param( 'out_qty' ) ),
		'difference_qty'       => floatval( $request->get_param( 'difference_qty' ) ),
		'price'                => floatval( $request->get_param( 'price' ) ),
		'amount'               => floatval( $request->get_param( 'amount' ) ),
		'tax_status'           => sanitize_text_field( $request->get_param( 'tax_status' ) ),
		'tax_class'            => sanitize_text_field( $request->get_param( 'tax_class' ) ),
		'tax_name'             => sanitize_text_field( $request->get_param( 'tax_name' ) ),
		'tax_rate'             => floatval( $request->get_param( 'tax_rate' ) ),
		'tax_amount'           => floatval( $request->get_param( 'tax_amount' ) ),
		'shipping_class'       => sanitize_text_field( $request->get_param( 'shipping_class' ) ),
		'shipper_name'         => sanitize_text_field( $request->get_param( 'shipper_name' ) ),
		'shipment_name'        => sanitize_text_field( $request->get_param( 'shipment_name' ) ),
		'shipment_rate'        => floatval( $request->get_param( 'shipment_rate' ) ),
		'shipment_amount'      => floatval( $request->get_param( 'shipment_amount' ) ),
		'discount_name'        => sanitize_text_field( $request->get_param( 'discount_name' ) ),
		'discount_rate'        => floatval( $request->get_param( 'discount_rate' ) ),
		'discount_amount'      => floatval( $request->get_param( 'discount_amount' ) ),
		'sale_price'           => floatval( $request->get_param( 'sale_price' ) ),
		'regular_price'        => floatval( $request->get_param( 'regular_price' ) ),
		'cogm_price'           => floatval( $request->get_param( 'cogm_price' ) ),
		'cogs_price'           => floatval( $request->get_param( 'cogs_price' ) ),
		'fifo_price'           => floatval( $request->get_param( 'fifo_price' ) ),
		'lifo_price'           => floatval( $request->get_param( 'lifo_price' ) ),
		'landing_cost'         => floatval( $request->get_param( 'landing_cost' ) ),
		'average_price'        => floatval( $request->get_param( 'average_price' ) ),
		'purchase_note'        => sanitize_textarea_field( $request->get_param( 'purchase_note' ) ),
		'customer_note'        => sanitize_textarea_field( $request->get_param( 'customer_note' ) ),
		'meta_fields'          => sanitize_textarea_field( $request->get_param( 'meta_fields' ) ),
		'weight'               => floatval( $request->get_param( 'weight' ) ),
		'length'               => floatval( $request->get_param( 'length' ) ),
		'width'                => floatval( $request->get_param( 'width' ) ),
		'height'               => floatval( $request->get_param( 'height' ) ),
		'images'               => sanitize_textarea_field( $request->get_param( 'images' ) ),
		'mfg_batch_number'     => sanitize_text_field( $request->get_param( 'mfg_batch_number' ) ),
		'mfg_serial_number'    => sanitize_text_field( $request->get_param( 'mfg_serial_number' ) ),
		'date_of_mfg'          => sanitize_text_field( $request->get_param( 'date_of_mfg' ) ),
		'date_of_expiry'       => sanitize_text_field( $request->get_param( 'date_of_expiry' ) ),
		'mfg_name'             => sanitize_text_field( $request->get_param( 'mfg_name' ) ),
		'bar_code'             => sanitize_text_field( $request->get_param( 'bar_code' ) ),
		'rfid_tag'             => sanitize_text_field( $request->get_param( 'rfid_tag' ) ),
		'remarks'              => sanitize_textarea_field( $request->get_param( 'remarks' ) ),
		'ledger_code'          => sanitize_text_field( $request->get_param( 'ledger_code' ) ),
		'ledger_name'          => sanitize_text_field( $request->get_param( 'ledger_name' ) ),
		'store_meta'           => sanitize_textarea_field( $request->get_param( 'store_meta' ) ),
		'workflow_meta'        => sanitize_textarea_field( $request->get_param( 'workflow_meta' ) ),
		'created_date'         => sanitize_text_field( $request->get_param( 'created_date' ) ),
		'created_user_id'      => intval( $request->get_param( 'created_user_id' ) ),
	);
}

/**
 * Create a new inventory voucher detail.
 *
 * @param wpdb            $wpdb The WordPress database object.
 * @param string          $table_name The table name.
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_create_inventory_voucher_detail( $wpdb, $table_name, WP_REST_Request $request ) {
	$data = woomorrintegration_inventory_voucher_detail_get_sanitized_data( $request );

	$wpdb->insert( $table_name, $data );
	$new_detail_id = $wpdb->insert_id;

	if ( $new_detail_id ) {
		return new WP_REST_Response(
			array(
				'message'           => 'Voucher detail created successfully',
				'voucher_detail_id' => $new_detail_id,
			),
			201
		);
	} else {
		return new WP_REST_Response(
			array(
				'message' => 'Failed to create voucher detail',
				'error'   => $wpdb->last_error,
			),
			500
		);
	}
}

/**
 * Retrieve all inventory voucher details.
 *
 * @param wpdb            $wpdb The WordPress database object.
 * @param string          $table_name The table name.
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_get_inventory_voucher_details( $wpdb, $table_name, WP_REST_Request $request ) {
	$voucher_id = isset( $request['id'] ) ? intval( $request['id'] ) : 0;

	if ( ! empty( $voucher_id ) ) {
		$details = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE inventory_voucher_id = %d",
				intval( $voucher_id )
			),
			ARRAY_A
		);
	} else {
		$details = $wpdb->get_results(
			"SELECT * FROM $table_name",
			ARRAY_A
		);
	}

	return new WP_REST_Response(
		$details,
		200
	);
}

/**
 * Update an existing inventory voucher detail.
 *
 * @param wpdb            $wpdb The WordPress database object.
 * @param string          $table_name The table name.
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_update_inventory_voucher_detail( $wpdb, $table_name, WP_REST_Request $request ) {
	$detail_id = isset( $request['id'] ) ? intval( $request['id'] ) : 0;
	$data      = woomorrintegration_inventory_voucher_detail_get_sanitized_data( $request );

	$updated = $wpdb->update(
		$table_name,
		$data,
		array( 'inventory_voucher_detail_id' => $detail_id )
	);

	if ( false !== $updated ) {
		return new WP_REST_Response(
			array( 'message' => 'Voucher detail updated successfully' ),
			200
		);
	} else {
		return new WP_REST_Response(
			array(
				'message' => 'Failed to update voucher detail',
				'error'   => $wpdb->last_error,
			),
			500
		);
	}
}

/**
 * Delete an inventory voucher detail.
 *
 * @param wpdb            $wpdb The WordPress database object.
 * @param string          $table_name The table name.
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_delete_inventory_voucher_detail( $wpdb, $table_name, WP_REST_Request $request ) {
	$detail_id = isset( $request['id'] ) ? intval( $request['id'] ) : 0;

	if ( empty( $detail_id ) ) {
		return new WP_REST_Response( array( 'message' => 'Voucher Detail ID is required' ), 400 );
	}

	$deleted = $wpdb->delete(
		$table_name,
		array( 'inventory_voucher_detail_id' => $detail_id )
	);

	if ( $deleted ) {
		return new WP_REST_Response(
			array( 'message' => 'Voucher detail deleted successfully' ),
			200
		);
	} else {
		return new WP_REST_Response(
			array(
				'message' => 'Failed to delete voucher detail',
				'error'   => $wpdb->last_error,
			),
			500
		);
	}
}
