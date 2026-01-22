# PdoConnect #

## Description ##

Verify a database connection with PDO connect.

PDO supports a wide range of database types - see <http://php.net/manual/en/pdo.drivers.php>. I tested it with Mysql, PostGres, Sqlite.

## Syntax ##

```php
$oMonitor->addCheck(
    [
        "name" => "Mysql Master",
        "description" => "Connect mysql db X on server Y",
        "check" => [
            "function" => "PdoConnect",
            "params" => [
                "connect"  => [pdo connect string],
                "user"     => [database user],
                "password" => [password],
            ],
        ],
    ]
);
```

## Parameters ##

| key      | type     | description
|---       |---       |---
|connect🔸 |(string)  |connect string, i.e. 'mysql:host=localhost;port=3306;dbname=mydatabase;'
|user      |(string)  |mysql username
|password  |(string)  |password
|timeout   |(integer) |optional timeout in sec; default: 5

🔸 required

Remark:  
The idea is not to enter credentials in the parameters. You should parse the config of your application and insert its variables.

## Examples ##

None yet.

### Sqlite

PDO connect examples:

```txt
sqlite:/opt/databases/mydb.sq3
sqlite::memory:
sqlite:
```

PHP snippet:

```php
$sSqlitefile=$aConfig['dataDir'].'/database/logs.db';
$oMonitor->addCheck(
    [
        "name" => "Sqlite DB for action logs",
        "description" => "Connect sqlite db ". basename($sSqlitefile),
        "parent" => "read config file",
        "check" => [
            "function" => "SqliteConnect",
            "params" => [
                "db"=>$sSqlitefile
            ],
        ],
    ]
);
```

### Mysql

PDO connect examples:

```txt
mysql:host=localhost;dbname=testdb
mysql:host=localhost;port=3307;dbname=testdb
mysql:unix_socket=/tmp/mysql.sock;dbname=testdb
```

PHP snippet:

```php
$sPdoConnectString = "mysql:host=$aDb[server];port=3306;dbname=$aDb[database];";

$oMonitor->addCheck(
    [
        "name" => "Mysql Master",
        "description" => "Connect mysql server " . $aDb['server'] . " as user " . $aDb['username'] . " to scheme " . $aDb['database'],
        "parent" => "read config file",
        "check" => [
            "function" => "PdoConnect",
            "params" => [
                "connect" => $sPdoConnectString,
                "user" => $aDb['username'],
                "password" => $aDb['password'],
            ],
        ],
    ]
);
```
