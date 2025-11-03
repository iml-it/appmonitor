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
 * 2024-12-21  v1.04  ah                   add php-modules and parent
 * 2025-01-06  v1.05  ah                   add checks for writable dirs; add df
 */

// ----------------------------------------------------------------------
// Init
// ----------------------------------------------------------------------

$aAppDefaults = [
    "name" => "Matomo web statistics",
    "tags" => ["matomo", "statistics"],
    "df" => [
        "warning" => "100MB",
        "critical" => "10MB"
    ]
];

require 'inc_appcheck_start.php';

// ----------------------------------------------------------------------
// Read Matomo specific config items
// ----------------------------------------------------------------------

$sConfigfile = $sApproot . '/config/config.ini.php';
if (!file_exists($sConfigfile)) {
    http_response_code(400);
    die('ERROR: Config file was not found. Set a correct $sApproot pointing to Matomo install dir.');
}
$aConfig = parse_ini_file($sConfigfile, true);


// ----------------------------------------------------------------------
// checks
// ----------------------------------------------------------------------

// required php modules
// see https://matomo.org/faq/on-premise/matomo-requirements/
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
                    "gd",
                    "mbstring",
                    "pdo_mysql",
                    "xml",
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
                "writable" => true,
            ],
        ],
    ]
);

$oMonitor->addCheck(
    [
        "name" => "Mysql Connect",
        "description" => "Connect mysql server " . $aConfig['database']['host'] . " as user " . $aConfig['database']['username'] . " to scheme " . $aConfig['database']['dbname'],
        "parent" => "config file",
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


// directory list from system check
foreach (['/tmp', '/tmp/assets', '/tmp/cache', '/tmp/climulti', '/tmp/latest', '/tmp/logs', '/tmp/sessions', '/tmp/tcpdf', '/tmp/templates_c'] as $sDir) {
    $oMonitor->addCheck(
        [
            "name" => "check writable dir $sDir",
            "description" => "The directory $sDir must be readable and writable",
            "group" => "folder",
            "check" => [
                "function" => "File",
                "params" => [
                    "filename" => "$sApproot/$sDir",
                    "dir" => true,
                    "readable" => true,
                    "writable" => true,
                ],
            ],
        ]
    );
}


if (isset($aAppDefaults['df'])) {
    
    $oMonitor->addCheck(
        [
            "name" => "check disk space",
            "description" => "The file storage must have some space left - warn: " . $aAppDefaults["df"]['warning'] . "/ critical: " . $aAppDefaults["df"]['critical'],
            "check" => [
                "function" => "Diskfree",
                "params" => [
                    "directory" => $sApproot,
                    "warning"   => $aAppDefaults["df"]['warning'],
                    "critical"  => $aAppDefaults["df"]['critical'],
                ],
            ],
        ]
    );
}

// ----------------------------------------------------------------------

require 'inc_appcheck_end.php';

// ----------------------------------------------------------------------