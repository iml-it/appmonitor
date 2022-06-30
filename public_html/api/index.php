<?php
/* ======================================================================
 * 
 * APPMONITOR :: API
 * 
 * ----------------------------------------------------------------------
 * ah <axel.hahm@iml.unibe.ch>
 * 2022-06-17  v1.0  ah  initial version
 * ======================================================================
 */

use iml\tinyapi;
use iml\tinyrouter;

require_once('../server/classes/appmonitor-server-api.class.php');
require_once('../server/classes/tinyapi.class.php');
require_once('../server/classes/tinyrouter.class.php');

// ----------------------------------------------------------------------
// CONFIG
// ----------------------------------------------------------------------

$aRoutes=[

    [ "/v1",                                       "_list_"         ],

    [ "/v1/apps",                                  "_list_"                                                ],

    // single application data
    [ "/v1/apps/id",                               ["method"=>"apiGetFilteredApp", "outmode" => "appid"]   ],
    [ "/v1/apps/id/@appid:[0-9a-f]*",              "_list_"                                                ],

    [ "/v1/apps/id/@appid:[0-9a-f]*/all",          ["method"=>"apiGetFilteredApp", "outmode" => "all"]     ],
    [ "/v1/apps/id/@appid:[0-9a-f]*/checks",       ["method"=>"apiGetFilteredApp", "outmode" => "checks"]  ],
    [ "/v1/apps/id/@appid:[0-9a-f]*/meta",         ["method"=>"apiGetFilteredApp", "outmode" => "meta"]    ],

    // multipe applications having a list of tags
    [ "/v1/apps/tags",                              ["method"=>"apiGetTags"]                               ],
    [ "/v1/apps/tags/@tags:[a-zA-Z,0-9\-]*",        "_list_"                                               ],
    [ "/v1/apps/tags/@tags:[a-zA-Z,0-9\-]*/all",    ["method"=>"apiGetFilteredApp", "outmode" => "all"]    ],
    [ "/v1/apps/tags/@tags:[a-zA-Z,0-9\-]*/checks", ["method"=>"apiGetFilteredApp", "outmode" => "checks"] ],
    [ "/v1/apps/tags/@tags:[a-zA-Z,0-9\-]*/meta",   ["method"=>"apiGetFilteredApp", "outmode" => "meta"]   ],

    // tags
    [ "/v1/tags",                                   ["method"=>"apiGetTags"]                               ],
    

];

$sAuthuser=false;

// ----------------------------------------------------------------------
// MAIN
// ----------------------------------------------------------------------


/*
$oApi = new tinyapi();
$oApi->allowMethods(['GET']);
$oApi->allowIps(['^130.92.']);
*/

$oMonitor = new appmonitorserver_api();
$aConfig=$oMonitor->getApiConfig();

// echo 'DEBUG <pre>';print_r($oMonitor->getApiUsers()); die("index.php");
$oApi = new tinyapi([
    'methods'=>['GET', 'OPTIONS' ], 
    'ips'=>isset($aConfig['sourceips']) ? $aConfig['sourceips'] : [],

    'users'=>$oMonitor->getApiUsers(),
    /*
    'users'=>[
        "*" => true, // allow anonymous

        "api"=>'$2y$10$5E4ZWyul.VdZjpP1.Ff6Le0z0kxu3ix7jnbYhv0Zg5vhvhjdJTOm6', // hello
        "cli"=>'$2y$10$EIv0PDJaruecZZCFYow1MekIT/NKqj0TS6cqk/.VOy1yPGJTEJNNO', // world
        //      ^
        //      |
        //      `--- echo password_hash("your-password-here", PASSWORD_BCRYPT)

    ],
    */
    'pretty'=> isset($aConfig['pretty']) ? $aConfig['pretty'] : false ,
]);


// ----------------------------------------------------------------------
// CHECKS

$oApi->checkMethod();
$oApi->checkIp();

$oMonitor->setUser($oApi->checkUser());
// $oApi->sendJson($sAuthuser);

// $oMonitor->apiSendHeaders();

if (!$oMonitor->hasRole('api')){
    $oApi->sendError(403, 'ERROR: Your user ['.$oMonitor->getUsername().'] has no permission to access the api.');
    die();
}


// ----------------------------------------------------------------------
// init router
$sApiUrl=isset($_GET['request']) && $_GET['request'] ? $_GET['request'] : false;
$oRouter=new tinyrouter($aRoutes, $sApiUrl);

$aFoundRoute=$oRouter->getRoute();
if(!$aFoundRoute){
    $oApi->sendError(400, 'ERROR: Your request was not understood. Maybe you try to access a non existing route or a variable / id contains in your url invalid chars.');
}

$oApi->stopIfOptions();

$sItem=isset($oRouter->getUrlParts()[1]) ? $oRouter->getUrlParts()[1] : false;
$callback=$oRouter->getCallback();

// echo '<pre>'; print_r($aFoundRoute);

if($callback=='_list_'){
    $oApi->sendJson($oRouter->getSubitems());
    die();
}

$sAction=isset($callback['method']) ? $callback['method'] : false;

// ----------------------------------------------------------------------
// get return data

$aData=[];
$oMonitor->loadClientData();
switch ($sItem){

    // ---------- SINGLE APP DATA
    case 'apps':

        // generate parameters
        $sAppId=$oRouter->getVar('appid');
        $aTags=$oRouter->getVar('tags') ? explode(',', $oRouter->getVar('tags')) : false;
        
        $aFilter=[
            'appid'=>$sAppId,
            'tags'=>$aTags,
        ];

        $sOutmode=isset($callback['outmode']) ? $callback['outmode'] : false;

        $aData=$oMonitor->$sAction($aFilter, $sOutmode);
        /*
         * is it really a good idea to send a 404??
         * 
        if(count($aData)==0){
            $aData=['http'=>'404', 'error'=> 'ERROR: No app was found that matches the filter.'];
        }
        */
        
        break;
        ;;
    
    // ---------- TAGS
    case 'tags':

        $aData=$oMonitor->$sAction();
        break;
        ;;

    default:
        $aData=[
            'http'=>400, 
            'error'=>'ERROR: unknown item ['.$sItem.'] ... or it is not implemented yet.'
        ];
}
$oApi->sendJson($aData);
