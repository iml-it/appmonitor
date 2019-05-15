<?php

require_once 'cache.class.php';
require_once 'lang.class.php';

define("CHANGETYPE_NOCHANGE", 0);
define("CHANGETYPE_NEW", 1);
define("CHANGETYPE_CHANGE", 2);
define("CHANGETYPE_DELETE", 3);


define("RESULT_OK", 0);
define("RESULT_UNKNOWN", 1);
define("RESULT_WARNING", 2);
define("RESULT_ERROR", 3);


/**
 * notificationhandler
 *
 * @author hahn
 */
class notificationhandler {

    protected $_sCacheIdPrefix="notificationhandler";
    protected $_iMaxLogentries=5000;
    
    /**
     * logdata for detected changes and sent notifications
     * @var array 
     */
    protected $_aLog = false;
    
    /**
     * language texts
     * @var object
     */
    protected $oLang = false;
    
    protected $_aNotificationOptions=false;
    protected $_sServerurl=false;
    
    // ------------------------------------------------------------------
    // data of the current app 
    // ------------------------------------------------------------------
    protected $_sAppId=false;
    protected $_iAppResultChange=false;
    protected $_aAppResult=false;
    protected $_aAppLastResult=false;

    // ----------------------------------------------------------------------
    // __construct
    // ----------------------------------------------------------------------

    /**
     * init
     * @param array  $aOptions  options array with the keys
     *                          - {string} lang       language of the GUI
     *                          - {string} serverurl  base url of the web app to build an url to an app specific page
     *                          - {string} notifications  appmionitor config settings in notification settings (for sleeptime and messages)
     * @return boolean
     */
    public function __construct($aOptions=array()) {
        if(isset($aOptions['lang'])){
            $this->_loadLangTexts($aOptions['lang']);
        }
        if(isset($aOptions['serverurl'])){
            $this->_sServerurl=$aOptions['serverurl'];
        }
        
        $this->_aNotificationOptions = isset($aOptions['notifications']) ? $aOptions['notifications'] : false;
        
        return true;
    }
    // ----------------------------------------------------------------------
    // protected functions - handle languages texts
    // ----------------------------------------------------------------------

    protected function _initMessenger($aOptions){
        if (!isset($aOptions['notifications'])){
            return false;
        }
        
        $this->_oMessenger = isset($aOptions['notifications'])
                ? new messenger($aOptions['notifications'])
                : false;
    }
    
    /**
     * load language texts
     * 
     * @param string  $sLang  language; i.e. "en-en"
     * @return type
     */
    protected function _loadLangTexts($sLang) {
        return $this->oLang = new lang($sLang);
    }
    /**
     * translate a text with language file inside section "notifications"
     * 
     * @param string $sWord
     * @return string
     */
    protected function _tr($sWord) {
        return $this->oLang->tr($sWord, array('notifications'));
    }

    // ----------------------------------------------------------------------
    // protected functions - handle cache of application checkdata
    // ----------------------------------------------------------------------
    
    /**
     * save last app status data to conpare with the next time
     * 
     * @param string $sAppId   of webapp (url or key)
     * @param array  $aData  data
     * @return boolean
     */
    protected function _deleteAppLastResult(){
        $oCache=new AhCache($this->_sCacheIdPrefix."-app", $this->_sAppId);
        $oCache->delete();
        $oCache=new AhCache($this->_sCacheIdPrefix."-notify", $this->_sAppId);
        $oCache->delete();
        return true;
    }

    /**
     * get current or last stored client notification data
     * this method also stores current notification data on change
     * 
     * @return array
     */
    protected function _getAppNotifications(){
        $oCache=new AhCache($this->_sCacheIdPrefix."-notify", $this->_sAppId);
        $aCached=$oCache->read();
        if (isset($this->_aAppResult['meta']['notifications']) && $aCached!==$this->_aAppResult['meta']['notifications']){
            $oCache->write($this->_aAppResult['meta']['notifications']);
            return $this->_aAppResult['meta']['notifications'];
        } else {
            return $aCached;
        }
    }

