# Plugins/checks folder #

In the folder plugins/checks/ are the files that contain the logic of a single check.

## Included checks ##

- [ApacheProcesses](apacheprocesses.md)
- [Cert](cert.md)
- [Diskfree](diskfree.md)
- [File](file.md)
- [HttpContent](httpcontent.md)
- [Loadmeter](loadmeter.md)
- [MysqlConnect](mysqlconnect.md)
- [PDOConnect](pdoconnect.md)
- [Phpmodules](phpmodules.md)
- [Ping](ping.md)
- [PortTcp](porttcp.md)
- [Simple](simple.md)
- [SqliteConnect](sqliteconnect.md)

To see all available checks:

```php
print_r($oMonitor->listChecks());
```
