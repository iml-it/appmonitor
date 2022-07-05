# Ping (WIP) #

## Description ##

Check if the local server or another host is listening to a given port number.

## Syntax ##

```php
$oMonitor->addCheck(
	array(
		"name" => "Ping",
		"description" => "ping to a server",
		"check" => array(
			"function" => "Ping",
			"params" => array(
				"host" => [hostname],
				"timeout" => [time],
			),
		),
	)
);
```

## Parameters ##

| key      | type     | description |
|---       |---       |---
|host      |(string)  |optional: hostname to connect to; if unavailable 127.0.0.1 will be tested
|timeout   |(integer) |optional timeout in sec; default: 5

## Examples ##

none.
