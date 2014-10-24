<?php

require_once('appmonitor-server.class.php');
$oMonitor = new appmonitorserver();
echo $oMonitor->renderHtml();
