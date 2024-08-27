<?php
/**
 * Campaign and Campaign Offer CRUD API for Woomorrintegration plugin.
 *
 * @package WOOMORRINTEGRATION
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Register REST API endpoints for campaign and campaign offer.
 */
function woomorrintegration_campaign_api_init() {

	register_rest_route(
		'woomorrintegration/v1',
		'/campaign(?:/(?P<id>\d+))?',
		array(
			'methods'             => 'GET, POST, PUT, DELETE',
			'callback'            => 'woomorrintegration_campaign_handler',
			'permission_callback' => 'woomorrintegration_campaign_permission_check',
		)
	);

	register_rest_route(
		'woomorrintegration/v1',
		'/campaignoffer(?:/(?P<id>\d+))?',
		array(
			'methods'             => 'GET, POST, PUT, DELETE',
			'callback'            => 'woomorrintegration_campaign_offer_handler',
			'permission_callback' => 'woomorrintegration_campaign_permission_check',
		)
	);

	register_rest_route(
		'woomorrintegration/v1',
		'/campaigntracking(?:/(?P<id>\d+))?',
		array(
			'methods'             => 'GET, POST, PUT, DELETE',
			'callback'            => 'woomorrintegration_campaign_tracking_handler',
			'permission_callback' => 'woomorrintegration_campaign_permission_check',
		)
	);

	register_rest_route(
		'woomorrintegration/v1',
		'/campaignuseroffer(?:/(?P<id>\d+))?',
		array(
			'methods'             => 'GET, POST, PUT, DELETE',
			'callback'            => 'woomorrintegration_campaign_user_offer_handler',
			'permission_callback' => 'woomorrintegration_campaign_permission_check',
		)
	);
}
add_action( 'rest_api_init', 'woomorrintegration_campaign_api_init' );

/**
 * Permission check for API requests.
 *
 * @param WP_REST_Request $request The REST API request.
 * @return bool True if the request is authorized, false otherwise.
 */
function woomorrintegration_campaign_permission_check( WP_REST_Request $request ) {
	$api_key      = get_option( 'woomorrintegration_api_secret_key' );
	$provided_key = $request->get_header( 'auth' );
	return $provided_key === $api_key;
}

/**
 * Main handler for campaign API requests.
 *
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_campaign_handler( WP_REST_Request $request ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'campaigns';
	switch ( $request->get_method() ) {
		case 'POST':
			return woomorrintegration_create_campaign( $wpdb, $table_name, $request );
		case 'GET':
			return woomorrintegration_get_campaigns( $wpdb, $table_name, $request );
		case 'PUT':
			return woomorrintegration_update_campaign( $wpdb, $table_name, $request );
		case 'DELETE':
			return woomorrintegration_delete_campaign( $wpdb, $table_name, $request );
		default:
			return new WP_REST_Response( array( 'message' => 'Invalid request method' ), 405 );
	}
}

/**
 * Sanitize and retrieve data from the request for campaigns.
 *
 * @param WP_REST_Request $request The REST API request.
 * @return array The sanitized data.
 */
function woomorrintegration_campaign_get_sanitized_data( WP_REST_Request $request ) {
	return array(
		'campaign_name'         => sanitize_text_field( $request->get_param( 'campaign_name' ) ),
		'campaign_code'         => sanitize_text_field( $request->get_param( 'campaign_code' ) ),
		'status'                => sanitize_text_field( $request->get_param( 'status' ) ),
		'description'           => sanitize_textarea_field( $request->get_param( 'description' ) ),
		'icon'                  => sanitize_text_field( $request->get_param( 'icon' ) ),
		'campaign_type'         => sanitize_text_field( $request->get_param( 'campaign_type' ) ),
		'campaign_start_date'   => sanitize_text_field( $request->get_param( 'campaign_start_date' ) ),
		'campaign_end_date'     => sanitize_text_field( $request->get_param( 'campaign_end_date' ) ),
		'campaign_objective'    => sanitize_text_field( $request->get_param( 'campaign_objective' ) ),
		'number_of_emails_sent' => intval( $request->get_param( 'number_of_emails_sent' ) ),
		'number_of_clicks'      => intval( $request->get_param( 'number_of_clicks' ) ),
		'number_of_conversions' => intval( $request->get_param( 'number_of_conversions' ) ),
		'conversion_rate'       => floatval( $request->get_param( 'conversion_rate' ) ),
		'revenue_generated'     => floatval( $request->get_param( 'revenue_generated' ) ),
		'custom_one'            => sanitize_text_field( $request->get_param( 'custom_one' ) ),
		'custom_two'            => sanitize_text_field( $request->get_param( 'custom_two' ) ),
		'custom_three'          => sanitize_text_field( $request->get_param( 'custom_three' ) ),
		'currency'              => sanitize_text_field( $request->get_param( 'currency' ) ),
		'financial_year'        => sanitize_text_field( $request->get_param( 'financial_year' ) ),
		'financial_period'      => sanitize_text_field( $request->get_param( 'financial_period' ) ),
		'meta_fields'           => sanitize_textarea_field( $request->get_param( 'meta_fields' ) ),
		'remarks'               => sanitize_textarea_field( $request->get_param( 'remarks' ) ),
		'store_meta'            => sanitize_textarea_field( $request->get_param( 'store_meta' ) ),
		'coupon_meta'           => sanitize_textarea_field( $request->get_param( 'coupon_meta' ) ),
		'workflow_meta'         => sanitize_textarea_field( $request->get_param( 'workflow_meta' ) ),
		'share_url'             => esc_url_raw( $request->get_param( 'share_url' ) ),
		'share_status'          => sanitize_text_field( $request->get_param( 'share_status' ) ),
		'business_name'         => sanitize_text_field( $request->get_param( 'business_name' ) ),
		'business_number'       => sanitize_text_field( $request->get_param( 'business_number' ) ),
		'ref_business'          => sanitize_text_field( $request->get_param( 'ref_business' ) ),
		'ref_business_number'   => sanitize_text_field( $request->get_param( 'ref_business_number' ) ),
		'ref_user'              => sanitize_text_field( $request->get_param( 'ref_user' ) ),
		'ref_appname'           => sanitize_text_field( $request->get_param( 'ref_appname' ) ),
		'ref_datetime'          => sanitize_text_field( $request->get_param( 'ref_datetime' ) ),
		'social_login_used'     => sanitize_text_field( $request->get_param( 'social_login_used' ) ),
		'created_user'          => sanitize_text_field( $request->get_param( 'created_user' ) ),
		'created_userid'        => intval( $request->get_param( 'created_userid' ) ),
		'created_datetime'      => sanitize_text_field( $request->get_param( 'created_datetime' ) ),
		'app_name'              => sanitize_text_field( $request->get_param( 'app_name' ) ),
	);
}

