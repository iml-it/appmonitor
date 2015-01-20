
ADDING TO NAGIOS


--- create nrpe config file:

$ cat /etc/nagios/nrpe.d/check_appmonitor.cfg
command[check_appmonitor]=/usr/lib/nagios/plugins/check_appmonitor /var/www/appmonitor/server/nagios/server-nagiosplugin.php


--- create nrpe execution file

$ cat /usr/lib/nagios/plugins/check_appmonitor
#!/bin/sh
#
# execute the php script
# /bin/php /var/www/appmonitor/server/nagios/server-nagiosplugin.php
#
if [ -z $1 ]; then
    echo "ERROR: I need an parameter to the server-nagiosplugin.php"
    exit 2
else

    ls -l "$1" >/dev/null 2>&1
    if [ $? -ne 0 ]; then
        echo "ERROR: file $1 does not exist"
        exit 2
    fi

    fgrep "getMonitoringData" "$1" >/dev/null
    if [ $? -ne 0 ]; then
        echo "ERROR: file $1 does not seem to be the needed appmonitor nagios check"
        exit 2
    fi

    # run it...
    /usr/bin/php "$1"

fi


--- create a service on monitoring host

/etc/nagios3/site/hosts/servers/monitor.cfg
(...)
define service {
    service_description             appmonitor [nrpe]
    host_name                       monitor.[your-domain]
    check_command                   check_nrpe!check_appmonitor
    use                             generic-service,graphed-service
}
(...)
