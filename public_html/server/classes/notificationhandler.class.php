<?php

require_once 'cache.class.php';
require_once 'lang.class.php';
require_once 'dbobjects/notifications.php';
require_once 'dbobjects/webapps.php';

define("CHANGETYPE_NOCHANGE", 0);
define("CHANGETYPE_NEW", 1);
define("CHANGETYPE_CHANGE", 2);
define("CHANGETYPE_DELETE", 3);

if (!defined('RESULT_OK')) {
    define("RESULT_OK", 0);
    define("RESULT_UNKNOWN", 1);
    define("RESULT_WARNING", 2);
    define("RESULT_ERROR", 3);
}

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
 * notificationhandler
 *
 * @author hahn
 * 
 * 2024-07-17  axel.hahn@unibe.ch  php 8 only: use typed variables
 * 2024-11-06  axel.hahn@unibe.ch  update html email output
 * 2025-02-21  axel.hahn@unibe.ch  use sqlite as storage
 */
class notificationhandler
{
    /**
     * Number of maximum of log entries for application notifications
     * @var int
     */
    protected int $_iMaxLogentries = 5000;

    /**
     * logdata for detected changes and sent notifications
     * @var array 
     */
    protected array $_aLog = [];

    /**
     * language object
     * @var lang
     */
    protected lang $oLang;

    /**
     * Array of notification options (from config)
     * @var array
     */
    protected array $_aNotificationOptions = [];

    /**
     * Server url of apmonitor instance to build an url to app specific pagees
     * @var string
     */
    protected string $_sServerurl = '';

    /**
     * database object for sent notifications
     * @var objnotifications
     */
    protected objnotifications $_oNotifications;

    /**
     * database object for webapps (last status, last OK)
     * @var objwebapps
     */
    protected objwebapps $_oWebapps;

    // ------------------------------------------------------------------
    // data of the current app 
    // ------------------------------------------------------------------
    /**
     * Current app id
     * @var string
     */
    protected string $_sAppId = '';

    /**
     * Type of change for a result status ... one of
     * CHANGETYPE_NOCHANGE, CHANGETYPE_NEW, CHANGETYPE_CHANGE, CHANGETYPE_DELETE
     * @var integer
     */
    protected int $_iAppResultChange = -1;

    /**
     * Currently fetched result of an web application
     * @var array
     */
    protected array $_aAppResult = [];

    /**
     * Last fetched result of an web application
     * @var array
     */
    protected array $_aAppLastResult = [];

    /**
     * delay sending a notification n times based on a result value
     * @var array
     */
    protected array $_aDelayNotification = [
        RESULT_OK => 0, // 0 = OK comes immediately
        RESULT_UNKNOWN => 2, // N = other types skip n repeats of same status
        RESULT_WARNING => 2,
        RESULT_ERROR => 2
    ];

    /**
     * plugin directory for notification types
     * @var string
     */
    protected string $_sPluginDir = __DIR__ . '/../plugins/notification';

    // ----------------------------------------------------------------------
    // __construct
    // ----------------------------------------------------------------------

    /**
     * init
     * @param array  $aOptions  options array with the keys
     *                          - {string} lang       language of the GUI
     *                          - {string} serverurl  base url of the web app to build an url to an app specific page
     *                          - {string} notifications  appmionitor config settings in notification settings (for sleeptime and messages)
     * @global object $oDB      database connection
     */
    public function __construct(array $aOptions = [])
    {
        global $oDB;
        if (isset($aOptions['lang'])) {
            $this->_loadLangTexts($aOptions['lang']);
        }
        if (isset($aOptions['serverurl'])) {
            $this->_sServerurl = $aOptions['serverurl'];
        }

        $this->_aNotificationOptions = isset($aOptions['notifications']) ? $aOptions['notifications'] : false;

        $this->_oNotifications = new objnotifications($oDB);
        $this->_oWebapps = new objwebapps($oDB);

    }

