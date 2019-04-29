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
 */

require_once('classes/appmonitor-client.class.php');
$oMonitor = new appmonitor();
$oMonitor->setWebsite('Appmonitor server');


// how often the server should ask for updates
$oMonitor->setTTL(300);

// a general include ... the idea is to a file with the same actions on all
// installations and hosts that can be deployed by a software delivery service 
// (Puppet, Ansible, ...)
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

$oMonitor->setResult();
$oMonitor->render();

// ----------------------------------------------------------------------
