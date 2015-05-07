<?php

class WC_FedEx_Shipping_Labels_Admin {
	
	
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );
    add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
    
    add_action( 'wp_ajax_generate_shipping_label', array( $this, 'wp_ajax_generate_shipping_label' ) );
    add_action( 'wp_ajax_get_label_print_commands', array( $this, 'wp_ajax_get_label_print_commands' ) );
    add_action( 'wp_ajax_mark_order_complete', array( $this, 'wp_ajax_mark_order_complete' ) );
	}
	
	
	public function add_menu_item() {
    add_menu_page(
      'Fedex Shipping',
      'Fedex Shipping',
      'edit_plugins',
      'fedex-shipping-labels',
      array( $this, 'admin_page_template' ),
      null,
      '58.9'
    );
  }
  
  
  public function admin_enqueue_scripts() {
    wp_enqueue_script( 'fedex-shipping-labels-js', WCFSL_BASE_URL . '/js/fedex-shipping-labels.js', array( 'jquery-ui-datepicker' ), WC_FedEx_Shipping_Labels::$version );
    wp_localize_script( 'fedex-shipping-labels-js', 'wcfsl', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
    wp_enqueue_style( 'fedex-shipping-labels-jquery-ui', WCFSL_BASE_URL . '/css/jquery-ui/ui-lightness/jquery-ui.css' );
    wp_enqueue_style( 'fedex-shipping-labels-jquery-ui-theme', WCFSL_BASE_URL . '/css/jquery-ui/ui-lightness/theme.css' );
    wp_enqueue_style( 'fedex-shipping-labels-css', WCFSL_BASE_URL . '/css/fedex-shipping-labels.css', null, WC_FedEx_Shipping_Labels::$version );
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
  			'post_status' => array( 'wc-processing' ),
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
  			)
  		);
  		$orders = get_posts( $args );
    }
  	
  	// render template
  	include WCFSL_BASE_DIR . '/templates/admin/fedex_shipping_labels.php';
  }
  
  
  public function wp_ajax_generate_shipping_label() {
    $order = wc_get_order( $_POST['order_id'] );
    if ( $order ) {
      $order_label = new WC_Order_Shipping_Label( $order );
      if ( $order_label->generate_label() ) {
        wp_send_json( true );
        wp_die();
      }
    }
    wp_send_json( false );
    wp_die();
  }
  
  
  public function wp_ajax_get_label_print_commands() {
    $printCommands = "";
    $order_ids = explode(',', $_POST['order_ids'] );
    if ( count($order_ids) > 0 ) {
      foreach ($order_ids as $order_id) {
        $meta = get_post_meta( $order_id );
        if ( empty($meta['shipping_label_data'][0]) ) {
          wp_send_json( array( 'error' => 'Shipping label was empty for order: ' . $order->ID ) );
          wp_die();
        }
        $printCommands .= $meta['shipping_label_data'][0];
      }
    }
    else {
      wp_send_json( array( 'error' => 'Order Ids was blank' ) );
      wp_die();
    }
    wp_send_json( array( 'print_commands' => $printCommands ) );
    wp_die();
  }
  
  
  public function wp_ajax_mark_order_complete() {
    $order = wc_get_order( $_POST['order_id'] );
    if ( $order ) {
      $order->update_status( 'completed', 'Order completed from FedEx Shipping' );
      wp_send_json( true );
      wp_die();
    }
    wp_send_json( false );
    wp_die();
  }
	
	
}