    // ----------------------------------------------------------------------
    // protected functions - handle languages texts
    // ----------------------------------------------------------------------

    /*
    protected function _initMessenger($aOptions){
        if (!isset($aOptions['notifications'])){
            return false;
        }
        
        $this->_oMessenger = isset($aOptions['notifications'])
                ? new messenger($aOptions['notifications'])
                : false;
    }
    */

    /**
     * Load language texts
     * @param string  $sLang  language; i.e. "en-en"
     * @return boolean
     */
    protected function _loadLangTexts(string $sLang): bool
    {
        $this->oLang = new lang($sLang);
        return true;
    }
    /**
     * Translate a text with language file inside section "notifications"
     * @param string $sWord
     * @return string
     */
    protected function _tr(string $sWord): string
    {
        return $this->oLang->tr($sWord, ['notifications']);
    }

    // ----------------------------------------------------------------------
    // protected functions - handle cache of application checkdata
    // ----------------------------------------------------------------------

    /**
     * Get current or last stored client notification receivers
     * this method also stores current notification data on change.
     * This information is cached if client status has no data (i.e. timeout)
     * and we want to inform 
     * 
     * @return array
     */
    protected function _getAppNotifications(): array
    {
        return $this->_aAppResult['meta']['notifications'] ?? [];
    }

    /**
     * Check if a defined sleep time was reached.
     * It returns false if no sleep time is defined.
     * It returns the 1st matching regex if a match was found.
     * @return boolean|string
     */
    public function isSleeptime(): bool|string
    {
        if (isset($this->_aNotificationOptions['sleeptimes']) && is_array($this->_aNotificationOptions['sleeptimes']) && count($this->_aNotificationOptions['sleeptimes'])) {
            $sNow = date("Y-m-d D H:i");
            foreach ($this->_aNotificationOptions['sleeptimes'] as $sRegex) {
                if (preg_match($sRegex, $sNow)) {
                    return $sRegex;
                }
            }
        }
        return false;
    }

    /**
     * Save last app status data to conpare with the item of the next time
     * @return boolean
     */
    protected function _saveAppResult()
    {
        $this->_oWebapps->set("lastresult", json_encode($this->_aAppResult));

        if (($this->_aAppResult['meta']['result'] ?? RESULT_ERROR) == RESULT_OK) {
            $this->_oWebapps->set("lastok", json_encode($this->_aAppResult));
        }

        return $this->_oWebapps->save();
    }


    // ----------------------------------------------------------------------
    // public functions - check changes (create/ update) and delete appdata
    // ----------------------------------------------------------------------


    /**
     * helper function: get type of change between current and last state
     * It returns one of CHANGETYPE_NEW | CHANGETYPE_CHANGE | CHANGETYPE_NOCHANGE
     * the value is stored in $this->_iAppResultChange
     * @return integer
     */
    protected function _detectChangetype($aCompareItem = false): int
    {
        if (!$this->_sAppId) {
            die("ERROR: " . __METHOD__ . " no application was initialized ... use setApp() first");
        }
        if (!$aCompareItem) {
            $aCompareItem = $this->_aAppLastResult ? $this->_aAppLastResult : false;
        }
        if (!$aCompareItem || !is_array($aCompareItem)) {
            $this->_iAppResultChange = CHANGETYPE_NEW;
        } else {
            if (
                isset($aCompareItem['result']['result']) && isset($this->_aAppResult['result']['result'])
                && $aCompareItem['result']['result'] !== $this->_aAppResult['result']['result']
            ) {
                $this->_iAppResultChange = CHANGETYPE_CHANGE;
            } else {
                $this->_iAppResultChange = CHANGETYPE_NOCHANGE;
            }
        }
        return $this->_iAppResultChange;
    }


