<?php
/**
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

/**
 * Queries the Maichimp API.
 *
 * @since 1.0.0
 *
 * @param   array    $args
 * @param   array    $assoc_args (optional)
 * @return  array    $result
 */
function rktgk_mcapi_query( $args, $assoc_args = false ) {
	
	global $rktgk_mcapi;
	
	$verb = array_shift( $args );
	$endpoint = '';
	foreach ( $args as $key => $arg ) {
		if ( 'query_args' !== $key ) {
			$endpoint .= $arg . '/';
		}
	}
	$endpoint = untrailingslashit( $endpoint );
	if ( isset( $args['query_args'] ) ) {
		$query = http_build_query( $args['query_args'] );
		$endpoint .= "?" . $query;
	}
	if ( $assoc_args ) {
		return $rktgk_mcapi->{$verb}( $endpoint, $assoc_args );
	} else {
		return $rktgk_mcapi->{$verb}( $endpoint );
	}
}

/**
 * Gets the hash of a user email for quering the API.
 *
 * @since 1.0.0
 *
 * @param  string  $email  The email address.
 * @return string  $hash   The hashed version of the email for API queries.
 */
function rktgk_mcapi_get_subscriber_hash( $email ) {
	global $rktgk_mcapi;
	return $rktgk_mcapi->subscriber_hash( $email );
}