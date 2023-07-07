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
 * SHOW COUNT OF ACTIVE APACHE PROCESSES
 * ____________________________________________________________________________
 * 
 * PARAMS:
 *   url     {string}   optional: override https server-status page; default is http://localhost/server-status
 *   warning {integer}  optional: limit to switch to warning (in percent); default: 50
 *   error   {integer}  optional: limit to switch to error (in percent); default: 75
 * 
 * USAGE:
 * Example with overriding all existing params
 * 
 * $oMonitor->addCheck(
 *    [
 *         "name" => "plugin ApacheProcesses",
 *         "description" => "check count running Apache processes",
 *         "check" => [
 *             "function" => "ApacheProcesses",
 *             "params" => [
 *                 "url" => "https://localhost/status",
 *                 "warning" => 75,
 *                 "error" => 90,
 *             ],
 *         ],
 *         "worstresult" => RESULT_OK
 *     ]
 * );
 * ____________________________________________________________________________
 * 
 * 2019-06-07  <axel.hahn@iml.unibe.ch>
 * 2022-07-06  <axel.hahn@iml.unibe.ch>  set group "monitor"
 * 
 */
class checkApacheProcesses extends appmonitorcheck{
    
    protected $_sServerStatusUrl = 'http://localhost/server-status';
    protected $_iWarn = 50;
    protected $_iError = 75;
    
    /**
     * fetch http server status and return slots, active and waiting processes
     * as array i.e. [total] => 256 \n    [free] => 247\n    [waiting] => 7\n    [active] => 2
     * @return boolean
     */
    protected function _getApacheProcesses() {

        $sBody = file_get_contents($this->_sServerStatusUrl);
        if(!$sBody){
            return false;
        }
        $sRegexScoreboard = '/<pre>(.*)\<\/pre\>/U';
        $aScore=[];
        $sStatusNobr = str_replace("\n", "", $sBody);

        if (preg_match_all($sRegexScoreboard, $sStatusNobr, $aTmpTable)) {
            $sScoreString=$aTmpTable[1][0];
            // $aScore['scoreboard']=$sScoreString;
            $aScore['total']=strlen($sScoreString);
            $aScore['free']=substr_count($sScoreString, '.');
            $aScore['waiting']=substr_count($sScoreString, '_');
            $aScore['active']=$aScore['total']-$aScore['free']-$aScore['waiting'];
        }
        return $aScore;
    }


    /**
     * get default group of this check
     * @param array   $aParams
     * @return array
     */
    public function getGroup(){
        return 'monitor';
    }

    /**
     * 
     * @param array   $aParams
     * @return array
     */
    public function run($aParams){
        
        // --- (1) verify if array key(s) exist:
        // $this->_checkArrayKeys($aParams, "...");
        if(isset($aParams['url']) && $aParams['url']){
            $this->_sServerStatusUrl=$aParams['url'];
        }
        if(isset($aParams['warning']) && (int)$aParams['warning']){
            $this->_iWarn=(int)$aParams['warning'];
        }
        if(isset($aParams['error']) && (int)$aParams['error']){
            $this->_iError=(int)$aParams['error'];
        }
    

        // --- (2) do something magic
        $aProcesses=$this->_getApacheProcesses();
        $iActive=$aProcesses ? $aProcesses['active'] : false;
        
        // set result code
        if($iActive===false){
            $iResult=RESULT_UNKNOWN;
        } else {
            $sComment='';
            $iTotal=$aProcesses['total'];
            $iResult=RESULT_OK;
            if(($iActive/$iTotal*100)>$this->_iWarn){
                $iResult=RESULT_WARNING;
                $sComment='more than warning level '.$this->_iWarn.'%';
            } else {
                $sComment='less than warning level '.$this->_iWarn.'%';
            }
            if(($iActive/$iTotal*100)>$this->_iError){
                $iResult=RESULT_ERROR;
                $sComment='more than error level '.$this->_iError.'%';
            }
        }


        // --- (3) response
        // see method appmonitorcheck->_setReturn()
        // 
        // {integer} you should use a RESULT_XYZ constant:
        //              RESULT_OK|RESULT_UNKNOWN|RESULT_WARNING|RESULT_ERROR
        // {string}  output text 
        // {array}   optional: counter data
        //              type   => {string} "counter"
        //              count  => {float}  value
        //              visual => {string} one of bar|line|simple (+params)
        //           
        return [
            $iResult, 
            ($iActive===false ? 'Apache httpd server status is not available' : 'apache processes: '.print_r($aProcesses, 1)).' '.$sComment,
            ($iActive===false 
                ? []
                : [
                    'type'=>'counter',
                    'count'=>$iActive,
                    'visual'=>'line',
                ]
            )
        ];
    }
}
