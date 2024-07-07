<?php
/**
 * Functions for user management and password reset in the MyIntegration plugin.
 *
 * This file contains functions to handle user registration, updating user data,
 * initiating and confirming password reset,
 * creating users in a chat application // this is snippit only not included here.
 *
 * @package WOOMORRINTEGRATION
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Initializes MyIntegration API endpoints for user management.
 */
function ffintegration_user_management_api_init() {

	register_rest_route(
		'ffintegration/v1',
		'/users(?:/(?P<id>\d+))?',
		array(
			'methods'             => 'GET, POST, PUT',
			'callback'            => 'ffintegration_user_management_handler',
			'permission_callback' => function ( WP_REST_Request $request ) {
				$api_key = get_option( 'woomorrintegration_api_secret_key' );
				$provided_key = $request->get_header( 'auth' );
				// $provided_key = isset( $_SERVER['HTTP_X_FFINTEGRATION_API_KEY'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FFINTEGRATION_API_KEY'] ) ) : '';
				return $provided_key === $api_key;
			},
		)
	);

	// New endpoint to login.
	register_rest_route(
		'ffintegration/v1',
		'/login',
		array(
			'methods'             => 'POST',
			'callback'            => 'ffintegration_login_request_handler',
			'permission_callback' => '__return_true',
		)
	);

	// New endpoint to initiate password reset.
	register_rest_route(
		'ffintegration/v1',
		'/password-reset',
		array(
			'methods'             => 'POST',
			'callback'            => 'ffintegration_password_reset_request_handler',
			'permission_callback' => '__return_true',
		)
	);

	// New endpoint to complete password reset.
	register_rest_route(
		'ffintegration/v1',
		'/password-reset/confirm',
		array(
			'methods'             => 'POST',
			'callback'            => 'ffintegration_password_reset_confirm_handler',
			'permission_callback' => '__return_true',
		)
	);
}

add_action( 'rest_api_init', 'ffintegration_user_management_api_init' );


/**
 * Callback function for MyIntegration API endpoint.
 *
 * Handles user management operations such as creating, updating, and retrieving users.
 *
 * @param WP_REST_Request $data The request data.
 * @return WP_REST_Response The API response.
 */
function ffintegration_user_management_handler( $data ) {
	$response       = array();
	$user_id        = isset( $data['id'] ) ? intval( $data['id'] ) : 0;
	$request_params = $data->get_params();

	if ( 'POST' === $data->get_method() ) {

		if ( $user_id > 0 ) {
			return ffintegration_update_user_data( $user_id, $request_params );
		} else {
			return ffintegration_create_user( $request_params );
		}
	} elseif ( 'PUT' === $data->get_method() ) {
		return ffintegration_update_user_data( $user_id, $request_params );
	} elseif ( 'GET' === $data->get_method() ) {
		if ( isset( $request_params['email'] ) && ! empty( $request_params['email'] ) ) {
			$user_data = get_user_by( 'email', $request_params['email'] );
			if ( $user_data ) {
				$response = ffintegration_beautify_users_data( $user_data );
				return new WP_REST_Response( $response, 200 );
			} else {
				// User not found, return error response.
				$error_response = array(
					'message'    => 'User with the provided email not found.',
					'error_code' => 'user_not_found',
				);
				return new WP_REST_Response( $error_response, 404 );
			}
		}

		if ( isset( $request_params['username'] ) && ! empty( $request_params['username'] ) ) {
			$user_data = get_user_by( 'login', $request_params['username'] );
			if ( $user_data ) {
				$response = ffintegration_beautify_users_data( $user_data );
				return new WP_REST_Response( $response, 200 );
			} else {
				// User not found, return error response.
				$error_response = array(
					'message'    => 'User with the provided username not found.',
					'error_code' => 'user_not_found',
				);
				return new WP_REST_Response( $error_response, 404 );
			}
		}

		$search_for = isset( $request_params['search'] ) ? $request_params['search'] : '';
		$role       = isset( $request_params['role'] ) ? $request_params['role'] : '';

		if ( isset( $request_params['for_business_number'] ) && ! empty( $request_params['for_business_number'] ) ) {
			$existing_users = get_users_by_meta( 'for_business_number', $request_params['for_business_number'], 0, $search_for, $role );
			$users_info     = array();
			foreach ( $existing_users as $user ) {
				$user_info    = ffintegration_beautify_users_data( $user );
				$users_info[] = $user_info;
			}
			return new WP_REST_Response( $users_info, 200 );
		}

		if ( isset( $request_params['business_number'] ) && ! empty( $request_params['business_number'] ) ) {
			$existing_users = get_users_by_meta( 'business_number', $request_params['business_number'] );
			$users_info     = array();
			foreach ( $existing_users as $user ) {
				$user_info    = ffintegration_beautify_users_data( $user );
				$users_info[] = $user_info;
			}
			return new WP_REST_Response( $users_info, 200 );
		}
		$other_filter_fields = array(
			'store_url',
			'store_short_url',
			'store_icon_url',
			'store_banner_uri',
			'from_business_name',
			'from_business_number',
			'store_info',
			'store_map_url',
		);
		foreach ( $other_filter_fields as $field ) {
			if ( isset( $request_params[ $field ] ) && ! empty( $request_params[ $field ] ) ) {
				$sanitized_value = sanitize_text_field( $request_params[ $field ] );

				$existing_users = get_users_by_meta( $field, $sanitized_value );
				$users_info     = array();
				foreach ( $existing_users as $user ) {
					$user_info    = ffintegration_beautify_users_data( $user );
					$users_info[] = $user_info;
				}
				return new WP_REST_Response( $users_info, 200 );
			}
		}

		$response = ffintegration_retrive_users_data( $user_id, $search_for, $role );
	}

	return new WP_REST_Response( $response, 200 );
}

