<?php

require_once 'cache.class.php';
require_once 'lang.class.php';
require_once 'counteritems.class.php';
require_once 'notificationhandler.class.php';

/**
 * ____________________________________________________________________________
 * 
 *  _____ _____ __                   _____         _ _           
 * |     |     |  |      ___ ___ ___|     |___ ___|_| |_ ___ ___ 
 * |-   -| | | |  |__   | .'| . | . | | | | . |   | |  _| . |  _|
 * |_____|_|_|_|_____|  |__,|  _|  _|_|_|_|___|_|_|_|_| |___|_|  
 *                          |_| |_|                              
 *                                                                                                                             
 *                       ___ ___ ___ _ _ ___ ___                                      
 *                      |_ -| -_|  _| | | -_|  _|                                     
 *                      |___|___|_|  \_/|___|_|                                       
 *                                                               
 * ____________________________________________________________________________
 * 
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
 * @version 0.127
 * @author Axel Hahn
 * @link https://github.com/iml-it/appmonitor
 * @license GPL
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL 3.0
 * @package IML-Appmonitor
 */
class appmonitorserver
{

    /**
     * key value hash with all clients to fetch appmon client status from
     * key is a hash; value the client url
     * @var array
     */
    protected $_urls = [];

    /**
     * hash with response data of all clients
     * @var array
     */
    protected $_data = [];

    /**
     * loaded config data
     * @var array
     */
    protected $_aCfg = [];

    /**
     * default TTL if a client does not send its own TTL value
     * value is in sec
     * @var integer
     */
    protected $_iTtl = 300;

    /**
     * default TTL if a client does not send its own TTL value
     * value is in sec
     * @var integer
     */
    protected $_iTtlOnError = 60;

    /**
     * name of the config file to load
     * @var type 
     */
    protected $_sConfigfile = "appmonitor-server-config.json";
    protected $_aMessages = [];
    /**
     * language texts object
     * @var object
     */
    protected $oLang = false;
    protected $_bIsDemo = false; // set true to disallow changing config in webgui
    protected $curl_opts = [
        CURLOPT_HEADER => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FAILONERROR => 0,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_USERAGENT => 'Appmonitor (using curl; see https://github.com/iml-it/appmonitor to install your own monitoring instance;)',
        // CURLMOPT_MAXCONNECTS => 10
    ];
    protected $_aCounter = false;

    /**
     * flag: show output on STDOUT 
     * @see send()
     * @var bool
     */
    protected $_bShowLog = false;

    /**
     * notificationhandler object to send email/ slack messages
     * it is initialized in method loadConfig()
     * @var object
     */
    var $oNotification = false;

    /**
     * detected user name to handle with roles
     * see config -> users -> USERNAME
     */
    protected $_user = '*';

    /**
     * constructor
     */
    public function __construct()
    {
        $this->loadConfig();
        $this->_loadLangTexts();
        $this->_handleParams();

        $_sUser = $this->getAlreadyAuthenticatedUser();
        $this->setUser($_sUser ? $_sUser : '*');
    }

    // ----------------------------------------------------------------------
    // protected functions
    // ----------------------------------------------------------------------

    /**
     * return config dir ... it is one dir up and "config"
     * @return type
     */
    protected function _getConfigDir()
    {
        return dirname(__DIR__) . '/config';
    }

    /**
     * load language texts
     */
    protected function _loadLangTexts()
    {
        return $this->oLang = new lang($this->_aCfg['lang']);
    }

    /**
     * get a flat array with all application ids and website + url
     * as subkeys
     * @return array
     */
    public function getAppIds()
    {
        $this->_getClientData(true);
        return array_keys($this->_data);
    }

    public function getConfigVars()
    {
        return $this->_aCfg;
    }