/**
 * Retrieve a campaign by ID.
 *
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_get_campaign_by_id( WP_REST_Request $request ) {
	global $wpdb;
	$table_name  = $wpdb->prefix . 'campaigns';
	$campaign_id = isset( $request['id'] ) ? intval( $request['id'] ) : 0;

	if ( empty( $campaign_id ) ) {
		return new WP_REST_Response( array( 'message' => 'Campaign ID is required' ), 400 );
	}

	$campaign = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM $table_name WHERE campaign_id = %d",
			$campaign_id
		),
		ARRAY_A
	);

	if ( null === $campaign ) {
		return new WP_REST_Response(
			array( 'message' => 'Campaign not found' ),
			404
		);
	}

	return new WP_REST_Response(
		$campaign,
		200
	);
}


/**
 * Create a new campaign.
 *
 * @param wpdb            $wpdb The WordPress database object.
 * @param string          $table_name The table name.
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_create_campaign( $wpdb, $table_name, $request ) {
	$data        = woomorrintegration_campaign_get_sanitized_data( $request );
	$inserted    = $wpdb->insert( $table_name, $data );
	$campaign_id = $wpdb->insert_id;

	if ( false === $inserted ) {
		error_log( 'Failed to insert campaign: ' . $wpdb->last_error );
		return new WP_REST_Response(
			array(
				'message' => 'Failed to create campaign.',
				'error'   => $wpdb->last_error,
			),
			500
		);
	}

	$campaign = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM $table_name WHERE campaign_id = %d",
			$campaign_id
		),
		ARRAY_A
	);

	return new WP_REST_Response(
		$campaign,
		201
	);
}


/**
 * Retrieve campaigns with optional filters.
 *
 * @param wpdb            $wpdb The WordPress database object.
 * @param string          $table_name The table name.
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_get_campaigns( $wpdb, $table_name, $request ) {
	$id = isset( $request['id'] ) ? intval( $request['id'] ) : 0;
	if ( ! empty( $id ) ) {
		return woomorrintegration_get_campaign_by_id( $request );
	}

	$filters = array(
		'from_business_number' => sanitize_text_field( $request->get_param( 'from_business_number' ) ),
		'from_business_name'   => sanitize_text_field( $request->get_param( 'from_business_name' ) ),
		'to_business_number'   => sanitize_text_field( $request->get_param( 'to_business_number' ) ),
		'to_business_name'     => sanitize_text_field( $request->get_param( 'to_business_name' ) ),
		'business_number'      => sanitize_text_field( $request->get_param( 'business_number' ) ),
		'business_name'        => sanitize_text_field( $request->get_param( 'business_name' ) ),
		'campaign_name'        => sanitize_text_field( $request->get_param( 'campaign_name' ) ),
	);

	$search_term = sanitize_text_field( $request->get_param( 'search' ) );

	$query    = "SELECT * FROM $table_name WHERE 1=1";
	$bindings = array();

	foreach ( $filters as $key => $value ) {
		if ( ! empty( $value ) ) {
			$query     .= $wpdb->prepare( " AND $key = %s", $value );
			$bindings[] = $value;
		}
	}

	if ( ! empty( $search_term ) ) {
		$search_query = $wpdb->prepare( " AND (campaign_name LIKE %s OR description LIKE %s)", '%' . $wpdb->esc_like( $search_term ) . '%', '%' . $wpdb->esc_like( $search_term ) . '%' );
		$query .= $search_query;
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
 * Update an existing campaign.
 *
 * @param wpdb            $wpdb The WordPress database object.
 * @param string          $table_name The table name.
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_update_campaign( $wpdb, $table_name, $request ) {
	$campaign_id = isset( $request['id'] ) ? intval( $request['id'] ) : 0;

	if ( empty( $campaign_id ) ) {
		return new WP_REST_Response( array( 'message' => 'Campaign ID is required' ), 400 );
	}

	$data    = woomorrintegration_campaign_get_sanitized_data( $request );
	$updated = $wpdb->update( $table_name, $data, array( 'campaign_id' => $campaign_id ) );

	if ( false === $updated ) {
		error_log( 'Failed to update campaign: ' . $wpdb->last_error );
		return new WP_REST_Response(
			array(
				'message' => 'Failed to update campaign',
				'error'   => $wpdb->last_error,
			),
			500
		);
	}

	$campaign = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM $table_name WHERE campaign_id = %d",
			$campaign_id
		),
		ARRAY_A
	);

	return new WP_REST_Response(
		$campaign,
		200
	);
}

/**
 * Delete a campaign.
 *
 * @param wpdb            $wpdb The WordPress database object.
 * @param string          $table_name The table name.
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_delete_campaign( $wpdb, $table_name, $request ) {
	$campaign_id = isset( $request['id'] ) ? intval( $request['id'] ) : 0;

	if ( empty( $campaign_id ) ) {
		return new WP_REST_Response( array( 'message' => 'Campaign ID is required' ), 400 );
	}

	$deleted = $wpdb->delete( $table_name, array( 'campaign_id' => $campaign_id ) );

	if ( false === $deleted ) {
		error_log( 'Failed to delete campaign: ' . $wpdb->last_error );
		return new WP_REST_Response(
			array(
				'message' => 'Failed to delete campaign',
				'error'   => $wpdb->last_error,
			),
			500
		);
	}

	return new WP_REST_Response(
		array(
			'message'     => 'Campaign deleted',
			'campaign_id' => $campaign_id,
		),
		200
	);
}


// ===================================================================
// Campaign Offer API Functions
// ===================================================================

/**
 * Main handler for campaign offer API requests.
 *
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_campaign_offer_handler( WP_REST_Request $request ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'campaign_offer';
	switch ( $request->get_method() ) {
		case 'POST':
			return woomorrintegration_create_campaign_offer( $wpdb, $table_name, $request );
		case 'GET':
			return woomorrintegration_get_campaign_offers( $wpdb, $table_name, $request );
		case 'PUT':
			return woomorrintegration_update_campaign_offer( $wpdb, $table_name, $request );
		case 'DELETE':
			return woomorrintegration_delete_campaign_offer( $wpdb, $table_name, $request );
		default:
			return new WP_REST_Response( array( 'message' => 'Invalid request method' ), 405 );
	}
}


/**
 * Sanitize and retrieve data from the request for campaign offers.
 *
 * @param WP_REST_Request $request The REST API request.
 * @return array The sanitized data.
 */
