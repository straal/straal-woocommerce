<?php
/**
 * Settings for Straal Checkout Gateway.
 **/

defined( 'ABSPATH' ) || exit;


$logging_label = __( 'Enable Logging', 'woocommerce' );

if ( defined( 'WC_LOG_DIR' ) ) {
	$log_url = add_query_arg( 'tab', 'logs', add_query_arg( 'page', 'wc-status', admin_url( 'admin.php' ) ) );
	$log_key = 'straal-checkout-' . sanitize_file_name( wp_hash( 'straal-checkout' ) ) . '-log';
	$log_url = add_query_arg( 'log_file', $log_key, $log_url );

	$logging_label .= ' | ' . sprintf( __( '%1$sView Log%2$s', 'woocommerce' ), '<a href="' . esc_url( $log_url ) . '">', '</a>' );
}

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
        'description'   => __( 'Title of payment method that user sees when placing the order. By default "Straal".', 'woocommerce' ),
        'default'       => __( 'Straal', 'woocommerce' ),
        'desc_tip'      => true,
    ),
    'description'               => array(
        'title'         => __( 'Description', 'woocommerce' ),
        'type'          => 'text',
        'desc_tip'      => true,
        'description'   => __( 'Description of the payment method that user sees when placing the order.', 'woocommerce' ),
        'default'       => __( "", 'woocommerce' ),
    ),
	'api_key_title'             => array(
		'title'         => __( 'Basic configuration', 'woocommerce' ),
		'type'          => 'title',
		'description'   => __( 'This will allow you to accept your payments.', 'woocommerce' ),
	),
    'api_key'                   => array(
        'title'         => __( 'API key', 'woocommerce' ),
        'type'          => 'password',
        'desc_tip'      => true,
        'description'   => __( 'API Key for authorizing a transaction. Contact your account manager to receive it or configure it in Straal Kompas.', 'woocommerce' ),
        'default'       => __( "", 'woocommerce' ),
    ),
	'sandbox_title'             => array(
		'title'         => __( 'Sandbox mode', 'woocommerce' ),
		'type'          => 'title',
		'description'   => __( 'Use Sandbox mode to create test transactions and see if everything works as intended.', 'woocommerce' ),
	),
	'sandbox'                   => array(
        'title'         => __( 'Enable/Disable', 'woocommerce' ),
		'type'          => 'checkbox',
		'label'         => __( 'Enable Straal sandbox mode', 'woocommerce' ),
        'description'   => __( 'You need a separate API key and test service to use the sandbox mode.', 'woocommerce' ),
		'default'       => 'no'
	),
    'sandbox_api_key'           => array(
        'title'         => __( 'Sandbox API key', 'woocommerce' ),
        'type'          => 'password',
        'desc_tip'      => true,
        'description'   => __( 'Sandbox mode API Key used to authenticate transaction.', 'woocommerce' ),
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
        'description'   => __( 'We will POST the notifications about the payment status at this URL. To configure the notifications provide callback URL to your Straal Account Manager.', 'woocommerce' ),
        'default'       => site_url().'/?wc-api=wc_gateway_straal',
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
    'wc_straal_checkout_debug'  => array(
        'title'         => __( 'Debug Log', 'woocommerce' ),
        'label'         => $logging_label,
        'description'         => __( 'Enable the logging of info messages.', 'woocommerce' ),
        'type'        => 'checkbox',
        'default'     => 'no',
    )
);