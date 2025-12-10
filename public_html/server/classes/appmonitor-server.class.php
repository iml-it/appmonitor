<?php

require_once 'cache.class.php';
require_once 'lang.class.php';
require_once 'simplerrd.class.php';
require_once 'notificationhandler.class.php';
require_once 'app.class.php';
require __DIR__ . '/../vendor/php-abstract-dbo/src/pdo-db.class.php';
require_once 'dbobjects/webapps.php';

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
 * @version 0.150
 * @author Axel Hahn
 * @link https://github.com/iml-it/appmonitor
 * @license GPL
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL 3.0
 * @package IML-Appmonitor
 * --------------------------------------------------------------------------------<br>
 * 2024-07-17  0.137  axel.hahn@unibe.ch  php 8 only: use typed variables
 * 2024-11-26  0.142  axel.hahn@unibe.ch  handle invalid response data
 * 2025-02-21  0.150  axel.hahn@unibe.ch  use sqlite as storage
 * 2025-03-11  0.154  axel.hahn@unibe.ch  add routes wth public keyword in API
 */
class appmonitorserver
{
    /**
     * Version (will be read from defaults file)
     * @var string
     */
    protected string $_sVersion = "";

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
     * WIP: Application getter object 
     * @var app object
     */
    protected app $oApp;

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

    /**
     * database object for web application response data
     * @var objwebapps
     */
    protected object $_oWebapps;

    // ----------------------------------------------------------------------
    // Constructor
    // ----------------------------------------------------------------------

