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
 * 2022-03-28  put checks into plugins/apps/
 */

$sApproot = str_replace('\\', '/', dirname(__DIR__));

require_once($sApproot.'/client/classes/appmonitor-client.class.php');

// require_once('classes/client_all_in_one.php');
$oMonitor = new appmonitor();
$oMonitor->setWebsite('Appmonitor server');

// how often the server should ask for updates
$oMonitor->setTTL(300);
$oMonitor->addTag('monitoring');


// a general include ... the idea is to a file with the same actions on all
// installations and hosts that can be deployed by a software delivery service 
// (Puppet, Ansible, ...)
@include 'general_include.php';

// include default checks for an application
@require 'plugins/apps/iml-appmonitor-server.php';

// ----------------------------------------------------------------------

$oMonitor->setResult();
$oMonitor->render();

// ----------------------------------------------------------------------
