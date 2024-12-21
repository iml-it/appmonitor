<?php
/* ______________________________________________________________________
 * 
 * A P P M O N I T O R  ::  CLIENT - CHECK
 * ______________________________________________________________________
 * 
 * Check for a Matomo instance.
 * Open Analytics platform - https://matomo.org/
 * 
 * It checks 
 * - the write access to the config file
 * - connect to matomo database (which is read from config)
 * - ssl certificate (on https request only)
 * 
 * @author: Axel Hahn - https://www.axel-hahn.de/
 * ----------------------------------------------------------------------
 * 2018-06-30  v1.0
 * 2019-05-24  v1.01  detect include or standalone mode
 * 2019-05-24  v1.02  detect include or standalone mode
 * 2024-12-20  v1.03  <axel.hahn@unibe.ch> integrate in appmonitor repository
 */

// ----------------------------------------------------------------------
// Init
// ----------------------------------------------------------------------

$aAppDefaults = [
    "name" => "Matomo web statistics",
    "tags" => ["matomo", "statistics"],
];

require 'inc_appcheck_start.php';

// ----------------------------------------------------------------------
// Read Matomo specific config items
// ----------------------------------------------------------------------

$sConfigfile = $sApproot . '/config/config.ini.php';
if (!file_exists($sConfigfile)) {
    header('HTTP/1.0 400 Bad request');
    die('ERROR: Config file was not found. Set a correct $sApproot pointing to Matomo install dir.');
}
$aConfig = parse_ini_file($sConfigfile, true);


// ----------------------------------------------------------------------
// checks
// ----------------------------------------------------------------------


$oMonitor->addCheck(
    [
        "name" => "config file",
        "description" => "The config file must be writable",
        "check" => [
            "function" => "File",
            "params" => [
                "filename" => $sConfigfile,
                "file" => true,
                "writable" => true,
            ],
        ],
    ]
);

$oMonitor->addCheck(
    [
        "name" => "Mysql Connect",
        "description" => "Connect mysql server " . $aConfig['database']['host'] . " as user " . $aConfig['database']['username'] . " to scheme " . $aConfig['database']['dbname'],
        "check" => [
            "function" => "MysqlConnect",
            "params" => [
                "server" => $aConfig['database']['host'],
                "user" => $aConfig['database']['username'],
                "password" => $aConfig['database']['password'],
                "db" => $aConfig['database']['dbname'],
            ],
        ],
    ]
);

// ----------------------------------------------------------------------

include 'shared_check_ssl.php';

// ----------------------------------------------------------------------

require 'inc_appcheck_end.php';

// ----------------------------------------------------------------------