    /**
     * (re) load config and get all urls to fetch (and all other config items)
     * This method 
     * - fills $this->_aCfg
     * - newly initializes $this->oNotification
     */
    public function loadConfig()
    {
        $aUserdata = [];
        $aDefaults = [];
        $this->_urls = [];

        $this->_aCfg = []; // reset current config array

        $sCfgFile = $this->_getConfigDir() . '/' . $this->_sConfigfile;
        $sCfgDefaultsFile = str_replace('.json', '-defaults.json', $sCfgFile);
        if (!file_exists($sCfgDefaultsFile)) {
            die("ERROR: default config file is not readable: [$sCfgDefaultsFile].");
        }

        $aDefaults = json_decode(file_get_contents($sCfgDefaultsFile), true);
        if (file_exists($sCfgFile)) {
            $aUserdata = json_decode(file_get_contents($sCfgFile), true);
        }
        $this->_aCfg = array_replace_recursive($aDefaults, $aUserdata);

        // undo unwanted recursive merge behaviour:
        $this->_aCfg['users'] = $aUserdata['users'] ?? $aDefaults['users'];

        if (isset($this->_aCfg['urls']) && is_array($this->_aCfg['urls'])) {
            // add urls
            foreach ($this->_aCfg["urls"] as $sUrl) {
                $this->addUrl($sUrl);
            }
        }
        if (isset($this->_aCfg['curl']['timeout'])) {
            $this->curl_opts[CURLOPT_TIMEOUT] = (int)$this->_aCfg['curl']['timeout'];
        }

        $this->oNotification = new notificationhandler([
            'lang' => $this->_aCfg['lang'],
            'serverurl' => $this->_aCfg['serverurl'],
            'notifications' => $this->_aCfg['notifications']
        ]);
    }
    /**
     * load monitoring data ... if not done yet; used in gui and api
     * @return boolean
     */
    public function loadClientData()
    {
        if (!count($this->_data)) {
            $this->_getClientData();
        }
        return true;
    }


    /**
     * save the current config
     * @return boolean
     */
    public function saveConfig($aNewCfg = false)
    {
        if ($this->_bIsDemo) {
            $this->_addLog($this->_tr('msgErr-demosite'), "error");
            return false;
        }
        if ($aNewCfg && is_array($aNewCfg)) {
            $this->_aCfg = $aNewCfg;
        }
        $sCfgFile = $this->_getConfigDir() . '/' . $this->_sConfigfile;

        // JSON_PRETTY_PRINT reqires PHP 5.4
        $iOptions=defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : 0;
        $sData = json_encode($this->_aCfg, $iOptions);

        return file_put_contents($sCfgFile, $sData);
    }

    /**
     * add a logging message to display in web gui in a message box
     * 
     * @param type $sMessage
     * @param type $sLevel
     * @return boolean
     */
    protected function _addLog($sMessage, $sLevel = "info")
    {
        $this->_aMessages[] = [
            'time' => microtime(true),
            'message' => $sMessage,
            'level' => $sLevel
        ];
        return true;
    }

    /**
     * setup action: add a new url and save the config
     * @param string $sUrl
     * @param bool   $bMakeCheck
     */
    public  function actionAddUrl($sUrl, $bMakeCheck = true)
    {
        if ($sUrl) {
            if (!isset($this->_aCfg["urls"]) || ($key = array_search($sUrl, $this->_aCfg["urls"])) === false) {

                $bAdd = true;
                if ($bMakeCheck) {
                    $aHttpData = $this->_multipleHttpGet([ $sUrl ]);
                    $sBody = $aHttpData[0]['response_body'] ?? false;
                    if (!is_array(json_decode($sBody, 1))) {
                        $bAdd = false;
                        $this->_addLog(
                            sprintf(
                                $this->_tr('msgErr-Url-not-added-no-appmonitor'),
                                $sUrl,
                                (isset($aHttpData[0]['response_header']) ? '<pre>' . $aHttpData[0]['response_header'] . '</pre>' : '-')
                            ),
                            'error'
                        );
                    }
                }
                if ($bAdd) {
                    $this->_addLog(sprintf($this->_tr('msgOK-Url-was-added'), $sUrl), "ok");
                    $this->_aCfg["urls"][] = $sUrl;
                    $this->saveConfig();
                    // $this->loadConfig();
                    return true;
                }
            } else {
                $this->_addLog(sprintf($this->_tr('msgErr-Url-was-added-already'), $sUrl));
            }
        }
        return false;
    }

