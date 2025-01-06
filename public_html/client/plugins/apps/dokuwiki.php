<?php
/* ______________________________________________________________________
 * 
 * A P P M O N I T O R  ::  CLIENT - CHECK
 * ______________________________________________________________________
 * 
 * Check for a Dokuwiki instance.
 * https://www.dokuwiki.org/
 * 
 * @author: Axel Hahn - https://www.axel-hahn.de/
 * ----------------------------------------------------------------------
 * 2024-12-23  v1.00  ah                   initial version
 * 2024-12-26  v1.01  ah                   fix directory checks
 * 2025-01-06  v1.02  ah                   add df
 */

// ----------------------------------------------------------------------
// Init
// ----------------------------------------------------------------------

$aAppDefaults = [
    "name" => "Dokuwiki",
    "tags" => ["dokuwiki", "wiki"],
    "df" => [
        "warning" => "100MB",
        "critical" => "10MB"
    ]
];

require 'inc_appcheck_start.php';

// ----------------------------------------------------------------------
// Read Concrete5 specific config items
// ----------------------------------------------------------------------

$sConfigfile = "$sApproot/conf/local.php";
if (!file_exists($sConfigfile)) {
    header('HTTP/1.0 400 Bad request');
    die('ERROR: Config file was not found. Use ?rel=[subdir] to set the correct subdir to find /conf/local.php.');
}

// ----------------------------------------------------------------------
// checks
// ----------------------------------------------------------------------

// required php modules
// see https://www.dokuwiki.org/install:php
$oMonitor->addCheck(
    [
        "name" => "PHP modules",
        "description" => "Check needed PHP modules",
        // "group" => "folder",
        "check" => [
            "function" => "Phpmodules",
            "params" => [
                "required" => [
                    "json",
                    "pcre",
                    "session",
                ],
                "optional" => [
                    "bz2",
                    "gd",
                    "intl",
                    "mbstring",
                    "openssl",
                    "zlib"
                ],
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

foreach (['lib/tpl/', 'lib/plugins/',] as $sDir) {
    $oMonitor->addCheck(
        [
            "name" => "check read dir $sDir",
            "description" => "The directory $sDir must be readable",
            "group" => "folder",
            "check" => [
                "function" => "File",
                "params" => [
                    "filename" => "$sApproot/$sDir",
                    "dir" => true,
                    "readable" => true,
                ],
            ],
        ]
    );
}


foreach (['data/attic', 'data/cache', 'data/index', 'data/locks', 'data/log', 'data/media', 'data/meta', 'data/pages', 'data/tmp',] as $sDir) {
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
                    "directory" => "$sApproot/data",
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
