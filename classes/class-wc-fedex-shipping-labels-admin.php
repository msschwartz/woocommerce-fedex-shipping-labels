<?php

class WC_FedEx_Shipping_Labels_Admin {
	
	
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );
    add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
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
  
  
  public function admin_enqueue_scripts() {
    wp_enqueue_script( 'fedex-shipping-labels-js', WCFSL_BASE_URL . '/js/fedex-shipping-labels.js', array( 'jquery-ui-datepicker' ) );
    wp_localize_script( 'fedex-shipping-labels-js', 'wcfsl', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
    wp_enqueue_style( 'fedex-shipping-labels-css', WCFSL_BASE_URL . '/css/jquery-ui/ui-lightness/jquery-ui.css' );
    wp_enqueue_style( 'fedex-shipping-labels-css', WCFSL_BASE_URL . '/css/jquery-ui/ui-lightness/theme.css' );
    wp_enqueue_style( 'fedex-shipping-labels-css', WCFSL_BASE_URL . '/css/fedex-shipping-labels.css' );
  }
  
  
  public function admin_page_template() {
    
    // grab available products
    $args = array(
      'numberposts' => -1,
      'post_type' => 'product',
    );
    $products = get_posts( $args );
    
  	// prepare orders
    if ( isset($_GET['start_date']) && isset($_GET['end_date']) ) {
      $start_date = date_parse($_GET['start_date']);
      $end_date = date_parse($_GET['end_date']);
    
    	$args = array(
  			'numberposts' => -1,
  			'post_type' => 'shop_order',
  			'post_status' => 'publish',
  			'date_query' => array(
  				'after' => array(
  						'year'  => $start_date['year'],
  						'month' => $start_date['month'],
  						'day'   => $start_date['day']
  				),
  				'before' => array(
  						'year'  => $end_date['year'],
  						'month' => $end_date['month'],
  						'day'   => $end_date['day']
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
    }
  	
  	// render template
  	include WCFSL_BASE_DIR . '/templates/admin/fedex_shipping_labels.php';
  }
	
	
}