    /**
     * delete an url to fetch and trigger to save the new config file
     * @param type $sUrl
     */
    public function actionDeleteUrl($sUrl)
    {
        if ($sUrl) {
            if (($key = array_search($sUrl, $this->_aCfg["urls"])) !== false) {
                $sAppId = $this->_generateUrlKey($sUrl);

                // $this->oNotification->deleteApp($sAppId);
                // $oCache = new AhCache("appmonitor-server", $this->_generateUrlKey($sUrl));
                // $oCache->delete();
                unset($this->_aCfg["urls"][$key]);
                $this->saveConfig();
                $this->loadConfig();

                // delete notification after config was saved
                if (!$this->_aCfg["urls"][$key]){
                    $this->oNotification->deleteApp($sAppId);
                    $oCache = new AhCache("appmonitor-server", $this->_generateUrlKey($sUrl));
                    $oCache->delete();    
                    $this->_addLog(sprintf($this->_tr('msgOK-Url-was-removed'), $sUrl), "ok");
                } else {
                    $this->_addLog(sprintf($this->_tr('msgErr-Url-not-removed-save-config-failed'), $sUrl), "ok");
                }
                return true;
            } else {
                $this->_addLog(sprintf($this->_tr('msgErr-Url-not-removed-it-does-not-exist'), $sUrl), "error");
            }
        }
        return false;
    }

    /**
     * check a user in local config
     * It can skip password check to authenticate anywhere
    public function checkUser($sUser, $sPassword=false){
        $this->_user=false;
        if(!isset($this->_aCfg["users"][$sUser])){
            return [ 'error' => 'User does not exist.' ];
        }
        $aUser=$this->_aCfg["users"][$sUser];

        if ($sPassword){
            if (isset($aUser['password'])){

                // TODO: this is clear text 
                // --> implement https://www.php.net/manual/en/function.password-verify.php
                // 
                // JS:
                // headers.set('Authorization', 'Basic ' + btoa(username + ":" + password));
                if ($aUser['password'] !== $sPassword){
                    return [ 'error' => 'authentication failed' ];
                }
            }
        }
        $this->_user=$sUser;
        return true;
    }
     */

    // ----------------------------------------------------------------------
    // USER FUNCTIONS
    // ----------------------------------------------------------------------

    /**
     * detect a user from $_SERVER env 
     */
    public function getAlreadyAuthenticatedUser()
    {
        // check if a user ist set with basic auth
        foreach ($this->_aCfg['userfields'] as $sUserkey) {
            if (isset($_SERVER[$sUserkey])) {
                return $_SERVER[$sUserkey];
            }
        }
        return '';
    }

    /**
     * get current username that was detected or set
     * @return string
     */
    public function getUserid()
    {
        return $this->_user;
    }
    /**
     * get current username that was detected or set
     * @return string
     */
    public function getUsername()
    {
        $aUser = $this->getUser();
        return isset($aUser['username']) ? print_r($aUser['username'], 1) : '[' . $this->_user . ']';
    }

    /**
     * get meta fields for current or given user
     * @param  string  $sUsername  optional: override user id 
     * @return 
     */
    public function getUser($sUsername = false)
    {
        $sUsername = $sUsername ? $sUsername : $this->_user;
        return ($sUsername && isset($this->_aCfg["users"][$sUsername]))
            ? $this->_aCfg["users"][$sUsername]
            : false;
    }

    /**
     * set a username to work with
     * @param  string  $sNewUser  username; it should be a user in config users key (or you loose all access)
     * @return bool
     */
    public function setUser($sNewUser)
    {
        $this->_user = preg_replace('/[^a-z0-9\*]/', '', $sNewUser);
        return true;
    }

    /**
     * get roles of a user. If the user itself has no roles
     * but was authenticated by the webserver then it gets
     * default roles from user "__default_authenticated_user__"
     */
    public function getRoles()
    {
        $aUser = $this->getUser();
        if (is_array($aUser)) {
            if (isset($aUser['roles'])) {
                return $aUser['roles'];
            }
        }
        // no roles for anonymous if user config was removed
        if ($this->_user == '*') {
            return false;
        }
        // non detected authenticated users inherit data from __default_authenticated_user__
        $aDefault = $this->getUser('__default_authenticated_user__');
        return $aDefault['roles'] ?? false;
    }

    /**
     * return if a user has a given role
     * @param  string  $sRequiredRole  name of the role to verify
     * @return true
     */
    public function hasRole($sRequiredRole)
    {
        $aRoles = $this->getRoles();
        if (is_array($aRoles) && count($aRoles)) {
            return (in_array('*', $aRoles)                // a user has * for all roles
                || in_array($sRequiredRole, $aRoles)  // the role name itself was found
            );
        }
        return false;
    }