    /**
     * check if a defined sleept time was reached
     * @return boolean
     */
    public function isSleeptime(){
        if(isset($this->_aNotificationOptions['sleeptimes']) && is_array($this->_aNotificationOptions['sleeptimes']) && count($this->_aNotificationOptions['sleeptimes'])){
            $sNow = date("Y-m-d D H:i");
            foreach($this->_aNotificationOptions['sleeptimes'] as $sRegex){
                if (preg_match($sRegex, $sNow)) {
                    return $sRegex;
                }                
            }
        }
        // echo '<pre>'.print_r($this->_aNotificationOptions, 1).'</pre>';
        return false;
    }
    
    /**
     * save last app status data to conpare with the next time
     * 
     * @param string $sAppId   of webapp (url or key)
     * @param array  $aData  data
     * @return boolean
     */
    protected function _saveAppResult(){
        $oCache=new AhCache($this->_sCacheIdPrefix."-app", $this->_sAppId);
        return $oCache->write($this->_aAppResult);
        return false;
    }
    
    
    // ----------------------------------------------------------------------
    // public functions - check changes (create/ update) and delete appdata
    // ----------------------------------------------------------------------
    

    /**
     * helper function: get type of change between current and last state
     * It returns one of CHANGETYPE_NEW | CHANGETYPE_CHANGE | CHANGETYPE_NOCHANGE
     * 
     * @return integer
     */
    protected function _detectChangetype(){
        if(!$this->_sAppId){
            die("ERROR: ".__METHOD__." no application was initialized ... use setApp() first");
        }
        
        if(!$this->_aAppLastResult || !is_array($this->_aAppLastResult)){
            $this->_iAppResultChange=CHANGETYPE_NEW;
        } else {
            if(isset($this->_aAppLastResult['result']['result']) && isset($this->_aAppResult['result']['result'])
                && $this->_aAppLastResult['result']['result']!==$this->_aAppResult['result']['result']
            ){
                $this->_iAppResultChange=CHANGETYPE_CHANGE;
            } else {
                $this->_iAppResultChange=CHANGETYPE_NOCHANGE;
            }
        }
        return $this->_iAppResultChange;
    }
    
    
    /**
     * set application with its current check result
     * @param string  $sAppId  application id
     * @param array   $aData   data of current check; can be false if you want to access last status
     * @return boolean
     */
    public function setApp($sAppId){
        $this->_sAppId=$sAppId;
        $this->_aAppResult=$this->getAppResult();
        $this->_iAppResultChange=false;
        $this->_aAppLastResult=$this->getAppLastResult();
        // echo "DEBUG: ".__METHOD__ . " current data = <pre>".print_r($this->_aAppResult, 1)."</pre>";
        return true;
    }
    
    /**
     * 
     * @param type $sKey
     * @param type $aData
     */
    public function notify(){
        if(!$this->_sAppId){
            die("ERROR: ".__METHOD__." no application was initialized ... use setApp() first");
        }
        if ($this->isSleeptime()){
            return false;
        }
        $iChangetype=$this->_detectChangetype();
        // $iResult=$this->_aAppResult['result']['result'];
        // $sLogMessage=$this->_generateMessage('changetype-'.$iChangetype.'.logmessage');
       
        switch ($iChangetype) {
            case CHANGETYPE_NOCHANGE:
                // echo "DEBUG: ".__METHOD__." NO change detected\n";
                break;

            case CHANGETYPE_NEW:
                // to get the notification metadata from current data
                // $this->_aAppLastResult=$this->_aAppResult;
            case CHANGETYPE_CHANGE:
                // to get the notification metadata from current data
                if(!$this->_aAppResult) {
                    // $this->_aAppResult=$this->_aAppLastResult;
                }
                $this->_saveAppResult();
                // trigger notification
                $this->sendAllNotifications($iChangetype);
                break;

            default:
                break;
        }
        
        // TODO: remove test calls
        /*
        $this->_generateMessage('email.subject');
        $this->_generateMessage('changetype-'.CHANGETYPE_CHANGE.'.email.message');
        $this->_generateMessage('changetype-'.CHANGETYPE_CHANGE.'.logmessage');
         * 
         */
        
        // echo "DEBUG: ".__METHOD__." done\n";
        return true;
        
    }
    
