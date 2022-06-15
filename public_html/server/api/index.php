<?php
/* ======================================================================
 * 
 * APPMONITOR :: API
 * 
 * API (POC .. do not use it yet):
 * ?item=apps
 *   ... shows ids
 * 
 * ?item=apps&appid=[APPID]
 *   ... shows metadata of app 
 * 
 * ?item=apps&appid=[APPID]&what=(all|meta|checks)
 *   ... show checks or all data of an app
 * 
 * ======================================================================
 */

require_once('../classes/appmonitor-server-gui.class.php');
require_once('../classes/tinyrouter.class.php');

$aRoutes=[
    // dummy entries:
    [ "/config",                             "get_config"     ],
    [ "/config/@var",                        "get_config_var" ],

    // wnated entries
    [ "/apps",                               "apiGetAppIds"       ],
    [ "/apps/@appid:[0-9a-f]*",              "acess_appdata"  ],
    [ "/apps/@appid:[0-9a-f]*/@what:[a-z]*", "acess_appdata"  ],
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
    die('<h1>400 Bad request</h1>unknown item ['.$sItem.'] ... or it is not implemented yet.');
}

// echo '<pre>'.print_r($aFoundRoute, 1).'</pre>';

$sItem=$oRouter->getUrlParts()[0];
$sAction=$oRouter->getCallback();

echo "$sItem ... $sAction";


// ----------------------------------------------------------------------
// init appmonitor

$oMonitor = new appmonitorserver_gui();
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

switch ($sItem){

    // ---------- TODO
    case 'apps':

        // ---------- get data
        $aData=[];
        $sAppId=$oRouter->getVar('appid');
        $sWhat=$oRouter->getVar('what');
        
        if (!$sAppId){
            $aData=$oMonitor->apiGetAppIds();
        } else {
            switch($sSubitem){
                case 'checks': 
                    $aData=$oMonitor->apiGetAppChecks($sAppId);
                    break;
                case 'all': 
                    $aData=$oMonitor->apiGetAppAllData($sAppId);
                    break;
                case 'meta': 
                default: 
                $aData=$oMonitor->apiGetAppMeta($sAppId);
                    ;;
            }
        }
        writeJson($aData);
        break;
    /*
    case 'data':
        $sHtml.='<pre>'.print_r($oMonitor->getMonitoringData(), 1).'</pre>';
        break;
    */
    
    default:
        header('HTTP/1.0 400 Bad request');
        die('<h1>400 Bad request</h1>unknown item ['.$sItem.'] ... or it is not implemented yet.');
}

