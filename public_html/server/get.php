<?php
/* ======================================================================
 * 
 * APPMONITOR :: AJAX HELPER
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

require_once('classes/appmonitor-server-gui.class.php');

$bDebug=true;

$sMode='html';
$sItem=isset($_GET['item']) && $_GET['item'] ? $_GET['item'] : false;

$sAppId=isset($_GET['appid']) && $_GET['appid'] ? $_GET['appid'] : false;
$sSubitem=isset($_GET['what']) && $_GET['what'] ? $_GET['what'] : false;

// TODO: id for notification $sId=isset($_GET['id']) && $_GET['id'] ? $_GET['id'] : false;

$sHtml='';


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

// $sHtml.=$bDebug ?  '<pre>'.print_r($_GET, 1).'</pre>' : '';
$oMonitor = new appmonitorserver_gui();
$oMonitor->loadClientData();
// echo $oMonitor->renderHtml();


switch ($sItem){
    
    // ---------- TODO
    case 'apps':
        $aData=[];
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
    
    // ---------- render html output
    case 'viewabout':
        $sHtml.=$oMonitor->generateViewAbout();
        break;
    case 'viewdebug':
        $sHtml.=$oMonitor->generateViewDebug();
        break;
    /*
     * TODO
    case 'viewnotificationdetails':
        $sHtml.=$oMonitor->generateViewNotification($sId);
        break;
     * 
     */
    case 'viewnotifications':
        $sHtml.=$oMonitor->generateViewNotifications();
        break;
    case 'viewproblems':
            $sHtml.=$oMonitor->generateViewProblems();
            break;
    case 'viewsetup':
        $sHtml.=$oMonitor->generateViewSetup();
        break;
    case 'viewweblist':
        $sHtml.=$oMonitor->generateViewWeblist();
        break;
    case 'viewweb':
        $sHtml.=$oMonitor->generateViewApp($sAppId);
        break;

    default:
        header('HTTP/1.0 400 Bad request');
        die('<h1>400 Bad request</h1>unknown item ['.$sItem.'] ... or it is not implemented yet.');
}
echo $sHtml;
