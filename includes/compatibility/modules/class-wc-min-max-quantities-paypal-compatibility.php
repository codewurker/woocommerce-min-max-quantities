<?php
/**
 * WC_Min_Max_Quantities_PayPal_Compatibility class
 *
 * @package  Woo Min Max Quantities
 * @since    4.3.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PayPal Compatibility.
 *
 * @version 4.3.2
 */
class WC_Min_Max_Quantities_PayPal_Compatibility {

	// Hide smart buttons in product pages when Min or Max qty/value are set.
	public static function init() {
		add_filter( 'woocommerce_paypal_payments_product_supports_payment_request_button', array( __CLASS__, 'handle_smart_buttons' ), 10, 2 );
	}

	/**
	 * Hide smart buttons in product pages when Min or Max qty/value are set.
	 *
	 * @param  bool       $is_supported
	 * @param  WC_Product $product
	 * 
	 * @return bool
	 */
	public static function handle_smart_buttons( $is_supported, $product ) {
		// If the smart button is not supported by some other plugin, respect that.
		if ( ! $is_supported ) {
			return $is_supported;
		}

		$mmq_instance  = WC_Min_Max_Quantities::get_instance();
		return $mmq_instance->can_display_express_checkout( $product );
	}
}

WC_Min_Max_Quantities_PayPal_Compatibility::init();
