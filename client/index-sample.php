<?php
/*
 * this is a sample file for the appmonitor client
 * copy the sample file to index.php and modify it as needed (se ../readme.txt).
 */

require_once('appmonitor-client.class.php');
$oMonitor = new appmonitor();

$oMonitor->addCheck(
    array(
        "name" => "Dummy",
        "description" => "Dummy Test",
        "check" => array(
            "function" => "Simple",
            "params" => array(
                "result" => 0,
                "value" => "The dummy test does nothing and was extremely successful",
            ),
        ),
    )
);


$oMonitor->setResult();
$oMonitor->render();