/**
 * Retrieves user data based on provided parameters.
 *
 * @param int    $user_id     The user ID.
 * @param array  $search      Additional parameters.
 * @param string $role      Additional parameters.
 * @return array             User data.
 */
function ffintegration_retrive_users_data( $user_id, $search = '', $role = '' ) {
	$response = array();
	if ( $user_id > 0 ) {
		// Retrieve information for a specific user based on the provided ID.
		$user_data = get_userdata( $user_id );

		if ( $user_data ) {
			$user_info = ffintegration_beautify_users_data( $user_data );
			$response  = $user_info;
		} else {
			$response['message'] = 'User not found.';
		}
	} else {
		$args = array();

		if ( $search ) {
			$args['search'] = '*' . esc_attr( $search ) . '*';
		}

		if ( $role ) {
			$args['role'] = $role;
		}

		// Retrieve information for all users.
		$users = get_users( $args );

		$users_info = array();
		foreach ( $users as $user ) {
			$user_info    = ffintegration_beautify_users_data( $user );
			$users_info[] = $user_info;
		}

		$response = $users_info;
	}

	return $response;
}

/**
 * Beautifies user data for display or processing.
 *
 * @param WP_User $user_data User data object.
 * @return array User information array.
 */
function ffintegration_beautify_users_data( $user_data ) {
	$user_id   = $user_data->ID;
	$user_info = array(
		'id'                        => $user_id,
		'username'                  => $user_data->user_login,
		'email'                     => $user_data->user_email,
		'first_name'                => get_user_meta( $user_id, 'first_name', true ),
		'last_name'                 => get_user_meta( $user_id, 'last_name', true ),
		'roles'                     => $user_data->roles,
		'business_category'         => get_user_meta( $user_id, 'business_category', true ),
		'business_name'             => get_user_meta( $user_id, 'business_name', true ),
		'business_number'           => get_user_meta( $user_id, 'business_number', true ),
		'for_business_name'         => get_user_meta( $user_id, 'for_business_name', true ),
		'for_business_number'       => get_user_meta( $user_id, 'for_business_number', true ),
		'business_description'      => get_user_meta( $user_id, 'business_description', true ),
		'business_shortdescription' => get_user_meta( $user_id, 'business_shortdescription', true ),
		'user_mobile'               => get_user_meta( $user_id, 'user_mobile', true ),
		'user_otp'                  => get_user_meta( $user_id, 'user_otp', true ),
		'avatar_url'                => get_avatar_url( $user_id ),
		'billing'                   => array(
			'first_name' => get_user_meta( $user_id, 'billing_first_name', true ),
			'last_name'  => get_user_meta( $user_id, 'billing_last_name', true ),
			'phone'      => get_user_meta( $user_id, 'billing_phone', true ),
			'address_1'  => get_user_meta( $user_id, 'billing_address_1', true ),
			'address_2'  => get_user_meta( $user_id, 'billing_address_2', true ),
			'country'    => get_user_meta( $user_id, 'billing_country', true ),
			'city'       => get_user_meta( $user_id, 'billing_city', true ),
			'state'      => get_user_meta( $user_id, 'billing_state', true ),
			'postcode'   => get_user_meta( $user_id, 'billing_postcode', true ),
			'company'    => get_user_meta( $user_id, 'billing_company', true ),
			'email'      => get_user_meta( $user_id, 'billing_email', true ),
		),
		'shipping'                  => array(
			'first_name' => get_user_meta( $user_id, 'shipping_first_name', true ),
			'last_name'  => get_user_meta( $user_id, 'shipping_last_name', true ),
			'phone'      => get_user_meta( $user_id, 'shipping_phone', true ),
			'address_1'  => get_user_meta( $user_id, 'shipping_address_1', true ),
			'address_2'  => get_user_meta( $user_id, 'shipping_address_2', true ),
			'country'    => get_user_meta( $user_id, 'shipping_country', true ),
			'city'       => get_user_meta( $user_id, 'shipping_city', true ),
			'state'      => get_user_meta( $user_id, 'shipping_state', true ),
			'postcode'   => get_user_meta( $user_id, 'shipping_postcode', true ),
			'company'    => get_user_meta( $user_id, 'shipping_company', true ),
		),
	// 'metadata' => get_user_meta($user_id),
	);

	$other_fields = array(
		'business_email',
		'business_phone_no',
		'identity_number',
		'passport_number',
		'tin_number',
		'sst_number',
		'tourism_tax_number',
		'msic_number',
		'classification_code',
		'digital_signature',
		'digital_signature_file',
		'import_license_no',
		'export_license_no',
		'billing_address_text',
		'shipping_address_text',
		'store_name',
		'ref_business_name',
		'ref_business_number',
		'store_geo_location',
		'store_meta_data',
		'store_url',
		'store_short_url',
		'store_icon_url',
		'store_banner_uri',
		'from_business_name',
		'from_business_number',
		'store_info',
		'store_map_url',
	);

	foreach ( $other_fields as $field ) {
		$user_info[ $field ] = get_user_meta( $user_id, $field, true );
	}

	return $user_info;
}

