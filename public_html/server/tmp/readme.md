# IML Appmonitor #

## Description ##

In the `./tmp/` directory is the cache for last fetched monitorig status infos
and notification logs.

Deleting subdirectories has no functional impact but removes historical data.

## Security ##

The web access to `./tmp/` directory should be denied.
This is tested with the delivered check of appmonitor server.

## Content in the directories ##

| dir                              | description
|---                               |---
|ahcache/appmonitor-server         |last fetched status of each app
|ahcache/counterids                |counter list of each app 
|ahcache/notificationhandler-app   |last notification status of each app (for respecting sleep times)
|ahcache/notificationhandler-log   |notification log
|ahcache/rrd                       |history data of each counter
|running_tinyservice_*.run         |touch file of a running server.php
