<?php
/**
 * mfa-ensure.php
 * 
 * @author Axel Hahn <axel.hahn@unibe.ch>
 * 
 */

require_once __DIR__.'/mfaclient.class.php';
$mfa = new mfaclient();

$mfa->debug($aConfig['debug']??false);

// if user was not set in config, set it manually
$mfa->setUser($this->getUserid());

$iHttpStatus=$mfa->ensure();

// mfa was skipped? Enable this line to see the reason
// echo $mfa->showStatus();
