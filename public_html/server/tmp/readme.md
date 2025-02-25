# IML Appmonitor #

## Description ##

In the `./tmp/` directory is the cache for last fetched monitorig status infos
and notification logs.

Deleting subdirectories has no functional impact but removes historical data.

## Security ##

The web access to `./tmp/` directory should be denied.
This is tested with the delivered check of appmonitor server.

## Content in the directories ##

⚠️ Hint: Some data was moved to sqlite database in v0.150.

| dir                              | description
|---                               |---
|ahcache/appmonitor-server         |last fetched status of each app
|running_tinyservice_*.run         |touch file of a running server.php
