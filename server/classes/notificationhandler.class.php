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
 * notofocationhandler
 *
 * @author hahn
 */
class notificationhandler {

    protected $_sCacheIdPrefix="notificationhandler";
    protected $_iMaxLogentries=10;
    
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
    
    // ------------------------------------------------------------------
    // data of the current app 
    // ------------------------------------------------------------------
    protected $_sAppId=false;
    protected $_aAppResult=false;
    protected $_aAppLastResult=false;


    /*
    private $_aChangetypes=array(
        CHANGETYPE_NOCHANGE => array('label'=>'no change', 'message'=>'Webapp has no change: __URL__.'),
        CHANGETYPE_NEW      => array('label'=>'new',       'message'=>'Webapp was added to the appmonitor: __URL__.'),
        CHANGETYPE_CHANGE   => array('label'=>'changed',   'message'=>'Status of __URL__ has changed from __OLDSTATUS__ to __NEWSTATUS__.'),
        CHANGETYPE_DELETE   => array('label'=>'deleted',   'message'=>'Webapp was deleted from appmonitor: __URL__.'),
    );
    
    private $_aResults=array(
        RESULT_OK      => array('label'=>'OK'),
        RESULT_UNKNOWN => array('label'=>'unknown'),
        RESULT_WARNING => array('label'=>'warning'),
        RESULT_ERROR   => array('label'=>'error'),
    );
     */
    
    // ----------------------------------------------------------------------
    // __construct
    // ----------------------------------------------------------------------

    public function __construct($sLang) {
        $this->_loadLangTexts($sLang);
        return true;
    }
    // ----------------------------------------------------------------------
    // private functions - handle languages texts
    // ----------------------------------------------------------------------

    /**
     * load language texts
     */
    protected function _loadLangTexts($sLang) {
        return $this->oLang = new lang($sLang);
    }
    /**
     * translate a text with language file
     * @param string $sWord
     * @return string
     */
    protected function _tr($sWord) {
        return $this->oLang->tr($sWord, array('notifications'));
    }

    // ----------------------------------------------------------------------
    // private functions - handle cache of application checkdata
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
        return $oCache->delete();
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
            return CHANGETYPE_NEW;
        } else {
            if($this->_aAppLastResult['result']['result']!==$this->_aAppResult['result']['result']){
                return CHANGETYPE_CHANGE;
            } else {
                return CHANGETYPE_NOCHANGE;
            }
        }
    }
    
    
    /**
     * set application with its current check result
     * @param string  $sAppId  application id
     * @param array   $aData   data of current check; can be false if you want to access last status
     * @return boolean
     */
    public function setApp($sAppId, $aData=false){
        $this->_sAppId=$sAppId;
        $this->_aAppResult=$aData;
        $this->_aAppLastResult=$this->getAppLastResult();
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
        
        $iChangetype=$this->_detectChangetype(); 
        $iResult=$this->_aAppResult['result']['result'];
        $sLogMessage=$this->_generateMessage('changetype-'.$iChangetype.'.logmessage');
        
        switch ($iChangetype) {
            case CHANGETYPE_NOCHANGE:
                // echo "DEBUG: ".__METHOD__." NO change detected\n";
                break;

            case CHANGETYPE_NEW:
            case CHANGETYPE_CHANGE:
                $this->_saveAppResult();
                $this->addLogitem(CHANGETYPE_CHANGE, $iResult, $this->_sAppId, $sLogMessage);
                // TODO: trigger notification
                
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
    
    public function deleteApp($sAppId){
        $this->setApp($sAppId, array());
        $aLastData=$this->getAppLastResult();
        // TODO: trigger notification

        $sLogMessage=$this->_generateMessage('changetype-'.CHANGETYPE_DELETE.'.logmessage');
        $this->_deleteAppLastResult();
        $this->addLogitem(CHANGETYPE_DELETE, 0, $sAppId, $sLogMessage);
        return true;
    }

    // ----------------------------------------------------------------------
    // functions for notifcation log
    // ----------------------------------------------------------------------
    
    /**
     * add a new item in notification log
     * 
     * @param string  $sChangetype
     * @param integer $sNewstatus
     * @param string  $sAppId
     * @param string  $sMessage
     * @return type
     */
    protected function addLogitem($sChangetype, $sNewstatus, $sAppId, $sMessage){
        // reread because service and webgui could change it
        $aData=$this->loadLogdata();
        // echo "DEBUG: ".__METHOD__." start\n";
        $this->_aLog[]=array(
            'timestamp'=> time(),
            'changetype'=> $sChangetype,
            'status'=> $sNewstatus,
            'appid'=> $sAppId,
            'message'=> $sMessage,
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
     * get last (differing) result from cache
     * @return type
     */
    public function getAppLastResult(){
        $oCache=new AhCache($this->_sCacheIdPrefix."-app", $this->_sAppId);
        return $oCache->read();
    }
    
    /**
     * get current log data
     * @return type
     */
    public function getLogdata($aFilter=array(), $iLimit=false){
        $aReturn=array();
        $aData=$this->loadLogdata();
        
        // filter
        if (count($aFilter)>0){
            foreach($aData as $aLogentry){
                // TODO filtering
                $aReturn[]=$aLogentry;
            }
        } else {
            $aReturn=$aData;
        }
        
        // limit
        
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
     * helper function: generate message text frem template based on type of
     * change, its template and the values of check data
     * 
     * @param string $sMessageId  one of changetype-[N].logmessage | changetype-[N].email.message | email.subject
     * @return integer
     */
    protected function _generateMessage($sMessageId){
        $iChangetype=$this->_detectChangetype();        
        $sTemplate=$this->_tr($sMessageId);
        
        /*
                [result] => Array
                (
                    [ts] => 1529672793
                    [result] => 3
                    [ttl] => 20
                    [url] => http://aum-cba02.unibe.ch/appmonitor/
                    [header] => 
                    [headerarray] => 
                    [httpstatus] => 
                    [error] => Http Request to appmonitor failed: host or service is unreachable.
                    [fromcache] => 
                )

         */
        $bIsNew= is_array($this->_aAppLastResult) && count($this->_aAppLastResult);
        $aReplace=array(
            '__URL__'            => $this->_aAppResult['result']['url'],
            '__TIME__'           => date("Y-m-d H:i:s", $this->_aAppResult['result']['ts']),
            '__CHANGE__'         => $this->_tr('changetype-'. $iChangetype),
            '__RESULT__'         => $this->_tr('Resulttype-'. $this->_aAppResult['result']['result']),
            
            '__APPID__'          => $this->_sAppId,
            '__HEADER__'         => $this->_aAppResult['result']['header'],
            
            '__LAST-TIME__'      => $bIsNew ? '-' : date("Y-m-d H:i:s", $this->_aAppLastResult['result']['ts']),
            '__LAST-RESULT__'    => $bIsNew ? '-' : $this->_tr('Resulttype-'. $this->_aAppLastResult['result']['result']),
            '__DELTA-TIME__'     => $bIsNew ? '-' : round(($this->_aAppResult['result']['ts'] - $this->_aAppLastResult['result']['ts'])/ 60)." min (".round(($this->_aAppResult['result']['ts'] - $this->_aAppLastResult['result']['ts'])/ 60/60)." h)",
            
        );
        
        // echo "DEBUG: type $iChangetype -  sTemplate = $sTemplate\n";
        // if($aData['result'][''])
        $sReturn = $this->_makeReplace($aReplace, $sTemplate);
        // echo "DEBUG: returns: $sReturn\n";
        return $sReturn;
    }
    
}
