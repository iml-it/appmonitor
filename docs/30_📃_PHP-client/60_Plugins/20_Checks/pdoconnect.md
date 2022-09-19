# PdoConnect #

## Description ##

Verify a database connection with PDO connect.

PDO supports a wide range of database types - see <http://php.net/manual/en/pdo.drivers.php>.
BUT: I just started with Mysql. To implement more types go to classes/appmonitor-checks.class.php - method checkPdoConnect().

## Syntax ##

```php
$oMonitor->addCheck(
    array(
        "name" => "Mysql Master",
        "description" => "Connect mysql db X on server Y",
        "check" => array(
            "function" => "PdoConnect",
            "params" => array(
                "connect"  => [pdo connect string],
                "user"     => [database user],
                "password" => [password],
            ),
        ),
    )
);
```

## Parameters ##

| key      | type     | description |
|---       |---       |---
|connect🔸 |(string)  |connect string, i.e. 'mysql:host=localhost;port=3306;dbname=mydatabase;'
|user🔸    |(string)  |mysql username
|password🔸|(string)  |password
|timeout   |(integer) |optional timeout in sec; default: 5

Remark:  
The idea is not to enter credentials in the parameters. You should parse the config of your application and insert its variables.

## Examples ##

None yet.
