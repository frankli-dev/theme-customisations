<?php
/**
 * Functions.php
 *
 * @package  Theme_Customisations
 * @author   WooThemes
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * functions.php
 * Add PHP snippets here
 */

// // Save user data from URL to Woocommerce session
add_action( 'template_redirect', 'set_custom_data_wc_session' );
function set_custom_data_wc_session () {
	if ( isset($_GET['add-to-cart']) && isset( $_GET['application_number']) && isset( $_GET['token'] ) ) {
		$application_number = isset( $_GET['application_number'] ) ? esc_attr( $_GET['application_number'] ) : '';
		$add_to_cart = isset( $_GET['add-to-cart'] ) ? esc_attr( $_GET['add-to-cart'] ) : '';
		$token = isset( $_GET['token'] ) ? esc_attr( $_GET['token'] ) : '';
		$product_id = 291;
		$found = false;
		if ( sizeof( WC()->cart->get_cart() ) > 0 ) {
			WC()->cart->empty_cart();
		}
		if($add_to_cart == '72') {
			WC()->cart->add_to_cart(291);
		}
		// Set the session data
		WC()->session->set( 'custom_data', array( 'application_number' => $application_number, 'token' => $token, 'add_to_cart' => $add_to_cart) );
	}
}
// // Autofill checkout fields from user data saved in Woocommerce session
// add_filter( 'woocommerce_billing_fields' , 'prefill_billing_fields' );
// function prefill_billing_fields ( $address_fields ) {
// // Get the session data
// $data = WC()->session->get('custom_data');
// var_dump($address_fields);
// // Email
// if( isset($data['application_number']) && ! empty($data['application_number']) )
// echo "youpi";
// $address_fields['application_number'] = $data['application_number'];
// return $address_fields;
// }
// add_filter( 'woocommerce_billing_fields', 'jeroensormani_add_checkout_fields' );
// function jeroensormani_add_checkout_fields( $fields ) {
// $fields['billing_FIELD_ID'] = array(
// 'label' => __( 'FIELD LABEL' ),
// 'type' => 'text',
// 'class' => array( 'form-row-wide' ),
// 'priority' => 35,
// 'required' => false,
// );
// return $fields;
// }
add_action( 'woocommerce_payment_complete', 'so_payment_complete' );
function so_payment_complete( $order_id ){
	$order = wc_get_order( $order_id );
	$transaction_id = $order->get_transaction_id();
	// $billingEmail = $order->billing_email;
	// $products = $order->get_items();
	// foreach($products as $prod){
	// $items[$prod['product_id']] = $prod['name'];
	// }
	$order_data = $order->get_data();
	$json = json_encode($order_data);
	$data = WC()->session->get('custom_data');
	$url = "https://application.com/v1/api/ds-160/completeOrder/" . $data["token"];
	// // post to the request somehow
	wp_remote_post( $url, array(
		'method' => 'POST',
		'timeout' => 45,
		'redirection' => 5,
		'httpversion' => '1.0',
		'blocking' => true,
		'headers' => array(),
		'body' => array('json' => $json, 'add_to_cart' => $data["add_to_cart"] ),
		'cookies' => array()
		)
	);
	// The text for the note
	$note = __("Payment info sent to the backend: " . $url);
	// Add the note
	$order->add_order_note( $note );
	// Save the data
	$order->save();
	/*$order_string = get_post_meta( $order_id, 'application_number', true );
	list($entry_id, $form_id) = explode("-", $order_string);
	$form = GFAPI::get_form( $form_id );
	$entry = GFAPI::get_entry( $entry_id );
	GFAPI::send_notifications( $form, $entry, 'woo_payment_complete' );
	gform_update_meta( $entry_id, 'cardinal_transaction_id', $transaction_id );*/
}
function update_entry_meta( $key, $lead, $form ){
	//update score
	$value = "toto";
	return $value;
}
add_filter( 'gform_entry_meta', function ( $entry_meta, $form_id ) {
	$entry_meta['cardinal_transaction_id'] = array(
		'label' => 'Cardinal Transaction ID',
		'is_numeric' => false,
		'update_entry_meta_callback' => 'update_entry_meta_cardinal_transaction_id',
		'is_default_column' => false,
		'filter' => array(
			'key' => 'cardinal_transaction_id',
			'text' => 'Cardinal TRX ID',
			'operators' => array(
				'is',
				'isnot',
			)
		),
	);
	return $entry_meta;
}, 10, 2 );
function update_entry_meta_cardinal_transaction_id( $key, $entry, $form ){
	//update test
	$value = "";
	return $value;
}
// Remove the "Additional Info" order notes
//add_filter( 'woocommerce_enable_order_notes_field', '__return_false' );
add_action( 'woocommerce_after_order_notes', 'application_number_field' );
function application_number_field( $checkout ) {
	$data = WC()->session->get('custom_data');
	echo '<label for="application_number" class="">' . __('Application Number') . '</label>';
	echo '<div id="application_number">';
	woocommerce_form_field( 'application_number', array(
		'type' => 'text',
		'class' => array('application_number-class form-row-wide'),
		'placeholder' => __('Enter your application number here'),
		'required' => true,
		'custom_attributes' => array('readonly' => 'readonly'),
		), $data['application_number']);
	echo '</div>';
}
add_action('woocommerce_checkout_process', 'application_number_field_process');
function application_number_field_process() {
	// Check if set, if its not set add an error.
	if ( ! $_POST['application_number'] )
		wc_add_notice( __( 'Please enter an application number' ), 'error' );
}
/**
Update the order meta with field value
*/
add_action( 'woocommerce_checkout_update_order_meta', 'application_number_field_update_order_meta' );
function application_number_field_update_order_meta( $order_id ) {
	if ( ! empty( $_POST['application_number'] ) ) {
		update_post_meta( $order_id, 'application_number', sanitize_text_field( $_POST['application_number'] ) );
	}
}
/**
Display field value on the order edit page
*/
add_action( 'woocommerce_admin_order_data_after_billing_address', 'application_number_field_display_admin_order_meta', 10, 1 );
function application_number_field_display_admin_order_meta($order){
	$order_string = get_post_meta( $order->id, 'application_number', true );
	list($order_id, $form_id) = explode("-", $order_string);
	echo '<p><strong>'.__('Application Number').':</strong> <a href="/wp-admin/admin.php?page=gf_entries&view=entry&id='.$form_id.'&lid='.$order_id.'&order=ASC&filter&paged=1&pos=0&field_id&operator">' . $order_id . '</a></p>';
}
// remove the order comments field
add_filter( 'woocommerce_checkout_fields' , 'alter_woocommerce_checkout_fields' );
function alter_woocommerce_checkout_fields( $fields ) {
	unset($fields['order']['order_comments']);
	return $fields;
}
add_filter( 'gform_notification_events', 'add_event' );
function add_event( $notification_events ) {
	$notification_events['woo_payment_complete'] = __( 'Woocommerce Payment Completed', 'gravityforms' );
	return $notification_events;
}
// Remove added to cart message
add_filter( 'wc_add_to_cart_message', 'remove_add_to_cart_message' );
function remove_add_to_cart_message() {
    return;
}
// allowing the visitor IP to be displayed instead of the proxy IP
add_filter( 'gform_ip_address', 'filter_gform_ip_address' );
function filter_gform_ip_address( $ip ) {
	// Return the IP address set by the proxy.
	// E.g. $_SERVER['HTTP_X_FORWARDED_FOR'] or $_SERVER['HTTP_CLIENT_IP']
	return $_SERVER['HTTP_X_FORWARDED_FOR'];
}
