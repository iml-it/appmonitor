
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
