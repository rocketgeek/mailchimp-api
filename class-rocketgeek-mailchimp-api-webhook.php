<?php
/**
 * A Mailchimp Webhook request for WordPress applications. 
 *
 * This is a wrapper class of webhook operations for the RocketGeek
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
 * @author Chad Butler
 * @version 1.1.0
 *
 * @see https://developer.mailchimp.com/documentation/mailchimp/guides/about-webhooks/
 */
if ( ! class_exists( 'RocketGeek_Mailchimp_API_Webhook' ) ) :
class RocketGeek_Mailchimp_API_Webhook {

	/**
	 *
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    array
	 */
	private static $event_subscriptions = array();

	/**
	 *
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    string 
	 */
	private static $received_webhook = null;

	/**
	 * Subscribe to an incoming webhook request. The callback will be invoked when a matching webhook is received.
	 *
	 * @since 1.1.0
	 *
	 * @param  string   $event    Name of the webhook event, e.g. subscribe, unsubscribe, campaign
	 * @param  callable $callback A callable function to invoke with the data from the received webhook
	 * @return void
	 */
	public static function subscribe( $event, callable $callback ) {
		if ( ! isset( self::$event_subscriptions[ $event ] ) ) self::$event_subscriptions[ $event ] = array();
		self::$event_subscriptions[ $event ][] = $callback;
		self::receive();
	}

	/**
	 * Retrieve the incoming webhook request as sent.
	 *
	 * @since 1.1.0
	 *
	 * @param  string      $input An optional raw POST body to use instead of php://input - mainly for unit testing.
	 * @return array|false        An associative array containing the details of the received webhook
	 */
	public static function receive( $input = null ) {
		if ( is_null( $input ) ) {
			if ( self::$received_webhook !== null ) {
				$input = self::$received_webhook;
			} else {
				$input = file_get_contents( "php://input" );
			}
		}
		if ( ! is_null( $input ) && $input != '' ) {
			return self::process_webhook( $input );
		}
		return false;
	}

	/**
	 * Process the raw request into a PHP array and dispatch any matching subscription callbacks.
	 *
	 * @since 1.1.0
	 *
	 * @param  string      $input The raw HTTP POST request
	 * @return array|false        An associative array containing the details of the received webhook
	 */
	private static function process_webhook( $input ) {
		self::$received_webhook = $input;
		parse_str( $input, $result );
		if ( $result && isset( $result['type'] ) ) {
			self::dispatch_webhook_event( $result['type'], $result['data'] );
			return $result;
		}
		return false;
	}

	/**
	 * Call any subscribed callbacks for this event.
	 *
	 * @since 1.1.0
	 *
	 * @param  string $event The name of the callback event
	 * @param  array  $data  An associative array of the webhook data
	 * @return void
	 */
	private static function dispatch_webhook_event( $event, $data ) {
		if ( isset( self::$event_subscriptions[ $event ] ) ) {
			foreach ( self::$event_subscriptions[ $event ] as $callback ) {
				$callback( $data );
			}
			// reset subscriptions
			self::$event_subscriptions[ $event ] = array();
		}
	}
}
endif;