    /**
     * Set application with its current check result
     * @param string  $sAppId  application id
     * @return boolean
     */
    public function setApp(string $sAppId): bool
    {
        $this->_sAppId = $sAppId;
        $this->_oWebapps->readByFields(['appid' => $this->_sAppId]);

        $this->_aAppResult = $this->getAppResult();
        $this->_iAppResultChange = -1;
        $this->_aAppLastResult = $this->getAppLastResult();
        // echo "DEBUG: ".__METHOD__ . " current data = <pre>".print_r($this->_aAppResult, 1)."</pre>";
        return true;
    }

    /**
     * Detect if a notification is needed.
     * It returns false if a sleep time was detected. Othwerwise it returns true.
     * 
     * @return boolean
     */
    public function notify(): bool
    {
        if (!$this->_sAppId) {
            die("ERROR: " . __METHOD__ . " no application was initialized ... use setApp() first");
        }
        if ($this->isSleeptime()) {
            return false;
        }
        $iChangetype = $this->_detectChangetype();
        $iResult = $this->_aAppResult['result']['result'] ?? RESULT_ERROR;

        // get the highest value for a delay
        $iMaxDelay = max(array_values($this->_aDelayNotification));

        $bDoNotify = false;
        switch ($iChangetype) {
            case CHANGETYPE_NOCHANGE:
                // increase counter
                $iCounter = (isset($this->_aAppLastResult['result']['counter']) ? $this->_aAppLastResult['result']['counter'] + 1 : $iMaxDelay + 1);
                $this->_aAppResult['laststatus'] = isset($this->_aAppLastResult['laststatus']) ? $this->_aAppLastResult['laststatus'] : false;
                break;

            case CHANGETYPE_CHANGE:
                // store last different application status - @see getMessageReplacements
                $this->_aAppResult['laststatus'] = $this->_aAppLastResult;
                // reset counter
                $iCounter = 0;
                break;

            case CHANGETYPE_NEW:
                // reset counter
                $iCounter = 0;
                $bDoNotify = true;
                break;

            default:
                break;
        }

        // setting $this->_aAppResult['laststatus'] above can create recursion
        if (isset($this->_aAppResult['laststatus']['laststatus'])) {
            unset($this->_aAppResult['laststatus']['laststatus']);
            $this->_saveAppResult();
        }

        // handle delayed notification:
        // actions as long counter is lower max delay only
        if ($iCounter <= $iMaxDelay) {

            $this->_aAppResult['result']['counter'] = $iCounter;
            $this->_saveAppResult();

            // not needed for CHANGETYPE_NEW: detect if count of repeats
            // with the same current status reached the notification delay value
            if (!$bDoNotify && $iCounter === $this->_aDelayNotification[$iResult]) {

                $iLastCounter = isset($this->_aAppResult['laststatus']['result']['counter'])
                    ? $this->_aAppResult['laststatus']['result']['counter']
                    : -1;
                $iLastResult = isset($this->_aAppResult['laststatus']['result']['result'])
                    ? $this->_aAppResult['laststatus']['result']['result']
                    : -1;

                if ($iLastResult >= 0 && $iLastCounter >= 0 && $iLastCounter >= $this->_aDelayNotification[$iLastResult]) {
                    $bDoNotify = true;
                }
            }
            /*
            IDEA: track skipped notifications

            if (!$bDoNotify && $iCounter<$this->_aDelayNotification[$iResult]){
                // echo "DEBUG: ".__METHOD__." skip notification for delayed sending ...\n";
                $aTexts=$this->getMessageReplacements();
                $this->addLogitem($this->_iAppResultChange, $iResult, $this->_sAppId, $sLogMessage, $this->_aAppResult);
            }
            */
        }
        if ($bDoNotify) {
            // on delayed sending: overwrite change type to send correct information
            if ($this->_iAppResultChange == CHANGETYPE_NOCHANGE) {
                $this->_iAppResultChange = CHANGETYPE_CHANGE;
            }
            $this->sendAllNotifications();
        }
        return true;
    }

