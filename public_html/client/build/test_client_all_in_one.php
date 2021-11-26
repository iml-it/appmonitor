<?php
/*

	This file is used to test a generated client_all_in_one.php 
	see merge_clientfiles.sh
*/

// ----------------------------------------------------------------------
// INIT
$sApproot = str_replace('\\', '/', dirname(__DIR__));
require_once(__DIR__.'/../classes/client_all_in_one.php');
echo "OK: file client_all_in_one.php was loaded\n";


$oMonitor = new appmonitor();
echo "OK: class appmonitor was initialized\n";


// ----------------------------------------------------------------------
$oMonitor->addTag('monitoring');

// ----------------------------------------------------------------------
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
echo "OK: the plugin File check was added.\n";

// ----------------------------------------------------------------------
$oMonitor->setResult();
echo "OK: setResult() was executed.\n";

// ----------------------------------------------------------------------
