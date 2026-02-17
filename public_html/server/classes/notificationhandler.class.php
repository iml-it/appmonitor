<?php

require_once 'cache.class.php';
require_once 'lang.class.php';
require_once 'dbobjects/notifications.php';
require_once 'dbobjects/webapps.php';
require_once 'time.class.php';

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
     * Last sent notification fur current app
     * 
     * Example:
     * Array
     *  (
     *     [id] => 98
     *     [timecreated] => 2025-09-11 11:02:18
     *     [timeupdated] => 
     *     [deleted] => 0
     *     [timestamp] => 1757588538
     *     [appid] => 60b1104800798cd79b694ca6f6764c15
     *     [changetype] => 2
     *     [status] => 3
     *     [message] => "..."
     *   )
     * @var array
     */
    protected array $_aLastNotification = [];
    
    /**
     * Currently fetched result of an web application
     * @var array
     */
    protected array $_aAppResult = [];

    /**
     * delay sending a notification n times based on a result value
     * @var array
     */
    protected array $_aDelayNotification = [
        RESULT_OK => 0, // 0 = OK comes immediately
        RESULT_UNKNOWN => 3, // N = other types skip n repeats of same status
        RESULT_WARNING => 3,
        RESULT_ERROR => 3
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

        // print_r($this->_aAppResult);
        $iCurrentResult=$this->_aAppResult['result']['result'] ?? -1;
        $iLastResult=$this->_aLastNotification['status'] ?? -1;

        if ($iLastResult<0) {
            $this->_iAppResultChange = CHANGETYPE_NEW;
        } else {
            $this->_iAppResultChange = $iCurrentResult == $iLastResult
                ? CHANGETYPE_NOCHANGE
                : CHANGETYPE_CHANGE
                ;
        }
        return $this->_iAppResultChange;
    }

    /**
     * Set application with its current check result
     * @param  string  $sAppId       application id
     * @param  array   $aClientData  optional: application response of a fresh request; default: read 'lastresult' column from database
     * @return boolean
     */
    public function setApp(string $sAppId, array $aClientData = []): bool
    {
        if($this->_sAppId == $sAppId){
            $this->_aAppResult = count($aClientData) ? $aClientData : $this->_aAppResult;
            return false;
        }
        $this->_sAppId = $sAppId;
        $this->_oWebapps->readByFields(['appid' => $this->_sAppId]);
        $this->_aAppResult = $aClientData;
        $this->_iAppResultChange = -1;

        $aLastNotify=$this->getLogdata(['appid'=>$this->_sAppId, 'count'=>1]);
        $this->_aLastNotification = $aLastNotify[0] ?? [];
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
        if($iChangetype == CHANGETYPE_NOCHANGE){
            return false;
        }

        $iResult = $this->_aAppResult['result']['result'] ?? RESULT_ERROR;
        $iCounter = $this->_aAppResult["result"]["resultcounter"][$iResult];
        $iDelay = $this->_aDelayNotification[$iResult];

        // echo __METHOD__." result = $iResult .. change = $iChangetype ... counter = $iCounter delay = $iDelay\n";
        if ($iCounter >= $iDelay) {
            $this->sendAllNotifications();
            return true;
        }
        return false;
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
     * Get last in database stored notification line.
     * You need to call setApp(<id>) first
     * 
     * @return array
     */
    public function getAppLastNotification(): array{
        return $this->_aLastNotification;
    }

    /**
     * Get last in database stored resultset of an application check
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
     * Get current log data and filter them.
     * When using 'since' or 'age' the log entry before starting range will be searched.
     * 
     * @param array   $aFilter  filter with possible keys timestamp|changetype|status|appid|message (see addLogitem())
     *                          - mode  {string} "last" = newest entries first
     *                          - count {integer} number of entries to return
     *                          - page  {integer} page number to show; default: 1
     *                          - where {string}  where clause
     *                          - since {string}  unix timestamp
     *                          - age   {int}     entries of the last N days
     * @return array
     */
    public function getLogdata(array $aFilter = []): array
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
        if ($aFilter['age']??false){
            $aFilter['since'] = date('U', strtotime("-{$aFilter['age']} days", time()));
        }

        if ($aFilter['since']??false){
            // detect the last logentry before given range
            $aSearchParams['tsfrom'] = $aFilter['since'];

            $aBefore=$this->_oNotifications->search(
                [
                    'columns' => '*',
                    'where' => ($aFilter['where'] ? $aFilter['where'] . " AND " : "") . "`timestamp` <= :tsfrom",
                    'order' => ['timestamp DESC'],
                    'limit' => "0,1",
                ],
                $aSearchParams
            );

            $aFilter['where'].=($aFilter['where'] ? " AND " : "") . "`timestamp` >= :tsfrom";
            $aSearchParams['tsfrom'] = $aBefore[0]['timestamp']??$aFilter['since'];
            unset($aFilter['since']);
        }

        // print_r($aFilter);
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
    // last notifcation of all apps (for overview page)
    // ----------------------------------------------------------------------

    public function getLastNotificationOfEachApp(): array{
        $aReturn=[];
        $aResult=$this->_oNotifications->makeQuery(
            'SELECT t1.* FROM objnotifications t1
            JOIN (SELECT appid, MAX(timestamp) timestamp FROM objnotifications GROUP BY appid) t2
                ON t1.appid = t2.appid AND t1.timestamp = t2.timestamp;'
            
        );
        foreach($aResult as $aItem){
            $aReturn[$aItem['appid']]=$aItem;
        }
        return $aReturn;
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
        if ($this->_iAppResultChange === -1) {
            $this->_detectChangetype();
        }
        $sMiss = '-';

        // @see notify()

        // current request
        if($this->_aAppResult){
            $aCurrent = $this->_aAppResult;
        } else {
            $aCurrent = json_decode($this->_oWebapps->get('lastresult')??[], 1);
            if(!is_array($aCurrent)){
                $aCurrent=[];
            }

        }

        // last OK state
        $aLastOK = json_decode($this->_oWebapps->get('lastok')??'', 1);

        // last notification message
        $aLastNotify = $this->getAppLastNotification();

        $aReplace = [
            '__APPID__' => $this->_sAppId ?: $sMiss,
            '__CHANGE__' => isset($this->_iAppResultChange) ? $this->_tr('changetype-' . $this->_iAppResultChange) : $sMiss,
            '__TIME__' => date("Y-m-d H:i:s", (time())),

            '__URL__' => $aCurrent['result']['url'] ?? (
                $aLastOK['result']['url'] ?? $sMiss
            ),
            '__HOST__' => $aCurrent['result']['host'] ?? $sMiss,
            '__WEBSITE__' => $aCurrent['result']['website'] ?? $sMiss,

            '__RESULT__' => isset($aCurrent['result']['result']) ? $this->_tr('Resulttype-' . $aCurrent['result']['result']) : $sMiss,
            '__ERROR__' => $aCurrent['result']['error'] ?? '',

            '__HEADER__' => $aCurrent['result']['header'] ?? $sMiss,

            '__LAST-TIME__' => $aLastNotify['timestamp']??false 
                ? date("Y-m-d H:i:s", $aLastNotify['timestamp']) : $sMiss,

            '__LAST-RESULT__' => isset($aLastNotify['status']) ? $this->_tr('Resulttype-' . $aLastNotify['status']) : $sMiss,
            '__DELTA-TIME__' => $aLastNotify['timestamp']??false 
                ? time::hrDelta(time() - $aLastNotify['timestamp'])
                : $sMiss,
            '__CURLERROR__' => $aCurrent['result']['curlerrormsg']??false
                ? sprintf($this->_tr('Curl-error'), $aCurrent['result']['curlerrormsg'], $aCurrent['result']['curlerrorcode'])
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
                    $aReplace['__RESULT__'] = '<span class="result-' . $aCurrent['result']['result'] . '">' . $aReplace['__RESULT__'] . '</span>';
                }
                break;

            default:
                # code...
                break;
        }

        if (isset($aCurrent['checks']) && count($aCurrent['checks'])) {

            // force sortorder in notifications - one key for each result ... 3 is error .. 0 is OK
            $aSortedChecks = [];
            for ($i = 3; $i >= 0; $i--) {
                $aSortedChecks[$i] = '';
            }
            foreach ($aCurrent['checks'] as $aCheck) {
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
        $this->addLogitem($this->_iAppResultChange, $iResult, $this->_sAppId, $sLogMessage);

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

        // take data from web app ... meta -> notifications
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
