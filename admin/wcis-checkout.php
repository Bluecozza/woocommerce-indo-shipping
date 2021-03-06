<?php
/*
  Rearrange checkout field and modify the data before saving
*/
class WCIS_Checkout {

  function __construct() {
    // TODO: this should be in theme, not in plugin
    // add_filter('woocommerce_default_address_fields', array($this, 'reorder_fields') );

    add_action('woocommerce_checkout_update_user_meta', array($this, 'update_user_meta'), 99, 2);
    add_action('woocommerce_checkout_update_order_meta', array($this, 'update_order_meta'), 99, 2);

    add_filter('woocommerce_cart_shipping_packages', array($this, 'parse_shipping_package') );
  }

  /*
    Reorder Billing and Shipping filds in Checkout page.

    @filter woocommerce_default_address_fields

    @param array $fields - The current list of fields
    @return array - The ordered list of fields
  */
  function reorder_fields($fields) {
    $fields['state']['priority'] = 42;
    $fields['city']['priority'] = 44;
    $fields['postcode']['priority'] = 46;

    return $fields;
  }

  /*
    Clean the User's city field from [id] notation used when calculating shipping cost. Only run when it's not Guest.

    @filter woocommerce_checkout_update_user_meta
    @param int $user_id - The customer that bought this
    @param array $posted - The data posted
  */
  function update_user_meta($user_id, $posted) {
    $city = $this->_clean_city_field($posted['billing_city']);
    update_user_meta($user_id, 'billing_city', $city);

    // if shipping city is passed on
    if(isset($posted['shipping_city']) ) {
      $city = $this->_clean_city_field($posted['shipping_city']);
    }
    update_user_meta($user_id, 'shipping_city', $city);
  }

  /*
    Clean the Order's city field from [id] notation used when calculating shipping cost.

    @filter woocommerce_checkout_update_order_meta
    @param int $order_id - The order that just created
    @param array $posted - The data posted
  */
  function update_order_meta($order_id, $posted) {
    $city = $this->_clean_city_field($posted['billing_city']);
    update_post_meta($order_id, '_billing_city', $city);

    // if shipping city is passed on
    if(isset($posted['shipping_city']) ) {
      $city = $this->_clean_city_field($posted['shipping_city']);
    }
    update_post_meta($order_id, '_shipping_city', $city);
  }


  /*
    Add Destination's ID to POST parameter

    @filter woocommerce_cart_shipping_packages
    @param mixed $packages - Cart parameters
    @return mixed
  */
  function parse_shipping_package($packages) {
    // look for district ID in city field
    preg_match('/\[(\d+)\]/', $packages[0]['destination']['city'], $matches);
    if(count($matches) ) {
      $packages[0]['destination']['destination_id'] = $matches[1];
    }

    return $packages;
  }

  /////

  /*
    Clean the city field. The raw formatt is "City name [id]". Remove the [id].

    @param string $city_raw
    @return string - City name without ID
  */
  private function _clean_city_field($city_raw) {
    preg_match('/[\w\s,]+/', $city_raw, $city);

    return trim($city[0]);
  }

}
