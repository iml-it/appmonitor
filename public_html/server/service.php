<?php
/*
 * ======================================================================
 *
 * APPMONITOR SERVICE
 * get all client data in a permanent loop
 *
 * ======================================================================
 */

// -----------------------------------------------------------------------------
// CONFIG
// -----------------------------------------------------------------------------

$iSleep = 5; // seconds

// -----------------------------------------------------------------------------
// FUNCTIONS
// -----------------------------------------------------------------------------
// wrapper for signal handler
function signal_handler($signo)
{
    global $oService;
    echo "calling sigHandler($signo)\n";
    return $oService->sigHandler($signo);
}

// -----------------------------------------------------------------------------
// INIT SERVICE
// -----------------------------------------------------------------------------
echo "\n===== APPMONITOR :: service =====\n\n";
require_once('classes/tinyservice.class.php');
global $oService;
$oService = new tinyservice(__FILE__, $iSleep, __DIR__ . '/tmp');

// disallow root to run it
$oService->denyRoot();

// setting a signal handler - works on *nix only - this is NOT platform independent
if (function_exists("pcntl_signal")) {

    declare(ticks=1);
    pcntl_signal(SIGTERM, "signal_handler");
    pcntl_signal(SIGINT, "signal_handler");
    pcntl_signal(SIGHUP, "signal_handler");
    pcntl_signal(SIGUSR1, "signal_handler");
}

if (!$oService->canStart()) {
    die("CANNOT START ... another process seems to run.\n");
}
// $oService->setSleeptime($iSleep);
echo "----------\n";
$oService->send("STARTUP: init appmonitor-server", true);

// -----------------------------------------------------------------------------
// INIT APPMONITOR SERVER
// -----------------------------------------------------------------------------

require_once('classes/appmonitor-server.class.php');
$oMonitor = new appmonitorserver();

// @since v0.98: show fetched urls in stdout
$oMonitor->setLogging(true);


// -----------------------------------------------------------------------------
// LOOP
// -----------------------------------------------------------------------------

$oService->send("STARTUP: start loop ... ", true);
// $oService->setDebug(true);
while (true) {
    $oService->send("RUNNING: reload appmonitor server config");
    $oMonitor->loadConfig(); // to get changed hosts during runtime
    $oService->send("RUNNING: getMonitoringData()");
    $oMonitor->getMonitoringData();
    $oService->sleep();
}

echo "DONE - you never will see me :-)";

// -----------------------------------------------------------------------------
