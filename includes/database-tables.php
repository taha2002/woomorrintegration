<?php
/**
 * Database table creation for Woomorrintegration plugin.
 *
 * @package WOOMORRINTEGRATION
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Database table creation for Woomorrintegration plugin.
 */
function woomorrintegration_create_tables() {
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();
	$table_version   = '1.1';

	// Check if the table version is installed.
	$installed_db_ver = get_option( 'woomorrintegration_db_version' );

	$table_name_chat = $wpdb->prefix . 'store_chat_messages';
	$sql_chat        = "CREATE TABLE $table_name_chat (
        message_id mediumint(9) NOT NULL AUTO_INCREMENT,
        status VARCHAR(255) NULL,
        message_type VARCHAR(255) NULL,
        sender_id BIGINT NULL,
        receiver_user BIGINT NULL,
        message TEXT NULL,
        replied_to_message_id BIGINT NULL,
        related_to_message_id BIGINT NULL,
        forwarded_from_message_id BIGINT NULL,
        seen_by_users JSON NULL,
        reactions JSON NULL,
        sender_desplay_name VARCHAR(255) NULL,
        attachment_url VARCHAR(255) NULL,
        attachment_name VARCHAR(255) NULL,
        message_opened BOOLEAN NULL,
        message_open_datetime TIMESTAMP NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
        app_name VARCHAR(255) NULL,
        attachment_type VARCHAR(255) NULL,
        data JSON NULL,
        PRIMARY KEY (message_id)
    ) $charset_collate;";

	$table_name_voucher = $wpdb->prefix . 'inventory_voucher';
	$sql_voucher        = "CREATE TABLE $table_name_voucher (
        inventory_voucher_id BIGINT NOT NULL AUTO_INCREMENT,
        datetime TIMESTAMP NULL DEFAULT NULL,
        voucher_group TEXT NULL,
        voucher_category TEXT NULL,
        voucher_narration TEXT NULL,
        voucher_number TEXT NULL,
        ref_voucher_no TEXT NULL,
        for_voucher_no TEXT NULL,
        voucher_status TEXT NULL,
        ref_order_no TEXT NULL,
        for_order_no TEXT NULL,
        order_date TIMESTAMP NULL DEFAULT NULL,
        purchase_order_no TEXT NULL,
        ref_purchase_order_no TEXT NULL,
        purchase_order_date TIMESTAMP NULL DEFAULT NULL,
        sales_channel_type TEXT NULL,
        returnable_status TEXT NULL,
        from_location TEXT NULL,
        to_location TEXT NULL,
        from_storage_area TEXT NULL,
        to_storage_area TEXT NULL,
        from_datetime TIMESTAMP NULL DEFAULT NULL,
        to_datetime TIMESTAMP NULL DEFAULT NULL,
        from_business_number TEXT NULL,
        from_business_name TEXT NULL,
        to_business_number TEXT NULL,
        to_business_name TEXT NULL,
        for_business_number TEXT NULL,
        for_business_name TEXT NULL,
        business_number TEXT NULL,
        business_name TEXT NULL,
        from_user_id BIGINT NULL,
        from_user_name TEXT NULL,
        to_user_id BIGINT NULL,
        to_user_name TEXT NULL,
        meta_fields TEXT NULL,
        remarks TEXT NULL,
        store_meta TEXT NULL,
        workflow_meta TEXT NULL,
        share_url TEXT NULL,
        share_status TEXT NULL,
        created_date TIMESTAMP NULL DEFAULT NULL,
        created_user_id BIGINT NULL,
        PRIMARY KEY (inventory_voucher_id)
    ) $charset_collate;";

	$table_name_voucher_detail = $wpdb->prefix . 'inventory_voucher_detail';
	$sql_voucher_detail        = "CREATE TABLE $table_name_voucher_detail (
        inventory_voucher_detail_id BIGINT NOT NULL AUTO_INCREMENT,
        inventory_voucher_id BIGINT NULL,
        detail_serial TEXT NULL,
        product_number TEXT NULL,
        product_name TEXT NULL,
        supplier_code TEXT NULL,
        serial_no TEXT NULL,
        description TEXT NULL,
        short_description TEXT NULL,
        product_image TEXT NULL,
        sku TEXT NULL,
        qty NUMERIC NULL,
        uom TEXT NULL,
        in_qty NUMERIC NULL,
        out_qty NUMERIC NULL,
        difference_qty NUMERIC NULL,
        price NUMERIC NULL,
        amount NUMERIC NULL,
        tax_status TEXT NULL,
        tax_class TEXT NULL,
        tax_name TEXT NULL,
        tax_rate NUMERIC NULL,
        tax_amount NUMERIC NULL,
        shipping_class TEXT NULL,
        shipper_name TEXT NULL,
        shipment_name TEXT NULL,
        shipment_rate NUMERIC NULL,
        shipment_amount NUMERIC NULL,
        discount_name TEXT NULL,
        discount_rate NUMERIC NULL,
        discount_amount NUMERIC NULL,
        sale_price NUMERIC NULL,
        regular_price NUMERIC NULL,
        cogm_price NUMERIC NULL,
        cogs_price NUMERIC NULL,
        fifo_price NUMERIC NULL,
        lifo_price NUMERIC NULL,
        landing_cost NUMERIC NULL,
        average_price NUMERIC NULL,
        purchase_note TEXT NULL,
        customer_note TEXT NULL,
        weight NUMERIC NULL,
        length NUMERIC NULL,
        width NUMERIC NULL,
        height NUMERIC NULL,
        images TEXT NULL,
        mfg_batch_number TEXT NULL,
        mfg_serial_number TEXT NULL,
        date_of_mfg TIMESTAMP NULL DEFAULT NULL,
        date_of_expiry TIMESTAMP NULL DEFAULT NULL,
        mfg_name TEXT NULL,
        bar_code TEXT NULL,
        rfid_tag TEXT NULL,
        remarks TEXT NULL,
        ledger_code TEXT NULL,
        ledger_name TEXT NULL,
        meta_fields TEXT NULL,
        store_meta TEXT NULL,
        workflow_meta TEXT NULL,
        created_date TIMESTAMP NULL DEFAULT NULL,
        created_user_id BIGINT NULL,
        PRIMARY KEY (inventory_voucher_detail_id)
    ) $charset_collate;";

	$table_name_campaign = $wpdb->prefix . 'campaigns';
	$sql_campaign        = "CREATE TABLE $table_name_campaign (
        campaign_id BIGINT NOT NULL AUTO_INCREMENT,
        campaign_name TEXT NULL,
        campaign_code TEXT NULL,
        status TEXT NULL,
        description TEXT NULL,
        icon TEXT NULL,
        campaign_type TEXT NULL,
        campaign_start_date TIMESTAMP NULL DEFAULT NULL,
        campaign_end_date TIMESTAMP NULL DEFAULT NULL,
        campaign_objective TEXT NULL,
        number_of_emails_sent BIGINT NULL,
        number_of_clicks BIGINT NULL,
        number_of_conversions BIGINT NULL,
        conversion_rate NUMERIC NULL,
        revenue_generated NUMERIC NULL,
        custom_one TEXT NULL,
        custom_two TEXT NULL,
        custom_three TEXT NULL,
        currency TEXT NULL,
        financial_year TEXT NULL,
        financial_period TEXT NULL,
        meta_fields TEXT NULL,
        remarks TEXT NULL,
        store_meta TEXT NULL,
        coupon_meta TEXT NULL,
        workflow_meta TEXT NULL,
        share_url TEXT NULL,
        share_status TEXT NULL,
        business_name TEXT NULL,
        business_number TEXT NULL,
        ref_business TEXT NULL,
        ref_business_number TEXT NULL,
        ref_user TEXT NULL,
        ref_appname TEXT NULL,
        ref_datetime TIMESTAMP NULL DEFAULT NULL,
        social_login_used TEXT NULL,
        created_user TEXT NULL,
        created_userid BIGINT NULL,
        created_datetime TIMESTAMP NULL DEFAULT NULL,
        app_name TEXT NULL,
        PRIMARY KEY (campaign_id)
    ) $charset_collate;";

	$table_name_campaign_offer = $wpdb->prefix . 'campaign_offer';
	$sql_campaign_offer        = "CREATE TABLE $table_name_campaign_offer (
        campaign_offer_id BIGINT NOT NULL AUTO_INCREMENT,
        status TEXT NULL,
        description TEXT NULL,
        icon TEXT NULL,
        campaign_id BIGINT NULL,
        campaign_name TEXT NULL,
        campaign_code TEXT NULL,
        offer_name TEXT NULL,
        offer_code TEXT NULL,
        offer_description TEXT NULL,
        offer_enddate TIMESTAMP NULL DEFAULT NULL,
        offer_type TEXT NULL,
        offer_start_date TIMESTAMP NULL DEFAULT NULL,
        offer_end_date TIMESTAMP NULL DEFAULT NULL,
        offer_redemption_status TEXT NULL,
        offer_redemption_date TIMESTAMP NULL DEFAULT NULL,
        offer_value NUMERIC NULL,
        offer_usage_frequency NUMERIC NULL,
        offer_satisfaction_rating NUMERIC NULL,
        offer_redemption_rate NUMERIC NULL,
        custom_one TEXT NULL,
        custom_two TEXT NULL,
        custom_three TEXT NULL,
        currency TEXT NULL,
        financial_year TEXT NULL,
        financial_period TEXT NULL,
        meta_fields TEXT NULL,
        remarks TEXT NULL,
        store_meta TEXT NULL,
        workflow_meta TEXT NULL,
        share_url TEXT NULL,
        share_status TEXT NULL,
        business_name TEXT NULL,
        business_number TEXT NULL,
        ref_business TEXT NULL,
        ref_business_number TEXT NULL,
        ref_user TEXT NULL,
        ref_appname TEXT NULL,
        ref_datetime TIMESTAMP NULL DEFAULT NULL,
        social_login_used TEXT NULL,
        created_user TEXT NULL,
        created_userid BIGINT NULL,
        created_datetime TIMESTAMP NULL DEFAULT NULL,
        app_name TEXT NULL,
        PRIMARY KEY (campaign_offer_id)
    ) $charset_collate;";

	$table_name_campaign_tracking = $wpdb->prefix . 'campaign_tracking';
	$sql_campaign_tracking        = "CREATE TABLE $table_name_campaign_tracking (
        campaign_tracking_id BIGINT NOT NULL AUTO_INCREMENT,
        campaign_id BIGINT NULL,
        campaign_offer_id BIGINT NULL,
        campaign_offer_user_id BIGINT NULL,
        user_id BIGINT NULL,
        offer_type TEXT NULL,
        offer_name TEXT NULL,
        offer_code TEXT NULL,
        status TEXT NULL,
        description TEXT NULL,
        icon TEXT NULL,
        event_type TEXT NULL,
        event_timestamp TIMESTAMP NULL DEFAULT NULL,
        event_details TEXT NULL,
        custom_one TEXT NULL,
        custom_two TEXT NULL,
        custom_three TEXT NULL,
        currency TEXT NULL,
        financial_year TEXT NULL,
        financial_period TEXT NULL,
        event_meta TEXT NULL,
        open_meta TEXT NULL,
        meta_fields TEXT NULL,
        remarks TEXT NULL,
        store_meta TEXT NULL,
        workflow_meta TEXT NULL,
        share_url TEXT NULL,
        share_status TEXT NULL,
        business_name TEXT NULL,
        business_number TEXT NULL,
        ref_business TEXT NULL,
        ref_business_number TEXT NULL,
        ref_user TEXT NULL,
        ref_appname TEXT NULL,
        ref_datetime TIMESTAMP NULL DEFAULT NULL,
        social_login_used TEXT NULL,
        created_user TEXT NULL,
        created_userid BIGINT NULL,
        created_datetime TIMESTAMP NULL DEFAULT NULL,
        app_name TEXT NULL,
        PRIMARY KEY (campaign_tracking_id)
    ) $charset_collate;";

	$table_name_campaign_user_offer = $wpdb->prefix . 'campaign_user_offer';
	$sql_campaign_user_offer        = "CREATE TABLE $table_name_campaign_user_offer (
        campaign_user_offer_id BIGINT NOT NULL AUTO_INCREMENT,
        campaign_offer_id BIGINT NULL,
        status TEXT NULL,
        open_status TEXT NULL,
        description TEXT NULL,
        icon TEXT NULL,
        user_id BIGINT NULL,
        user_name TEXT NULL,
        user_email TEXT NULL,
        user_mobile TEXT NULL,
        to_business_name TEXT NULL,
        to_business_no TEXT NULL,
        for_business_name TEXT NULL,
        for_business_no TEXT NULL,
        user_fullname TEXT NULL,
        user_meta TEXT NULL,
        user_social_meta TEXT NULL,
        campaign_id BIGINT NULL,
        campaign_type TEXT NULL,
        campaign_name TEXT NULL,
        campaign_code TEXT NULL,
        campaign_url TEXT NULL,
        campaign_start TIMESTAMP NULL DEFAULT NULL,
        campaign_end TIMESTAMP NULL DEFAULT NULL,
        campaign_status TEXT NULL,
        offer_name TEXT NULL,
        offer_code TEXT NULL,
        offer_description TEXT NULL,
        offer_url TEXT NULL,
        offer_status TEXT NULL,
        offer_type TEXT NULL,
        offer_start_date TIMESTAMP NULL DEFAULT NULL,
        offer_end_date TIMESTAMP NULL DEFAULT NULL,
        offer_redemption_status TEXT NULL,
        offer_redemption_date TIMESTAMP NULL DEFAULT NULL,
        offer_percent NUMERIC NULL,
        offer_value NUMERIC NULL,
        offer_amount NUMERIC NULL,
        offer_opens BIGINT NULL,
        offer_share_count BIGINT NULL,
        offer_share_meta TEXT NULL,
        custom_one TEXT NULL,
        custom_two TEXT NULL,
        custom_three TEXT NULL,
        currency TEXT NULL,
        financial_year TEXT NULL,
        financial_period TEXT NULL,
        event_meta TEXT NULL,
        open_meta TEXT NULL,
        meta_fields TEXT NULL,
        remarks TEXT NULL,
        store_meta TEXT NULL,
        workflow_meta TEXT NULL,
        share_url TEXT NULL,
        share_status TEXT NULL,
        business_name TEXT NULL,
        business_number TEXT NULL,
        ref_business TEXT NULL,
        ref_business_number TEXT NULL,
        ref_user TEXT NULL,
        ref_appname TEXT NULL,
        ref_datetime TIMESTAMP NULL DEFAULT NULL,
        social_login_used TEXT NULL,
        created_user TEXT NULL,
        created_userid BIGINT NULL,
        created_datetime TIMESTAMP NULL DEFAULT NULL,
        app_name TEXT NULL,
        PRIMARY KEY (campaign_user_offer_id)
    ) $charset_collate;";

	$table_name_user_log = $wpdb->prefix . 'user_log';
	$sql_user_log        = "CREATE TABLE $table_name_user_log (
        user_log_id BIGINT NOT NULL AUTO_INCREMENT,
        status TEXT NULL,
        description TEXT NULL,
        icon TEXT NULL,
        user_id BIGINT NULL,
        user_name TEXT NULL,
        user_email TEXT NULL,
        full_name TEXT NULL,
        user_mobile TEXT NULL,
        telemetry_log_id BIGINT NULL,
        session_id TEXT NULL,
        session_meta TEXT NULL,
        event_type TEXT NULL,
        event_details TEXT NULL,
        user_agent TEXT NULL,
        event_meta TEXT NULL,
        geo_meta TEXT NULL,
        social_meta TEXT NULL,
        share_meta TEXT NULL,
        chat_meta TEXT NULL,
        document_meta TEXT NULL,
        user_ip_address TEXT NULL,
        alert_message TEXT NULL,
        risk_message TEXT NULL,
        risk_message_meta TEXT NULL,
        message_to_user TEXT NULL,
        message_to_group TEXT NULL,
        for_business_name TEXT NULL,
        for_business_number TEXT NULL,
        zip_code TEXT NULL,
        browser TEXT NULL,
        device TEXT NULL,
        role TEXT NULL,
        city TEXT NULL,
        state TEXT NULL,
        country TEXT NULL,
        geo_codes TEXT NULL,
        geo_location TEXT NULL,
        http_method TEXT NULL,
        http_url TEXT NULL,
        request_headers TEXT NULL,
        request_payload TEXT NULL,
        operating_system TEXT NULL,
        response_status_code INT NULL,
        response_time_ms BIGINT NULL,
        response_headers TEXT NULL,
        response_payload TEXT NULL,
        response_status TEXT NULL,
        response_duration BIGINT NULL,
        response_error TEXT NULL,
        error_message TEXT NULL,
        error_alert_meta TEXT NULL,
        exception_stacktrace TEXT NULL,
        host_header TEXT NULL,
        request_to_ip TEXT NULL,
        custom_one TEXT NULL,
        custom_two TEXT NULL,
        custom_three TEXT NULL,
        currency TEXT NULL,
        financial_year TEXT NULL,
        financial_period TEXT NULL,
        meta_fields TEXT NULL,
        remarks TEXT NULL,
        store_meta TEXT NULL,
        workflow_meta TEXT NULL,
        share_url TEXT NULL,
        share_status TEXT NULL,
        business_name TEXT NULL,
        business_number TEXT NULL,
        ref_business TEXT NULL,
        ref_business_number TEXT NULL,
        ref_user TEXT NULL,
        ref_appname TEXT NULL,
        ref_datetime TIMESTAMP NULL DEFAULT NULL,
        social_login_used TEXT NULL,
        created_user TEXT NULL,
        created_userid BIGINT NULL,
        created_datetime TIMESTAMP NULL DEFAULT NULL,
        created_at_geo TEXT NULL,
        app_name TEXT NULL,
        PRIMARY KEY (user_log_id)
    ) $charset_collate;";

	// Include WordPress upgrade functions.
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	// Execute the SQL for each table.
	dbDelta( $sql_chat );
	dbDelta( $sql_voucher );
	dbDelta( $sql_voucher_detail );
	dbDelta( $sql_campaign );
	dbDelta( $sql_campaign_offer );
	dbDelta( $sql_campaign_tracking );
	dbDelta( $sql_campaign_user_offer );
	dbDelta( $sql_user_log );

	// Update the database version option.
	add_option( 'woomorrintegration_db_version', $table_version );
}
