<?php
/**
 * Plugin Name: WooCommerce FedEx Shipping Labels
 * Plugin URI: #
 * Description: Print FedEx shipping labels for orders.
 * Author: Michael Schwartz
 * Author URI: #
 * Version: 3.1
 */

// Define some constants
if ( ! defined('WCFSL_BASE_URL') ) {
	define('WCFSL_BASE_URL', plugin_dir_url(__FILE__));
}
if ( ! defined('WCFSL_BASE_DIR') ) {
	define('WCFSL_BASE_DIR', dirname(__FILE__));
}

/**
 * Check if WooCommerce is active
 **/
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}

require_once 'classes/class-wc-fedex-shipping-labels-admin.php';
require_once 'lib/FedEx/fedex-shipping-label-service.php';
require_once 'lib/class-woocommerce-order-shipping-label.php';

/*
 * Main FedEx shipping label class
 */
class WC_FedEx_Shipping_Labels {

	public static $name = 'fedex-shipping-labels';

	public static $text_domain = 'woocommerce-fedex-shipping-labels';

	public static $plugin_file = __FILE__;

	public static $version = '3.1';


	/**
	 * Set up the class, including it's hooks & filters, when the file is loaded.
	 */
	public static function init() {
		$wcFedexShippingLabelsAdmin = new WC_FedEx_Shipping_Labels_Admin();
				
		// add tracking number to email
		add_action( 'woocommerce_email_after_order_table', __CLASS__ . '::woocommerce_email_after_order_table' );
		
		// adding message to edit address page
		add_action( 'woocommerce_before_edit_address_form_shipping', __CLASS__ . '::woocommerce_before_edit_address_form_shipping' );
		
		// add note about chanding shipping address under shipping address inside order details
		add_action( 'woocommerce_admin_order_data_after_shipping_address', __CLASS__ . '::woocommerce_admin_order_data_after_shipping_address' );
		
		// update pending orders when user edits a subscriptions shipping address
		add_action( 'woocommerce_customer_save_address', __CLASS__ . '::maybe_update_subscription_order_addresses', 11, 3 );
		
		// validate shipping state
		add_action( 'woocommerce_after_checkout_validation', __CLASS__ . '::woocommerce_after_checkout_validation');
		
		// add custom admin order actions
		add_filter( 'woocommerce_order_actions', __CLASS__ . '::woocommerce_order_actions' );
		
		// generate/download shipping label action handlers
		add_action( 'woocommerce_order_action_generate_shipping_label', __CLASS__ . '::order_action_generate_shipping_label' );
		add_action( 'woocommerce_order_action_download_shipping_label', __CLASS__ . '::order_action_download_shipping_label' );
		
		// custom shipping address changed email
		add_filter( 'woocommerce_email_classes', __CLASS__ . '::add_wc_shipping_address_changed_email' );
		add_action( 'woocommerce_order_action_send_shipping_address_changed_email', __CLASS__ . '::order_action_send_shipping_address_changed_email' );
	}
	
	
	// Add note to the edit shipping address page.
	public static function woocommerce_before_edit_address_form_shipping( $template_name ) {
		if ( ! isset( $_GET['subscription'] ) ) {
			echo '<p><em><strong>Note:</strong> Editing your shipping address will only affect 
				future orders. If you would like to change an existing order, please contact support.</em></p>';
		}
	}
	
	
	// Add tracking number to order completed customer email
	public static function woocommerce_email_after_order_table($order) {
		if ( $order->status == 'completed' ) {
			$order_label = new WC_Order_Shipping_Label( $order );
			$tracking_number = $order_label->tracking_number();
			$tracking_url = $order_label->track_package_link();
			if ( ! empty( $tracking_number ) ) {
				echo '<h2>Tracking Information</h2>';
				echo '<p><strong>Service: </strong>FedEx</p>';
				echo '<p>';
				echo '  <strong>Tracking Number: </strong>' . '<a href="' . $tracking_url . '">' . $tracking_number . '</a>';
				echo '  <br />';
				echo '  <i>*please allow 48 hours for this tracking number to be processed*</i>';
				echo '</p>';
			}
		}
	}
	
	
	// Message for admin "Order Details" box
	public static function woocommerce_admin_order_data_after_shipping_address() {
		echo '<p><em>Note: If you are updating the shipping address on a subscription, 
			you must update the "Initial Order" if all future orders should use the new address.</em></p>';
	}
	
	
	
