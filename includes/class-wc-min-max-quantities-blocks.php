<?php

/**
 * WC_Min_Max_Quantities_Blocks class.
 */
class WC_Min_Max_Quantities_Blocks {

  public function __construct() {
		add_action( 'init', array( $this, 'register_custom_blocks' ) );
		add_action( 'woocommerce_block_template_area_product-form_after_add_block_inventory', array( $this, 'add_blocks_to_product_editor' ) );
  }

  public function register_custom_blocks() {
		if ( isset( $_GET['page'] ) && 'wc-admin' === $_GET['page'] ) {
			register_block_type( WC_MMQ_ABSPATH . 'assets/dist/admin/product-editor/min-quantity/block.json' );
			register_block_type( WC_MMQ_ABSPATH . 'assets/dist/admin/product-editor/max-quantity/block.json' );
			register_block_type( WC_MMQ_ABSPATH . 'assets/dist/admin/product-editor/group-of-quantity/block.json' );
			register_block_type( WC_MMQ_ABSPATH . 'assets/dist/admin/product-editor/variation-exclude/block.json' );
			register_block_type( WC_MMQ_ABSPATH . 'assets/dist/admin/product-editor/variation-group-of-quantity/block.json' );
			register_block_type( WC_MMQ_ABSPATH . 'assets/dist/admin/product-editor/variation-max-quantity/block.json' );
			register_block_type( WC_MMQ_ABSPATH . 'assets/dist/admin/product-editor/variation-min-quantity/block.json' );
			register_block_type( WC_MMQ_ABSPATH . 'assets/dist/admin/product-editor/variation-override/block.json' );
		}
	}

  public function add_blocks_to_product_editor( $inventory_tab ) {

		if ( ! method_exists( $inventory_tab, 'add_section' ) ) {
			return;
		}

		if ( $inventory_tab->get_root_template()->get_id() === 'simple-product' ) {
			$this->add_blocks_to_simple_product_template( $inventory_tab );
		} elseif ( $inventory_tab->get_root_template()->get_id() === 'product-variation' ) {
			$this->add_blocks_to_product_variation_template( $inventory_tab );
		}
	}

	private function add_blocks_to_simple_product_template( $inventory_tab ) {
		$section = $inventory_tab->add_section(
			array(
				'id'         => 'wc_min_max_section',
				'attributes' => array(
					'title' => __( 'Quantity rules', 'woocommerce-min-max-quantities' ),
				),
				'hideConditions' => array(
					array(
						'expression' => 'editedProduct.sold_individually === true',
					),
				),
			)
		);

		$section->add_block(
			array(
				'id' => 'wc_min_max_group_of_quantity',
				'blockName' => 'woocommerce-min-max/group-of-quantity-field',
				'attributes' => array(
					'label' => __( 'Sell in groups of', 'woocommerce-min-max-quantities' ),
				)
			)
		);
	
		$section->add_block(
			array(
				'id' => 'wc_min_max_minimum_allowed_quantity',
				'blockName' => 'woocommerce-min-max/min-quantity-field',
				'attributes' => array(
					'label' => __( 'Min. Quantity', 'woocommerce-min-max-quantities' ),
				)
			)
		);
		$section->add_block(
			array(
				'id' => 'wc_min_max_maximum_allowed_quantity',
				'blockName' => 'woocommerce-min-max/max-quantity-field',
				'attributes' => array(
					'label' => __( 'Max. Quantity', 'woocommerce-min-max-quantities' ),
				)
			)
		);
		$section->add_block(
			array(
				'id' => 'wc_min_max_allow_combination',
				'blockName' => 'woocommerce/product-checkbox-field',
				'hideConditions' => array(
					array(
						'expression' => 'editedProduct.type !== "variable"',
					),
				),
				'attributes' => array(
					'label' => __( 'Combine variations', 'woocommerce-min-max-quantities' ),
					'property' => 'meta_data.allow_combination',
					'checkedValue' => 'yes',
					'uncheckedValue' => 'no',
					'tooltip' => __( 'Check to apply the settings above to the sum of quantities of variations. E.g., max quantity of 5 can be satisfied by adding 2 units of one variation and 3 units of another.', 'woocommerce-min-max-quantities' ),
				)
			)
		);
		$section->add_block(
			array(
				'id' => 'wc_min_max_cart_exclude_from_order',
				'blockName' => 'woocommerce/product-checkbox-field',
				'attributes' => array(
					'label' => __( 'Exclude from order rules', 'woocommerce-min-max-quantities' ),
					'property' => 'meta_data.minmax_cart_exclude',
					'checkedValue' => 'yes',
					'uncheckedValue' => 'no',
					'tooltip' => sprintf( __( 'Check to exclude this product from the total order quantity and value calculations set up in %1$sorder settings.%2$s', 'woocommerce-min-max-quantities' ), '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=products' ) . '" target="_blank">', '</a>' ),
				)
			)
		);
	}

	private function add_blocks_to_product_variation_template( $inventory_tab ) {
		$section = $inventory_tab->add_section(
			array(
				'id'         => 'wc_min_max_section',
				'attributes' => array(
					'title' => __( 'Quantity rules', 'woocommerce-min-max-quantities' ),
				),
			)
		);
		$section->add_block(
			array(
				'id' => 'wc_min_max_override_main_product',
				'blockName' => 'woocommerce-min-max/variation-override-field',
				'attributes' => array(
					'label' => __( "Override the main product's settings", 'woocommerce-min-max-quantities' ),
				)
			)
		);
		$section->add_block(
			array(
				'id' => 'wc_min_max_group_of_quantity',
				'blockName' => 'woocommerce-min-max/variation-group-of-quantity-field',
				'attributes' => array(
					'label' => __( 'Sell in groups of', 'woocommerce-min-max-quantities' ),
				)
			)
		);
		$section->add_block(
			array(
				'id' => 'wc_min_max_minimum_allowed_quantity',
				'blockName' => 'woocommerce-min-max/variation-min-quantity-field',
				'attributes' => array(
					'label' => __( 'Min. Quantity', 'woocommerce-min-max-quantities' ),
					'tooltip' => __(
						'Enter a minimum quantity customers can buy in a single order. This is particularly useful for items sold in larger quantities, like multipacks.',
						'woocommerce-min-max-quantities'
					),
				)
			)
		);
		$section->add_block(
			array(
				'id' => 'wc_min_max_maximum_allowed_quantity',
				'blockName' => 'woocommerce-min-max/variation-max-quantity-field',
				'attributes' => array(
					'label' => __( 'Max. Quantity', 'woocommerce-min-max-quantities' ),
					'placeholder' => __('No limit', 'woocommerce-min-max-quantities'),
					'tooltip' => __(
						'Enter a maximum quantity customers can buy in a single order. This is particularly useful for items that have limited quantity, like art or handmade goods.',
						'woocommerce-min-max-quantities'
					),
				)
			)
		);
		$section->add_block(
			array(
				'id' => 'wc_min_max_cart_exclude_from_order',
				'blockName' => 'woocommerce-min-max/variation-exclude-field',
				'attributes' => array(
					'label' => __( 'Exclude from order rules', 'woocommerce-min-max-quantities' ),
					'tooltip' => sprintf( __( 'Check to exclude this product from the total order quantity and value calculations set up in %1$sorder settings.%2$s', 'woocommerce-min-max-quantities' ), '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=products' ) . '" target="_blank">', '</a>' ),
				),
			)
		);
	}
}

// Add blocks to new product editor.
new WC_Min_Max_Quantities_Blocks();

