<?php

require __DIR__ . '/check-appmonitor-server.php';
$sJson=ob_get_contents();
ob_end_clean();
$oMonitor->renderHtmloutput($sJson);