	// If user changes a subscription shipping address and an existing order is pending
	// update that order's shipping address.
	public function maybe_update_subscription_order_addresses( $user_id, $load_address ) {
		global $woocommerce, $wp;

		if ( ! WC_Subscriptions_Manager::user_has_subscription( $user_id ) ) {
			return;
		}
		
		if ( $load_address != 'shipping' ) {
			return;
		}

		$subscription_ids = array();
		
		if ( isset( $_POST['update_all_subscriptions_addresses'] ) ) {

			$users_subscriptions = WC_Subscriptions_Manager::get_users_subscriptions( $user_id );

			foreach ( $users_subscriptions as $subscription ) {
				array_push( $subscription_ids, $subscription['order_id'] );
			}

		} elseif ( isset( $_POST['update_subscription_address'] ) ) {

			$subscription = WC_Subscriptions_Manager::get_subscription( $_POST['update_subscription_address'] );

			// Update the address only if the user actually owns the subscription
			if ( ! empty( $subscription ) ) {
				array_push( $subscription_ids, $subscription['order_id'] );
			}

		}
		
		if ( count( $subscription_ids ) > 0 ) {
			
			$base_order = $order = wc_get_order( $subscription_ids[0] );
		
			$args = array(
				'numberposts' => -1,
				'post_type' => 'shop_order',
				'post_status' => 'publish',
				'tax_query' => array(
					array(
						'taxonomy' => 'shop_order_status',
						'field' => 'slug',
						'terms' => array('processing')
					),
				),
				'meta_query' => array(
					array(
						'key'     => '_customer_user',
						'value'   => $user_id,
					),
					array(
						'key'     => '_original_order',
						'value'   => $subscription_ids,
					),
				),
			);
			$posts = get_posts( $args );
			
			$address = array(
				'first_name' => $base_order->shipping_first_name,
				'last_name' => $base_order->shipping_last_name,
				'address_1' => $base_order->shipping_address_1,
				'address_2' => $base_order->shipping_address_2,
				'city' => $base_order->shipping_city,
				'state' => $base_order->shipping_state,
				'postcode' => $base_order->shipping_postcode,
				'country' => $base_order->shipping_country,
			);
			
			foreach ($posts as $post) {
				$order = wc_get_order( $post->ID );
				$order->set_address( $address, 'shipping' );
			}
			
		}
		
		
	}
	
	
	
	// Check if user entered valid shipping state
	public static function woocommerce_after_checkout_validation() {
		global $wpdb, $current_user;
		
		// grab shipping address		
		$ship_state = WC()->customer->get_shipping_state();
		if ( empty($ship_state) ) {
			$ship_state = WC()->customer->get_state();
		}
		
		// smart post only allows 48 states
		$allowed_states = array(
			"AL", "AZ", "AR", "CA", "CO", "CT", "DC", "DE", "FL", "GA",
			"ID",	"IL",	"IN",	"IA",	"KS",	"KY",	"LA",	"ME",	"MD",	"MA",
			"MI",	"MN",	"MS",	"MO",	"MT",	"NE",	"NV",	"NH",	"NJ",	"NM",
			"NY",	"NC",	"ND",	"OH",	"OK",	"OR",	"PA",	"RI",	"SC",	"SD",
			"TN",	"TX",	"UT",	"VT",	"VA",	"WA",	"WV",	"WI",	"WY"
		);
    if ( ! in_array( $ship_state, $allowed_states ) ) {
      wc_add_notice( 'We do not support the shipping state at this time.', 'error' );
    }
	}
	
	
	// adding options to admin view order screen
	public static function woocommerce_order_actions( $options ) {
		$options['generate_shipping_label'] = 'Generate Shipping Label';
		$options['download_shipping_label'] = 'Download Shipping Label';
		$options['send_shipping_address_changed_email'] = 'Send Shipping Address Changed Email';
		return $options;
	}
	
	
	public static function order_action_generate_shipping_label( $order ) {
		$order_label = new WC_Order_Shipping_Label( $order );
		if ( ! $order_label->generate_label() ) {
			echo "Generation failed. ({$order_label->shipping_label_error()})";
			exit();
		}
	}
	
	
	public static function order_action_download_shipping_label( $order ) {
		$order_label = new WC_Order_Shipping_Label( $order );
		$label_data = $order_label->shipping_label_data();

		header("Content-Type: application/octet-stream");
		header("Content-Disposition: attachment; filename=label.zpl");
		echo $label_data;
		exit();
	}
	
	
	// custom email when admin changes customer's shipping address
	public static function add_wc_shipping_address_changed_email( $email_classes ) {
		require_once 'classes/class-wc-shipping-address-changed-email.php';
    $email_classes['WC_Shipping_Address_Changed_Email'] = new WC_Shipping_Address_Changed_Email();
    return $email_classes;
	}
	
	
	public static function order_action_send_shipping_address_changed_email( $order ) {
		$mailer = WC()->mailer();
		$mails = $mailer->get_emails();
    $mail = $mails['WC_Shipping_Address_Changed_Email'];
    $mail->trigger( $order );
	}
		
	
}

WC_FedEx_Shipping_Labels::init(); 
