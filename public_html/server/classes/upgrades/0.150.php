<?php
// ----------------------------------------------------------------------
// since v0.150 (02-2025)
// Introduction of database. Cached data will be imported to the database.
// included by ../appmonitor-server-upgrade.php

$_sCacheModule = "notificationhandler-log";
$_sCacheId = "log";

title("Switch cache data to database - import notifications");
if ($this->oNotification->countLogitems() > 0) {
    skip("Already imported notifications: " . $this->oNotification->countLogitems() . ". Nothing to do.");
} else {
    // $oDB->setDebug(true);
    if (!$_oNotifications = new objnotifications($oDB)) {
        fail("Failed to initilize notifications object.");
    }
    ;
    echo "Notifications object was initialized.\n";

    echo "Initializing cache...\n";
    $oCache = new AhCache($_sCacheModule, $_sCacheId);
    $aLog = $oCache->read();
    if (!is_array($aLog) || !count($aLog)) {
        skip("No notifications as cached file found.");
    } else {

        echo "Found: " . count($aLog) . " entries in cache\n";

        $iCounter = 0;
        foreach ($aLog as $aLogitem) {
            $iCounter++;
            echo ".";
            // echo "$iCounter - $aLogitem[appid] - $aLogitem[message]<br>";
            $_oNotifications->new();

            $_oNotifications->setItem([
                'timestamp' => $aLogitem['timestamp'],
                'appid' => $aLogitem['appid'],
                'changetype' => $aLogitem['changetype'],
                'status' => $aLogitem['status'],
                'message' => $aLogitem['message'],
                // 'result'      => json_encode($aLogitem['result'], 1),
            ]);

            if (!$_oNotifications->create()) {
                fail("Error: " . $oDB->error() . "<br>" . print_r($oDB->lastquery(1), 1) . "<br>\n");
            }
        }
        echo "\n";

        echo "Notifications imported: " . $_oNotifications->count() . "<br>";
        if ($_oNotifications->count() !== count($aLog)) {
            fail("Not all notifications were imported.");
        }
        ok();
    }
}

// ----------------------------------------------------------------------

title("Import response data to database");
if (!$_oWebapp = new objwebapps($oDB)) {
    fail("Failed to initilize webapps object.");
}
;
echo "Webapps object was initialized.\n";

if ($_oWebapp->count() > 0) {
    skip("Already imported responses: " . $_oWebapp->count() . ". Nothing to do.");
} else {

    echo "Initializing cache...\n";
    // $oCacheResults = new AhCache("appmonitor-server");
    $oCacheLastResult = new AhCache("notificationhandler-app"); // notificationhandler-app

    // $aResults=$oCacheResults->getCachedItems();
    $aResults = $oCacheLastResult->getCachedItems();

    echo "Starting import of cached responses...\n";

    // loop over cache entries and import
    foreach (array_values($aResults) as $aCacheitem) {

        $oCacheLastResult = new AhCache("notificationhandler-app", $aCacheitem['cacheid']); // notificationhandler-app
        $aDataLast = $oCacheLastResult->read();

        $_oWebapp->new();
        $_oWebapp->set("appid", $aCacheitem['cacheid']);

        $_oWebapp->set("lastresult", json_encode($aDataLast));
        if ($aDataLast['result']['result'] == 0) {
            $_oWebapp->set("lastok", json_encode($aDataLast));
        }

        if (!$_oWebapp->create()) {
            fail("Error: " . $oDB->error() . "<br>" . print_r($oDB->lastquery(1), 1) . "<br>\n");
        }
    }
    ok(count($aResults) . " responses imported.");

}
// ----------------------------------------------------------------------

title("Import rrd data to database");
if (!$_oSimplerrd = new objsimplerrd($oDB)) {
    fail("Failed to initilize simplerrd object.");
}
;
echo "Simplerrd object was initialized.\n";
$iCount = 0;
if ($_oSimplerrd->count() > 0) {
    skip("Already imported rrd data: " . $_oSimplerrd->count() . ". Nothing to do.");
} else {

    echo "Initializing cache...\n";
    // $oCacheResults = new AhCache("appmonitor-server");
    $oCacheRrd = new AhCache("rrd");

    // $aResults=$oCacheResults->getCachedItems();
    $aResults = $oCacheRrd->getCachedItems();

    echo "Starting import of cached items...\n";
    // print_r($aResults);
    // loop over cache entries and import
    foreach (array_values($aResults) as $aCacheitem) {
        // $oCacheResults->setCacheId($aCacheitem['cacheid']);
        // $aData=$oCacheResults->read();
        // print_r($aData);
        $iCount++;

        $sAppid = substr($aCacheitem['cacheid'], 0, 32);
        $sCounterkey = substr($aCacheitem['cacheid'], 33, 200);


        $oCacheRrd = new AhCache("rrd", $aCacheitem['cacheid']);
        $oCacheRrd->setCacheId($aCacheitem['cacheid']);
        $aDataCache = $oCacheRrd->read();
        echo "$sAppid - $sCounterkey - " . count($aDataCache) . " values .. ";

        // print_r($aDataCache); die();
        $_oSimplerrd->new();

        if (
            !$_oSimplerrd->setItem([
                "appid" => $sAppid,
                "countername" => $sCounterkey,
                "data" => json_encode($aDataCache),
                // "timestamp" => $aDataset['timestamp'],
                // "data" => $aDataset['data']['status'],
                // "value" => $aDataset['data']['value']
            ])
        ) {
            fail("Failed to set item #$iCount :-/");
        }
        echo "OK.\n";

        if (!$_oSimplerrd->create()) {
            fail("Error: " . $oDB->error() . "<br>" . print_r($oDB->lastquery(1), 1) . "<br>\n");
        }

    }
    ok("$iCount rrd items imported.");

}

// ----------------------------------------------------------------------

foreach (["counterids", "notificationhandler-app", "notificationhandler-log", "notificationhandler-notify", "responsetime", "rrd"] as $sModulename) {
    $oCache = new AhCache($sModulename);
    $iCachefiles = count($oCache->getCachedItems());
    title("Drop old cache - $sModulename ($iCachefiles items)");
    if ($oCache->deleteModule()) {
        ok();
    } else {
        fail();
    }
}

// ----------------------------------------------------------------------

delFiles([
    "public_html/server/classes/counteritems.class.php",
    "docs/90_PHP-classes/server/counteritems.class.php.md",
]);

// ----------------------------------------------------------------------
