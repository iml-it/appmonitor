<?php
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/
require_once('classes/appmonitor-server-gui.class.php');
$oMonitor = new appmonitorserver_gui();
echo $oMonitor->renderHtml();
