<?php
/**
 * Plugin Name: WooCommerce FedEx Shipping Labels
 * Plugin URI: #
 * Description: Print FedEx shipping labels for orders.
 * Author: Michael Schwartz
 * Author URI: #
 * Version: 2.0.0
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

require_once 'lib/class-wc-fedex-shipping-labels-admin.php';
require_once 'lib/FedEx/fedex-shipping-label-service.php';
require_once 'lib/class-woocommerce-order-shipping-label.php';

/*
 * Main FedEx shipping label class
 */
class WC_FedEx_Shipping_Labels {

	public static $name = 'fedex-shipping-labels';

	public static $text_domain = 'woocommerce-fedex-shipping-labels';

	public static $plugin_file = __FILE__;

	public static $version = '1.0.0';


	/**
	 * Set up the class, including it's hooks & filters, when the file is loaded.
	 */
	public static function init() {
		add_action( 'admin_footer', __CLASS__ . '::woocommerce_bulk_admin_footer' );
		add_action( 'load-edit.php', __CLASS__ . '::woocommerce_order_bulk_action_download' );
		add_action( 'load-edit.php', __CLASS__ . '::woocommerce_order_bulk_action_generate' );
		add_action( 'admin_notices', __CLASS__ . '::woocommerce_order_bulk_admin_notices' );
		
		// add tracking number to email
		add_action( 'woocommerce_email_after_order_table', __CLASS__ . '::woocommerce_email_after_order_table' );
		
		// adding message to edit address page
		add_action( 'woocommerce_before_template_part', __CLASS__ . '::maybe_add_message_to_edit_address_page' );
		
		// add note about chanding shipping address under shipping address inside order details
		add_action( 'woocommerce_admin_order_data_after_shipping_address', __CLASS__ . '::woocommerce_admin_order_data_after_shipping_address' );
		
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


	/**
	 * Add extra bulk action options to mark orders as complete or processing
	 *
	 * Using Javascript until WordPress core fixes: http://core.trac.wordpress.org/ticket/16031
	 *
	 * @access public
	 * @return void
	 */
	public static function woocommerce_bulk_admin_footer() {
		global $post_type;

		if ( 'shop_order' == $post_type ) {
			?>
			<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery('<option>').val('print_shipping_labels').text('Download Shipping Labels').appendTo("select[name='action']");
				jQuery('<option>').val('print_shipping_labels').text('Download Shipping Labels').appendTo("select[name='action2']");
				jQuery('<option>').val('regenerate_shipping_labels').text('Generate Shipping Labels').appendTo("select[name='action']");
				jQuery('<option>').val('regenerate_shipping_labels').text('Generate Shipping Labels').appendTo("select[name='action2']");
			});
			</script>
			<?php
		}
	}

	/**
	 * Process the bulk actions for order label printing
	 *
	 * @access public
	 * @return void
	 */
	public static function woocommerce_order_bulk_action_download() {
		$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
		$action = $wp_list_table->current_action();

		// only continue if the action is print_shipping_labels
		if ( $action != 'print_shipping_labels' ) {
			return;
		}

		$post_ids = array_map( 'absint', (array) $_REQUEST['post'] );

		$count = 0;
		$all_labels_data = '';

		foreach( $post_ids as $post_id ) {
			$order = new WC_Order( $post_id );
			$order_label = new WC_Order_Shipping_Label( $order );
			$label_data = $order_label->shipping_label_data();
			if ( empty ( $label_data ) ) {
				echo "Order #{$order->id} has no label. {$order_label->shipping_label_error()}";
				exit();
			}
			$all_labels_data .= $label_data;
		}
		
		if ( empty ( $all_labels_data ) ) {
			echo 'No shipping labels were generated!';
			exit();
		}

		header("Content-Type: application/octet-stream");
		header("Content-Disposition: attachment; filename=labels.zpl");
		echo $all_labels_data;
		exit();
	}

	/**
	 * Process the bulk actions for regenerating shipping labels
	 *
	 * @access public
	 * @return void
	 */
	public static function woocommerce_order_bulk_action_generate() {
		$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
		$action = $wp_list_table->current_action();

		// only continue if the action is print_shipping_labels
		if ( $action != 'regenerate_shipping_labels' ) {
			return;
		}

		$post_ids = array_map( 'absint', (array) $_REQUEST['post'] );
		$errors = array();

		foreach( $post_ids as $post_id ) {
			$order = new WC_Order( $post_id );
			$order_label = new WC_Order_Shipping_Label( $order );
			if ( $order_label->generate_label() ) {
				// GOOD
			}
			else {
				$errors[] = "#{$order->id} failed ({$order_label->shipping_label_error()})";
			}
		}
		
		if (empty($errors)) {
			$message = "Generated labels with no errors!";
		}
		else {
			$message = "Some labels failed to generate: " . implode(', ', $errors);
		}

		$sendback = add_query_arg( array( 'post_type' => 'shop_order', 'shipping_label_message' => urlencode( $message ) ), '' );
		wp_redirect( $sendback );
		exit;
	}

	public static function woocommerce_order_bulk_admin_notices() {
		global $post_type, $pagenow;

		if ( isset( $_REQUEST['shipping_label_message'] ) ) {
			if ( 'edit.php' == $pagenow && 'shop_order' == $post_type ) {
				echo '<div class="updated"><p>' . $_REQUEST['shipping_label_message'] . '</p></div>';
			}
		}
	}
	
	
	// After a user updates their address, generate a new shipping labels for pending orders.
	public static function maybe_add_message_to_edit_address_page( $template_name ) {
		if ( 'myaccount/form-edit-address.php' === $template_name ) {
			if ( isset( $_GET['subscription'] ) ) {
				echo '<p><em><strong>Note:</strong> Editing the shipping address on a subscription will only affect 
					future renewal orders. If you would like an existing renewal order to use 
					the new address, please contact support.</em></p>';
			}
			else {
				echo '<p><em><strong>Note:</strong> Editing your shipping address will only affect 
					future orders. If you would like an existing order to use the new address, 
					please contact support.</em></p>';
			}
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
		require_once 'lib/class-wc-shipping-address-changed-email.php';
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
