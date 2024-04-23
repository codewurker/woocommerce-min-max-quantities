<?php
/**
 * WC_MMQ_Core_Compatibility class
 *
 * @package  Woo Min/Max Quantities
 * @since    2.5.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Functions for WC core back-compatibility.
 *
 * @class    WC_MMQ_Core_Compatibility
 * @version  4.2.3
 */
class WC_MMQ_Core_Compatibility {

	/**
	 * Current REST request stack.
	 * An array containing WP_REST_Request instances.
	 *
	 * @since 4.2.3
	 *
	 * @var array
	 */
	private static $requests = array();

	/**
	 * Cache 'gt' comparison results for WP version.
	 *
	 * @since  4.3.0
	 * @var    array
	 */
	private static $is_wp_version_gt = array();

	/**
	 * Cache 'gte' comparison results for WP version.
	 *
	 * @since  4.3.0
	 * @var    array
	 */
	private static $is_wp_version_gte = array();


	/**
	 * Constructor.
	 */
	public static function init() {
		// Save current rest request.
		add_filter( 'rest_pre_dispatch', array( __CLASS__, 'save_rest_request' ), 10, 3 );
		add_filter( 'woocommerce_hydration_dispatch_request', array( __CLASS__, 'save_hydration_request' ), 10, 2 );
		add_filter( 'rest_request_after_callbacks', array( __CLASS__, 'pop_rest_request' ), PHP_INT_MAX );
		add_filter( 'woocommerce_hydration_request_after_callbacks', array( __CLASS__, 'pop_rest_request' ), PHP_INT_MAX );
	}
	/*
	|--------------------------------------------------------------------------
	| Callbacks.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Pops the current request from the execution stack.
	 *
	 * @since  4.2.3
	 *
	 * @param  WP_REST_Response $response
	 * @param  WP_REST_Server|array $handler
	 * @param  WP_REST_Request  $request
	 * @return mixed
	 */
	public static function pop_rest_request( $response ) {
		if ( ! empty( self::$requests ) && is_array( self::$requests ) ) {
			array_pop( self::$requests );
		}

		return $response;
	}

	/**
	 * Saves the current hydration request.
	 *
	 * @since  4.2.3
	 *
	 * @param  mixed            $result
	 * @param  WP_REST_Request  $request
	 * @return mixed
	 */
	public static function save_hydration_request( $result, $request ) {
		if ( ! is_array( self::$requests ) ) {
			self::$requests = array();
		}

		self::$requests[] = $request;
		return $result;
	}

	/**
	 * Saves the current rest request.
	 *
	 * @since  6.15.0
	 *
	 * @param  mixed            $result
	 * @param  WP_REST_Server   $server
	 * @param  WP_REST_Request  $request
	 * @return mixed
	 */
	public static function save_rest_request( $result, $server, $request ) {
		if ( ! is_array( self::$requests ) ) {
			self::$requests = array();
		}

		self::$requests[] = $request;
		return $result;
	}

	/*
	|--------------------------------------------------------------------------
	| Utilities.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Returns the current Store/REST API request or false.
	 *
	 * @since  2.5.0
	 *
	 * @return WP_REST_Request|false
	 */
	public static function get_api_request() {
		if ( empty( self::$requests ) || ! is_array( self::$requests ) ) {
			return false;
		}

		return end( self::$requests );
	}

	/**
	 * Whether this is a Store API request.
	 *
	 * @since  2.5.0
	 *
	 * @param  string  $route
	 * @return boolean
	 */
	public static function is_store_api_request( $route = '' ) {

		// Check the request URI.
		$request = self::get_api_request();

		if ( false !== $request && strpos( $request->get_route(), 'wc/store' ) !== false ) {
			if ( '' === $route || strpos( $request->get_route(), $route ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns true if the installed version of WordPress is greater than or equal to $version.
	 *
	 * @since  4.3.0
	 *
	 * @param  string  $version
	 * @return boolean
	 */
	public static function is_wp_version_gt( $version ) {
		if ( ! isset( self::$is_wp_version_gt[ $version ] ) ) {
			global $wp_version;

			$mmq_instance = WC_Min_Max_Quantities::get_instance();

			self::$is_wp_version_gt[ $version ] = $wp_version && version_compare( $mmq_instance->plugin_version( true, $wp_version ), $version, '>' );
		}
		return self::$is_wp_version_gt[ $version ];
	}

	/**
	 * Returns true if the installed version of WordPress is greater than or equal to $version.
	 *
	 * @since  4.3.0
	 *
	 * @param  string  $version
	 * @return boolean
	 */
	public static function is_wp_version_gte( $version ) {
		if ( ! isset( self::$is_wp_version_gte[ $version ] ) ) {
			global $wp_version;

			$mmq_instance = WC_Min_Max_Quantities::get_instance();

			self::$is_wp_version_gte[ $version ] = $wp_version && version_compare( $mmq_instance->plugin_version( true, $wp_version ), $version, '>=' );
		}
		return self::$is_wp_version_gte[ $version ];
	}
}

WC_MMQ_Core_Compatibility::init();
