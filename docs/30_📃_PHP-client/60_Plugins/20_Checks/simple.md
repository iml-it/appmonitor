# Simple #

## Description ##

The most simple variant is direct call with the resultcode and output text.

## Syntax ##

```php
$oMonitor->addCheck(
    array(
        "name" => "Dummy",
        "description" => "Dummy Test",
        "check" => array(
        "function" => "Simple",
            "params" => array(
                "result" => RESULT_OK,
                "value" => "The dummy test does nothing and was extremely successful",
                "count" => [float value],
                "visual"  => "[styling parameters]",
            ),
        ),
    )
);
```

## Parameters ##

| key        | type     | description |
|---         |---       |---
|result🔸    |(integer) | result code<br>After loading the client class you can use constants to keep the code more readable<br>RESULT_OK = OK (0)<br>RESULT_UKNOWN = unknown (1)<br>RESULT_WARNING = Warning (2) <br>RESULT_ERROR = Error (3) |
|value🔸     |(string)  | ouput text to describe the result|
|count       |(float)   | ptional; if a count exists in a check then a tile will be rendered |
|visual      |(string)  | optional; used if a "count" was given. see counter description [Description of response](../../../20_Client/20_Description_of_response.md)|

🔸 required

You can use the simple check to verify just anything that has no pre defined function
yet. Set a value for the text that should be visible and the result code (you should use the constants from table above to keep it more readable).

## Examples ##

None yet.