    /**
     * delete application
     * @param string  $sAppId  app id
     * @return boolean
     */
    public function deleteApp($sAppId){
        $this->setApp($sAppId);
        $this->_iAppResultChange=CHANGETYPE_DELETE;
        // $sLogMessage=$this->_generateMessage('changetype-'.CHANGETYPE_DELETE.'.logmessage', CHANGETYPE_DELETE);
        // $this->addLogitem(CHANGETYPE_DELETE, RESULT_UNKNOWN, $sAppId, $sLogMessage);
        
        // trigger notification
        $this->sendAllNotifications();
        $this->_deleteAppLastResult();
        return true;
    }

    // ----------------------------------------------------------------------
    // functions for notifcation log
    // ----------------------------------------------------------------------
    
    /**
     * add a new item in notification log
     * 
     * @param integer  $iChangetype  type of change; see CHANGETYPE_ constants
     * @param integer  $sNewstatus   resultcode; see RESULT_ constants
     * @param string   $sAppId       application id
     * @param string   $sMessage     message text
     * @param array    $aResult      response ($this->_aAppResult)
     * @return type
     */
    protected function addLogitem($iChangetype, $sNewstatus, $sAppId, $sMessage, $aResult){
        // reread because service and webgui could change it
        $aData=$this->loadLogdata();
        $this->_aLog[]=array(
            'timestamp'=> time(),
            'changetype'=> $iChangetype,
            'status'=> $sNewstatus,
            'appid'=> $sAppId,
            'message'=> $sMessage,
            'result'=> $aResult,
        );
        
        $this->cutLogitems();
        $this->saveLogdata();
        return $this->_aLog;
    }
    
    /**
     * helper function - limit log to N entries
     * @return boolean
     */
    protected function cutLogitems(){
        if(count($this->_aLog)>$this->_iMaxLogentries){
            while(count($this->_aLog)>$this->_iMaxLogentries){
                array_shift($this->_aLog);
            }
        }
        return true;
    }
    
    /**
     * get current result from cache
     * @return type
     */
    public function getAppResult(){
        $oCache=new AhCache("appmonitor-server", $this->_sAppId);
        return $oCache->read();
    }
    /**
     * get last (differing) result from cache
     * @return type
     */
    public function getAppLastResult(){
        $oCache=new AhCache($this->_sCacheIdPrefix."-app", $this->_sAppId);
        return $oCache->read();
    }
    
    /**
     * get current log data
     * 
     * @param array   $aFilter  filter with possible keys timestamp|changetype|status|appid|message (see addLogitem())
     * @param integer $iLimit
     * @param boolean $bRsort   flag to reverse sort logs; default is true (=newset entry first)
     * @return array
     */
    public function getLogdata($aFilter=array(), $iLimit=false, $bRsort=true){
        $aReturn=array();
        $aData=$this->loadLogdata();
        if($bRsort){
            rsort($aData);
        }
        
        // filter
        if (count($aFilter)>0){
            foreach($aData as $aLogentry){
                if($iLimit && count($aReturn)>=$iLimit){
                    break;
                }
                $bAdd=false;
                foreach ($aFilter as $sKey=>$sValue){
                    if($aLogentry[$sKey]===$sValue){
                        $bAdd=true;
                    }
                }
                if($bAdd){
                    $aReturn[]=$aLogentry;
                }
            }
        } else {
            $aReturn=$aData;
        }
        
        return $aReturn;
    }

