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
        'default'       => __( "todo_description", 'woocommerce' ),
    ),
	'sandbox'                   => array(
		'title'         => __( 'Sandbox mode', 'woocommerce' ),
		'type'          => 'checkbox',
		'label'         => __( 'Enable Straal sandbox (separate API key required)', 'woocommerce' ),
		'default'       => 'no',
		'description'   => __( 'Straal sandbox is used to test making transactions.', 'woocommerce' ),
	),
    'api_key'                   => array(
        'title'         => __( 'API key', 'woocommerce' ),
        'type'          => 'text',
        'desc_tip'      => true,
        'description'   => __( 'API key used to authenticate transaction.', 'woocommerce' ),
        'default'       => __( "", 'woocommerce' ),
    ),
    'sandbox_api_key'           => array(
        'title'         => __( 'Sandbox API key', 'woocommerce' ),
        'type'          => 'text',
        'desc_tip'      => true,
        'description'   => __( 'API key used to authenticate sandbox transaction.', 'woocommerce' ),
        'default'       => __( "", 'woocommerce' ),
    ),
    'notifications_user'        => array(
        'title'         => __( 'Notifications username', 'woocommerce' ),
        'type'          => 'text',
        'desc_tip'      => true,
        'description'   => __( 'Username to authenticate order status updates.', 'woocommerce' ),
        'default'       => __( "", 'woocommerce' ),
    ),
    'notifications_password'    => array(
        'title'         => __( 'Notifications password', 'woocommerce' ),
        'type'          => 'password',
        'desc_tip'      => true,
        'description'   => __( 'Password to authenticate order status updates.', 'woocommerce' ),
        'default'       => __( "", 'woocommerce' ),
    ),
);