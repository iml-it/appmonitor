<?php
/* ======================================================================
 * 
 * APPMONITOR :: API
 * 
 * ----------------------------------------------------------------------
 * ah <axel.hahm@iml.unibe.ch>
 * 2022-06-17  v0.1  ah  initial version
 * 2022-07-01  v1.0  ah  first public version
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

    [ "/",                                         "_help_"         ],
    [ "/health",                                   "_health_"         ],
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
// $oApi->sendJson($sAuthuser);

if (!$oMonitor->hasRole('api')){
    $oApi->sendError(403, 'ERROR: Your user ['.$oMonitor->getUserid().'] has no permission to access the api.');
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


if($callback=='_list_'){
    $oApi->sendJson($oRouter->getSubitems());
    die();
}
if($callback=='_health_'){
    $aData = $oMonitor->getMonitoringData();
    $aReturn=[
        'health'=>[
            'status'=>isset($aData['return'])?'OK':'error',
            'statusmessage'=>isset($aData['return'])?'Appmonitor is up and running.':'No monitoring data available',
        ],
        // 'raw'=>$aData,
        'monitoring'=>[
            'status'=>'-1',
            'statusmessage'=>'no monitoring data available',
        ],
    ];
    if(isset($aData['return'])){
        $aReturn['monitoring'] = [
            'status'=>$aData['return'],
            'statusmessage'=>$oMonitor->getResultValue($aData["return"]),
            'apps'=>[
                'count'=>$aData["results"]["total"],
                0=>['count'=>$aData["results"][0],'label'=>$oMonitor->getResultValue(0)],
                1=>['count'=>$aData["results"][1],'label'=>$oMonitor->getResultValue(1)],
                2=>['count'=>$aData["results"][2],'label'=>$oMonitor->getResultValue(2)],
                3=>['count'=>$aData["results"][3],'label'=>$oMonitor->getResultValue(3)],
            ]
        ];
    }
    $oApi->sendJson($aReturn);
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
