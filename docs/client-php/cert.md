<style>
	.required{color:#f22;}
	.optional{color:#888;}
</style>

[UP: PHP client: default checks](../client-php-checks.md)

--- 

# Cert #


## Description ##

Check if a SSL certificate is still valid ... and does not expire soon.


## Syntax ##

```php
$oMonitor->addCheck(
	array(
		"name" => "Certificate check",
		"description" => "Check if SSL cert is valid and does not expire soon",
		"check" => array(
			"function" => "Cert",
			"params" => array(
				"url"      => [url-to-check],
				"verify"   => [flag-for-verification],
				"warning"  => [days-before-cert-expires],
			),
		),
	)
);
```


## Parameters ##

| key      | type     | description |
|---       |---       |---
|url       |(string)  |url to connect check i.e. https://example.com:3000; default: own protocol + server of your webapp
|verify    |(boolean) |optional: flag verify certificate; default = true
|warning   |(integer) |optional: count of days to warn; default=30

I recommend to set verify to *true*. If you should get a warning like 

    PHP Warning:  stream_socket_client(): SSL operation failed with code 1. OpenSSL Error messages:
    error:14090086:SSL routines:ssl3_get_server_certificate:certificate verify failed in (...)appmonitor-checks.class.php on line NNN

... then set it back to *false* to make a test for expiration only.


It returns OK if 
- ssl connect is successful
- valid-to date expires in more than 30 days (or given limit)

You get a warning if it expires soon.

You get an error, if 
- it is not a ssl target
- certificate is expired
- ssl connect fails


## Examples ##

In most cases you can use this snippet to check the ssl certificate of the own instance.

```php
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']){
    $oMonitor->addCheck(
        array(
            "name" => "Certificate check",
            "description" => "Check if SSL cert is valid and does not expire soon",
            "check" => array(
                "function" => "Cert",
            ),
        )
    );
}
```
