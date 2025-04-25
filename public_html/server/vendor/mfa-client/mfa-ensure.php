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

// set custom user (ignores field "user" in mfaconfig.php)
$mfa->setUser($this->getUserid());

$iHttpStatus=$mfa->ensure(false);
