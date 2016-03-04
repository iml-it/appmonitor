<?php

/**
 * APPMONITOR SERVER<br>
 * <br>
 * THERE IS NO WARRANTY FOR THE PROGRAM, TO THE EXTENT PERMITTED BY APPLICABLE <br>
 * LAW. EXCEPT WHEN OTHERWISE STATED IN WRITING THE COPYRIGHT HOLDERS AND/OR <br>
 * OTHER PARTIES PROVIDE THE PROGRAM ?AS IS? WITHOUT WARRANTY OF ANY KIND, <br>
 * EITHER EXPRESSED OR IMPLIED, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED <br>
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE. THE <br>
 * ENTIRE RISK AS TO THE QUALITY AND PERFORMANCE OF THE PROGRAM IS WITH YOU. <br>
 * SHOULD THE PROGRAM PROVE DEFECTIVE, YOU ASSUME THE COST OF ALL NECESSARY <br>
 * SERVICING, REPAIR OR CORRECTION.<br>
 * <br>
 * --------------------------------------------------------------------------------<br>
 * TODO:
 * - server job that caches all entries
 * - GUI uses cached data only
 * - NAGIOS output
 * --------------------------------------------------------------------------------<br>
 * <br>
 * --- HISTORY:<br>
 * 2015-01-20  0.8  axel.hahn@iml.unibe.ch   fixed icons, nagios check<br>
 * 2014-11-27  0.7  axel.hahn@iml.unibe.ch   added icons, lang texts, ...<br>
 * 2014-11-21  0.6  axel.hahn@iml.unibe.ch   added setup functions<br>
 * 2014-10-24  0.5  axel.hahn@iml.unibe.ch<br>
 * --------------------------------------------------------------------------------<br>
 * @version 0.09
 * @author Axel Hahn
 * @link TODO
 * @license GPL
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL 3.0
 * @package IML-Appmonitor
 */
class appmonitorserver {

    var $_data = array();
    var $_aCfg = array();
    var $_urls = array();
    var $_iTtl = 60;
    var $_iTtlOnError = 3;
    var $_sConfigfile = "appmonitor-server-config.json";
    var $_sProjectUrl = "https://github.com/iml-it/appmonitor";
    var $_sTitle = "Appmonitor Server GUI v0.11";
    protected $_aMessages = array();
    private $_aIco = array(
        'title' => '<i class="fa fa-th"></i>',
        'welcome' => '<i class="fa fa-flag-o" style="font-size: 500%;float: left; margin: 0 1em 10em 0;"></i>',
        'reload' => '<i class="fa fa-refresh"></i>',
        'webs' => '<i class="fa fa-globe"></i>',
        'checks' => '<i class="fa fa-navicon"></i>',
        'setup' => '<i class="fa fa-wrench"></i>',
        'debug' => '<i class="fa fa-bug"></i>',
        'ok' => '<i class="fa fa-check"></i>',
        'info' => '<i class="fa fa-info"></i>',
        'warning' => '<i class="fa fa-warning"></i>',
        'error' => '<i class="fa fa-flash"></i>',
        'back' => '<i class="fa fa-level-up"></i>',
        'add' => '',
        'del' => '',
        'close' => '',
    );
    private $_sLang = 'en-en'; // default language
    private $_aLang = array(); // language texts
    private $_bDebug = false;  // show debug tab?
    private $_bIsDemo = false; // set true to disallow changing config in webgui

    /**
     * constructor
     */
    public function __construct() {
        $this->_loadLangTexts();
        $this->_loadConfig();
        $this->_handleParams();
    }

    // ----------------------------------------------------------------------
    // private functions
    // ----------------------------------------------------------------------

    /**
     * return config dir ... it is one dir up and "config"
     * @return type
     */
    private function _getConfigDir() {
        return dirname(__DIR__) . '/config';
    }

    /**
     * load language texts
     */
    private function _loadLangTexts() {
        $sCfgFile = $this->_getConfigDir() . '/lang-' . $this->_sLang . '.json';
        if (!file_exists($sCfgFile)) {
            // die("no lang file " . $sCfgFile);
        } else {
            $this->_aLang = json_decode(file_get_contents($sCfgFile), true);
        }
    }

