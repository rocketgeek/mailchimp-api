<?php
/**
 * MailChimp API v3 wrapper for WordPress applications.
 *
 * A wrapper class for the MailChimp API version 3 using the WordPress
 * HTTP API.  Based on the MailChimp API class by Drew McLellan
 * (https://github.com/drewm/mailchimp-api) and modified for use in
 * WordPress without cURL, and instead uses WP's wp_remote_post() and
 * wp_remote_get(). Formatted to WordPress coding standards.
 *
 * MailChimp API v3:   https://developer.mailchimp.com
 * WordPress HTTP API: https://developer.wordpress.org/plugins/http-api/
 * This class:         https://github.com/rocketgeek/mailchimp-api
 * Drew's class:       https://github.com/drewm/mailchimp-api
 *
 * @author Chad Butler
 * @version 1.1.0
 */
class RocketGeek_MailChimp_API {

	/**
	 * The MailChimp API key.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string
	 */
	private $api_key;

	/**
	 * Default MailChimp API endpoint.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string
	 */
	private $api_endpoint = 'https://<dc>.api.mailchimp.com/3.0';

	/**
	 * SSL setting.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    boolean
	 */
	public $verify_ssl = true;

	/**
	 * Successful request indicator.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    boolean
	 */
	private $request_successful = false;

	/**
	 * Most recent error container.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string
	 */
	private $last_error = '';

	/**
	 * Most recent response container.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array
	 */
	private $last_response = array();

	/**
	 * Most recent request container.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array
	 */
	private $last_request = array();
	
	/**
	 * Create a new instance.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Added $api_endpoint as param.
	 *
	 * @param string $api_key      Your MailChimp API key.
	 * @param string $api_endpoint Optional custom API endpoint.
	 */
	public function __construct( $api_key, $api_endpoint = null ) {
		$this->api_key = $api_key;

		// Check the API key for validity.
		if ( null === $api_endpoint ) {
			
			if ( strpos( $this->api_key, '-' ) === false ) {
				wp_die( __( 'Invalid MailChimp API key supplied.', 'rg_mc_api' ) );
			}

			// Get the datacenter.
			$dc = substr( $this->api_key, strpos( $this->api_key, '-' ) + 1 );

			// Create the endpoint.
			$this->api_endpoint = str_replace( '<dc>', $dc, $this->api_endpoint );
			
		} else {
			
			// Use the given endpoint.
			$this->api_endpoint = $api_endpoint;
			
		}
		// Set a clean response container.
		$this->last_response = array( 'headers' => null, 'body' => null );
	}

	/**
	 * Create a new instance of a Batch request.
	 *
	 * Optionally with the ID of an existing batch.
	 *
	 * @since 1.0.0
	 *
	 * @param  object $this     This object class.
	 * @param  string $batch_id Optional ID of an existing batch.
	 * @return batch            New Batch object.
	 */
	public function new_batch( $batch_id = null ) {
		include_once( 'class-rocketgeek-mailchimp-api-batch.php' );
		return new RocketGeek_MailChimp_API_Batch( $this, $batch_id );
	}
	
	/**
	 * Return the current endpoint.
	 *
	 * @since 1.1.0
	 *
	 * @return string The URL to the API endpoing.
	 */
	public function get_api_endpoint() {
		return $this->api_endpoint;
	}

	/**
	 * Convert an email address into a 'subscriber hash' for identifying the subscriber in a method URL.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $email The subscriber's email address
	 * @return string        Hashed version of the input
	 */
	public function subscriber_hash( $email ) {
		return md5( strtolower( $email ) );
	}

	/**
	 * Was the last request successful?
	 *
	 * @since 1.0.0
	 *
	 * @return bool True for success, false for failure
	 */
	public function success() {
		return $this->request_successful;
	}

	/**
	 * Get the last error.
	 *
	 * Get last error returned by either the network transport, or by the API.
	 * If something didn't work, this should contain the string describing the problem.
	 *
	 * @since 1.0.0
	 *
	 * @return array|false describing the error
	 */
	public function get_last_error() {
		return $this->last_error ( null != $this->last_error ) ? $this->last_error : false;
	}

	/**
	 * Get an array containing the HTTP headers and the body of the API response.
	 *
	 * @since 1.0.0
	 *
	 * @return array Assoc array with keys 'headers' and 'body'
	 */
	public function get_last_response() {
		return $this->last_response;
	}

	/**
	 * Get an array containing the HTTP headers and the body of the API request.
	 *
	 * @since 1.0.0
	 *
	 * @return array Assoc array
	 */
	public function get_last_request() {
		return $this->last_request;
	}