    /**
     * Delete application: this method triggers deletion of its notification 
     * data and last result
     * Triggered by apmonitor-server class - actionDeleteUrl(string $sUrl)
     * 
     * @param string  $sAppId  app id
     * @return boolean
     */
    public function deleteApp(string $sAppId): bool
    {
        $this->setApp($sAppId);
        $this->_iAppResultChange = CHANGETYPE_DELETE;

        // trigger notification
        $this->sendAllNotifications();

        // finally: delete webapp data in db
        $this->_oWebapps->delete();
        return true;
    }

    // ----------------------------------------------------------------------
    // functions for notifcation log
    // ----------------------------------------------------------------------

    /**
     * Add a new item in notification log. It returns the result of the write
     * action of the log data.
     * 
     * @param integer  $iChangetype  type of change; see CHANGETYPE_ constants
     * @param integer  $sNewstatus   resultcode; see RESULT_ constants
     * @param string   $sAppId       application id
     * @param string   $sMessage     message text
     * @param array    $aResult      response ($this->_aAppResult) REMOVED
     * @return bool
     */
    protected function addLogitem(int $iChangetype, string $sNewstatus, string $sAppId, string $sMessage/*, array $aResult*/): bool|int
    {
        $this->_oNotifications->new();
        $this->_oNotifications->setItem([
            'timestamp' => time(),
            'changetype' => $iChangetype,
            'status' => $sNewstatus,
            'appid' => $sAppId,
            'message' => $sMessage,
            // 'result' => json_encode($aResult),
        ]);
        return $this->_oNotifications->create();
    }

    /**
     * Get count of notification log entries
     * @return int
     */
    public function countLogitems(): int
    {
        return $this->_oNotifications->count() ?? 0;
    }

    /**
     * Get current result from cache using a shared cache object 
     * that was written by appmonitor-server class
     * @return array
     */
    public function getAppResult(): array
    {
        $oCache = new AhCache("appmonitor-server", $this->_sAppId);

        // in the cache is an array - but cache->read() is general and can return any data type
        $aData = $oCache->read();
        return is_array($aData) ? $aData : [];
    }

    /**
     * Get 2nd last resultset of an application
     * @return array
     */
    public function getAppLastResult()
    {
        if (!$sJson = $this->_oWebapps->get("lastresult")) {
            return [];
        }
        $aData = json_decode($sJson, 1);
        return is_array($aData) ? $aData : [];
    }

    /**
     * Get current log data and filter them
     * @param array   $aFilter  filter with possible keys timestamp|changetype|status|appid|message (see addLogitem())
     *                          - mode  {string} "last" = newest entries first
     *                          - count {integer} number of entries to return
     *                          - page  {integer}
     * @param integer $iLimit   set a maximum of log entries
     * @param boolean $bRsort   flag to reverse sort logs; default is true (=newest entry first)
     * @return array
     */
    public function getLogdata(array $aFilter = [], int $iLimit = 0, bool $bRsort = true): array
    {

        $aFilter['mode'] ??= 'last';
        $aFilter['count'] ??= 25;
        $aFilter['page'] ??= 1;
        $aFilter['where'] ??= '';

        $aSearchParams = [];
        if ($aFilter['appid'] ?? false) {
            $aFilter['where'] = '`appid` = :appid';
            $aSearchParams = ['appid' => $aFilter['appid']];
        }

        $aData = $this->_oNotifications->search(
            [
                'columns' => '*',
                'where' => $aFilter['where'],
                'order' => ['timestamp DESC'],
                'limit' => (($aFilter['page'] - 1) * $aFilter['count']) . ", " . $aFilter['count'],
            ],
            $aSearchParams
        );

        return is_array($aData) ? $aData : [];

    }

    // ----------------------------------------------------------------------
    // functions for notifcation 
    // ----------------------------------------------------------------------

    /**
     * Helper function: replace based on str_replace
     * @param array  $aReplace  key value array; keys=search; value= replace
     * @param string $sString
     * @return string
     */
    protected function _makeReplace(array $aReplace, string $sString): string
    {
        return str_replace(array_keys($aReplace), array_values($aReplace), $sString);
    }

