<?php
/**
 * A MailChimp Batch operation for WordPress applications. 
 *
 * This is a wrapper class of batch operations for the RocketGeek
 * MailChimp API for WordPress. Based on the MailChimp API class 
 * by Drew McLellan (https://github.com/drewm/mailchimp-api) and 
 * modified for use in WordPress without cURL, using WP's 
 * wp_remote_post() and wp_remote_get(). Formatted to WordPress 
 * coding standards.
 *
 * MailChimp API v3:   https://developer.mailchimp.com
 * WordPress HTTP API: https://developer.wordpress.org/plugins/http-api/
 * This class:         https://github.com/rocketgeek/mailchimp-api
 * Drew's class:       https://github.com/drewm/mailchimp-api
 *
 * @author Chad Butler
 * @version 1.1.0
 *
 * @see https://developer.mailchimp.com/documentation/mailchimp/reference/batches/
 */
class RocketGeek_MailChimp_API_Batch {

	/**
	 *
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    
	 */
	private $mailchimp;

	/**
	 *
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array
	 */
	private $operations = array();
	
	/**
	 *
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    
	 */
	private $batch_id;

	/**
	 * Class constructor
	 *
	 * @since 1.0.0
	 *
	 * @param
	 * @param
	 * @param
	 */
	public function __construct( MailChimp $mailchimp, $batch_id = null ) {
		$this->mailchimp = $mailchimp;
		$this->batch_id  = $batch_id;
	}

	/**
	 * Add an HTTP DELETE request operation to the batch - for deleting data.
	 *
	 * @since 1.0.0
	 *
	 * @param   string $id     ID for the operation within the batch
	 * @param   string $method URL of the API request method
	 * @return  void
	 */
	public function delete( $id, $method ) {
		$this->queue_operation( 'DELETE', $id, $method );
	}

	/**
	 * Add an HTTP GET request operation to the batch - for retrieving data
	 *
	 * @param   string $id     ID for the operation within the batch
	 * @param   string $method URL of the API request method
	 * @param   array $args    Assoc array of arguments (usually your data)
	 * @return  void
	 */
	public function get( $id, $method, $args = array() ) {
		$this->queue_operation( 'GET', $id, $method, $args );
	}

	/**
	 * Add an HTTP PATCH request operation to the batch - for performing partial updates
	 *
	 * @since 1.0.0
	 *
	 * @param   string $id     ID for the operation within the batch
	 * @param   string $method URL of the API request method
	 * @param   array $args    Assoc array of arguments (usually your data)
	 * @return  void
	 */
	public function patch( $id, $method, $args = array() ) {
		$this->queue_operation( 'PATCH', $id, $method, $args );
	}

	/**
	 * Add an HTTP POST request operation to the batch - for creating and updating items
	 * @param   string $id     ID for the operation within the batch
	 * @param   string $method URL of the API request method
	 * @param   array $args    Assoc array of arguments (usually your data)
	 * @return  void
	 */
	public function post( $id, $method, $args = array() ) {
		$this->queue_operation( 'POST', $id, $method, $args );
	}

	/**
	 * Add an HTTP PUT request operation to the batch - for creating new items
	 *
	 * @since 1.0.0
	 *
	 * @param   string $id     ID for the operation within the batch
	 * @param   string $method URL of the API request method
	 * @param   array $args    Assoc array of arguments (usually your data)
	 * @return  void
	 */
	public function put( $id, $method, $args = array() ) {
		$this->queue_operation( 'PUT', $id, $method, $args );
	}

	/**
	 * Execute the batch request
	 * 
	 * @since 1.0.0
	 *
	 * @param  int         $timeout  Request timeout in seconds (optional)
	 * @return array|false           Assoc array of API response, decoded from JSON
	 */
	public function execute( $timeout = 10 ) {
		$req = array( 'operations' => $this->operations );

		$result = $this->mailchimp->post( 'batches', $req, $timeout );

		if ( $result && isset( $result['id'] ) ) {
			$this->batch_id = $result['id'];
		}

		return $result;
	}

	/**
	 * Check the status of a batch request. 
	 *
	 * If the current instance of the Batch object
	 * was used to make the request, the batch_id is already known and is therefore optional.
	 *
	 * @since 1.0.0
	 *
	 * @param  string      $batch_id ID of the batch about which to enquire
	 * @return array|false           Assoc array of API response, decoded from JSON
	 */
	public function check_status( $batch_id = null ) {
		if ( $batch_id === null && $this->batch_id ) {
			$batch_id = $this->batch_id;
		}

		return $this->mailchimp->get('batches/' . $batch_id);
	}

	/**
	 * Get operations
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_operations() {
		return $this->operations;
	}

	/**
	 * Add an operation to the internal queue.
	 *
	 * @since 1.0.0
	 *
	 * @param   string $http_verb GET, POST, PUT, PATCH or DELETE
	 * @param   string $id        ID for the operation within the batch
	 * @param   string $method    URL of the API request method
	 * @param   array $args       Assoc array of arguments (usually your data)
	 * @return  void
	 */
	private function queue_operation( $http_verb, $id, $method, $args = null ) {
		$operation = array(
			'operation_id' => $id,
			'method' => $http_verb,
			'path' => $method,
		);

		if ( $args ) {
			if ( $http_verb == 'GET' ) {
				$key = 'params';
				$operation[$key] = $args;
			} else {
				$key = 'body';
				$operation[$key] = json_encode( $args );
			}
		}

		$this->operations[] = $operation;
	}
}