/**
 * Updates user data including billing and shipping information, roles, password, and email.
 *
 * @param int   $user_id     The ID of the user to update.
 * @param array $update_data An array of data to update for the user.
 * @param bool  $is_create   Optional. Whether the update operation is for creating a new user. Default is false.
 * @return mixed WP_REST_Response on success, array on create, or array containing error message on failure.
 */
function ffintegration_update_user_data( $user_id, $update_data, $is_create = false ) {
	$response  = array();
	$user_data = get_userdata( $user_id );

	// Define allowed fields for billing and shipping.
	$allowed_billing_fields = array(
		'first_name',
		'last_name',
		'phone',
		'address_1',
		'address_2',
		'country',
		'city',
		'state',
		'postcode',
		'company',
		'email',
	);

	$allowed_shipping_fields = array(
		'first_name',
		'last_name',
		'phone',
		'address_1',
		'address_2',
		'country',
		'city',
		'state',
		'postcode',
		'company',
	);

	// Define other fields to update.
	$other_fields_to_update = array(
		'business_category',
		'business_name',
		'for_business_number',
		'for_business_name',
		'business_description',
		'business_shortdescription',
		'user_otp',
		'first_name',
		'last_name',
		'business_email',
		'business_phone_no',
		'identity_number',
		'passport_number',
		'tin_number',
		'sst_number',
		'tourism_tax_number',
		'msic_number',
		'classification_code',
		'digital_signature',
		'digital_signature_file',
		'import_license_no',
		'export_license_no',
		'billing_address_text',
		'shipping_address_text',
		'store_name',
		'ref_business_name',
		'ref_business_number',
		'store_geo_location',
		'store_meta_data',

	);

	// Update other fields.
	foreach ( $other_fields_to_update as $field ) {
		if ( isset( $update_data[ $field ] ) ) {
			update_user_meta( $user_id, $field, sanitize_text_field( $update_data[ $field ] ) );
		}
	}

	// Define other unique fields to update.
	$other_unique_fields_to_update = array(
		'business_number',
		'user_mobile',

		'store_url',
		'store_short_url',
		'store_icon_url',
		'store_banner_uri',
		'from_business_name',
		'from_business_number',
		'store_info',
		'store_map_url',
	);

	// Update other unique fields.
	foreach ( $other_unique_fields_to_update as $field ) {
		if ( isset( $update_data[ $field ] ) ) {
			$existing_user = get_user_by_meta( $field, $update_data[ $field ], $user_id );
			if ( $existing_user ) {
				$response['message'] = $field . ' value this already used ' . $update_data[ $field ];
				return new WP_REST_Response( $response, 500 );
			}
			update_user_meta( $user_id, $field, sanitize_text_field( $update_data[ $field ] ) );
		}
	}

	// Update billing information if set.
	if ( isset( $update_data['billing'] ) && is_array( $update_data['billing'] ) ) {
		foreach ( $update_data['billing'] as $key => $value ) {
			if ( in_array( $key, $allowed_billing_fields ) && isset( $value ) ) {
				update_user_meta( $user_id, 'billing_' . $key, sanitize_text_field( $value ) );
			}
		}
	}

	// Update shipping information if set.
	if ( isset( $update_data['shipping'] ) && is_array( $update_data['shipping'] ) ) {
		foreach ( $update_data['shipping'] as $key => $value ) {
			if ( in_array( $key, $allowed_shipping_fields ) && isset( $value ) ) {
				update_user_meta( $user_id, 'shipping_' . $key, sanitize_text_field( $value ) );
			}
		}
	}

	// Update user roles if set.
	if ( isset( $update_data['roles'] ) && ! empty( $update_data['roles'] ) ) {

		$roles = $update_data['roles'];
		// Ensure that $roles is an array.
		if ( ! is_array( $roles ) ) {
			$roles = array( $roles );
		}

		// Remove any invalid roles.
		$valid_roles = array();
		foreach ( $roles as $role ) {
			if ( get_role( $role ) ) {
				$valid_roles[] = $role;
			}
		}

		// Remove existing roles.
		foreach ( $user_data->roles as $existing_role ) {
			$user_data->remove_role( $existing_role );
		}

		// Add new roles.
		foreach ( $valid_roles as $new_role ) {
			$user_data->add_role( $new_role );
		}
	}

	// Update user password if set.
	if ( isset( $update_data['password'] ) && ! empty( $update_data['password'] ) ) {
		wp_set_password( sanitize_text_field( $update_data['password'] ), $user_id );
	}

	// Update user email if set.
	if ( isset( $update_data['email'] ) && ! empty( $update_data['email'] ) ) {
		// Check if the provided email is different from the current email.
		$current_email = get_userdata( $user_id )->user_email;
		$new_email     = sanitize_email( $update_data['email'] );

		if ( $new_email !== $current_email ) {
			// Check if the new email is already in use by another user.
			if ( email_exists( $new_email ) ) {
				$response['message'] = 'Email is already in use by another user.';
				return $response;
			}

			// Update the user's email.
			wp_update_user(
				array(
					'ID'         => $user_id,
					'user_email' => $new_email,
				)
			);
		}
	}

	$user_data = get_userdata( $user_id );
	$response  = ffintegration_beautify_users_data( $user_data );
	if ( $is_create ) {
		return $response;
	}
	return new WP_REST_Response( $response, 200 );
}

