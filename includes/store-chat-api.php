<?php
/**
 * Functions for chat message handling in the Woomorrintegration plugin.
 *
 * Creating, retrieving, and updating chat messages.
 *
 * @package WOOMORRINTEGRATION
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Initializes Woomorrintegration API endpoints for user management and chat messages.
 */
function woomorrintegration_api_init() {

	// Register chat message endpoints.
	register_rest_route(
		'woomorrintegration/v1',
		'/storechatmessages',
		array(
			'methods'             => array( 'GET', 'POST', 'PUT' ),
			'callback'            => 'woomorrintegration_message_handler',
			'permission_callback' => 'woomorrintegration_permission_check',
		)
	);

	// Register reaction update endpoint.
	register_rest_route(
		'woomorrintegration/v1',
		'/updatereactions',
		array(
			'methods'             => 'POST',
			'callback'            => 'woomorrintegration_update_reactions_handler',
			'permission_callback' => 'woomorrintegration_permission_check',
		)
	);

}

add_action( 'rest_api_init', 'woomorrintegration_api_init' );

/**
 * Permission check for API requests.
 *
 * @param WP_REST_Request $request The REST API request.
 * @return bool True if the request is authorized, false otherwise.
 */
function woomorrintegration_permission_check( WP_REST_Request $request ) {
	$api_key      = get_option( 'woomorrintegration_api_secret_key' );
	$provided_key = $request->get_header( 'auth' );
	return $provided_key === $api_key;
}

