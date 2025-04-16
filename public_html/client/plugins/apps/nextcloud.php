<?php
/* ______________________________________________________________________
 * 
 * A P P M O N I T O R  ::  CLIENT - CHECK
 * ______________________________________________________________________
 * 
 * Check for a Nextcloud instance.
 * 
 * It checks 
 * - the read + write access to the config file
 * - connect to database (which is read from config)
 * - the read + write access to data dir
 * - free disk space on data dir
 * - ssl certificate (on https request only)
 * 
 * @author: Axel Hahn - https://www.axel-hahn.de/
 * ----------------------------------------------------------------------
 * 2025-01-02  v1.0
 */

// ----------------------------------------------------------------------
// Init
// ----------------------------------------------------------------------

$aAppDefaults = [
    "name" => "Nextcloud",
    "tags" => ["nextcloud", "share"],
    "df" => [
        "warning" => "1GB",
        "critical" => "100MB"
    ]
];

require 'inc_appcheck_start.php';

// ----------------------------------------------------------------------
// Read Nextcloud specific config items
// ----------------------------------------------------------------------

$sConfigfile = "$sApproot/config/config.php";
if (!file_exists($sConfigfile)) {
    header('HTTP/1.0 400 Bad request');
    die('ERROR: Config file was not found. Use ?rel=/NAME or similiar to set a relative install dir.');
}

if (!include "$sConfigfile") {
    header('HTTP/1.0 400 Bad request');
    die('ERROR: Unable to read config file.');
}

// now $CONFIG is available ...
/*
Array
(
    [instanceid] => ocw...
    [passwordsalt] => cNs...
    [secret] => kFdQXw2w...
    [trusted_domains] => Array
        (
            [0] => https://www.example.com
        )

    [datadirectory] => /home/httpd/cloud/data
    [dbtype] => mysql
    [version] => 30.0.4.1
    [overwrite.cli.url] => https://www.example.com/cloud
    [dbname] => nextcloud
    [dbhost] => 127.0.0.1
    [dbport] => 
    [dbtableprefix] => oc_
    [mysql.utf8mb4] => 1
    [dbuser] => mydbuser
    [dbpassword] => 516px9kcc...
    [installed] => 1
    [maintenance] => 
    [theme] => 
    [loglevel] => 2
    [mail_smtpmode] => smtp
    [mail_sendmailmode] => smtp
)
*/

if (!isset($CONFIG) || !is_array($CONFIG)) {
    header('HTTP/1.0 400 Bad request');
    die('ERROR: Config file was found but has unexpected format.');
} 


// ----------------------------------------------------------------------
// checks
// ----------------------------------------------------------------------

// required php modules
// see https://docs.nextcloud.com/server/latest/admin_manual/installation/system_requirements.html
// doesn't show needed modules
/*
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
*/

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
        "name" => "Version",
        "description" => "Nextcloud version",
        "parent" => "config file",
        "check" => [
            "function" => "Simple",
            "params" => [
                "result" => RESULT_OK,
                "value" => $CONFIG['version'] ?? "??",
                // "count" => $CONFIG['version'] ?? "??",
                // "visual" => "simple"
            ],
        ],
    ]
);
$oMonitor->addCheck(
    [
        "name" => "maintenance",
        "description" => "Is maintenance mode enabled?",
        "parent" => "config file",
        "check" => [
            "function" => "Simple",
            "params" => [
                "result" => $CONFIG['maintenance'] ? RESULT_ERROR : RESULT_OK,
                "value" => $CONFIG['maintenance'] ? "enabled - public access is denied" : "disabled (OK)",
            ],
        ],
    ]
);

// ----------------------------------------------------------------------
// database
// ----------------------------------------------------------------------

if($CONFIG['dbtype'] == "mysql"){
    $oMonitor->addCheck(
        [
            "name" => "Mysql Connect",
            // "description" => "Connect mysql server " . $aConfig['database']['host'] . " as user " . $aConfig['database']['username'] . " to scheme " . $aConfig['database']['dbname'],
            "description" => "Connect mysql server",
            "parent" => "config file",
            "check" => [
                "function" => "MysqlConnect",
                "params" => [
                    "server" => $CONFIG['dbhost'],
                    "user" => $CONFIG['dbuser'],
                    "password" => $CONFIG['dbpassword'],
                    "db" => $CONFIG['dbname'],
                    "port" => $CONFIG['dbport'],
                ],
            ],
        ]
    );
}

// ----------------------------------------------------------------------
// data directory
// ----------------------------------------------------------------------

$oMonitor->addCheck(
    [
        "name" => "data dir",
        "description" => "Data directory must be readable and writable",
        "parent" => "config file",
        "check" => [
            "function" => "File",
            "params" => [
                "filename" => $CONFIG['datadirectory'],
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
            "name" => "check disk space",
            "description" => "The file storage must have some space left - warn: " . $aAppDefaults["df"]['warning'] . "/ critical: " . $aAppDefaults["df"]['critical'],
            "parent" => "data dir",
            "check" => [
                "function" => "Diskfree",
                "params" => [
                    "directory" => $CONFIG['datadirectory'],
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