/**
 * Creates a new user with provided data.
 *
 * @param array $data An array containing user data including username, email, password, and other optional fields.
 * @return mixed WP_REST_Response on success or array containing error message on failure.
 */
function ffintegration_create_user( $data ) {
	$response = array();

	// Check if required fields are present.
	if ( empty( $data['username'] ) || empty( $data['email'] ) || empty( $data['password'] ) ) {
		$response['message'] = 'Username, email, and password are required fields.';
		$response['error']   = 'Username, email, and password are required fields.';
		return new WP_REST_Response( $response, 400 );
	}

	// Define other unique fields to update.
	$unique_fields = array(
		'business_number',
		'user_mobile',

		'store_url',
		'store_short_url',
		'store_icon_url',
		'store_banner_uri',
		'from_business_name',
		'from_business_number',
		'store_info',
		'store_map_url',
	);

	// Update other unique fields.
	foreach ( $unique_fields as $field ) {
		if ( isset( $data[ $field ] ) ) {
			$existing_user = get_user_by_meta( $field, $data[ $field ] );
			if ( $existing_user ) {
				$response['message']    = $field . ' value this already used ' . $data[ $field ];
				$response['error']      = 'this value already used ';
				$response['errorField'] = $field;
				return new WP_REST_Response( $response, 500 );
			}
			// update_user_meta( $user_id, $field, sanitize_text_field( $data[ $field ] ) );
		}
	}

	// Create user data array.
	$user_data = array(
		'user_login' => sanitize_text_field( $data['username'] ),
		'user_email' => sanitize_email( $data['email'] ),
		'user_pass'  => sanitize_text_field( $data['password'] ),
	);

	// Check if roles are provided and valid
	// if ( isset( $data['roles'] ) && is_array( $data['roles'] ) ) {
	// $valid_roles = array( 'admin', 'customer' ); // Adjust this as per your allowed roles
	// $roles       = array_intersect( $data['roles'], $valid_roles );

	// $roles = $data['roles'] ;
	// Ensure that $roles is an array
	// if ( ! is_array( $roles) ) {
	// $roles = array( $roles );
	// }

	// Remove any invalid roles
	// $valid_roles = array();
	// foreach ( $roles as $role ) {
	// if ( get_role( $role ) ) {
	// $valid_roles[] = $role;
	// }
	// }

	// $user_data['roles'] = ! empty( $valid_roles ) ? $valid_roles : array( 'customer' ); // Default role if none provided or invalid
	// }

	// Create the user.
	$user_id = wp_insert_user( $user_data );

	if ( is_wp_error( $user_id ) ) {
		$response['message'] = 'Error creating user: ' . $user_id->get_error_message();
		$response['error']   = 'Error creating user: ' . $user_id->get_error_message();
		return new WP_REST_Response( $response, 500 );
	}

	unset( $data['email'] );
	unset( $data['password'] );
	unset( $data['username'] );

	$response = ffintegration_update_user_data( $user_id, $data, true );

	// create_user_in_chatapp( $response );.

	return new WP_REST_Response( $response, 201 );
}

