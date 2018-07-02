
# [APPMONITOR](../readme.md) > SERVER #

OPEN SOURCE from University Berne :: IML - Institute of Medical Education

https://github.com/iml-it/appmonitor


- - -


# Requirements #

- PHP7 (up to PHP 7.2; runs on php5 but is not suppoorted)
- php-curl

The server uses 

- [ahCache] (https://www.axel-hahn.de/docs/ahcache/index.htm) class to store 
   serialized data as file (included)
- [cdnorlocal] (https://www.axel-hahn.de/docs/cdnorlocal/index.htm)

... and loads from CDNJS (but could be stored locally too):
- [jquery] (http://jquery.com/)
- [datatables plugin] (http://datatables.net/)
- [font-awesome] (http://fortawesome.github.io/Font-Awesome/)


# Features #

- very small installation (100 kB zip file)
- fast web interface using a single html page
- notifications can be sent as email and as slack message
- sleep time to be quiet

# Installation #

Below a document root of a website create a directory [webroot]/appmonitor/
- copy all files of the archive into [webroot]/appmonitor/
- verify the installation with your browser 
  http://localhost/appmonitor/server/server.php
  You should see a welcome message.
- Go to the setup.
- Add the url http://localhost/appmonitor/client/check-appmonitor-server.php to integrate a first check.



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
    "theme": "default",
    "lang": "en-en",
    "debug": false,
    "pagereload": 60,
    "serverurl": "http:\/\/monitor.example.com\/appmonitor\/server\/server.php",
    "notifications": {
        "sleeptimes": [
            "/(Sat|Sun)/",
            "/[2][1-3]:/",
            "/[0][0-4]:/"
        ],
        "from": 
			"email": [
				"noreply@example.com"
			],
			"slack": [
				"noreply@example.com"
			],
        "email": [
            "sysadmin@example.com"
        ],
        "slack": {
            "#sysadmin-channel": "https:\/\/hooks.slack.com\/services\/AAAAA\/BBBBB\/CCCCCC"
            }
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
| _theme_         | \{string\} name of css to load (aka "skin")                           |
| _debug_         | \{bool\} show debug tab with internal values                          |
| _lang_          | \{string\} language                                                   |
| _pagereload_    | \{integer\} auto refresh of server webgui in sec (0=off; default: 60) |
| _notifications_ | \{array\} notification setup                                          |
| _serverurl_     | \{string\} url of installation; it is used for notification only      |
| _urls_          | \{array\} list of urls                                                |

Remarks:

- _urls_ is a flat list of urls
- "notifications": notification targets (optional) \
  Here can be the subkeys 
  - "email": flat list of emails
  - "slack": key-value list with a readable label for the target channel and the Slack webhook url
  - "from":  sender information ... in the subkeys 
    - "email": email address for notifications (is reply-to address too)
    - "slack": sender name ("Appmonitor" as default)
  - "sleeptimes": flat array of time definitions when no notification will be sent. Each entry is a regex. If any matches the current system time (PHP function date("D H:i") - it returns short weekdays plus hour, ":" and minutes: "Mon 09:23"). The example will disable Notifications:
    - "/(Sat|Sun)/" --> Saturday and Sunday
	- "/[2][1-3]:/" --> daily from 21:00-23:59
	- "/[0][0-4]:/" --> daily from 00:00-04:59

  
# Notification #

## Message texts ##

The sent messages are language dependent texts.
In the language file they are defined in the subkey "notifications".
- changetype-[N].logmessage
- changetype-[N].email.message
- email.subject

[N] is an integer value between 0..3 (for change type)

These texts can contain placeholders.

| Placeholder          | Description                                          |
|---                   |---                                                   |
| _\_\_APPID___        | id of application                                    |
| _\_\_CHANGE___       | one of new\|no change\|change\|deleted (1)           |
| _\_\_DELTA-TIME___   | delta since last state change i.e. NN min (HH h) (2) |
| _\_\_HEADER___       | Http response header (maybe for an email message)    |
| _\_\_LAST-RESULT___  | result of last check; see RESULT (2)                 |
| _\_\_LAST-TIME___    | time of last check; see TIME (2)                     |
| _\_\_RESULT___       | one of OK\|Unknown\|Warning\|Error (1)               |
| _\_\_TIME___         | current time YYYY-MM-DD hh:mm:ss                     | 
| _\_\_URL___          | url of web application check                         |
| _\_\_MONITORURL___   | url to monitoring page (3)                           | 

Remarks:
- (1) this depends on the set appmonitor server language. The values are these of the English version.
- (2) It requires that a saved state with another status for this url. Value is "-" if there is no state change logged yet
- (3) requires a value for "serverurl" in the config

To preview the texts you can 
- set "debug" to true in you config
- open server monitoring in the browser - go into a detail page of any web app
- on the bottom you see all placeholders, current replacements and the preview messages for all change types


# Security #

Important remark:
The appmonitor has no user login. Protect it by configuration of your webserver, i.e. use ip restriction and/ or basic authentication.