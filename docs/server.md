<style>
	.mark{background:#fc0; color:#f22;}
	.optional{color:#888;}
</style>

# [APPMONITOR](readme.md) > SERVER #

OPEN SOURCE from University of Bern :: IML - Institute of Medical Education

https://github.com/iml-it/appmonitor


- - -


# Requirements #

- PHP7
- php-curl

The server uses 

- [ahCache] (https://www.axel-hahn.de/docs/ahcache/index.htm) class to store 
   serialized data as file (included)
- [cdnorlocal] (https://www.axel-hahn.de/docs/cdnorlocal/index.htm)

... and loads from CDNJS (but could be stored locally too):
- [AdminLTE] (https://adminlte.io/)
- [chartJs] (https://www.chartjs.org/)
- [jQuery] (https://jquery.com/)
- [Datatables plugin] (https://datatables.net/)
- [Font Awesome] (https://fontawesome.com/)


# Installation #

## Install the server ##
Below a document root of a website create a directory [webroot]/appmonitor/
- copy all files of the archive into [webroot]/appmonitor/
- verify the installation with your browser 
  http://localhost/appmonitor/server/
  You will see a welcome message.
- Go to the setup.
- Add the url http://localhost/appmonitor/client/check-appmonitor-server.php to integrate a first check.

## Other clients ##
The next step is creating client checks thats urls you can add in the setup.
See [Client](client.md) for general introduction. For PHP applications see [PHP-Client](client-php.md) + [PHP-plugins](client-php-plugins.md).


## Production use ##

If you are happy with the first tests then read the sections below.


# Configuration #

The config is in json syntax. The files are located in 
_[webroot-appmonitor]/server/config/appmonitor-server-config.json_

| File                                    | Description                                |
|---                                      |---                                         |
| appmonitor-server-config.json           | Custom settings                            |
| appmonitor-server-config-defaults.json  | DO NOT OVERWRITE - Defaultsetup            |


On the first start of the web gui the defaults will be used. 
By entering a first client appmonitor url the user config will be written.

If you would like to setup it manually without webgui then copy 
appmonitor-server-config-defaults.json to appmonitor-server-config.json
(same name - without "-defaults")


``` json
{
    "skin": "skin-purple",
    "layout": "sidebar-mini",
    "lang": "en-en",
    "debug": false,
    "servicecache": false,
    "curl":{
        "timeout": 15
    },
    "pagereload": 60,
    "serverurl": "http:\/\/monitor.example.com\/appmonitor\/server\/",
    "notifications": {
        "sleeptimes": [
            "/(Sat|Sun)/",
            "/[2][1-3]:/",
            "/[0][0-4]:/"
        ],
        "from": {
            "email": [
                "noreply@example.com"
            ],
            "slack": [
                "noreply@example.com"
            ],
        },
        "email": [
            "sysadmin@example.com"
        ],
        "slack": {
            "#sysadmin-channel": "https:\/\/hooks.slack.com\/services\/AAAAA\/BBBBB\/CCCCCC"
            }
        },
        "messages": {
            "[text-id]": "[Custom message text using placeholders.]"
        }
    },
    "view": {
        "overview":{
            "webapps": true,
            "hosts": true,
            "checks": true,
            "notification": true
        },
        "appdetails":{
            "appstatus": true,
            "httpcode": true,
            "age": true,
            "checks": true,
            "times": false,
            "receiver": false,
            "notification": false
        }
    },	
    "urls":[
        "http://localhost/appmonitor/client/",
        "http://server1/appmonitor/client/",
        "http://server2/appmonitor/client/"
    ]
}
```

The values are:

| Key             | Description                                                           |
|---              |---                                                                    |
| _curl_          | \{array\} curl settings for fetching client results                   |
| _debug_         | \{bool\} show debug tab with internal values                          |
| _lang_          | \{string\} language (en-en\|de-de)                                    |
| _layout_        | \{string\} name of adminLte layout (one of fixed\|layout-boxed\|layout-top-nav\|sidebar-mini(=default)\|sidebar-collapse) |
| _notifications_ | \{array\} notification setup                                          |
| _pagereload_    | \{integer\} auto refresh of server webgui in sec (0=off; default: 60) |
| _serverurl_     | \{string\} url of installation; it is used for notification only      |
| _servicecache_  | \{bool\} flag for caching; if using service then web gui uses cached data only |
| _skin_          | \{string\} name of adminLte skin (one of skin-blue\|skin-black\|skin-purple(=default)\|skin-yellow\|skin-red\|skin-green ... and *-light) |
| _theme_         | \{string\} name of css to load (aka "skin") ... do not use anymore    |
| _view_          | \{array\} show/ hide elements on ouput pages                          |
| _urls_          | \{array\} list of urls                                                |

Remarks:

- "curl": curl settings \
  Here can be the subkeys 
  - "timeout": integer value in seconds; default is 15. If you use a service then you can tweak: set servicecache to true and a higher timeout in curl -> timeout
- _urls_ is a flat list of urls
- "notifications": notification targets (optional) \
  Here can be the subkeys 
  - "email": flat list of emails
  - "slack": key-value list with a readable label for the target channel and the Slack webhook url
  - "from":  sender information ... in the subkeys 
    - "email": email address for notifications (is reply-to address too)
    - "slack": sender name ("Appmonitor" as default)
  - "sleeptimes": flat array of time definitions when no notification will be sent. Each entry is a regex. If any matches the current system time (PHP function date("Y-m-d D H:i") - it returns the date in YYYY-MM-DD, the short weekday plus hour, ":" and minutes: "2018-07-04 Mon 09:23"). Pay attention to the dividers: "-" is used for dates and ":" to divide hour and minute. The example will disable Notifications:
    - "/(Sat|Sun)/" --> Saturday and Sunday
	- "/[2][1-3]<span class="mark">:</span>/" --> digit 2 + 1..3 before ":" --> daily from 21:00-23:59
	- "/[0][0-4]<span class="mark">:</span>/" --> digit 0 + 0..4 before ":" --> daily from 00:00-04:59
	- other examples
	- "/2018<span class="mark">-</span>08<span class="mark">-</span>01/" --> disable notification on complete 1st of August 2018 (Swiss holiday)
	- "/[0-9]{4}<span class="mark">-</span>12<span class="mark">-</span>/" --> 4 digits is a year then "minus" + month 12 --> disables notification in December of each year
  - "messages": override default notification messages (see next chapter)


  
# Notification #

## Message texts ##

The sent messages are language dependent texts and the appmonitor is
shipped with a default. In the language file they are defined in the subkey 
"notifications".

You can override the defaults with defining the keys in the server config
in the section notifications -> messages.

These are the message keys:
- changetype-[N].logmessage
- changetype-[N].email.message
- changetype-[N].email.subject

[N] is an integer value between 0..3 (for result type)

These texts can contain placeholders.

| Placeholder          | Description                                                |
|---                   |---                                                         |
| _\_\_APPID___        | id of application                                          |
| _\_\_CHANGE___       | one of new\|no change\|change\|deleted (1)                 |
| _\_\_DELTA-TIME___   | delta since last state change i.e. NN min (HH h) (2)       |
| _\_\_ERROR___        | Error message of a failed response                         |
| _\_\_HEADER___       | Http response header (maybe for an email message)          |
| _\_\_HOST___         | hostname (from client meta -> host)                        |
| _\_\_LAST-RESULT___  | result of last check; see RESULT (2)                       |
| _\_\_LAST-TIME___    | time of last check; see TIME (2)                           |
| _\_\_MONITORURL___   | url to monitoring page (3)                                 |
| _\_\_RESULT___       | one of OK\|Unknown\|Warning\|Error (1)                     |
| _\_\_TIME___         | current time YYYY-MM-DD hh:mm:ss                           | 
| _\_\_URL___          | url of web application check                               |
| _\_\_WEBSITE___      | name of the website/ service (from client meta -> website) |

Remarks:
- (1) this depends on the set appmonitor server language. The values are these of the English version.
- (2) It requires that a saved state with another status for this url. Value is "-" if there is no state change logged yet
- (3) requires a value for "serverurl" in the config

To preview the texts you can 
- set "debug" to true in you config
- open server monitoring in the browser - go into a detail page of any web app
- on the bottom you see all placeholders, current replacements and the preview messages for all change types


# Security #

## User access ## 

Important remark:
The appmonitor has no user login. Protect it by configuration of your webserver, i.e. use ip restriction and/ or basic authentication.

## Sensitive data ## 

These files _may_ contain sensitive data and could be interesting for hackers. Deny the web access for
- [approot]/server/config/
- [approot]/server/tmp/

In both directories is a .htaccess - if you set _AllowOverride Limit_ these .htaccess will be used. Otherwise create a directory or Location section to deny the web access.


# Service #

The server instance can be used by just using the web interface. For your first tests you don't need a running service. But it is highly recommended for a production use.

The service that is a permanently running loop that fetches updated information of the outdated client data and sends notification data around the clock (respecting the sleep times).


## Run as systemd service ##

This method works on newer Linux OS with systemd, i.e. CentOS 7.

Below */etc/systemd/*  create a service file */etc/systemd/system/appmonitor.service*

[webroot] is something like /var/www/localhost/public_html/

```
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


## Manual start ##

This method does runs on all OS (MS Windows, Mac, Linux).

remark for *nix systems:

Manual start form command line as apache user (www-data or apache) is possible if the user apache has a login shell. Do not start as another user to prevent permission problems with created files (in ./server/tmp/).

Interactive mode (*nix and MS Windows):

`php [webroot]/appmonitor/server/service.php`


To let it run permanently in the background and after logging out use the nohup command in front and an ampersend at the end (*nix only):

`nohup php [webroot]/appmonitor/server/service.php &`


# CLI #

For automation tools like Puppet, Chef, Ansible & Co it is required to set values to trigger a configuration.
The cli.php returns exitcode 0 if the action was successful; and <> 0 if an error occured.


You can see the supported parameters: with *php server/cli.php* (without parameter)

```
; _________________________________________________________________________________________
;
;
;    CLI API FOR APPMONITOR
;
; _________________________________________________________________________________________
;
HELP:
    ./cli.php [ACTION [parameter1 [parameter2]]]

    ACTIONs and its parameter are:

        --addurl URL
            add a client monitor url
            parameter1: url

        --deleteurl URL
            delete a client monitor url
            url must exist
            parameter1: url

        --remove VARNAME
            remove subkey from config
            parameter1: VARNAME

        --show VARNAME
            show value(s) of the config
            use no param (or ALL as varname) to show whole config
            parameter1: VARNAME (optional; default is ALL)

        --set VARNAME VALUE
            set a value of a given key. If the key does not exist it will be created.
            parameter1: VARNAME
            parameter2: VALUE

    remarks:
    - in VARNAME - use '.' as divider of subkeys
    - you can chain commands. i.e.
      --set VARNAME VALUE --show
      They will be processed sequentially.
```

## Show current configuration ##

### Introduction ###

To see all variables of the current config you can use no additional filter (or you the keyword ALL)

*php server/cli.php* **--show** *ALL*

To see a single variable (or any subkey of the hash):

*php server/cli.php* **--show** *urls*

```
(...)
Array
(
    [1] => http://server-01/appmonitor/client/
    [2] => http://server-02/appmonitor/client/
    [3] => http://server-03/appmonitor/client/
)
``` 

### Nested subkeys ###

To see only then subitem of a key use the "<span class="mark">.</span>" char as divider and chain all subkeys:

*$ php server/cli.php* **--show** *notifications<span class="mark">.</span>sleeptimes*
```
(...)
Array
(
    [0] => /(Sat|Sun)/
    [1] => /[2][1-3]:/
    [2] => /[0][0-4]:/
)
``` 


*$ php server/cli.php* **--show** *notifications<span class="mark">.</span>sleeptimes<span class="mark">.</span>2*
```
/[0][0-4]:/
``` 


### Chaining of commands ###

You can chain several commands. This is helpful for modification actions (see the sections below) to see the result directly.

Example: to show the config, then add or delete something and show the current config after the change again:

*$ php server/cli.php* **--show** [--[modification action]] **--show**


### Add and remove urls of appmonitor clients ###

You can 

*php server/cli.php* **--addurl** *[url]*

*php server/cli.php* **--addurl** *https://example.com/appmonitor/client/*

You get an OK message if it was successful - or an error message (with exitcode <>0).


Removing an url works in the same way. The url you want to delete must exist.

*php server/cli.php* **--deleteurl** *[url]*



### Add / set a variable/ key ###

With the parameter --set you can set a single value (integer, string) to a given key(structure).

To set a varioable in the first level:

*php server/cli.php* **--set** pagereload 120

To add an array value i.e. in the notification section name the keys. If the last subkey is an array then automatically an array item will be added.

*php ./cli.php* **--set** notifications.sleeptimes "/(Wed)/" --show notifications.sleeptimes

... shows the result:
```
(...)
Array
(
    [0] => /(Sat|Sun)/
    [1] => /[2][1-3]:/
    [2] => /[0][0-4]:/
    [3] => /(Wed)/
)
```

To modify an array item you add the count.

Example to change Wednesday to Thursday:

*php ./cli.php* **--set** notifications.sleeptimes.3 "/(Thu)/" --show notifications.sleeptimes

```
(...)
Array
(
    [0] => /(Sat|Sun)/
    [1] => /[2][1-3]:/
    [2] => /[0][0-4]:/
    [3] => /(Thu)/
)
```

### Remove a variable/ key ###

With given a key as parameter it will be deleted. 

Remark: You can delete a single value - but also a complete key structure.

*php ./cli.php* **--remove** notifications.sleeptimes.3