/**
 * Retrieves a user by meta key and value.
 *
 * @param string $meta_key The meta key to search for.
 * @param mixed  $meta_value The meta value to match.
 * @param int    $exclude_user_id Optional. User ID to exclude from search results.
 * @return WP_User|false WP_User object if user is found, otherwise false.
 */
function get_user_by_meta( $meta_key, $meta_value, $exclude_user_id = 0 ) {
	// Helper function to get a user by meta key and value.
	$args = array(
		'meta_key'   => $meta_key,
		'meta_value' => $meta_value,
	);

	if ( $exclude_user_id ) {
		$args['exclude'] = array( $exclude_user_id );
	}

	$users = get_users( $args );

	return ! empty( $users ) ? $users[0] : false;
}

/**
 * Retrieves users by meta key and value.
 *
 * @param string $meta_key The meta key to search for.
 * @param mixed  $meta_value The meta value to match.
 * @param int    $exclude_user_id Optional. User ID to exclude from search results.
 * @param string $search Optional. Search string to filter users.
 * @return array An array of WP_User objects matching the search criteria.
 */
function get_users_by_meta( $meta_key, $meta_value, $exclude_user_id = 0, $search = '', $role = '' ) {
	// Helper function to get a user by meta key and value.
	$args = array(
		'meta_key'   => $meta_key,
		'meta_value' => $meta_value,
	);

	if ( $exclude_user_id ) {
		$args['exclude'] = array( $exclude_user_id );
	}

	if ( $search ) {
		$args['search'] = '*' . esc_attr( $search ) . '*';
	}

	if ( $role ) {
		$args['role'] = $role;
	}

	$users = get_users( $args );

	return ! empty( $users ) ? $users : array();
}

