<?php
/**
 * Mailchimp API v3 wrapper for WordPress applications.
 *
 * A wrapper class for the Mailchimp API version 3 using the WordPress
 * HTTP API.  Based on the Mailchimp API class by Drew McLellan
 * (https://github.com/drewm/mailchimp-api) and modified for use in
 * WordPress without cURL, and instead uses WP's wp_remote_post() and
 * wp_remote_get(). Formatted to WordPress coding standards.
 *
 * Mailchimp API v3:   https://developer.mailchimp.com
 * WordPress HTTP API: https://developer.wordpress.org/plugins/http-api/
 * This class:         https://github.com/rocketgeek/mailchimp-api
 * Drew's class:       https://github.com/drewm/mailchimp-api
 *
 * This library is open source and Apache-2.0 licensed. I hope you find it 
 * useful for your project(s). Attribution is appreciated ;-)
 *
 * @package    RocketGeek_Mailchimp_API
 * @version    1.1.0
 * @author     Chad Butler <https://butlerblog.com>
 * @author     RocketGeek <https://rocketgeek.com>
 * @copyright  Copyright (c) 2016-2021 Chad Butler
 * @license    Apache-2.0
 *
 * Copyright [2021] Chad Butler, RocketGeek
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *     https://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
if ( ! class_exists( 'RocketGeek_Mailchimp_API' ) ) :
class RocketGeek_Mailchimp_API {

	/**
	 * The Mailchimp API key.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string
	 */
	private $api_key;

	/**
	 * Default Mailchimp API endpoint.
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
	 * @param string $api_key      Your Mailchimp API key.
	 * @param string $api_endpoint Optional custom API endpoint.
	 */
	public function __construct( $api_key, $api_endpoint = null ) {
		$this->api_key = $api_key;

		// Check the API key for validity.
		if ( null === $api_endpoint ) {
			
			if ( strpos( $this->api_key, '-' ) === false ) {
				//wp_die( __( 'Invalid Mailchimp API key supplied.', 'rg_mc_api' ) );
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
		return new RocketGeek_Mailchimp_API_Batch( $this, $batch_id );
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
		return $this->last_error = ( null != $this->last_error ) ? $this->last_error : false;
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

		// Endpoint assembly.
		$url = $this->api_endpoint . '/' . $method;

        $this->last_error         = '';
        $this->request_successful = false;
        $response                 = array('headers' => null, 'body' => null);
        $this->last_response      = $response;

        $this->last_request = array(
            'method'  => $http_verb,
            'path'    => $method,
            'url'     => $url,
            'body'    => '',
            'timeout' => $timeout,
        );

	    $request_args = array(
	    	'headers' => array(
			    'Accept'        => 'application/vnd.api+json',
			    'Content-Type'  => 'application/vnd.api+json',
			    'Authorization' => 'apikey ' . $this->api_key
		    ),
		    'user-agent' => 'RocketGeek/Mailchimp-API/3.0 (github.com/rocketgeek/mailchimp-api)',
		    'timeout'    => apply_filters( 'mailchimp_sync_api_timeout', $timeout ),
		    'sslverify'  => $this->verify_ssl,
		    'method'     => strtoupper( $http_verb )
	    );

		/**
		 * Filter request args.
		 * 
		 * @since 1.1.0
		 * 
		 * @param  array   $args
		 * @param  string  $method
		 * @param  array   $request_args
		 */
	    $args = apply_filters( 'rktgk_mc_api_request_args', $args, $method, $request_args );

	    if ( 'get' !== $http_verb ) {
		    $request_args['body'] = wp_json_encode( $args );
	    }
	    else {
		    $request_args['body'] = $args;
	    }

	    $wp_response = wp_remote_request( $url, $request_args );
	    if ( is_wp_error( $wp_response ) ) {
		    $this->last_error = $wp_response->get_error_code() . ': ' . $wp_response->get_error_message();
	    }

        $response['body']    = wp_remote_retrieve_body( $wp_response );
        $response['headers'] = wp_remote_retrieve_headers( $wp_response );
	    $this->last_request['headers'] = $request_args['headers'];

        $formatted_response = $this->format_response( $wp_response );

        $this->deterine_success( $wp_response, $formatted_response );

        return $formatted_response;
		
	}
	
    /**
     * Decode the response and format any error messages for debugging.
	 * 
	 * @since 1.1.0
	 * 
     * @param  array        $response The response from the curl request
     * @return array|false            The JSON decoded into an array
     */
    private function format_response( $response ) {
		$this->last_response = $response;

		if ( ! is_wp_error( $response ) && ! empty( $response['body'] ) ) {
			return json_decode( $response['body'], true );
		}

		return false;
	}

    /**
     * Check if the response was successful or a failure. If it failed, store the error.
	 * 
	 * @since 1.1.0
	 * 
     * @param  array       $response            The response from the curl request
     * @param  array|false $formatted_response  The response body payload from the curl request
     * @return bool                             If the request was successful
     */
    private function deterine_success( $response, $formatted_response ) {
		$status = $this->find_http_status( $response, $formatted_response );

		if ( $status >= 200 && $status <= 299 ) {
			$this->request_successful = true;
			return true;
		}

		if ( isset( $formatted_response['detail'] ) ) {
			$this->last_error = sprintf( '%d: %s', $formatted_response['status'], $formatted_response['detail'] );
			return false;
		}

		$this->last_error = 'Unknown error, call get_last_response() to find out what happened.';
		return false;
	}

    /**
     * Find the HTTP status code from the headers or API response body.
	 * 
	 * @since 1.1.0
	 * 
     * @param   array       $response            The response from the curl request
     * @param   array|false $formatted_response  The response body payload from the curl request
     * @return  int                              HTTP status code
     */
    private function find_http_status( $response, $formatted_response ) {
		$status = wp_remote_retrieve_response_code( $response );
		if ( is_wp_error( $response ) ) {
			if ( empty( $status ) ) {
				return 418;
			}
			return $status;
		}

		if ( ! empty( $status ) ) {
			return  $status;
		}
		elseif ( !empty( $response['body'] ) && isset( $formatted_response['status'] ) ) {
			return (int)$formatted_response['status'];
		}

		return 418;
	}
	
}
endif;