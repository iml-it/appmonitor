<?php
/* ======================================================================
 * 
 * APPMONITOR :: API
 * 
 * ----------------------------------------------------------------------
 * ah <axel.hahm@iml.unibe.ch>
 * 2022-06-17  v0.1  ah  initial version
 * 2022-07-01  v1.0  ah  first public version
 * 2025-03-11  v1.1  ah  add routes wth public keyword in API
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

    [ "/",                                          "_help_"                                                , "Show help."],
    [ "/health",                                    "_health_"                                              , "Health check for mintoring tools."],
    [ "/v1",                                        "_list_"                                                , "Version 1"],

    [ "/v1/apps",                                   "_list_"                                                , "List available subdirs"],

    // single application data
    [ "/v1/apps/id",                                ["method"=>"apiGetFilteredApp", "outmode" => "appid"]   , "List all apps with ids"],
    [ "/v1/apps/id/@appid:[0-9a-f]*",               "_list_"                                                , "List available subdirs"],

    [ "/v1/apps/id/@appid:[0-9a-f]*/all",           ["method"=>"apiGetFilteredApp", "outmode" => "all"]     , "Show Metadata and checks"],
    [ "/v1/apps/id/@appid:[0-9a-f]*/checks",        ["method"=>"apiGetFilteredApp", "outmode" => "checks"]  , "Show checks only"],
    [ "/v1/apps/id/@appid:[0-9a-f]*/meta",          ["method"=>"apiGetFilteredApp", "outmode" => "meta"]    , "Show metadata only"],
    [ "/v1/apps/id/@appid:[0-9a-f]*/public",         ["method"=>"apiGetFilteredApp", "outmode" => "public"] , "Show public infos - no technical details"],

    // multipe applications having a list of tags
    [ "/v1/apps/tags",                              ["method"=>"apiGetTags"]                                , "List all available tags"],
    [ "/v1/apps/tags/@tags:[a-zA-Z,0-9\-]*",        "_list_"                                                , "List available subdirs"],
    [ "/v1/apps/tags/@tags:[a-zA-Z,0-9\-]*/all",    ["method"=>"apiGetFilteredApp", "outmode" => "all"]     , "Show Metadata and checks"],
    [ "/v1/apps/tags/@tags:[a-zA-Z,0-9\-]*/appid",  ["method"=>"apiGetFilteredApp", "outmode" => "appid"]   , "Show app id and name for each app"],
    [ "/v1/apps/tags/@tags:[a-zA-Z,0-9\-]*/checks", ["method"=>"apiGetFilteredApp", "outmode" => "checks"]  , "Show checks only"],
    [ "/v1/apps/tags/@tags:[a-zA-Z,0-9\-]*/meta",   ["method"=>"apiGetFilteredApp", "outmode" => "meta"]    , "Show metadata only"],
    [ "/v1/apps/tags/@tags:[a-zA-Z,0-9\-]*/public", ["method"=>"apiGetFilteredApp", "outmode" => "public"]  , "Show public infos - no technical details"],

    // tags
    [ "/v1/tags",                                   ["method"=>"apiGetTags"]                               , "List all available tags"],
    

];

$sAuthuser=false;

// ----------------------------------------------------------------------
// MAIN
// ----------------------------------------------------------------------

$oMonitor = new appmonitorserver_api();
$aConfig=$oMonitor->getApiConfig();

$oApi = new tinyapi([
    'methods'=>['GET', 'OPTIONS' ], 
    'ips'=>isset($aConfig['sourceips']) ? $aConfig['sourceips'] : [],

    'users'=>$oMonitor->getApiUsers(),
    'pretty'=> isset($aConfig['pretty']) ? $aConfig['pretty'] : false ,
]);


// ----------------------------------------------------------------------
// CHECKS

$oApi->checkMethod();
$oApi->checkIp();

$oMonitor->setUser($oApi->checkUser());


// ----------------------------------------------------------------------
// init router
$sApiUrl=$_GET['request'] ?? false;
$oRouter=new tinyrouter($aRoutes, $sApiUrl);

$aFoundRoute=$oRouter->getRoute();
if(!$aFoundRoute){
    $oApi->sendError(400, 'ERROR: Your request was not understood. Maybe you try to access a non existing route or a variable / id contains in your url invalid chars.');
}

$oApi->stopIfOptions();

$sItem=$oRouter->getUrlParts()[1] ?? false;
$callback=$oRouter->getCallback();


if($callback=='_list_'){
    $oApi->sendJson($oRouter->getSubitems());
    die();
}
if($callback=='_health_'){
    $oApi->sendJson($oMonitor->apiGetHeatlth());
    die();
}
if($callback=='_help_'){
    if(file_exists('help.php')){
        include "help.php";
    } else {
        $oApi->sendJson([
            'http'=>400, 
            'error'=>'ERROR: help is not enabled.'
        ]);
    }
    die();
}

$sAction=isset($callback['method']) ? $callback['method'] : '';

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

        $sOutmode=$callback['outmode'] ?? false;

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
