<?php
/**
 * Class WC_Gateway_Straal_Logger file.
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates debug logging.
 */
class WC_Gateway_Straal_Logger {

	/**
	 * Pointer to gateway making the request.
	 **/
	protected $gateway;

	/**
	 * WC_Logger instance.
	 **/
    protected $logger;

	/**
	 * WC_Logger context.
	 **/
    protected $log_context;

	/**
	 * Constructor.
     * 
	 * @param WC_Gateway_Straal $gateway Paypal gateway object.
	 **/
	public function __construct( $gateway ) {
        $this->gateway      = $gateway;
        $this->logger       = wc_get_logger();        
        $this->log_context  = array( 'source' => 'straal-checkout' );
	}

    /**
     * Check if logging is enabled by user.
     *
     * @return bool
     */
    public function is_logging_enabled() {
        return 'yes' === $this->gateway->get_option( 'wc_straal_checkout_debug', 'no' );
    }

    /**
     * Add a log.
     *
     * @param string $level One of the following:
     *     'emergency': System is unusable.
     *     'alert': Action must be taken immediately.
     *     'critical': Critical conditions.
     *     'error': Error conditions.
     *     'warning': Warning conditions.
     *     'notice': Normal but significant condition.
     *     'info': Informational messages.
     *     'debug': Debug-level messages.
     * @param string $message Log message.
     * @param mixed $data The data to be printed with message log.
     */
    public function log( string $level, string $message, $data ) {
        if ( $this->is_logging_enabled() ) {
            $this->logger->log( $level, $message, $this->log_context );
            if ( !is_null($data) ) {
                $this->logger->log( $level, json_encode($data, true), $this->log_context );
            }
        }
    }

    /**
     * Adds a info level message.
     * 
     * @param string $message
     * @param mixed $data
     */
    public function info( $message, $data ) {
        $this->log( WC_Log_Levels::INFO, $message, $data );
    }

    /**
     * Adds a error level message.
     * 
     * @param string $message
     * @param mixed $data
     */
    public function error( $message, $data ) {
        $this->log( WC_Log_Levels::INFO, $message, $data );
    }
}