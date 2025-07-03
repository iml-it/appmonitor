<?php
/**
 * mfa-ensure.php
 * included by public_html/server/classes/appmonitor-server-gui.class.php
 * 
 * @author Axel Hahn <axel.hahn@unibe>
 * 
 */

require_once __DIR__.'/mfaclient.class.php';
$mfa = new mfaclient();

$mfa->setUser($this->getUserid());

$iHttpStatus=$mfa->ensure();

// mfa was skipped? Enable this line to see the reason
// echo $mfa->showStatus();