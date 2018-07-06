<?php

/*
 * this is a sample file for the appmonitor client
 * copy the sample file to index.php and modify it as needed (see ../readme.md).
 */

require_once('classes/appmonitor-client.class.php');
$oMonitor = new appmonitor();
$oMonitor->setWebsite('Appmonitor server');
$oMonitor->setTTL(10);


@include 'general_include.php';

$sApproot = str_replace('\\', '/', dirname(__DIR__));

// ----------------------------------------------------------------------

$oMonitor->addTag('monitoring');

// ----------------------------------------------------------------------

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

$oMonitor->setResult();
$oMonitor->render();

// ----------------------------------------------------------------------