    /**
     * Helper function: get the array with all current replacements in message 
     * texts with key = placeholder and value = replacement
     * 
     * @return array
     */
    public function getMessageReplacements(): array
    {
        $sMode = 'html';
        /*
                [result] => Array
                (
                    [ts] => 1529672793
                    [result] => 3
                    [ttl] => 300
                    [url] => http://example.com/appmonitor/
                    [header] => 
                    [headerarray] => 
                    [httpstatus] => 
                    [error] => Http Request to appmonitor failed: host or service is unreachable.
                    [fromcache] => 
                )

         */
        if ($this->_iAppResultChange === -1) {
            $this->_detectChangetype();
        }
        $sMiss = '-';

        // @see notify()
        $aCompare = isset($this->_aAppResult['laststatus']) ? $this->_aAppResult['laststatus'] : [];
        $aReplace = [
            '__APPID__' => $this->_sAppId,
            '__CHANGE__' => isset($this->_iAppResultChange) ? $this->_tr('changetype-' . $this->_iAppResultChange) : $sMiss,
            '__TIME__' => date("Y-m-d H:i:s", (time())),
            '__URL__' => isset($this->_aAppResult['result']['url']) ? $this->_aAppResult['result']['url']
                : (isset($aCompare['result']['url']) ? $aCompare['result']['url'] : $sMiss),
            '__HOST__' => isset($this->_aAppResult['result']['host']) ? $this->_aAppResult['result']['host'] : $sMiss,
            '__WEBSITE__' => isset($this->_aAppResult['result']['website']) ? $this->_aAppResult['result']['website'] : $sMiss,

            '__RESULT__' => isset($this->_aAppResult['result']['result']) ? $this->_tr('Resulttype-' . $this->_aAppResult['result']['result']) : $sMiss,
            '__ERROR__' => isset($this->_aAppResult['result']['error']) && $this->_aAppResult['result']['error']
                ? $this->_aAppResult['result']['error'] : '',

            '__HEADER__' => isset($this->_aAppResult['result']['header']) ? $this->_aAppResult['result']['header'] : $sMiss,

            '__LAST-TIME__' => isset($aCompare['result']['ts']) ? date("Y-m-d H:i:s", $aCompare['result']['ts']) : $sMiss,
            '__LAST-RESULT__' => isset($aCompare['result']['result']) ? $this->_tr('Resulttype-' . $aCompare['result']['result']) : $sMiss,
            '__DELTA-TIME__' => isset($aCompare['result']['ts']) ?
                round((time() - $aCompare['result']['ts']) / 60) . " min "
                . "(" . round((time() - $aCompare['result']['ts']) / 60 / 60 * 4) / 4 . " h)"
                : $sMiss,
            '__CURLERROR__' => isset($this->_aAppResult['result']['curlerrormsg']) && $this->_aAppResult['result']['curlerrormsg']
                ? sprintf($this->_tr('Curl-error'), $this->_aAppResult['result']['curlerrormsg'], $this->_aAppResult['result']['curlerrorcode'])
                : '',

        ];
        if ($this->_sServerurl) {
            $aReplace['__MONITORURL__'] = $this->_sServerurl . '#divweb-' . $this->_sAppId;
        }
        // echo '<pre>'.print_r($this->_aAppResult['checks'], 1).'</pre>';
        switch ($sMode) {
            case 'html':
                $aReplace['__ERROR__'] = '<span class="error">' . $aReplace['__ERROR__'] . '</span>';
                $aReplace['__CURLERROR__'] = '<span class="error">' . $aReplace['__CURLERROR__'] . '</span>';
                if ($aReplace['__RESULT__'] != $sMiss) {
                    $aReplace['__RESULT__'] = '<span class="result-' . $this->_aAppResult['result']['result'] . '">' . $aReplace['__RESULT__'] . '</span>';
                }
                break;

            default:
                # code...
                break;
        }

        if (isset($this->_aAppResult['checks']) && count($this->_aAppResult['checks'])) {

            // force sortorder in notifications - one key for each result ... 3 is error .. 0 is OK
            $aSortedChecks = [];
            for ($i = 3; $i >= 0; $i--) {
                $aSortedChecks[$i] = '';
            }
            foreach ($this->_aAppResult['checks'] as $aCheck) {
                $iResult = $aCheck['result'];
                $aSortedChecks[$iResult] .= "<br>\n<br>\n"
                    . '----- <strong>' . $aCheck['name'] . '</strong> (' . $aCheck['description'] . ")<br>\n"
                    . $aCheck['value'] . "<br>\n"
                    . '<span class="result-' . $aCheck['result'] . '">' . $this->_tr('Resulttype-' . $aCheck['result']) . '</span>';
            }
            $aReplace['__CHECKS__'] = implode("", $aSortedChecks);
        } else {
            $aReplace['__CHECKS__'] = html_entity_decode($this->_tr('msgErr-missing-section-checks'));
        }
        return $aReplace;
    }

