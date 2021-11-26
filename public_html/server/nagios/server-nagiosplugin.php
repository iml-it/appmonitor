<?php
/**
 *      NAGIOS PLUGIN FOR A GLOBAL CHECK
 * 
 *      This script creates nagios compatible output and exitcodes
 * 
 * ----------------------------------------------------------------------
 * 2015-01-20  <axel.hahn@iml.unibe.ch>  first running version
 * ----------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// CONFIG
// ----------------------------------------------------------------------

    /**
     * mapping of rc codes - left: appmonitor - right nagios returncodes
     */
    $aMappingRc=array(
        0=>0,
        1=>3,
        2=>1,
        3=>2,
    );
    

// ----------------------------------------------------------------------
// GET DATA
// ----------------------------------------------------------------------

    require_once(dirname(__DIR__).'/classes/appmonitor-server.class.php');
    $oMonitor = new appmonitorserver();

    $aData=$oMonitor->getMonitoringData();

// ----------------------------------------------------------------------
// REFORMAT OUTPUT
// see https://nagios-plugins.org/doc/guidelines.html
// ----------------------------------------------------------------------

    $sOut='Appmonitor-Check - returns '.$oMonitor->getResultValue($aData["return"]). ' - returncodes by server are ';
    foreach($aData["messages"] as $sKey=>$sResult){
        $sOut.=$sResult." ";
    }
    
    // .. add some graph data
    $sOut.=' | ';
    
    foreach($aData["results"] as $sKey=>$iResult){
        $sOut.="'". ($oMonitor->getResultValue($sKey)?$oMonitor->getResultValue($sKey): $sKey2) ."'=".$iResult." ";
    }
    
    echo $sOut;

    // NAGIOS compatible exitcode
    exit ($aMappingRc[$aData["return"]]);

// ----------------------------------------------------------------------
