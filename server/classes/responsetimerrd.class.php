<?php

require_once 'simplerrd.class.php';

/**
 * store last N response time to draw a graph
 * 
 *
 * @author hahn
 */
class responsetimeRrd {

    protected $_sAppId = false;
    protected $_oSR = false;
    
    // ----------------------------------------------------------------------
    // __construct
    // ----------------------------------------------------------------------

    public function __construct($aAppid=false) {
        if($aAppid){
            $this->setApp($aAppid);
        }
        return true;
    }

    public function setApp($aAppid){
        $this->_sAppId=$aAppid;
        $this->_oSR=new simpleRrd('response-'.$aAppid);
        return true;
    }

    
    // ----------------------------------------------------------------------
    // public functions
    // ----------------------------------------------------------------------

    public function add($sStatus, $time, $sMsg){
        return $this->_oSR->add(array('status'=>$sStatus, 'time'=>$time, 'message'=>$sMsg));
    }

    
    /**
     * delete application
     * @param string  $sAppId  app id
     * @return boolean
     */
    public function delete(){
        return $this->_oSR->delete();
    }
    /**
     * delete application
     * @param string  $sAppId  app id
     * @return boolean
     */
    public function get($iMax=false){
        return $this->_oSR->get($iMax);
    }
}
