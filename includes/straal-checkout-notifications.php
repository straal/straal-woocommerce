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

		add_action( 'woocommerce_api_wc_gateway_straal', array( $this, 'validate_notification' ) );
		add_action( 'handle_valid_notification', array( $this, 'process_notification' ), 10, 1 );
	}

	/**
	 * Validate notification.
	 */
	public function validate_notification() {
		$json = file_get_contents('php://input');
		$request_body = json_decode($json);

		if ( !empty( $request_body ) && $this->verify_request( $request_body->data ) ) {
			do_action( 'handle_valid_notification', $request_body );
			exit;
		}

		wp_die( 'Invalid Straal Notification', 'Straal Notifications', array( 'response' => 500 ) );
	}

	/**
	 * Verify if request is a Straal notification.
	 * 
	 * @param object $notification_data JSON with notification details.
	 */
	public function verify_request( $notification_data ) {
		$order = $this->get_order_from_notification( $notification_data );
		
		$this->validate_currency( $order, $notification_data->response->currency );
		$this->validate_amount( $order, $notification_data->response->amount );

		return true;
	}

	/**
	 * Check if currency included in notification body matches order currency.
	 *
	 * @param WC_Order $order  Order object.
	 * @param string $notification_currency  Currency code provided in notification.
	 */
	protected function validate_currency( $order, $currency ) {
		$order_currency = strtolower( $order->get_currency() );
		$notification_currency = strtolower( $currency );
		if ( $order_currency !== $notification_currency ) {
			error_log('currency mismatch');
			$order->update_status( 'on-hold', __( 'Validation error: Straal and WooCommerce currencies do not match.', 'woocommerce' ) );
			exit;
		}
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
		if ( number_format( $order_amount, 2, '.', '' ) !== number_format( $notification_amount, 2, '.', '' ) ) {
			error_log('amount mismatch');
			$order->update_status( 'on-hold', __( 'Validation error: Straal and WooCommerce amounts do not match.', 'woocommerce' ) );
			exit;
		}
	}

	/**
	 * Set order status based on notification.
	 * 
	 * @param object $request_body Body of a POST request
	 */
	public function process_notification( $request_body ) {
		$payment_status = $request_body->event;

		switch ($payment_status) {
			case 'request_finished':
				$this->payment_status_request_finished( $request_body->data );
				break;
			case 'pay_by_link_payment_succeeded':
				break;
			case 'pay_by_link_payment_failed':
				break;
		}
    }

	/**
	 * Handle notification 'request_finished'.
	 * 
	 * @param object $notification_data JSON with notification details.
	 */
	public function payment_status_request_finished( $notification_data ) {
		$order = $this->get_order_from_notification( $notification_data );
		$is_transaction_authorized = $notification_data->response->authorized;

		error_log('Order ' . $order->get_id() . ' is ' . ($is_transaction_authorized ? 'authorized.' : 'not authorized.'));

        if ( $order && !$order->has_status( wc_get_is_paid_statuses() ) ) {
			if ( $is_transaction_authorized ) {
				$this->complete_order( $order, $notification_data );
			} else {
				$order->update_status( 'failed', __('Payment via Straal Checkout failed.', 'woocommerce' ) );
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
		$order->payment_complete( $notification_data->response->id );
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
		$notification_response = $notification_data->response;
		$order_id = $notification_response->reference;
		return wc_get_order( $order_id );
	}
}