function woomorrintegration_campaign_offer_get_sanitized_data( WP_REST_Request $request ) {
	return array(
		'status'                    => sanitize_text_field( $request->get_param( 'status' ) ),
		'description'               => sanitize_textarea_field( $request->get_param( 'description' ) ),
		'icon'                      => sanitize_text_field( $request->get_param( 'icon' ) ),
		'campaign_id'               => intval( $request->get_param( 'campaign_id' ) ),
		'campaign_name'             => sanitize_text_field( $request->get_param( 'campaign_name' ) ),
		'campaign_code'             => sanitize_text_field( $request->get_param( 'campaign_code' ) ),
		'offer_name'                => sanitize_text_field( $request->get_param( 'offer_name' ) ),
		'offer_code'                => sanitize_text_field( $request->get_param( 'offer_code' ) ),
		'offer_description'         => sanitize_textarea_field( $request->get_param( 'offer_description' ) ),
		'offer_enddate'             => sanitize_text_field( $request->get_param( 'offer_enddate' ) ),
		'offer_type'                => sanitize_text_field( $request->get_param( 'offer_type' ) ),
		'offer_start_date'          => sanitize_text_field( $request->get_param( 'offer_start_date' ) ),
		'offer_end_date'            => sanitize_text_field( $request->get_param( 'offer_end_date' ) ),
		'offer_redemption_status'   => sanitize_text_field( $request->get_param( 'offer_redemption_status' ) ),
		'offer_redemption_date'     => sanitize_text_field( $request->get_param( 'offer_redemption_date' ) ),
		'offer_value'               => floatval( $request->get_param( 'offer_value' ) ),
		'offer_usage_frequency'     => intval( $request->get_param( 'offer_usage_frequency' ) ),
		'offer_satisfaction_rating' => floatval( $request->get_param( 'offer_satisfaction_rating' ) ),
		'offer_redemption_rate'     => floatval( $request->get_param( 'offer_redemption_rate' ) ),
		'custom_one'                => sanitize_text_field( $request->get_param( 'custom_one' ) ),
		'custom_two'                => sanitize_text_field( $request->get_param( 'custom_two' ) ),
		'custom_three'              => sanitize_text_field( $request->get_param( 'custom_three' ) ),
		'currency'                  => sanitize_text_field( $request->get_param( 'currency' ) ),
		'financial_year'            => sanitize_text_field( $request->get_param( 'financial_year' ) ),
		'financial_period'          => sanitize_text_field( $request->get_param( 'financial_period' ) ),
		'meta_fields'               => sanitize_textarea_field( $request->get_param( 'meta_fields' ) ),
		'remarks'                   => sanitize_textarea_field( $request->get_param( 'remarks' ) ),
		'store_meta'                => sanitize_textarea_field( $request->get_param( 'store_meta' ) ),
		'workflow_meta'             => sanitize_textarea_field( $request->get_param( 'workflow_meta' ) ),
		'share_url'                 => esc_url_raw( $request->get_param( 'share_url' ) ),
		'share_status'              => sanitize_text_field( $request->get_param( 'share_status' ) ),
		'business_name'             => sanitize_text_field( $request->get_param( 'business_name' ) ),
		'business_number'           => sanitize_text_field( $request->get_param( 'business_number' ) ),
		'ref_business'              => sanitize_text_field( $request->get_param( 'ref_business' ) ),
		'ref_business_number'       => sanitize_text_field( $request->get_param( 'ref_business_number' ) ),
		'ref_user'                  => sanitize_text_field( $request->get_param( 'ref_user' ) ),
		'ref_appname'               => sanitize_text_field( $request->get_param( 'ref_appname' ) ),
		'ref_datetime'              => sanitize_text_field( $request->get_param( 'ref_datetime' ) ),
		'social_login_used'         => sanitize_text_field( $request->get_param( 'social_login_used' ) ),
		'created_user'              => sanitize_text_field( $request->get_param( 'created_user' ) ),
		'created_userid'            => intval( $request->get_param( 'created_userid' ) ),
		'created_datetime'          => sanitize_text_field( $request->get_param( 'created_datetime' ) ),
		'app_name'                  => sanitize_text_field( $request->get_param( 'app_name' ) ),
	);
}