    /**
     * load config and get all urls to fetch
     */
    private function _loadConfig() {
        $sCfgFile = $this->_getConfigDir() . '/' . $this->_sConfigfile;
        if (!file_exists($sCfgFile)) {
            // die(sprintf($this->_aMsg['noconfig'], basename($sCfgFile), dirname($sCfgFile)));
        } else {
            $this->_urls = array();
            $this->_aCfg = json_decode(file_get_contents($sCfgFile), true);
            if (is_array($this->_aCfg) && array_key_exists("urls", $this->_aCfg)) {
                // add urls
                foreach ($this->_aCfg["urls"] as $sUrl) {
                    $this->addUrl($sUrl);
                }
            }
        }
    }

    /**
     * save the current config
     * @return type
     */
    private function _saveConfig() {
        if ($this->_bIsDemo) {
            $this->_addLog($this->_tr('msgErr-demosite'), "error");
            return false;
        }
        $sCfgFile = $this->_getConfigDir() . '/' . $this->_sConfigfile;

        // JSON_PRETTY_PRINT reqires PHP 5.4
        $sData = (defined('JSON_PRETTY_PRINT')) ?
                $sData = json_encode($this->_aCfg, JSON_PRETTY_PRINT) : $sData = json_encode($this->_aCfg);

        if (file_exists($sCfgFile)) {
            copy($sCfgFile, $sCfgFile . ".bak");
        }

        return file_put_contents($sCfgFile, $sData);
    }

    /**
     * add a logging message
     * @param type $sMessage
     * @param type $sLevel
     * @return boolean
     */
    private function _addLog($sMessage, $sLevel = "info") {
        $this->_aMessages[] = array(
            'time' => microtime(true),
            'message' => $sMessage,
            'level' => $sLevel
        );
        /*
          if ($sLevel=="MAIL"){
          mail($aCfg["emailDeveloper"], "Logmessage", $sMessage);
          }
         */
        return true;
    }

    /**
     * get all messages as html output
     * @return string
     */
    private function _renderLogs() {
        $sOut = '';
        if (count($this->_aMessages)) {
            foreach ($this->_aMessages as $aLogentry) {
                $sOut.='<div class="divlog' . $aLogentry["level"] . '">'
                        . $this->_aIco[$aLogentry["level"]] . ' '
                        . $aLogentry["message"]
                        . ' (' . $aLogentry["level"] . ')'
                        . '</div>';
            }
        }
        if ($sOut){
            $sOut='<div id="divmodal"><div class="divdialog">'
                    . $sOut
                    . '<br><a href="#" class="btn " onclick="reloadPage()">' . $this->_aIco["close"] . ' ' . $this->_tr('btn-close') . '</a><br><br>'
                    . '</a></div>';
        }
        return $sOut;
    }

    /**
     * setup action: add a new url and save the config
     * @param string $sUrl
     * @param bool   $bPreviewOnly
     */
    private function _actionAddUrl($sUrl, $bPreviewOnly = true) {
        if ($sUrl) {
            if (!array_key_exists("urls", $this->_aCfg) || ($key = array_search($sUrl, $this->_aCfg["urls"])) === false) {

                $bAdd = true;
                if ($bPreviewOnly) {
                    $aClientData = json_decode($this->_httpGet($sUrl), true);
                    if (!is_array($aClientData)) {
                        $bAdd = false;
                        $this->_addLog(sprintf($this->_tr('msgErr-Url-not-added-no-appmonitor'), $sUrl), 'error');
                    }
                }
                if ($bAdd) {
                    // TODO: translate
                    $this->_addLog("URL was added: " . $sUrl, "ok");
                    $this->_aCfg["urls"][] = $sUrl;
                    $this->_saveConfig();
                    $this->_loadConfig();
                }
            } else {
                $this->_addLog(sprintf($this->_tr('msgErr-Url-was-added-already'), $sUrl));
            }
        }
    }

