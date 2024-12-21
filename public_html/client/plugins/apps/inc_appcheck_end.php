<?php
/* ______________________________________________________________________
 * 
 * A P P M O N I T O R  ::  CLIENT - INCLUDE FOR APP CHECKS :: ON END
 * ______________________________________________________________________
 */

include 'shared_check_ssl.php';

// $bStandalone was set in inc_appcheck_start.php
// send response if client was not initialized there
if($bStandalone){
    $oMonitor->setResult();
    $oMonitor->render();
}

// ----------------------------------------------------------------------
