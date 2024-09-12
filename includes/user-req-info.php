<?php
/**
 * Get information about user who visited a page of your website.
 *
 * @package WOOMORRINTEGRATION
 * @author Taha Bou
 * @version 1.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require __DIR__ . '/../vendor/autoload.php';
// use WhichBrowser\Parser;

/**
 * Class UserInfo
 * This class provides methods to retrieve user information, such as IP, browser, device, and geolocation data.
 *
 * @package UserInfo
 */
class UserInfo {

	/**
	 * @var array $browser_info Stores browser-related information.
	 */
	private $browser_info;

	/**
	 * @var array $geo_info Stores geolocation information.
	 */
	private $geo_info;

	/**
	 * Autoload information from external services and set values of internal properties
	 */
	public function __construct() {
		try {
			$this->browser_info = new WhichBrowser\Parser( $_SERVER['HTTP_USER_AGENT'] );
		} catch ( Exception $e ) {
			$this->browser_info = array();
		}

		try {
			$this->geo_info = $this->get_geo_info();
			if ( ! is_array( $this->geo_info ) ) {
				throw new Exception( 'Invalid response from the IPGeolocation service.', 1 );
			}
		} catch ( Exception $e ) {
			$this->geo_info = array();
		}
	}

	/**
	 * Get the user's IP address.
	 *
	 * @return string|null User's IP address.
	 */
	public function get_ip() {
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			return end( array_filter( array_map( 'trim', explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) );
		}

		return $_SERVER['REMOTE_ADDR'] ?? null;
	}

	/**
	 * Get the reverse DNS of the user's IP address.
	 *
	 * @return string Reverse DNS of the user's IP address.
	 */
	public function get_reverse_dns() {
		return gethostbyaddr( $this->get_ip() );
	}

	/**
	 * Get the current page URL.
	 *
	 * @return string The current URL.
	 */
	public function get_current_url(): string {
		return 'http' . ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 's' : '' )
			. '://' . $_SERVER['SERVER_NAME']
			. ( $_SERVER['SERVER_PORT'] !== '80' ? ':' . $_SERVER['SERVER_PORT'] : '' )
			. $_SERVER['REQUEST_URI'];
	}

	/**
	 * Get the referrer URL.
	 *
	 * @return string|null The referrer URL or null if not set.
	 */
	public function get_referer_url(): ?string {
		return $_SERVER['HTTP_REFERER'] ?? null;
	}

	/**
	 * Get the user's browser language.
	 *
	 * @return string The user's browser language in uppercase (e.g., 'EN').
	 */
	public function get_language(): string {
		return strtoupper( substr( $_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2 ) );
	}

	/**
	 * Get the user's device information.
	 *
	 * @return string The name of the user's device.
	 */
	public function get_device(): string {
		if ( isset( $this->browser_info->device->type ) ) {
			return $this->browser_info->device->type;
		}
		return 'Unknown Device';
	}

	/**
	 * Get the user's operating system information.
	 *
	 * @return string The name of the user's operating system.
	 */
	public function get_os(): string {
		if ( isset( $this->browser_info->os->name ) ) {
			return $this->browser_info->os->name;
		}
		return 'Unknown OS';
	}

	/**
	 * Get the user's browser information.
	 *
	 * @return string The name and version of the user's browser.
	 */
	public function get_browser(): string {
		if ( isset( $this->browser_info->browser->name, $this->browser_info->browser->version->value ) ) {
			return $this->browser_info->browser->name . ' ' . $this->browser_info->browser->version->value;
		}
		return 'Unknown Browser';
	}

	/**
	 * Get the user's country code (ISO 3166-1 alpha-2).
	 *
	 * @return string The user's country code.
	 */
	public function get_country_code(): string {
		return $this->geo_info['country_code2'] ?? '';
	}

	/**
	 * Get the user's country name.
	 *
	 * @return string The name of the user's country.
	 */
	public function get_country_name(): string {
		return $this->geo_info['country_name'] ?? '';
	}

	/**
	 * Get the user's region/state code.
	 *
	 * @return string The code of the user's region/state.
	 */
	public function get_region_code(): string {
		return $this->geo_info['state_code'] ?? '';
	}

	/**
	 * Get the user's region/state name.
	 *
	 * @return string The name of the user's region/state.
	 */
	public function get_region_name(): string {
		return $this->geo_info['state_prov'] ?? '';
	}

	/**
	 * Get the user's city name.
	 *
	 * @return string The name of the user's city.
	 */
	public function get_city(): string {
		return $this->geo_info['city'] ?? '';
	}

	/**
	 * Get the user's ZIP/postal code.
	 *
	 * @return string The user's ZIP code.
	 */
	public function get_zipcode(): string {
		return $this->geo_info['zipcode'] ?? '';
	}

	/**
	 * Get the user's latitude.
	 *
	 * @return string The latitude of the user's location.
	 */
	public function get_latitude(): string {
		return $this->geo_info['latitude'] ?? '';
	}

	/**
	 * Get the user's longitude.
	 *
	 * @return string The longitude of the user's location.
	 */
	public function get_longitude(): string {
		return $this->geo_info['longitude'] ?? '';
	}

	/**
	 * Check if the connection was made through a proxy.
	 *
	 * @return bool True if the connection was made via a proxy; otherwise, false.
	 */
	public function is_proxy(): bool {
		return ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] );
	}

	/**
	 * Fetch geolocation information using the IPGeolocation.io API.
	 *
	 * @return array The geolocation information in array format.
	 */
	private function get_geo_info(): array {
		$ip  = $this->get_ip();
		$url = 'https://api.ipgeolocation.io/ipgeo?include=hostname&ip=' . $ip;

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 15 );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'origin: https://ipgeolocation.io' ) );
		$response = curl_exec( $ch );
		curl_close( $ch );

		return json_decode( $response, true ) ?: array();
	}
}
