<?php
/**
 * WC_MMQ_REST_API class
 *
 * @package  Woo Min/Max Quantities
 * @since    4.3.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add custom REST API fields.
 *
 * @class    WC_MMQ_REST_API
 * @version  4.3.0
 */
class WC_MMQ_REST_API {

	/**
	 * Custom REST API product field names, indicating support for getting/updating.
	 * @var array
	 */
	private static $product_fields = array(
		'group_of_quantity'                  => array( 'get', 'update' ),
		'min_quantity'                       => array( 'get', 'update' ),
		'max_quantity'                       => array( 'get', 'update' ),
		'exclude_order_quantity_value_rules' => array( 'get', 'update' ),
		'exclude_category_quantity_rules'    => array( 'get', 'update' ),
		'combine_variations'                 => array( 'get', 'update' ),
	);

	/**
	 * Setup order class.
	 */
	public static function init() {

		// Register WP REST API custom product fields.
		add_action( 'rest_api_init', array( __CLASS__, 'register_product_fields' ), 0 );

		// Filter responses from the variations endpoint.
		add_action( 'rest_api_init', array( __CLASS__, 'filter_variation_fields' ), 0 );
	}

	/**
	 * Filters REST API product variation responses to add custom data.
	 */
	public static function filter_variation_fields() {

		// Modify GET requests for product variations.
		add_filter( 'woocommerce_rest_prepare_product_variation_object', array( __CLASS__, 'filter_product_variation_response' ), 10, 2 );

		// Modify PUT requests for product variations.
		add_filter( 'woocommerce_rest_pre_insert_product_variation_object', array( __CLASS__, 'set_variation_quantity_rules' ), 10, 2 );

		// Add Min/Max Quantities fields to variations schema.
		add_filter( 'woocommerce_rest_product_variation_schema', array( __CLASS__, 'filter_variation_schema' ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Variations.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Modify GET requests for product variations.
	 *
	 * @param  array  $schema
	 * @return array
	 */
	public static function filter_variation_schema( $schema ) {

		foreach ( self::get_extended_variation_schema() as $field_name => $field_content ) {
			$schema[ $field_name ] = $field_content;
		}

		return $schema;
	}

	/**
	 * Filters WC REST API GET product variation responses.
	 *
	 * @since  4.3.0
	 *
	 * @param  WP_REST_Response   $response
	 * @param  WC_Data            $product
	 * @return WP_REST_Response
	 */
	public static function filter_product_variation_response( $response, $product ) {

		if ( $product->is_type( 'variation' ) ) {

			$data = $response->get_data();

			foreach ( self::get_extended_variation_schema() as $field_name => $field_content ) {
				$data[ $field_name ] = self::get_product_field( $field_name, $product );
			}

			$response->set_data( $data );
		}

		return $response;
	}

	/**
	 * Filters WC REST API SET product variation responses.
	 *
	 * @since  4.3.0
	 *
	 * @param  WC_Product_Variation $variation
	 * @param  WP_REST_Response     $response
	 *
	 */
	public static function set_variation_quantity_rules( $variation, $request ) {

		if ( ! is_a( $variation, 'WC_Product_Variation' ) ) {
			return $variation;
		}

		if ( isset( $request[ 'variation_quantity_rules' ] ) ) {
			$variation->update_meta_data( 'min_max_rules', wc_clean( $request[ 'variation_quantity_rules' ] ) );
			$variation->save();
		}

		if ( isset( $request[ 'group_of_quantity' ] ) ) {
			$variation->update_meta_data( 'variation_group_of_quantity', (int) wc_clean( $request[ 'group_of_quantity' ] ) );
			$variation->save();
		}

		if ( isset( $request[ 'min_quantity' ] ) ) {

			$group_of_rule = $variation->get_meta( 'variation_group_of_quantity', true );
			$min_quantity  = (int) wc_clean( $request[ 'min_quantity' ] );

			if ( $group_of_rule && ( 0 !== $min_quantity % $group_of_rule ) ) {
				/* translators: Group of quantity */
				throw new WC_REST_Exception( 'woocommerce_rest_invalid_variation_min_quantity', sprintf( __( 'The minimum quantity must be a multiple of %d.', 'woocommerce-min-max-quantities' ), $group_of_rule ), 400 );
			}

			$variation->update_meta_data( 'variation_minimum_allowed_quantity', $min_quantity );
			$variation->save();
		}

		if ( isset( $request[ 'max_quantity' ] ) ) {

			$group_of_rule = (int) $variation->get_meta( 'variation_group_of_quantity', true );
			$min_quantity  = (int) $variation->get_meta( 'variation_minimum_allowed_quantity', true );

			$max_quantity = '' !== wc_clean( $request[ 'max_quantity' ] ) ? (int) wc_clean( $request[ 'max_quantity' ] ) : '';

			if ( '' !== $max_quantity ) {
				if ( $min_quantity && $max_quantity < $min_quantity ) {
					/* translators: Minimum quantity */
					throw new WC_REST_Exception( 'woocommerce_rest_invalid_variation_max_quantity', sprintf( __( 'The maximum quantity must be greater than %d, which is the Minimum Quantity.', 'woocommerce-min-max-quantities' ), $min_quantity ), 400 );
				}

				if ( $group_of_rule && ( 0 !== $max_quantity % $group_of_rule ) ) {
					/* translators: Group of quantity */
					throw new WC_REST_Exception( 'woocommerce_rest_invalid_variation_max_quantity', sprintf( __( 'The maximum quantity must be a multiple of %d.', 'woocommerce-min-max-quantities' ), $group_of_rule ), 400 );
				}
			}

			$variation->update_meta_data( 'variation_maximum_allowed_quantity', $max_quantity );
			$variation->save();
		}

		if ( isset( $request[ 'exclude_order_quantity_value_rules' ] ) ) {
			$variation->update_meta_data( 'variation_minmax_cart_exclude', wc_clean( $request[ 'exclude_order_quantity_value_rules' ] ) );
			$variation->save();
		}

		if ( isset( $request[ 'exclude_category_quantity_rules' ] ) ) {
			$variation->update_meta_data( 'variation_minmax_category_group_of_exclude', wc_clean( $request[ 'exclude_category_quantity_rules' ] ) );
			$variation->save();
		}

		if ( isset( $request[ 'combine_variations' ] ) ) {
			throw new WC_REST_Exception( 'woocommerce_rest_invalid_product_type_allow_combinations', __( 'The Allow Combinations option can only be set for Variable Products.', 'woocommerce-min-max-quantities' ) , 400 );
		}

		return $variation;
	}

	/**
	 * Gets extended (unprefixed) variation schema properties for products.
	 *
	 * @return array
	 */
	private static function get_extended_variation_schema() {

		return array(
			'variation_quantity_rules'           => array(
				'description' => __( 'Enable this option to set quantity rules for a specific variation.', 'woocommerce-min-max-quantities' ),
				'type'        => 'string',
				'enum'        => array( 'yes', 'no' ),
				'context'     => array( 'view', 'edit' )
			),
			'group_of_quantity'                  => array(
				'description' => __( 'Require variations to be purchased in multiples of this value.', 'woocommerce-min-max-quantities' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' )
			),
			'min_quantity'                       => array(
				'description' => __( 'Minimum required variation quantity.', 'woocommerce-min-max-quantities' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' )
			),
			'max_quantity'                       => array(
				'description' => __( 'Maximum allowed variation quantity.', 'woocommerce-min-max-quantities' ),
				'type'        => WC_MMQ_Core_Compatibility::is_wp_version_gte( '5.5' ) ? array( 'integer', 'string' ) : '',
				'context'     => array( 'view', 'edit' )
			),
			'exclude_order_quantity_value_rules' => array(
				'description' => __( 'Exclude variation from order quantity and value rules.', 'woocommerce-min-max-quantities' ),
				'type'        => 'string',
				'enum'        => array( 'yes', 'no' ),
				'context'     => array( 'view', 'edit' )
			),
			'exclude_category_quantity_rules'    => array(
				'description' => __( 'Exclude variation from category quantity rules.', 'woocommerce-min-max-quantities' ),
				'type'        => 'string',
				'enum'        => array( 'yes', 'no' ),
				'context'     => array( 'view', 'edit' )
			)
		);
	}

	/*
	|--------------------------------------------------------------------------
	| Products.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Register custom REST API fields for product requests.
	 */
	public static function register_product_fields() {

		foreach ( self::$product_fields as $field_name => $field_supports ) {

			$args = array(
				'schema' => self::get_product_field_schema( $field_name )
			);

			if ( in_array( 'get', $field_supports ) ) {
				$args[ 'get_callback' ] = array( __CLASS__, 'get_product_field_value' );
			}
			if ( in_array( 'update', $field_supports ) ) {
				$args[ 'update_callback' ] = array( __CLASS__, 'update_product_field_value' );
			}

			register_rest_field( 'product', $field_name, $args );
		}
	}

	/**
	 * Gets extended (unprefixed) schema properties for products.
	 *
	 * @return array
	 */
	private static function get_extended_product_schema() {

		return array(
			'group_of_quantity'                  => array(
				'description' => __( 'Require products to be purchased in multiples of this value.', 'woocommerce-min-max-quantities' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' )
			),
			'min_quantity'                       => array(
				'description' => __( 'Minimum required product quantity.', 'woocommerce-min-max-quantities' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' )
			),
			'max_quantity'                       => array(
				'description' => __( 'Maximum allowed product quantity.', 'woocommerce-min-max-quantities' ),
				'type'        => WC_MMQ_Core_Compatibility::is_wp_version_gte( '5.5' ) ? array( 'integer', 'string' ) : '',
				'context'     => array( 'view', 'edit' )
			),
			'exclude_order_quantity_value_rules' => array(
				'description' => __( 'Exclude product from order quantity and value rules.', 'woocommerce-min-max-quantities' ),
				'type'        => 'string',
				'enum'        => array( 'yes', 'no' ),
				'context'     => array( 'view', 'edit' )
			),
			'exclude_category_quantity_rules'    => array(
				'description' => __( 'Exclude product from category quantity rules.', 'woocommerce-min-max-quantities' ),
				'type'        => 'string',
				'enum'        => array( 'yes', 'no' ),
				'context'     => array( 'view', 'edit' )
			),
			'combine_variations'                 => array(
				'description' => __( 'Enable this option to combine the quantities of all purchased variations when checking quantity rules.', 'woocommerce-min-max-quantities' ),
				'type'        => 'string',
				'enum'        => array( 'yes', 'no' ),
				'context'     => array( 'view', 'edit' )
			)
		);
	}

	/**
	 * Gets schema properties for MMQ product fields.
	 *
	 * @param  string  $field_name
	 * @return array
	 */
	public static function get_product_field_schema( $field_name ) {

		$extended_schema = self::get_extended_product_schema();
		$field_schema    = isset( $extended_schema[ $field_name ] ) ? $extended_schema[ $field_name ] : null;

		return $field_schema;
	}

	/**
	 * Gets values for MMQ product fields.
	 *
	 * @param  array            $response
	 * @param  string           $field_name
	 * @param  WP_REST_Request  $request
	 * @return array
	 */
	public static function get_product_field_value( $response, $field_name, $request ) {

		$data = null;

		if ( isset( $response[ 'id' ] ) ) {
			$product = wc_get_product( $response[ 'id' ] );
			$data    = self::get_product_field( $field_name, $product );
		}

		return $data;
	}

	/**
	 * Updates values for MMQ product fields.
	 *
	 * @param  mixed   $value
	 * @param  mixed   $response
	 * @param  string  $field_name
	 * @return boolean
	 */
	public static function update_product_field_value( $field_value, $response, $field_name ) {

		$product_id = false;

		if ( $response instanceof WP_Post ) {
			$product_id   = absint( $response->ID );
			$product      = wc_get_product( $product_id );
			$product_type = $product->get_type();
		} elseif ( $response instanceof WC_Product ) {
			$product_id   = $response->get_id();
			$product      = $response;
			$product_type = $response->get_type();
		}

		// Only possible to set fields of 'bundle' type products.
		if ( $product_id ) {

			// Set group of value.
			if ( 'group_of_quantity' === $field_name ) {

				$product->update_meta_data( 'group_of_quantity', (int) wc_clean( $field_value ) );
				$product->save();

			// Set minimum quantity.
			} elseif ( 'min_quantity' === $field_name ) {

				$mmq_instance  = WC_Min_Max_Quantities::get_instance();
				$group_of_rule = $product->get_meta( 'group_of_quantity', true ) ? $product->get_meta( 'group_of_quantity', true ) : $mmq_instance->get_group_of_quantity_for_product( $product );
				$max_quantity  = $product->get_meta( 'maximum_allowed_quantity', true );
				$min_quantity  = (int) wc_clean( $field_value );

				if ( '' !== $max_quantity && $max_quantity < $min_quantity ) {
					/* translators: Minimum quantity */
					throw new WC_REST_Exception( 'woocommerce_rest_invalid_max_quantity', sprintf( __( 'The minimum quantity must be less than %d, which is the Maximum Quantity.', 'woocommerce-min-max-quantities' ), $max_quantity ), 400 );
				}

				if ( $group_of_rule && ( 0 !== $min_quantity % $group_of_rule ) ) {
					/* translators: Group of quantity */
					throw new WC_REST_Exception( 'woocommerce_rest_invalid_min_quantity', sprintf( __( 'The minimum quantity must be a multiple of %d.', 'woocommerce-min-max-quantities' ), $group_of_rule ), 400 );
				}

				$product->update_meta_data( 'minimum_allowed_quantity', $min_quantity );
				$product->save();

			// Set maximum quantity.
			} elseif ( 'max_quantity' === $field_name ) {

				$mmq_instance  = WC_Min_Max_Quantities::get_instance();
				$group_of_rule = $product->get_meta( 'group_of_quantity', true ) ? $product->get_meta( 'group_of_quantity', true ) : $mmq_instance->get_group_of_quantity_for_product( $product );
				$min_quantity  = $product->get_meta( 'minimum_allowed_quantity', true );
				$max_quantity  = wc_clean( $field_value );

				if ( $min_quantity && $max_quantity < $min_quantity ) {
					/* translators: Minimum quantity */
					throw new WC_REST_Exception( 'woocommerce_rest_invalid_max_quantity', sprintf( __( 'The maximum quantity must be greater than %d, which is the Minimum Quantity.', 'woocommerce-min-max-quantities' ), $min_quantity ), 400 );
				}

				if ( $group_of_rule && ( 0 !== $max_quantity % $group_of_rule ) ) {
					/* translators: Group of quantity */
					throw new WC_REST_Exception( 'woocommerce_rest_invalid_max_quantity', sprintf( __( 'The maximum quantity must be a multiple of %d.', 'woocommerce-min-max-quantities' ), $group_of_rule ), 400 );
				}

				$product->update_meta_data( 'maximum_allowed_quantity', wc_clean( $field_value ) );
				$product->save();

			// Set Exclude from > Order rules.
			} elseif ( 'exclude_order_quantity_value_rules' === $field_name ) {

				$product->update_meta_data( 'minmax_cart_exclude', wc_clean( $field_value ) );
				$product->save();

			// Set Exclude from > Category rules.
			} elseif ( 'exclude_category_quantity_rules' === $field_name ) {

				$product->update_meta_data( 'minmax_category_group_of_exclude', wc_clean( $field_value ) );
				$product->save();

			// Set Exclude from > Category rules.
			} elseif ( 'combine_variations' === $field_name ) {

				if ( 'variable' !== $product_type ) {
					throw new WC_REST_Exception( 'woocommerce_rest_invalid_product_type_allow_combinations', __( 'The Allow Combinations option can only be set for Variable Products.', 'woocommerce-min-max-quantities' ) , 400 );
				}

				$product->update_meta_data( 'allow_combination', wc_clean( $field_value ) );
				$product->save();
			}
		}

		return true;
	}

	/**
	 * Gets bundle-specific product data.
	 *
	 * @since  5.0.0
	 *
	 * @param  string      $key
	 * @param  WC_Product  $product
	 * @return array
	 */
	private static function get_product_field( $key, $product ) {

		$product_type = $product->get_type();
		$product_id   = $product->get_id();

		switch ( $key ) {

			case 'group_of_quantity' :

				if ( 'variation' === $product_type ) {
					if ( 'yes' === $product->get_meta( 'min_max_rules', true ) ) {
						$value = (int) $product->get_meta( 'variation_group_of_quantity', true );
					} else {
						$parent_product = wc_get_product( $product->get_parent_id() );
						$mmq_instance   = WC_Min_Max_Quantities::get_instance();
						$value          = $mmq_instance->get_group_of_quantity_for_product( $parent_product );
					}
				} else {
					$mmq_instance      = WC_Min_Max_Quantities::get_instance();
					$value             = $mmq_instance->get_group_of_quantity_for_product( $product );
				}

			break;
			case 'min_quantity' :

				if ( 'variation' === $product_type ) {
					if ( 'yes' === $product->get_meta( 'min_max_rules', true ) ) {
						$value = (int) $product->get_meta( 'variation_minimum_allowed_quantity', true );
					} else {
						$parent_product = wc_get_product( $product->get_parent_id() );
						$value          = (int) $parent_product->get_meta( 'minimum_allowed_quantity', true );
					}
				} else {
					$value = (int) $product->get_meta( 'minimum_allowed_quantity', true );
				}

			break;
			case 'max_quantity' :

				if ( 'variation' === $product_type ) {
					if ( 'yes' === $product->get_meta( 'min_max_rules', true ) ) {
						$max_quantity = $product->get_meta( 'variation_maximum_allowed_quantity', true );
					} else {
						$parent_product = wc_get_product( $product->get_parent_id() );
						$max_quantity   = $parent_product->get_meta( 'maximum_allowed_quantity', true );
					}
				} else {
					$max_quantity = $product->get_meta( 'maximum_allowed_quantity', true );
				}

				$value = '' !== $max_quantity ? (int) $max_quantity : '';

			break;
			case 'exclude_order_quantity_value_rules' :

				if ( 'variation' === $product_type ) {
					if ( 'yes' === $product->get_meta( 'min_max_rules', true ) ) {
						$value = $product->get_meta( 'variation_minmax_cart_exclude', true );
					} else {
						$parent_product = wc_get_product( $product->get_parent_id() );
						$value          = $parent_product->get_meta( 'minmax_cart_exclude', true );
					}
				} else {
					$value = $product->get_meta( 'minmax_cart_exclude', true );
				}

				if ( '' === $value ) {
					$value = 'no';
				}

			break;
			case 'exclude_category_quantity_rules' :

				if ( 'variation' === $product_type ) {
					if ( 'yes' === $product->get_meta( 'min_max_rules', true ) ) {
						$value = $product->get_meta( 'variation_minmax_category_group_of_exclude', true );
					} else {
						$parent_product = wc_get_product( $product->get_parent_id() );
						$value          = $parent_product->get_meta( 'minmax_category_group_of_exclude', true );
					}
				} else {
					$value = $product->get_meta( 'minmax_category_group_of_exclude', true );
				}

				if ( '' === $value ) {
					$value = 'no';
				}

			break;
			case 'combine_variations' :

				$value = "no";

				if ( 'variable' === $product_type ) {
					$value = $product->get_meta( 'allow_combination', true );
				}

				if ( '' === $value ) {
					$value = 'no';
				}

			break;
			case 'variation_quantity_rules' :

				$value = "no";

				if ( 'variation' === $product_type ) {
					$value = $product->get_meta( 'min_max_rules', true );
				}

				if ( '' === $value ) {
					$value = 'no';
				}

			break;
		}

		return $value;
	}
}

WC_MMQ_REST_API::init();
