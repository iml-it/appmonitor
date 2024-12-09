---
title: Server configuration
keywords: "files, settings, configuration"
---

## Configuration files

The config is in json syntax. The files are located in
_[webroot-appmonitor]/server/config/appmonitor-server-config.json_

File                                    | Description
---                                     |---
appmonitor-server-config.json           | Custom settings
appmonitor-server-config-defaults.json  | DO NOT OVERWRITE - Defaultsetup
appmonitor-server-urls.json             | Urls to monitor (will be created)

## Configuration

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
    "serverurl": "https:\/\/monitorserver\/appmonitor\/server\/",
    "pagereload": 60,
    "servicecache": false,
    "curl":{
        "timeout": 15
    },
    "notifications":{
        "from": {
            "email":"sysadmin@example.com",
            "slack":"Appmonitor"
        },
        "email":[],
        "slack":[]
    },
    "api":{
        "sourceips":[
            "^127\\.0\\.0\\.1$"
        ],
        "pretty": false
    },
    "users": {
        "*": {
            "password": false,
            "username": "anonymous",
            "comment": "anonymous access",
            "roles": [ "api", "ui", "ui-config", "ui-debug" ]
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
            "times": true,
            "tags": true,
            "receiver": true,
            "notification": true
        }
    }
}
```

The values are:

Key             | Description
---             |---
_api_           | \{array\} access of api
_curl_          | \{array\} curl settings for fetching client results
_debug_         | \{bool\} show debug tab with internal values
_lang_          | \{string\} language (en-en\|de-de)
_layout_        | \{string\} name of adminLte layout (one of fixed\|layout-boxed\|layout-top-nav\|sidebar-mini(=default)\|sidebar-collapse)
_notifications_ | \{array\} notification setup
_pagereload_    | \{integer\} auto refresh of server webgui in sec (0=off; default: 60)
_serverurl_     | \{string\} url of installation; it is used for notification only
_servicecache_  | \{bool\} flag for caching; if using service then web gui uses cached data only
_skin_          | \{string\} name of adminLte skin (one of skin-blue\|skin-black\|skin-purple(=default)\|skin-yellow\|skin-red\|skin-green ... and *-light)
_theme_         | \{string\} name of css to load (aka "skin") ... do not use anymore
_users_         | \{array\} define users and roles
_view_          | \{array\} show/ hide elements on ouput pages

The values with arrays are described below.

### api

Configure api access.

Here can be the subkeys

- `sourceips` flat list of regex to describe allowed ip addresses. We recommend to limit ip address on webserver config level for better performance.
- `pretty` boolean; enable pretty print of json response

Remark:  to configure users with api access go to section users having a role item "api".

Example:

```json
    ...
    "api":{
        "sourceips":[
            "^172\\.22\\.0\\.1$",
            "^192\\.168\\.10\\.20$",
        ],
        "pretty": true
    },
    ...
```

### curl

Curl settings.

Here can be the subkeys

- `timeout`: integer value in seconds; default is 15. If you use a service then you can tweak: set servicecache to true and a higher timeout in curl -> timeout

### notifications

notification targets (optional)

Here can be the subkeys

- `email`: flat list of email addresses that get notifications for \*\*all\*\* added applications. Maybe you want to add devops and sysadmins here.
- `slack`: key-value list with a readable label for the target channel and the Slack webhook url.
- `from`:  sender information which user is delivering notifications ... in the subkeys
  - `email`: email address for notifications (is reply-to address too)
  - `slack`: sender name ("Appmonitor" is default)
- `sleeptimes`: flat array of time definitions when no notification will be sent. Each entry is a regex. If any matches the current system time (PHP function date("Y-m-d D H:i") - it returns the date in YYYY-MM-DD, the short weekday plus hour, ":" and minutes: "2018-07-04 Mon 09:23"). Pay attention to the dividers: "-" is used for dates and ":" to divide hour and minute. The example will disable Notifications:
  - `/(Sat|Sun)/` --> Saturday and Sunday
  - `/[2][1-3]:/` --> digit 2 + 1..3 before ":" --> daily from 21:00-23:59
  - `/[0][0-4]:/` --> digit 0 + 0..4 before ":" --> daily from 00:00-04:59
  - other examples
    - `/2018-08-01/` --> disable notification on complete 1st of August 2018 (Swiss holiday)
    - `/[0-9]{4}-12-/` --> 4 digits is a year then "minus" + month 12 --> disables notification in December of each year

```json
    ...
    "notifications": {
        "from": {
            "email": "appmonitor@example.com",
            "slack": "Appmonitor"
        },
        "email": [
            "devops@example.com",
            "sysadmin@example.com",
        ],
        "slack": [],
        "sleeptimes": [
            "\/[2][1-3]:\/",
            "\/[0][0-6]:\/"
        ]
    },
    ...
