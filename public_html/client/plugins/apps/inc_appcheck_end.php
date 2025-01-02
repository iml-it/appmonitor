<?php
/* ______________________________________________________________________
 * 
 * A P P M O N I T O R  ::  CLIENT - INCLUDE FOR APP CHECKS :: ON END
 * ______________________________________________________________________
 */

include 'shared_check_ssl.php';

// $bStandalone was set in inc_appcheck_start.php
// send response if client was not initialized there
if ($bStandalone) {

    if(count($oMonitor->getChecks())==0){

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

    $oMonitor->setResult();
    $oMonitor->render();
}

// ----------------------------------------------------------------------
