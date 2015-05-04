<?php

class WC_Order_Shipping_Label {

  private $order;

  function __construct($order) {
    $this->order = $order;
  }

  public function shipping_label_error() {
    return get_post_meta( $this->order->id, 'shipping_label_error', true );
  }

  private function set_shipping_label_error($error) {
    return update_post_meta( $this->order->id, 'shipping_label_error', $error );
  }

  public function tracking_number() {
    return get_post_meta( $this->order->id, 'tracking_number', true );
  }

  private function set_tracking_number($tracking_number) {
    return update_post_meta( $this->order->id, 'tracking_number', $tracking_number );
  }

  public function shipping_label_data() {
    return get_post_meta( $this->order->id, 'shipping_label_data', true );
  }

  private function set_shipping_label_data($shipping_label_data) {
    return update_post_meta( $this->order->id, 'shipping_label_data', $shipping_label_data );
  }

  public function track_package_link() {
    return "http://www.fedex.com/fedextrack/?tracknumbers=" . $this->tracking_number();
  }

  public function generate_label() {
    $service = new FedEx_Shipping_Label_Service();
    $response = $service->generate_label( $this->order );
    if ( $response ) {
      if ( ! empty( $response['error'] ) ) {
        $this->set_shipping_label_error( $response['error'] );
        $this->set_tracking_number('');
        $this->set_shipping_label_data('');
        return false;
      }
      else {
        $this->set_shipping_label_error('');
        $this->set_tracking_number( $response['tracking_number'] );
        $this->set_shipping_label_data( $response['label_data'] );
        return true;
      }
    }
    else {
      $this->set_shipping_label_error('generate_label returned empty response');
      $this->set_tracking_number('');
      $this->set_shipping_label_data('');
      return false;
    }
  }
}