```

### users

The users section defines users and its roles to access the api or web ui.
The subkey is the user id of a user. There are special user ids:

- `*` - contains the roles for anonymous access
- `__default_authenticated_user__` - default roles for an by the webserver authenticated user
- `[userid]` - a user id for api or web ui access. Allowed chars are a-z (lowercase) and 0-9.

If you create your first user then copy the entries for \* and __default_authenticated_user__ from default config.

The object below the user id contains

key       | description
----------|---------------------------
comment   | additional comments
password  | password hash for api user
roles     | flat list of roles
username  | Users display name

Remark:

The password hash will be verified by api requests only. It is optional for non protected api directory - then the user and password will be verified by the api itself.

To create a password hash on command line you can use

```txt
php -r 'echo password_hash("your-password-here", PASSWORD_BCRYPT);'
```

BUT: we recommend to use webservers protection with basic authentication for better performance.

All users without password field or `password: false` will match for users with webservers basic authentication.

Existing roles:

role      | description
----------|---------------------------
api       | general access to api
ui        | general access to the web interface
ui-config | additional role for web ui: allow access to configuration page
ui-debug  | additional role for web ui: show debug information
\*        | wildcard; grant access to all roles (= admin user)

Example:

```json
    ...
    "users": {
        "*": {
            "password": false,
            "username": "anonymous",
            "comment": "anonymous access: no config and no debug infos",
            "roles": [ "ui" ]
        },
        "api": {
            "password": "$2y$10$5E4ZWyul.VdZjpP1.Ff6Le0z0kxu3ix7jnbYhv0Zg5vhvhjdJTOm6",
            "comment": "api user for Axels Dashboard",
            "roles": [ "api" ]
        },
        "__default_authenticated_user__": {
            "comment": "default roles for an by the webserver authenticated user",
            "roles": [ "api", "ui" ]
        }
        "superuser": {
            "comment": "Access to all things here",
            "roles": [ "*" ]
        }
    },
    ...
```

## Urls

The list of appmonitor client urls is in appmonitor-server-urls.json.
This file is not part of the repository. It will be created if you store the first url.

## View

The key "view" has 2 subnodes

* "overview" - elements to show on application overview page
* "appdetails" - elements to show on application detail page

### view:overview

Set it true or false to set the visibility.

Key            | Description
---------------|---------------------------
"webapps"      | tile: number of web applications
"hosts"        | tile: number of hosts
"checks"       | tile: number of checks total for all web apps
"notification" | tile: show stats if notifications are currently enabled

### view:appdetails

Set it true or false to set the visibility.

Key            | Description
---------------|---------------------------
"appstatus"    | tile: number of web applications
"httpcode"     | tile: http status code of last check
"age"          | tile: age of the currently visible information and TTL
"checks"       | tile: number of checks total for current web app
"times"        | tile: total time to perform all checks of the web app (if available)
"tags"         | tile: show count of tags and its names
"receiver"     | tile: show app specific notification recerivers
"notification" | tile: show stats if notifications are currently enabled