    /**
     * Helper function: generate message text frem template based on type of
     * change, its template and the values of check data
     * 
     * @param string $sMessageId  one of changetype-[N].logmessage | changetype-[N].email.message | email.subject
     * @return string
     */
    public function getReplacedMessage($sMessageId): string
    {
        $sTemplate = isset($this->_aNotificationOptions['messages'][$sMessageId]) && $this->_aNotificationOptions['messages'][$sMessageId]
            ? $this->_aNotificationOptions['messages'][$sMessageId]
            : $this->_tr($sMessageId);
        // $sTemplate=$this->_tr($sMessageId);
        return $this->_makeReplace($this->getMessageReplacements(), $sTemplate);
    }

    /**
     * Write log entry and send notifications with all found notification plugins
     * It returns false there is no change in the app.
     * @return boolean
     */
    protected function sendAllNotifications(): bool
    {
        if ($this->_iAppResultChange === -1) {
            die("ERROR: " . __METHOD__ . " failed to detect change type - or app was not initialized.");
            // return false;
        }

        // take template for log message and current result type
        $sLogMessage = $this->getReplacedMessage('changetype-' . $this->_iAppResultChange . '.logmessage');

        // override result if an app was deleted: 
        // - use current result, if it exists
        // - use RESULT_UNKNOWN if action was delete or result does not exist
        $iResult = ($this->_iAppResultChange == CHANGETYPE_DELETE)
            ? RESULT_UNKNOWN
            : (isset($this->_aAppResult['result']['result'])
                ? $this->_aAppResult['result']['result']
                : RESULT_UNKNOWN
            );

        // echo "DEBUG:".__METHOD__." add log an sending messages - $sLogMessage\n";
        $this->addLogitem($this->_iAppResultChange, $iResult, $this->_sAppId, $sLogMessage, $this->_aAppResult);

        $sMessage = $this->getReplacedMessage('changetype-' . $this->_iAppResultChange . '.email.message');
        foreach ($this->getPlugins() as $sPlugin) {

            // get plugin specific receivers
            $aTo = array_values($this->getAppNotificationdata($sPlugin));

            if (count($aTo)) {
                $aOptions = [
                    '__plugin__' => $sPlugin,
                    'from' => (isset($this->_aNotificationOptions['from'][$sPlugin]) && $this->_aNotificationOptions['from'][$sPlugin])
                        ? $this->_aNotificationOptions['from'][$sPlugin]
                        : false,
                    'to' => $aTo,
                    'important' => true,
                    'subject' => strip_tags($this->getReplacedMessage('changetype-' . $this->_iAppResultChange . '.email.subject')),
                    'message' => strip_tags(str_replace('<br>', "\n", $sMessage)),
                    'htmlmessage' => '
                        <style>
                            body{background: #f8f8f8; font-size: 1.1em; font-family: Arial, Helvetica, sans-serif; text-align: center;}
                            body>div{background: #fff; margin: 1em auto; border: #eee 2px solid; padding: 0; max-width: 1000px; text-align: left;}
                            body>div>div{padding: 1em; }
                            h1{background: #eee; border-bottom: 2px solid #800; color: #666; font-size: 130%; padding: 0.5em; margin: 0 0 1em 0;}
                            h2{color: #888; padding: 0em; margin: 0 0 1em 0;}
                            .footer{background: #f8f8f8; padding: 0.5em; margin-top: 3em; text-align: right;}
                            .result-0{color: green;  background: #dfd; }
                            .result-1{color: purple; background: #fdf; }
                            .result-2{color: #a60; background: #fec; }
                            .result-3, .error{color: #c00; background: #fdd; }
                        </style>
                        <h1>IML Appmonitor</h1>
                        <div>
                        ' . $sMessage
                        . '<br><br><div class="footer"><strong>IML Appmonitor</strong> | GNU GPL 3.0 | Source <a href="https://github.com/iml-it/appmonitor">Github</a></div>'
                        . '</div>'
                    ,
                ];
                // $sSendMethod="send_$sPlugin";
                // $sSendMethod($aOptions);

                $sClassname = $sPlugin . "Notification"; // eg. "emailNotification"
                $oPlugin = new $sClassname;
                $oPlugin::send($aOptions);
            }
        }

        return true;
    }

    /**
     * Get an array with notification plugins
     * It is a list of basenames in the plugin directory server/plugins/notification/*.php
     * Additionally its functions will be included to be used in sendAllNotifications
     * @return array
     */
    function getPlugins(): array
    {
        $aReturn = [];
        foreach (glob($this->_sPluginDir . '/*.php') as $sPlugin) {
            $aReturn[] = str_replace('.php', '', basename($sPlugin));
            include_once($sPlugin);
        }
        return $aReturn;
    }

    /**
     * Get array with notification data of an app
     * taken from check result meta -> notifications merged with server config
     * 
     * @param string  $sType  optional: type email|slack; defailt: false (=return all keys)
     * @return array
     */
    public function getAppNotificationdata(string $sType = ''): array
    {

        $aMergeMeta = [];
        $aArray_keys = $sType ? [$sType] : array_keys($this->_aNotificationOptions);

        // server side notifications:
        // echo '<pre>'.print_r($this->_aNotificationOptions, 1).'</pre>';

        // got from client
        $aClientNotifications = $this->_getAppNotifications();
        // echo '<pre>'.print_r($aClientNotifications, 1).'</pre>';


        // take data from web app ... meta -> notifications
        // $aMergeMeta=isset($this->_aAppLastResult['meta']['notifications']) ? $this->_aAppLastResult['meta']['notifications'] : [];
        foreach ($aArray_keys as $sNotificationType) {
            // echo "DEBUG: $sNotificationType\n<pre>" . print_r($aClientNotifications[$sNotificationType], 1) . '</pre>';
            if (isset($aClientNotifications[$sNotificationType]) && count($aClientNotifications[$sNotificationType])) {
                foreach ($aClientNotifications[$sNotificationType] as $sKey => $Value) {
                    if (is_int($sKey)) {
                        $aMergeMeta[$sNotificationType][] = $Value;
                    } else {
                        $aMergeMeta[$sNotificationType][$sKey] = $Value;
                    }
                }
            }
            if (isset($this->_aNotificationOptions[$sNotificationType]) && is_array($this->_aNotificationOptions[$sNotificationType])) {
                foreach ($this->_aNotificationOptions[$sNotificationType] as $sKey => $Value) {
                    if (is_int($sKey)) {
                        $aMergeMeta[$sNotificationType][] = $Value;
                    } else {
                        $aMergeMeta[$sNotificationType][$sKey] = $Value;
                    }
                }
            }
        }
        return $sType
            ? (isset($aMergeMeta[$sType]) ? $aMergeMeta[$sType] : [])
            : $aMergeMeta;
    }
}
