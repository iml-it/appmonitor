# Exec #

## Description ##

Execute a shell command.
Use the parameter "command" to set the command to execute.

The default behaviour is:

* the check returns OK on exitcode zero and critical on a
non zero exitcode.
* You get the output of the command from STDOUT.

To fetch STDERR too add a `2>&1` behind your command.

To handle custom exitcodes you can use the exit* parameters
for finetuning.

## Syntax ##

```php
$oMonitor->addCheck(
    [
        "name" => "exec check",
        "description" => "exec check",
        "check" => [
            "function" => "Exec",
            "params" => [
                "command"      => [string: Command],
                "output"       => [bool: show output?]
                // optional: handle custom exitcodes
                "exitOK"       => [array],
                "exitWarn"     => [array],
                "exitCritical" => [array],
            ],
        ],
    ]
);
```

## Parameters ##

| key         | type     | description
|---          |---       |---
|command🔸    |(string)  |filename or directory to check
|exitOK       |(array)   |array of integers for ok exitcodes
|exitWarn     |(array)   |array of integers for exitcodes with warning
|exitCritical |(array)   |array of integers for exitcodes that result in an error (otherwise the plugin can return OK or CRITITCAL only)
|output       |(bool)    |flag: show output of the executed command; default: true

🔸 required

## Examples ##

### Demo with list command ###

Just as a demo: execute a ls on a non existing directory.
To see the error of ls on STDERR we add `2>&1`.

```php
$oMonitor->addCheck(
    [
        "name" => "exec test",
        "description" => "Test ls command",
        "check" => [
            "function" => "Exec",
            "params" => [
                "command" => 'ls -l /a/non/existing/dir 2>&1',
                "output" => true,
            ],
        ],
    ]
);
```

### Custom return codes ###

To allow another non-zero existode as OK you can use the array exitOK.
Here is a returncode example with rsync (which is not really a good test command in a monitoring).
Rsync returns 24 for "Partial transfer due to vanished source files". We want to allow 0 and 24 as OK values. (Zero is always OK - so we need to add 24 only.)

```php
$oMonitor->addCheck(
    [
        "name" => "exec test 2",
        "description" => "Rsync data",
        "check" => [
            "function" => "Exec",
            "params" => [
                "command" => 'rsync -rvt /var/data/testfile* /backup',
                "output" => false,
                "exitOK" => [24]
            ],
        ],
    ]
);
```
