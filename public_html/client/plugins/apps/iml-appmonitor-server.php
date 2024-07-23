<?php
/* ______________________________________________________________________
 * 
 * A P P M O N I T O R  ::  CLIENT - CHECKS for server instance
 * ______________________________________________________________________
 * 
 * requires variable $sApproot
 * 
 * @author: Axel Hahn
 * ----------------------------------------------------------------------
 * 2019-04-29  aded check for ssl cert; removed a check
 * 2019-05-17  aded check http to config- and tmp dir
 * 2021-11-nn  removed all checks ... created as single files
 * 2022-03-28  move checks into plugins/apps/
 * 2024-07-23  php 8: short array syntax
 */

// ----------------------------------------------------------------------
// files and dirs
// ----------------------------------------------------------------------

$oMonitor->addCheck(
    [
        "name" => "write to ./tmp/",
        "description" => "Check cache storage",
        // "group" => "folder",
        "check" => [
            "function" => "File",
            "params" => [
                "filename" => "$sApproot/server/tmp",
                "dir" => true,
                "writable" => true,
            ],
        ],
    ]
);
$oMonitor->addCheck(
    [
        "name" => "write to ./config/",
        "description" => "Check config target directory",
        // "group" => "folder",
        "check" => [
            "function" => "File",
            "params" => [
                "filename" => "$sApproot/server/config",
                "dir" => true,
                "writable" => true,
            ],
        ],
    ]
);
$oMonitor->addCheck(
    [
        "name" => "check config file",
        "description" => "The config file must be writable",
        "parent" => "write to ./config/",
        // "group" => "file",
        "check" => [
            "function" => "File",
            "params" => [
                "filename" => "$sApproot/server/config/appmonitor-server-config.json",
                "file" => true,
                "writable" => true,
            ],
        ],
    ]
);

$oMonitor->addCheck(
    [
        "name" => "PHP modules",
        "description" => "Check needed PHP modules",
        // "group" => "folder",
        "check" => [
            "function" => "Phpmodules",
            "params" => [
                "required" => ["curl"],
                "optional" => [],
            ],
        ],
    ]
);

// ----------------------------------------------------------------------
// protect dirs against web access
// specialty: if the test results in an error, the total result switches
// to WARNING -> see worstresult value
// ----------------------------------------------------------------------
$sBaseUrl = 'http'.(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 's' : '')
        .'://'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT']
        .dirname(dirname($_SERVER['REQUEST_URI']));

foreach(['server/config', 'server/tmp'] as $sMyDir){
    $oMonitor->addCheck(
        [
            "name" => "http to $sMyDir",
            "description" => "Check if the $sMyDir directory is not accessible (counts as warning on fail)",
            "group" => "deny",
            "check" => [
                "function" => "HttpContent",
                "params" => [
                    "url" => "$sBaseUrl/$sMyDir/readme.md",
                    "status" => 403,
                ],
            ],
            "worstresult" => RESULT_WARNING
        ]
    );
}

// ----------------------------------------------------------------------
// count of current projects
// ----------------------------------------------------------------------
require_once($sApproot.'/server/classes/appmonitor-server.class.php');
$oServer=new appmonitorserver();
$iCount=count($oServer->getAppIds());
$oMonitor->addCheck(
    [
        "name" => "appcounter",
        "description" => "Monitored apps",
        "group" => "monitor",
        "parent" => "check config file",
        "check" => [
            "function" => "Simple",
            "params" => [
                "result" => RESULT_OK,
                "value" => "Found monitored web apps: $iCount",
                "count" => $iCount,
                "visual" => "simple",
            ],
        ],
    ]
);
// ----------------------------------------------------------------------
// check running service
// ----------------------------------------------------------------------
require_once($sApproot.'/server/classes/tinyservice.class.php');
ob_start();
$oService = new tinyservice("$sApproot/server/service.php", 15, "$sApproot/server/tmp");
$sIsStopped=$oService->canStart();
$out=ob_get_contents();
ob_clean();
$oMonitor->addCheck(
    [
        "name" => "running service",
        "description" => "Check if the service is running",
        "group" => "service",
        "check" => [
            "function" => "Simple",
            "params" => [
                "result" => ($sIsStopped ? RESULT_WARNING : RESULT_OK),
                "value" => ($sIsStopped 
                    ? "Info: Service is NOT running. Apps are checked interactively only (if the appmonitor web ui is running). | Output: $out" 
                    : "OK, service is running. | Output: $out"
                )
            ],
        ],
        "worstresult" => RESULT_OK        
    ]
);
// ----------------------------------------------------------------------
// check certificate if https is used
// ----------------------------------------------------------------------
include 'shared_check_ssl.php';

// ----------------------------------------------------------------------
// plugin test
// ----------------------------------------------------------------------
/*
 * 
 * AS A DEMO: using a custom plugin:
 * 
$oMonitor->addCheck(
    array(
        "name" => "plugin test",
        "description" => "minimal test of the plugin plugins/checkHello.php",
        "check" => array(
            "function" => "Hello",
            "params" => array(
                "message" => "Here I am",
            ),
        ),
    )
);
$oMonitor->addCheck(
    array(
        "name" => "plugin Load",
        "description" => "check current load",
        "check" => array(
            "function" => "Loadmeter",
            "params" => array(
                "warning" => 1.0,
                "error" => 3,
            ),
        ),
        "worstresult" => RESULT_OK
    )
);
$oMonitor->addCheck(
    array(
        "name" => "plugin ApacheProcesses",
        "description" => "check count running Apache processes",
        "check" => array(
            "function" => "ApacheProcesses",
            "params" => array(
            ),
        ),
        "worstresult" => RESULT_OK
    )
);
*/

// ----------------------------------------------------------------------
