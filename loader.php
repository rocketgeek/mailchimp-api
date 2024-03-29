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

global $rktgk_mcapi;
if ( ! function_exists( 'rktgk_mcapi_init' ) && ! is_object( $rktgk_mcapi ) ):
    function rktgk_mcapi_init( $api_key, $api_endpoint ) {
        include_once( 'functions.php' );
        include_once( 'class-rocketgeek-mailchimp-api.php' );
        include_once( 'class-rocketgeek-mailchimp-api-batch.php' );
        include_once( 'class-rocketgeek-mailchimp-api-webhook.php' );
        $rktgk_mcapi = new RocketGeek_Mailchimp_API( $api_key, $api_endpoint );
    }
endif;