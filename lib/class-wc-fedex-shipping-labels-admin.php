<?php

class WC_FedEx_Shipping_Label_Admin {

	/**
	 * The WooCommerce settings tab name
	 */
	public static $tab_name = 'fedex_shipping_labels';

	/**
	 * The prefix for subscription settings
	 */
	public static $option_prefix = 'woocommerce_fedex_shipping_labels';

	/**
	 * A translation safe screen ID for the Manage Subscriptions admin page.
	 *
	 * Set once all plugins are loaded to apply the 'woocommerce_subscriptions_screen_id' filter.
	 */
	public static $admin_screen_id;

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 */
	public static function init() {
		add_action( 'admin_menu', __CLASS__ . '::add_menu_item' );

		add_action( 'woocommerce_settings_tabs_fedex_shipping_labels', __CLASS__ . '::fedex_shipping_labels_settings_page' );

		add_action( 'woocommerce_update_options_' . self::$tab_name, __CLASS__ . '::update_fedex_shipping_labels_settings' );
	}
	
	
	public static function add_menu_item() {
    add_menu_page(
      'Fedex Shipping',
      'Fedex Shipping',
      'edit_plugins',
      'fedex-shipping-labels',
      null,
      null,
      58
    );
  }
	

	/**
	 * Add the FedEx Shipping Labels settings tab to the WooCommerce settings tabs array.
	 *
	 * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the FedEx Shipping tab.
	 * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the FedEx Shipping tab.
	 */
	public static function add_fedex_shipping_labels_settings_tab( $settings_tabs ) {

		$settings_tabs[self::$tab_name] = 'FedEx Shipping';

		return $settings_tabs;
	}

	/**
	 * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
	 *
	 * @uses woocommerce_admin_fields()
	 * @uses self::get_settings()
	 */
	public static function fedex_shipping_labels_settings_page() {
		woocommerce_admin_fields( self::get_settings() );
	}

	/**
	 * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
	 *
	 * @uses woocommerce_update_options()
	 * @uses self::get_settings()
	 */
	public static function update_fedex_shipping_labels_settings() {
		woocommerce_update_options( self::get_settings() );
	}

