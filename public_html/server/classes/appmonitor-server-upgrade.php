<?php
/*

    INCLUDE FILE FOR APPMONITOR SERVER UPDATES
    see appmonitor-server.class.php - method loadConfig()

    This file is included into the clas only if an upgrade is required.

*/

if(!isset($this->_aCfg)){
    die("ERROR: This file cannot be called directly.");
}

// ----------------------------------------------------------------------
// FUNCTIONS
// ----------------------------------------------------------------------

function title($sTitle){
    $bHtml=$_SERVER['HTTP_HOST']??false;
    echo $bHtml 
        ? "<strong>---------- $sTitle</strong><br>"
        : "---------- $sTitle\n";
}

function fail($sMessage=''){
    echo "\n❌ ERROR: $sMessage<br>Aborting here.\n";
    die();
}

function ok($sMessage=''){
    echo "✅ OK $sMessage\n\n";
}

function skip($sMessage=''){
    echo "🔹 SKIP $sMessage\n\n";
}

// ----------------------------------------------------------------------
// MAIN
// ----------------------------------------------------------------------

if (isset($_SERVER['HTTP_HOST'])) { 
    echo "<pre><h1>UPGRADE to $this->_sVersion</h1>"; 
} else {
    echo "\n\n>>>>>>>>>> UPGRADE to $this->_sVersion\n\n\n";
}

// echo "from [$sLastVersion] --> [$this->_sVersion]\n";
// title("hi");
// skip("skiptest");
// ok("Test-OK");
// fail("Test-Abbruh");

// ----------------------------------------------------------------------
// since 0.139 (11-2024)
// config with urls was moved to an extra file
title("Check url config");
// migration for old way to load urls
if (isset($this->_aCfg['urls']) && is_array($this->_aCfg['urls'])) {
    echo "Url configuration Needs to be converted...\n";
    foreach ($this->_aCfg["urls"] as $sUrl) {
        $this->addUrl($sUrl);
    }
    $this->saveUrls();
    unset($this->_aCfg['urls']);
    if($this->saveConfig()){
        ok();
    } else {
        fail();
    }
} else {
    skip("Url configuration was converted already.");
}

// ----------------------------------------------------------------------
// since v0.150 (01-2025)
// Introduction of database. Cached data will be imported to the database.

$_sCacheModule = "notificationhandler-log";
$_sCacheId = "log";

title("Switch cache data to database - import notifications");
if($this->oNotification->countLogitems() > 0){
    skip("Already imported notifications: ".$this->oNotification->countLogitems().". Nothing to do.");
} else {
    // $oDB->setDebug(true);
    if (!$_oNotifications=new objnotifications($oDB)){
        fail("Failed to initilize notifications object.");
    };
    echo "Notifications object was initialized.\n";

    echo "Initializing cache...\n";
    $oCache = new AhCache($_sCacheModule, $_sCacheId);
    $aLog = $oCache->read();
    if(!is_array($aLog) || !count($aLog)){
        skip("No notifications as cached file found.");
    } else {

        echo "Found: ".count($aLog)." entries in cache\n";

        $iCounter=0;
        foreach($aLog as $aLogitem){
            $iCounter++;
            // echo "$iCounter - $aLogitem[appid] - $aLogitem[message]<br>";
            $_oNotifications->new();
    
            $_oNotifications->setItem([
                'timestamp'   => $aLogitem['timestamp'],
                'appid'       => $aLogitem['appid'],
                'changetype'  => $aLogitem['changetype'],
                'status'      => 0,
                'message'     => $aLogitem['message'],
                'result'      => json_encode($aLogitem['result'], 1),
            ]);
    
            if(!$_oNotifications->create()) {
                fail("Error: ".$oDB->error()."<br>".print_r($oDB->lastquery(1), 1)."<br>\n");
            }
        }
    
        echo "Notifications imported: ".$_oNotifications->count()."<br>";
        if($_oNotifications->count() !== count($aLog)){
            fail("Not all notifications were imported.");
        }
        ok();
    }
}

// ----------------------------------------------------------------------
/*
title("Drop old notifications cache");
$oCache = new AhCache($_sCacheModule);
$iCachefiles=count($oCache->getCachedItems());
if ($iCachefiles){
    echo "Deleting data...\n";
    if ($oCache->deleteModule()){
        ok();
    } else {
        fail();
    }
} else {
    skip("No cache found.");
}
*/


// ----------------------------------------------------------------------

title("UPGRADER is finished");
if (isset($_SERVER['HTTP_HOST'])) {
    echo "\n\nRefresh the page in the webbroeser!\n\n";
    echo "</pre>";
}
