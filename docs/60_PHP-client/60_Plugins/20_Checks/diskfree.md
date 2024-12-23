# Diskfree #

## Description ##

Check if a given filesystem / directory that it has enough space.

## Syntax ##

```php
$oMonitor->addCheck(
    [
        "name" => "check file storage",
        "description" => "The file storage have some space left",
        "check" => [
            "function" => "Diskfree",
            "params" => [
                "directory" => "[directory]",
                "warning"   => [size],
                "critical"  => [size],
            ],
        ],
    ]
);
```

## Parameters ##

| key        | type     | description
|---         |---       |---
|directory🔸 |(string)  | directory to check
|warning     |{integer\|string} | size for warning level
|critical🔸  |(integer\|string) | size for critical level

🔸 required

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
