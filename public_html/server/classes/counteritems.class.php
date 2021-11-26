<?php

require_once 'simplerrd.class.php';

/**
 * container for all counters of a single app
 * store last N response time to draw a graph
 *
 * @example
 * <code>
 * // INIT
 * $oCounters=new counteritems($sAppId, $sCounterId); 
 * OR
 * $oCounters=new counteritems();
 * $oCounters->setApp($sAppId);
 * $oCounters->setCounter($sCounterId);
 * 
 * // ADD VALUE
 * $oCounters->add([array]);
 * 
 * </code>
 *
 * @author hahn
 */
class counteritems {

    // ----- storing counter values
    
    /**
     * id of the application
     * @var type 
     */
    protected $_sAppId = false;
    /**
     * id of the counter
     * @var type 
     */
    protected $_sCounterId = false;
    
    /**
     * instance of simpleRrd to keep N history values
     * @var type 
     */
    protected $_oSR = false;
    

    // ----- save used counters
    
    /**
     * array of used counterids 
     * @var type 
     */
    protected $_aCounters = array();
    /**
     * id of the cache item
     * @var type 
     */
    protected $_sCacheId = false;
    protected $_sCacheIdPrefix = 'counterids';
    
    /**
     * instance of ahcache class
     * @var type 
     */
    protected $_oCache = false;
    
    // ----------------------------------------------------------------------
    // __construct
    // ----------------------------------------------------------------------

    public function __construct($sAppid=false, $sCounterId=false) {
        if($sAppid){
            $this->setApp($sAppid, $sCounterId);
        }
        return true;
    }

    /**
     * set application
     * @param string $sAppid      id of an app
     * @param string $sCounterId  optional: name of a counter
     * @return boolean
     */
    public function setApp($sAppid, $sCounterId=false){
        $this->_sCounterId=false;
        $this->_sAppId=$sAppid;
        $this->_sCacheId=$sAppid;
        $this->_oCache=new AhCache($this->_sCacheIdPrefix, $this->_sCacheId);
        $this->_aCounters=$this->_oCache->read();
        if(!$this->_aCounters){
            $this->_aCounters=array();
        }
        if($sCounterId){
            $this->setCounter($sCounterId);
        }
        return true;
    }
    
    /**
     * set counter name
     * @param string $sCounterId  name of a counter
     * @param array  $aMeta       metadata with these keys
     *                            - title  - text above value
     *                            - visual - viaualisation type
     * @return boolean
     */
    public function setCounter($sCounterId, $aMeta=false){
        $this->_sCounterId=$sCounterId;

        $this->_oSR=false;
        if(!$this->_sAppId || !$this->_sCounterId ){
            echo 'FATAL ERROR in '.__METHOD__.' - you need to setApp() before using setCounter()<br>'."\n";
            return false;
        } else {
            if(!isset($this->_aCounters[$this->_sCounterId]) || is_array($aMeta)){
                $this->_aCounters[$this->_sCounterId]=($aMeta ? $aMeta : array());
                $this->_oCache->write($this->_aCounters);
            }
            $this->_oSR=new simpleRrd($this->_sAppId.'-'.$this->_sCounterId);
            return true;
        }
    }
    /**
     * get all stored counters of the current app
     * @return type array
     */
    public function getCounters(){
        return $this->_aCounters;
    }
    
    /**
     * delete a single counter history
     * @param type $sCounterId
     * @return boolean
     */
    public function deleteCounter($sCounterId=false){
        if(!$sCounterId){
            $this->setCounter($sCounterId);
        }
        if(isset($this->_aCounters[$sCounterId])){
            $this->_oSR->delete();
            unset($this->_aCounters[$sCounterId]);
            $this->_oCache->write($this->_aCounters);
            return true;
        }
        return false;
    }

    
    // ----------------------------------------------------------------------
    // public functions
    // ----------------------------------------------------------------------

    /**
     * add a value to the current counter
     * @param array  $aItem  array item to add
     * @return boolean
     */
    public function add($aItem){
        return $this->_oSR->add($aItem);
    }

    
    /**
     * delete all application counters
     * @return boolean
     */
    public function delete(){
        $aCounters=$this->getCounters();
        if(count($aCounters)){
            foreach (array_keys($aCounters) as $sCounterid){
                $this->deleteCounter($sCounterid);
            }
        }
        return true;
    }
    /**
     * get last N values
     * @param integer  $iMax  optional: get last N values; default: get all stored values
     * @return array
     */
    public function get($iMax=false){
        return $this->_oSR->get($iMax);
    }
}
