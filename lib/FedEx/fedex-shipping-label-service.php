<?php

class FedEx_Shipping_Label_Service {

	const WSDL_PRODUCTION = 'ShipService_v15.prod.wsdl';
	const WSDL_TESTING = 'ShipService_v15.test.wsdl';
	const DROP_OFF_TYPE = 'REGULAR_PICKUP'; 
	const SERVICE_TYPE = 'SMART_POST';
	const PACKAGING_TYPE = 'YOUR_PACKAGING';

	private $wsdl;
	private $api_key;
	private $api_password;
	private $api_meter_number;
	private $api_account_number;
	private $hub_id;

	function __construct() {
		// Load the settings.
		$this->init_settings();
	}

	private function init_settings() {
		if ( ENVIRONMENT == 'production' ) {
			$this->wsdl = self::WSDL_PRODUCTION;
		}
		else {
			$this->wsdl = self::WSDL_TESTING;
		}
		
		$this->api_key = FEDEX_API_KEY;
		$this->api_password = FEDEX_API_PASSWORD;
		$this->api_meter_number = FEDEX_METER_NUMBER;
		$this->api_account_number = FEDEX_ACCOUNT_NUMBER;
		$this->hub_id = FEDEX_SMART_POST_HUB_ID;
	}

	/*
	 * Main method for generating the shipping label.
	 *
	 * $param $order WC_Order
	 *
	 * returns
	 * array(
	 *   'error' => string,
	 *   'tracking_number' => string,
	 *   'label_data' => string
	 * )
	 */
	public function generate_label($order) {
		ini_set("soap.wsdl_cache_enabled", "0");
		try {
			$client = new SoapClient( __DIR__ . '/' . $this->wsdl );

			// for each item, create label
			$label_data = '';
			$tracking_number = null;
			$number_of_packages = $order->get_item_count();
			$item_counter = 1;
			$items = $order->get_items();

			// iterate over all order items
			foreach($items as $item) {
				// some items can have multiple quantities
				if ( ! empty( $item['qty'] ) ) {
					$item_qty = $item['qty'];
				}
				else {
					$item_qty = 1;
				}

				// get product for this order item
				$product = get_product( $item['variation_id'] ? $item['variation_id'] : $item['product_id'] );

				// print labels for all items 
				for($i = 0; $i < $item_qty; $i++) {
					set_time_limit(30);
					
					// creating request object for this item
					$request = $this->create_request($order, $product);
					
					// send request object to FedEx web service
					$response = $client->processShipment( $request );
					
					// was request successful?
					if ($response->HighestSeverity != 'FAILURE' && $response->HighestSeverity != 'ERROR') {
						// if this is the first item, save tracking number
						if ($item_counter == 1) {
							$tracking_number = $this->get_ground_tracking_number($response->CompletedShipmentDetail->CompletedPackageDetails->TrackingIds);
						}

						// append this label to the all labels data string
						$label_data .= $response->CompletedShipmentDetail->CompletedPackageDetails->Label->Parts->Image;
						$label_data .= "\n";
					}
					else {
						$this->write_to_log('Unable to generate label: ' . "\n" . print_r( $response->Notifications, true ) );
						return array('error' => $this->get_error_messages( $response->Notifications ) );
					}

					$item_counter++;
				}
			}

			// successful generation
			return array(
				'label_data' => $label_data,
				'tracking_number' => $tracking_number
			);
		}
		catch (SoapFault $exception) {
			$this->write_to_log( 'SoapFault: ' . $exception->getMessage() );
			return array('error' => 'SoapFault: ' . $exception->getMessage());
		}
		catch (Exception $exception) {
			$this->write_to_log( $exception->getMessage() );
			return array('error' => 'Exception: ' . $exception->getMessage());
		}
	}

	/*
	 * Create the soap request element
	 */
	private function create_request($order, $product) {
		$request = array();
		$request['WebAuthenticationDetail'] = array(
			'UserCredential' =>array(
				'Key' => $this->api_key, 
				'Password' => $this->api_password
			)
		);
		$request['ClientDetail'] = array(
			'AccountNumber' => $this->api_account_number, 
			'MeterNumber' => $this->api_meter_number
		);
		$request['TransactionDetail'] = array('CustomerTransactionId' => '*** Ground Domestic Shipping Request using PHP ***');
		$request['Version'] = array(
			'ServiceId' => 'ship', 
			'Major' => '15', 
			'Intermediate' => '0', 
			'Minor' => '0'
		);
		$request['RequestedShipment'] = array(
			'ShipTimestamp' => date('c'),
			'DropoffType' => self::DROP_OFF_TYPE, 
			'ServiceType' => self::SERVICE_TYPE, 
			'PackagingType' => self::PACKAGING_TYPE,
			'Shipper' => $this->get_shipper(),
			'Recipient' => $this->get_recipient( $order ),
			'ShippingChargesPayment' => $this->get_shipping_charges_payment(),
			'SmartPostDetail' => $this->get_smart_post_detail(),
			'LabelSpecification' => $this->get_label_specification(), 
			'RateRequestTypes' => array('ACCOUNT'), 
			'PackageCount' => 1, // force single package for smartpost
			'PackageDetail' => 'INDIVIDUAL_PACKAGES',                                        
			'RequestedPackageLineItems' => array(
				'0' => $this->get_package_line_item( $order, $product )
			)
		);

		return $request;
	}

