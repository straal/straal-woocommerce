<?php
/**
 * Class WC_Gateway_Straal_Request file.
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates requests to send to Straal.
 */
class WC_Gateway_Straal_Request {

    /**
     * Straal API endpoint.
     */
    protected $endpoint;

	/**
	 * Pointer to gateway making the request.
	 **/
	protected $gateway;

	/**
	 * Order data for the request.
	 **/
	protected $order;

	/**
	 * Key for Straal API authorization.
	 **/
	protected $api_key;

	/**
	 * Constructor.
     * 
	 * @param WC_Gateway_Straal $gateway Paypal gateway object.
     * @param string $api_key Merchant's Straal API key.
	 **/
	public function __construct( $gateway, $api_key ) {
		$this->endpoint = 'https://api.straal.com/';
		$this->gateway  = $gateway;
		$this->api_key  = $api_key;
	}

    /**
     * Request to Straal API to create customer and return their id.
     * Id is later used to initialize checkout instance.
     *
     * @param WC_Order $order Order object.
     * @return string
     */
    public function create_customer( $order ) {
        $response = wp_remote_post( $this->endpoint . 'v1/customers', $args = array(
            'headers' 	=> array(
                'Content-Type'	=> 'application/json',
                'Authorization' => $this->get_authorization_string(),
            ),
            'body' 		=> 
                json_encode( 
                    array(
                        'email' 	=> $order->get_billing_email(),
                        'reference' => $order->get_customer_id(),
                    ) 
                ),
        ) );

        return $this->extract_customer_id_from_response( $order, $response );
    }

    /**
     * Request to Straal API to initialize checkout instance and return its meta.
     *
     * @param WC_Order $order Order object.
     * @param string $customer_id Straal internal customer id.
     * @return array
     */
    public function initialize_checkout( $order, $customer_id ) {
        if ( $customer_id ) {
            $transaction_params = array(
                'currency'          => $order->get_currency(),
                'amount'            => $this->gateway->parse_transaction_total( $order->get_total() ),
                'ttl'               => 600,
                'return_url'        => $this->gateway->get_return_url( $order ),
                'failure_url'       => $this->gateway->get_return_url( $order ), // To update
                'order_description' => 'Star Wars mug XXL', //TODO: Generate order description
                'order_reference'   => $order->get_order_number(),
            );
    
            $response = wp_remote_post( $this->endpoint . 'v1/customers/' . $customer_id . '/checkouts', $args = array(
                'body'      => json_encode( $transaction_params ),
                'headers' 	=> array(
                    'Content-Type'	=> 'application/json',
                    'Authorization' => $this->get_authorization_string(),
                ),
            ) );
    
            $response_body = json_decode( wp_remote_retrieve_body( $response ) );
    
            return $response_body;
        }
    }

    /**
     * Request to Straal API to refund transaction.
     *
     * @param WC_Order $order Order object.
     * @param int $amount Refund amount.
     * @param string $reason Refund reason.
     * @return array
     */
    public function refund_transaction( $order, $amount, $reason ) {
        $transaction_id = $order->get_transaction_id();
        error_log('refund_transaction');

        if ( $transaction_id ) {
            error_log('transaction_id');
            error_log($transaction_id);
            $refund_params = array(
                'amount'     => $amount,
                'extra_data' => array(
                    'reason' => $reason
                )
            );

            $response = wp_remote_post( $this->endpoint . 'v1/transactions/' . $transaction_id . '/refund', $args = array(
                'body'      => json_encode( $refund_params ),
                'headers'   => array(
                    'Content-Type'	=> 'application/json',
                    'Authorization' => $this->get_authorization_string(),
                )
            ) );
    
            return $response;
        }
    }

    /**
     * Generate authorization string for HTTP request from API key.
     *
     * @return string
     */
    public function get_authorization_string() {
        return 'Basic ' . base64_encode( ':' . $this->api_key );
    }

    /**
     * Get Straal customer id from customers list by WooCommerce customer id.
     *
     * @param WC_Order $order Order object.
     * @return string
     */
    public function get_customer_id_by_reference( $order ) {
        $response = wp_remote_get( $this->endpoint . 'v1/customers?reference__eq=' . $order->get_customer_id(), $args = array(
            'headers' => array(
                'Authorization' => $this->get_authorization_string(),
            ),
        ) );

        $response_body = json_decode( wp_remote_retrieve_body( $response ) );
        $customer = array_values( $response_body->data )[0];

        return $customer->id;
    }

    /**
     * Get Straal customer id from create_customer response.
     * If response says customer already exists, find id from customers list by WooComerce customer id.
     *
     * @param WC_Order $order Order object.
     * @param array $response Response from create_customer request.
     * @return string
     */
    public function extract_customer_id_from_response( $order, $response ) {
        $response_body = json_decode( wp_remote_retrieve_body( $response ) );

        if (wp_remote_retrieve_response_code( $response ) !== 200) {
            if (property_exists($response_body, 'errors')) {
                foreach ( $response_body->errors as $error ) {
                    if ( $error->code === 12005 ) {
                        return $this->get_customer_id_by_reference( $order );
                    }
                }
            }
        } else {
            return $response_body['id'];
        }
    }
}