/**
 * Handles the login request.
 *
 * @param WP_REST_Request $data The request data.
 * @return WP_REST_Response The REST response.
 */
function ffintegration_login_request_handler( $data ) {
	$response       = array();
	$request_params = $data->get_params();

	$email    = sanitize_email( $request_params['email'] );
	$password = $request_params['password'];

	if ( ! empty( $email ) && ! empty( $password ) ) {
		// Use WordPress authentication functions to check credentials.
		$user = wp_authenticate( $email, $password );

		// Check if authentication was successful.
		if ( ! is_wp_error( $user ) ) {
			$response['message'] = 'Login successfully.';
			$response['user']    = ffintegration_beautify_users_data( $user );
			return new WP_REST_Response( $response, 200 );
		} else {
			// Authentication failed, user not found or invalid credentials.
			$response['message'] = 'Invalid email or password.';
			return new WP_REST_Response( $response, 401 );
		}
	} else {
		$response['message'] = 'User not found.';
		return new WP_REST_Response( $response, 500 );
	}
}

/**
 * Handles the password reset request.
 *
 * @param WP_REST_Request $data The request data.
 * @return WP_REST_Response The REST response.
 */
function ffintegration_password_reset_request_handler( $data ) {
	$response       = array();
	$request_params = $data->get_params();

	$email = sanitize_email( $request_params['email'] );

	// Generate a random code (you can customize this logic).
	$reset_code = wp_generate_password( 6, false );

	// Save the reset code in user metadata.
	$user = get_user_by( 'email', $email );
	if ( $user ) {
		update_user_meta( $user->ID, '_reset_code', $reset_code );

		// Send the reset code to the user's email (customize this part).
		wp_mail( $email, 'Password Reset Code', 'Your reset code: ' . $reset_code );

		$response['message'] = 'Reset code sent successfully.';
		return new WP_REST_Response( $response, 200 );
	} else {
		$response['message'] = 'User not found.';
		return new WP_REST_Response( $response, 500 );
	}
}

/**
 * Handles the password reset confirmation.
 *
 * @param WP_REST_Request $data The request data.
 * @return WP_REST_Response The REST response.
 */
function ffintegration_password_reset_confirm_handler( $data ) {
	$response       = array();
	$request_params = $data->get_params();

	$email        = sanitize_email( $request_params['email'] );
	$reset_code   = sanitize_text_field( $request_params['code'] );
	$new_password = sanitize_text_field( $request_params['new_password'] );

	$user = get_user_by( 'email', $email );

	if ( $user ) {
		$saved_code = get_user_meta( $user->ID, '_reset_code', true );

		if ( $reset_code === $saved_code ) {
			// Code matches, update the password.
			wp_set_password( $new_password, $user->ID );

			// Remove the reset code from user metadata.
			delete_user_meta( $user->ID, '_reset_code' );

			if ( function_exists( 'change_firebase_password_by_email' ) ) {
				change_firebase_password_by_email( $email, $new_password );
			} else {
				$response['message'] = 'erorr there is no function change_firebase_password_by_email';
				return new WP_REST_Response( $response, 500 );
			}

			$response['message'] = 'Password updated successfully.';
			return new WP_REST_Response( $response, 200 );
		} else {
			$response['message'] = 'Invalid reset code.';
			return new WP_REST_Response( $response, 500 );
		}
	} else {
		$response['message'] = 'User not found.';
		return new WP_REST_Response( $response, 500 );
	}
}

/**
 * Retrieves the time zone based on the country code.
 *
 * @param string $country_code The country code.
 * @return string The time zone.
 */
function get_timezone_by_country_code( $country_code ) {
	// Map country codes to time zones.
	$timezones = array(
		'SG' => 'Asia/Singapore',
		'MY' => 'Asia/Kuala_Lumpur',
		'IN' => 'Asia/Kolkata',
		'PH' => 'Asia/Manila',
		'ID' => 'Asia/Jakarta',
	);

	// Check if the country code exists in the mapping.
	if ( isset( $timezones[ $country_code ] ) ) {
		return $timezones[ $country_code ];
	}

	// Default to UTC if no time zone is found for the country code.
	return 'UTC';
}
