<?php

require_once('classes/appmonitor-client.class.php');
$oMonitor = new appmonitor();

@include 'general_include.php';


// ----------------------------------------------------------------------
// add a few custom checks
// $oMonitor->addCheck(...)
$oMonitor->addCheck(
    [
        "name" => "Http-test",
        "description" => "Test Assetserver",
        "check" => [
            "function" => "HttpContent",
            "params" => [
                "url" => "https://assets.measured.preview.iml.unibe.ch",
                "status" => 404,
            ],
        ],
    ]
);

// ----------------------------------------------------------------------
// send the response

$oMonitor->setResult();
$oMonitor->render();

// ----------------------------------------------------------------------

// ----------------------------------------------------------------------
