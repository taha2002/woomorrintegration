<?php
/**
 * Class Updater
 *
 * The Updater class handles plugin updates by integrating with the WordPress update system.
 * 
 * @package WOOMORRINTEGRATION
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Updater {
	/**
	 * The plugin's directory name.
	 *
	 * @var string $plugin_slug
	 */
	public $plugin_slug;

	/**
	 * The current version of the plugin.
	 *
	 * @var string $version
	 */
	public $version;

	/**
	 * The key used for caching remote plugin data.
	 *
	 * @var string $cache_key
	 */
	public $cache_key;

	/**
	 * Flag indicating whether caching is allowed.
	 *
	 * @var bool $cache_allowed
	 */

	public $cache_allowed;

	/**
	 * Updater constructor.
	 *
	 * Initializes the Updater class.
	 */
	public function __construct() {

		if ( defined( 'WP_WOOMORRINTEGRATION_DEV_MODE' ) ) {
			add_filter( 'https_ssl_verify', '__return_false' );
			add_filter( 'https_local_ssl_verify', '__return_false' );
			add_filter( 'http_request_host_is_external', '__return_true' );
		}

		$this->plugin_slug   = dirname( plugin_basename( __DIR__ ) );
		$this->version       = WOOMORRINTEGRATION_VERSION;
		$this->cache_key     = WOOMORRINTEGRATION_CACHE_KEY;
		$this->cache_allowed = false;

		add_filter( 'plugins_api', array( $this, 'info' ), 20, 3 );
		add_filter( 'site_transient_update_plugins', array( $this, 'update' ) );
		add_action( 'upgrader_process_complete', array( $this, 'purge' ), 10, 2 );

	}

	/**
	 * Send request to remote server to fetch plugin data.
	 *
	 * @return mixed|false Remote plugin data if successful, false otherwise.
	 */
	public function request() {

		$remote = get_transient( $this->cache_key );

		if ( false === $remote || ! $this->cache_allowed ) {

			$remote = wp_remote_get(
				WOOMORRINTEGRATION_UPDATE_URL,
				array(
					'timeout' => 10,
					'headers' => array(
						'Accept' => 'application/json',
					),
				)
			);

			if ( is_wp_error( $remote ) || 200 !== wp_remote_retrieve_response_code( $remote ) || empty( wp_remote_retrieve_body( $remote ) ) ) {
				return false;
			}

			set_transient( $this->cache_key, $remote, DAY_IN_SECONDS );

		}

		$remote = json_decode( wp_remote_retrieve_body( $remote ) );

		return $remote;

	}

	/**
	 * Filter hook for retrieving plugin information.
	 *
	 * @param mixed  $response The response object.
	 * @param string $action The type of request being performed.
	 * @param object $args The request arguments.
	 * @return mixed The filtered response object.
	 */
	function info( $response, $action, $args ) {

		// do nothing if you're not getting plugin information right now.
		if ( 'plugin_information' !== $action ) {
			return $response;
		}

		// do nothing if it is not our plugin.
		if ( empty( $args->slug ) || $this->plugin_slug !== $args->slug ) {
			return $response;
		}

		// get updates.
		$remote = $this->request();

		if ( ! $remote ) {
			return $response;
		}

		$response = new \stdClass();

		$response->name           = $remote->name;
		$response->slug           = $remote->slug;
		$response->version        = $remote->version;
		$response->tested         = $remote->tested;
		$response->requires       = $remote->requires;
		$response->author         = $remote->author;
		$response->author_profile = $remote->author_profile;
		$response->donate_link    = $remote->donate_link;
		$response->homepage       = $remote->homepage;
		$response->download_link  = $remote->download_url;
		$response->trunk          = $remote->download_url;
		$response->requires_php   = $remote->requires_php;
		$response->last_updated   = $remote->last_updated;

		$response->sections = array(
			'description'  => $remote->sections->description,
			'installation' => $remote->sections->installation,
			'changelog'    => $remote->sections->changelog,
		);

		if ( ! empty( $remote->banners ) ) {
			$response->banners = array(
				'low'  => $remote->banners->low,
				'high' => $remote->banners->high,
			);
		}

		return $response;

	}

	/**
	 * Filter hook for updating plugin information.
	 *
	 * @param mixed $transient The transient data.
	 * @return mixed The modified transient data.
	 */
	public function update( $transient ) {

		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$remote = $this->request();

		if ( $remote && version_compare( $this->version, $remote->version, '<' ) && version_compare( $remote->requires, get_bloginfo( 'version' ), '<=' ) && version_compare( $remote->requires_php, PHP_VERSION, '<' ) ) {
			$response              = new \stdClass();
			$response->slug        = $this->plugin_slug;
			$response->plugin      = "{$this->plugin_slug}/{$this->plugin_slug}.php";
			$response->new_version = $remote->version;
			$response->tested      = $remote->tested;
			$response->package     = $remote->download_url;

			$transient->response[ $response->plugin ] = $response;

		}

		return $transient;

	}

	/**
	 * Action hook for purging cache after plugin update.
	 *
	 * @param object $upgrader The upgrader object.
	 * @param array  $options The upgrader options.
	 */
	public function purge( $upgrader, $options ) {

		// if ( 'update' === $options['action'] && 'plugin' === $options['type'] ) {
		// 	// refresh configuration.
		// 	( new Configurations() )->refresh_configs();
		// }

		if ( $this->cache_allowed && 'update' === $options['action'] && 'plugin' === $options['type'] ) {
			// just clean the cache when new plugin version is installed.
			delete_transient( $this->cache_key );
		}

	}

}

new Updater();