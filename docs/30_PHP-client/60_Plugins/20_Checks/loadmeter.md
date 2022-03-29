# Loadmeter #

## Description ##

Get system load and render it as a tile.

## Requirements ##

- The system load is available on \*NIX systems only.

## Syntax ##

```php
$oMonitor->addCheck(
    array(
        "name" => "plugin Load",
        "description" => "check current load",
        "check" => array(
            "function" => "Loadmeter",
            "params" => array(
                "warning" => [float],
                "error" => [float],
            ),
        ),
        "worstresult" => RESULT_OK
    )
);
```

## Parameters ##


| key        | type     | description
|---         |---       |---
|warning     |(float)   | warning level
|error       |(float)   | error level


If the load value is not available the result is UNKNOWN

If no warning or error value are given then the result is always OK.

We recommend to use ```"worstresult" => RESULT_OK```. 
The effect is: if the load check returns anything else then OK in the backend
the client check switches to the corresponding color, but it has no effect to the total 
result for the availability of the application.

## Examples ##

```php
$oMonitor->addCheck(
    array(
        "name" => "plugin Load",
        "description" => "check current load",
        "check" => array(
            "function" => "Loadmeter",
            "params" => array(
                "warning" => 1.0,
                "error" => 3,
            ),
        ),
        "worstresult" => RESULT_OK
    )
);
```