    /**
     * delete an url to fetch and trigger to save the new config file
     * @param type $sUrl
     */
    private function _actionDeleteUrl($sUrl) {
        if ($sUrl) {
            if (($key = array_search($sUrl, $this->_aCfg["urls"])) !== false) {
                unset($this->_aCfg["urls"][$key]);
                $this->_saveConfig();
                require_once 'cache.class.php';
                $oCache = new AhCache("appmonitor-server", $sUrl);
                $oCache->delete();
                $this->_loadConfig();
                $this->_addLog(sprintf($this->_tr('msgOK-Url-was-removed'), $sUrl), "ok");
            } else {
                $this->_addLog(sprintf($this->_tr('msgErr-Url-not-removed-it-does-not-exist'), $sUrl), "error");
            }
        }
    }

    /**
     * helper function: handle url parameters
     */
    private function _handleParams() {
        // echo "<br><br><br><br><br><br>" . print_r($_POST, true); //print_r($_SERVER,true);
        $sAction = (array_key_exists("action", $_GET)) ? $_GET["action"] : '';
        switch ($sAction) {
            case "addurl":
                $this->_actionAddUrl($_GET["url"]);

                break;

            case "deleteurl":
                $this->_actionDeleteUrl($_GET["url"]);

                break;
            default:
                break;
        }
    }

