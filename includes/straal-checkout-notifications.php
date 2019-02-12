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
	 * Constructor.
	 * 
	 */
	public function __construct( $gateway ) {
		$this->gateway  = $gateway;
		add_action( 'woocommerce_api_wc_gateway_straal', array( $this, 'process_notification' ) );
	}

	/**
	 * Process notification.
	 */
	public function process_notification() {
		$json = file_get_contents('php://input');
		$request_body = json_decode($json);
		
		if (!empty($request_body)) {
			$notification_type = $request_body->event;
			switch ($notification_type) {
				case 'request_finished':
					if ($this->process_request_finished( $request_body )) {
						exit;
					}
					break;
				case 'pay_by_link_payment_succeeded':
					if ($this->process_pay_by_link_payment_succeeded( $request_body )) {
						exit;
					}
					break;
				case 'pay_by_link_payment_failed':	
					if ($this->process_pay_by_link_payment_failed( $request_body )) {
						exit;
					}
			}
		}
		wp_die( 'Invalid Straal Notification', 'Straal Notifications', array( 'response' => 500 ) );
	}

	/**
	 * Process Straal card notification.
	 * 
	 * @param object $request_body  Body of a POST request.
	 */
	protected function process_request_finished( $request_body ) {
		$should_handle_notification = $request_body->data->permission == 'v1.transactions.checkout_attempt_create';
		$notification_data = $request_body->data->response;
		
		$is_valid = $should_handle_notification && $this->verify_currency_and_amount( $notification_data );

		if ($is_valid) {
			$is_transaction_authorized = $notification_data->authorized;
			$this->finalize_payment( $notification_data, $is_transaction_authorized );
		}

		return $is_valid;
	}

	/**
	 * Process Straal Pay-by-link success notification.
	 * 
	 * @param object $request_body  Body of a POST request.
	 */
	protected function process_pay_by_link_payment_succeeded( $request_body ) {
		$notification_data = $request_body->data->transaction;
		
		$is_valid = $this->verify_currency_and_amount( $notification_data );

		if ($is_valid) {
			$this->finalize_payment( $notification_data, true );
		}

		return $is_valid;
	}

	/**
	 * Process Straal Pay-by-link success notification.
	 * 
	 * @param object $request_body  Body of a POST request.
	 */
	protected function process_pay_by_link_payment_failed( $request_body ) {
		$notification_data = $request_body->data->transaction;
		
		$is_valid = $this->verify_currency_and_amount( $notification_data );

		if ($is_valid) {
			$this->finalize_payment( $notification_data, false );
		}

		return $is_valid;
	}

	/**
	 * Verify if notification data has correct currency and amount values.
	 * 
	 * @param object $notification_data JSON with notification details.
	 */
	protected function verify_currency_and_amount( $notification_data ) {
		$order = $this->get_order_from_notification( $notification_data );
		$currency_is_valid = $this->validate_currency( $order, $notification_data->checkout->currency );
		$amount_is_valid = $this->validate_amount( $order, $notification_data->checkout->amount );
		return $currency_is_valid && $amount_is_valid;
	}

	/**
	 * Check if currency included in notification body matches order currency.
	 *
	 * @param WC_Order $order  Order object.
	 * @param string $currency  Currency code provided in notification.
	 */
	protected function validate_currency( $order, $currency ) {
		$order_currency = strtolower( $order->get_currency() );
		$notification_currency = strtolower( $currency );
		$is_valid = $order_currency == $notification_currency;
		if ( !$is_valid ) {
			error_log('currency mismatch');
			$order->update_status( 'on-hold', __( 'Validation error: Straal and WooCommerce currencies do not match.', 'woocommerce' ) );
		}
		return $is_valid;
	}

	/**
	 * Check if amount included in notification body matches order amount.
	 *
	 * @param WC_Order $order Order object.
	 * @param string $amount Transaction total in Straal format.
	 */
	protected function validate_amount( $order, $amount ) {
		$order_amount = $order->get_total();
		$notification_amount = $this->gateway->format_transaction_total( $amount );
		$is_valid = number_format( $order_amount, 2, '.', '' ) == number_format( $notification_amount, 2, '.', '' );
		if ( !$is_valid ) {
			error_log('amount mismatch');
			$order->update_status( 'on-hold', __( 'Validation error: Straal and WooCommerce amounts do not match.', 'woocommerce' ) );
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
			error_log('Order ' . $order->get_id() . ' is ' . ($is_transaction_authorized ? 'authorized.' : 'not authorized.'));
			
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
		$order->payment_complete( $notification_data->id );
		WC()->cart->empty_cart();
	}

	/**
	 * Check if currency included in notification body matches order currency.
	 *
	 * @param object $notification_data JSON with notification details.
	 * 
     * @return WC_Order
	 */
	protected function get_order_from_notification( $notification_data ) {
		$order_id = $notification_data->checkout->order_reference;
		return wc_get_order( $order_id );
	}
}