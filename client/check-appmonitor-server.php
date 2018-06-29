<?php
/*
 * this is a sample file for the appmonitor client
 * copy the sample file to index.php and modify it as needed (see ../readme.md).
 */

require_once('appmonitor-client.class.php');
$oMonitor = new appmonitor();

$oMonitor->setWebsite('Appmonitor server');
$oMonitor->setTTL(2);

// to add notifications
// $oMonitor->addEmail('sysadmin@example.com');
// $oMonitor->addSlackWebhook(array("mywebhook"=> "https://hooks.slack.com/services/(...)"));


$sApproot=str_replace('\\', '/',dirname(__DIR__));

$oMonitor->addCheck(
    array(
        "name" => "check tmp subdir",
        "description" => "Check cache storage",
        "check" => array(
            "function" => "Dir",
            "params" => array(
                "dir" => $sApproot . "/server/tmp",
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
            "function" => "Dir",
            "params" => array(
                "dir" => $sApproot . "/server/config",
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
                "file" => $sApproot . "/server/config/appmonitor-server-config.json",
                "writable" => true,
            ),
        ),
    )
);

$oMonitor->setResult();
$oMonitor->render();