# File #

## Description ##

Check if a file for file, link or directory. Use the parameter "filename" to set the full filename.

Other given parameters are flags to check. Its values can be set to true (flag must must be true) or false (flag must fail to return a true result). Missing flags won't be checked. 

Giving just a filename without any other flag returns true.

## Syntax ##

```php
$oMonitor->addCheck(
    array(
        "name" => "file check",
        "description" => "file check",
        "check" => array(
            "function" => "File",
            "params" => array(
                "filename" => [string: Full Path],
                "exists"   => [boolean],
                "[flag]"   => [boolean],
            ),
        ),
    )
);
```

## Parameters ##

| key      | type     | description |
|---       |---       |---
|filename🔸|(string)  |filename or directory to check
|exists    |(boolean) |"filename" must exist/ must be absent
|dir       |(boolean) |filetype directory
|file      |(boolean) |filetype file
|link      |(boolean) |filetype symbolic link
|executable|(boolean) |flag executable
|readable  |(boolean) |flag is readable
|writable  |(boolean) |flag is writable

## Examples ##

### Example 1 ###

Check if "filename" is a directory and is writable

```php
$oMonitor->addCheck(
    array(
        "name" => "tmp subdir",
        "description" => "Check cache storage",
        "check" => array(
            "function" => "File",
            "params" => array(
                "filename" => $sApproot . "/server/tmp",
                "dir"      => true,
                "writable" => true,
            ),
        ),
    )
);
```

### Example 2 ###

With *"exists" => false* you can check if a file does not exist (flag is checked that it is not matching).

```php
$oMonitor->addCheck(
    array(
        "name" => "Maintenance mode",
        "description" => "Check if Maintenance mode is not activated by a flag file",
        "check" => array(
            "function" => "File",
            "params" => array(
                "filename" => "/var/www/maintenance_is_active.txt",
                "exists"      => false,
            ),
        ),
    )
);
```
