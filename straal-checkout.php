<?php
/**
 * Plugin Name: Straal Checkout
 * Plugin URI: http://woocommerce.com/products/straal-checkout/
 * Description: Todo.
 * Version: 1.0.0
 * Author: WooCommerce
 * Author URI: http://woocommerce.com/
 * Developer: Straal
 * Developer URI: http://straal.com/
 * Text Domain: straal-checkout
 * Domain Path: /languages
 *
 * Woo: 12345:342928dfsfhsf8429842374wdf4234sfd
 * WC requires at least: 2.2
 * WC tested up to: 2.3
 *
 * Copyright: © 2009-2015 WooCommerce.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {		
	function init_straal_gateway_class() {
		class WC_Gateway_Straal extends WC_Payment_Gateway {

			/**
			 * Initialize gateway.
			 **/
			public function __construct() {
				$plugin_dir = plugin_dir_url(__FILE__);

				$this->id 					= 'straal_checkout';
				$this->has_fields 			= false;
				$this->order_button_text 	= __( 'Proceed to Straal', 'woocommerce' );
				$this->method_title      	= __( 'Straal Checkout', 'woocommerce' );
				$this->method_description 	= __( 'todo_method_description', 'woocommerce' );
				$this->icon 				= apply_filters( 'woocommerce_gateway_icon', $plugin_dir.'assets/icon.png' );
				$this->supports				= array(
					'refunds'
				);

				// Load the settings.
				$this->init_form_fields();
				$this->init_settings();

				// Define user set variables.
				$this->title        	= $this->get_option( 'title' );
				$this->description  	= $this->get_option( 'description' );
				$this->sandbox      	= $this->is_sandbox_enabled();
				$this->api_key  		= $this->sandbox ? $this->get_option( 'sandbox_api_key' ) : $this->get_option( 'api_key' );

				// Actions.
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

				if ( ! $this->is_valid_for_use() ) {
					$this->enabled = 'no';
				} else {					
					// Initialize Straal requests class
					include_once dirname( __FILE__ ) . '/includes/straal-checkout-request.php';
					$this->straal_request = new WC_Gateway_Straal_Request( $this, $this->api_key );

					// Initialize notifications callback
					include_once dirname( __FILE__ ) . '/includes/straal-checkout-notifications.php';
					new WC_Gateway_Straal_Notifications( $this );
				}
			}

			/**
			 * Initialise Gateway Settings Form Fields.
			 */
			public function init_form_fields() {
				$this->form_fields = include 'includes/form-fields.php';
			}

			/**
			 * Process the payment and return the result.
			 *
			 * @param int $order_id Order ID.
			 * @return array
			 */
			public function process_payment( $order_id ) {
				include_once dirname( __FILE__ ) . '/includes/straal-checkout-request.php';
				
				$order = wc_get_order( $order_id );
					
				$straal_customer_id = $this->straal_request->create_customer( $order );
				$straal_checkout_meta = $this->straal_request->initialize_checkout( $order, $straal_customer_id );

				if ( $straal_customer_id !== null && $straal_checkout_meta !== null ) {
					$checkout_url = $straal_checkout_meta->checkout_url;
					$order->set_transaction_id( $straal_checkout_meta->id );

					return array(
						'result'   => 'success',
						'redirect' => $checkout_url,
					);	
				} else {
					wc_add_notice( __('Payment error, please try again.', 'woocommerce' ), 'error' );
				}
			}

			/**
			 * Can the order be refunded via Straal?
			 *
			 * @param  WC_Order $order Order object.
			 * @return bool
			 */
			public function can_refund_order( $order ) {		
				return $order && $order->get_transaction_id();
			}

			/**
			 * Process a refund if supported.
			 *
			 * @param  int    $order_id Order ID.
			 * @param  float  $amount Refund amount.
			 * @param  string $reason Refund reason.
			 * @return bool|WP_Error
			 */
			public function process_refund( $order_id, $amount = null, $reason = '' ) {
				$order = wc_get_order( $order_id );
		
				if ( ! $this->can_refund_order( $order ) ) {
					return new WP_Error( 'error', __( 'Refund failed.', 'woocommerce' ) );
				}

				$response = $this->straal_request->refund_transaction( $order, $this->parse_transaction_total( $amount ), $reason );

				if ( wp_remote_retrieve_response_code( $response ) === 200 ) {
					$result = json_decode( wp_remote_retrieve_body( $response ) );
					$result_amount = end( $result->refunds )->amount;
					$order->add_order_note(
						/* translators: 1: Refund amount, 2: Refunded transaction id */
						sprintf( 
							__( 'Refunded %1$s %2$s, transaction ID: %3$s, reason: %4$s', 'woocommerce' ), 
							$this->format_transaction_total( $result_amount ), 
							strtoupper( $result->currency ), 
							$result->id, 
							$reason 
						)
					);
					return true;
				} else {
					return false;
				}		
			}

			/**
			 * Check if this gateway is configured for use.
			 *
			 * @return bool
			 */
			public function is_valid_for_use() {
				return !empty($this->api_key);
			}

			/**
			 * Check if this gateway is configured for use.
			 *
			 * @return bool
			 */
			public function is_sandbox_enabled() {
				return 'yes' === $this->get_option( 'sandbox', 'no' );
			}

			/**
			 * Parse total amount format from WooCommerce to Straal.
			 *
			 * @param string $value Total amount in format used by WooCommerce.
			 * @return int
			 */
			public function parse_transaction_total( $value ) {
				$value_float = floatval( $value );
				return intval( $value_float * 100 );
			}
		
			/**
			 * Parse total amount format from Straal to WooCommerce.
			 *
			 * @param int $value Total amount in format used by Straal.
			 * @return float
			 */
			public function format_transaction_total( $value ) {
				$value_float = floatval( $value );
				return $value_float / 100;
			}
		}
	}

	/**
	 * Create gateway class after plugins are loaded.
	 **/
	add_action( 'plugins_loaded', 'init_straal_gateway_class' );

	function add_straal_gateway_class( $methods ) {
		$methods[] = 'WC_Gateway_Straal'; 
		return $methods;
	}

	/**
	 * Register gateway in WooCommerce.
	 **/
	add_filter( 'woocommerce_payment_gateways', 'add_straal_gateway_class' );
}