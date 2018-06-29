<?php
/*
 * ======================================================================
 * 
 * APPMONITOR SERVICE
 * get all client data in a permanent loop
 * 
 * 
 * ======================================================================
 */

// -----------------------------------------------------------------------------
// CONFIG
// -----------------------------------------------------------------------------

$iSleep=20; // seconds


// -----------------------------------------------------------------------------
// MAIN
// -----------------------------------------------------------------------------
require_once('classes/tinyservice.class.php');
$oService=new tinyservice('appmomonitor_server_loop', $iSleep);


// disallow root to run it
$oService->denyRoot();

// setting a signal handler - this is NOT platform independent
pcntl_signal(SIGTERM, "\$oService->sigHandler");
// pcntl_signal(SIGHUP, "sig_handler");

if (!$oService->canStart()){
    die("CANNOT START ... another process seems to run.");
}

$oService->send("STARTUP: init appmonitor-server", true);
require_once('classes/appmonitor-server.class.php');
$oMonitor = new appmonitorserver();


$oService->send("STARTUP: start loop ... using sleep time of $iSleep sec.", true);
// $oService->setDebug(true);
while (true) {
    $oService->send("RUNNING: reload appmonitor server config");
    $aData=$oMonitor->loadConfig(); // to get changed hosts during runtime
    $oService->send("RUNNING: getMonitoringData()");
    $aData=$oMonitor->getMonitoringData();
    $oService->sleep();
    
}
