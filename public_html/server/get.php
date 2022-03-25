<?php
/* ======================================================================
 * 
 * APPMONITOR :: AJAX HELPER
 * 
 * API:
 * ?item=apps
 * ?item=apps&appid=...
 * ?item=apps&appid=...&what=...
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
    header('Content-Type: application/json');
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
        // $sHtml.='<pre>'.print_r($oMonitor->apiGetAppIds(), 1).'</pre>';
        writeJson($sAppId
            ? $oMonitor->apiGetAppAllData($sAppId)
            : $oMonitor->apiGetAppIds()
        );
        break;
    case 'appdata':
        $sHtml.='<pre>'.print_r($oMonitor->apiGetAppAllData($sAppId), 1).'</pre>';
        break;
    case 'appmeta':
        // $sHtml.='<pre>'.print_r($oMonitor->apiGetAppMeta($sAppId), 1).'</pre>';
        writeJson($oMonitor->apiGetAppMeta($sAppId));
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
