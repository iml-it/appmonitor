<?php

require __DIR__ . '/check-appmonitor-server.php';
$sJson=ob_get_contents();
ob_end_clean();
echo $oMonitor->renderHtmloutput($sJson);
