<?php
/* ______________________________________________________________________
 * 
 * A P P M O N I T O R  ::  CLIENT - INCLUDE FOR APP CHECKS :: ON START
 * ______________________________________________________________________
 */

// ----------------------------------------------------------------------
// CHECK IF THE APPROOT IS SET
// ----------------------------------------------------------------------

// initialize client and set very basic metadata ... if needed
$bStandalone = !(class_exists('appmonitor') && isset($oMonitor));
if ($bStandalone) {
    require_once __DIR__ . '/../../classes/appmonitor-client.class.php';
    $oMonitor = new appmonitor();

    if (!isset($sApproot) || empty($sApproot)) {
        $sApproot = $_SERVER['DOCUMENT_ROOT'];
        if (isset($_GET['rel'])) {
            $sApproot .= str_replace('..', '__', $_GET['rel']);
            if (!is_dir($sApproot)) {
                header('HTTP/1.0 400 Bad request');
                die('ERROR: The given rel dir does not exist below webroot.');
            }
        }
    }

    // --- set values coming from app plugins defaults & GET params 
    // "name"
    // "host"
    // "tags"    
    // "dfw", "dfc"    
    $aAppDefaults['name'] = $_GET['name']??$aAppDefaults['name'];
    $aAppDefaults['host'] = $_GET['host']??false
        ? explode(',', $_GET['host'])
        : ($_SERVER['HTTP_HOST'] ?? '');
    $aAppDefaults['tags'] = $_GET['tags']??false ? explode(',', $_GET['tags']) : $aAppDefaults['tags'];

    $aAppDefaults['df']['warning'] = (isset($_GET['dfw']) && $_GET['dfw']) ? $_GET['dfw'] : $aAppDefaults['df']['warning'] ?? false;
    $aAppDefaults['df']['critical'] = (isset($_GET['dfc']) && $_GET['dfc']) ? $_GET['dfc'] : $aAppDefaults['df']['critical'] ?? false;
    if($aAppDefaults['df']['warning'] == false && $aAppDefaults['df']['critical'] == false) {
        unset($aAppDefaults['df']);
    }

    if ($aAppDefaults['name']) {
        $oMonitor->setWebsite($aAppDefaults['name']);
    }
    if ($aAppDefaults['host']) {
        $oMonitor->setHost($aAppDefaults['host']);
    }
    ;
    if (isset($aAppDefaults['tags']) && is_array($aAppDefaults['tags']) && count($aAppDefaults['tags']) > 0) {
        foreach ($aAppDefaults['tags'] as $sTag) {
            $oMonitor->addTag($sTag);
        }
    }

    @include __DIR__ . '/../../general_include.php';
}

// ----------------------------------------------------------------------
