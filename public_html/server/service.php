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

$iSleep = 5;         // seconds
$iShowRunning = 300; // timeout to show "RUNNING" with memory usage

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
echo "Refresh outdated monitoring data.\n\n";
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

$sAction=$argv[1]??"start";

switch ($sAction){
    case "start":
        break;;
    // case "stop":
    //     $oService->stop();
    //     exit(0);
    case "status":
        $oService->send("STATUS: ".
            $oService->canStart()
                ? "running"
                : "stopped"
        );
        exit(0);
    default:
        echo "ERROR: wrong action. It is one of start|status";
        exit(1);
}

if (!$oService->canStart()) {
    die("CANNOT START ... another process seems to run.\n");
}

// -----------------------------------------------------------------------------
// INIT APPMONITOR SERVER
// -----------------------------------------------------------------------------

echo "\n\n";
$oService->send("STARTUP: load class file for appmonitor-server", true);
require_once('classes/appmonitor-server.class.php');

// -----------------------------------------------------------------------------
// LOOP
// -----------------------------------------------------------------------------

$oService->send("STARTUP: start loop ... ", true);
// $oService->setDebug(true);
$iLastInfo=time();
while (true) {
    $oMonitor = new appmonitorserver();
    $oMonitor->setLogging(true);
    $oService->send("RUNNING: reload appmonitor server config");
    // $oMonitor->loadConfig(); // to get changed hosts during runtime
    // $oService->send("RUNNING: refreshClientData()");
    $oMonitor->refreshClientData();
    unset($oMonitor);
    $oService->sleep();
    if (time() - $iLastInfo > $iShowRunning){
        $iLastInfo=time();
        $oService->send("RUNNING", true);
    }
}

echo "DONE - you never will see me :-)";

// -----------------------------------------------------------------------------
