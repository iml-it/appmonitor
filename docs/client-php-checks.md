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

To see all available checks:

```php
print_r($oMonitor->listChecks());
```

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



