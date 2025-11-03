# TinyRouter

Axels first php class of a router.

## Installation

Copy the file *tinyrouter.class.php* somewhere into your project.

## Example

```php

// load the class
require_once('../classes/tinyrouter.class.php');

// define routes
$aRoutes=[
    [ "/config",                             "get_config"     ],
    [ "/config/@var",                        "get_config_var" ],

    [ "/apps",                               "listapps"       ],
    [ "/apps/@appid:[0-9a-f]*",              "acess_appdata"  ],
    [ "/apps/@appid:[0-9a-f]*/@what:[a-z]*", "acess_appdata"  ],
//    ^                                       ^
//    |                                       |
//    route                                   callback (string|array|... any type you want to get back)
//      string     = folder
//      @var       = set a variable
//      @var:regex = set variable if it matches the given regex
];

// take an url ... or use the request uri if you have pretty urls
$sApiUrl=isset($_GET['request']) && $_GET['request'] ? $_GET['request'] : false;

// init the class
$oRouter=new tinyrouter($aRoutes, $sApiUrl);

// it is the same like
// $oRouter=new tinyrouter();
// $oRouter->setRoutes($aRoutes);
// $oRouter->setUrl($sApiUrl);

// get the last matching route
$aFoundRoute=$oRouter->getRoute();
if(!$aFoundRoute){
    http_response_code(400);
    die('<h1>400 Bad request</h1>');
}

// ... continue
```

The getRoute() method returns an array

* with the matching route
* name of the callback
* vars on parts with starting @ chars

```txt
// on url /apps/12345/meta

Array
(
    [route] => /apps/@appid:[0-9a-f]*/@what:[a-z]*
    [callback] => acess_appdata
    [vars] => Array
        (
            [appid] => 12345
            [what] => meta
        )

)
```

If no route matches - or a variable did not match a required regex - then getRoute() returns *false*.

Maybe the keys of the array change in future. You can access the data with specialized getter functions:

```php
// get the fallback 
$sAction=$oRouter->getCallback();

// all vars
$aAllvars=$oRouter->getVars();

// get single vars
$sAppId=$oRouter->getVar('appid');
$sWhat=$oRouter->getVar('what');
```
