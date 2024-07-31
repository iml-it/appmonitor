<?php
/* ______________________________________________________________________
 * 
 * A P P M O N I T O R  ::  CLIENT - INCLUDE FOR APP CHECKS :: ON START
 * ______________________________________________________________________
 */

// ----------------------------------------------------------------------
// CHECK IF THE APPROOT IS SET
// ----------------------------------------------------------------------

if (!$sApproot) {
    header('HTTP/1.0 503 Service Unavailable');
    echo "<h1>503 Service Unavailable</h1>";
    echo 'ERROR:'.PHP_EOL;
    echo '$sApproot was not set. Define it before including the application check.'.PHP_EOL;
    echo 'Set the base folder of your application installation.'.PHP_EOL;
    echo PHP_EOL;
    echo 'Example:'.PHP_EOL;
    echo '$sApproot = $_SERVER[\'DOCUMENT_ROOT\'];'.PHP_EOL;
    echo '$sApproot = $_SERVER[\'DOCUMENT_ROOT\'].\'/myapp\';'.PHP_EOL;
    die();
}

// initialize client and set very basic metadata ... if needed
$bStandalone=!(class_exists('appmonitor') && isset($oMonitor));
if($bStandalone){
    require_once(__DIR__.'/../../classes/appmonitor-client.class.php');
    $oMonitor = new appmonitor();
    $oMonitor->setWebsite('Wordpress Instance');

    @include __DIR__.'/../../general_include.php';
}

// ----------------------------------------------------------------------
// FUNCTIONS
// ----------------------------------------------------------------------


// ----------------------------------------------------------------------
