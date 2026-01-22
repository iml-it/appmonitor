# ApacheProcesses #

## Description ##

Get the available, waiting, active and inactive apache worker processes and render a tile with the
active processes.

The returned counts of active workers is for all domains running on the same host.

## Requirements ##

- works for apache httpd only; not for other webservers (i.e. NginX, ...)
- server status must be available (default: <http://localhost/server-status>) - to do so, enable mod_status and verify that ExtendedStatus = On)
- IMPORTANT: limit the access to the server status page to the required systems (i.e. IP restriction).

## Syntax ##

```php
$oMonitor->addCheck(
    [
        "name" => "plugin ApacheProcesses",
        "description" => "check count running Apache processes",
        "check" => [
            "function" => "ApacheProcesses",
            "params" => [
                "url"     => [url for apache httpd server status page],
                "warning" => [warn on min N percent of active workers],
                "error"   => [error limit],
            ],
        ],
        "worstresult" => RESULT_OK
    ]
);
```

## Parameters ##

| key        | type     | description
|---         |---       |---
|url         |(string)  | optional: override https server-status page; default is <http://localhost/server-status>; Use it if the protocol to localhost is not http, but https or if it requires an authentication
|warning     |(integer) | optional: limit to switch to warning (in percent); default: 50
|error       |(integer) | optional: limit to switch to error (in percent); default: 75

If a result is not available the result is UNKNOWN

We recommend to use ```"worstresult" => RESULT_OK```.
The effect is: if the load check returns anything else then OK in the backend
the client check switches to the corresponding color, but it has no effect to the total
result for the availability of the application.

## Examples ##

```php
$oMonitor->addCheck(
    [
        "name" => "plugin ApacheProcesses",
        "description" => "check count running Apache processes",
        "check" => [
            "function" => "ApacheProcesses",
            "params" => [
                "url" => "https://localhost/status",
                "warning" => 30,
                "error" => 50,
            ],
        ],
        "worstresult" => RESULT_OK
    ]
);
```
