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
 * CUSTOM CHECK FOR FILE OBJECTS
 * 
 * Check files, directories, links if the exist or not, if they are accessible 
 * or not.
 * ____________________________________________________________________________
 * 
 * 2021-10-26  <axel.hahn@iml.unibe.ch>
 * 
 */
class checkFile extends appmonitorcheck{
    /**
     * get default group of this check
     * @param array   $aParams - see run() method
     * @return array
     */
    public function getGroup($aParams){
        $sReturn='file';
        if(isset($aParams['dir'])){
            $sReturn='folder';
        }
        foreach(['exists', 'executable', 'readable', 'writable'] as $sFlag){
            if (isset($aParams[$sFlag]) && !$aParams[$sFlag]){
                $sReturn='deny';
            }
        }
        return $sReturn;
    }

    /**
     * check a file
     * @param array $aParams
     * [
     *     "filename"    directory that must exist
     *     "exists"      "filename" must exist/ must be absent
     *     "dir"         filetype directory
     *     "file"        filetype file
     *     "link"        filetype symbolic link
     *     "executable"  flag executable
     *     "readable"    flag is readable
     *     "writable"    flag is writable
     * ]
     * @return boolean
     */
    public function run($aParams) {
        $aOK = [];
        $aErrors = [];
        $this->_checkArrayKeys($aParams, "filename");
        $sFile = $aParams["filename"];

        if (isset($aParams['exists'])) {
            $sMyflag = 'exists=' . ($aParams['exists'] ? 'yes' : 'no');
            if (file_exists($sFile) && $aParams['exists']) {
                $aOK[] = $sMyflag;
            } else {
                $aErrors[] = $sMyflag;
            }
        }
        foreach ([ 'dir', 'executable', 'file', 'link', 'readable', 'writable' ] as $sFiletest) {
            if (isset($aParams[$sFiletest])) {
                $sTestCmd = 'return is_' . $sFiletest . '("' . $sFile . '");';
                if (eval($sTestCmd) && $aParams[$sFiletest]) {
                    $aOK[] = $sFiletest . '=' . ($aParams[$sFiletest] ? 'yes' : 'no');
                } else {
                    $aErrors[] = $sFiletest . '=' . ($aParams[$sFiletest] ? 'yes' : 'no');
                }
            }
        }
        $sMessage = (count($aOK) ? ' flags OK: ' . implode('|', $aOK) : '')
                . ' ' . (count($aErrors) ? ' flags FAILED: ' . implode('|', $aErrors) : '')
        ;
        if (count($aErrors)) {
            return [
                RESULT_ERROR, 
                'file test [' . $sFile . '] ' . $sMessage
            ];
        } else {
            return[
                RESULT_OK, 'file test [' . $sFile . '] ' . $sMessage
            ];
        }
    }

}
