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
				"critical" => [days-before-cert-expires],
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
|warning   |(integer) |optional: count of days to warn; default=21
|critical  |(integer) |optional: count of days to raise critical; default=5

I recommend to set verify to *true*. If you should get a warning like 

    PHP Warning:  stream_socket_client(): SSL operation failed with code 1. OpenSSL Error messages:
    error:14090086:SSL routines:ssl3_get_server_certificate:certificate verify failed in (...)appmonitor-checks.class.php on line NNN

... then set it back to *false* to make a test for expiration only.

It returns OK if

- ssl connect is successful
- certificate is valid more than 30 days (or given "warning" limit)

You get a warning if it expires soon:

- "Expires soon." - certificate expires in less than 21 days (or given "warning" limit)
- "Expires very soon!" - certificate expires very soon in less than 5 days (or given "critcal" limit)

Even with reaching critical date the application status is "warning" because the application is still functional.

You get an error, if

- it is not a ssl target
- ssl connect fails
- certificate is expired

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

To not to repeat the same code you can use an include to a file located in public_html/client/plugins/apps/:

```php
include 'shared_check_ssl.php';
```
