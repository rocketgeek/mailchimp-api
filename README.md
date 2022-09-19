Mailchimp API for WordPress
===========================

A simple Mailchimp API v3 wrapper in PHP based on Drew McLellan's project, rewritten to use WordPress' HTTP API and packaged for simple use in plugins and themes without the need for Composer.

Requires PHP 5.4 and WordPress

Installation
------------

Add the library to your project and include the main file.

```php
include( 'your/actual/path/class-rocketgeek-mailchimp-api.php' ); 
```

If you wish to use the batch request or webhook interfaces, you'll also need to download and include the `Batch.php` or `Webhook.php` files:

```php
include( 'your/actual/path/class-rocketgeek-mailchimp-api-batch.php' ); 
include( 'your/actual/path/class-rocketgeek-mailchimp-api-webhook.php' ); 
```

These are optional. If you're not using batches or webhooks you can just skip them. You can always come back and add them later.

Examples
--------

Start by adding the class and creating an instance with your API key

```php
$my_wp_chimp = new RocketGeek_Mailchimp_API( $my_api_key );
```

Note: If you're using this in a plugin or theme project, your API key can (and probably should) be coming from an option in WP. Also, you can optionally specify an $api_endpoint as a second argument if you need a second instance or you need your endpoint to be something other than 'https://<dc>.api.mailchimp.com/3.0'.

You can list all the mailing lists (with a `get` on the `lists` method)

```php
$result = $my_wp_chimp->get( 'lists' );

print_r( $result );
```

Subscribe someone to a list (with a `post` to the `lists/{listID}/members` method):

```php
$list_id = 'b1234346';

$result = $my_wp_chimp->post( "lists/$list_id/members", [
		'email_address' => 'davy@example.com',
		'status'        => 'subscribed',
	] );

print_r( $result );
```

Update a list member with more information (using `patch` to update):

```php
$list_id = 'b1234346';
$subscriber_hash = $my_wp_chimp->subscriber_hash( 'davy@example.com' );

$result = $my_wp_chimp->patch( "lists/$list_id/members/$subscriber_hash", [
		'merge_fields' => ['FNAME'=>'Davy', 'LNAME'=>'Jones'],
		'interests'    => ['2s3a384h' => true],
	] );

print_r( $result );
```

Remove a list member using the `delete` method:

```php
$list_id = 'b1234346';
$subscriber_hash = $my_wp_chimp->subscriber_hash( 'davy@example.com' );

$my_wp_chimp->delete( "lists/$list_id/members/$subscriber_hash" );
```

Quickly test for a successful action with the `success()` method:

```php
$list_id = 'b1234346';

$result = $my_wp_chimp->post( "lists/$list_id/members", [
		'email_address' => 'davy@example.com',
		'status'        => 'subscribed',
	] );

if ( $my_wp_chimp->success() ) {
	print_r( $result );	
} else {
	echo $my_wp_chimp->get_last_error();
}
```

Batch Operations
----------------