    // ----------------------------------------------------------------------

    /**
     * helper function: handle url parameters
     */
    protected function _handleParams()
    {
        $sAction = $_POST["action"] ?? '';
        switch ($sAction) {
            case "addurl":
                $this->actionAddUrl($_POST["url"]);

                break;

            case "deleteurl":
                $this->actionDeleteUrl($_POST["url"]);

                break;
            default:
                break;
        }
    }

    /**
     * get a flat array of tags sent from all clients
     * @return array
     */
    protected function _getClientTags()
    {
        $aTags = [];
        foreach ($this->_data as $aEntries) {
            if (isset($aEntries['meta']['tags'])) {
                foreach ($aEntries['meta']['tags'] as $sTag) {
                    $aTags[] = $sTag;
                }
            }
        }
        sort($aTags);
        $aTags = array_unique($aTags);
        return $aTags;
    }

    /**
     * generate array with http status values from a string
     * 
     * @param string $sHttpHeader
     * @return array
     */
    protected function _getHttpStatusArray($sHttpHeader)
    {
        if (!$sHttpHeader) {
            return false;
        }
        $aHeader = [];
        foreach (explode("\r\n", $sHttpHeader) as $sLine) {
            preg_match_all('#^(.*)\:(.*)$#U', $sLine, $aMatches);
            $sKey = $aMatches[1][0] ?? '_status';
            $sValue = $aMatches[2][0] ?? $sLine;
            $aHeader[$sKey] = $sValue;
            if ($sKey === '_status') {
                preg_match_all('#HTTP.*([0-9][0-9][0-9])#', $sValue, $aMatches);
                $aHeader['_statuscode'] = $aMatches[1][0] ?? false;
            }
        }
        return $aHeader;
    }

    protected function _getHttpStatus($sHttpHeader)
    {
        $aHeader = $this->_getHttpStatusArray($sHttpHeader);
        return $aHeader['_statuscode'] ?? false;
    }

    /**
     * helper function for multi_curl_exec
     * hint from kempo19b
     * http://php.net/manual/en/function.curl-multi-select.php
     * 
     * @param CurlMultiHandle  $mh             multicurl master handle
     * @param boolean          $still_running  
     * @return type
     */
    protected function full_curl_multi_exec($mh, &$still_running)
    {
        do {
            $rv = curl_multi_exec($mh, $still_running);
        } while ($rv == CURLM_CALL_MULTI_PERFORM);
        return $rv;
    }

    protected function _multipleHttpGet($aUrls)
    {
        $aResult = [];

        // prepare curl object
        $master = curl_multi_init();

        // requires php>=5.5:
        if (function_exists('curl_multi_setopt')) {
            // force parallel requests
            curl_multi_setopt($master, CURLMOPT_PIPELINING, 0);
            // curl_multi_setopt($master, CURLMOPT_MAXCONNECTS, 50);
        }

        $curl_arr = [];
        foreach ($aUrls as $sKey => $sUrl) {
            $curl_arr[$sKey] = curl_init($sUrl);
            curl_setopt_array($curl_arr[$sKey], $this->curl_opts);
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
            $sHeader = '';
            $sBody = '';
            $aResponse = explode("\r\n\r\n", curl_multi_getcontent($curl_arr[$sKey]), 2);
            list($sHeader, $sBody) = count($aResponse) > 1
                ? $aResponse
                : [$aResponse[0], ''];

            $aResult[$sKey] = [
                'url' => $sUrl,
                'response_header' => $sHeader,
                'response_body' => $sBody,
                'curlinfo' => curl_getinfo($curl_arr[$sKey])
            ];
            curl_multi_remove_handle($master, $curl_arr[$sKey]);
        }
        curl_multi_close($master);
        return $aResult;
    }

