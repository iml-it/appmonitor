<?php
/* ======================================================================
 * 
 * APPMONITOR :: AJAX HELPER
 * 
 * Fetch html code for web ui
 * 
 * ======================================================================
 */

require_once('classes/appmonitor-server-gui.class.php');

$sItem = isset($_GET['item']) && $_GET['item'] ? $_GET['item'] : false;
$sAppId = isset($_GET['appid']) && $_GET['appid'] ? $_GET['appid'] : false;

$sHtml = '';

$oMonitor = new appmonitorserver_gui();
$oMonitor->loadClientData();

switch ($sItem) {

        // ---------- render html output
    case 'viewabout':
        $sHtml .= $oMonitor->generateViewAbout();
        break;
    case 'viewdebug':
        $sHtml .= $oMonitor->generateViewDebug();
        break;
    case 'viewnotifications':
        $sHtml .= $oMonitor->generateViewNotifications($_POST ?? []);
        break;
    case 'viewproblems':
        $sHtml .= $oMonitor->generateViewProblems();
        break;
    case 'viewsetup':
        $sHtml .= $oMonitor->generateViewSetup();
        break;
    case 'viewweblist':
        $sHtml .= $oMonitor->generateViewWeblist();
        break;
    case 'viewweb':
        $sHtml .= $oMonitor->generateViewApp($sAppId);
        break;

    default:
        http_response_code(400);
        die('<h1>400 Bad request</h1>unknown item [' . htmlentities($sItem) . '] ... or it is not implemented yet.');
}

echo $sHtml . $oMonitor->renderDbDebug();
