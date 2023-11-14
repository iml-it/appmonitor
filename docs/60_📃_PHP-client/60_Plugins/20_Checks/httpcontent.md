# HttpContent #

## Description ##

This check verifies if a given url can be requested. Optionally you can test if it follows wanted rules:

* specific http status code
* http response header / response body contains/ not containtains given text text
* http response header / response body matches a given regex

## Syntax ##

```php
$oMonitor->addCheck(
    array(
        "name" => "HttpContent 1",
        "description" => "check if the example website sends a response",
        "check" => array(
            "function" => "HttpContent",
            "params" => array(
                "url" => "http://www.example.com/",
                "[option]" => "[see parameters table]",
            ),
        ),
    )
);
```

## Parameters ##

| key              | type     | description |
|---               |---       |---
|url🔸             |(string)  |url to fetch
|userpwd           |(string)  |set user and password; syntax: "[username]:[password]"
|timeout           |(integer) |optional timeout in sec; default: 5
|headeronly        |(boolean) |optional flag to fetch http response herader only (HEAD request); default: false = returns header and body; 
|follow            |(boolean) |optional flag to follow a location; default: false = do not follow; If you set it to true it ries to follow (but this is not a safe method)
|sslverify         |boolean   |flag: enable/ disable verification of ssl certificate; default: true (verification is on)
|status            |(integer) |test for an expected http status code; if none is given then test fails on status 400 and greater
|headercontains    |(string)  |test for a string in the http response header; it returns OK if the text was found
|headernotcontains |(string)  |test for a string in the http response header; it returns OK if the text was not found
|headerregex       |(string)  |test for a regex in the http response header; it returns OK if the regex matches
|bodycontains      |(string)  |test for a string in the http response body; it returns OK if the text was found
|bodynotcontains   |(string)  |test for a string in the http response body; it returns OK if the text was not found
|bodyregex         |(string)  |test for a regex in the http response body; it returns OK if the regex matches;

🔸 required

Remarks:

The checks for text strings are case sensitive. If you need a case insensitive test use a regex with "i" modifyer like in example 3.

## Examples ##

### Example 1 ###

Check if a http reponse is successful.

```php
$oMonitor->addCheck(
    array(
        "name" => "HttpContent 1",
        "description" => "check if the example website sends a response",
        "check" => array(
            "function" => "HttpContent",
            "params" => array(
                "url" => "http://www.example.com/",
            ),
        ),
    )
);
```

### Example 2 ###

Check if a http reponse is successful and contains a wanted text.

```php
$oMonitor->addCheck(
    array(
        "name" => "HttpContent 1",
        "description" => "check if the example website sends a response and contains hello in the text",
        "check" => array(
            "function" => "HttpContent",
            "params" => array(
                "url" => "http://www.example.com/",
                "bodycontains" => "hello",
            ),
        ),
    )
);
```

### Example 3 ###

Check availability of an api using user and password.
`$aConfig["awx"]` is an example configuration hash with subkeys url, user and pasword.

```php
if(isset($aConfig['awx']) && isset($aConfig['awx']['url'])){

    $oMonitor->addCheck(
        array(
            "name" => "AWX API",
            "description" => "check if AWX api is available",
            "group" => "network",
            "check" => [
                "function" => "HttpContent",
                "params" => [
                    ['url'] => $aConfig['awx']['url'],
                    ['userpwd'] => $aConfig['awx']['user'].':'.$aConfig['awx']['password'],
                ],
            ],
        )
    );
}
```

### Example 4 ###

Check the status code: Is the http status a 307 and points to a wanted target?

```php
$oMonitor->addCheck(
    array(
        "name" => "HttpContent 2",
        "description" => "check if the example website is a redirect with 307",
        "check" => array(
        "function" => "HttpContent",
            "params" => array(
                "url" => "https://www.example.com/redirect",
                "headeronly" => true,
                "status" => 307,
                "headerregex" => "#Location: https://www.example.com/mytarget#i",
            ),
        ),
    )
);
```

In the same way - by setting a status code to 40x - you also can check if sensitive information is not accessible.

```php
$oMonitor->addCheck(
    array(
        "name" => "Secure config",
        "description" => "check if config is not readable wit a browser",
        "check" => array(
        "function" => "HttpContent",
            "params" => array(
                "url" => "https://www.example.com/config/sample.json",
                "headeronly" => true,
                "status" => 403,
            ),
        ),
    )
);
```
