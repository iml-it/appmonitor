<?php
/* ======================================================================
 * 
 * APPMONITOR :: API
 * 
 * ======================================================================
 */

require_once('../server/classes/appmonitor-server-api.class.php');
require_once('../server/classes/tinyrouter.class.php');

$aRoutes=[

    [ "/v1",                                       "_list_"         ],

    // dummy entries:
    [ "/v1/config",                                "get_config"       ],
    [ "/v1/config/@var",                           "get_config_var"   ],
    [ "/v1/apps/@appid:[0-9a-f]*/@what:[a-z]*",    "acess_appdata"       ],

    [ "/v1/apps",                                  "_list_"         ],

    // single application data
    [ "/v1/apps/id",                               "apiGetAppIds"     ],
    [ "/v1/apps/id/@appid:[0-9a-f]*",              "_list_"     ],

    [ "/v1/apps/id/@appid:[0-9a-f]*/all",          "apiGetAppAllData" ],
    [ "/v1/apps/id/@appid:[0-9a-f]*/checks",       "apiGetAppChecks"  ],
    [ "/v1/apps/id/@appid:[0-9a-f]*/meta",         "apiGetAppMeta"    ],

    // 
    [ "/v1/apps/tags",                              "apiGetTags"    ],
    [ "/v1/apps/tags/@tags:[a-zA-Z,0-9\-]*",        "apiGetAppMeta"    ],

    // tags
    [ "/v1/tags",                                   "apiGetTags"       ],
    

];

// ----------------------------------------------------------------------
// FUNCTIONS
// ----------------------------------------------------------------------


/**
 * send API response:
 * set content type in http response header and transform data to json
 * @param  array  $aData  array of data to send
 */
function writeJson($aData){
    $_aHeader=[
        '404'=>'HTTP/1.0 404 Not Found'
    ];
    header('Content-Type: application/json');
    if(isset($aData['http'])){
        header($_aHeader[$aData['http']]);
    }
    echo json_encode($aData); 
    exit (0);
}


// ----------------------------------------------------------------------
// MAIN
// ----------------------------------------------------------------------


$sApiUrl=isset($_GET['request']) && $_GET['request'] ? $_GET['request'] : false;
$oRouter=new tinyrouter($aRoutes, $sApiUrl);

$aFoundRoute=$oRouter->getRoute();
if(!$aFoundRoute){
    header('HTTP/1.0 400 Bad request');
    die('<h1>400 Bad request</h1>Your request was not understood.');
}

// echo '<pre>'.print_r($aFoundRoute, 1).'</pre>';

$sItem=isset($oRouter->getUrlParts()[1]) ? $oRouter->getUrlParts()[1] : false;
$sAction=$oRouter->getCallback();

if($sAction=='_list_'){
    writeJson($oRouter->getSubitems());
    die("LIST");
}

$sAction=$oRouter->getCallback();


// ----------------------------------------------------------------------
// init appmonitor

$oMonitor = new appmonitorserver_api();
$oMonitor->loadClientData();

$_aTmpCfg=$oMonitor->getConfigVars();
$aCfg=$_aTmpCfg['api'];
/*
echo '<pre>'; 
print_r($_SERVER);
print_r($aCfg);die();
*/


// ---------- check access

/*

$bAllowRequest=true;
if(isset($aCfg['sourceips']) && is_array($aCfg['sourceips'])){
    $bAllowRequest=false;
    $sMyIp=$_SERVER['REMOTE_ADDR'];
    foreach($aCfg['sourceips'] as $sRegex) {
        if (preg_match($sRegex, $sMyIp)){
            $bAllowRequest=true;
            break;
        }
    }
}
if(!$bAllowRequest){
    header("HTTP/1.1 401 Unauthorized");
    die('<h1>401 Unauthorized</h1>');
}
$bFoundOrigin=array_search($_SERVER['REMOTE_ADDR'], $aCfg['sourceips']);
// header('Access-Control-Allow-Origin: '.$bFoundOrigin);

if (isset($aCfg['header']) && is_array($aCfg['header'])){
    foreach($aCfg['header'] as $sHeader=>$sValue){
        header($sHeader . ': '.$sValue);
    }
}

*/


// ----------------------------------------------------------------------
// get return data

$aData=[];
switch ($sItem){

    // ---------- SINGLE APP DATA
    case 'apps':

        $sAppId=$oRouter->getVar('appid');
        // $sWhat=$oRouter->getVar('what');
        if (!$sAppId){
            $aData=$oMonitor->$sAction();
        } else {
            $aData=$oMonitor->$sAction($sAppId);
        }
        break;
    
    // ---------- TAGS
    case 'tags':

        $aData=$oMonitor->$sAction();
        break;
    default:
        header('HTTP/1.0 400 Bad request');
        die('<h1>400 Bad request</h1>ERROR: unknown item ['.$sItem.'] ... or it is not implemented yet.');
}
writeJson($aData);