    /**
     * helpfer function: get client data from meta and generate a key
     * "result" with whole summary
     * @param type $aClientdata
     */
    protected function _generateResultArray($aClientData)
    {
        $aReturn = [];
        $aReturn["ts"] = date("U");
        $aReturn["result"] = 1; // set "unknown" as default

        if (!isset($aClientData["meta"])) {
            return $aReturn;
        }
        foreach ([ "host", "website", "result" ] as $sField) {
            $aReturn[$sField] = $aClientData["meta"][$sField] ?? false;
        }

        // returncodes
        $aResults = [
            'total' => 0,
            0 => 0,
            1 => 0,
            2 => 0,
            3 => 0,
        ];
        if (isset($aClientData["checks"]) && count($aClientData["checks"])) {
            $aResults["total"] = count($aClientData["checks"]);
            foreach ($aClientData["checks"] as $aCheck) {
                $iResult = $aCheck["result"];
                $aResults[$iResult]++;
            }
        }
        $aReturn["summary"] = $aResults;
        return $aReturn;
    }

    /**
     * detect outdated application checks by reading cached data
     * if age (since last write) is larger 2 x TTL then it uis marked as outdated.
     * REMARK: 
     */
    protected function _detect_outdated_appchecks()
    {
        foreach ($this->_urls as $sKey => $sUrl) {
            $oCache = new AhCache("appmonitor-server", $this->_generateUrlKey($sUrl));

            $this->_data[$sKey] = $oCache->read();

            $iAge = isset($this->_data[$sKey]["result"]["ts"]) ? (time() - $this->_data[$sKey]["result"]["ts"]) : 0;
            if (
                $iAge
                && $iAge > 2 * $this->_data[$sKey]["result"]["ttl"]
                && $this->_data[$sKey]["result"]["error"] != $this->_tr('msgErr-Http-outdated')
            ) {
                $this->_data[$sKey]["result"]["error"] = $this->_tr('msgErr-Http-outdated');
                $this->_data[$sKey]["result"]["result"] = RESULT_UNKNOWN;
                if (!isset($this->_data[$sKey]["result"]["outdated"])) {
                    $this->_data[$sKey]["result"]["outdated"] = true;

                    /*
                    // write to cache that notification class can read from it
                    $oCache->write($this->_data[$sKey], 0);
                    $this->oNotification->setApp($sKey);
                    $this->oNotification->notify();
                    */
                }
            }
        }
    }

