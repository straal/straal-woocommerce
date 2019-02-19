<?php
/**
 * Settings for Straal Checkout Gateway.
 **/

defined( 'ABSPATH' ) || exit;

return array(
    'enabled' 			        => array(
        'title' 	    => __( 'Enable/Disable', 'woocommerce' ),
        'type' 		    => 'checkbox',
        'label' 	    => __( 'Enable Straal Checkout', 'woocommerce' ),
        'default' 	    => 'yes',
    ),
    'title'                     => array(
        'title'         => __( 'Title', 'woocommerce' ),
        'type'          => 'text',
        'description'   => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
        'default'       => __( 'Straal Checkout', 'woocommerce' ),
        'desc_tip'      => true,
    ),
    'description'               => array(
        'title'         => __( 'Description', 'woocommerce' ),
        'type'          => 'text',
        'desc_tip'      => true,
        'description'   => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
        'default'       => __( "Pay via Straal Checkout with card or pay-by-link.", 'woocommerce' ),
    ),
	'api_key_title'             => array(
		'title'         => __( 'Basic configuration', 'woocommerce' ),
		'type'          => 'title',
		'description'   => __( 'Required in order to start using Straal payments.', 'woocommerce' ),
	),
    'api_key'                   => array(
        'title'         => __( 'API key', 'woocommerce' ),
        'type'          => 'password',
        'desc_tip'      => true,
        'description'   => __( 'API key used to authenticate transaction.', 'woocommerce' ),
        'default'       => __( "", 'woocommerce' ),
    ),
	'sandbox_title'             => array(
		'title'         => __( 'Sandbox mode', 'woocommerce' ),
		'type'          => 'title',
		'description'   => __( 'Sandbox mode lets you create transactions in a test environment.', 'woocommerce' ),
	),
	'sandbox'                   => array(
        'title'         => __( 'Enable/Disable', 'woocommerce' ),
		'type'          => 'checkbox',
		'label'         => __( 'Enable Straal sandbox mode', 'woocommerce' ),
        'description'   => __( 'Separate API key is required for sandbox mode to work.', 'woocommerce' ),
		'default'       => 'no'
	),
    'sandbox_api_key'           => array(
        'title'         => __( 'Sandbox API key', 'woocommerce' ),
        'type'          => 'text',
        'desc_tip'      => true,
        'description'   => __( 'API key used to authenticate sandbox transaction.', 'woocommerce' ),
        'default'       => __( "", 'woocommerce' ),
    ),
	'notifications_title'       => array(
		'title'         => __( 'Notifications configuration', 'woocommerce' ),
		'type'          => 'title',
        'description'   => __( 'Notifications from Straal allow for automatic payment status updates for your orders. To make notifications work you need to provide Straal staff with WooCommerce Callback URL and authorization credentials (should be also defined in user/password fields below).', 'woocommerce' ),
	),
    'notifications_callback'        => array(
        'title'         => __( 'Callback URL', 'woocommerce' ),
        'type'          => 'text',
        'desc_tip'      => true,
        'description'   => __( 'WooCommerce callback URL which is used to receive notifications from Straal. You need to provide it to Straal staff in order to have orders payment statuses automatically updated.', 'woocommerce' ),
        'default'       => site_url() . '/wc-api/wc_gateway_straal',
        'custom_attributes' => array(
            'readonly' => 'readonly'
        ),
    ),
    'notifications_user'        => array(
        'title'         => __( 'Notifications username', 'woocommerce' ),
        'type'          => 'text',
        'desc_tip'      => true,
        'description'   => __( 'Username to authenticate order status updates.', 'woocommerce' ),
        'default'       => __( "", 'woocommerce' )
    ),
    'notifications_password'    => array(
        'title'         => __( 'Notifications password', 'woocommerce' ),
        'type'          => 'password',
        'desc_tip'      => true,
        'description'   => __( 'Password to authenticate order status updates.', 'woocommerce' ),
        'default'       => __( "", 'woocommerce' ),
    ),
);