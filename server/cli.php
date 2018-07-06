<?php
/*
 * 
 * APPMONITOR :: C L I 
 * 
 */

require_once('./classes/appmonitor-server.class.php');
$bDebug=false;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// ----------------------------------------------------------------------
// FUNCTIONS
// ----------------------------------------------------------------------

/**
 * show a help for the syntax of cli
 * @global array $argv
 */
function showHelp(){
    global $argv;
    echo "HELP:
        ".$argv[0]." [action] [parameter]
            actions are:
            --addurl URL
            --deleteurl URL - url must exist
            --show VARNAME - show value of config
                             use ALL for varname to show whole config
                             use ':' as divider of subkeys
            --set  VARNAME VALUE (coming soon)
";
}

/**
 * prevenmt that root executes this script
 */
function denyRoot(){
    if (function_exists("posix_getpwuid")) {
        $processUser = posix_getpwuid(posix_geteuid());
        if ($processUser['name'] == "root") {
            die("ERROR: Do not start the script as user root. Run it as the user of the application\n");
        }
    }
}

/**
 * check if key exists in an array
 * 
 * @global srray  $aCfg      config data of appmonitor
 * @param string  $sVarname  subkey to check
 * @param array   $aArray    array to substitute $aCfg
 * @return boolean
 */
function checkCfgvarExists($sVarname, $aArray=false){
    global $aCfg;
    $sDivider=':';
    if(!$aArray){
        $aArray=$aCfg;
    }
    
    $aTmp=preg_split('/'.$sDivider.'/', $sVarname);
    // print_r($aTmp);
    $sSubkey=array_shift($aTmp);
    if(!isset($aArray[$sSubkey])){
        echo "ERROR: a varname $sSubkey does not exist in the config.\n";
        exit(1);
    }
    if(count($aTmp)){
        return checkCfgvarExists(implode($sDivider, $aTmp), $aArray[$sSubkey]);
    }
    return $aArray[$sSubkey];
}

/**
 * write debug output
 * @param type $s
 */
function wd($s){
    global $bDebug;
    echo $bDebug ? "DEBUG: $s\n" : '';
}
// ----------------------------------------------------------------------
// MAIN
// ----------------------------------------------------------------------

echo '
; _________________________________________________________________________________________
;
;
;    CLI API FOR APPMONITOR
;
; _________________________________________________________________________________________
;
';


denyRoot();


// ----- get prameters

if($argc<3){
    showHelp();
    exit(0);
}
$sAction=$argv[1];
$sVarname=$argv[2];
$sValue=isset($argv[3]) ? $argv[3] : false;

wd("action = $sAction | varname = $sVarname | value = $sValue\n");

// ----- do action

$oMonitor = new appmonitorserver();
$aCfg=$oMonitor->getConfigVars();

switch ($sAction){

    case "--addurl":
        $sUrl=$sVarname;
        wd("addurl $sUrl ...");
        if ($oMonitor->actionAddUrl($sUrl)){
            echo "OK, url $sUrl was added.\n";
        } else {
            echo "ERROR: url $sUrl was NOT added. Maybe it is \n- not an url or\n- it is not app monitor or\n- it already exists.\n";
            exit (1);
        }
        // $aCfg=$oMonitor->getConfigVars(); print_r($aCfg['urls']);
        break;
    case "--deleteurl":
        $sUrl=$sVarname;
        wd("deleteurl $sUrl ...");
        if ($oMonitor->actionDeleteUrl($sUrl)){
            echo "OK, url $sUrl was deleted.\n";
        } else {
            echo "ERROR: url $sUrl was NOT deleted.\n";
            exit (1);
        }
        $aCfg=$oMonitor->getConfigVars();
        print_r($aCfg['urls']);
        break;
    
    case "--set":
        wd("set var $sVarname to $sValue ...");
        checkCfgvarExists($sVarname);
        if (is_array($aCfg[$sVarname])){
            echo "ERROR: You cannot set $sVarname - it is an array.";
            exit(1);
        }
        $aCfg[$sVarname]=$sValue;
        echo "$sVarname was set to $sValue\n";
        echo "REMARK: work in progress - the value was NOT saved.\n";
        break;
    case "--show":
        echo "; show var $sVarname\n";
        
        if($sVarname==='ALL'){
            print_r($aCfg);
            break;
        }
        print_r(checkCfgvarExists($sVarname));
        // print_r($aCfg[$sVarname]);
        break;
    default:
        echo "ERROR: not implemented action: ".$sAction."\n";
        exit(1);
        break;
}

wd("OK");
// ----------------------------------------------------------------------
