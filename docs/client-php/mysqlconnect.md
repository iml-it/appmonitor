<style>
	.required{color:#f22;}
	.optional{color:#888;}
</style>

[UP: PHP client: default checks](../client-php-checks.md)

--- 

# MysqlConnect #


## Description ##

verify a database connection with mysqli real connect function.


## Syntax ##

```php
$oMonitor->addCheck(
	array(
		"name" => "Mysql Master",
		"description" => "Connect mysql db master ".$aDb['host']." - " . $aDb['path'],
		"check" => array(
			"function" => "MysqlConnect",
			"params" => array(
			  "server"   => $aDb['host'],
			  "user"     => $aDb['user'],
			  "password" => $aDb['pass'],
			  "db"       => $aDb['path'],
			  "port"     => $aDb['port'], // optional
			),
		),
	)
);
```


## Parameters ##

Parameters:

| key      | type     | description |
|---       |---       |---
|server    |(string)  |hostname/ ip of mysql server <span class="required">(*)</span>
|user      |(string)  |mysql username <span class="required">(*)</span>
|password  |(string)  |password <span class="required">(*)</span>
|db        |(string)  |database name / scheme to connect <span class="required">(*)</span>
|port      |(integer) |database port; optional
|timeout   |(integer) |optional timeout in sec; default: 5

Remark:  
The idea is not to enter credentials in the parameters. You should parse the config of your application and insert its variables.


## Examples ##

None yet.