The Mailchimp [Batch Operations](https://developer.mailchimp.com/documentation/mailchimp/guides/how-to-use-batch-operations/) functionality enables you to complete multiple operations with a single call. A good example is adding thousands of members to a list - you can perform this in one request rather than thousands.

```php
$my_wp_chimp       = new RocketGeek_Mailchimp_API( $my_api_key );
$my_wp_chimp_batch = $my_wp_chimp->new_batch();
```

You can then make requests on the `Batch` object just as you would normally with the `Mailchimp` object. The difference is that you need to set an ID for the operation as the first argument, and also that you won't get a response. The ID is used for finding the result of this request in the combined response from the batch operation.

```php
$my_wp_chimp_batch->post( "op1", "lists/$list_id/members", [
		'email_address' => 'micky@example.com',
		'status'        => 'subscribed',
	] );

$my_wp_chimp_batch->post( "op2", "lists/$list_id/members", [
		'email_address' => 'michael@example.com',
		'status'        => 'subscribed',
	] );

$my_wp_chimp_batch->post( "op3", "lists/$list_id/members", [
		'email_address' => 'peter@example.com',
		'status'        => 'subscribed',
	] );
```

Once you've finished all the requests that should be in the batch, you need to execute it.

```php
$result = $my_wp_chimp_batch->execute();
```

The result includes a batch ID. At a later point, you can check the status of your batch:

```php
$my_wp_chimp->new_batch( $batch_id );
$result = $Batch->check_status();
```

When your batch is finished, you can download the results from the URL given in the response. In the JSON, the result of each operation will be keyed by the ID you used as the first argument for the request.

Webhooks
--------

**Note:** Use of the Webhooks functionality requires at least PHP 5.4, but if you're still out there running this then you've got serious security issues.

Mailchimp [webhooks](https://kb.mailchimp.com/integrations/other-integrations/how-to-set-up-webhooks) enable your code to be notified of changes to lists and campaigns.  If you want to build a "two-way" application that receives information from Mailchimp as well as sending, this is what you need.

When you set up a webhook you specify a URL on your server for the data to be sent to. This wrapper's Webhook class helps you catch that incoming webhook in a tidy way. It uses a subscription model, with your code subscribing to whichever webhook events it wants to listen for. You provide a callback function that the webhook data is passed to.

To listen for the `unsubscribe` webhook:

```php
RocketGeek_Mailchimp_API_Webhook::subscribe( 'unsubscribe', function( $data ) {
	print_r( $data );
});
```

At first glance the _subscribe/unsubscribe_ looks confusing - your code is subscribing to the Mailchimp `unsubscribe` webhook event. The callback function is passed as single argument - an associative array containing the webhook data.

If you'd rather just catch all webhooks and deal with them yourself, you can use:

```php
$result = RocketGeek_Mailchimp_API_Webhook::receive();
print_r( $result );
```

There doesn't appear to be any documentation for the content of the webhook data. It's helpful to use something like [ngrok](https://ngrok.com) for tunneling the webhooks to your development machine - you can then use its web interface to inspect what's been sent and to replay incoming webhooks while you debug your code.

Troubleshooting
---------------

To get the last error returned by either the HTTP client or by the API, use `getLastError()`:

```php
echo $my_wp_chimp->getLastError();
```

For further debugging, you can inspect the headers and body of the response:

```php
print_r($my_wp_chimp->getLastResponse());
```

If you suspect you're sending data in the wrong format, you can look at what was sent to Mailchimp by the wrapper:

```php
print_r($my_wp_chimp->getLastRequest());
```

If your server's CA root certificates are not up to date you may find that SSL verification fails and you don't get a response. The correction solution for this [is not to disable SSL verification](https://snippets.webaware.com.au/howto/stop-turning-off-curlopt_ssl_verifypeer-and-fix-your-php-config/). The solution is to update your certificates. If you can't do that, there's an option at the top of the class file. Please don't just switch it off without at least attempting to update your certs -- that's lazy and dangerous. You're not a lazy, dangerous developer are you?

If you have **high-level implementation questions about your project** ("How do I add this to WordPress", "I've got a form that takes an email address...") please **take them to somewhere like StackOverflow**. If you think you've found a bug, or would like to discuss a change or improvement, feel free to raise an issue and we'll figure it out between us.

Contributing
------------

This is a fairly simple wrapper, but it has been made much better by contributions from those using it. If you'd like to suggest an improvement, please raise an issue to discuss it before making your pull request.

Pull requests for bugs are more than welcome - please explain the bug you're trying to fix in the message.

## Versioning

I use [SemVer](https://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/rocketgeek/jquery_tabs/tags). 

## Authors

* **Chad Butler** - [ButlerBlog](https://github.com/butlerblog)
* **RocketGeek** - [RocketGeek](https://github.com/rocketgeek)

* Based on original code from Drew McLellan (https://github.com/drewm/mailchimp-api)

## License

This project is licensed under the Apache-2.0 License - see the [LICENSE](LICENSE) file for details.

I hope you find this project useful. If you use it your project, attribution is appreciated.
