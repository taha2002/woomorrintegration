<?php
/**
 * Plugin Name:       Woomorrintegration
 * Plugin URI:        https://morr.biz/
 * Description:       Add woo integration with morr.
 * Version:           1.0.0
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

// Define constants.
define( 'WOOMORRINTEGRATION_VERSION', '1.0.0' );
define( 'WOOMORRINTEGRATION_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WOOMORRINTEGRATION_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WOOMORRINTEGRATION_UPDATE_URL', 'https://wpfreighter.com/plugin-wpfreighter.json' );
define( 'WOOMORRINTEGRATION_CACHE_KEY', 'wpwoomorrintegration_updater' );


// Register activation and deactivation hooks.
register_activation_hook( __FILE__, 'woomorrintegration_activate' );
register_deactivation_hook( __FILE__, 'woomorrintegration_deactivate' );

// Include necessary files.
require_once WOOMORRINTEGRATION_PLUGIN_DIR . 'admin/admin-page.php';
require_once WOOMORRINTEGRATION_PLUGIN_DIR . 'includes/user-management-api.php';
require_once WOOMORRINTEGRATION_PLUGIN_DIR . 'includes/woomorrintegration-session.php';
require_once WOOMORRINTEGRATION_PLUGIN_DIR . 'includes/media-upload.php';
require_once WOOMORRINTEGRATION_PLUGIN_DIR . 'includes/woo-custom-api-fields.php';
require_once WOOMORRINTEGRATION_PLUGIN_DIR . 'includes/woocommerce-analysis-api.php';
require_once WOOMORRINTEGRATION_PLUGIN_DIR . 'app/updater.php';

/**
 * Activation hook callback function.
 */
function woomorrintegration_activate() {
	// activation tasks.
}

/**
 * Deactivation hook callback function.
 */
function woomorrintegration_deactivate() {
	// deactivation tasks.
}
