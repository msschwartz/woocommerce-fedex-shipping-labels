<?php

class WC_FedEx_Shipping_Labels_Admin {
	
	
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );
	}
	
	
	public function add_menu_item() {
    add_menu_page(
      'Fedex Shipping',
      'Fedex Shipping',
      'edit_plugins',
      'fedex-shipping-labels',
      array( $this, 'admin_page_template' )
    );
  }
  
  
  public function admin_page_template() {
  	// prepare orders
  	$args = array(
			'numberposts' => -1,
			'post_type' => 'shop_order',
			'post_status' => 'publish',
			'date_query' => array(
				'after' => array(
						'year'  => '2015',
						'month' => '4',
						'day'   => '15'
				),
				'before' => array(
						'year'  => '2015',
						'month' => '4',
						'day'   => '16'
				),
				'inclusive' => true,
			),
			'tax_query'=>array(array(
				'taxonomy' => 'shop_order_status',
				'field' => 'slug',
				'terms' => array('processing')
			))
		);
		$orders = get_posts( $args );
  	
  	// render template
  	include WCFSL_BASE_DIR . '/templates/admin/fedex_shipping_labels.php';
  }
	
	
}