    /**
     * constructor
     * @global object $oDB      database connection
     */
    public function __construct(bool $bReadonly = false)
    {
        global $oDB;

        $this->loadConfig($bReadonly);
        $this->_loadLangTexts();
        $this->oApp = new app();

        if (!$bReadonly) {
            // remark: $oDB is initialized in loadConfig()
            $this->_oWebapps = new objwebapps($oDB);

            $this->_handleParams();

            $_sUser = $this->getAlreadyAuthenticatedUser();
            $this->setUser($_sUser ? $_sUser : '*');

        }

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
     * Get a flat array with all application id
     * as subkeys
     * 
     * @return array
     */
    public function getAppIds(): array
    {
        return array_keys($this->_urls);
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
     * @global object $oDB         database connection
     * @param  bool   $bReadonly   readonly; default false; true skips 
     *                             - datbase intialisation
     *                             - notification handler
     *                             - version check and upgrade
     * @return void
     */
    public function loadConfig(bool $bReadonly = false): void
    {
        global $oDB;
        $aUserdata = [];
        $aDefaults = [];
        $this->_urls = [];

        $this->_aCfg = []; // reset current config array

        $sCfgUserFile = $this->_getConfigDir() . '/' . $this->_sConfigfile;
        $sCfgDefaultsFile = str_replace('.json', '-defaults.json', $sCfgUserFile);


        if (!file_exists($sCfgDefaultsFile)) {
            die("ERROR: default config file is not readable: [$sCfgDefaultsFile].");
        }
        $aDefaults = json_decode(file_get_contents($sCfgDefaultsFile), true);


        if (file_exists($sCfgUserFile)) {
            $aUserdata = json_decode(file_get_contents($sCfgUserFile), true);
        } else {
            $this->_aCfg=['version' => $aDefaults['version']??'?'];
            $this->saveConfig(); // create $sCfgFile
            $aUserdata = $this->_aCfg;
        }

        $this->_aCfg = array_replace_recursive($aDefaults, $aUserdata);

        // undo unwanted recursive merge behaviour:
        $this->_aCfg['users'] = $aUserdata['users'] ?? $aDefaults['users'];

        // load urls from a separate file
        $sUrlFile = $this->_getConfigDir() . '/' . $this->_sUrlfile;
        if (file_exists($sUrlFile)) {
            $this->_urls = json_decode(file_get_contents($this->_getConfigDir() . '/' . $this->_sUrlfile), true);
        }

        if (isset($this->_aCfg['curl']['timeout'])) {
            $this->curl_opts[CURLOPT_TIMEOUT] = (int) $this->_aCfg['curl']['timeout'];
        }

        if (!$bReadonly) {

            // initialize database
            // echo $this->_aCfg['dsn'];
            if ($this->_aCfg['db']['dsn'] ?? false) {
                $this->_aCfg['db']['dsn'] = str_replace('{{APPDIR}}', dirname(__DIR__), $this->_aCfg['db']['dsn']);
            }

            $oDB = new axelhahn\pdo_db([
                'db' => $this->_aCfg['db'],
                // 'showdebug'=>true,
                // 'showerrors'=>true,
            ]);
            if (!$oDB->db) {
                // echo $oDB->error().'<br>';
                die("SORRY, unable to connect the database.");
            }
            /*
            if($this->hasRole('ui-debug')){
                $oDB->showErrors(true);
            }
            */

            $this->oNotification = new notificationhandler([
                'lang' => $this->_aCfg['lang'],
                'serverurl' => $this->_aCfg['serverurl'],
                'notifications' => $this->_aCfg['notifications']
            ]);

            // Upgrade if needed
            $this->_sVersion = $aDefaults['version'];
            $sLastVersion = $aUserdata['version'] ?? false;
            if (
                $sLastVersion !== $this->_sVersion
            ) {
                require "appmonitor-server-upgrade.php";
                $this->_aCfg['version'] = $this->_sVersion;
                $this->saveConfig(); // update $sCfgUserFile
            } 
        }

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
        $aErrors = [];
        $_iNumber = 0;
        if (!is_array(json_decode($sBody, 1))) {
            $aErrors[] = 'Response is not a valid JSON.';
        } else {
            $aResponse = json_decode($sBody, 1);
            if (!isset($aResponse['meta'])) {
                $aErrors[] = 'Section "meta" was not found';
            } else {
                foreach (['host', 'website', 'result'] as $sField) {
                    if (!isset($aResponse['meta'][$sField])) {
                        $aErrors[] = "Section 'meta > $sField' was not found";
                    }
                }
                if (isset($aResponse['meta']['result'])) {
                    if (!is_numeric($aResponse['meta']['result'])) {
                        $aErrors[] = "Section 'meta > result' is not a number - $aResponse[meta][result]";
                    } else if ($aResponse['meta']['result'] < RESULT_OK || $aResponse['meta']['result'] > RESULT_ERROR) {
                        $aErrors[] = "Section 'meta > result' is not a valid number - $aResponse[meta][result]";
                    }
                }
            }
            if (!isset($aResponse['checks'])) {
                $aErrors[] = 'Section "checks" was not found';
            } else {
                foreach ($aResponse['checks'] as $aCheck) {
                    $_iNumber++;
                    if (!is_array($aCheck)) {
                        $aErrors[] = "Section 'check #$_iNumber' is not an array - $aCheck";
                    } else {
                        foreach (['name', 'description', 'result', 'value'] as $sField) {
                            if (!isset($aCheck[$sField])) {
                                $aErrors[] = "Section 'check #$_iNumber > $sField' was not found";
                            }
                        }
                        if (isset($aCheck['result'])) {
                            if (!is_numeric($aCheck['result'])) {
                                $aErrors[] = "Section 'check #$_iNumber > result' is not a number - $aCheck[result]";
                            } else if ($aCheck['result'] < RESULT_OK || $aCheck['result'] > RESULT_ERROR) {
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

                    $aErrors = $this->_checkResponse($aHttpData[0]['response_body'] ?? false);
                    if (count($aErrors)) {
                        $bAdd = false;
                        $this->_addLog(
                            sprintf(
                                $this->_tr('msgErr-Url-not-added-no-appmonitor'),
                                $sUrl,
                                (isset($aHttpData[0]['response_header']) ? '<pre>' . $aHttpData[0]['response_header'] . '</pre>' : '-')
                                . "- " . implode('<br>- ', $aErrors) . "<br>"
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

                unset($this->_urls[$key]);
                $this->saveUrls();
                $this->loadConfig();


                // delete notification after config was saved
                if (!isset($this->_urls[$key])) {
                    $this->oNotification->deleteApp($sAppId);
                    $rrd = new simpleRrd($key);
                    $rrd->deleteApp();
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
        // return $this->_user=="*" ? "fred" : $this->_user;
        return $this->_user;
    }

    /**
     * Get current username that was detected or set
     * @return string
     */
    public function getUsername(): string
    {
        $aUser = $this->getUserSettings();
        return isset($aUser['username']) ? print_r($aUser['username'], 1) : "[$this->_user]";
    }

    /**
     * Get meta fields for current or given user
     * @param  string  $sUsername  optional: override current user id - used for generic user field "__default_authenticated_user__"
     * @return bool|array
     */
    public function getUserSettings(string $sUsername = ''): bool|array
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
        // Shibboleth:
        // [REMOTE_USER] => https://idp.example.com/idp/shibboleth!https://myapp.example.com/shibboleth!9ZF/Gm0Mh5L+m14tU4rlwKLkqTM=
        // $this->_user = preg_replace('/[^a-z0-9\*]/', '', $sNewUser);
        $this->_user = $sNewUser;
        return true;
    }

    /**
     * Get roles of a user. If the user itself has no roles
     * but was authenticated by the webserver then it gets
     * default roles from user "__default_authenticated_user__"
     * @return bool|array
     */
    public function getRoles(): bool|array
    {
        $aUser = $this->getUserSettings();
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
        $aDefault = $this->getUserSettings('__default_authenticated_user__');
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
    protected function _getAllClientTags(): array
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
        foreach ($aUrls as $sAppid => $sUrl) {
            $curl_arr[$sAppid] = curl_init($sUrl);
            curl_setopt_array($curl_arr[$sAppid], $this->curl_opts);
            curl_multi_add_handle($master, $curl_arr[$sAppid]);
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
        foreach ($aUrls as $sAppid => $sUrl) {
            $sHeader = '';
            $sBody = '';
            $aResponse = explode("\r\n\r\n", curl_multi_getcontent($curl_arr[$sAppid]), 2);
            list($sHeader, $sBody) = count($aResponse) > 1
                ? $aResponse
                : [$aResponse[0], ''];

            $aResult[$sAppid] = [
                'url' => $sUrl,
                'response_header' => $sHeader,
                'response_body' => $sBody,
                'curlinfo' => curl_getinfo($curl_arr[$sAppid]),
                'curlerrorcode' => curl_errno($curl_arr[$sAppid]),
                'curlerrormsg' => curl_error($curl_arr[$sAppid]),
            ];
            curl_multi_remove_handle($master, $curl_arr[$sAppid]);
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
     * Check if the timestamp of last check is still within given ttl
     * and return true if so (= can be taken from cache) or false (= need to refresh)
     * 
     * @param array $aLastResult
     * @return bool
     */
    protected function _isResultExpired(array $aLastResult=[]): bool{
        if(!count($aLastResult)
            || !($aLastResult['result']['ts']??false)
            || !($aLastResult['result']['ttl']??false)
            || time() - $aLastResult['result']['ts'] > $aLastResult['result']['ttl']
        ){
            return true;
        }
        return false;
    }

    /**
     * Refresh outdated app data
     * 
     * 
     * @param boolean  $ForceCache  flag: use cache; default: false (=automatic selection by source and config "servicecache")
     * @return boolean
     */
    public function refreshClientData(bool $ForceCache = false): bool|array
    {
        if (!$ForceCache) {
            $ForceCache = isset($_SERVER['REQUEST_METHOD']) && ($this->_aCfg['servicecache']??false);
        }
        // if($ForceCache){
        //     return true;
        // }

        // echo "ForceCache = ".($ForceCache ? "ON" : "OFF")."<br>";

        // --- find out dated apps
        foreach($this->_oWebapps->search(
            [
                'columns' =>['lastresult', 'appid'],
            ],
        )?:[] as $aRow){
            $aResult[$aRow['appid']]=$aRow;
        }
        
        $aUrls2Refresh=[];
        $this->_data=[];

        foreach ($this->_urls as $sAppid => $sUrl){
            $aLastResult=json_decode($aResult[$sAppid]['lastresult']??'', 1)??[];
            $this->oApp->set($aLastResult);
            // if($this->_isResultExpired($aLastResult) && !$ForceCache ){
            if(!$ForceCache && $this->oApp->isOutdated()){
                $aUrls2Refresh[$sAppid] = $sUrl;
                // echo "ADD $sUrl<br>";
            } else {
                $this->_data[$sAppid] = $aLastResult;
                // echo "from DB $sUrl<br>";
            }
        }
        
        // --- fetch new data from outdated apps and store its status to database
        if (count($aUrls2Refresh)) {
            // $this->send("URLs to refresh: ". print_r($aUrls2Refresh, 1));
            $aAllHttpdata = $this->_multipleHttpGet($aUrls2Refresh);
            foreach ($aAllHttpdata as $sAppid => $aResult) {

                $this->_oWebapps->readByFields(['appid' => $sAppid]);

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
                $aJsonErrors = $this->_checkResponse($aResult['response_body']);

                $aClientData = [];
                if (count($aJsonErrors)) {

                    // no response eg on 503:
                    // load list of last checks and set its checks to unknown

                    // $aClientData["result"]=RESULT_ERROR;
                    $sError .= "- " . implode('<br>- ', $aJsonErrors) . "<br>";

                    // read last ok data
                    // $this->_oWebapps->readByFields(['appid'=>$this->_generateUrlKey($sUrl)]);
                    if ($sLastOK = $this->_oWebapps->get("lastok")) {
                        $aLastOK = json_decode($sLastOK, true);

                        unset($aLastOK["result"]);
                        foreach (['ttl', 'result', 'time'] as $delkey) {
                            if (isset($aLastOK['meta'][$delkey])) {
                                unset($aLastOK['meta'][$delkey]);
                            }
                        }
                        $aLastOK['meta']['result'] = RESULT_UNKNOWN;
                        $aLastChecks = [];
                        foreach ($aLastOK['checks']??[] as $aCheck) {
                            foreach (['value', 'result', 'time', 'count'] as $delkey) {
                                if (isset($aCheck[$delkey])) {
                                    unset($aCheck[$delkey]);
                                }
                            }
                            $aCheck['result'] = RESULT_UNKNOWN;
                            $aCheck['value'] = '?';
                            $aLastChecks[] = $aCheck;
                        }
                        // unset($aLastOK['checks']);
                        $aLastOK['checks'] = $aLastChecks;
                        // print_r($aLastOK);
                        $aClientData = $aLastOK;
                    }


                } else {

                    $aClientData = json_decode($aResult['response_body'], true);

                    // add more metadata
                    // $aClientData["result"] = $this->_generateResultArray($aClientData);
                }
                $aClientData["result"] = $this->_generateResultArray($aClientData);

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

                $aClientData["result"]["ts"] = time();
                $aClientData["result"]["url"] = $this->_urls[$sAppid];
                $aClientData["result"]["ttl"] = $iTtl ?? $this->_iTtl;
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

                // --- counters
                $aCounters = [];
                $aCounters['_responsetime'] = [
                    'title' => $this->_tr('Chart-responsetime'),
                    'visual' => 'bar',
                    'status' => $aClientData["result"]["result"],
                    'value' => floor($aResult['curlinfo']['total_time'] * 1000),
                ];

                if (isset($aClientData['checks']) && count($aClientData['checks'])) {
                    // echo '<pre>'.print_r($aClientData['checks'], 1).'</pre>';
                    foreach ($aClientData['checks'] as $aCheck) {
                        $sIdSuffix = preg_replace('/[^a-zA-Z0-9]/', '', $aCheck['name']) . '-' . md5($aCheck['name']);
                        $sTimerId = 'time-' . $sIdSuffix;
                        $aCounters[$sTimerId] = [
                            'title' => 'timer for[' . $aCheck['description'] . '] in [ms]',
                            'visual' => 'bar',
                            'status' => $aCheck['result'],
                            'value' => str_replace('ms', '', isset($aCheck['time']) ? $aCheck['time'] : '')
                        ];
                        if (isset($aCheck['count']) || (isset($aCheck['type']) && $aCheck['type'] === 'counter')) {
                            $sCounterId = 'check-' . $sIdSuffix;
                            $aCounters[$sCounterId] = [
                                'title' => $aCheck['description'],
                                'visual' => $aCheck['visual'] ?? false,
                                'status' => $aCheck['result'],
                                'value' => $aCheck['count'] ?? $aCheck['value']
                            ];
                        }
                    }
                }
                $aClientData["counters"] = $aCounters;

                // --- check counter for current result
                $rrd = new simpleRrd($sAppid);
                foreach ($aCounters as $sCounterKey => $aCounter) {
                    $rrd->setId($sCounterKey);
                    $rrd->add([
                        'status' => $aCounter['status'],
                        'value' => $aCounter['value'],
                    ]);
                }

                $LastInDB=[];
                if ($sLastWritten = $this->_oWebapps->get("lastresult")) {
                        $LastInDB = json_decode($sLastWritten, true);
                }

                // --- update result counter: how often the current result was seen?
                $iCurrentCounter=($LastInDB["result"]["resultcounter"][$aClientData["result"]["result"]]??0);
                $aResultCounter=[
                    RESULT_OK=>0,
                    RESULT_UNKNOWN=>0,
                    RESULT_WARNING=>0,
                    RESULT_ERROR=>0,
                ];
                $aResultCounter[$aClientData["result"]["result"]]=$iCurrentCounter+1;
                $aClientData["result"]["resultcounter"] = $aResultCounter;

                $this->_data[$sAppid] = $aClientData;

                $this->send(
                    ""
                    . $aResult['url']
                    . " Httpstatus=$iHttpStatus"
                    . " TTL=$iTtl"
                    . " responsetime=" . floor($aResult['curlinfo']['total_time'] * 1000) . "ms"
                    . " appstatus=" . $this->_tr('Resulttype-' . $aClientData["result"]["result"])
                    . " $sError $aResult[curlerrormsg]"
                );

                // TODO
                // Flap detection
                // via counter der Responsetime kann ich den letzten Status
                // der letzten N requests abgreifen und den bisherigen Counter
                // abbilden
                // $rrd = new simpleRrd($sAppid);
                // $rrd->setId('_responsetime');
                // $aResponses=$rrd->get(3);

                //     // -- wenn aktueller Status = RESULT_OK - vorletzter <> OK --> versenden
                //     // -- wenn aktueller Status = RESULT_OK - letzte Notifikation <> OK --> versenden
                //     // -- wenn aktueller Status <> RESULT_OK
                //     //    vorherige 3 x derselbe Status --> mit delay versenden

                //     print_r($aResponses);

                $this->oNotification->setApp($sAppid, $aClientData);
                $this->oNotification->notify();

                // store in database
                $this->_oWebapps->set("lastresult", json_encode($aClientData));
                if (($aClientData['meta']['result'] ?? false) == RESULT_OK) {
                    $this->_oWebapps->set("lastok", json_encode($aClientData));
                }
                $this->_oWebapps->save();

            }
        }
        // if(!$ForceCache){
        //     $this->_data=[];
        // }

        return true;
    }

    /**
     * Get all client data; it fetches all given urls
     * Used in service.php
     * 
     * @param boolean  $ForceCache  flag: use cache; default: false (=automatic selection by source and config "servicecache")
     * @return boolean
     */
    protected function _getClientData(bool $ForceCache = false): bool
    {
        $this->refreshClientData();

        // $this->_data = [];
        // foreach($this->_oWebapps->search(
        //     [
        //         'columns' =>['lastresult', 'appid'],
        //     ],
        // ) as $aRow){
        //     $this->_data[$aRow['appid']] = json_decode($aRow['lastresult']??[], 1);
        //     $this->_data[$aRow['appid']]["result"]["fromdb"] = true;
        // }
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
        $sAppid = $this->_generateUrlKey($sUrl);
        $this->_urls[$sAppid] = $sUrl;
        return true;
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
        return $sReturn;
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
        foreach ($this->_data as $sAppid => $aEntries) {
            $iCountApps++; // count of webapps
            $this->oApp->set($aEntries);
            if($this->oApp->status()>=0){
                $aResults[$this->oApp->status()]++; // counter by result of app
            }
            if ($this->oApp->host()) {
                $aServers[$this->oApp->host()] = true; // helper array to count hosts
            }

            // count of checks
            if (isset($this->_data[$sAppid]["result"]["summary"])) {
                $aChecks = $this->_data[$sAppid]["result"]["summary"];
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

    function getWebappLabel(): string
    {
        $sReturn = '';

        return $sReturn;
    }

    /**
     * Get all client data and final result as array
     * It returns the keys
     * - return {integer}  total status of all apps; 0 = ok ... 3 = error
     * - messages {array}  array of messages
     * - results  {array}  array of status code as key and occurcances as value
     * 
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
                'return' => RESULT_ERROR,
                'messages' => [$this->_tr('msgErr-nocheck')]
            ];
        }

        // loop over all webapps
        foreach ($this->_data as $sAppid => $aEntries) {

            // filter if a host was given
            if (
                !$sHost ||
                (isset($aEntries["result"]["host"]) && $sHost == $aEntries["result"]["host"])
            ) {

                if (
                    !isset($aEntries["result"])
                    || !isset($aEntries["checks"]) || !count($aEntries["checks"])
                ) {
                    // no value for app result or no checks = assume no response data
                    $iMaxReturn = RESULT_ERROR;
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
