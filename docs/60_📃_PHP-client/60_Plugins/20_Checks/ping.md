# Ping #

## Description ##

Check if the local server or another host is listening to a given port number.

## Syntax ##

```php
$oMonitor->addCheck(
    [
        "name" => "Ping",
        "description" => "ping to a server",
        "check" => [
            "function" => "Ping",
            "params" => [
                "host" => [hostname]
            ],
        ],
    ]
);
```

## Parameters ##

| key      | type     | description
|---       |---       |---
|host      |(string)  |optional: hostname or ip address to connect to; if unavailable 127.0.0.1 will be tested

## Examples ##

```php
$oMonitor->addCheck(
    [
        "name" => "Ping",
        "description" => "ping to www.example.com",
        "check" => [
            "function" => "Ping",
            "params" => [
                "host" => "www.example.com"
            ],
        ],
    ]
);
```
