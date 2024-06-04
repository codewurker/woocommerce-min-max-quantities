<?php
/**
 * WC_Min_Max_Quantities_Stripe_Compatibility class
 *
 * @package  Woo Min Max Quantities
 * @since    4.3.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stripe Compatibility.
 *
 * @version 4.3.2
 */
class WC_Min_Max_Quantities_Stripe_Compatibility {

	// Hide smart buttons in product pages when Min or Max qty/value are set.
	public static function init() {
		add_filter( 'wc_stripe_hide_payment_request_on_product_page', array( __CLASS__, 'handle_express_checkout_buttons' ), 10, 2 );
	}

	/**
	 * Hide express checkout buttons in product pages when Min or Max qty/value are set.
	 *
	 * @param  bool    $hide
	 * @param  WP_Post $post
	 *
	 * @return bool
	 */
	public static function handle_express_checkout_buttons( $hide, $post = null ) {
		// If the button is already hidden by some other plugin, respect that.
		if ( $hide ) {
			return $hide;
		}

		if ( is_null( $post ) ) {
			global $post;
		}

		if ( ! is_object( $post ) || empty( $post->ID ) ) {
			return $hide;
		}

		$product = wc_get_product( $post->ID );
		if ( $product && is_a( $product, 'WC_Product' ) ) {
			$mmq_instance  = WC_Min_Max_Quantities::get_instance();
			$hide = ! $mmq_instance->can_display_express_checkout( $product ); // the filter needs true for hiding the button.
			return $hide;
		}

		return $hide;
	}
}

WC_Min_Max_Quantities_Stripe_Compatibility::init();