    /**
     * make an http get request and return the response body
     * @param string $url
     * @return string
     */
    private function _httpGet($url, $iTimeout = 5) {
        if (!function_exists("curl_init")) {
            return file_get_contents($sUrl);
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, $iTimeout);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

    /**
     * helpfer function: get client data from meta and generate a key
     * "result" with whole summary
     * @param type $aClientdata
     */
    private function _generateResultArray($aClientData) {
        $aReturn = array();
        $aReturn["ts"] = date("U");
        $aReturn["result"] = 3; // set error as default

        if (!array_key_exists("meta", $aClientData)) {
            return $aReturn;
        }
        foreach (array("host", "website", "result") as $sField) {
            $aReturn[$sField] = array_key_exists($sField, $aClientData["meta"]) ? $aClientData["meta"][$sField] : false;
        }

        // returncodes
        $aResults = array(
            'total' => 0,
            0 => 0,
            1 => 0,
            2 => 0,
            3 => 0,
        );
        if (array_key_exists("checks", $aClientData) && count($aClientData["checks"])) {
            $aResults["total"] = count($aClientData["checks"]);
            foreach ($aClientData["checks"] as $aCheck) {
                $iResult = $aCheck["result"];
                $aResults[$iResult] ++;
            }
        }
        $aReturn["summary"] = $aResults;
        return $aReturn;
    }

    /**
     * get all client data; it fetches all given urls
     * @return boolean
     */
    private function _getClientData() {
        require_once 'cache.class.php';

        $this->_data = array();
        foreach ($this->_urls as $sKey => $sUrl) {
            $oCache = new AhCache("appmonitor-server", $sUrl);
            if ($oCache->isExpired()) {
                // Cache existiert nicht oder ist veraltet 
                $aClientData = json_decode($this->_httpGet($sUrl), true);
                $iTtl = $this->_iTtl;
                if (!is_array($aClientData)) {
                    $iTtl = $this->_iTtlOnError;
                    $aClientData = array();
                } else {
                    if (
                            is_array($aClientData) && array_key_exists("ttl", $aClientData["meta"]) && $aClientData["meta"]["ttl"]
                    ) {
                        $iTtl = (int) $aClientData["meta"]["ttl"];
                    }
                }
                // fix missing values
                $aClientData["result"] = $this->_generateResultArray($aClientData);
                $aClientData["result"]["ttl"] = $iTtl;
                $aClientData["result"]["url"] = $sUrl;

                // write cache
                $oCache->write($aClientData, $iTtl);
                $aClientData["result"]["fromcache"] = false;
            } else {
                // age is below ttl ... read from Cache 
                $aClientData = $oCache->read();
                $aClientData["result"]["fromcache"] = true;
            }
            $this->_data[$sKey] = $aClientData;
        }
        return true;
    }

    /**
     * translate a text with language file
     * @param string $sWord
     * @return string
     */
    private function _tr($sWord) {
        return (array_key_exists($sWord, $this->_aLang)) ? $this->_aLang[$sWord] : $sWord . ' (undefined in ' . $this->_sLang . ')';
    }

    // ----------------------------------------------------------------------
    // setter
    // ----------------------------------------------------------------------

    /**
     * add appmonitor url
     * @param string $sUrl
     * @return boolean
     */
    public function addUrl($sUrl) {
        $this->_urls[md5($sUrl)] = $sUrl;
        // $this->_urls[$sUrl] = $sUrl;
        return true;
    }
    
    /**
     * remove appmonitor url
     * @param string $sUrl
     * @return boolean
     */
    public function removeUrl($sUrl) {
        if (array_key_exists(md5($sUrl), $this->_urls)) {
            unset($this->_urls[md5($sUrl)]);
            return true;
        }
        return false;
    }

    /**
     * switch demo mode on off
     * TODO: check how switch demo mode and handle parameters
     * @param type $bBool
     * @return type
     */
    public function setDemoMode($bBool=true) {
        return $this->_bIsDemo=$bBool;
    }

    // ----------------------------------------------------------------------
    // output
    // ----------------------------------------------------------------------

    /**
     * get human readable time
     * @param int $iSec  seconds
     * @return string
     */
    private function _hrTime($iSec) {
        $sReturn = '';
        $sReturn = $iSec . " sec";
        if ($iSec > 60) {
            $sReturn = round($iSec / 60) . " min";
        }
        if ($iSec > 60 * 60 * 2) {
            $sReturn = round($iSec / (60 * 60)) . " h";
        }
        if ($iSec > 60 * 60 * 24 * 2) {
            $sReturn = round($iSec / (60 * 60 * 24)) . " d";
        }
        return ' (' . $sReturn . ' ago)';
    }

    /**
     * helper: generate html code for table header
     * @param array  $aHeaditems  items in header colums
     * @return string
     */
    private function _generateTableHead($aHeaditems) {
        $sReturn = '';
        foreach ($aHeaditems as $sKey) {
            $sReturn.='<th>' . $sKey . '</th>';
        }
        return '<thead><tr>' . $sReturn . '</tr></thead>';
    }

    /**
     * helper: generate list of websites with colored boxes based on site status
     * @return string
     */
    private function _generateWeblist() {
        $sReturn = '';
        $iMiss = 0;
        if (!count($this->_data)) {
            return '<div class="divok">' . $this->_aIco["welcome"] . ' ' . sprintf($this->_tr('msgErr-nocheck-welcome'), $this->_getConfigDir() . '/' . $this->_sConfigfile) . '</div>';
        }
        foreach ($this->_data as $sKey => $aEntries) {
            if (array_key_exists("result", $aEntries) && array_key_exists("result", $aEntries["result"]) && array_key_exists("website", $aEntries["result"]) && array_key_exists("host", $aEntries["result"])
            ) {
                $sReturn.='<div class="divhost result' . $aEntries["result"]["result"] . '" '
                        . 'onclick="window.location.hash=\'#divweb' . $sKey . '\'; showDiv( \'#divweb' . $sKey . '\' )">'
                        . '<a href="#divweb' . $sKey . '">' . $aEntries["result"]["website"] . '</a><br>'
                        . $aEntries["result"]["host"] . '<br>'
                        . $this->_renderBadgesForWebsite($sKey, true)
                        . '</div>';
            } else {
                $iMiss++;
            }
        }
        if (!$sReturn) {
            return '<div class="diverror">' . $this->_aIco["error"] . ' ' . sprintf($this->_tr('msgErr-nodata'), $this->_getConfigDir() . '/' . $this->_sConfigfile) . '</div>';
        }
        if ($iMiss > 0) {
            $sReturn = '<div class="diverror">' . $this->_aIco["error"] . ' ' . sprintf($this->_tr('msgErr-missedchecks'), $iMiss) . '</div>' . $sReturn;
        }
        return $sReturn . '<div style="clear;"><br><br></div>';
    }

    /**
     * helper: generate html code with all checks.
     * if a hast is given it renders the data for this host only
     * @param  string  $sHost  optional hostname (as filter); default: all hosts
     * @return string
     */
    private function _generateMonitorTable($sHost = false) {
        $sReturn = '';
        if (!count($this->_data)) {
            return '<div class="divok">' . $this->_aIco["welcome"] . ' ' . sprintf($this->_tr('msgErr-nocheck-welcome'), $this->_getConfigDir() . '/' . $this->_sConfigfile) . '</div>';
        }

        $sTableClass = $sHost ? "datatablehost" : "datatable";
        $sReturn.=$sHost ? $this->_generateTableHead(array(
                    $this->_tr('Timestamp'),
                    $this->_tr('TTL'),
                    $this->_tr('Check'),
                    $this->_tr('Description'),
                    $this->_tr('Result'),
                    $this->_tr('Output'),
                )) : $this->_generateTableHead(array(
                    $this->_tr('Host'),
                    $this->_tr('Website'),
                    $this->_tr('Timestamp'),
                    $this->_tr('TTL'),
                    $this->_tr('Check'),
                    $this->_tr('Description'),
                    $this->_tr('Result'),
                    $this->_tr('Output'),
        ));
        $sReturn.='<tbody>';

        foreach ($this->_data as $sKey => $aEntries) {

            // filter if a host was given
            if (!$sHost ||
                    (
                    array_key_exists("result", $aEntries) && array_key_exists("host", $aEntries["result"]) && $sHost == $aEntries["result"]["host"]
                    )
            ) {

                if (
                        !array_key_exists("result", $aEntries)
                        /*
                          || !array_key_exists("host", $aEntries["meta"])
                          || !array_key_exists("host", $aEntries["website"])
                         * 
                         */ || !array_key_exists("checks", $aEntries) || !count($aEntries["checks"])
                ) {
                    $sReturn.='<tr class="result3">'
                            . '<td>?</td>'
                            . '<td>?</td>'
                            . '<td>' . date("Y-m-d H:i:s", $aEntries["result"]["ts"]) . ' (' . (date("U") - $aEntries["result"]["ts"]) . '&nbsp;s)</td>'
                            . '<td>' . $aEntries["result"]["ttl"] . '</td>'
                            . '<td>' . $aEntries["result"]["url"] . '</td>'
                            . '<td>?</td>'
                            . '<td>?</td>'
                            . '<td>' . $this->_tr('msgErr-Http-request-failed') . '</td>'
                            . '</tr>';
                } else {
                    foreach ($aEntries["checks"] as $aCheck) {
                        $sReturn.='<tr class="result' . $aCheck["result"] . '">';
                        if (!$sHost) {
                            $sReturn.='<td>' . $aEntries["result"]["host"] . '</td>'
                                    . '<td>' . $aEntries["result"]["website"] . '</td>';
                        }
                        $sReturn.=
                                // . '<td>' . date("H:i:s", $aEntries["meta"]["ts"]) . ' ' . $this->_hrTime(date("U") - $aEntries["meta"]["ts"]) . '</td>'
                                '<td>' . date("Y-m-d H:i:s", $aEntries["result"]["ts"]) . ' (' . (date("U") - $aEntries["result"]["ts"]) . '&nbsp;s)</td>'
                                . '<td>' . $aEntries["result"]["ttl"] . '</td>'
                                . '<td>' . $aCheck["name"] . '</td>'
                                . '<td>' . $aCheck["description"] . '</td>'
                                . '<td>' . $aCheck["result"] . '</td>'
                                . '<td>' . $aCheck["value"] . '</td>'
                                . '</tr>';
                    }
                }
            }
        }
        $sReturn.='</tbody>';
        return '<table class="' . $sTableClass . '">' . $sReturn . '</table>';
    }

    /**
     * get html code for setup page
     * @return string
     */
    private function _generateSetup() {
        $sReturn = '';
        $sFormOpenTag = '<form action="?" method="GET">';
        $sReturn .='<h3>' . $this->_tr('Setup-client-list') . '</h3>';
        foreach ($this->_data as $sKey => $aData) {
            $iResult = array_key_exists("result", $aData["result"]) ? $aData["result"]["result"] : 3;
            $sWebsite = array_key_exists("website", $aData["result"]) ? $aData["result"]["website"] : '-';
            $sHost = array_key_exists("host", $aData["result"]) ? $aData["result"]["host"] : '-';
            $sUrl = $aData["result"]["url"];

            $sReturn.='<div class="divhost result' . $iResult . '" style="float: none; ">'
                    . '<div style="float: right">'
                    . $sFormOpenTag
                    . '<input type="hidden" name="action" value="deleteurl">'
                    . '<input type="hidden" name="url" value="' . $sUrl . '">'
                    . '<input type="submit" class="btn btndel" '
                    . 'onclick="return confirm(\'' . sprintf($this->_tr('btn-deleteUrl-confirm'), $sUrl) . '\')" '
                    . 'value="' . $this->_tr('btn-deleteUrl') . '">'
                    //. '<a href="#" class="btn btndel"><i class="fa fa-minus"></i> delete</a>'
                    . '</form>'
                    . '</div>'
                    . ' ' . $this->_aIco['webs'] . ' ' . $this->_tr('Website') . ' '
                    . $sWebsite
                    . ' | ' . $this->_tr('Host') . ' '
                    . $sHost
                    . ' | ' . $this->_tr('Url') . ' '
                    . '<a href="' . $sUrl . '" target="_blank">'
                    . $sUrl
                    . '</a>'
                    . '</div>';
        }
        $sReturn .='<br><br><h3>' . $this->_tr('Setup-add-client') . '</h3>';
        $sReturn.='<p>' . $this->_tr('Setup-add-client-pretext') . '</p>'
                . $sFormOpenTag
                . '<input type="hidden" name="action" value="addurl">'
                . '<input type="text" class="inputtext" name="url" size="70" value="" '
                . 'placeholder="http://[domain]/appmonitor/client/" '
                . 'pattern="http.*://..*" '
                . 'required="required" '
                . '>'
                // . '<a href="?#" class="btn btnadd" onclick="this.parentNode.submit(); return false;"><i class="fa fa-plus"></i> add</a>'
                . '<input type="submit" class="btn btnadd" value="' . $this->_tr('btn-addUrl') . '">'
                . '</form><br>';
        return $sReturn;
    }

    /**
     * gt html code for badged list with errors, warnings, unknown, ok
     * @param string $sKey    key of the check (hashed url)
     * @param bool   $bShort  display type short (counter only) or long (with texts)
     * @return string|boolean
     */
    private function _renderBadgesForWebsite($sKey, $bShort = false) {
        $iResult = $this->_data[$sKey]["result"]["result"];
        if (!array_key_exists("summary", $this->_data[$sKey]["result"])) {
            return false;
        }
        $aEntries = $this->_data[$sKey]["result"]["summary"];
        $sHtml = $this->_tr('Result-checks') . ': <strong>' . $aEntries["total"] . '</strong> ';
        for ($i = 3; $i >= 0; $i--) {
            $sKey = $i;
            if ($aEntries[$sKey] > 0) {
                $sHtml.=' <span class="badge result' . $i . '" title="' . $aEntries[$sKey] . ' x ' . $this->getResultValue($i) . '">' . $aEntries[$sKey] . '</span> ';
                if (!$bShort) {
                    $sHtml.=$this->_tr('Resulttype-' . $i) . ' ';
                }
            }
        }
        return $sHtml;
    }

    /**
     * get all client data and final result as array
     * @param   string  $sHost  filter by given hostname
     * @return  array
     */
    public function getMonitoringData($sHost = false) {

        $aReturn = array();
        $iMaxReturn = 0;
        $aMessages = array();
        $aResults = array();

        if (!count($this->_data)) {
            $this->_getClientData();
        }

        // print_r($this->_data);

        if (!count($this->_data)) {
            return array(
                'return' => 3,
                'messages' => array($this->_tr('msgErr-nocheck'))
            );
        }
        foreach ($this->_data as $sKey => $aEntries) {

            // filter if a host was given
            if (!$sHost ||
                    (
                    array_key_exists("result", $aEntries) && array_key_exists("host", $aEntries["result"]) && $sHost == $aEntries["result"]["host"]
                    )
            ) {

                if (
                        !array_key_exists("result", $aEntries)
                        /*
                          || !array_key_exists("host", $aEntries["meta"])
                          || !array_key_exists("host", $aEntries["website"])
                         * 
                         */ || !array_key_exists("checks", $aEntries) || !count($aEntries["checks"])
                ) {
                    if ($iMaxReturn < 3)
                        $iMaxReturn = 3;
                    $aMessages[] = $this->_tr('msgErr-Http-request-failed') . ' (' . $aEntries["result"]["url"] . ')';
                } else {
                    if ($iMaxReturn < $aEntries["result"]["result"])
                        $iMaxReturn = $aEntries["result"]["result"];
                    $aMessages[] = $aEntries["result"]["host"] . ': ' . $aEntries["result"]["result"];
                    foreach ($aEntries["result"]["summary"] as $key => $value) {
                        if (!array_key_exists($key, $aResults)) {
                            $aResults[$key] = 0;
                        }
                        $aResults[$key]+=$value;
                    }
                }
            }
        }
        return array(
            'return' => $iMaxReturn,
            'messages' => $aMessages,
            'results' => $aResults,
        );
    }

    /**
    * returns a readable result by given integer; i.e. 0=OK, 1=unknown, ...
    * @return string
    */
    public function getResultValue($i){
        return $this->_tr('Resulttype-' . $i);
    }
    
    /**
     * render html output of monitoring output (data only)
     * @return string
     */
    public function renderHtmlContent() {
        if (!count($this->_data)) {
            $this->_getClientData();
        }
        $sHtml = '';

        // ----- boxes with all websites
        $sId = 'divwebs';
        $sHtml.='<div class="outsegment" id="' . $sId . '">'
                . '<h2>' . $this->_aIco["webs"] . ' ' . $this->_tr('Webs-header') . '</h2>'
                . $this->_generateWeblist()
                . '</div>';

        // ----- one table per checked client
        foreach ($this->_data as $sKey => $aEntries) {
            $sId = 'divweb' . $sKey;
            if (array_key_exists("result", $aEntries) && array_key_exists("result", $aEntries["result"]) && array_key_exists("website", $aEntries["result"]) && array_key_exists("host", $aEntries["result"])
            ) {
                $sHtml.='<div class="outsegment" id="' . $sId . '">'
                        . '<h2>' . $this->_aIco["webs"] . ' ' . $aEntries["result"]["website"] . ' (on ' . $aEntries["result"]["host"] . ')</h2>'
                        . '<div class="divhost result' . $aEntries["result"]["result"] . '" style="float: none;">'
                        . '<a href="#divwebs" class="btn">' . $this->_aIco['back'] . ' ' . $this->_tr('btn-back') . '</a> '
                        . $this->_renderBadgesForWebsite($sKey)
                        . '</div><br>';
                if (array_key_exists("host", $aEntries["result"])) {
                    $sHtml.=$this->_generateMonitorTable($aEntries["result"]["host"]);
                }
                $sHtml.='</div>';
            }
        }

        // ----- table with all checks from all clients
        $sId = 'divall';
        $sHtml .= '<div class="outsegment" id="' . $sId . '">'
                . '<h2>' . $this->_aIco["checks"] . ' ' . $this->_tr('Checks-header') . '</h2>'
                . $this->_generateMonitorTable()
                . '</div>';


        // ----- settings page
        $sId = 'divsetup';
        $sHtml.='<div class="outsegment" id="' . $sId . '">'
                . '<h2>' . $this->_aIco["setup"] . ' ' . $this->_tr('Setup') . '</h2>'
                . $this->_generateSetup()
                . '</div>';

        // ----- debug tab
        if ($this->_bDebug) {
            $sId = 'divdebug';
            $sHtml.='<div class="outsegment" id="' . $sId . '">'
                    . '<h2>' . $this->_aIco["debug"] . ' ' . $this->_tr('Debug') . '</h2>'
                    . '<h3>' . $this->_tr('Debug-config') . '</h3>'
                    . '<pre>' . print_r($this->_aCfg, true) . '</pre>'
                    . '<h3>' . $this->_tr('Debug-urls') . '</h3>'
                    . '<pre>' . print_r($this->_urls, true) . '</pre>'
                    . '<h3>' . $this->_tr('Debug-clientdata') . '</h3>'
                    . '<pre>' . print_r($this->_data, true) . '</pre>'
                    . '</div>';
        }
        return $sHtml;
    }

    /**
     * render html output of monitoring output (whole page)
     * @return string
     */
    public function renderHtml() {
        $sHtml = $this->renderHtmlContent();
        $sNavi = '';
        $sTitle = $this->_sTitle;


        $sNavi.='<a href="#" class="reload" onclick="reloadPage()">' . $this->_aIco["reload"] . ' ' . $this->_tr('Reload') . '</a>';

        $sId = 'divwebs';
        $sFirstDiv = $sId;
        $sNavi.='<a href="#' . $sId . '" class="webs" >' . $this->_aIco["webs"] . ' ' . $this->_tr('Webs') . '</a>';

        $sId = 'divall';
        $sNavi.= '<a href="#' . $sId . '" class="checks" >' . $this->_aIco["checks"] . ' ' . $this->_tr('Checks') . '</a>';

        $sId = 'divsetup';
        $sNavi.='<a href="#' . $sId . '" class="setup" >' . $this->_aIco["setup"] . ' ' . $this->_tr('Setup') . '</a>';

        if ($this->_bDebug) {
            $sId = 'divdebug';
            $sNavi.='<a href="#' . $sId . '"  class="debug" >' . $this->_aIco["debug"] . ' ' . $this->_tr('Debug') . '</a>';
        }

        $sHtml = '<!DOCTYPE html>' . "\n"
                . '<html>' . "\n"
                . '<head>' . "\n"
                . '<title>' . $sTitle . '</title>'
                . '<script type="text/javascript" src="datatables/media/js/jquery.js"></script>' . "\n"
                . '<script type="text/javascript" src="datatables/media/js/jquery.dataTables.min.js"></script>' . "\n"
                . '<script type="text/javascript" src="javascript/functions.js"></script>' . "\n"
                . '<link href="datatables/media/css/jquery.dataTables.min.css" rel="stylesheet"/>'
                . '<link href="themes/default.css" rel="stylesheet"/>'
                . '</head>' . "\n"
                . '<body>' . "\n"
                . '<div class="divtop">'
                . '<div class="divtopheader">'
                . '<span style="float: right">'.sprintf($this->_tr('generated-at'), date("Y-m-d H:i:s")) . '</span>'
                . '<h1>' . $this->_aIco['title'] . ' ' . $sTitle . '</h1>'
                . '<br>'
                . '</div>'
                . '<div class="divtopnavi">'
                . $sNavi
                . '</div>'
                . '</div>'
                . '<div class="divlog">' . $this->_renderLogs() . '</div>'
                . '<div class="divmain">'
                . '' . $sHtml . "\n"
                . '</div>'
                . '<div class="footer"><a href="' . $this->_sProjectUrl . '" target="_blank">' . $this->_sProjectUrl . '</a></div>'
                . '<script>'
                . '$(document).ready(function() {'
                . ' $(\'.datatable\').dataTable( { "order": [[ 6, "desc" ]] } ); '
                . ' $(\'.datatablehost\').dataTable( { "order": [[ 4, "desc" ]] } ); '
                . 'if (document.location.hash) {'
                . ' showDiv( document.location.hash ) ; '
                . '} else {'
                . ' showDiv( "#' . $sFirstDiv . '" ) ; '
                . '}'
                . '$("a[href*=#]").click(function() { showDiv( this.hash ) } ); '
                . '/* window.setTimeout("updateContent()", 5000); */'
                . '} );'
                . '</script>' . "\n"
                . '</body></html>';

        return $sHtml;
    }

}