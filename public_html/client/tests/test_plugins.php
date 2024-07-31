<?php
/*
    TEST CLIENT CHECKS
*/

// ----------------------------------------------------------------------
// INIT
$sApproot = str_replace('\\', '/', dirname(__DIR__));


// require_once(__DIR__.'/../classes/client_all_in_one.php');
// echo "OK: file client_all_in_one.php was loaded\n";

require_once(__DIR__.'/../classes/appmonitor-client.class.php');

$oMonitor = new appmonitor();

$oMonitor->listChecks();


echo "OK: class appmonitor was initialized\n";


// ----------------------------------------------------------------------
$oMonitor->addTag('monitoring');

// ----------------------------------------------------------------------
$oMonitor->addCheck(
    [
        "name" => "check config subdir",
        "description" => "Check config target directory",
        "check" => [
            "function" => "File",
            "params" => [
                "filename" => $sApproot . "/server/config",
                "dir" => true,
                "writable" => true,
            ],
        ],
    ]
);
echo "OK: the plugin File check was added.\n";

// ----------------------------------------------------------------------
$oMonitor->setResult();
echo "OK: setResult() was executed.\n";

// ----------------------------------------------------------------------
