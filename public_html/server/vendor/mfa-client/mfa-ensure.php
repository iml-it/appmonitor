<?php

// $_SERVER['REMOTE_USER']="axel";
if(!($_SERVER['REMOTE_USER']??false)){
    return true;
}

$aConfig = @include "mfaconfig.php";
if(!($aConfig["mfa"]['api']??false)){
    return true;
}

require_once __DIR__.'/mfaclient.class.php';
$mfa = new mfaclient($aConfig["mfa"], ($_SERVER['REMOTE_USER']??''));

// $mfa->debug(true);

$iHttpStatus=$mfa->ensure();
