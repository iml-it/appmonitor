<?php
/* ______________________________________________________________________
 * 
 * A P P M O N I T O R  ::  GENERIC CHECK SSL CERT
 * 
 * If https is enabled on standard port 443 the validity of the
 * certificate.
 * If the current appplication uses http only this check does nothing.
 * 
 * ______________________________________________________________________
 * 
 * @author: Axel Hahn
 * ----------------------------------------------------------------------
 * 2022-03-28  created
 */


// ----------------------------------------------------------------------
// check certificate - only if https is used
// ----------------------------------------------------------------------
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) {
    $oMonitor->addCheck(
        [
            "name" => "Certificate check",
            "description" => "Check if SSL cert is valid and does not expire soon",
            "check" => [
                "function" => "Cert",
            ],
        ]
    );
}

// ----------------------------------------------------------------------