    /**
     * read stored log
     * @return type
     */
    public function loadLogdata(){
        $oCache=new AhCache($this->_sCacheIdPrefix."-log", "log");
        $this->_aLog=$oCache->read();
        if(!$this->_aLog){
            $this->_aLog=array();
        }
        return $this->_aLog;
    }
    
    /**
     * save log
     * @return type
     */
    protected function saveLogdata(){
        if ($this->_aLog && is_array($this->_aLog) && count($this->_aLog)){            
            $oCache=new AhCache($this->_sCacheIdPrefix."-log", "log");
            
            // echo "DEBUG saving notification logdata:\n";
            // print_r($this->_aLog);
            return $this->_aLog=$oCache->write($this->_aLog);
        }
        return false;
    }
    
    // ----------------------------------------------------------------------
    // functions for notifcation 
    // ----------------------------------------------------------------------

    /**
     * helper function: replace based on str_replace
     * @param array  $aReplace  key value array; keys=search; value= replace
     * @param string $sString
     * @return string
     */
    protected function _makeReplace($aReplace, $sString) {
        $aFrom = array();
        $aTo = array();
        foreach ($aReplace as $sKey => $sValue) {
            $aFrom[] = $sKey;
            $aTo[] = $sValue;
        }
        return str_replace($aFrom, $aTo, $sString);
    }

    /**
     * helper function: get the array with all current replacements in message 
     * texts with key = placeholder and value = replacement
     * 
     * @return array
     */
    public function getMessageReplacements(){
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
        if ($this->_iAppResultChange===false){
            $this->_detectChangetype();
        }
        $sMiss='-';
        $aReplace=array(
            '__APPID__'          => $this->_sAppId,
            '__CHANGE__'         => isset($this->_iAppResultChange) ? $this->_tr('changetype-'. $this->_iAppResultChange) : $sMiss,
            '__TIME__'           => date("Y-m-d H:i:s", (time())),
            '__URL__'            => isset($this->_aAppResult['result']['url']) ? $this->_aAppResult['result']['url'] 
                                        : (isset($this->_aAppLastResult['result']['url']) ? $this->_aAppLastResult['result']['url'] : $sMiss),
            '__HOST__'           => isset($this->_aAppResult['result']['host']) ? $this->_aAppResult['result']['host'] : $sMiss,
            '__WEBSITE__'        => isset($this->_aAppResult['result']['website']) ? $this->_aAppResult['result']['website'] : $sMiss,

            '__RESULT__'         => isset($this->_aAppResult['result']['result']) ? $this->_tr('Resulttype-'. $this->_aAppResult['result']['result']) : $sMiss,
            '__ERROR__'         => isset($this->_aAppResult['result']['error']) && $this->_aAppResult['result']['error'] 
                                        ? $this->_aAppResult['result']['error'] : '',
            
            '__HEADER__'         => isset($this->_aAppResult['result']['header']) ? $this->_aAppResult['result']['header'] : $sMiss,
            
            '__LAST-TIME__'      => isset($this->_aAppLastResult['result']['ts']) ? date("Y-m-d H:i:s", $this->_aAppLastResult['result']['ts']) : $sMiss,
            '__LAST-RESULT__'    => isset($this->_aAppLastResult['result']['result']) ? $this->_tr('Resulttype-'. $this->_aAppLastResult['result']['result']) : $sMiss,
            '__DELTA-TIME__'     => isset($this->_aAppLastResult['result']['ts']) ? 
                    round((time() - $this->_aAppLastResult['result']['ts'])/ 60)." min "
                    . "(".round((time() - $this->_aAppLastResult['result']['ts'])/ 60/60*4)/4 ." h)"
                    : $sMiss    
                    ,
            
        );
        if($this->_sServerurl){
            $aReplace['__MONITORURL__']=$this->_sServerurl . '#divweb-'.$this->_sAppId;
        }
        // echo '<pre>'.print_r($this->_aAppResult['checks'], 1).'</pre>';
        $sChecks='';
        if(isset($this->_aAppResult['checks']) && count($this->_aAppResult['checks'])){
            
            // force sortorder in notifications - one key for each result ... 3 is error .. 0 is OK
            $aSortedChecks=array();
            for($i=3; $i>=0; $i--){
                $aSortedChecks[$i]='';
            }
            foreach($this->_aAppResult['checks'] as $aCheck){
                $iResult=$aCheck['result'];
                $aSortedChecks[$iResult].="\n\n"
                        . '----- '.$aCheck['name'].' ('.$aCheck['description'].")\n"
                        . $aCheck['value']."\n"
                        . $this->_tr('Resulttype-'. $aCheck['result'])
                        ;
            }
            $aReplace['__CHECKS__']=implode("", $aSortedChecks);
        } else {
            $aReplace['__CHECKS__']=html_entity_decode($this->_tr('msgErr-missing-section-checks'));
        }
        return $aReplace;
    }
    
