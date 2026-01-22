<?php
/* ______________________________________________________________________
 * 
 * A P P M O N I T O R  ::  CLIENT - CHECK  ::  GENERAL INCLUDE
 * ______________________________________________________________________
 * 
 * The idea behind  is to a file with the same actions on all your 
 * installations and hosts that can be deployed by a software delivery service 
 * (Puppet, Ansible, ...)
 * 
 * Instruction:
 * (1) copy sample file to general_include.php and enable wished features below
 * (2) in your checks enable it by
 *     @include 'general_include.php';
 *     see index.sample.php too
 * 
 * @author: Axel Hahn
 * ----------------------------------------------------------------------
 * 2018-06-30  v0.1
 */

// ----------------------------------------------------------------------
// SECURITY STUFF ... protect access to monitoring data
// ----------------------------------------------------------------------

// --- check an IP range of allowed clients
/*
$oMonitor->checkIp([
    '127.0.0.1',
    '::1',
    '192.168.',
]);
 */

// --- check a token
// an incoming request must have the param ?token=123
// $oMonitor->checkToken('token', '123');


// ----------------------------------------------------------------------
// NOTIFICATION
// ----------------------------------------------------------------------

// $oMonitor->addEmail('sysadmin@example.com');
// $oMonitor->addSlackWebhook([ "mywebhook"=> "https://hooks.slack.com/services/(...)" ]);

