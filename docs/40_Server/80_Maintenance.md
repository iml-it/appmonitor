## Backup

The Appmonitor writes here:

* Config file: *public_html/server/config/appmonitor-server-config.json*
* Data: 
  * *public_html/server/**data**/* contains sqlite data. Backup the file or create a dump using sqlite3 binary
  * *public_html/server/**tmp**/*

The recommendation is to update the whole application dir. The size won't exceed a few megabyte.

Example snippet with gzip compression

```shell
backupdir=/var/backup
sqlite3 public_html/server/data/appmonitor.sqlite3 .dump >${backupdir}/appmonitor.sqlite3.sql \
    && gzip ${backupdir}/appmonitor.sqlite3.sql
    && echo OK
```

## Restore

Restore the files of the application dir.
If backed up the sqlite file to a sql file then you can restore it with the sqlite3 binary:

```shell
backupdir=/var/backup
${backupdir}/appmonitor.sqlite3.sql.gz | sqlite3 public_html/server/data/appmonitor.sqlite3 && echo OK
```

## Update software

### On installation with git

Using git is the most simple way to update the software.

```txt
cd [appdir]
git pull
```

### On installation with zip

Download the zip file from Github: <https://github.com/iml-it/appmonitor>.

Extract all files and put all files below the master subdir into your current installation dir - overwrite existing files.