	/**
	 * Get all the settings for the FedEx Shipping Labels extension in the format required by the @see woocommerce_admin_fields() function.
	 *
	 * @return array Array of settings in the format required by the @see woocommerce_admin_fields() function.
	 */
	public static function get_settings() {
		global $woocommerce;

		return array(

			// API Configuration Section
			array(
				'name'     => 'API Configuration',
				'type'     => 'title',
				'desc'     => '',
				'id'       => self::$option_prefix . '_api_configuration_options'
			),

			array(
				'name'     => 'Enable Test Mode',
				'type'     => 'checkbox',
				'desc'     => '',
				'id'       => self::$option_prefix . '_api_test_mode',
				'default'  => 'yes'
			),

			array(
				'name'     => 'Test API Key',
				'desc'     => '',
				'tip'      => '',
				'id'       => self::$option_prefix . '_test_api_key',
				'css'      => 'min-width:150px;',
				'std'      => '',
				'type'     => 'text',
			),

			array(
				'name'     => 'Test API Password',
				'desc'     => '',
				'tip'      => '',
				'id'       => self::$option_prefix . '_test_api_password',
				'css'      => 'min-width:150px;',
				'std'      => '',
				'type'     => 'text',
			),

			array(
				'name'     => 'Test Account #',
				'desc'     => '',
				'tip'      => '',
				'id'       => self::$option_prefix . '_test_api_account_number',
				'css'      => 'min-width:150px;',
				'std'      => '',
				'type'     => 'text',
			),

			array(
				'name'     => 'Test Meter #',
				'desc'     => '',
				'tip'      => '',
				'id'       => self::$option_prefix . '_test_api_meter_number',
				'css'      => 'min-width:150px;',
				'std'      => '',
				'type'     => 'text',
			),

			array(
				'name'     => 'API Key',
				'desc'     => '',
				'tip'      => '',
				'id'       => self::$option_prefix . '_api_key',
				'css'      => 'min-width:150px;',
				'std'      => '',
				'type'     => 'text',
			),

			array(
				'name'     => 'API Password',
				'desc'     => '',
				'tip'      => '',
				'id'       => self::$option_prefix . '_api_password',
				'css'      => 'min-width:150px;',
				'std'      => '',
				'type'     => 'text',
			),

			array(
				'name'     => 'Account #',
				'desc'     => '',
				'tip'      => '',
				'id'       => self::$option_prefix . '_api_account_number',
				'css'      => 'min-width:150px;',
				'std'      => '',
				'type'     => 'text',
			),

			array(
				'name'     => 'Meter #',
				'desc'     => '',
				'tip'      => '',
				'id'       => self::$option_prefix . '_api_meter_number',
				'css'      => 'min-width:150px;',
				'std'      => '',
				'type'     => 'text',
			),

			array( 'type' => 'sectionend', 'id' => self::$option_prefix . '_api_configuration_options' ),


			// Shipper Address Section
			array(
				'name'     => 'Shipper Address',
				'type'     => 'title',
				'desc'     => '',
				'id'       => self::$option_prefix . '_shipper_address_options'
			),

			array(
				'name'     => 'Person Name',
				'desc'     => '',
				'tip'      => '',
				'id'       => self::$option_prefix . '_shipper_person_name',
				'css'      => 'min-width:150px;',
				'std'      => '',
				'type'     => 'text',
			),

			array(
				'name'     => 'Company Name',
				'desc'     => '',
				'tip'      => '',
				'id'       => self::$option_prefix . '_shipper_company_name',
				'css'      => 'min-width:150px;',
				'std'      => '',
				'type'     => 'text',
			),

			array(
				'name'     => 'Phone Number',
				'desc'     => '',
				'tip'      => '',
				'id'       => self::$option_prefix . '_shipper_phone_number',
				'css'      => 'min-width:150px;',
				'std'      => '',
				'type'     => 'text',
			),

			array(
				'name'     => 'Address 1',
				'desc'     => '',
				'tip'      => '',
				'id'       => self::$option_prefix . '_shipper_address1',
				'css'      => 'min-width:150px;',
				'std'      => '',
				'type'     => 'text',
			),

			array(
				'name'     => 'Address 2',
				'desc'     => '',
				'tip'      => '',
				'id'       => self::$option_prefix . '_shipper_address2',
				'css'      => 'min-width:150px;',
				'std'      => '',
				'type'     => 'text',
			),

			array(
				'name'     => 'City',
				'desc'     => '',
				'tip'      => '',
				'id'       => self::$option_prefix . '_shipper_city',
				'css'      => 'min-width:150px;',
				'std'      => '',
				'type'     => 'text',
			),

			array(
				'name'     => 'State',
				'desc'     => '',
				'tip'      => '',
				'id'       => self::$option_prefix . '_shipper_state',
				'css'      => 'min-width:150px;',
				'std'      => '',
				'type'     => 'text',
			),

			array(
				'name'     => 'Postal',
				'desc'     => '',
				'tip'      => '',
				'id'       => self::$option_prefix . '_shipper_postal',
				'css'      => 'min-width:150px;',
				'std'      => '',
				'type'     => 'text',
			),

			array(
				'name'     => 'Country',
				'desc'     => '',
				'tip'      => '',
				'id'       => self::$option_prefix . '_shipper_country',
				'css'      => 'min-width:150px;',
				'std'      => 'US',
				'type'     => 'text',
			),

			array( 'type' => 'sectionend', 'id' => self::$option_prefix . '_shipper_address_options' )
		);
	}
}

WC_FedEx_Shipping_Label_Admin::init();