/**
 * Retrieve a campaign offer by ID.
 *
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_get_campaign_offer_by_id( WP_REST_Request $request ) {
	global $wpdb;
	$table_name        = $wpdb->prefix . 'campaign_offer';
	$campaign_offer_id = isset( $request['id'] ) ? intval( $request['id'] ) : 0;

	if ( empty( $campaign_offer_id ) ) {
		return new WP_REST_Response( array( 'message' => 'Campaign Offer ID is required' ), 400 );
	}

	$campaign_offer = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM $table_name WHERE campaign_offer_id = %d",
			$campaign_offer_id
		),
		ARRAY_A
	);

	if ( null === $campaign_offer ) {
		return new WP_REST_Response(
			array( 'message' => 'Campaign Offer not found' ),
			404
		);
	}

	return new WP_REST_Response(
		$campaign_offer,
		200
	);
}


/**
 * Create a new campaign offer.
 *
 * @param wpdb            $wpdb The WordPress database object.
 * @param string          $table_name The table name.
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_create_campaign_offer( $wpdb, $table_name, $request ) {
	$data              = woomorrintegration_campaign_offer_get_sanitized_data( $request );
	$inserted          = $wpdb->insert( $table_name, $data );
	$campaign_offer_id = $wpdb->insert_id;

	if ( false === $inserted ) {
		error_log( 'Failed to insert campaign offer: ' . $wpdb->last_error );
		return new WP_REST_Response(
			array(
				'message' => 'Failed to create campaign offer.',
				'error'   => $wpdb->last_error,
			),
			500
		);
	}

	$campaign_offer = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM $table_name WHERE campaign_offer_id = %d",
			$campaign_offer_id
		),
		ARRAY_A
	);

	return new WP_REST_Response(
		$campaign_offer,
		201
	);
}


/**
 * Retrieve campaign offers with optional filters.
 *
 * @param wpdb            $wpdb The WordPress database object.
 * @param string          $table_name The table name.
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_get_campaign_offers( $wpdb, $table_name, $request ) {
	$id = isset( $request['id'] ) ? intval( $request['id'] ) : 0;
	if ( ! empty( $id ) ) {
		return woomorrintegration_get_campaign_offer_by_id( $request );
	}

	$filters = array(
		'campaign_id'     => intval( $request->get_param( 'campaign_id' ) ),
		'business_number' => sanitize_text_field( $request->get_param( 'business_number' ) ),
		'business_name'   => sanitize_text_field( $request->get_param( 'business_name' ) ),
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
		error_log( 'Failed to retrieve campaign offers: ' . $wpdb->last_error );
		return new WP_REST_Response(
			array(
				'message' => 'Error retrieving campaign offers',
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
 * Update an existing campaign offer.
 *
 * @param wpdb            $wpdb The WordPress database object.
 * @param string          $table_name The table name.
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_update_campaign_offer( $wpdb, $table_name, $request ) {
	$campaign_offer_id = isset( $request['id'] ) ? intval( $request['id'] ) : 0;

	if ( empty( $campaign_offer_id ) ) {
		return new WP_REST_Response( array( 'message' => 'Campaign Offer ID is required' ), 400 );
	}

	$data    = woomorrintegration_campaign_offer_get_sanitized_data( $request );
	$updated = $wpdb->update( $table_name, $data, array( 'campaign_offer_id' => $campaign_offer_id ) );

	if ( false === $updated ) {
		error_log( 'Failed to update campaign offer: ' . $wpdb->last_error );
		return new WP_REST_Response(
			array(
				'message' => 'Failed to update campaign offer',
				'error'   => $wpdb->last_error,
			),
			500
		);
	}

	$campaign_offer = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM $table_name WHERE campaign_offer_id = %d",
			$campaign_offer_id
		),
		ARRAY_A
	);

	return new WP_REST_Response(
		$campaign_offer,
		200
	);
}


/**
 * Delete a campaign offer.
 *
 * @param wpdb            $wpdb The WordPress database object.
 * @param string          $table_name The table name.
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_delete_campaign_offer( $wpdb, $table_name, $request ) {
	$campaign_offer_id = isset( $request['id'] ) ? intval( $request['id'] ) : 0;

	if ( empty( $campaign_offer_id ) ) {
		return new WP_REST_Response( array( 'message' => 'Campaign Offer ID is required' ), 400 );
	}

	$deleted = $wpdb->delete( $table_name, array( 'campaign_offer_id' => $campaign_offer_id ) );

	if ( false === $deleted ) {
		error_log( 'Failed to delete campaign offer: ' . $wpdb->last_error );
		return new WP_REST_Response(
			array(
				'message' => 'Failed to delete campaign offer',
				'error'   => $wpdb->last_error,
			),
			500
		);
	}

	return new WP_REST_Response(
		array(
			'message'           => 'Campaign offer deleted',
			'campaign_offer_id' => $campaign_offer_id,
		),
		200
	);
}


// ===================================================================
// Campaign Tracking API Functions
// ===================================================================

/**
 * Main handler for campaign tracking API requests.
 *
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_campaign_tracking_handler( WP_REST_Request $request ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'campaign_tracking';
	switch ( $request->get_method() ) {
		case 'POST':
			return woomorrintegration_create_campaign_tracking( $wpdb, $table_name, $request );
		case 'GET':
			return woomorrintegration_get_campaign_trackings( $wpdb, $table_name, $request );
		case 'PUT':
			return woomorrintegration_update_campaign_tracking( $wpdb, $table_name, $request );
		case 'DELETE':
			return woomorrintegration_delete_campaign_tracking( $wpdb, $table_name, $request );
		default:
			return new WP_REST_Response( array( 'message' => 'Invalid request method' ), 405 );
	}
}

/**
 * Sanitize and retrieve data from the request for campaign tracking.
 *
 * @param WP_REST_Request $request The REST API request.
 * @return array The sanitized data.
 */