    /**
     * helper function: generate message text frem template based on type of
     * change, its template and the values of check data
     * 
     * @param string $sMessageId  one of changetype-[N].logmessage | changetype-[N].email.message | email.subject
     * @return integer
     */
    public function getReplacedMessage($sMessageId){
        $sTemplate=isset($this->_aNotificationOptions['messages'][$sMessageId]) && $this->_aNotificationOptions['messages'][$sMessageId]
                ? $this->_aNotificationOptions['messages'][$sMessageId]
                : $this->_tr($sMessageId)
                ;
        // $sTemplate=$this->_tr($sMessageId);
        $sReturn = $this->_makeReplace($this->getMessageReplacements(), $sTemplate);
        return $sReturn;
    }
    
    /**
     * write log entry and send notifications
     * @return boolean
     */
    protected function sendAllNotifications(){
        if($this->_iAppResultChange===false){
            die("ERROR: " .__METHOD__ ." no change was detected - or app was not initialized.");
            return false;
        }

        // take template for log message and current result type
        $sLogMessage=$this->getReplacedMessage('changetype-'.$this->_iAppResultChange.'.logmessage');
        
        // set result: 
        // - use current result, if it existst
        // - use RESULT_UNKNOWN if action was delete or result does not exist
        $iResult=($this->_iAppResultChange==CHANGETYPE_DELETE) ? RESULT_UNKNOWN 
                : (isset($this->_aAppResult['result']['result']) ? $this->_aAppResult['result']['result'] : RESULT_UNKNOWN)
                ;

        $this->addLogitem($this->_iAppResultChange, $iResult, $this->_sAppId, $sLogMessage, $this->_aAppResult);
        
        $this->_sendEmailNotifications();
        $this->_sendSlackNotifications();
        return true;
    }

    /**
     * get array with notification data of an app
     * taken from check result meta -> notifications merged with server config
     * 
     * @param string  $sType  optional: type email|slack; defailt: false (=return all keys)
     * @return array
     */
    
