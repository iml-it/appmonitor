<?php
/**
 * mfa-ensure.php
 * 
 * @author Axel Hahn <axel.hahn@unibe>
 * @package IML-Appmonitor
 * 
 */

if(!($_SERVER['REMOTE_USER']??false)){
    return true;
}

$aConfig = @include "mfaconfig.php";
if(!($aConfig["mfa"]['api']??false)){
    return true;
}

require_once __DIR__.'/mfaclient.class.php';
$mfa = new mfaclient($aConfig["mfa"], ($_SERVER['REMOTE_USER']??''));

$mfa->debug($aConfig["mfa"]['debug']??false);

$iHttpStatus=$mfa->ensure();
