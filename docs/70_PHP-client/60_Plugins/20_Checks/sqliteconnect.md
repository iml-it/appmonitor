# SqliteConnect #

## Description ##

Make a database connection to a sqlite database.
The function fails if the filename does not exist or the PDO cannot open it

```php
$o = new PDO("sqlite:".$aParams["db"],(...));
```

## Syntax ##

```php
$oMonitor->addCheck(
    [
        "name" => "Slite test",
        "description" => "Connect sqlite database file",
        "check" => [
            "function" => "SqliteConnect",
            "params" => [
                "db"       => [database file],
                "user"     => [username],
                "password" => [password],
                "timeout"  => [time],
            ],
        ],
    ]
);
```

## Parameters ##

| key      | type     | description
|---       |---       |---
|db🔸      |(string)  |full path of the sqlite database file
|user      |(string)  |optional: username; default: empty
|password  |(string)  |optional: password; default: empty
|timeout   |(integer) |optional timeout in sec; default: 5

🔸 required

## Examples ##

```php
$sSqlitefile=$aConfig['dataDir'].'/database/logs.db';
$oMonitor->addCheck(
    [
        "name" => "Sqlite DB for action logs",
        "description" => "Connect sqlite db ". basename($sSqlitefile),
        "check" => [
            "function" => "SqliteConnect",
            "params" => [
                "db"=>$sSqlitefile
            ],
        ],
    ]
);
```
