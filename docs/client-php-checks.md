<style>
	.required{color:#f22;}
	.optional{color:#888;}
</style>


# [APPMONITOR](readme.md) > [CLIENT](client.md) > [PHP-Client](client-php.md) > Checks

Free software and Open Source from University of Bern :: IML - Institute of Medical Education

https://github.com/iml-it/appmonitor

- - -

# Check functions in detail #


## Introduction ##

The checks are defined in appmonitor-checks.class.php as private functions - but can 
be check\*.php files in the plugins directory too.


## General include of a check ##


You can add several checks with the class that
was initialized on top of the file.

The class has a render method that generates the json for you.

In the area between $oMonitor = new appmonitor(); and render(); you can add
as many checks you want.
The syntax is

```php
$oMonitor->addCheck(
  array(
    "name" => "[short name of the check]",
    "description" => "[an a bit longer description]",
    "check" => [Array for the check],
    "worstresult" => RESULT_WARNING
  )
);
```

| key        | type     | description |
|---         |---       |---
|name        |(string)  | "id" if the check <span class="required">(*)</span>|
|description |(string)  | a short description <span class="required">(*)</span>|
|check       |(array)   | check to perform <span class="required">(*)</span>|
|worstresult |(integer) | optional: limit maximum error level if the check fails<br>if the check should fail then its result is an error - but this check is not highly relevant for a running application then you can override the influence to the total result set a maximum level i.e. RESULT_WARNING.|


The check contains 2 keys:

	"function" => "[Name of a defined check]",
	"params" => [key->value array; count and keys depend on the function]


## Included functions ##

- [ApacheProcesses](client-php/apacheprocesses.md)
- [Cert](client-php/cert.md)
- [Diskfree](client-php/diskfree.md)
- [File](client-php/file.md)
- [HttpContent](client-php/httpcontent.md)
- [Loadmeter](client-php/loadmeter.md)
- [MysqlConnect](client-php/mysqlconnect.md)
- [PDOConnect](client-php/pdoconnect.md)
- [PortTcp](client-php/porttcp.md)
- [Simple](client-php/simple.md)
- [SqliteConnect](client-php/sqliteconnect.md)

To see all available checks:

```php
print_r($oMonitor->listChecks());
```
