<?php

/**
 * simple storages to keep last N items of an object
 *
 * @author hahn
 */
class simpleRrd {

    /**
     * prefix for cached items of this class
     * @var string
     */
    protected $_sCacheIdPrefix="rrd";
    
    /**
     * max number of kept data items
     * @var integer
     */
    protected $_iMaxLogentries=1000;
    
    /**
     * logdata for detected changes and sent notifications
     * @var array 
     */
    protected $_aLog = [];
    
    /**
     * id of the cache item
     * @var type 
     */
    protected $_sCacheId = false;
    
    /**
     * instance of ahcache class
     * @var type 
     */
    protected $_oCache = false;

    // ----------------------------------------------------------------------
    // __construct
    // ----------------------------------------------------------------------

    public function __construct($sId=false) {
        if($sId){
            $this->setId($sId);
        }
        return true;
    }


    // ----------------------------------------------------------------------
    // protected functions - handle cache of application checkdata
    // ----------------------------------------------------------------------
    /**
     * helper function - limit log to N entries
     * @return boolean
     */
    protected function _cutLogitems(){
        if(count($this->_aLog)>$this->_iMaxLogentries){
            while(count($this->_aLog)>$this->_iMaxLogentries){
                array_shift($this->_aLog);
            }
        }
        return true;
    }
    
    /**
     * get current or last stored client notification data
     * this method also stores current notification data on change
     * @return array
     */
    protected function _getLogs(){
        $this->_aLog=$this->_oCache->read();
        if (!is_array($this->_aLog)){
            $this->_aLog=[];
        }
        return true;
    }

    /**
     * save log data
     * @return boolean
     */
    protected function _saveLogs(){
        return $this->_oCache->write($this->_aLog);
    }
    
    // ----------------------------------------------------------------------
    // public functions
    // ----------------------------------------------------------------------

    /**
     * add data item
     * @param type $aDataItem
     * @return boolean
     */
    public function add($aDataItem){
        $this->_getLogs();
        $this->_aLog[]=array('timestamp'=>time(), 'data'=>$aDataItem);
        $this->_cutLogitems();
        return $this->_saveLogs();
    }

    
    /**
     * delete application
     * @return boolean
     */
    public function delete(){
        if(!$this->_sCacheId){
            return false;
        }
        return $this->_oCache->delete();
    }
    /**
     * get array with stored items
     * 
     * @param integer  $iMax  optional: limit
     * @return array
     */
    public function get($iMax=false){
        $aReturn=array();
        $this->_getLogs();
        $aTmp=$this->_aLog;
        if (!count($aTmp)){
            return [];
        }
        $iMax=$iMax ? $iMax : count($aTmp);
        $iMax=min($iMax, count($aTmp));
        for($i=0; $i<$iMax; $i++){
            $aReturn[]=array_pop($aTmp);
            // $aReturn[]=$aTmp;
        }
        return $aReturn;
    }
    
    /**
     * set id for this rrd value store
     * 
     * @param type $sId
     * @return boolean
     */
    public function setId($sId){
        $this->_sCacheId=$sId;
        $this->_oCache=new AhCache($this->_sCacheIdPrefix, $this->_sCacheId);
        $this->_getLogs();
        return true;
    }

}