	/*
	 * Grab shipper info from settings
	 */
	private function get_shipper() {
		$shipper = array(
			'Contact' => array(
				'PersonName' => FEDEX_SHIPPER_PERSON_NAME,
				'CompanyName' => FEDEX_SHIPPER_COMPANY_NAME,
				'PhoneNumber' => FEDEX_SHIPPER_PHONE_NUMBER
			),
			'Address' => array(
				'StreetLines' => array(FEDEX_SHIPPER_ADDRESS_1, FEDEX_SHIPPER_ADDRESS_2),
				'City' => FEDEX_SHIPPER_CITY,
				'StateOrProvinceCode' => FEDEX_SHIPPER_STATE,
				'PostalCode' => FEDEX_SHIPPER_POSTAL,
				'CountryCode' => FEDEX_SHIPPER_COUNTRY
			)
		);
		return $shipper;
	}

	/*
	 * Extract the shipping address from the order.
	 */
	private function get_recipient($order) {
		$recipient = array(
			'Contact' => array(
				'PersonName' => $order->shipping_first_name . ' ' . $order->shipping_last_name,
				'CompanyName' => $order->company_name,
				'PhoneNumber' => $order->billing_phone
			),
			'Address' => array(
				'StreetLines' => array($order->shipping_address_1, $order->shipping_address_2),
				'City' => $order->shipping_city,
				'StateOrProvinceCode' => $order->shipping_state,
				'PostalCode' => $order->shipping_postcode,
				'CountryCode' => $order->shipping_country,
				'Residential' => true
			)
		);
		return $recipient;	
	}

	/*
	 * Return the shipping charges array
	 */
	private function get_shipping_charges_payment() {
		$shippingChargesPayment = array(
			'PaymentType' => 'SENDER',
			'Payor' => array(
				'ResponsibleParty' => array(
					'AccountNumber' => $this->api_account_number,
					'Contact' => null,
					'Address' => array(
						'CountryCode' => 'US'
					)
				)
			)
		);
		return $shippingChargesPayment;
	}
	
	
	// return the smart post config
	private function get_smart_post_detail() {
		$smartPostDetail = array(
			'Indicia' => 'PARCEL_SELECT',
			'AncillaryEndorsement' => 'CARRIER_LEAVE_IF_NO_RESPONSE',
			'SpecialServices' => 'USPS_DELIVERY_CONFIRMATION',
			'HubId' => $this->hub_id
		);
		return $smartPostDetail;
	}
	

	/*
	 * Return Label Specification
	 */
	private function get_label_specification(){
		$labelSpecification = array(
			'LabelFormatType' => 'COMMON2D', // valid values COMMON2D, LABEL_DATA_ONLY
			'ImageType' => 'ZPLII',  // valid values DPL, EPL2, PDF, ZPLII and PNG
			'LabelStockType' => 'STOCK_4X6' // 'PAPER_4X6' 'STOCK_4X6'
		);
		return $labelSpecification;
	}

	/*
	 * Return the configured shipping box.
	 */
	private function get_package_line_item($order, $product) {
		$product_weight = $product->get_weight();
		if ( empty($product_weight) || empty($product->length) || empty($product->width) || empty($product->height) ) {
			throw new Exception("product is missing shipping information");
		}

		$packageLineItem = array(
			'SequenceNumber' => 1,
			'GroupPackageCount' => 1,
			'Weight' => array(
				'Value' => $product->get_weight(),
				'Units' => 'LB'
			),
			'Dimensions' => array(
				'Length' => $product->length,
				'Width' => $product->width,
				'Height' => $product->height,
				'Units' => 'IN'
			),
			'CustomerReferences' => array(
				'0' => array(
					'CustomerReferenceType' => 'INVOICE_NUMBER', 
					'Value' => $order->id
				)
			),
			'SpecialServicesRequested' => array()
		);
		return $packageLineItem;
	}
	
	
	// grab ground tracking number from tracking ids
	private function get_ground_tracking_number($trackingIds) {
		foreach ($trackingIds as $key => $value) {
			if ($value->TrackingIdType == "GROUND") {
				return $value->TrackingNumber;
			}
		}
		return "";
	}
	

	/*
	 * Write message to the log file.
	 */
	private function write_to_log($message) {
		if ($logfile = fopen( __DIR__  . '/log/log.txt', "a")) {
			fwrite($logfile, date("D M j G:i:s T Y") . ': ' . $message . "\n\n");
		}
	}

	private function get_error_messages($notes) {
		$error_messages = array();
		foreach($notes as $note_key => $note){
			if ( is_string($note) ) {
				if ( $note_key == 'Message' ) {  
	      	$error_messages[] = $note;
	      }
      }
			else {
				foreach ($note as $key => $msg) {
					if ( $key == 'Message' ) {    
			      $error_messages[] = $msg;
		      }
		    }
		  }
		}
		return implode($error_messages, ', ');
	}
}