	/**
	 * Make an HTTP DELETE request.
	 *
	 * @since 1.0.0
	 *
	 * @param   string      $method  URL of the API request method.
	 * @param   array       $args    Assoc array of arguments (if any).
	 * @param   integer     $timeout Timeout limit for request in seconds.
	 * @return  array|bool           Assoc array of API response, decoded from JSON.
	 */
	public function delete( $method, $args = array(), $timeout = 10 ) {
		return $this->make_request( 'delete', $method, $args, $timeout );
	}

	/**
	 * Make an HTTP GET request.
	 *
	 * @since 1.0.0
	 *
	 * @param   string     $method  URL of the API request method.
	 * @param   array      $args    Assoc array of arguments (usually your data).
	 * @param   int        $timeout Timeout limit for request in seconds.
	 * @return  array|bool          Assoc array of API response, decoded from JSON.
	 */
	public function get( $method, $args = array(), $timeout = 10 ) {
		return $this->make_request( 'get', $method, $args, $timeout );
	}

	/**
	 * Make an HTTP PATCH request.
	 *
	 * @since 1.0.0
	 *
	 * @param   string     $method  URL of the API request method.
	 * @param   array      $args    Assoc array of arguments (usually your data).
	 * @param   int        $timeout Timeout limit for request in seconds.
	 * @return  array|bool          Assoc array of API response, decoded from JSON.
	 */
	public function patch( $method, $args = array(), $timeout = 10 ) {
		return $this->make_request( 'patch', $method, $args, $timeout );
	}

	/**
	 * Make an HTTP POST request.
	 *
	 * @since 1.0.0
	 *
	 * @param   string     $method  URL of the API request method.
	 * @param   array      $args    Assoc array of arguments (usually your data).
	 * @param   int        $timeout Timeout limit for request in seconds.
	 * @return  array|bool          Assoc array of API response, decoded from JSON.
	 */
	public function post( $method, $args = array(), $timeout = 10 ) {
		return $this->make_request( 'post', $method, $args, $timeout );
	}

	/**
	 * Make an HTTP PUT request.
	 *
	 * @since 1.0.0
	 *
	 * @param   string     $method  URL of the API request method.
	 * @param   array      $args    Assoc array of arguments (usually your data).
	 * @param   int        $timeout Timeout limit for request in seconds.
	 * @return  array|bool          Assoc array of API response, decoded from JSON.
	 */
	public function put( $method, $args = array(), $timeout = 10 ) {
		return $this->make_request( 'put', $method, $args, $timeout );
	}

	/**
	 * Performs the underlying HTTP request.
	 *
	 * @since 1.0.0
	 *
	 * @param  string     $http_verb The HTTP verb to use: get, post, put, patch, delete.
	 * @param  string     $method    The API method to be called.
	 * @param  array      $args      Assoc array of parameters to be passed.
	 * @param  integer    $timeout   Timeout limit for request in seconds.
	 * @return array|bool            Assoc array of decoded result.
	 */
	private function make_request( $http_verb, $method, $args = array(), $timeout = 10 ) {

		// Endpoint assumbly.
		$url = $this->api_endpoint . '/' . $method;

		// Begin with clean containers.
		$this->last_error         = '';
		$this->request_successful = false;
		$response                 = array( 'headers' => null, 'body' => null );
		$this->last_response      = $response;

		// Headers must include encoded API key.
		$headers = array(
			'Accept: application/vnd.api+json',
			'Content-Type: application/vnd.api+json',
			'Authorization' => 'Basic ' . base64_encode( 'user:'. $this->api_key )
		);

		// Request arguments.
		$this->last_request = array(
			'method'      => ( 'delete' == $http_verb ) ? 'DELETE' : $http_verb,
			'timeout'     => $timeout,
			'httpversion' => '1.0',
			'sslverify'   => $this->verify_ssl,
			'headers'     => $headers,
			'cookies'     => array(),
			'body'        => null,
		);

		// JSON encode $args for all except delete (which require the body to be null and unencoded).
		if ( 'delete' != $http_verb ) {
			$this->last_request['body'] = json_encode( $args );
		}

		// Handle WP HTTP API action.
		switch ( $http_verb ) {
			case 'delete':
			case 'post':
			case 'patch':
			case 'put':
				if ( ! empty( $args ) ) {
					$encoded = json_encode( $args );
					$this->last_request['body'] = $encoded;
				}
				$response = wp_remote_post( $url, $this->last_request );
				break;
			case 'get':
				$url = ( ! empty( $args ) ) ? add_query_arg( $args, $url ) : $url;
				$response = wp_remote_get( $url, $this->last_request );
				break;
		}

		// Put response into container if not an error.
		if ( ! is_wp_error( $response ) ) {
		  $this->last_response['headers'] = $response['headers'];
		  $this->last_response['body']    = $response['body'];
		}
		
		// Retrieve the response body.
		$body = wp_remote_retrieve_body( $this->last_response );
		
		// Return the JSON decoded response.
		return json_decode( $body, true );
	}

}
