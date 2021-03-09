<?php
/**
 * Class WC_Gateway_Straal_Notifications file.
 * 
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles transaction status updates from Straal.
 */
class WC_Gateway_Straal_Notifications {

	/**
	 * Pointer to gateway making the request.
	 **/
	protected $gateway;

	/**
	 * Straal logger instance.
	 **/
    protected $logger;

	/**
	 * Constructor.
	 * 
	 */
	public function __construct( $gateway ) {
		$this->gateway = $gateway;
		add_action( 'woocommerce_api_wc_gateway_straal', array( $this, 'process_notification' ) );
        include_once dirname( __FILE__ ) . '/straal-checkout-logger.php';
        $this->logger = new WC_Gateway_Straal_Logger( $gateway );
	}

	/**
	 * Process notification.
	 */
	public function process_notification() {
		$json = file_get_contents('php://input');
		$request_body = json_decode($json);
		
		if ( !empty($request_body) ) {
			if ( $request_body->event == 'checkout_attempt_finished' ) {
				$notification_data = $request_body->data;
				$is_transaction_authorized = $this->get_transaction_authorization_status( $notification_data );	
				if (
					isset($is_transaction_authorized) &&
					$this->process_checkout_attempt_finished( $notification_data, $is_transaction_authorized )
				) {
					exit;
				}
			} else {
				wp_die( 'Non-handled Straal Notification', 'Straal Notifications', array( 'response' => 200 ) );
			}
		}
		wp_die( 'Invalid Straal Notification', 'Straal Notifications', array( 'response' => 500 ) );
	}

	/**
	 * Process Straal checkout_attempt_finished notification.
	 * 
	 * @param object $notification_data JSON with notification details.
	 * @param boolean $is_transaction_authorized Transaction authorization info.
	 * 
     * @return boolean
	 */
	protected function process_checkout_attempt_finished( $notification_data, $is_transaction_authorized ) {
		
		$is_valid = $this->verify_currency_and_amount( $notification_data );

		if ($is_valid) {
			$this->finalize_payment( $notification_data, $is_transaction_authorized );
		}

		return $is_valid;
	}

	/**
	 * Finalize payment.
	 * 
	 * @param object $notification_data JSON with notification details.
	 * @param boolean $is_transaction_authorized Transaction authorization info.
	 */
	public function finalize_payment( $notification_data, $is_transaction_authorized ) {
		$order = $this->get_order_from_notification( $notification_data );
		
		if ( $order ) {
		    $log_msg = 'Order ' . $order->get_id() . ' is ' . ($is_transaction_authorized ? 'authorized.' : 'not authorized.');
			$this->logger->info( $log_msg , NULL );
			
			if ( !$order->has_status( wc_get_is_paid_statuses() ) ) {
				if ( $is_transaction_authorized ) {
					$this->complete_order( $order, $notification_data );
				} else {
					$order->update_status( 'failed', __('Payment via Straal Checkout failed.', 'woocommerce' ) );
				}
			}
		}

	}

	/**
	 * Complete order.
	 *
	 * @param WC_Order $order  Order object.
	 * @param object $notification_data JSON with notification details.
	 */
	protected function complete_order( $order, $notification_data ) {
		$order->payment_complete( $notification_data->transaction->id );
		WC()->cart->empty_cart();
	}

	/**
	 * Verify if notification data has correct currency and amount values.
	 * 
	 * @param object $notification_data JSON with notification details.
	 * 
     * @return boolean
	 */
	protected function verify_currency_and_amount( $notification_data ) {
		$order = $this->get_order_from_notification( $notification_data );
		if ($order) {
			$currency_is_valid = $this->validate_currency( $order, $notification_data->checkout->currency );
			$amount_is_valid = $this->validate_amount( $order, $notification_data->checkout->amount );
			return $currency_is_valid && $amount_is_valid;
		} else {
			return false;
		}
	}

	/**
	 * Check if currency included in notification body matches order currency.
	 *
	 * @param WC_Order $order  Order object.
	 * @param string $currency  Currency code provided in notification.
	 * 
     * @return boolean
	 */
	protected function validate_currency( $order, $currency ) {
		$order_currency = strtolower( $order->get_currency() );
		$notification_currency = strtolower( $currency );
		$is_valid = $order_currency == $notification_currency;
		if ( !$is_valid ) {
			$this->logger->info( 'validate_currency: Validation error: Straal and WooCommerce currencies do not match.' , NULL);
			$order->update_status( 'on-hold', __( 'Validation error: Straal and WooCommerce currencies do not match.', 'woocommerce' ) );
		}
		return $is_valid;
	}

	/**
	 * Check if amount included in notification body matches order amount.
	 *
	 * @param WC_Order $order Order object.
	 * @param string $amount Transaction total in Straal format.
	 * 
     * @return boolean
	 */
	protected function validate_amount( $order, $amount ) {
		$order_amount = $order->get_total();
		$notification_amount = $this->gateway->format_transaction_total( $amount );
		$is_valid = number_format( $order_amount, 2, '.', '' ) == number_format( $notification_amount, 2, '.', '' );
		if ( !$is_valid ) {
			$this->logger->info( 'validate_amount: Validation error: Straal and WooCommerce amounts do not match.' , NULL );
			$order->update_status( 'on-hold', __( 'Validation error: Straal and WooCommerce amounts do not match.', 'woocommerce' ) );
		}
		return $is_valid;
	}

	/**
	 * Check if currency included in notification body matches order currency.
	 *
	 * @param object $notification_data JSON with notification details.
	 * 
     * @return boolean|WC_Order
	 */
	protected function get_order_from_notification( $notification_data ) {
		if (property_exists($notification_data, 'checkout')) {
			$order_id = $notification_data->checkout->order_reference;
			return wc_get_order( $order_id );
		} else {
			$this->logger->error( 'finalize_payment: Cannot extract order id from notification data.' , NULL );
			return false;
		}
	}

	/**
	 * Check if checkout attempt was successful.
	 *
	 * @param object $notification_data JSON with notification details.
	 * 
     * @return boolean|NULL
	 */
	protected function get_transaction_authorization_status( $notification_data ) {
		if ($notification_data) {
			$attempt_status = $notification_data->checkout_attempt->status;
	
			$success_statuses = array( 'succeeded' );
			$failed_statuses = array( 'failed', 'expired' );

			if ( in_array($attempt_status, $success_statuses) ) {
				return true;
			} elseif ( in_array($attempt_status, $failed_statuses) ) {
				return false;
			}
		} else {			
			$this->logger->error( 'get_transaction_authorization_status: No notification data provided!.' , NULL );
		}
	}
}