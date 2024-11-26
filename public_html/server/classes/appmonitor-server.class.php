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
 * @version 0.142
 * @author Axel Hahn
 * @link https://github.com/iml-it/appmonitor
 * @license GPL
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL 3.0
 * @package IML-Appmonitor
 * --------------------------------------------------------------------------------<br>
 * 2024-07-17  0.137  axel.hahn@unibe.ch  php 8 only: use typed variables
 * 2024-11-26  0.142  axel.hahn@unibe.ch  handle invalid response data
 */
class appmonitorserver
{

    /**
     * Key value hash with all clients to fetch appmon client status from
     * key is a hash; value the client url
     * @var array
     */
    protected array $_urls = [];

    /**
     * Hash with response data of all clients
     * @var array
     */
    protected array $_data = [];

    /**
     * Loaded config data
     * @var array
     */
    protected array $_aCfg = [];

    /**
     * Default TTL if a client does not send its own TTL value
     * value is in sec
     * 
     * @var integer
     */
    protected int $_iTtl = 300;

    /**
     * Default TTL if a client does not send its own TTL value
     * Value is in sec
     * 
     * @var integer
     */
    protected int $_iTtlOnError = 60;

    /**
     * name of the config file to load
     * @var string
     */
    protected string $_sConfigfile = "appmonitor-server-config.json";

    /**
     * name of the config file with all urls to monitor
     * @var string
     */
    protected string $_sUrlfile = "appmonitor-server-urls.json";

    /**
     * Array of messages to log
     * @var array
     */
    protected array $_aMessages = [];

    /**
     * language texts object
     * @var lang object
     */
    protected lang $oLang;

    /**
     * Flag if the demo mode is used
     * Set true to disallow changing config in webgui
     * 
     * @var bool
     */
    protected bool $_bIsDemo = false;

    /**
     * Array of curl default option for http requests
     * @var array
     */
    protected array $curl_opts = [
        CURLOPT_HEADER => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FAILONERROR => 0,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_USERAGENT => 'Appmonitor (using curl; see https://github.com/iml-it/appmonitor to install your own monitoring instance;)',
        // CURLMOPT_MAXCONNECTS => 10
    ];

    /**
     * Flag: show output on STDOUT 
     * @see send()
     * 
     * @var bool
     */
    protected bool $_bShowLog = false;

    /**
     * notificationhandler object to send email/ slack messages
     * it is initialized in method loadConfig()
     * 
     * @var notificationhandler object
     */
    public notificationhandler $oNotification;

    /**
     * Detected user name to handle with roles
     * see config -> users -> USERNAME
     * 
     * @var string
     */
    protected string $_user = '*';

    // ----------------------------------------------------------------------
    // Constructor
    // ----------------------------------------------------------------------

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
     * Return config dir ... it is one dir up and "config"
     * @return string
     */
    protected function _getConfigDir(): string
    {
        return dirname(__DIR__) . '/config';
    }

    /**
     * Load language texts and put it into $this->oLang
     * @return bool
     */
    protected function _loadLangTexts()
    {
        $this->oLang = new lang($this->_aCfg['lang']);
        return true;
    }

    /**
     * Get a flat array with all application ids and website + url
     * as subkeys
     * 
     * @return array
     */
    public function getAppIds(): array
    {
        $this->_getClientData(true);
        return array_keys($this->_data);
    }

    /**
     * Get a hash with all configuration items
     * @return array
     */
    public function getConfigVars(): array
    {
        return $this->_aCfg;
    }

    /**
     * (re) load config and get all urls to fetch (and all other config items)
     * This method 
     * - fills $this->_aCfg
     * - newly initializes $this->oNotification
     * 
     * @return void
     */
    public function loadConfig(): void
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

        // load urls from a separate file
        $sUrlFile=$this->_getConfigDir() . '/' . $this->_sUrlfile;
        if(file_exists($sUrlFile)){
            $this->_urls=json_decode(file_get_contents($this->_getConfigDir() . '/' . $this->_sUrlfile), true);
        }

        // migration for old way to load urls
        if (isset($this->_aCfg['urls']) && is_array($this->_aCfg['urls'])) {
            foreach ($this->_aCfg["urls"] as $sUrl) {
                $this->addUrl($sUrl);
            }
            $this->saveUrls();
            unset($this->_aCfg['urls']);
            $this->saveConfig();
        }

