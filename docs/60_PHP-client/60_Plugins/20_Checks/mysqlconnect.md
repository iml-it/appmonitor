# MysqlConnect #

## Description ##

verify a database connection with mysqli real connect function.

## Syntax ##

```php
$oMonitor->addCheck(
    [
        "name" => "Mysql Master",
        "description" => "Connect mysql db X on server Y",
        "check" => [
            "function" => "MysqlConnect",
            "params" => [
                "server"   => $aDb['host'],
                "user"     => $aDb['user'],
                "password" => $aDb['pass'],
                "db"       => $aDb['path'],
                "port"     => $aDb['port'], // optional
            ],
        ],
    ]
);
```

## Parameters ##

Parameters:

| key      | type     | description
|---       |---       |---
|server🔸  |(string)  |hostname/ ip of mysql server
|user🔸    |(string)  |mysql username
|password🔸|(string)  |password
|db🔸      |(string)  |database name / scheme to connect
|port      |(integer) |database port; optional
|timeout   |(integer) |optional timeout in sec; default: 5

🔸 required

Remark:  
The idea is not to enter credentials in the parameters. You should parse the config of your application and insert its variables.

## Examples ##

In most cases you need to read/ parse a config file in a first check and then take its variables to connect to the database with the found settings.

```php
$sConfigfile = $sApproot . '/wp-config.php';
$aConfig = include($sConfigfile);
$sActive=$aConfig['default-connection'];
$aDb=$aConfig['connections'][$sActive];

$oMonitor->addCheck(
    [
        "name" => "check config file",
        "description" => "The config file must be writable",
        "check" => [
            "function" => "File",
            "params" => [
                "filename" => $sConfigfile,
                "file" => true,
                "writable" => true,
            ],
        ],
    ]
);

$oMonitor->addCheck(
    [
        "name" => "Mysql Connect",
        "description" => "Connect mysql server " . $aDb['server'] 
                         . " as user " . $aDb['username'] 
                         . " to scheme " . $aDb['database'],
        "parent" => "check config file",
        "check" => [
            "function" => "MysqlConnect",
            "params" => [
                "server"   => $aDb['server'],
                "user"     => $aDb['username'],
                "password" => $aDb['password'],
                "db"       => $aDb['database'],
                // "port"     => $aDb['port'],
            ],
        ],
    ]
);
```