function woomorrintegration_campaign_tracking_get_sanitized_data( WP_REST_Request $request ) {
	return array(
		'campaign_id'            => intval( $request->get_param( 'campaign_id' ) ),
		'campaign_offer_id'      => intval( $request->get_param( 'campaign_offer_id' ) ),
		'campaign_offer_user_id' => intval( $request->get_param( 'campaign_offer_user_id' ) ),
		'user_id'                => intval( $request->get_param( 'user_id' ) ),
		'offer_type'             => sanitize_text_field( $request->get_param( 'offer_type' ) ),
		'offer_name'             => sanitize_text_field( $request->get_param( 'offer_name' ) ),
		'offer_code'             => sanitize_text_field( $request->get_param( 'offer_code' ) ),
		'status'                 => sanitize_text_field( $request->get_param( 'status' ) ),
		'description'            => sanitize_textarea_field( $request->get_param( 'description' ) ),
		'icon'                   => sanitize_text_field( $request->get_param( 'icon' ) ),
		'event_type'             => sanitize_text_field( $request->get_param( 'event_type' ) ),
		'event_timestamp'        => sanitize_text_field( $request->get_param( 'event_timestamp' ) ),
		'event_details'          => sanitize_textarea_field( $request->get_param( 'event_details' ) ),
		'custom_one'             => sanitize_text_field( $request->get_param( 'custom_one' ) ),
		'custom_two'             => sanitize_text_field( $request->get_param( 'custom_two' ) ),
		'custom_three'           => sanitize_text_field( $request->get_param( 'custom_three' ) ),
		'currency'               => sanitize_text_field( $request->get_param( 'currency' ) ),
		'financial_year'         => sanitize_text_field( $request->get_param( 'financial_year' ) ),
		'financial_period'       => sanitize_text_field( $request->get_param( 'financial_period' ) ),
		'event_meta'             => sanitize_textarea_field( $request->get_param( 'event_meta' ) ),
		'open_meta'              => sanitize_textarea_field( $request->get_param( 'open_meta' ) ),
		'meta_fields'            => sanitize_textarea_field( $request->get_param( 'meta_fields' ) ),
		'remarks'                => sanitize_textarea_field( $request->get_param( 'remarks' ) ),
		'store_meta'             => sanitize_textarea_field( $request->get_param( 'store_meta' ) ),
		'workflow_meta'          => sanitize_textarea_field( $request->get_param( 'workflow_meta' ) ),
		'share_url'              => esc_url_raw( $request->get_param( 'share_url' ) ),
		'share_status'           => sanitize_text_field( $request->get_param( 'share_status' ) ),
		'business_name'          => sanitize_text_field( $request->get_param( 'business_name' ) ),
		'business_number'        => sanitize_text_field( $request->get_param( 'business_number' ) ),
		'ref_business'           => sanitize_text_field( $request->get_param( 'ref_business' ) ),
		'ref_business_number'    => sanitize_text_field( $request->get_param( 'ref_business_number' ) ),
		'ref_user'               => sanitize_text_field( $request->get_param( 'ref_user' ) ),
		'ref_appname'            => sanitize_text_field( $request->get_param( 'ref_appname' ) ),
		'ref_datetime'           => sanitize_text_field( $request->get_param( 'ref_datetime' ) ),
		'social_login_used'      => sanitize_text_field( $request->get_param( 'social_login_used' ) ),
		'created_user'           => sanitize_text_field( $request->get_param( 'created_user' ) ),
		'created_userid'         => intval( $request->get_param( 'created_userid' ) ),
		'created_datetime'       => sanitize_text_field( $request->get_param( 'created_datetime' ) ),
		'app_name'               => sanitize_text_field( $request->get_param( 'app_name' ) ),
	);
}