    public function getAppNotificationdata($sType=false){

        $aMergeMeta=array();
        $aArray_keys=$sType ? array($sType) : array_keys($this->_aNotificationOptions);

        // server side notifications:
        // echo '<pre>'.print_r($this->_aNotificationOptions, 1).'</pre>';

        // got from client
        $aClientNotifications=$this->_getAppNotifications();
        // echo '<pre>'.print_r($aClientNotifications, 1).'</pre>';

        
        // take data from web app ... meta -> notifications
        // $aMergeMeta=isset($this->_aAppLastResult['meta']['notifications']) ? $this->_aAppLastResult['meta']['notifications'] : array();
        foreach($aArray_keys as $sNotificationType){
            // echo "DEBUG: $sNotificationType\n<pre>" . print_r($aClientNotifications[$sNotificationType], 1) . '</pre>';
            if(isset ($aClientNotifications[$sNotificationType]) && count($aClientNotifications[$sNotificationType])){
                foreach($aClientNotifications[$sNotificationType] as $sKey=>$Value){
                    if(is_int($sKey)){
                        $aMergeMeta[$sNotificationType][]=$Value;
                    } else {
                        $aMergeMeta[$sNotificationType][$sKey]=$Value;
                    }
                }
            }
            if (isset($this->_aNotificationOptions[$sNotificationType]) && is_array($this->_aNotificationOptions[$sNotificationType])){
                foreach($this->_aNotificationOptions[$sNotificationType] as $sKey=>$Value){
                    if(is_int($sKey)){
                        $aMergeMeta[$sNotificationType][]=$Value;
                    } else {
                        $aMergeMeta[$sNotificationType][$sKey]=$Value;
                    }
                }
            }
        }
        return $sType 
                ? (isset($aMergeMeta[$sType]) ? $aMergeMeta[$sType] : array())
                : $aMergeMeta;
    }
    // ---------- email
    /**
     * get flat array with contacts email addresses for current app from 
     * check result meta -> notifications -> email
     */
    public function getAppEmailContacts(){
        return array_values($this->getAppNotificationdata('email'));
    }
    /**
     * get flat array with slack webhook addresses for current app from 
     * check result meta -> notifications -> slack
     * remark: to get key cvalue array use
     * $this->getAppNotificationdata('slack') 
     * instead
     */
    public function getAppSlackChannels(){
        return array_values($this->getAppNotificationdata('slack'));
    }
    
    /**
     * send email notifications to monitor server admins and application contacts
     * @return boolean
     */
    protected function _sendEmailNotifications(){
        $sFrom=(isset($this->_aNotificationOptions['from']['email']) && $this->_aNotificationOptions['from']['email']) ? $this->_aNotificationOptions['from']['email'] : false;
        if(!$sFrom){
            return false; // no from address
        }

        $aTo=$this->getAppEmailContacts();
        if(!count($aTo)){
            return false; // no to adress in server config nor app metadata
        }

        $sTo=implode(";", $aTo);
        $sEmailSubject=$this->getReplacedMessage('changetype-'.$this->_iAppResultChange.'.email.subject');
        $sEmailBody=$this->getReplacedMessage('changetype-'.$this->_iAppResultChange.'.email.message');

        mail($sTo,  
            utf8_decode(html_entity_decode($sEmailSubject)), 
            utf8_decode(html_entity_decode($sEmailBody)),
            "From: " . $sFrom . "\r\n" 
            . "Reply-To: " . $sFrom . "\r\n"

            . "X-Priority: 1 (Highest)\r\n"
            . "X-MSMail-Priority: High\r\n"
            . "Importance: High\r\n"

        );
        return true;
    }
    
    // ---------- slack
    
    /**
     * send email notifications to monitor server admins and application contacts
     * @return boolean
     */
    protected function _sendSlackNotifications(){
        $sFrom=(isset($this->_aNotificationOptions['from']['slack']) && $this->_aNotificationOptions['from']['slack']) ? $this->_aNotificationOptions['from']['slack'] : false;
        if(!$sFrom){
            return false; // no from address
        }
        $aTargetChannels=$this->getAppNotificationdata('slack');

        if(!count($aTargetChannels)){
            return false; // no slack channel in server config nor app metadata
        }

        // --- start sending
        $data=array(
            'text'       => $this->getReplacedMessage('changetype-'.$this->_iAppResultChange.'.email.message'),
            'username'   => '[APPMONITOR]',
            'icon_emoji' => false
        );

        $options = array(
          'http' => array(
            'header'  => 'Content-type: application/x-www-form-urlencoded\r\n',
            'method'  => 'POST',
            'content' => json_encode($data)
          )
        );
        $context  = stream_context_create($options);

        // --- loop over slack targets
        foreach($aTargetChannels as $sLabel=>$sChannelUrl){
            // check if channel exists in predefined channels
            $result = file_get_contents($sChannelUrl, false, $context);
        }

        return true;
    }
    
}
