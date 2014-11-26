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
 * - fetch urls
 * - server job that caches all entries
 * - GUI uses cached data only
 * --------------------------------------------------------------------------------<br>
 * <br>
 * --- HISTORY:<br>
 * 2014-11-21  0.6  axel.hahn@iml.unibe.ch   added setup functions<br>
 * 2014-10-24  0.5  axel.hahn@iml.unibe.ch<br>
 * --------------------------------------------------------------------------------<br>
 * @version 0.6
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
    var $_sTitle = "Appmonitor Server GUI v0.6";
    protected $_aMessages = array();
    private $_aMsg = array(
        // 'noconfig' => 'ERROR: The config file does not exist.<hr>Maybe you just installed the server...<br>You need create the config file %s<br>Go into the directory %s<br>and copy the sample file.',
        'nocheck' => '<h3>Welcome to the Appmonitor Server Webgui!</h3><p>At the moment it looks very naked because no check was defined yet.<br>To add an url to check go to the <a href="#divsetup">Setup</a><br><br>Remark: You also can edit the config file %s.</p>',
        'nodata' => 'ERROR: Sorry, no response from any website. See Tab <a href="#divall">Overview</a> for details.',
        'missedchecks' => 'ERROR: <strong>%d</strong> check(s) failed. The view is incomplete. See Tab <a href="#divall">Overview</a> for details.',
    );
    
    private $_bDebug = false;
    

    /**
     * constructor
     */
    public function __construct() {
        $this->_loadConfig();
        $this->_handleParams();
    }

    // ----------------------------------------------------------------------
    // private functions
    // ----------------------------------------------------------------------

    /**
     * load config and get all urls to fetch
     */
    private function _loadConfig() {
        $sCfgFile = __DIR__ . '/' . $this->_sConfigfile;
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
        $sCfgFile = __DIR__ . '/' . $this->_sConfigfile;

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
                        . $aLogentry["message"]
                        . ' (' . $aLogentry["level"] . ')'
                        . '</div>';
            }
        }
        return $sOut;
    }

    /**
     * setup action: add a new url and save the config
     * @param type $sUrl
     */
    private function _actionAddUrl($sUrl, $bPreviewOnly = true) {
        if ($sUrl) {
            if (!array_key_exists("urls", $this->_aCfg) || ($key = array_search($sUrl, $this->_aCfg["urls"])) === false) {

                $bAdd = true;
                if ($bPreviewOnly) {
                    $aClientData = json_decode($this->_httpGet($sUrl), true);
                    if (!is_array($aClientData)) {
                        $bAdd = false;
                        $this->_addLog("URL not added: " . $sUrl . " - it does not seems to be a app monitor url.");
                    }
                }
                if ($bAdd) {
                    $this->_addLog("URL was added: " . $sUrl, "ok");
                    $this->_aCfg["urls"][] = $sUrl;
                    $this->_saveConfig();
                    $this->_loadConfig();
                }
            } else {
                $this->_addLog("Skip. URL was added already: " . $sUrl . ".");
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
                $this->_addLog("URL was removed: " . $sUrl, "ok");
                unset($this->_aCfg["urls"][$key]);
                $this->_saveConfig();
                require_once 'cache.class.php';
                $oCache = new AhCache("appmonitor-server", $sUrl);
                $oCache->delete();
                $this->_loadConfig();
            } else {
                $this->_addLog("URL cannot be removed - it doees not exist in the config: " . $sUrl);
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
        
        if (!array_key_exists("meta", $aClientData)){
            return $aReturn;
        }
        foreach(array("host", "website", "result") as $sField){
            $aReturn[$sField]=
                array_key_exists($sField, $aClientData["meta"])
                ?$aClientData["meta"][$sField]
                :false;
        }
        
        // returncodes
        $aResults=array(
            'total'=>0,
            'is_0'=>0,
            'is_1'=>0,
            'is_2'=>0,
            'is_3'=>0,
        );
        if (array_key_exists("checks",$aClientData) && count($aClientData["checks"])){
            $aResults["total"]=count($aClientData["checks"]);
            foreach($aClientData["checks"] as $aCheck){
                $iResult=$aCheck["result"];
                $sKey="is_${iResult}";
                $aResults[$sKey]++;
            }
        }
        $aReturn["summary"]=$aResults;
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
                // Cache-Daten lesen 
                $aClientData = $oCache->read();
                $aClientData["result"]["fromcache"] = true;
            }
            $this->_data[$sKey] = $aClientData;
        }
        return true;
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
            return '<div class="diverror">' . sprintf($this->_aMsg['nocheck'], __DIR__ . '/' . $this->_sConfigfile) . '</div>';
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
            return '<div class="diverror">' . sprintf($this->_aMsg['nodata'], __DIR__ . '/' . $this->_sConfigfile) . '</div>';
        }
        if ($iMiss > 0) {
            $sReturn = '<div class="diverror">' . sprintf($this->_aMsg['missedchecks'], $iMiss) . '</div>' . $sReturn;
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
            return '<div class="diverror">' . sprintf($this->_aMsg['nocheck'], __DIR__ . '/' . $this->_sConfigfile) . '</div>';
        }

        $sTableClass = $sHost ? "datatablehost" : "datatable";
        $sReturn.=$sHost ? $this->_generateTableHead(array("Timestamp", "TTL", "Check", "Description", "Result", "Output",)) : $this->_generateTableHead(array("Host", "Website", "Timestamp", "TTL", "Check", "Description", "Result", "Output",));
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
                            . '<td>Http Request to appmonitor failed.</td>'
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
        foreach ($this->_data as $sKey => $aData) {
            $iResult = array_key_exists("result", $aData["result"]) ? $aData["result"]["result"] : 3;
            $sWebsite = array_key_exists("website", $aData["result"]) ? $aData["result"]["website"] : '-';
            $sHost = array_key_exists("host", $aData["result"]) ? $aData["result"]["host"] : '-';
            $sUrl = $aData["result"]["url"];
            
            $sReturn.='<div class="divhost result' . $iResult . '" style="float: none;">'
                    . '<div style="float: right">'
                    . $sFormOpenTag
                    . '<input type="hidden" name="action" value="deleteurl">'
                    . '<input type="hidden" name="url" value="' . $sUrl . '">'
                    . '<input type="submit" class="btn btndel" '
                    . 'onclick="return confirm(\'Are you sure? You want to delete\n' . $sUrl . '?\')" '
                    . 'value=" delete ">'
                    . '</form>'
                    . '</div>'
                    . ' website '
                    . $sWebsite
                    . ' | on host '
                    . $sHost
                    . ' | url '
                    . '<a href="' . $sUrl . '" target="_blank">'
                    . $sUrl
                    . '</a>'
                    . '</div>';
        }
        $sReturn.='<br><br>Enter a appmonitor client url to add a new monitor:<br>'
                . $sFormOpenTag
                . '<input type="hidden" name="action" value="addurl">'
                . '<input type="text" class="inputtext" name="url" size="70" value="" '
                . 'placeholder="http://[domain]/appmonitor/client/" '
                . 'pattern="http.*://..*" '
                . 'required="required" '
                . '>'
                . '<input type="submit" class="btn btnadd" value=" add ">'
                . '</form><br>';
        return $sReturn;
    }

    /**
     * gt html code for badged list with errors, warnings, unknown, ok
     * @param string $sKey    key of the check (hashed url)
     * @param bool   $bShort  display type short (counter only) or long (with texts)
     * @return string|boolean
     */
    private function _renderBadgesForWebsite($sKey, $bShort=false){
        $iResult=$this->_data[$sKey]["result"]["result"];
        if (!array_key_exists("summary", $this->_data[$sKey]["result"])){
            return false;
        }
        $aEntries=$this->_data[$sKey]["result"]["summary"];
        $aResultTypes=array(
            0=>"ok",
            1=>"unknown",
            2=>"warning",
            3=>"error",
        );
        $sHtml='Checks <strong>'.$aEntries["total"].'</strong> ';
        for ($i=3; $i>=0; $i--){
            if ($aEntries["is_$i"]>0){
                $sHtml.=' <span class="badge result'.$i.'" title="'.$aEntries["is_$i"] .' x '. $aResultTypes[$i].'">'.$aEntries["is_$i"].'</span> ';
                if (!$bShort) {
                    $sHtml.=$aResultTypes[$i].' ';
                }
            }
        }
        return $sHtml;
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
                . '<h2>Monitor :: Webs</h2>'
                . $this->_generateWeblist()
                . '</div>';

        // ----- table with all checks from all clients
        $sId = 'divall';
        $sHtml .= '<div class="outsegment" id="' . $sId . '">'
                . '<h2>Overview with all Checks</h2>'
                . $this->_generateMonitorTable()
                . '</div>';

        // ----- one table per checked client
        foreach ($this->_data as $sKey => $aEntries) {
            $sId = 'divweb' . $sKey;
            if (array_key_exists("result", $aEntries) && array_key_exists("result", $aEntries["result"]) && array_key_exists("website", $aEntries["result"]) && array_key_exists("host", $aEntries["result"])
            ) {
                $sHtml.='<div class="outsegment" id="' . $sId . '">'
                        . '<h2>' . $aEntries["result"]["website"] . ' (on ' . $aEntries["result"]["host"] . ')</h2>'
                        . '<div class="divhost result' . $aEntries["result"]["result"] . '" style="float: none;">'
                            . '<a href="#divwebs" class="btn">back</a> '
                            . $this->_renderBadgesForWebsite($sKey)
                        . '</div><br>';
                        if (array_key_exists("host", $aEntries["result"])){
                            $sHtml.=$this->_generateMonitorTable($aEntries["result"]["host"]);
                        }
                $sHtml.='</div>';
            }
        }
        
        // ----- settings page
        $sId = 'divsetup';
        $sHtml.='<div class="outsegment" id="' . $sId . '">'
                . '<h2>Setup</h2>'
                . $this->_generateSetup()
                . '</div>';

        // ----- debug tab
        if ($this->_bDebug) {
            $sId = 'divdebug';
            $sHtml.='<div class="outsegment" id="' . $sId . '">'
                    . '<h2>Debug</h2>'
                    . '<h3>config</h3>'
                    . '<pre>' . print_r($this->_aCfg, true) . '</pre>'
                    . '<h3>urls</h3>'
                    . '<pre>' . print_r($this->_urls, true) . '</pre>'
                    . '<h3>client data</h3>'
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


        $sNavi.='<a href="#" class="reload" onclick="reloadPage()">Reload</a>';

        $sId = 'divwebs';
        $sFirstDiv = $sId;
        $sNavi.='<a href="#' . $sId . '" class="webs" >Webs</a>';

        $sId = 'divall';
        $sNavi.= '<a href="#' . $sId . '" class="checks" >Checks</a>';

        $sId = 'divsetup';
        $sNavi.='<a href="#' . $sId . '" class="setup" >Setup</a>';

        if ($this->_bDebug) {
            $sId = 'divdebug';
            $sNavi.='<a href="#' . $sId . '"  class="debug" >Debug</a>';
        }

        $sHtml = '<!DOCTYPE html>' . "\n"
                . '<html>' . "\n"
                . '<head>' . "\n"
                . '<title>' . $sTitle . '</title>'
                . '<script type="text/javascript" src="http://code.jquery.com/jquery-1.11.1.min.js"></script>' . "\n"
                . '<script type="text/javascript" src="http://cdn.datatables.net/1.10.2/js/jquery.dataTables.min.js"></script>' . "\n"
                . '<link href="http://cdn.datatables.net/1.10.2/css/jquery.dataTables.css" rel="stylesheet"/>'
                . '<link href="appmonitor-server.css" rel="stylesheet"/>'
                . '</head>' . "\n"
                . '<body>' . "\n"
                . '<div class="divtop">'
                . '<div class="divtopheader">'
                . '<h1>' . $sTitle . '</h1>'
                . 'generated at ' . date("Y-m-d H:i:s") . '<br>'
                . '</div>'
                . '<div class="divtopnavi">'
                . $sNavi
                . '</div>'
                . '</div>'
                . '<div class="divlog">' . $this->_renderLogs() . '</div>'
                . '<div class="divmain">'
                . '' . $sHtml . "\n"
                . '</div>'
                . '<div class="footer"><a href="https://github.com/iml-it/appmonitor" target="_blank">https://github.com/iml-it/appmonitor</a></div>'
                . '<script>'
                . 'function reloadPage(){'
                . ' if (window.location.search) { window.location.href = window.location.pathname+window.location.hash; } '
                . ' else { window.location.reload(); } '
                . '}'
                . 'function updateContent(){'
                . '$.ajax({
                    url: "?updatecontent",
                    context: document.body
                    }).done(function(data) {
                    $( ".divmain" ).html( data );
                    });'
                . '}'
                . 'function showDiv(sDiv, oLink ){'
                . '$(".outsegment").hide(); '
                . '$(sDiv).fadeIn(); '
                . '$(".divtopnavi a").removeClass("active");'
                . '$("a[href*="+sDiv+"]").addClass("active");'
                . '}'
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