/**
 * Retrieve a campaign tracking record by ID.
 *
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_get_campaign_tracking_by_id( WP_REST_Request $request ) {
	global $wpdb;
	$table_name           = $wpdb->prefix . 'campaign_tracking';
	$campaign_tracking_id = isset( $request['id'] ) ? intval( $request['id'] ) : 0;

	if ( empty( $campaign_tracking_id ) ) {
		return new WP_REST_Response( array( 'message' => 'Campaign Tracking ID is required' ), 400 );
	}

	$campaign_tracking = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM $table_name WHERE campaign_tracking_id = %d",
			$campaign_tracking_id
		),
		ARRAY_A
	);

	if ( null === $campaign_tracking ) {
		return new WP_REST_Response(
			array( 'message' => 'Campaign Tracking record not found' ),
			404
		);
	}

	return new WP_REST_Response(
		$campaign_tracking,
		200
	);
}


/**
 * Create a new campaign tracking record.
 *
 * @param wpdb            $wpdb The WordPress database object.
 * @param string          $table_name The table name.
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_create_campaign_tracking( $wpdb, $table_name, $request ) {
	$data                 = woomorrintegration_campaign_tracking_get_sanitized_data( $request );
	$inserted             = $wpdb->insert( $table_name, $data );
	$campaign_tracking_id = $wpdb->insert_id;

	if ( false === $inserted ) {
		error_log( 'Failed to insert campaign tracking record: ' . $wpdb->last_error );
		return new WP_REST_Response(
			array(
				'message' => 'Failed to create campaign tracking record.',
				'error'   => $wpdb->last_error,
			),
			500
		);
	}

	$campaign_tracking = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM $table_name WHERE campaign_tracking_id = %d",
			$campaign_tracking_id
		),
		ARRAY_A
	);

	return new WP_REST_Response(
		$campaign_tracking,
		201
	);
}


/**
 * Retrieve campaign tracking records with optional filters.
 *
 * @param wpdb            $wpdb The WordPress database object.
 * @param string          $table_name The table name.
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_get_campaign_trackings( $wpdb, $table_name, $request ) {
	$id = isset( $request['id'] ) ? intval( $request['id'] ) : 0;
	if ( ! empty( $id ) ) {
		return woomorrintegration_get_campaign_tracking_by_id( $request );
	}

	// Add filters here as needed.
	$filters = array(
		'campaign_id'     => intval( $request->get_param( 'campaign_id' ) ),
		'business_number' => sanitize_text_field( $request->get_param( 'business_number' ) ),
		'business_name'   => sanitize_text_field( $request->get_param( 'business_name' ) ),
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
		error_log( 'Failed to retrieve campaign tracking records: ' . $wpdb->last_error );
		return new WP_REST_Response(
			array(
				'message' => 'Error retrieving campaign tracking records',
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
 * Update an existing campaign tracking record.
 *
 * @param wpdb            $wpdb The WordPress database object.
 * @param string          $table_name The table name.
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_update_campaign_tracking( $wpdb, $table_name, $request ) {
	$campaign_tracking_id = isset( $request['id'] ) ? intval( $request['id'] ) : 0;

	if ( empty( $campaign_tracking_id ) ) {
		return new WP_REST_Response( array( 'message' => 'Campaign Tracking ID is required' ), 400 );
	}

	$data    = woomorrintegration_campaign_tracking_get_sanitized_data( $request );
	$updated = $wpdb->update( $table_name, $data, array( 'campaign_tracking_id' => $campaign_tracking_id ) );

	if ( false === $updated ) {
		error_log( 'Failed to update campaign tracking record: ' . $wpdb->last_error );
		return new WP_REST_Response(
			array(
				'message' => 'Failed to update campaign tracking record',
				'error'   => $wpdb->last_error,
			),
			500
		);
	}

	$campaign_tracking = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM $table_name WHERE campaign_tracking_id = %d",
			$campaign_tracking_id
		),
		ARRAY_A
	);

	return new WP_REST_Response(
		$campaign_tracking,
		200
	);
}

/**
 * Delete a campaign tracking record.
 *
 * @param wpdb            $wpdb The WordPress database object.
 * @param string          $table_name The table name.
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_delete_campaign_tracking( $wpdb, $table_name, $request ) {
	$campaign_tracking_id = isset( $request['id'] ) ? intval( $request['id'] ) : 0;

	if ( empty( $campaign_tracking_id ) ) {
		return new WP_REST_Response( array( 'message' => 'Campaign Tracking ID is required' ), 400 );
	}

	$deleted = $wpdb->delete( $table_name, array( 'campaign_tracking_id' => $campaign_tracking_id ) );

	if ( false === $deleted ) {
		error_log( 'Failed to delete campaign tracking record: ' . $wpdb->last_error );
		return new WP_REST_Response(
			array(
				'message' => 'Failed to delete campaign tracking record',
				'error'   => $wpdb->last_error,
			),
			500
		);
	}

	return new WP_REST_Response(
		array(
			'message'              => 'Campaign tracking record deleted',
			'campaign_tracking_id' => $campaign_tracking_id,
		),
		200
	);
}

// ===================================================================
// Campaign User Offer API Functions
// ===================================================================

/**
 *  Main handler for campaign user offer API requests .
 *
 * @param WP_REST_Request $request The REST API request .
 * @return WP_REST_Response The response .
 */
function woomorrintegration_campaign_user_offer_handler( WP_REST_Request $request ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'campaign_user_offer';
	switch ( $request->get_method() ) {
		case 'POST':
			return woomorrintegration_create_campaign_user_offer( $wpdb, $table_name, $request );
		case 'GET':
			return woomorrintegration_get_campaign_user_offers( $wpdb, $table_name, $request );
		case 'PUT':
			return woomorrintegration_update_campaign_user_offer( $wpdb, $table_name, $request );
		case 'DELETE':
			return woomorrintegration_delete_campaign_user_offer( $wpdb, $table_name, $request );
		default:
			return new WP_REST_Response( array( 'message' => 'Invalid request method' ), 405 );
	}
}


/**
 * Sanitize and retrieve data from the request for campaign user offers.
 *
 * @param WP_REST_Request $request The REST API request.
 * @return array The sanitized data.
 */
