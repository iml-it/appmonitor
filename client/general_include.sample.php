<?php
/* ______________________________________________________________________
 * 
 * A P P M O N I T O R  ::  CLIENT - CHECK  ::  GENERAL INCLUDE
 * ______________________________________________________________________
 * 
 * @author: Axel Hahn
 * ----------------------------------------------------------------------
 * 2018-06-30  v0.1
 */


// copy sample file to general_include.php and enable wished features below


// ----------------------------------------------------------------------
// SECURITY STUFF ... protect access to monitoring data
// ----------------------------------------------------------------------

// --- check an IP range
/*
$oMonitor->checkIp(array(
    '127.0.0.1',
    '::1',
    '192.168.',
));
 */

// --- check a token
// an incoming request must have the param ?token=123
// $oMonitor->checkTokem('token', '123');


// ----------------------------------------------------------------------
// NOTIFICATION
// ----------------------------------------------------------------------

// $oMonitor->addEmail('sysadmin@example.com');
// $oMonitor->addSlackWebhook(array("mywebhook"=> "https://hooks.slack.com/services/(...)"));

