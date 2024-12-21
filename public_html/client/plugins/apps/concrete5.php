<?php
/* ______________________________________________________________________
 * 
 * A P P M O N I T O R  ::  CLIENT - CHECK
 * ______________________________________________________________________
 * 
 * Check for a Concrete5 instance.
 * CMS https://www.concrete5.org/
 * 
 * It checks 
 * - the write access to the config file
 * - the write access to the file storage
 * - connect to mysql database (which is read from config)
 * - ssl certificate (on https request only)
 * 
 * @author: Axel Hahn - https://www.axel-hahn.de/
 * ----------------------------------------------------------------------
 * 2018-06-30  v1.0   ah
 * 2019-05-24  v1.01  ah                   detect include or standalone mode
 * 2024-11-18  v1.02  <axel.hahn@unibe.ch> integrate in appmonitor repository
 * 2024-11-22  v1.03  <axel.hahn@unibe.ch> send 400 instead of 503 on error
 * 2024-12-21  v1.04  ah                   short array syntax; add php-modules and parent
 */

// ----------------------------------------------------------------------
// Init
// ----------------------------------------------------------------------

$aAppDefaults = [
    "name" => "Concrete5 CMS",
    "tags" => ["concrete5", "cms"],
];

require 'inc_appcheck_start.php';

// ----------------------------------------------------------------------
// Read Concrete5 specific config items
// ----------------------------------------------------------------------


$sConfigfile = $sApproot . '/application/config/database.php';
if (!file_exists($sConfigfile)) {
    header('HTTP/1.0 400 Bad request');
    die('ERROR: Config file was not found. Use ?rel=[subdir] to set the correct subdir to find /application/config/database.php.');
}

$aConfig = include($sConfigfile);
$sActive=$aConfig['default-connection'];

if(!isset($aConfig['connections'][$sActive])){
    header('HTTP/1.0 400 Bad request');
    die('ERROR: Config file application/config/database.php was read - but database connection could not be detected from it in connections -> '.$sActive.'.');
}
// print_r($aConfig['connections'][$sActive]); die();
$aDb=$aConfig['connections'][$sActive];

// ----------------------------------------------------------------------
// checks
// ----------------------------------------------------------------------

// required php modules
// see https://documentation.concretecms.org/developers/introduction/system-requirements
$oMonitor->addCheck(
    [
        "name" => "PHP modules",
        "description" => "Check needed PHP modules",
        // "group" => "folder",
        "check" => [
            "function" => "Phpmodules",
            "params" => [
                "required" => [
                    "PDO", 
                    "curl", 
                    "dom", 
                    "fileinfo", 
                    "gd", 
                    "iconv", 
                    "mbstring", 
                    "pdo_mysql", 
                    "xml", 
                    "zip"
                ],
                "optional" => [],
            ],
        ],
    ]
);

$oMonitor->addCheck(
    [
        "name" => "config file",
        "description" => "The config file must be readable and writable",
        "check" => [
            "function" => "File",
            "params" => [
                "filename" => $sConfigfile,
                "file" => true,
                "readable" => true,
                "writable" => true,
            ],
        ],
    ]
);
$oMonitor->addCheck(
    [
        "name" => "check file storage",
        "description" => "The file storage must be writable",
        "check" => [
            "function" => "File",
            "params" => [
                "filename" => $sApproot .'/application/files',
                "dir" => true,
                "writable" => true,
            ],
        ],
    ]
);

$sPdoConnectString="mysql:host=$aDb[server];port=3306;dbname=$aDb[database];";

$oMonitor->addCheck(
    [
        "name" => "Mysql Master",
        "description" => "Connect mysql server " . $aDb['server'] . " as user " . $aDb['username'] . " to scheme " . $aDb['database'],
        "parent" => "config file",
        "check" => [
            "function" => "PdoConnect",
            "params" => [
                "connect"  => $sPdoConnectString,
                "user"     => $aDb['username'],
                "password" => $aDb['password'],
            ],
        ],
    ]
);

// ----------------------------------------------------------------------

require 'inc_appcheck_end.php';

// ----------------------------------------------------------------------