function woomorrintegration_campaign_user_offer_get_sanitized_data( WP_REST_Request $request ) {
	return array(
		'campaign_offer_id'       => intval( $request->get_param( 'campaign_offer_id' ) ),
		'status'                  => sanitize_text_field( $request->get_param( 'status' ) ),
		'open_status'             => sanitize_text_field( $request->get_param( 'open_status' ) ),
		'description'             => sanitize_textarea_field( $request->get_param( 'description' ) ),
		'icon'                    => sanitize_text_field( $request->get_param( 'icon' ) ),
		'user_id'                 => intval( $request->get_param( 'user_id' ) ),
		'user_name'               => sanitize_text_field( $request->get_param( 'user_name' ) ),
		'user_email'              => sanitize_email( $request->get_param( 'user_email' ) ),
		'user_mobile'             => sanitize_text_field( $request->get_param( 'user_mobile' ) ),
		'to_business_name'        => sanitize_text_field( $request->get_param( 'to_business_name' ) ),
		'to_business_no'          => sanitize_text_field( $request->get_param( 'to_business_no' ) ),
		'for_business_name'       => sanitize_text_field( $request->get_param( 'for_business_name' ) ),
		'for_business_no'         => sanitize_text_field( $request->get_param( 'for_business_no' ) ),
		'user_fullname'           => sanitize_text_field( $request->get_param( 'user_fullname' ) ),
		'user_meta'               => sanitize_textarea_field( $request->get_param( 'user_meta' ) ),
		'user_social_meta'        => sanitize_textarea_field( $request->get_param( 'user_social_meta' ) ),
		'campaign_id'             => intval( $request->get_param( 'campaign_id' ) ),
		'campaign_type'           => sanitize_text_field( $request->get_param( 'campaign_type' ) ),
		'campaign_name'           => sanitize_text_field( $request->get_param( 'campaign_name' ) ),
		'campaign_code'           => sanitize_text_field( $request->get_param( 'campaign_code' ) ),
		'campaign_url'            => esc_url_raw( $request->get_param( 'campaign_url' ) ),
		'campaign_start'          => sanitize_text_field( $request->get_param( 'campaign_start' ) ),
		'campaign_end'            => sanitize_text_field( $request->get_param( 'campaign_end' ) ),
		'campaign_status'         => sanitize_text_field( $request->get_param( 'campaign_status' ) ),
		'offer_name'              => sanitize_text_field( $request->get_param( 'offer_name' ) ),
		'offer_code'              => sanitize_text_field( $request->get_param( 'offer_code' ) ),
		'offer_description'       => sanitize_textarea_field( $request->get_param( 'offer_description' ) ),
		'offer_url'               => esc_url_raw( $request->get_param( 'offer_url' ) ),
		'offer_status'            => sanitize_text_field( $request->get_param( 'offer_status' ) ),
		'offer_type'              => sanitize_text_field( $request->get_param( 'offer_type' ) ),
		'offer_start_date'        => sanitize_text_field( $request->get_param( 'offer_start_date' ) ),
		'offer_end_date'          => sanitize_text_field( $request->get_param( 'offer_end_date' ) ),
		'offer_redemption_status' => sanitize_text_field( $request->get_param( 'offer_redemption_status' ) ),
		'offer_redemption_date'   => sanitize_text_field( $request->get_param( 'offer_redemption_date' ) ),
		'offer_percent'           => floatval( $request->get_param( 'offer_percent' ) ),
		'offer_value'             => floatval( $request->get_param( 'offer_value' ) ),
		'offer_amount'            => floatval( $request->get_param( 'offer_amount' ) ),
		'offer_opens'             => intval( $request->get_param( 'offer_opens' ) ),
		'offer_share_count'       => intval( $request->get_param( 'offer_share_count' ) ),
		'offer_share_meta'        => sanitize_textarea_field( $request->get_param( 'offer_share_meta' ) ),
		'custom_one'              => sanitize_text_field( $request->get_param( 'custom_one' ) ),
		'custom_two'              => sanitize_text_field( $request->get_param( 'custom_two' ) ),
		'custom_three'            => sanitize_text_field( $request->get_param( 'custom_three' ) ),
		'currency'                => sanitize_text_field( $request->get_param( 'currency' ) ),
		'financial_year'          => sanitize_text_field( $request->get_param( 'financial_year' ) ),
		'financial_period'        => sanitize_text_field( $request->get_param( 'financial_period' ) ),
		'event_meta'              => sanitize_textarea_field( $request->get_param( 'event_meta' ) ),
		'open_meta'               => sanitize_textarea_field( $request->get_param( 'open_meta' ) ),
		'meta_fields'             => sanitize_textarea_field( $request->get_param( 'meta_fields' ) ),
		'remarks'                 => sanitize_textarea_field( $request->get_param( 'remarks' ) ),
		'store_meta'              => sanitize_textarea_field( $request->get_param( 'store_meta' ) ),
		'workflow_meta'           => sanitize_textarea_field( $request->get_param( 'workflow_meta' ) ),
		'share_url'               => esc_url_raw( $request->get_param( 'share_url' ) ),
		'share_status'            => sanitize_text_field( $request->get_param( 'share_status' ) ),
		'business_name'           => sanitize_text_field( $request->get_param( 'business_name' ) ),
		'business_number'         => sanitize_text_field( $request->get_param( 'business_number' ) ),
		'ref_business'            => sanitize_text_field( $request->get_param( 'ref_business' ) ),
		'ref_business_number'     => sanitize_text_field( $request->get_param( 'ref_business_number' ) ),
		'ref_user'                => sanitize_text_field( $request->get_param( 'ref_user' ) ),
		'ref_appname'             => sanitize_text_field( $request->get_param( 'ref_appname' ) ),
		'ref_datetime'            => sanitize_text_field( $request->get_param( 'ref_datetime' ) ),
		'social_login_used'       => sanitize_text_field( $request->get_param( 'social_login_used' ) ),
		'created_user'            => sanitize_text_field( $request->get_param( 'created_user' ) ),
		'created_userid'          => intval( $request->get_param( 'created_userid' ) ),
		'created_datetime'        => sanitize_text_field( $request->get_param( 'created_datetime' ) ),
		'app_name'                => sanitize_text_field( $request->get_param( 'app_name' ) ),
	);
}


