<?php
/* ______________________________________________________________________
 * 
 * A P P M O N I T O R  ::  CLIENT - CHECK  ::  SAMPLE
 * ______________________________________________________________________
 * 
 * this is a sample file for the appmonitor client
 * copy the sample file to index.php and modify it as needed (see ../readme.md).
 * 
 */

require_once('classes/appmonitor-client.class.php');
$oMonitor = new appmonitor();

// set a name with application name and environment or hostname
$oMonitor->setWebsite('[My CMS on host XY]');

// how often the server should ask for updates
$oMonitor->setTTL(300);

// a general include ... the idea is to a file with the same actions on all
// installations and hosts that can be deployed by a software delivery service 
// (Puppet, Ansible, ...)
@include 'general_include.php';

// add any tag to add it in the filter list in the server web gui
// $oMonitor->addTag('cms');
// $oMonitor->addTag('production');

// ----------------------------------------------------------------------

// include default checks for an application
// @require 'plugins/apps/[name-of-app].php';

// add a few custom checks
// $oMonitor->addCheck(...)
$oMonitor->addCheck(
    [
        "name" => "hello plugin",
        "description" => "Test a plugin ... plugins/checks/hello.php",
        "check" => [
            "function" => "Hello",
            "params" => [
                "message" => "Here I am",
            ],
        ],
    ]
);

// ----------------------------------------------------------------------

$oMonitor->setResult();
$oMonitor->render();

// ----------------------------------------------------------------------
