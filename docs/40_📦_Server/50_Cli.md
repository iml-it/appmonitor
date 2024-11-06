## Usage

For automation tools like Puppet, Chef, Ansible & Co it is required to set values to trigger a configuration.
The cli.php returns exitcode 0 if the action was successful; and <> 0 if an error occured.

You can see the supported parameters: with *php server/cli.php* (without parameter)

```txt
; _________________________________________________________________________________________
;
;
;    CLI API FOR APPMONITOR
;
; _________________________________________________________________________________________
;
HELP:
    ./cli.php [ACTION [parameter1 [parameter2]]]

    ACTIONs and its parameter are:

        --addurl URL
            add a client monitor url
            parameter1: url

        --deleteurl URL
            delete a client monitor url
            url must exist
            parameter1: url

        --remove VARNAME
            remove subkey from config
            parameter1: VARNAME

        --show VARNAME
            show value(s) of the config
            use no param (or ALL as varname) to show whole config
            parameter1: VARNAME (optional; default is ALL)

        --set VARNAME VALUE
            set a value of a given key. If the key does not exist it will be created.
            parameter1: VARNAME
            parameter2: VALUE

    remarks:
    - in VARNAME - use '.' as divider of subkeys
    - you can chain commands. i.e.
      --set VARNAME VALUE --show
      They will be processed sequentially.
```

## Show current configuration

### Introduction

To see all variables of the current config you can use no additional filter (or you the keyword ALL)

`php server/cli.php --show ALL`

To see a single variable (or any subkey of the hash):

`php server/cli.php --show <key>`

### Nested subkeys

To see only then subitem of a key use the `.` char as divider and chain all subkeys:

`php server/cli.php --show notifications.sleeptimes`

```txt
(...)
Array
(
    [0] => /(Sat|Sun)/
    [1] => /[2][1-3]:/
    [2] => /[0][0-4]:/
)
```

`php server/cli.php --show notifications.sleeptimes.2`

```txt
/[0][0-4]:/
```

### Chaining of commands

You can chain several commands. This is helpful for modification actions (see the sections below) to see the result directly.

Example: to show the config, then add or delete something and show the current config after the change again:

`php server/cli.php* --show [--[modification action]] --show`

### Add and remove urls of appmonitor clients

You can

`php server/cli.php --addurl [url]`

`php server/cli.php --addurl https://example.com/appmonitor/client/`

You get an OK message if it was successful - or an error message (with exitcode <>0).

Removing an url works in the same way. The url you want to delete must exist.

`php server/cli.php --deleteurl [url]`

### Add / set a variable/ key

With the parameter `--set` you can set a single value (integer, string) to a given key(structure).

To set a varioable in the first level:

php server/cli.php --set pagereload 120`

To add an array value i.e. in the notification section name the keys. If the last subkey is an array then automatically an array item will be added.

`php ./cli.php --set notifications.sleeptimes "/(Wed)/" --show notifications.sleeptimes`

... shows the result:

```txt
(...)
Array
(
    [0] => /(Sat|Sun)/
    [1] => /[2][1-3]:/
    [2] => /[0][0-4]:/
    [3] => /(Wed)/
)
```

To modify an array item you add the count.

Example to change Wednesday to Thursday:

`php ./cli.php --set notifications.sleeptimes.3 "/(Thu)/" --show notifications.sleeptimes`

```txt
(...)
Array
(
    [0] => /(Sat|Sun)/
    [1] => /[2][1-3]:/
    [2] => /[0][0-4]:/
    [3] => /(Thu)/
)
```

### Remove a variable/ key

With given a key as parameter it will be deleted.

Remark: You can delete a single value - but also a complete key structure.

`php ./cli.php --remove notifications.sleeptimes.3`