/**
 * Retrieve a campaign user offer by ID.
 *
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_get_campaign_user_offer_by_id( WP_REST_Request $request ) {
	global $wpdb;
	$table_name             = $wpdb->prefix . 'campaign_user_offer';
	$campaign_user_offer_id = isset( $request['id'] ) ? intval( $request['id'] ) : 0;

	if ( empty( $campaign_user_offer_id ) ) {
		return new WP_REST_Response( array( 'message' => 'Campaign User Offer ID is required' ), 400 );
	}

	$campaign_user_offer = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM $table_name WHERE campaign_user_offer_id = %d",
			$campaign_user_offer_id
		),
		ARRAY_A
	);

	if ( null === $campaign_user_offer ) {
		return new WP_REST_Response(
			array( 'message' => 'Campaign User Offer not found' ),
			404
		);
	}

	return new WP_REST_Response(
		$campaign_user_offer,
		200
	);
}

/**
 * Create a new campaign user offer.
 *
 * @param wpdb            $wpdb The WordPress database object.
 * @param string          $table_name The table name.
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_create_campaign_user_offer( $wpdb, $table_name, $request ) {
	$data                   = woomorrintegration_campaign_user_offer_get_sanitized_data( $request );
	$inserted               = $wpdb->insert( $table_name, $data );
	$campaign_user_offer_id = $wpdb->insert_id;

	if ( false === $inserted ) {
		error_log( 'Failed to insert campaign user offer: ' . $wpdb->last_error );
		return new WP_REST_Response(
			array(
				'message' => 'Failed to create campaign user offer.',
				'error'   => $wpdb->last_error,
			),
			500
		);
	}

	$campaign_user_offer = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM $table_name WHERE campaign_user_offer_id = %d",
			$campaign_user_offer_id
		),
		ARRAY_A
	);

	return new WP_REST_Response(
		$campaign_user_offer,
		201
	);
}


/**
 * Retrieve campaign user offers with optional filters.
 *
 * @param wpdb            $wpdb The WordPress database object.
 * @param string          $table_name The table name.
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_get_campaign_user_offers( $wpdb, $table_name, $request ) {
	$id = isset( $request['id'] ) ? intval( $request['id'] ) : 0;
	if ( ! empty( $id ) ) {
		return woomorrintegration_get_campaign_user_offer_by_id( $request );
	}

	// Add filters here as neede.
	$filters = array(
		'campaign_id'       => intval( $request->get_param( 'campaign_id' ) ),
		'campaign_offer_id' => intval( $request->get_param( 'campaign_offer_id' ) ),
		'user_id'           => intval( $request->get_param( 'user_id' ) ),
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
		error_log( 'Failed to retrieve campaign user offers: ' . $wpdb->last_error );
		return new WP_REST_Response(
			array(
				'message' => 'Error retrieving campaign user offers',
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
 * Update an existing campaign user offer.
 *
 * @param wpdb            $wpdb The WordPress database object.
 * @param string          $table_name The table name.
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_update_campaign_user_offer( $wpdb, $table_name, $request ) {
	$campaign_user_offer_id = isset( $request['id'] ) ? intval( $request['id'] ) : 0;

	if ( empty( $campaign_user_offer_id ) ) {
		return new WP_REST_Response( array( 'message' => 'Campaign User Offer ID is required' ), 400 );
	}

	$data    = woomorrintegration_campaign_user_offer_get_sanitized_data( $request );
	$updated = $wpdb->update( $table_name, $data, array( 'campaign_user_offer_id' => $campaign_user_offer_id ) );

	if ( false === $updated ) {
		error_log( 'Failed to update campaign user offer: ' . $wpdb->last_error );
		return new WP_REST_Response(
			array(
				'message' => 'Failed to update campaign user offer',
				'error'   => $wpdb->last_error,
			),
			500
		);
	}

	$campaign_user_offer = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM $table_name WHERE campaign_user_offer_id = %d",
			$campaign_user_offer_id
		),
		ARRAY_A
	);

	return new WP_REST_Response(
		$campaign_user_offer,
		200
	);
}


/**
 * Delete a campaign user offer.
 *
 * @param wpdb            $wpdb The WordPress database object.
 * @param string          $table_name The table name.
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_delete_campaign_user_offer( $wpdb, $table_name, $request ) {
	$campaign_user_offer_id = isset( $request['id'] ) ? intval( $request['id'] ) : 0;

	if ( empty( $campaign_user_offer_id ) ) {
		return new WP_REST_Response( array( 'message' => 'Campaign User Offer ID is required' ), 400 );
	}

	$deleted = $wpdb->delete( $table_name, array( 'campaign_user_offer_id' => $campaign_user_offer_id ) );

	if ( false === $deleted ) {
		error_log( 'Failed to delete campaign user offer: ' . $wpdb->last_error );
		return new WP_REST_Response(
			array(
				'message' => 'Failed to delete campaign user offer',
				'error'   => $wpdb->last_error,
			),
			500
		);
	}

	return new WP_REST_Response(
		array(
			'message'                => 'Campaign user offer deleted',
			'campaign_user_offer_id' => $campaign_user_offer_id,
		),
		200
	);
}
