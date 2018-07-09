<?php
/*
 * 
 * APPMONITOR :: C L I 
 * 
 */
require_once(__DIR__ . '/classes/appmonitor-server.class.php');
$bDebug=false;

global $sDivider;
$sDivider='.';


$oMonitor = new appmonitorserver();
$aCfg=$oMonitor->getConfigVars();

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
    global $argv, $sDivider;
    echo "HELP:
    ".$argv[0]." [ACTION [parameter1 [parameter2]]]

    ACTIONs and its parameter are:

        --addurl URL
            add a client monitor url
            parameter1: url

        --deleteurl URL 
            delete a client monitor url
            url must exist
            parameter1: url

        --delete VARNAME
            remove subkey from config
            parameter1: VARNAME

        --show VARNAME
            show value(s) of the config
            use no param (or ALL as varname) to show whole config
            parameter1: VARNAME (optional; default is ALL)

        --set VARNAME VALUE
            set a value of a given key. If the key does not exist it will be created.
            parameter1: VARNAME
            parameter2: VALUE

    remarks:
    - in VARNAME - use '$sDivider' as divider of subkeys
    - you can chain commands. i.e. 
      --set VARNAME VALUE --show 
      They will be processed sequentially.
";          
}

/**
 * prevent that root executes this script - requires php posix module on *nix
 */
function denyRoot(){
    if (function_exists("posix_getpwuid")) {
        $processUser = posix_getpwuid(posix_geteuid());
        wd("detected user: ".print_r($processUser, 1));
        if ($processUser['name'] == "root") {
            die("ERROR: Do not start the script as user root. Run it as the user of the application\n");
        }
    }
}

/**
 * check if key exists in an array
 * 
 * @global array  $aCfg      config data of appmonitor
 * @param string  $sVarname  subkey to check
 * @param array   $aArray    array to substitute $aCfg
 * @return boolean
 */
function checkCfgvarExists($sVarname, $aArray=false){
    global $aCfg, $sDivider;
    if(!$aArray){
        $aArray=$aCfg;
    }
    
    $aTmp=preg_split('/\\'.$sDivider.'/', $sVarname);
    $sSubkey=array_shift($aTmp);
    if(!isset($aArray[$sSubkey])){
        quit("a varname [$sSubkey] does not exist in the config.\n");
    }
    if(count($aTmp)){
        return checkCfgvarExists(implode($sDivider, $aTmp), $aArray[$sSubkey]);
    }
    return $aArray[$sSubkey];
}

/**
 * set a (new) value in array
 * 
 * @global array $aCfg
 * @param type $sVarname
 * @param type $value
 * @return boolean
 */
function cfgSet($sVarname, $value){
    global $aCfg, $sDivider;
    
    $aArray=&$aCfg;
    $aTmp=preg_split('/\\'.$sDivider.'/', $sVarname);
    $sLastKey=array_pop($aTmp);
    if(count($aTmp)){
        foreach($aTmp as $sKeyname){
            if(!isset($aArray[$sKeyname])){
                $aArray[$sKeyname]=array();
            }
            $aArray=&$aArray[$sKeyname];
        }
    }
    echo "sLastKey = $sLastKey \n";
    if(is_array($aArray[$sLastKey])){
        $aArray[$sLastKey][]=$value;
    } else {
        $aArray[$sLastKey]=$value;
    }
    return true;
}

/**
 * delete a value or subkey in the array
 * @global array $aCfg
 * @param type $sVarname
 * @return boolean
 */
function cfgRemove($sVarname){
    global $aCfg, $sDivider;
    
    $aArray=&$aCfg;
    $aTmp=preg_split('/\\'.$sDivider.'/', $sVarname);
    $sLastKey=array_pop($aTmp);
    if($aTmp!==false){
        if(count($aTmp)) foreach($aTmp as $sKeyname){
            if(!isset($aArray[$sKeyname])){
                quit("the subkey [$sKeyname] was not found in wanted structure $sVarname");
            }
            $aArray=&$aArray[$sKeyname];
        }
        if(!isset($aArray[$sLastKey])){
            quit("the last subkey [$sLastKey] was not found in wanted structure $sVarname");
        }
        unset($aArray[$sLastKey]);
    }
    return true;
}

