<?php

class FedEx_Shipping_Label_Service {

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
		set_time_limit(30); // prevent timeout in batch processing
		ini_set("soap.wsdl_cache_enabled", "0");
		try {
			$client = new SoapClient( $this->wsdl_path() );
			
			// creating request object for this item
			$request = $this->create_request( $order );
			
			// send request object to FedEx web service
			$response = $client->processShipment( $request );
			
			// was request successful?
			if ($response->HighestSeverity != 'FAILURE' && $response->HighestSeverity != 'ERROR') {
								
				return array(
					'label_data' => $response->CompletedShipmentDetail->CompletedPackageDetails->Label->Parts->Image,
					'tracking_number' => $this->get_ground_tracking_number($response->CompletedShipmentDetail->CompletedPackageDetails->TrackingIds)
				);
				
			}
			else {
				
				$this->write_to_log('Unable to generate label: ' . "\n" . print_r( $response->Notifications, true ) );
				return array(
					'error' => $this->get_error_messages( $response->Notifications ) 
				);
				
			}
		}
		catch (SoapFault $exception) {
			
			$this->write_to_log( 'SoapFault: ' . $exception->getMessage() );
			return array(
				'error' => 'SoapFault: ' . $exception->getMessage()
			);
			
		}
		catch (Exception $exception) {
			
			$this->write_to_log( $exception->getMessage() );
			return array(
				'error' => 'Exception: ' . $exception->getMessage()
			);
			
		}
	}
	
	
	
	private function wsdl_path() {
		if ( ENVIRONMENT == 'production' ) {
			return __DIR__ . '/' . 'ShipService_v15.prod.wsdl';
		}
		else {
			return __DIR__ . '/' . 'ShipService_v15.test.wsdl';
		}
	}
	


	/*
	 * Create the soap request element
	 */
	private function create_request($order) {
		$request = array();
		$request['WebAuthenticationDetail'] = array(
			'UserCredential' =>array(
				'Key' => FEDEX_API_KEY, 
				'Password' => FEDEX_API_PASSWORD
			)
		);
		$request['ClientDetail'] = array(
			'AccountNumber' => FEDEX_ACCOUNT_NUMBER, 
			'MeterNumber' => FEDEX_METER_NUMBER
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
			'DropoffType' => 'REGULAR_PICKUP', 
			'ServiceType' => $this->service_type( $order ), 
			'PackagingType' => 'YOUR_PACKAGING',
			'Shipper' => $this->get_shipper(),
			'Recipient' => $this->get_recipient( $order ),
			'ShippingChargesPayment' => $this->get_shipping_charges_payment(),
			'LabelSpecification' => $this->get_label_specification(), 
			'RateRequestTypes' => array('ACCOUNT'), 
			'PackageCount' => 1,
			'PackageDetail' => 'INDIVIDUAL_PACKAGES',
			'RequestedPackageLineItems' => array(
				'0' => $this->get_package_line_item( $order )
			)
		);
		
		if ( $request['RequestedShipment']['ServiceType'] == 'SMART_POST' ) {
			$request['RequestedShipment']['SmartPostDetail'] = $this->get_smart_post_detail();
		}

		return $request;
	}
	
	
	private function service_type( $order ) {
		if ( $order->get_item_count() == 1 ) {
			return 'SMART_POST';
		}
		else {
			return 'GROUND_HOME_DELIVERY';
		}
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
					'AccountNumber' => FEDEX_ACCOUNT_NUMBER,
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
			'HubId' => FEDEX_SMART_POST_HUB_ID
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
	private function get_package_line_item( $order ) {
		$quantity = $order->get_item_count();
		$packageLineItem = array(
			'SequenceNumber' => 1,
			'GroupPackageCount' => 1,
			'Weight' => array(
				'Value' => CRATE_WEIGHT * $quantity,
				'Units' => 'LB'
			),
			'CustomerReferences' => array(
				'0' => array(
					'CustomerReferenceType' => 'INVOICE_NUMBER', 
					'Value' => $order->id . '-' . 'QTY' . $quantity
				)
			),
			'SpecialServicesRequested' => array()
		);
		return $packageLineItem;
	}
	
	
	// grab ground tracking number from tracking ids
	private function get_ground_tracking_number($trackingIds) {
		if ( ! is_array( $trackingIds ) ) {
			$trackingIds = array( $trackingIds );
		}
		foreach ( $trackingIds as $key => $value ) {
			if ( $value->TrackingIdType == "GROUND" || $value->TrackingIdType == "FEDEX" ) {
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
