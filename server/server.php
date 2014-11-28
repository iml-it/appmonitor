<?php

require_once('classes/appmonitor-server.class.php');
$oMonitor = new appmonitorserver();
echo $oMonitor->renderHtml();
