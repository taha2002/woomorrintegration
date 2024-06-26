<?php
/**
 * Plugin Name:       Woomorrintegration
 * Plugin URI:        https://morr.biz/
 * Description:       Add WooCommmerce integration to morr.
 * Version:           1.0.29
 * Author:            Taha Bou
 * Author URI:        http://taha2002.github.io/
 * Text Domain:       WOOMORRINTEGRATION
 *
 * @link              https://morr.biz/
 * @package           WOOMORRINTEGRATION
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


// Define a global array to store the filters
global $ran_filters;
$ran_filters = array();


// add_filter(
// 'all',
// function ( $tag ) {
// global $ran_filters;
// $ran_filters[] = $tag;
// return $tag;
// }
// );

// add_action(
// 'shutdown',
// function () {
// global $ran_filters;

// if ( ! empty( $ran_filters ) ) {
// echo '<pre>';
// echo 'Filters that have been run:';
// echo '<ul>';
// foreach ( $ran_filters as $filter ) {
// echo '<li>' . esc_html( $filter ) . '</li>';
// }
// echo '</ul>';
// echo '</pre>';
// }
// }
// );

// Define constants.
define( 'WOOMORRINTEGRATION_VERSION', '1.0.2' );
define( 'WOOMORRINTEGRATION_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WOOMORRINTEGRATION_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WOOMORRINTEGRATION_UPDATE_URL', 'https://raw.githubusercontent.com/taha2002/woomorrintegration/main/plugin-woomorrintegration.json' );
define( 'WOOMORRINTEGRATION_CACHE_KEY', 'wpwoomorrintegration_updater' );


// Register activation and deactivation hooks.
register_activation_hook( __FILE__, 'woomorrintegration_activate' );
register_deactivation_hook( __FILE__, 'woomorrintegration_deactivate' );
add_action( 'init', 'woomorrintegration_set_rewrite_rules' );

function woomorrintegration_set_rewrite_rules() {
	add_rewrite_rule( 'pdfinvoice/?$', 'index.php?pdfinvoice=1', 'top' );
}

// Add custom query variable to catch the rewrite rule.
add_filter( 'query_vars', 'woomorrintegration_query_vars' );
function woomorrintegration_query_vars( $vars ) {
	$vars[] = 'pdfinvoice';
	return $vars;
}

// Template redirect to handle the custom query variable.
add_action( 'template_redirect', 'woomorrintegration_template_redirect' );
function woomorrintegration_template_redirect() {
	if ( get_query_var( 'pdfinvoice' ) ) {
		require WOOMORRINTEGRATION_PLUGIN_DIR . 'front/pdfInvoice.php';
		exit;
	}
}

// Include necessary files.
require_once WOOMORRINTEGRATION_PLUGIN_DIR . 'includes/functions.php';
require_once WOOMORRINTEGRATION_PLUGIN_DIR . 'admin/admin-page.php';
require_once WOOMORRINTEGRATION_PLUGIN_DIR . 'includes/user-management-api.php';
require_once WOOMORRINTEGRATION_PLUGIN_DIR . 'includes/store-chat-api.php';
require_once WOOMORRINTEGRATION_PLUGIN_DIR . 'includes/woomorrintegration-session.php';
require_once WOOMORRINTEGRATION_PLUGIN_DIR . 'includes/media-upload.php';
require_once WOOMORRINTEGRATION_PLUGIN_DIR . 'includes/woo-custom-api-fields.php';
require_once WOOMORRINTEGRATION_PLUGIN_DIR . 'includes/woocommerce-analysis-api.php';
require_once WOOMORRINTEGRATION_PLUGIN_DIR . 'app/updater.php';

/**
 * Activation hook callback function.
 */
function woomorrintegration_activate() {
	woomorrintegration_set_rewrite_rules();
	flush_rewrite_rules();
	storechat_table_install();
}

/**
 * Deactivation hook callback function.
 */
function woomorrintegration_deactivate() {
	flush_rewrite_rules();
}

/**
 * Install custom table for storing chat messages.
 */
function storechat_table_install() {
	global $wpdb;

	$table_name      = $wpdb->prefix . 'store_chat_messages';
	$charset_collate = $wpdb->get_charset_collate();
	$table_version   = '1.0';

	$installed_db_ver = get_option( 'woomorrintegration_db_version' );

	if ( $installed_db_ver != $table_version ) {

		$sql = "CREATE TABLE $table_name (
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

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		add_option( 'woomorrintegration_db_version', $table_version );
	}
}
