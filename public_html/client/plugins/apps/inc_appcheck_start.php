<?php
/* ______________________________________________________________________
 * 
 * A P P M O N I T O R  ::  CLIENT - INCLUDE FOR APP CHECKS :: ON START
 * ______________________________________________________________________
 */

// ----------------------------------------------------------------------
// CHECK IF THE APPROOT IS SET
// ----------------------------------------------------------------------

// initialize client and set very basic metadata ... if needed
$bStandalone=!(class_exists('appmonitor') && isset($oMonitor));
if($bStandalone){
    require_once(__DIR__.'/../../classes/appmonitor-client.class.php');
    $oMonitor = new appmonitor();
    
    if (!isset($sApproot) || empty($sApproot)) {
        $sApproot = $_SERVER['DOCUMENT_ROOT'];

        $oMonitor->addCheck(
            [
                "name" => "Simple",
                "description" => "Welcome to a simple app check. This is just a quick winner.",
                "check" => [
                "function" => "Simple",
                    "params" => [
                        "result" => RESULT_OK,
                        "value" => "Create a custom check and add all checks you need to test the ability to run the application",
                    ],
                ],
            ]
        );
    }

    @include __DIR__.'/../../general_include.php';
}

// ----------------------------------------------------------------------
// FUNCTIONS
// ----------------------------------------------------------------------


// ----------------------------------------------------------------------
