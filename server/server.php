<?php

require_once('classes/appmonitor-server-gui.class.php');
$oMonitor = new appmonitorserver_gui();
echo $oMonitor->renderHtml();
