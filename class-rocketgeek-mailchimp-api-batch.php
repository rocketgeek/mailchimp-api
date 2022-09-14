<?php
/**
 * A Mailchimp Batch operation for WordPress applications. 
 *
 * This is a wrapper class of batch operations for the RocketGeek
 * Mailchimp API for WordPress. Based on the Mailchimp API class 
 * by Drew McLellan (https://github.com/drewm/mailchimp-api) and 
 * modified for use in WordPress without cURL, using WP's 
 * wp_remote_post() and wp_remote_get(). Formatted to WordPress 
 * coding standards.
 *
 * Mailchimp API v3:   https://developer.mailchimp.com
 * WordPress HTTP API: https://developer.wordpress.org/plugins/http-api/
 * This class:         https://github.com/rocketgeek/mailchimp-api
 * Drew's class:       https://github.com/drewm/mailchimp-api
 *
 * @see https://developer.mailchimp.com/documentation/mailchimp/reference/batches/
 *
 * @package    RocketGeek_Mailchimp_API
 * @version    1.1.0
 * @author     Chad Butler <https://butlerblog.com>
 * @author     RocketGeek <https://rocketgeek.com>
 * @copyright  Copyright (c) 2016-2022 Chad Butler
 * @license    Apache-2.0
 *
 * Copyright [2022] Chad Butler, RocketGeek
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
if ( ! class_exists( 'RocketGeek_Mailchimp_API_Batch' ) ) :
class RocketGeek_Mailchimp_API_Batch {

	/**
	 * Internal container for the Mailchimp API object.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    
	 */
	private $mailchimp;

	/**
	 * Container for the current batch operation.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array
	 */
	private $operations = array();
	
	/**
	 * Container for the batch ID.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    
	 */
	private $batch_id;

	/**
	 * Class constructor. 
	 *
	 * @since 1.0.0
	 *
	 * @param object  $mailchimp
	 * @param object  $mailchimp_api_obj
	 * @param string  $batch_id
	 */
	public function __construct( RocketGeek_Mailchimp_API $mailchimp_api_obj, $batch_id = null ) {
		$this->mailchimp = $mailchimp_api_obj;
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
			if ( 'GET' == $http_verb ) {
				$key = 'params';
				$operation[ $key ] = $args;
			} else {
				$key = 'body';
				$operation[ $key ] = json_encode( $args );
			}
		}

		$this->operations[] = $operation;
	}
}
endif;