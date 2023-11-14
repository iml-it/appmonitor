The server instance can be used by just using the web interface. For your first tests you don't need a running service. But it is highly recommended for a production use.

The service that is a permanently running loop that fetches updated information of the outdated client data and sends notification data around the clock (respecting the sleep times).

# Run as systemd service #

This method works on newer Linux OS with systemd, i.e. CentOS 7.

Below */etc/systemd/*  create a service file */etc/systemd/system/appmonitor.service*

[webroot] is something like /var/www/localhost/public_html/

```ini
[Unit]
Description=IML Appmonitor service daemon
Wants=multi-user.target

[Service]
ExecStart=/usr/bin/php [webroot]/appmonitor/server/service.php
Restart=on-failure
RestartSec=30s
SyslogIdentifier=appmonitor
User=apache
Group=root
Type=simple

[Install]
WantedBy=multi-user.target
```

Check it with

`systemd-analyze verify /etc/systemd/system/appmonitor.service`

Then work these commands:

```
systemctl start appmonitor
systemctl status appmonitor
systemctl stop appmonitor
```

# Manual start #

This method does runs on all OS (MS Windows, Mac, Linux).

remark for *nix systems:

Manual start form command line as apache user (www-data or apache) is possible if the user apache has a login shell. Do not start as another user to prevent permission problems with created files (in ./server/tmp/).

Interactive mode (*nix and MS Windows or docker container):

`php [webroot]/appmonitor/server/service.php`

To let it run permanently in the background and after logging out use the nohup command in front and an ampersend at the end (*nix only):

`nohup php [webroot]/appmonitor/server/service.php &`
