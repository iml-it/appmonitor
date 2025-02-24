<?php
// ----------------------------------------------------------------------
// since 0.139 (11-2024)
// config with urls was moved to an extra file
// included by ../appmonitor-server-upgrade.php

title("Check url config");
// migration for old way to load urls
if (isset($this->_aCfg['urls']) && is_array($this->_aCfg['urls'])) {
    echo "Url configuration Needs to be converted...\n";
    foreach ($this->_aCfg["urls"] as $sUrl) {
        $this->addUrl($sUrl);
    }
    $this->saveUrls();
    unset($this->_aCfg['urls']);
    if($this->saveConfig()){
        ok();
    } else {
        fail();
    }
} else {
    skip("Url configuration was converted already.");
}
