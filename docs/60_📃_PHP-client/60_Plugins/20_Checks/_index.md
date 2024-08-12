<html>
<div class="hero">
    <h2>Plugins/checks/ folder</h2>
    In the folder plugins/checks/ are the files that contain the logic of a single check.
</div>
</html>

## Included checks ##

- [ApacheProcesses](apacheprocesses.md)
- [Cert](cert.md)
- [Diskfree](diskfree.md)
- [Exec](exec.md)
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
