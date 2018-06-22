
# APPMONITOR :: SERVER #

University Berne
IML - Institute of Medical Education

https://github.com/iml-it/appmonitor

- - -


# REQUIREMENTS #

- php7 (runs on php5 but is not suppoorted)
- php-curl

The server uses 

- [ahCache] (https://www.axel-hahn.de/docs/ahcache/index.htm) class to store 
   serialized data as file (included)
- [cdnorlocal] (https://www.axel-hahn.de/docs/cdnorlocal/index.htm)

... and loads from CDNJS (but could be stored locally too):
- [jquery] (http://jquery.com/)
- [datatables plugin] (http://datatables.net/)
- [font-awesome] (http://fortawesome.github.io/Font-Awesome/)




# CONFIG #

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
    "debug": false,
    "lang": "en-en",
    "tmpdir": "__DIR__/tmp",
    "notification":{
        "email":{
            "Sysadmin": "sysadmin@example.com"
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

| Key            | Description                                         |
|---             |---                                                  |
| _theme_        | \{string\} name of css to load (aka "skin")         |
| _debug_        | \{bool\} show debug tab with internal values        |
| _lang_         | \{string\} language                                 |
| _tmpdir_       | \{string\} custom temp dir; default: tmp inside app |
| _notification_ | \{array\} notification setup                        |
| _urls_         | \{array\} list of urls                              |

Remarks:

- _tmpdir_ must be writable for apache user and service.php (cli)
- _notification_ will be used in a future release
- _urls_ is a flat list of urls
