<?php 

if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Shipping_Address_Changed_Email extends WC_Email {
  
  
  public function __construct() {

    // set ID, this simply needs to be a unique name
    $this->id = 'wc_shipping_address_changed';

    // this is the title in WooCommerce Email settings
    $this->title = 'Shipping Address Changed';

    // this is the description in WooCommerce email settings
    $this->description = 'When admin changes a shipping address, they can send this email notification to the customer.';

    // these are the default heading and subject lines that can be overridden using the settings
    $this->subject = 'Mitten Crate Shipping Updated for order {order_number}';
    $this->heading = 'Hello!';

    // Call parent constructor to load any other defaults not explicity defined here
    parent::__construct();
    
  }


  /**
   * Determine if the email should actually be sent and setup email merge variables
   *
   * @since 0.1
   * @param int $order_id
   */
  public function trigger( $order ) {

    // bail if no order ID is present
    if ( ! $order )
      return;

    // setup order object
    $this->object = $order;
    $this->recipient = $this->object->billing_email;

    // replace variables in the subject/headings
    $this->find[] = '{order_number}';
    $this->replace[] = $this->object->get_order_number();
    
    if ( ! $this->is_enabled() || ! $this->get_recipient() )
      return;
    
    $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
  }
  
  /**
   * get_content_html function.
   *
   * @access public
   * @return string
   */
  function get_content_html() {
    ob_start();
    do_action( 'woocommerce_email_header', $this->get_heading() );
    
    echo '<p>We are notifying you of the shipping changes made to order ' . $this->object->get_order_number() . '.</p>';
    
    echo '<p>';
    echo 'Our records have been updated and will ship to this address:<br />';
    echo wp_kses( $this->object->get_formatted_shipping_address(), array( 'br' => array() ) );
    echo '</p>';
    
    echo '<p>We thank you for choosing Mitten Crate and supporting Michigan.</p>';
    
    echo '<p>Cheers,<br />The Mitten Crate Team</p>';
    
    echo '<p>***Please do not reply as this email is an automated response to notify you of account shipping changed. Replies to this email will not be received.***</p>';
    
    do_action( 'woocommerce_email_footer' );
    return ob_get_clean();
  }

  /**
   * get_content_plain function.
   *
   * @access public
   * @return string
   */
  function get_content_plain() {
    return "";
  }

}