/**
 * quit with error message and exitcode <> 0
 * @param string  $sMessage  text to show
 * @param integer $iExit     optional: exitcode; default=1
 */
function quit($sMessage, $iExit=1){
    echo "ERROR: $sMessage\n";
    exit($iExit);
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

if($argc<2){
    showHelp();
    exit(0);
}
array_shift($argv);

while(count($argv)>0){
    $sAction=$argv[0];
    $sParam2=isset($argv[1]) ? $argv[1] : NULL;
    $sParam3=isset($argv[2]) ? $argv[2] : NULL;

    wd("action = $sAction | varname = $sParam2 | value = $sParam3\n");

    // ----- do action

    switch ($sAction){

        case "--addurl":
            if(!$sParam2){
                quit("param for url to delete is required.\n");
            }
            $sUrl=$sParam2;
            wd("addurl $sUrl ...");
            if ($oMonitor->actionAddUrl($sUrl)){
                echo "OK, url [$sUrl] was added.\n";
            } else {
                quit("url [$sUrl] was NOT added. Maybe it is \n- not an url or\n- it is not app monitor or\n- it already exists.\n");
            }
            array_shift($argv);
            array_shift($argv);
            // $aCfg=$oMonitor->getConfigVars(); print_r($aCfg['urls']);
            break;
        case "--deleteurl":
            if(!$sParam2){
                quit("param for url to delete is required.\n");
            }
            $sUrl=$sParam2;
            wd("deleteurl $sUrl ...");
            if ($oMonitor->actionDeleteUrl($sUrl)){
                echo "OK, url [$sUrl] was deleted.\n";
            } else {
                quit("url [$sUrl] was NOT deleted.\n");
            }
            // $aCfg=$oMonitor->getConfigVars(); print_r($aCfg['urls']);
            array_shift($argv);
            array_shift($argv);
            break;

        case "--delete":
            if(strpos($sParam2, "urls")===0){
                quit("use --deleteurl [url] to remove a client monitor url");
            }
            if(!$sParam2){
                quit("param for key(structure) to delete is required.\n");
            }
            wd("delete var $sParam2 ...");
            cfgRemove($sParam2);
            $oMonitor->saveConfig($aCfg);
            echo "OK, [$sParam2] was removed.\n";
            // wd("config is now: ");
            // print_r($aCfg);
            array_shift($argv);
            array_shift($argv);
            break;

        case "--set":
            if(!$sParam2){
                quit("param for key(structure) to set is required.\n");
            }
            if(strpos($sParam2, "urls")===0){
                quit("use --addurl [url] to add a new client monitor url");
            }
            if(!$sParam3===NULL){
                quit("param for value to to set to [$sParam2] is required.\n");
            }
            wd("set var $sParam2 to $sParam3 ...");
            cfgSet($sParam2, $sParam3);
            $oMonitor->saveConfig($aCfg);
            echo "OK, [$sParam2] = $sParam3 was set\n";
            // print_r($aCfg);
            array_shift($argv);
            array_shift($argv);
            array_shift($argv);
            break;
        case "--show":
            array_shift($argv);

            if(strpos($sParam2, '--')===0){
                $sParam2='ALL';
            } else {
                array_shift($argv);
            }
            echo "; show var [$sParam2]\n";
            if(!$sParam2 || $sParam2==='ALL'){
                print_r($aCfg);
                break;
            }
            print_r(checkCfgvarExists($sParam2));
            echo "\n";
            // print_r($aCfg[$sVarname]);
            break;
        default:
            quit("not implemented action: ".$sAction."\n");
            break;
    }
    echo "; ----------------------------------------------------------------------\n";
}

wd("finishing with status OK");
// ----------------------------------------------------------------------
