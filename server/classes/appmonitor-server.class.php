<?php
require_once 'cache.class.php';
require_once 'lang.class.php';
require_once 'notificationhandler.class.php';
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
 * 2018-06-22  0.15  axel.hahn@iml.unibe.ch   split server class<br>
 * 2018-06-21  0.14  axel.hahn@iml.unibe.ch   use multicurl with parrallel requests; fetch http header; added tiles<br>
 * 2017-06-20  0.12  axel.hahn@iml.unibe.ch   use POST instead of GET<br>
 * 2015-01-20  0.8   axel.hahn@iml.unibe.ch   fixed icons, nagios check<br>
 * 2014-11-27  0.7   axel.hahn@iml.unibe.ch   added icons, lang texts, ...<br>
 * 2014-11-21  0.6   axel.hahn@iml.unibe.ch   added setup functions<br>
 * 2014-10-24  0.5   axel.hahn@iml.unibe.ch<br>
 * --------------------------------------------------------------------------------<br>
 * @version 0.15
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
    var $_iTtlOnError = 20;
    var $_sConfigfile = "appmonitor-server-config.json";
    
    protected $_aMessages = array();
    protected $oLang = false;

    protected $_bIsDemo = false; // set true to disallow changing config in webgui
    private static $curl_opts = array(
        CURLOPT_HEADER => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_FAILONERROR => 0,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
            // CURLMOPT_MAXCONNECTS => 10
    );
    
    protected $_aCounter=false;

    /**
     * constructor
     */
    public function __construct() {
        $this->loadConfig();
        $this->_loadLangTexts();
        $this->_handleParams();
    }

    // ----------------------------------------------------------------------
    // private functions
    // ----------------------------------------------------------------------

    /**
     * return config dir ... it is one dir up and "config"
     * @return type
     */
    protected function _getConfigDir() {
        return dirname(__DIR__) . '/config';
    }

    /**
     * load language texts
     */
    protected function _loadLangTexts() {
        return $this->oLang = new lang($this->_aCfg['lang']);
    }

    /**
     * (re) load config and get all urls to fetch
     */
    public function loadConfig() {
        $aUserdata=array();
        $aDefaults=array();
        $this->_urls = array();
        
        $this->_aCfg=array(); // reset current config array
        
        $sCfgFile = $this->_getConfigDir() . '/' . $this->_sConfigfile;
        $sCfgDefaultsFile = str_replace('.json', '-defaults.json', $sCfgFile);
        if (!file_exists($sCfgDefaultsFile)) {
            die("ERROR: default config file is not readable: [$sCfgDefaultsFile].");
        } 

        $aDefaults=json_decode(file_get_contents($sCfgDefaultsFile), true);
        if (file_exists($sCfgFile)) {
            $aUserdata=json_decode(file_get_contents($sCfgFile), true);
        }
        $this->_aCfg = array_merge($aDefaults, $aUserdata);

        if (is_array($this->_aCfg) && array_key_exists("urls", $this->_aCfg)) {
            // add urls
            foreach ($this->_aCfg["urls"] as $sUrl) {
                $this->addUrl($sUrl);
            }
        }
        $this->oNotifcation=new notificationhandler(array(
            'lang' => $this->_aCfg['lang'],
            'serverurl' => $this->_aCfg['serverurl'],
            'notifications' => $this->_aCfg['notifications']
        ));
    }

    /**
     * save the current config
     * @return type
     */
    protected function _saveConfig() {
        if ($this->_bIsDemo) {
            $this->_addLog($this->_tr('msgErr-demosite'), "error");
            return false;
        }
        $sCfgFile = $this->_getConfigDir() . '/' . $this->_sConfigfile;

        // JSON_PRETTY_PRINT reqires PHP 5.4
        $sData = (defined('JSON_PRETTY_PRINT')) ? $sData = json_encode($this->_aCfg, JSON_PRETTY_PRINT) : $sData = json_encode($this->_aCfg);

        /*
        if (file_exists($sCfgFile)) {
            copy($sCfgFile, $sCfgFile . ".bak");
        }
         */

        return file_put_contents($sCfgFile, $sData);
    }

    /**
     * add a logging message to display in web gui in a message box
     * 
     * @param type $sMessage
     * @param type $sLevel
     * @return boolean
     */
    protected function _addLog($sMessage, $sLevel = "info") {
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
     * setup action: add a new url and save the config
     * @param string $sUrl
     * @param bool   $bPreviewOnly
     */
    protected function _actionAddUrl($sUrl, $bPreviewOnly = true) {
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
                    // $this->loadConfig();
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
    protected function _actionDeleteUrl($sUrl) {
        if ($sUrl) {
            if (($key = array_search($sUrl, $this->_aCfg["urls"])) !== false) {
                $sAppId=$this->_generateUrlKey($sUrl);
                $this->oNotifcation->deleteApp($sAppId);
                
                $oCache = new AhCache("appmonitor-server", $this->_generateUrlKey($sUrl));
                $oCache->delete();
                unset($this->_aCfg["urls"][$key]);
                $this->_saveConfig();
                $this->loadConfig();
                $this->_addLog(sprintf($this->_tr('msgOK-Url-was-removed'), $sUrl), "ok");
            } else {
                $this->_addLog(sprintf($this->_tr('msgErr-Url-not-removed-it-does-not-exist'), $sUrl), "error");
            }
        }
    }

    /**
     * helper function: handle url parameters
     */
    protected function _handleParams() {
        // echo "<br><br><br><br><br><br>POST: " . print_r($_POST, true); //print_r($_SERVER,true);
        $sAction = (array_key_exists("action", $_POST)) ? $_POST["action"] : '';
        switch ($sAction) {
            case "addurl":
                $this->_actionAddUrl($_POST["url"]);

                break;

            case "deleteurl":
                $this->_actionDeleteUrl($_POST["url"]);

                break;
            default:
                break;
        }
    }

    /**
     * generate array with http status values from a string
     * 
     * @param string $sHttpHeader
     * @return array
     */
    protected function _getHttpStatusArray($sHttpHeader){
        if(!$sHttpHeader){
            return false;
        }
        $aHeader=array();
        foreach(explode("\r\n",$sHttpHeader) as $sLine){
            preg_match_all('#^(.*)\:(.*)$#U', $sLine, $aMatches);
            $sKey=isset($aMatches[1][0]) ? $aMatches[1][0] : '_status';
            $sValue=isset($aMatches[2][0]) ? $aMatches[2][0] : $sLine;
            $aHeader[$sKey]=$sValue;
            if($sKey==='_status'){
                preg_match_all('#HTTP.*([0-9][0-9][0-9])#', $sValue, $aMatches);
                $aHeader['_statuscode']=isset($aMatches[1][0]) ? $aMatches[1][0] : false;
            }
        }
        return $aHeader;
    }
    
    protected function _getHttpStatus($sHttpHeader){
        $aHeader=$this->_getHttpStatusArray($sHttpHeader);
        return isset($aHeader['_statuscode']) ? $aHeader['_statuscode'] : false;
    }
    
    /**
     * helper function for multi_curl_exec
     * hint from kempo19b
     * http://php.net/manual/en/function.curl-multi-select.php
     * 
     * @param handle  $mh             multicurl master handle
     * @param boolean $still_running  
     * @return type
     */
    protected function full_curl_multi_exec($mh, &$still_running) {
        do {
            $rv = curl_multi_exec($mh, $still_running);
        } while ($rv == CURLM_CALL_MULTI_PERFORM);
        return $rv;
    }

    protected function _multipleHttpGet($aUrls) {
        $aResult = array();

        // prepare curl object
        $master = curl_multi_init();

        // requires php>=5.5:
        if (function_exists('curl_multi_setopt')) {
            // force parallel requests
            curl_multi_setopt($master, CURLMOPT_PIPELINING, 0);
            // curl_multi_setopt($master, CURLMOPT_MAXCONNECTS, 50);
        }

        $curl_arr = array();
        foreach ($aUrls as $sKey => $sUrl) {
            $curl_arr[$sKey] = curl_init($sUrl);
            curl_setopt_array($curl_arr[$sKey], self::$curl_opts);
            /*
              if (array_key_exists('userpwd', $aData)) {
              curl_setopt($curl_arr[$i], CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
              curl_setopt($curl_arr[$i], CURLOPT_USERPWD, $aData['userpwd']);
              }
             */
            curl_multi_add_handle($master, $curl_arr[$sKey]);
        }

        // make all requests
        self::full_curl_multi_exec($master, $running);
        do {
            curl_multi_select($master);
            self::full_curl_multi_exec($master, $running);
            while ($info = curl_multi_info_read($master)) {
                
            }
        } while ($running);

        // get results
        foreach ($aUrls as $sKey => $sUrl) {
            $aTmp = explode("\r\n\r\n", curl_multi_getcontent($curl_arr[$sKey]), 2);
            // echo $sResponse . '<pre>'.print_r($aTmp, 1).'</pre>'; die();
            $aResult[$sKey] = array(
                'url' => $sUrl,
                'response_header' => $aTmp[0],
                'response_body' => count($aTmp) > 1 ? $aTmp[1] : false,
            );
            curl_multi_remove_handle($master, $curl_arr[$sKey]);
        }
        curl_multi_close($master);

        return $aResult;
    }

    /**
     * make an http get request and return the response body
     * @param string $url
     * @return string
     */
    protected function _httpGet($url, $iTimeout = 5) {
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
    protected function _generateResultArray($aClientData) {
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
    protected function _getClientData() {
        $this->_data = array();
        $aUrls = array();
        foreach ($this->_urls as $sKey => $sUrl) {
            $oCache = new AhCache("appmonitor-server", $this->_generateUrlKey($sUrl));
            if ($oCache->isExpired()) {
                // Cache does not exist or is expired
                $aUrls[$sKey] = $sUrl;
            } else {
                // age is bel['result']['error']ow ttl ... read from Cache 
                $this->_data[$sKey] = $oCache->read();
                $this->_data[$sKey]["result"]["fromcache"] = true;
            }
        }
        // fetch all non cached items
        if (count($aUrls)) {
            $aAllHttpdata = $this->_multipleHttpGet($aUrls);
            foreach ($aAllHttpdata as $sKey => $aResult) {
                $aClientData = json_decode($aResult['response_body'], true);
                $iTtl = $this->_iTtl;
                if (!is_array($aClientData)) {
                    $iTtl = $this->_iTtlOnError;
                    $aClientData = array();
                } else {
                    if (
                            is_array($aClientData) && isset($aClientData["meta"]) && array_key_exists("ttl", $aClientData["meta"]) && $aClientData["meta"]["ttl"]
                    ) {
                        $iTtl = (int) $aClientData["meta"]["ttl"];
                    }
                }
                // detect error
                $iHttpStatus=$this->_getHttpStatus($aResult['response_header']);
                $sError=!$aResult['response_header'] ? $this->_tr('msgErr-Http-request-failed')
                            : ((!$iHttpStatus || $iHttpStatus<200 || $iHttpStatus>299) 
                                ? $this->_tr('msgErr-Http-error')
                                : (!count($aClientData) ? $this->_tr('msgErr-Http-no-jsondata') : false)
                                )
                            ;

                // add more metadata
                $aClientData["result"] = $this->_generateResultArray($aClientData);
                $aClientData["result"]["ttl"] = $iTtl;
                $aClientData["result"]["url"] = $aResult['url'];
                $aClientData["result"]["header"] = $aResult['response_header'];
                $aClientData["result"]["headerarray"] = $this->_getHttpStatusArray($aResult['response_header']);
                $aClientData["result"]["httpstatus"] = $iHttpStatus;
                $aClientData["result"]["error"] = $sError;
                
                
                // write cache
                $oCache = new AhCache("appmonitor-server", $this->_generateUrlKey($aResult['url']));
                $oCache->write($aClientData, $iTtl);

                $aClientData["result"]["fromcache"] = false;
                $this->_data[$sKey] = $aClientData;
                
                
                $this->oNotifcation->setApp($sKey);
                $this->oNotifcation->notify();
            }
        }
        return true;
    }

    /**
     * translate a text with language file
     * @param string $sWord
     * @return string
     */
    protected function _tr($sWord) {
        return $this->oLang->tr($sWord, array('gui'));
    }

    // ----------------------------------------------------------------------
    // setter
    // ----------------------------------------------------------------------

    protected function _generateUrlKey($sUrl){
        return md5($sUrl);
    }
    /**
     * add appmonitor url
     * @param string $sUrl
     * @return boolean
     */
    public function addUrl($sUrl) {
        $sKey=$this->_generateUrlKey($sUrl);
        $this->_urls[$sKey] = $sUrl;
        return true;
    }

    /**
     * remove appmonitor url
     * @param string $sUrl
     * @return boolean
     */
    public function removeUrl($sUrl) {
        $sKey=$this->_generateUrlKey($sUrl);
        if (array_key_exists($sKey, $this->_urls)) {
            unset($this->_urls[$sKey]);
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
    public function setDemoMode($bBool = true) {
        return $this->_bIsDemo = $bBool;
    }

    // ----------------------------------------------------------------------
    // output
    // ----------------------------------------------------------------------

    /**
     * get human readable time
     * @param int $iSec  seconds
     * @return string
     */
    protected function _hrTime($iSec) {
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

    
    protected function _getCounter() {
        $iCountApps=0;
        $iCountChecks=0;
        $aResults=array(0,0,0,0);
        $aCheckResults=array(0,0,0,0);
        $aServers=array();
        foreach ($this->_data as $sKey => $aEntries) {
            $iCountApps++;// count of webapps
            $aResults[$aEntries['result']['result']]++;// counter by result of app
            if (isset($aEntries['result']['host']) && $aEntries['result']['host']){
                $aServers[$aEntries['result']['host']]=true;// helper array to count hosts
            }

            // count of checks
            if(isset($this->_data[$sKey]["result"]["summary"])){
                $aChecks = $this->_data[$sKey]["result"]["summary"];
                $iCountChecks+=$aChecks["total"];
                for ($i=0; $i<4; $i++){
                    $aCheckResults[$i]+=$aChecks[$i];
                }
            }
        }
        return array(
            'apps'=>$iCountApps,
            'hosts'=>count($aServers),
            'appresults'=>$aResults,
            'checks'=>$iCountChecks,
            'checks'=>$iCountChecks,
            'checkresults'=>$aCheckResults
        );
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

        if (!count($this->_data) || true) {
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
                        $aResults[$key] += $value;
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

    
}
