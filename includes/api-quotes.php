<?php
/**
 * REST API for Store Quotes.
 *
 * @package WOOMORRINTEGRATION
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Make sure your Woomorr_Query class is loaded before this file.
// require_once __DIR__ . '/woomorr-query.php';

/**
 * Controller for the /quotes REST API endpoint.
 */
class Woomorr_Quotes_API_Controller {

	/** @var wpdb The WordPress database instance. */
	protected $wpdb;

	/** @var string The namespace for the REST API. */
	protected $namespace = 'woomorrintegration/v1';

	/** @var string The base for the REST API route. */
	protected $rest_base = 'quotes';
    
    /** @var string The name of the quotes table. */
    protected $table_quotes;

    /** @var string The name of the quote products table. */
	protected $table_quote_products;

	/** @var array Whitelist of fields for the quotes table. */
	protected $allowed_quote_fields = array(
		'quote_id', 'quote_code','quote_ref_code', 'customer_id', 'supplier_id',
		'from_user_id','from_user_mobile','to_user_id','to_user_mobile',
        'customer_key', 'supplier_key',
		'for_business_number', 'for_business_name', 'quote_status',
		'grand_total', 'wc_order_id', 'created_at', 'expires_at', 'created_by'
	);

	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
        $this->table_quotes = $this->wpdb->prefix . 'store_quotes';
        $this->table_quote_products = $this->wpdb->prefix . 'store_quote_products';

		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, [
			[
				'methods'             => WP_REST_Server::READABLE, // GET
				'callback'            => [ $this, 'get_items' ],
				'permission_callback' => [ $this, 'permission_check' ],
			],
			[
				'methods'             => WP_REST_Server::CREATABLE, // POST
				'callback'            => [ $this, 'create_item' ],
				'permission_callback' => [ $this, 'permission_check' ],
			],
		] );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
			'args' => [
				'id' => [
					'description' => 'Unique identifier for the quote.',
					'type'        => 'integer',
				],
			],
			[
				'methods'             => WP_REST_Server::READABLE, // GET
				'callback'            => [ $this, 'get_item' ],
				'permission_callback' => [ $this, 'permission_check' ],
			],
			[
				'methods'             => WP_REST_Server::EDITABLE, // PUT, PATCH
				'callback'            => [ $this, 'update_item' ],
				'permission_callback' => [ $this, 'permission_check' ],
			],
			[
				'methods'             => WP_REST_Server::DELETABLE, // DELETE
				'callback'            => [ $this, 'delete_item' ],
				'permission_callback' => [ $this, 'permission_check' ],
			],
		] );
	}

	/**
	 * Check if a given request has access.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return bool|WP_Error
	 */
	public function permission_check( $request ) {
		// This is a basic API key check. You could also implement
		// current_user_can('manage_options') for logged-in admin users.
		$api_key      = get_option( 'woomorrintegration_api_secret_key' );
		$provided_key = $request->get_header( 'auth' );
		if ( ! $api_key || $provided_key !== $api_key ) {
			return new WP_Error( 'rest_forbidden', 'Invalid API key.', [ 'status' => 401 ] );
		}
		return true;
	}

	/**
	 * Retrieve a list of quotes. (Read/GET)
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		$params = $request->get_params();

		// Use our brilliant Query Builder!
		$query_builder = new Woomorr_Query( 'store_quotes', $this->allowed_quote_fields );

		// Filtering.
		if ( ! empty( $params['status'] ) ) {
			$query_builder->where( 'quote_status', sanitize_text_field( $params['status'] ) );
		}
		if ( ! empty( $params['customer_id'] ) ) {
			$query_builder->where( 'customer_id', absint( $params['customer_id'] ) );
		}
		if ( ! empty( $params['customer_key'] ) ) {
			$query_builder->where( 'customer_key', sanitize_text_field( $params['customer_key'] ) );
		}
		if ( ! empty( $params['supplier_key'] ) ) {
			$query_builder->where( 'supplier_key', sanitize_text_field( $params['supplier_key'] ) );
		}

		if ( ! empty( $params['business_number'] ) ) {
			$query_builder->where( 'for_business_number', sanitize_text_field( $params['business_number'] ) );
		}

		// Search.
		if ( ! empty( $params['search'] ) ) {
			// $query_builder->like( 'quote_code', $params['search'] );
			$search_fields = array(
				'quote_code',
				'quote_ref_code',
				'customer_key',
				'supplier_key',
				'for_business_name',
				'note',
				'remarks',
			);
			$query_builder->search( $search_fields, $params['search'] );
		}

		// Pagination.
		$page     = ! empty( $params['page'] ) ? absint( $params['page'] ) : 1;
		$per_page = ! empty( $params['per_page'] ) ? absint( $params['per_page'] ) : 10;
		$query_builder->paginate( $page, $per_page );

		// Ordering.
		$orderby = ! empty( $params['orderby'] ) ? $params['orderby'] : 'created_at';
		$order   = ! empty( $params['order'] ) ? $params['order'] : 'DESC';
		$query_builder->order_by( $orderby, $order );

		$results = $query_builder->get();

		// ======================================================================
		// Fetch line items for the retrieved quotes.
		// ======================================================================

		// Get an array of just the quote IDs from the first query's results.
		$quote_ids = wp_list_pluck( $results['data'], 'quote_id' );

		// Initialize line_items array for each quote and parse status_history.
		foreach ( $results['data'] as $key => $quote ) {
			$results['data'][ $key ]->line_items = array();

			$history = json_decode( $results['data'][ $key ]->status_history, true );
			if ( ! is_array( $history ) ) {
				$history = array();
			}
			$results['data'][ $key ]->status_history = $history;
		}

		// Only run the second query if we actually have quotes.
		if ( ! empty( $quote_ids ) ) {
			// Prepare the IN clause for the SQL query.
			$id_placeholders = implode( ', ', array_fill( 0, count( $quote_ids ), '%d' ) );

			// Fetch all line items for all the quotes in a single query.
			$line_items_query = $this->wpdb->prepare(
				"SELECT * FROM {$this->table_quote_products} WHERE store_quote_id IN ( {$id_placeholders} )",
				$quote_ids
			);
			$all_line_items = $this->wpdb->get_results( $line_items_query );

			// Group the line items by their parent quote ID.
			$line_items_by_quote_id = [];
			foreach ( $all_line_items as $item ) {
				$line_items_by_quote_id[ $item->store_quote_id ][] = $item;
			}

			// Map the grouped line items back to the main quote results.
			foreach ( $results['data'] as $key => $quote ) {
				if ( isset( $line_items_by_quote_id[ $quote->quote_id ] ) ) {
					$results['data'][ $key ]->line_items = $line_items_by_quote_id[ $quote->quote_id ];
				}
			}
		}

		$response = new WP_REST_Response( $results['data'], 200 );
		$response->header( 'X-WP-Total', $results['total'] );
		$response->header( 'X-WP-TotalPages', $results['total_pages'] );

		return $response;
	}

	/**
	 * Retrieve a single quote. (Read/GET by ID)
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$id = (int) $request['id'];
		$quote = $this->get_quote_by_id( $id );

		if ( ! $quote ) {
			return new WP_Error( 'rest_not_found', 'Quote not found.', array( 'status' => 404 ) );
		}

		return new WP_REST_Response( $quote, 200 );
	}

	/**
	 * Create a new quote. (Create/POST)
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		$body = $request->get_json_params();

		$quote_data = $this->prepare_item_for_db( $body );
		$line_items = isset( $body['line_items'] ) && is_array( $body['line_items'] ) ? $body['line_items'] : [];

		if ( empty( $body['customer_key'] ) || empty( $body['supplier_key'] ) ) {
			// return new WP_Error( 'rest_missing_param', 'Missing required fields: customer_key, supplier_key.', [ 'status' => 400 ] );
		}

		// Generate a unique quote code.
		$quote_data['quote_code'] = 'Q-' . time() . '-' . wp_rand( 100, 999 );
		$quote_data['created_by'] = $body['created_by'] ? $body['created_by'] : get_current_user_id(); // Or another source.

		$user_info       = get_userdata( $quote_data['created_by'] );
		$initial_history = array(
			array(
				'quote_status'    => $quote_data['quote_status'] ?? 'draft',
				'note'            => 'Quote created.',
				'changed_by_id'   => $quote_data['created_by'],
				'changed_by_name' => $user_info ? $user_info->display_name : 'System',
				'created_at'      => gmdate( 'Y-m-d H:i:s' ),
			),
		);
		// Encode the history array into a JSON string for the database.
		$quote_data['status_history'] = wp_json_encode( $initial_history );


		$this->wpdb->query( 'START TRANSACTION' );

		$inserted = $this->wpdb->insert( $this->table_quotes, $quote_data );

		if ( ! $inserted ) {
			$this->wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'rest_db_error', 'Failed to create quote.', array( 'status' => 500, 'db_error' => $this->wpdb->last_error ) );
		}

		$quote_id = $this->wpdb->insert_id;

		// Insert line items.
		foreach ( $line_items as $item ) {
			$item_data = $this->prepare_line_item_for_db( $item, $quote_id );
			$item_inserted = $this->wpdb->insert( $this->table_quote_products, $item_data );
			if ( ! $item_inserted ) {
				$this->wpdb->query( 'ROLLBACK' );
				return new WP_Error( 'rest_db_error', 'Failed to create quote line items.', [ 'status' => 500, 'db_error' => $this->wpdb->last_error ] );
			}
		}

		$this->wpdb->query( 'COMMIT' );

		$new_quote = $this->get_quote_by_id( $quote_id );
		return new WP_REST_Response( $new_quote, 201 ); // 201 Created
	}

	/**
	 * Update an existing quote. (Update/PUT)
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		$id = (int) $request['id'];
		$existing_quote = $this->get_quote_by_id( $id, false );
		if ( ! $existing_quote ) {
			return new WP_Error( 'rest_not_found', 'Quote not found to update.', array( 'status' => 404 ) );
		}

		$body = $request->get_json_params();
		$quote_data = $this->prepare_item_for_db( $body, $existing_quote );
		$line_items = isset( $body['line_items'] ) && is_array( $body['line_items'] ) ? $body['line_items'] : array();

		// --- NEW: Logic to append to the JSON history field ---.
		// Decode existing history to start with.
		$history =  $existing_quote['status_history'];
		if ( ! is_array( $history ) ) {
			$history = array();
		}

		$new_status = $quote_data['quote_status'] ?? $existing_quote['quote_status'];

		// Check if the status has actually changed.
		if ( $new_status !== $existing_quote['quote_status'] ) {
			$user_id   = get_current_user_id();
			$user_info = get_userdata( $user_id );

			// Create and append the new history entry.
			$history[] = array(
				'quote_status'    => $new_status,
				'note'            => $body['status_change_note'] ?? null,
				'changed_by_id'   => $user_id,
				'changed_by_name' => $user_info ? $user_info->display_name : 'System',
				'created_at'      => gmdate( 'Y-m-d H:i:s' ),
			);
		}

		$quote_data['status_history'] = wp_json_encode( $history );

		$this->wpdb->query( 'START TRANSACTION' );

		// Update main quote.
		$updated = $this->wpdb->update( $this->table_quotes, $quote_data, array( 'quote_id' => $id ) );
		if ( false === $updated ) {
			$this->wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'rest_db_error', 'Failed to update quote.', array( 'status' => 500, 'db_error' => $this->wpdb->last_error ) );
		}

		// Replace line items (easiest and safest approach).
		$this->wpdb->delete( $this->table_quote_products, array( 'store_quote_id' => $id ) );
		foreach ( $line_items as $item ) {
			$item_data = $this->prepare_line_item_for_db( $item, $id );
			$item_inserted = $this->wpdb->insert( $this->table_quote_products, $item_data );
			if ( ! $item_inserted ) {
				$this->wpdb->query( 'ROLLBACK' );
				return new WP_Error( 'rest_db_error', 'Failed to update quote line items.', array( 'status' => 500, 'db_error' => $this->wpdb->last_error ) );
			}
		}

		$this->wpdb->query( 'COMMIT' );

		$updated_quote = $this->get_quote_by_id( $id );
		return new WP_REST_Response( $updated_quote, 200 );
	}

	/**
	 * Delete a quote. (Delete/DELETE)
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$id = (int) $request['id'];
		$quote_to_delete = $this->get_quote_by_id( $id );
		if ( ! $quote_to_delete ) {
			return new WP_Error( 'rest_not_found', 'Quote not found to delete.', [ 'status' => 404 ] );
		}

		$this->wpdb->query( 'START TRANSACTION' );

		// Delete line items first.
		$this->wpdb->delete( $this->table_quote_products, [ 'store_quote_id' => $id ] );

		// Delete main quote.
		$deleted = $this->wpdb->delete( $this->table_quotes, [ 'quote_id' => $id ] );
		if ( ! $deleted ) {
			$this->wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'rest_db_error', 'Failed to delete quote.', [ 'status' => 500, 'db_error' => $this->wpdb->last_error ] );
		}

		$this->wpdb->query( 'COMMIT' );

		return new WP_REST_Response( [ 'deleted' => true, 'previous' => $quote_to_delete ], 200 );
	}

	// --- Helper Functions ---

	/**
	 * Helper to get a full quote with line items by its ID.
	 */
	protected function get_quote_by_id( $id, $include_line_items = true ) {
		$quote = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->table_quotes} WHERE quote_id = %d", $id ),
			ARRAY_A
		);

		if ( $quote ) {
			$quote['status_history'] = json_decode( $quote['status_history'], true );
			// Ensure it's always an array in the response, even if null in the DB.
			if ( ! is_array( $quote['status_history'] ) ) {
				$quote['status_history'] = array();
			}

			if ( $include_line_items ) {
				$quote['line_items'] = $this->wpdb->get_results(
					$this->wpdb->prepare( "SELECT * FROM {$this->table_quote_products} WHERE store_quote_id = %d", $id ),
					ARRAY_A
				);
			}
		}
		return $quote;
	}

	/**
	 * Sanitize and prepare main quote data for database insertion/update.
	 */
	protected function prepare_item_for_db( $data, $existing_data = [] ) {
		$prepared_data = array(
			'customer_key'        => isset( $data['customer_key'] ) ? sanitize_text_field( $data['customer_key'] ) : $existing_data['customer_key'] ?? null,
			'supplier_key'        => isset( $data['supplier_key'] ) ? sanitize_text_field( $data['supplier_key'] ) : $existing_data['supplier_key'] ?? null,
			'customer_id'         => isset( $data['customer_id'] ) ? absint( $data['customer_id'] ) : $existing_data['customer_id'] ?? null,
			'supplier_id'         => isset( $data['supplier_id'] ) ? absint( $data['supplier_id'] ) : $existing_data['supplier_id'] ?? null,

			'from_user_mobile'        => isset( $data['from_user_mobile'] ) ? sanitize_text_field( $data['from_user_mobile'] ) : $existing_data['from_user_mobile'] ?? null,
			'from_user_id'         => isset( $data['from_user_id'] ) ? absint( $data['from_user_id'] ) : $existing_data['from_user_id'] ?? null,

			'to_user_mobile'        => isset( $data['to_user_mobile'] ) ? sanitize_text_field( $data['to_user_mobile'] ) : $existing_data['to_user_mobile'] ?? null,
			'to_user_id'         => isset( $data['to_user_id'] ) ? absint( $data['to_user_id'] ) : $existing_data['to_user_id'] ?? null,

			'quote_ref_code'        => isset( $data['quote_ref_code'] ) ? sanitize_text_field( $data['quote_ref_code'] ) : $existing_data['quote_ref_code'] ?? null,

			'for_business_number' => isset( $data['for_business_number'] ) ? sanitize_text_field( $data['for_business_number'] ) : $existing_data['for_business_number'] ?? null,
			'for_business_name'   => isset( $data['for_business_name'] ) ? sanitize_text_field( $data['for_business_name'] ) : $existing_data['for_business_name'] ?? null,
			'quote_status'        => isset( $data['quote_status'] ) ? sanitize_key( $data['quote_status'] ) : $existing_data['quote_status'] ?? 'draft',
			'subtotal'            => isset( $data['subtotal'] ) ? wc_format_decimal( $data['subtotal'] ) : $existing_data['subtotal'] ?? 0,
			'tax_total'           => isset( $data['tax_total'] ) ? wc_format_decimal( $data['tax_total'] ) : $existing_data['tax_total'] ?? 0,
			'grand_total'         => isset( $data['grand_total'] ) ? wc_format_decimal( $data['grand_total'] ) : $existing_data['grand_total'] ?? 0,
			'wc_order_id'         => isset( $data['wc_order_id'] ) ? absint( $data['wc_order_id'] ) : $existing_data['wc_order_id'] ?? null,
			'note'                => isset( $data['note'] ) ? sanitize_textarea_field( $data['note'] ) : $existing_data['note'] ?? null,
			'remarks'             => isset( $data['remarks'] ) ? sanitize_textarea_field( $data['remarks'] ) : $existing_data['remarks'] ?? null,
			'terms_of_supply'     => isset( $data['terms_of_supply'] ) ? wp_kses_post( $data['terms_of_supply'] ) : $existing_data['terms_of_supply'] ?? null,
			'expires_at'          => isset( $data['expires_at'] ) ? gmdate( 'Y-m-d H:i:s', strtotime( $data['expires_at'] ) ) : $existing_data['expires_at'] ?? null,
			'created_by'          => isset( $data['created_by'] ) ? sanitize_text_field( $data['created_by'] ) : $existing_data['created_by'] ?? null,
		);

		// NEW: Automatically find and set internal WordPress user IDs.
        if ( ! empty( $prepared_data['customer_key'] ) ) {
            // $prepared_data['customer_id'] = $this->get_user_id_from_phone_key( $prepared_data['customer_key'] );
        }
        if ( ! empty( $prepared_data['supplier_key'] ) ) {
            // $prepared_data['supplier_id'] = $this->get_user_id_from_phone_key( $prepared_data['supplier_key'] );
        }

		return $prepared_data;
	}

	/**
	 * Sanitize and prepare line item data for database insertion.
	 */
	protected function prepare_line_item_for_db( $item, $quote_id ) {
		return [
			'store_quote_id'         => $quote_id,
			'product_id'             => isset( $item['product_id'] ) ? absint( $item['product_id'] ) : 0,
			'product_name'           => isset( $item['product_name'] ) ? sanitize_text_field( $item['product_name'] ) : '',
			'quantity'               => isset( $item['quantity'] ) ? wc_format_decimal( $item['quantity'] ) : 1,
			'unit_price'             => isset( $item['unit_price'] ) ? wc_format_decimal( $item['unit_price'] ) : 0,
			'line_subtotal'          => isset( $item['line_subtotal'] ) ? wc_format_decimal( $item['line_subtotal'] ) : 0,
			'line_tax'               => isset( $item['line_tax'] ) ? wc_format_decimal( $item['line_tax'] ) : 0,
			'line_total'             => isset( $item['line_total'] ) ? wc_format_decimal( $item['line_total'] ) : 0,
			'product_note'           => isset( $item['product_note'] ) ? sanitize_textarea_field( $item['product_note'] ) : null,
			'product_remarks'        => isset( $item['product_remarks'] ) ? sanitize_textarea_field( $item['product_remarks'] ) : null,
			'product_supply_remarks' => isset( $item['product_supply_remarks'] ) ? sanitize_textarea_field( $item['product_supply_remarks'] ) : null,
		];
	}

	/**
	 * NEW: Helper to find a WordPress User ID from a phone number meta key.
	 *
	 * @param string $phone The phone number to search for.
	 * @return int|null The user ID if found, otherwise null.
	 */
	protected function get_user_id_from_phone_key( $phone ) {
		if ( empty( $phone ) ) {
			return null;
		}

		// Assumes the phone number is stored in the 'billing_phone' meta key.
		// Change 'billing_phone' if you use a different meta key.
		$users = get_users(
			array(
				'meta_key'   => 'billing_phone',
				'meta_value' => sanitize_text_field( $phone ),
				'number'     => 1,
				'fields'     => 'ID',
			)
		);

		return ! empty( $users ) ? (int) $users[0] : null;
	}
}

// Instantiate the controller to hook everything up.
new Woomorr_Quotes_API_Controller();