    /**
     * get all client data; it fetches all given urls
     * @param boolean  $ForceCache  flag: use cache; default: false (=automatic selection by source and config "servicecache")
     * @return boolean
     */
    protected function _getClientData($ForceCache = false)
    {
        if (!$ForceCache) {
            $ForceCache = isset($_SERVER['REQUEST_METHOD']) && isset($this->_aCfg['servicecache']) && $this->_aCfg['servicecache'];
        }
        $this->_data = [];
        $aUrls = [];
        foreach ($this->_urls as $sKey => $sUrl) {
            $oCache = new AhCache("appmonitor-server", $this->_generateUrlKey($sUrl));
            if ($oCache->isExpired() && !$ForceCache) {
                // Cache does not exist or is expired
                $aUrls[$sKey] = $sUrl;
            } else {
                // age is bel['result']['error']ow ttl ... read from Cache 
                $aCached = $oCache->read();
                $this->_data[$sKey] = $aCached ? $aCached : [];

                $this->_data[$sKey]["result"]["fromcache"] = true;
            }
        }
        // fetch all non cached items
        if (count($aUrls)) {
            $aAllHttpdata = $this->_multipleHttpGet($aUrls);
            foreach ($aAllHttpdata as $sKey => $aResult) {
                $aClientData = json_decode($aResult['response_body'], true);
                // $iTtl = $this->_iTtl;
                if (!is_array($aClientData)) {
                    $iTtl = $this->_iTtlOnError;
                    $aClientData = [];
                } else {
                    if (
                        isset($aClientData["meta"]["ttl"]) && $aClientData["meta"]["ttl"]
                    ) {
                        $iTtl = (int) $aClientData["meta"]["ttl"];
                    }
                }
                // detect error
                $iHttpStatus = $this->_getHttpStatus($aResult['response_header']);
                $sError = !$aResult['response_header']
                    ? $this->_tr('msgErr-Http-request-failed')
                    : (
                        (!$iHttpStatus || $iHttpStatus < 200 || $iHttpStatus > 299)
                        ? $this->_tr('msgErr-Http-error')
                        : (!count($aClientData) ? $this->_tr('msgErr-Http-no-jsondata') : false)
                    );

                // add more metadata
                $aClientData["result"] = $this->_generateResultArray($aClientData);

                // set application status
                // 2xx -> check json response
                // no status = connect failed -> error
                // 4xx -> no data -> unknown
                // 5xx -> application error -> error
                if (!$iHttpStatus || $iHttpStatus >= 400) {
                    $aClientData["result"]["result"] = (!$iHttpStatus || $iHttpStatus >= 500)
                        ? RESULT_ERROR
                        : RESULT_UNKNOWN;
                }

                $aClientData["result"]["ttl"] = $iTtl;
                $aClientData["result"]["url"] = $aResult['url'];
                $aClientData["result"]["header"] = $aResult['response_header'];
                $aClientData["result"]["headerarray"] = $this->_getHttpStatusArray($aResult['response_header']);
                $aClientData["result"]["httpstatus"] = $iHttpStatus;
                $aClientData["result"]["error"] = $sError;

                if (!isset($aClientData["result"]["website"]) || !$aClientData["result"]["website"]) {
                    $aClientData["result"]["website"] = $this->_tr('unknown');
                }

                // $aClientData["result"]["curlinfo"] = $aResult['curlinfo'];

                $oCounters = new counteritems($sKey);
                $oCounters->setCounter('_responsetime', [
                    'title' => $this->_tr('Chart-responsetime'),
                    'visual' => 'bar',
                ]);
                $oCounters->add([
                    'status' => $aClientData["result"]["result"],
                    'value' => floor($aResult['curlinfo']['total_time'] * 1000)
                ]);
                // find counters in a check result
                if (isset($aClientData['checks']) && count($aClientData['checks'])) {
                    // echo '<pre>'.print_r($aClientData['checks'], 1).'</pre>';
                    foreach ($aClientData['checks'] as $aCheck) {
                        $sIdSuffix = preg_replace('/[^a-zA-Z0-9]/', '', $aCheck['name']) . '-' . md5($aCheck['name']);
                        $sTimerId = 'time-' . $sIdSuffix;
                        $oCounters->setCounter($sTimerId, [
                            'title' => 'timer for[' . $aCheck['description'] . '] in [ms]',
                            'visual' => 'bar'
                        ]);
                        $oCounters->add([
                            'status' => $aCheck['result'],
                            'value' => str_replace('ms', '', isset($aCheck['time']) ? $aCheck['time'] : '')
                        ]);
                        if (isset($aCheck['count']) || (isset($aCheck['type']) && $aCheck['type'] === 'counter')) {
                            $sCounterId = 'check-' . $sIdSuffix;
                            // $oCounters->setCounter($sCounterId);
                            $oCounters->setCounter($sCounterId, [
                                'title' => $aCheck['description'],
                                'visual' => $aCheck['visual'] ?? false,
                            ]);
                            $oCounters->add([
                                'status' => $aCheck['result'],
                                'value' => $aCheck['count'] ?? $aCheck['value']
                            ]);
                        }
                    }
                }
                $this->send(
                    ""
                        . $aResult['url']
                        . " Httpstatus=" . $iHttpStatus
                        . " TTL=$iTtl"
                        . " responsetime=" . floor($aResult['curlinfo']['total_time'] * 1000) . "ms"
                        . " appstatus=" . $this->_tr('Resulttype-' . $aClientData["result"]["result"])
                        . $sError
                );

                // write cache
                $oCache = new AhCache("appmonitor-server", $this->_generateUrlKey($aResult['url']));

                // randomize cachetime of appmonitor client response: ttl + 2..30 sec
                $iTtl = $iTtl + rand(2, min(5 + round($iTtl / 3), 30));

                $oCache->write($aClientData, $iTtl);

                $aClientData["result"]["fromcache"] = false;
                $this->_data[$sKey] = $aClientData;


                $this->oNotification->setApp($sKey);
                $this->oNotification->notify();
            }
        }
        $this->_detect_outdated_appchecks();
        return true;
    }

    /**
     * translate a text with language file
     * @param string $sWord
     * @return string
     */
    protected function _tr($sWord)
    {
        return $this->oLang->tr($sWord, [ 'gui' ]);
    }

    // ----------------------------------------------------------------------
    // setter
    // ----------------------------------------------------------------------

