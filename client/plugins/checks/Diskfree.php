<?php
/**
 * ____________________________________________________________________________
 * 
 *  _____ _____ __                   _____         _ _           
 * |     |     |  |      ___ ___ ___|     |___ ___|_| |_ ___ ___ 
 * |-   -| | | |  |__   | .'| . | . | | | | . |   | |  _| . |  _|
 * |_____|_|_|_|_____|  |__,|  _|  _|_|_|_|___|_|_|_|_| |___|_|  
 *                          |_| |_|                              
 *                           _ _         _                                            
 *                       ___| |_|___ ___| |_                                          
 *                      |  _| | | -_|   |  _|                                         
 *                      |___|_|_|___|_|_|_|   
 *                                                               
 * ____________________________________________________________________________
 * 
 * CHECK FOR FREE DISKSPACE
 * ____________________________________________________________________________
 * 
 * 2021-10-26  <axel.hahn@iml.unibe.ch>
 * 
 */
class checkFile extends appmonitorcheck{
    /**
     * check free disk space on a given directory
     * @param array $aParams
     * array(
     *     "directory"   directory that must exist
     *     "warning"     space for warning (optional)
     *     "critical"    minimal space
     * )
     * @return boolean
     */
    public function run($aParams) {
        $this->_checkArrayKeys($aParams, "directory", "critical");
        
        $sDirectory = $aParams["directory"];
        if(!is_dir($sDirectory)){
            return [
                RESULT_ERROR, 
                'directory [' . $sDirectory . '] does not exist. Maybe it is wrong or is not mounted.'
            ];
        }
        
        $iWarn = isset($aParams["warning"]) ? $this->_getSize($aParams["warning"]) : false;
        $iCritical = $this->_getSize($aParams["critical"]);
        $iSpaceLeft=disk_free_space($sDirectory);
        
        
        $sMessage='[' . $sDirectory . '] has '.$this->_getHrSize($iSpaceLeft).' left.';
        
        if($iWarn){
            if($iWarn<=$iCritical){
                header('HTTP/1.0 503 Service Unavailable');
                die("ERROR in a Diskfree check - warning value must be larger than critical.<pre>" . print_r($aParams, true));
            }
            if ($iWarn<$iSpaceLeft){
                return [
                    RESULT_OK, 
                    $sMessage.' Warning level is not reached yet (still '.$this->_getHrSize($iSpaceLeft-$iWarn).' over warning limit).'
                ];
            }
            if ($iWarn>$iSpaceLeft && $iCritical<$iSpaceLeft){
                return [
                    RESULT_WARNING, 
                    $sMessage.' Warning level '.$this->_getHrSize($iWarn).' was reached (space is '.$this->_getHrSize($iWarn-$iSpaceLeft).' below warning limit; still '.$this->_getHrSize($iSpaceLeft-$iCritical).' over critical limit).'
                ];
            }
        }
        // check space
        if ($iCritical<$iSpaceLeft){
            return [RESULT_OK, $sMessage .' Minimum is not reached yet (still '.$this->_getHrSize($iSpaceLeft-$iCritical).' over critical limit).'];
        } else {
            return [RESULT_ERROR, $sMessage];
        }
    }

}
