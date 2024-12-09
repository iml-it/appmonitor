# Phpmodules #

## Description ##

Check if given php modules are installed. You can define required and optional php modules.

* The result is OK if all modules are found.
* It results in an error if one of the required modules is missing. (*)
* You get a warning if all required modules are present and an optional PHP module is missing

(*) Remark: Here is no implemented validation for a correct module name.
A warning or error can be raised on wrong written module names too.

## Syntax ##

```php
$oMonitor->addCheck(
    [
        "name" => "PHP modules",
        "description" => "Check needed PHP modules",
        "check" => [
            "function" => "Phpmodules",
            "params" => [
                "required" => [list-of-package-names],
                "optional" => [list-of-package-names],
            ],
        ],
    ]
);
```

## Parameters ##

| key      | type     | description
|---       |---       |---
|required  |(array)   |list of required php modules
|optional  |(array)   |list of optional php modules

The names of the modules are those in the output of `php -m`.

## Examples ##

### Check required modules ###

Ensure that named PHP modules are installed.

```php
$oMonitor->addCheck(
    [
        "name" => "PHP modules",
        "description" => "Check needed PHP modules",
        "check" => [
            "function" => "Phpmodules",
            "params" => [
                "required" => ["curl", "pdo"]
            ],
        ],
    ]
);
```

### Prevent warning on missing optional module ###

To prevent that the total status of an application switches to warning if an optional
module is missing you need to

* check optional modules without required modules: use 2 seprate checks
* add attribute `"worstresult" => RESULT_OK` in the check for optional modules

```php
$oMonitor->addCheck(
    [
        "name" => "Needed PHP modules",
        "description" => "Check needed PHP modules",
        "check" => [
            "function" => "Phpmodules",
            "params" => [
                "required" => ["curl"],
                // "optional" => [],
            ],
        ],
    ]
);
$oMonitor->addCheck(
    [
        "name" => "Optional PHP modules",
        "description" => "Check optional PHP modules",
        "check" => [
            "function" => "Phpmodules",
            "params" => [
                // "required" => [],
                "optional" => ["xml"],
            ],
        ],
        "worstresult" => RESULT_OK
    ]
);
```