    protected function _generateUrlKey($sUrl)
    {
        return md5($sUrl);
    }

    /**
     * add appmonitor url
     * @param string $sUrl
     * @return boolean
     */
    public function addUrl($sUrl)
    {
        $sKey = $this->_generateUrlKey($sUrl);
        $this->_urls[$sKey] = $sUrl;
        return true;
    }

    /**
     * remove appmonitor url
     * @param string $sUrl
     * @return boolean
     */
    public function removeUrl($sUrl)
    {
        $sKey = $this->_generateUrlKey($sUrl);
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
    public function setDemoMode($bBool = true)
    {
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
    protected function _hrTime($iSec)
    {
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
     * helper function for counters for overview over all web apps
     * 
     * @return type
     */
    protected function _getCounter()
    {
        $iCountApps = 0;
        $iCountChecks = 0;
        $aResults = [ 0, 0, 0, 0 ];
        $aCheckResults = [ 0, 0, 0, 0 ];
        $aServers = [];
        foreach ($this->_data as $sKey => $aEntries) {
            $iCountApps++; // count of webapps
            $aResults[$aEntries['result']['result']]++; // counter by result of app
            if (isset($aEntries['result']['host']) && $aEntries['result']['host']) {
                $aServers[$aEntries['result']['host']] = true; // helper array to count hosts
            }

            // count of checks
            if (isset($this->_data[$sKey]["result"]["summary"])) {
                $aChecks = $this->_data[$sKey]["result"]["summary"];
                $iCountChecks += $aChecks["total"];
                for ($i = 0; $i < 4; $i++) {
                    $aCheckResults[$i] += $aChecks[$i];
                }
            }
        }
        return [
            'apps' => $iCountApps,
            'hosts' => count($aServers),
            'appresults' => $aResults,
            'checks' => $iCountChecks,
            'checks' => $iCountChecks,
            'checkresults' => $aCheckResults
        ];
    }

    /**
     * set flag for logging to standard output
     */
    public function setLogging($bShow)
    {
        return $this->_bShowLog = !!$bShow;
    }
    /**
     * write a message to STDOUT (if actiated or logging is on)
     *
     * @param string   $sMessage  message text
     * @param boolean  $bShow     flag to write to stdout (overrides internal value)
     * @return boolean
     */
    public function send($sMessage, $bShow = false)
    {
        echo ($bShow || $this->_bShowLog)
            ? (date("Y-m-d H:i:s") . " " . $sMessage . "\n")
            : '';
    }
    /**
     * get all client data and final result as array
     * @param   string  $sHost  filter by given hostname
     * @return  array
     */
    public function getMonitoringData($sHost = false)
    {

        $iMaxReturn = 0;
        $aMessages = [];
        $aResults = [];

        if (!count($this->_data) || true) {
            $this->_getClientData();
        }

        // print_r($this->_data);

        if (!count($this->_data)) {
            return [
                'return' => 3,
                'messages' => [ $this->_tr('msgErr-nocheck') ]
            ];
        }
        foreach ($this->_data as $sKey => $aEntries) {

            // filter if a host was given
            if (
                !$sHost ||
                (isset($aEntries["result"]["host"]) && $sHost == $aEntries["result"]["host"])
            ) {

                if (
                    !isset($aEntries["result"])
                    || !isset($aEntries["checks"]) || !count($aEntries["checks"])
                ) {
                    if ($iMaxReturn < 3){
                        $iMaxReturn = 3;
                    }
                    $aMessages[] = $this->_tr('msgErr-Http-request-failed') . ' (' . $aEntries["result"]["url"] . ')';
                } else {
                    if ($iMaxReturn < $aEntries["result"]["result"]) {
                        $iMaxReturn = $aEntries["result"]["result"];
                    }
                    // $aMessages[] = $aEntries["result"]["host"] . ': ' . $aEntries["result"]["result"];
                    foreach ($aEntries["result"]["summary"] as $key => $value) {
                        if (!isset($aResults[$key])) {
                            $aResults[$key] = 0;
                        }
                        $aResults[$key] += $value;
                    }
                }
            }
        }
        return [
            'return' => $iMaxReturn,
            'messages' => $aMessages,
            'results' => $aResults,
        ];
    }
}
