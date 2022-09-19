# Diskfree #

## Description ##

Check if a given filesystem / directory that it has enough space.

## Syntax ##

```php
$oMonitor->addCheck(
    array(
        "name" => "check file storage",
        "description" => "The file storage have some space left",
        "check" => array(
            "function" => "Diskfree",
            "params" => array(
                "directory" => "[directory]",
                "warning"   => [size],
                "critical"  => [size],
            ),
        ),
    )
);
```

## Parameters ##

| key        | type     | description |
|---         |---       |---
|result      |(integer) | result code <span class="required">(*)</span><br>After loading the client class you can use constants to keep the code more readable<br>RESULT_OK = OK (0)<br>RESULT_UKNOWN = unknown (1)<br>RESULT_WARNING = Warning (2) <br>RESULT_ERROR = Error (3) |
|value       |(string)  | ouput text to describe the result <span class="required">(*)</span> |
|count       |(float)   | optional; if a count exists in a check then a tile will be rendered |
|visual      |(string)  | optional; used if a "count" was given. see counter description [Description of response](../../../20_Client/20_Description_of_response.md)|

| key      | type     | description |
|---       |---       |---
|directory |(string)  | directory to check  <span class="required">(*)</span>
|warning   |{integer\|string} | size for warning level
|critical  |(integer\|string) | size for critical level <span class="required">(*)</span>

Remark to the [size] value:

The values for warning and critical

- must be integer OR
- integer or float added by a size unit (see below)
- warning level must be higher than critical value
- units can be mixed in warning and critical value

supported size units are 

- 'B' byte
- 'KB' kilobyte
- 'MB' megabyte
- 'GB' gigabyte
- 'TB' terabyte

Example for Diskfree size params:

```php
"warning"   => "1.25GB",
"critical"  => "500.7MB",
```

## Examples ##

None yet.
