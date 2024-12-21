<?php
/* ______________________________________________________________________
 * 
 * WORK IN PROGRESS
 * 
 * A P P M O N I T O R  ::  CLIENT - CHECK
 * ______________________________________________________________________
 * 
 * Check for a Wordpress instance.
 * Blogsoftware https://wordpress.org/
 * 
 * It checks 
 * - the write access to the config file
 * - connect to mysql database (which is read from config)
 * - ssl certificate (on https request only)
 * 
 * @author: <axel.hahn@unibe.ch>
 * ----------------------------------------------------------------------
 * 2018-11-07  v0.01
 * 2019-05-24  v0.02  detect include or standalone mode
 * 2019-05-24  v0.03  detect include or standalone mode
 * 2024-07-31  v0.04  first version for wordpress check in plugins/apps/ 
 * 2024-11-21  v0.05  use shared_check_sl 
 * 2024-11-22  v0.07  <axel.hahn@unibe.ch> send 400 instead of 503 on error
 * 2024-12-21  v0.08  ah                   add php-modules and parent
  */

// ----------------------------------------------------------------------
// Init
// ----------------------------------------------------------------------

 $aAppDefaults = [
    "name" => "Wordpress",
    "tags" => ["wordpress", "blog"],
];

require 'inc_appcheck_start.php';

// ----------------------------------------------------------------------
// Read config items
// ----------------------------------------------------------------------

$sConfigfile = $sApproot . '/wp-config.php';
if (!file_exists($sConfigfile)) {
    header('HTTP/1.0 400 Bad request');
    die('ERROR: Config file [wp-config.php] was not found. Set a correct app root pointing to wordpress install dir.');
}

require($sConfigfile);
$aDb=[
  'server'   => DB_HOST,
  'username' => DB_USER,
  'password' => DB_PASSWORD,
  'database' => DB_NAME,
  // 'port'     => ??,
]; 

// ----------------------------------------------------------------------
// checks
// ----------------------------------------------------------------------

// required php modules
// see https://ertano.com/required-php-modules-for-wordpress/
$oMonitor->addCheck(
    [
        "name" => "PHP modules",
        "description" => "Check needed PHP modules",
        // "group" => "folder",
        "check" => [
            "function" => "Phpmodules",
            "params" => [
                "required" => [
                    // "cmath",
                    "cli",
                    "curl", 
                    "date", 
                    "dom", 
                    "fileinfo", 
                    "filter", 
                    "gd", 
                    "gettext",
                    "hash", 
                    "iconv",
                    "imagick",
                    "json",
                    // "libsodium",
                    "mysql",
                    "openssl",
                    "pcre",
                    //"opcache",
                    // "readline",
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
        "description" => "The config file must be writable",
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
        "name" => "Mysql Connect",
        "description" => "Connect mysql server " . $aDb['server'] . " as user " . $aDb['username'] . " to scheme " . $aDb['database'],
        "parent" => "config file",
        "check" => [
            "function" => "MysqlConnect",
            "params" => [
                "server"   => $aDb['server'],
                "user"     => $aDb['username'],
                "password" => $aDb['password'],
                "db"       => $aDb['database'],
                // "port"     => $aDb['port'],
            ],
        ],
    ]
);

require 'inc_appcheck_end.php';

// ----------------------------------------------------------------------