/**
 * Handler for user management API requests.
 *
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_user_management_handler( WP_REST_Request $request ) {
	// Handle user management operations here.
	return new WP_REST_Response( array( 'message' => 'User management endpoint' ), 200 );
}

/**
 * Handler for chat message API requests.
 *
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_message_handler( WP_REST_Request $request ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'store_chat_messages';
	$method     = $request->get_method();

	if ( $method === 'POST' ) {
		// Create a new message.
		$data = array(
			'status'                    => sanitize_text_field( $request->get_param( 'status' ) ),
			'message_type'              => sanitize_text_field( $request->get_param( 'message_type' ) ),
			'sender_id'                 => intval( $request->get_param( 'sender_id' ) ),
			'receiver_user'             => intval( $request->get_param( 'receiver_user' ) ),
			'message'                   => sanitize_textarea_field( $request->get_param( 'message' ) ),
			'replied_to_message_id'     => intval( $request->get_param( 'replied_to_message_id' ) ),
			'related_to_message_id'     => intval( $request->get_param( 'related_to_message_id' ) ),
			'forwarded_from_message_id' => intval( $request->get_param( 'forwarded_from_message_id' ) ),
			'seen_by_users'             => $request->get_param( 'seen_by_users' ) ? wp_json_encode( $request->get_param( 'seen_by_users' ) ) : '[]',
			'reactions'                 => $request->get_param( 'reactions' ) ? wp_json_encode( $request->get_param( 'reactions' ) ) : '{}',
			'sender_desplay_name'       => sanitize_text_field( $request->get_param( 'sender_desplay_name' ) ),
			'attachment_url'            => esc_url_raw( $request->get_param( 'attachment_url' ) ),
			'attachment_name'           => sanitize_text_field( $request->get_param( 'attachment_name' ) ),
			'message_opened'            => filter_var( $request->get_param( 'message_opened' ), FILTER_VALIDATE_BOOLEAN ),
			'message_open_datetime'     => sanitize_text_field( $request->get_param( 'message_open_datetime' ) ),
			'created_at'                => current_time( 'mysql' ),
			'app_name'                  => sanitize_text_field( $request->get_param( 'app_name' ) ),
			'attachment_type'           => sanitize_text_field( $request->get_param( 'attachment_type' ) ),
			'data'                      => $request->get_param( 'data' ) ? wp_json_encode( $request->get_param( 'data' ) ) : '{}',
		);

		$inserted   = $wpdb->insert( $table_name, $data );
		$message_id = $wpdb->insert_id;

		if ( false === $inserted ) {
			// Log the error for debugging.
			error_log( 'Failed to insert message: ' . $wpdb->last_error );
			return new WP_REST_Response(
				array(
					'message' => 'Failed to create message.',
					'error'   => $wpdb->last_error,
				),
				500
			);
		}

		$message = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE message_id = %d",
				$message_id
			),
			ARRAY_A
		);

		return new WP_REST_Response(
			array(
				'message'      => 'Message created',
				'message_id'   => $message_id,
				'message_data' => $message,
			),
			201
		);

	} elseif ( $method === 'GET' ) {
		// Get messages between two users.
		$sender_id     = intval( $request->get_param( 'sender_id' ) );
		$receiver_user = intval( $request->get_param( 'receiver_user' ) );

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE (sender_id = %d AND receiver_user = %d) OR (sender_id = %d AND receiver_user = %d)",
				$sender_id,
				$receiver_user,
				$receiver_user,
				$sender_id
			),
			ARRAY_A
		);

		return new WP_REST_Response( $results, 200 );

	} elseif ( $method === 'PUT' ) {
		// Update a message by ID.
		$message_id = intval( $request->get_param( 'message_id' ) );
		$data       = array(
			'status'                    => sanitize_text_field( $request->get_param( 'status' ) ),
			'message_type'              => sanitize_text_field( $request->get_param( 'message_type' ) ),
			'message'                   => sanitize_textarea_field( $request->get_param( 'message' ) ),
			'replied_to_message_id'     => intval( $request->get_param( 'replied_to_message_id' ) ),
			'related_to_message_id'     => intval( $request->get_param( 'related_to_message_id' ) ),
			'forwarded_from_message_id' => intval( $request->get_param( 'forwarded_from_message_id' ) ),
			'seen_by_users'             => wp_json_encode( $request->get_param( 'seen_by_users' ) ),
			'reactions'                 => wp_json_encode( $request->get_param( 'reactions' ) ),
			'sender_desplay_name'       => sanitize_text_field( $request->get_param( 'sender_desplay_name' ) ),
			'attachment_url'            => esc_url_raw( $request->get_param( 'attachment_url' ) ),
			'attachment_name'           => sanitize_text_field( $request->get_param( 'attachment_name' ) ),
			'message_opened'            => filter_var( $request->get_param( 'message_opened' ), FILTER_VALIDATE_BOOLEAN ),
			'message_open_datetime'     => sanitize_text_field( $request->get_param( 'message_open_datetime' ) ),
			'updated_at'                => current_time( 'mysql' ),
			'app_name'                  => sanitize_text_field( $request->get_param( 'app_name' ) ),
			'attachment_type'           => sanitize_text_field( $request->get_param( 'attachment_type' ) ),
			'data'                      => wp_json_encode( $request->get_param( 'data' ) ),
		);

		$updated = $wpdb->update( $table_name, $data, array( 'message_id' => $message_id ) );

		if ( false === $updated ) {
			// Log the error for debugging.
			error_log( 'Failed to update message: ' . $wpdb->last_error );
			return new WP_REST_Response(
				array(
					'message' => 'Failed to update message.',
					'error'   => $wpdb->last_error,
				),
				500
			);
		}

		$message = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE message_id = %d",
				$message_id
			),
			ARRAY_A
		);

		return new WP_REST_Response(
			array(
				'message'      => 'Message updated',
				'message_id'   => $message_id,
				'message_data' => $message,
			),
			200
		);
	}

	return new WP_REST_Response( array( 'message' => 'Invalid request method' ), 405 );
}


/**
 * Handler for updating reactions of a chat message.
 *
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response.
 */
function woomorrintegration_update_reactions_handler( WP_REST_Request $request ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'store_chat_messages';
	$message_id = intval( $request->get_param( 'message_id' ) );
	$user_id    = sanitize_text_field( $request->get_param( 'user_id' ) );
	$reaction   = sanitize_text_field( $request->get_param( 'reaction' ) );

	$message = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT reactions FROM $table_name WHERE message_id = %d",
			$message_id
		),
		ARRAY_A
	);

	if ( null === $message ) {
		return new WP_REST_Response( array( 'message' => 'Message not found' ), 404 );
	}

	$reactions = json_decode( $message['reactions'], true );
	if ( ! is_array( $reactions ) ) {
		$reactions = array();
	}

	$reactions[ $user_id ] = $reaction;

	$updated = $wpdb->update(
		$table_name,
		array( 'reactions' => wp_json_encode( $reactions ) ),
		array( 'message_id' => $message_id )
	);

	if ( false === $updated ) {
		// Log the error for debugging.
		error_log( 'Failed to update reactions: ' . $wpdb->last_error );
		return new WP_REST_Response(
			array(
				'message' => 'Failed to update reactions.',
				'error'   => $wpdb->last_error,
			),
			500
		);
	}

	return new WP_REST_Response(
		array(
			'message'    => 'Reactions updated',
			'message_id' => $message_id,
		),
		200
	);
}
