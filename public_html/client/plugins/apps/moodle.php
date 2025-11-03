<?php
/* ______________________________________________________________________
 * 
 * WORK IN PROGRESS
 * 
 * A P P M O N I T O R  ::  CLIENT - CHECK
 * ______________________________________________________________________
 * 
 * Check for a Moodle instance.
 * https://moodle.org/
 * 
 * It checks 
 * - the write access to the config file
 * - connect to mysql database (which is read from config)
 * - ssl certificate (on https request only)
 * 
 * @author: <axel.hahn@unibe.ch>
 * ----------------------------------------------------------------------
 * 2025-10-31  v0.01
 * 2025-11-03  v0.02  update parents; update php modules
 */

// ----------------------------------------------------------------------
// Init
// ----------------------------------------------------------------------

$aAppDefaults = [
    "name" => "Moodle",
    "tags" => ["moodle", "lms"],
    "df" => [
        "warning" => "100MB",
        "critical" => "10MB"
    ]
];

require 'inc_appcheck_start.php';

// ----------------------------------------------------------------------
// Read config items
// ----------------------------------------------------------------------

// Moodle 5.1: from webroot one dir up....
$sConfigfile = dirname($sApproot) . '/config.php';
if (!file_exists($sConfigfile)) {

    // Moodle 5.0: config.php is in webroot
    $sConfigfile = $sApproot . '/config.php';
    if (!file_exists($sConfigfile)) {
        header('HTTP/1.0 400 Bad request');
        die('ERROR: Config file [config.php] was not found. Set a correct app root pointing to Moodle install dir.');
    }
}
require($sConfigfile);
$aDb = [
    'server' => $CFG->dbhost??null,
    'username' => $CFG->dbuser??null,
    'password' => $CFG->dbpass??null,
    'database' => $CFG->dbname??null,
    'port'     => $CFG->dboptions['dbport']??null,
];

// ----------------------------------------------------------------------
// checks
// ----------------------------------------------------------------------

// Set a required database module
$aDbModules=[
    "mariadb"=>"mysqli",
    // to be continued here for other database types
];

$db_module=$aDbModules[$CFG->dbtype??false]??null;

// required php modules
// https://docs.moodle.org/501/en/PHP
$oMonitor->addCheck(
    [
        "name" => "PHP modules",
        "description" => "Check needed PHP modules",
        "check" => [
            "function" => "Phpmodules",
            "params" => [
                "required" => [
                    "ctype",
                    "curl",
                    "gd",
                    "iconv",
                    "intl",
                    "json",
                    "mbstring",
                    "pcre",
                    "redis",
                    "SimpleXML",
                    "SPL",
                    "soap",
                    "xml",
                    "zip",

                    // database ... one of it
                    // "mysqli",
                    // "oci8",
                    // "pdo",
                    // "pgsql",
                    // "sqlsrv",
                    $db_module,

                ],
                "optional" => [

                    // recommenmded
                    "openssl",  // required for networking and web services
                    "soap",     // required for web services
                    "sodium",   // required on PHP 8 and above
                    "tokenizer",
                    "xmlrpc",   // required for networking and web services
                ],
            ],
        ],
    ]
);

$oMonitor->addCheck(
    [
        "name" => "config file",
        "description" => "The config file must be readable (readonly is OK)",
        "check" => [
            "function" => "File",
            "params" => [
                "filename" => $sConfigfile,
                "file" => true,
                "readable" => true,
                // "writable" => true,
            ],
        ],
    ]
);


if($CFG->dbtype??false == "mariadb")
$oMonitor->addCheck(
    [
        "name" => "Mysql Connect",
        "description" => "Connect mysql server " . $aDb['server'] . " as user " . $aDb['username'] . " to scheme " . $aDb['database'],
        "parent" => "config file",
        "check" => [
            "function" => "MysqlConnect",
            "params" => [
                "server" => $aDb['server'],
                "user" => $aDb['username'],
                "password" => $aDb['password'],
                "db" => $aDb['database'],
                // "port"     => $aDb['port'],
            ],
        ],
    ]
);

$oMonitor->addCheck(
    [
        "name" => "Moodle data dir",
        "description" => "The data dir must be writable",
        "parent" => "config file",
        "check" => [
            "function" => "File",
            "params" => [
                "filename" => $CFG->dataroot,
                "dir" => true,
                "readable" => true,
                "writable" => true,
            ],
        ],
    ]
);

if (isset($aAppDefaults['df'])) {
    
    $oMonitor->addCheck(
        [
            "name" => "check disk space in moodledata dir",
            "description" => "The file storage must have some space left - warn: " . $aAppDefaults["df"]['warning'] . "/ critical: " . $aAppDefaults["df"]['critical'],
            "parent" => "Moodle data dir",
            "check" => [
                "function" => "Diskfree",
                "params" => [
                    "directory" => $CFG->dataroot,
                    "warning"   => $aAppDefaults["df"]['warning'],
                    "critical"  => $aAppDefaults["df"]['critical'],
                ],
            ],
        ]
    );
}

require 'inc_appcheck_end.php';

// ----------------------------------------------------------------------
