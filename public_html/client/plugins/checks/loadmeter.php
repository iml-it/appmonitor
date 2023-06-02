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
 * SHOW LOAD AS LINE
 * 
 * A plugin always is loaded in clases/appmonitor-checks.class.php
 * Have look there for the used protected classes
 * ____________________________________________________________________________
 * 
 * PARAMS:
 *   warning {float}  limit to switch to warning
 *   error   {float}  limit to switch to error
 * 
 * USAGE:
 * 
 * $oMonitor->addCheck(
 *     [
 *         "name" => "plugin Load",
 *         "description" => "check current load",
 *         "check" => [
 *             "function" => "Loadmeter",
 *             "params" => [
 *                "warning" => 1.0,
 *                "error" => 3,
 *             ],
 *         ],
 *         "worstresult" => RESULT_OK
 *     ]
 * );
 * ____________________________________________________________________________
 * 
 * 2019-06-06  <axel.hahn@iml.unibe.ch>
 * 
 */
class checkLoadmeter extends appmonitorcheck{
    /**
     * get default group of this check
     * @param array   $aParams
     * @return array
     */
    public function getGroup(){
        return 'monitor';
    }

    /**
     * detect load of a machine and return a float value
     * windows part was taken from https://stackoverflow.com/questions/5588616/how-do-you-calculate-server-load-in-php
     * @return float
     */
    protected function _getLoad() {
        if (function_exists('sys_getloadavg')){
            $load = sys_getloadavg();
            return $load[0];
        } else {
            // Only MS Windows has not implemented sys_getloadavg
            // try something else
            if(class_exists('COM')){
                $wmi=new COM('WinMgmts:\\\\.');
                $cpus=$wmi->InstancesOf('Win32_Processor');
                $load=0;
                if(version_compare('4.50.0', PHP_VERSION) == 1){
                    while($cpu = $cpus->Next()){
                        $load += $cpu->LoadPercentage;
                    }
                }else{
                    foreach($cpus as $cpu){
                        $load += $cpu->LoadPercentage;
                    }
                }
                return $load;
            }
            return false;
        }
    }

    /**
     * 
     * @param array   $aParams
     * @return array
     */
    public function run($aParams){
        
        // --- (1) verify if array key(s) exist:
        // $this->_checkArrayKeys($aParams, "...");


        // --- (2) do something magic
        // $fLoad=rand(0, 1.3);
        // $fLoad=$this->_getServerLoad();
        $fLoad=$this->_getLoad();
        
        // set result code
        if($fLoad===false){
            $iResult=RESULT_UNKNOWN;
        } else {
            $iResult=RESULT_OK;
            if(isset($aParams['warning']) && $aParams['warning'] && $fLoad>$aParams['warning']){
                $iResult=RESULT_WARNING;
            }
            if(isset($aParams['error']) && $aParams['error'] && $fLoad>$aParams['error']){
                $iResult=RESULT_ERROR;
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
            ($fLoad===false ? 'load value is not available' : 'current load is: '.$fLoad),
            ($fLoad===false 
                ? []
                : [
                    'type'=>'counter',
                    'count'=>$fLoad,
                    'visual'=>'line',
                ]
            )
        ]
        ;
    }
}
