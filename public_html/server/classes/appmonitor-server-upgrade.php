<?php
/*

    INCLUDE FILE FOR APPMONITOR SERVER UPDATES
    see appmonitor-server.class.php - method loadConfig()

    This file is included into the clas only if an upgrade is required.

*/

if (!isset($this->_aCfg)) {
    die("ERROR: This file cannot be called directly.");
}

ignore_user_abort(true);
set_time_limit(0);

require_once 'dbobjects/simplerrd.php';

// ----------------------------------------------------------------------
// FUNCTIONS
// ----------------------------------------------------------------------

function title($sTitle)
{
    $bHtml = $_SERVER['HTTP_HOST'] ?? false;
    echo $bHtml
        ? "<strong>---------- $sTitle</strong>\n"
        : "---------- $sTitle\n";
}

function fail($sMessage = '')
{
    echo "\n❌ ERROR: $sMessage<br>Aborting here.\n";
    die();
}

function ok($sMessage = '')
{
    echo "✅ OK $sMessage\n\n";
}

function skip($sMessage = '')
{
    echo "🔹 SKIP $sMessage\n\n";
}

function delFiles(array $aFilelist)
{
    title("Delete obsolete files...");
    $sApproot = realpath(__DIR__ . "/../../..");
    foreach ($aFilelist as $sFile) {
        $sFullPath = "$sApproot/$sFile";
        if (file_exists($sFullPath)) {
            if (unlink($sFullPath)) {
                ok($sFile);
            } else {
                fail("Deletion of file $sFile failed.");
            }
        } else {
            skip("$sFile (does not exist anymore)");
        }
    }
}

// ----------------------------------------------------------------------
// MAIN
// ----------------------------------------------------------------------

if (isset($_SERVER['HTTP_HOST'])) {
    echo "<pre><h1>UPGRADE to $this->_sVersion</h1>";
} else {
    echo "\n\n>>>>>>>>>> UPGRADE to $this->_sVersion\n\n\n";
}

// echo "from [$sLastVersion] --> [$this->_sVersion]\n";

$aUpdaters = glob(__DIR__ . "/upgrades/*.php");
sort($aUpdaters);

foreach ($aUpdaters as $sUpgradefile) {
    $sUpgradeVersion = str_replace('.php', '', basename($sUpgradefile));
    if ($sUpgradeVersion > $sLastVersion && $sUpgradeVersion <= $this->_sVersion) {
        echo "<h2>Apply " . $sUpgradeVersion . "</h2>";
        include $sUpgradefile;
    } else {
        echo "SKIP " . $sUpgradeVersion . "\n";
    }
}

// ----------------------------------------------------------------------

title("UPGRADER is finished");
if (isset($_SERVER['HTTP_HOST'])) {
    echo "</pre>
    <h2>👉 Refresh the page in the webbrowser.</h2>
    ... or wait for 10 sec to reload automatically.<br>
    <script>
        window.setTimeout('document.location.reload()', 10000);
    </script>
    ";
    // die();
}

// ----------------------------------------------------------------------
