# Check functions in detail #

## Introduction ##

The available different checks are the files in public_html/client/plugins/checks/.

## General include of a check ##

Have a look to Let's have a look to public_html/client/index.sample.php.

You can add all checks after initializing the appmonitor-client class that
is initialized on top of the file.

The class has a render() method that generates the json for you. It must be at the end.

In the area between `$oMonitor = new appmonitor();` and `render();` you can place
as many checks you want.

The syntax is

```php
// init (just once needed)
require_once('classes/appmonitor-client.class.php');
$oMonitor = new appmonitor();

// ...

// now you can use addCheck() multiple times.
$oMonitor->addCheck(
  array(
    "name" => "[short name of the check]",
    "description" => "[an a bit longer description]",
    "group" => "[optional: name of a group]",
    "parent" => "[optional: reference a name of another check]",
    "check" => [Array for the check],
    "worstresult" => RESULT_WARNING
  )
);
```

| key        | type     | description |
|---         |---       |---
|name        |(string)  | "id" if the check <span class="required">(*)</span>|
|description |(string)  | a short description <span class="required">(*)</span>|
|group       |(string)  | optional override name of a group |
|parent      |(string)  | optional set a "name" of another check to create a deoendency chain |
|check       |(array)   | check to perform <span class="required">(*)</span>|
|worstresult |(integer) | optional: limit maximum error level if the check fails<br>if the check should fail then its result is an error - but this check is not highly relevant for a running application then you can override the influence to the total result set a maximum level i.e. RESULT_WARNING.|


The key `check` contains 2 subkeys:

	"function" => "[Name of a defined check]",
	"params" => [key->value array; count and keys depend on the function]


### Included checks ###

- [ApacheProcesses](50_Plugins/20_Checks/apacheprocesses.md)
- [Cert](50_Plugins/20_Checks/cert.md)
- [Diskfree](50_Plugins/20_Checks/diskfree.md)
- [File](50_Plugins/20_Checks/file.md)
- [HttpContent](50_Plugins/20_Checks/httpcontent.md)
- [Loadmeter](50_Plugins/20_Checks/loadmeter.md)
- [MysqlConnect](50_Plugins/20_Checks/mysqlconnect.md)
- [PDOConnect](50_Plugins/20_Checks/pdoconnect.md)
- [PortTcp](50_Plugins/20_Checks/porttcp.md)
- [Simple](50_Plugins/20_Checks/simple.md)
- [SqliteConnect](50_Plugins/20_Checks/sqliteconnect.md)

To see all available checks:

```php
print_r($oMonitor->listChecks());
```

### Groups ###

This functionality has impact in the rendered view in the web ui only.

Without any group all check results are connected directly to the application node.

```text
+--------+          +---------+    
| My App +--------->| Check 1 |
+--------+ \        +---------+
           |\       +---------+
           | `----->| Check 2 |
           \        +---------+
            \       +---------+
             `----->| Check 3 |
                    +---------+  
```

By adding a group "in front" of a check a node for the group will be inserted. All checks of the same type will are connected with a group of checks.

Example:

The checks 1 + 2 get the group "file". Check 3 gets a group "database". The graphical view will change like this:

```text
+--------+          +--------+          +---------+    
| My App +--------->| File   +--------->| Check 1 |
+--------+ \        +--------+ \        +---------+
            |                   \       +---------+
            |                    `----->| Check 2 |
            |                           +---------+
             \      +-----------+       +---------+
              `---->| Database  +------>| Check 3 |
                    +-----------+       +---------+
```

A default group is set in all by default shipped checks.

You can override it by setting another group.


| Group      | Description |
|---         |--- 
| cloud      | |
| database   | |
| deny       | |
| disk       | |
| file       | |
| folder     | |
| monitor    | |
| network    | |
| security   | |
| service    | |

## Chaining with parent ##

With a chaining value you can reference another check by giving its name value.

As an example: One of the first checks can be the check to read a config. In it are your database credentials. In the check the database access you set a reference to the config check as parent.

In the monitor web ui you get a rendered tree.
