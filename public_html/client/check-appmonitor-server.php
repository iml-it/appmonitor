<?php
/* ______________________________________________________________________
 * 
 * A P P M O N I T O R  ::  CLIENT - CHECK
 * ______________________________________________________________________
 * 
 * This is the check file for the appmonitor server installation
 * Have a look to the docs/client-php.md and index.sample.php
 * to write your own checks
 * 
 * @author: Axel Hahn
 * ----------------------------------------------------------------------
 * 2019-04-29  aded check for ssl cert; removed a check
 * 2019-05-17  aded check http to config- and tmp dir
 * 2021-11-nn  removed all checks ... created as single files
 */

require_once('classes/appmonitor-client.class.php');
// require_once('classes/client_all_in_one.php');
$oMonitor = new appmonitor();
$oMonitor->setWebsite('Appmonitor server');

// how often the server should ask for updates
$oMonitor->setTTL(300);

// a general include ... the idea is to a file with the same actions on all
// installations and hosts that can be deployed by a software delivery service 
// (Puppet, Ansible, ...)
@include 'general_include.php';


// ----------------------------------------------------------------------

$oMonitor->addTag('monitoring');

// ----------------------------------------------------------------------
// files and dirs
// ----------------------------------------------------------------------
$sApproot = str_replace('\\', '/', dirname(__DIR__));


$oMonitor->addCheck(
    array(
        "name" => "check tmp subdir",
        "description" => "Check cache storage",
        "check" => array(
            "function" => "File",
            "params" => array(
                "filename" => $sApproot . "/server/tmp",
                "dir" => true,
                "writable" => true,
            ),
        ),
    )
);
$oMonitor->addCheck(
    array(
        "name" => "check config subdir",
        "description" => "Check config target directory",
        "check" => array(
            "function" => "File",
            "params" => array(
                "filename" => $sApproot . "/server/config",
                "dir" => true,
                "writable" => true,
            ),
        ),
    )
);
$oMonitor->addCheck(
    array(
        "name" => "check config file",
        "description" => "The config file must be writable",
        "check" => array(
            "function" => "File",
            "params" => array(
                "filename" => $sApproot . "/server/config/appmonitor-server-config.json",
                "file" => true,
                "writable" => true,
            ),
        ),
    )
);
// ----------------------------------------------------------------------
// protect dirs against web access
// specialty: if the test results in an error, the total result switches
// to WARNING -> see worstresult value
// ----------------------------------------------------------------------
$sBaseUrl = 'http'.(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 's' : '')
        .'://'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT']
        .dirname(dirname($_SERVER['REQUEST_URI']));

foreach(array('server/config', 'server/tmp') as $sMyDir){
    $oMonitor->addCheck(
        array(
            "name" => "deny http to $sMyDir",
            "description" => "Check if the $sMyDir directory is not accessible (counts as warning on fail)",
            "check" => array(
                "function" => "HttpContent",
                "params" => array(
                    "url" => $sBaseUrl . "/$sMyDir/readme.md",
                    "status" => 403,
                ),
            ),
            "worstresult" => RESULT_WARNING
        )
    );
}

// ----------------------------------------------------------------------
// count of current projects
// ----------------------------------------------------------------------
require_once(__DIR__.'/../server/classes/appmonitor-server.class.php');
$oServer=new appmonitorserver();
$iCount=count($oServer->apiGetAppIds());
$oMonitor->addCheck(
    array(
        "name" => "appcounter",
        "description" => "Monitored apps",
        "check" => array(
            "function" => "Simple",
            "params" => array(
                "result" => RESULT_OK,
                "value" => "Found monitored web apps: $iCount",
                "count" => $iCount,
                "visual" => "simple",
            ),
        ),
    )
);
// ----------------------------------------------------------------------
// check certificate if https is used
// ----------------------------------------------------------------------
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']){
    $oMonitor->addCheck(
        array(
            "name" => "Certificate check",
            "description" => "Check if SSL cert is valid and does not expire soon",
            "check" => array(
                "function" => "Cert",
            ),
        )
    );
}

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

$oMonitor->setResult();
$oMonitor->render();

// ----------------------------------------------------------------------