        if (isset($this->_aCfg['curl']['timeout'])) {
            $this->curl_opts[CURLOPT_TIMEOUT] = (int) $this->_aCfg['curl']['timeout'];
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
    public function loadClientData(): bool
    {
        if (!count($this->_data)) {
            $this->_getClientData();
        }
        return true;
    }


    /**
     * Save the current or new config data as file.
     * @return boolean
     */
    public function saveConfig(array $aNewCfg = []): bool
    {
        if ($this->_bIsDemo) {
            $this->_addLog($this->_tr('msgErr-demosite'), "error");
            return false;
        }
        if (count($aNewCfg)) {
            $this->_aCfg = $aNewCfg;
        }
        $sCfgFile = $this->_getConfigDir() . '/' . $this->_sConfigfile;

        $iOptions = defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : 0;
        $sData = json_encode($this->_aCfg, $iOptions);

        return file_put_contents($sCfgFile, $sData);
    }

    /**
     * Save the current or new config data as file.
     * @return boolean
     */
    public function saveUrls(array $aNewCfg = []): bool
    {
        if ($this->_bIsDemo) {
            $this->_addLog($this->_tr('msgErr-demosite'), "error");
            return false;
        }
        $sCfgFile = $this->_getConfigDir() . '/' . $this->_sUrlfile;

        $iOptions = defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : 0;
        $sData = json_encode($this->_urls, $iOptions);

        return file_put_contents($sCfgFile, $sData);
    }

    /**
     * Add a logging message to display in web gui in a message box
     * 
     * @param  string  $sMessage  Message text
     * @param  string  $sLevel    level: info, warning, error
     * @return boolean
     */
    protected function _addLog(string $sMessage, string $sLevel = "info")
    {
        $this->_aMessages[] = [
            'time' => microtime(true),
            'message' => $sMessage,
            'level' => $sLevel
        ];
        return true;
    }

    /**
     * Verify the Response if it is a valid JSON and has all required fields
     * It returns an array with found errors. An empty array means everything is ok
     * 
     * @param string $sBody
     * @return array
     */
    protected function _checkResponse(string $sBody): array
    {
        $aErrors=[];
        $_iNumber=0;
        if (!is_array(json_decode($sBody, 1))) {
            $aErrors[] = 'Response is not a valid JSON.';
        } else {
            $aResponse = json_decode($sBody, 1);
            if (!isset($aResponse['meta'])) {
                $aErrors[] = 'Section "meta" was not found';
            } else {
                foreach(['host', 'website', 'result'] as $sField){
                    if (!isset($aResponse['meta'][$sField])) {
                        $aErrors[] = "Section 'meta > $sField' was not found";
                    }
                }
                if(isset($aResponse['meta']['result'])){
                    if(!is_numeric($aResponse['meta']['result'])){
                        $aErrors[] = "Section 'meta > result' is not a number - $aResponse[meta][result]";
                    } else if($aResponse['meta']['result']<RESULT_OK || $aResponse['meta']['result']>RESULT_ERROR) {
                        $aErrors[] = "Section 'meta > result' is not a valid number - $aResponse[meta][result]";
                    }
                }
            }
            if (!isset($aResponse['checks'])) {
                $aErrors[] = 'Section "checks" was not found';
            } else {
                foreach($aResponse['checks'] as $aCheck){
                    $_iNumber++;
                    if(!is_array($aCheck)){
                        $aErrors[] = "Section 'check #$_iNumber' is not an array - $aCheck";
                    } else {
                        foreach(['name', 'description', 'result', 'value'] as $sField){
                            if (!isset($aCheck[$sField])) {
                                $aErrors[] = "Section 'check #$_iNumber > $sField' was not found";
                            }
                        }
                        if(isset($aCheck['result'])){
                            if(!is_numeric($aCheck['result'])){
                                $aErrors[] = "Section 'check #$_iNumber > result' is not a number - $aCheck[result]";
                            } else if($aCheck['result']<RESULT_OK || $aCheck['result']>RESULT_ERROR) {
                                $aErrors[] = "Section 'check #$_iNumber > result' is not a valid number - $aCheck[result]";
                            }
                        }
                    }
                }
            }
        }
        return $aErrors;
    }

    /**
     * Setup action: add a new url and save the config
     * @param string $sUrl        url to add
     * @param bool   $bMakeCheck  Flag: check a valid url and response is JSON
     * @return bool
     */
    public function actionAddUrl(string $sUrl, bool $bMakeCheck = true): bool
    {
        if ($sUrl) {
            // if (!isset($this->_aCfg["urls"]) || ($key = array_search($sUrl, $this->_aCfg["urls"])) === false) {
            if (array_search($sUrl, $this->_urls) === false) {

                $bAdd = true;
                if ($bMakeCheck) {
                    $aHttpData = $this->_multipleHttpGet([$sUrl]);
                    
                    $aErrors=$this->_checkResponse($aHttpData[0]['response_body'] ?? false);
                    if(count($aErrors)){
                        $bAdd = false;
                        $this->_addLog(
                            sprintf(
                                $this->_tr('msgErr-Url-not-added-no-appmonitor'),
                                $sUrl,
                                (isset($aHttpData[0]['response_header']) ? '<pre>' . $aHttpData[0]['response_header'] . '</pre>' : '-')
                                ."- ".implode('<br>- ', $aErrors)."<br>"
                            ),
                            'error'
                        );
                    }
                }
                if ($bAdd) {
                    $this->_addLog(sprintf($this->_tr('msgOK-Url-was-added'), $sUrl), "ok");
                    $this->addUrl($sUrl);
                    $this->saveUrls();
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
     * Setup action: Delete an url to fetch and trigger to save the new config file
     * @param string $sUrl   url to delete in the config
     * @return bool
     */
    public function actionDeleteUrl(string $sUrl): bool
    {
        if ($sUrl) {
            if (($key = array_search($sUrl, $this->_urls)) !== false) {
                $sAppId = $this->_generateUrlKey($sUrl);

                // $this->oNotification->deleteApp($sAppId);
                // $oCache = new AhCache("appmonitor-server", $this->_generateUrlKey($sUrl));
                // $oCache->delete();
                unset($this->_urls[$key]);
                $this->saveUrls();
                $this->loadConfig();

                // delete notification after config was saved
                if (!isset($this->_urls[$key])) {
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
     * Return a detected user from $_SERVER env
     * @return string
     */
    public function getAlreadyAuthenticatedUser(): string
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
     * Get current username that was detected or set
     * @return string
     */
    public function getUserid(): string
    {
        return $this->_user;
    }

    /**
     * Get current username that was detected or set
     * @return string
     */
    public function getUsername(): string
    {
        $aUser = $this->getUser();
        return isset($aUser['username']) ? print_r($aUser['username'], 1) : "[$this->_user]";
    }

    /**
     * Get meta fields for current or given user
     * @param  string  $sUsername  optional: override current user id 
     * @return bool|array
     */
    public function getUser(string $sUsername = ''): bool|array
    {
        $sUsername = $sUsername ? $sUsername : $this->_user;
        return ($sUsername && isset($this->_aCfg["users"][$sUsername]))
            ? $this->_aCfg["users"][$sUsername]
            : false;
    }

    /**
     * Set a username to work with
     * @param  string  $sNewUser  username; it should be a user in config users key (or you loose all access)
     * @return bool
     */
    public function setUser(string $sNewUser): bool
    {
        $this->_user = preg_replace('/[^a-z0-9\*]/', '', $sNewUser);
        return true;
    }

    /**
     * Get roles of a user. If the user itself has no roles
     * but was authenticated by the webserver then it gets
     * default roles from user "__default_authenticated_user__"
     * @return bool|array
     */
    public function getRoles():bool|array
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
     * Return if a user has a given role
     * @param  string  $sRequiredRole  name of the role to verify
     * @return bool
     */
    public function hasRole(string $sRequiredRole): bool
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
     * Helper function: handle url parameters
     * - action = "addurl|deleteurl" + url = "..."
     * 
     * @return void
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
     * Get a flat array of tags sent from all clients
     * @return array
     */
    protected function _getClientTags(): array
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
     * Generate array with http status values from a string
     * 
     * @param string $sHttpHeader
     * @return array
     */
    protected function _getHttpStatusArray(string $sHttpHeader): array
    {
        if (!$sHttpHeader) {
            return [];
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

    /**
     * Get http status code from a given header 
     * It returns false if no status code was found (http request failed and has no response)
     * @param  string  $sHttpHeader  http response header
     * @return bool|int
     */
    protected function _getHttpStatus(string $sHttpHeader): bool|int
    {
        $aHeader = $this->_getHttpStatusArray($sHttpHeader);
        return $aHeader['_statuscode'] ?? false;
    }

    /**
     * Helper function for multi_curl_exec
     * hint from kempo19b
     * http://php.net/manual/en/function.curl-multi-select.php
     * 
     * @param CurlMultiHandle  $mh             multicurl master handle
     * @param boolean          $still_running  
     * @return int
     */
    protected function full_curl_multi_exec($mh, &$still_running): int
    {
        do {
            $rv = curl_multi_exec($mh, $still_running);
        } while ($rv == CURLM_CALL_MULTI_PERFORM);
        return $rv;
    }

    /**
     * Ececute multiple http requests in parallel and return an array
     * with url as key and its result infos in subkeys
     *   - 'url'              {string} url
     *   - 'response_header'  {string} http response header
     *   - 'response_body'    {string} http response body
     *   - 'curlinfo'         {array}  curl request infos
     *   - 'curlerrorcode'    {int}    curl error code
     *   - 'curlerrormsg'     {string} curl error message
     *
     * @param array $aUrls  array of urls to fetch
     * @return array
     */
    protected function _multipleHttpGet(array $aUrls): array
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
                'curlinfo' => curl_getinfo($curl_arr[$sKey]),
                'curlerrorcode' => curl_errno($curl_arr[$sKey]),
                'curlerrormsg' => curl_error($curl_arr[$sKey]),
            ];
            curl_multi_remove_handle($master, $curl_arr[$sKey]);
        }
        curl_multi_close($master);
        return $aResult;
    }

    /**
     * Helper function: get client data from meta and generate a key
     * "result" with whole summary
     * 
     * @param array $aClientdata
     * @return array
     */
    protected function _generateResultArray($aClientData): array
    {
        $aReturn = [];
        $aReturn["result"] = RESULT_UNKNOWN; // set "unknown" as default

        if (!isset($aClientData["meta"])) {
            return $aReturn;
        }
        foreach (["host", "website", "result"] as $sField) {
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
     * Detect outdated application checks by reading cached data
     * if age (since last write) is larger 2 x TTL then it uis marked as outdated.
     * @return void
     */
    protected function _detect_outdated_appchecks(): void
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
     * Get all client data; it fetches all given urls
     * 
     * 
     * @param boolean  $ForceCache  flag: use cache; default: false (=automatic selection by source and config "servicecache")
     * @return boolean
     */
    protected function _getClientData(bool $ForceCache = false): bool
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

                // detect http error
                $iHttpStatus = $this->_getHttpStatus($aResult['response_header']);
                $sError = !$aResult['response_header']
                    ? $this->_tr('msgErr-Http-request-failed')
                    . (isset($aResult['curlerrormsg'])
                        ? '<br>' . sprintf($this->_tr('Curl-error'), $aResult['curlerrormsg'], $aResult['curlerrorcode'])
                        : ''
                    )
                    : (
                        (!$iHttpStatus || $iHttpStatus > 299)
                        ? $this->_tr('msgErr-Http-error')
                        # : (!count($aClientData) ? $this->_tr('msgErr-Http-no-jsondata') : false)
                        : false
                    );
            
                // check syntax of response
                $aJsonErrors=$this->_checkResponse($aResult['response_body']);
                if(count($aJsonErrors)){
                    $aClientData=[];
                    // $aClientData["result"]=RESULT_ERROR;
                    $sError.="- ".implode('<br>- ', $aJsonErrors)."<br>";
                } else {

                    $aClientData = json_decode($aResult['response_body'], true);

                    // add more metadata
                    $aClientData["result"] = $this->_generateResultArray($aClientData);
                }
                $aClientData["result"]["ts"]=date('U');

                $iTtl = $sError 
                    ? $this->_iTtlOnError 
                    : (int) ($aClientData["meta"]["ttl"] ?? $this->_iTtl);
                    

                // set application status
                // 2xx -> check json response
                // no status = connect failed -> error
                // 4xx -> no data -> unknown
                // 5xx -> application error -> error
                if (
                    !$iHttpStatus 
                    || $iHttpStatus >= 400
                    || count($aJsonErrors)
                ) {
                    $aClientData["result"]["result"] = (!$iHttpStatus || $iHttpStatus >= 500)
                        ? RESULT_ERROR
                        : RESULT_UNKNOWN;
                }

                $aClientData["result"]["ttl"] = $iTtl ?? $this->_iTtl;
                $aClientData["result"]["url"] = $aResult['url'];
                $aClientData["result"]["header"] = $aResult['response_header'];
                $aClientData["result"]["headerarray"] = $this->_getHttpStatusArray($aResult['response_header']);
                $aClientData["result"]["httpstatus"] = $iHttpStatus;
                $aClientData["result"]["error"] = $sError;
                $aClientData["result"]["curlerrorcode"] = $aResult['curlerrorcode'];
                $aClientData["result"]["curlerrormsg"] = $aResult['curlerrormsg'];

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
                    . $sError . " " . $aResult['curlerrormsg']
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
     * Translate a text with language file
     * @param string $sWord
     * @return string
     */
    protected function _tr(string $sWord): string
    {
        return $this->oLang->tr($sWord, ['gui']);
    }

    // ----------------------------------------------------------------------
    // setter
    // ----------------------------------------------------------------------

    /**
     * Helper function: generate a unique url key
     * @param string $sUrl
     * @return string
     */
    protected function _generateUrlKey(string $sUrl): string
    {
        return md5($sUrl);
    }

    /**
     * Add appmonitor url to current object
     * @param string $sUrl  url to add
     * @return boolean
     */
    public function addUrl($sUrl): bool
    {
        $sKey = $this->_generateUrlKey($sUrl);
        $this->_urls[$sKey] = $sUrl;
        return true;
    }

    /**
     * remove appmonitor url from current object
     * @param string $sUrl url to remove
     * @return boolean
     */
    public function removeUrl(string $sUrl): bool
    {
        $sKey = $this->_generateUrlKey($sUrl);
        if (array_key_exists($sKey, $this->_urls)) {
            unset($this->_urls[$sKey]);
            return true;
        }
        return false;
    }

    /**
     * switch demo mode on and off
     * TODO: check how switch demo mode and handle parameters
     * @param bool $bBool
     * @return bool
     */
    public function setDemoMode($bBool = true)
    {
        return $this->_bIsDemo = $bBool;
    }

    // ----------------------------------------------------------------------
    // output
    // ----------------------------------------------------------------------

    /**
     * Get a human readable time of a given age in seconds
     * @param int $iSec  seconds
     * @return string
     */
    protected function _hrTime(int $iSec): string
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
     * Helper function for counters for overview over all web apps
     * 
     * @return array
     */
    protected function _getCounter(): array
    {
        $iCountApps = 0;
        $iCountChecks = 0;
        $aResults = [0, 0, 0, 0];
        $aCheckResults = [0, 0, 0, 0];
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
            'checkresults' => $aCheckResults
        ];
    }

    /**
     * Set flag for logging to standard output
     * @param bool $bShow  new logging flag
     * @return bool
     */
    public function setLogging(bool $bShow): bool
    {
        return $this->_bShowLog = !!$bShow;
    }

    /**
     * Write a message to STDOUT (if actiated or logging is on)
     *
     * @param string   $sMessage  message text
     * @param boolean  $bShow     flag to write to stdout (overrides set show log value)
     * @return boolean
     */
    public function send(string $sMessage, $bShow = false): bool
    {
        echo ($bShow || $this->_bShowLog)
            ? (date("Y-m-d H:i:s") . " " . $sMessage . "\n")
            : '';
        return true;
    }

    /**
     * Get all client data and final result as array
     * @param   string  $sHost  filter by given hostname
     * @return  array
     */
    public function getMonitoringData(string $sHost = ''): array
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
                'messages' => [$this->_tr('msgErr-nocheck')]
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
                    if ($iMaxReturn < 3) {
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

    /**
     * Get a readable result by given integer; i.e. 0=OK, 1=unknown, ...
     * @return string
     */
    public function getResultValue(int $i): string
    {
        return $this->_tr('Resulttype-' . $i);
    }
}
