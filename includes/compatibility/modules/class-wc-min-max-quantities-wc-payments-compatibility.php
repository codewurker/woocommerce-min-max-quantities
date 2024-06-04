<?php
/**
 * WC_Min_Max_Quantities_WC_Payments_Compatibility class
 *
 * @package  Woo Min Max Quantities
 * @since    4.3.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Payments Compatibility.
 *
 * @version 4.3.2
 */
class WC_Min_Max_Quantities_WC_Payments_Compatibility {

	public static function init() {
        // Hide express checkout buttons in product pages when Min or Max qty/value are set.
		add_filter( 'wcpay_payment_request_is_product_supported', array( __CLASS__, 'handle_express_checkout_buttons' ), 10, 2 );
		add_filter( 'wcpay_woopay_button_is_product_supported', array( __CLASS__, 'handle_express_checkout_buttons' ), 10, 2 );
	}

	/**
	 * Hide express checkout buttons in product pages when Min or Max qty/value are set.
	 *
	 * @param  bool       $is_supported
	 * @param  WC_Product $product
	 *
	 * @return bool
	 */
	public static function handle_express_checkout_buttons( $is_supported, $product ) {
		// If the smart button is not supported by some other plugin, respect that.
		if ( ! $is_supported ) {
			return $is_supported;
		}

		$mmq_instance  = WC_Min_Max_Quantities::get_instance();
		return $mmq_instance->can_display_express_checkout( $product );
	}
}

WC_Min_Max_Quantities_WC_Payments_Compatibility::init();
