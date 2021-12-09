<?php

if(!defined('RESULT_OK')){
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
 *                           _ _         _                                            
 *                       ___| |_|___ ___| |_                                          
 *                      |  _| | | -_|   |  _|                                         
 *                      |___|_|_|___|_|_|_|   
 *                                                               
 * ____________________________________________________________________________
 * 
 * APPMONITOR :: CLASS FOR CLIENT TEST FUNCTIONS<br>
 * <br>
 * THERE IS NO WARRANTY FOR THE PROGRAM, TO THE EXTENT PERMITTED BY APPLICABLE <br>
 * LAW. EXCEPT WHEN OTHERWISE STATED IN WRITING THE COPYRIGHT HOLDERS AND/OR <br>
 * OTHER PARTIES PROVIDE THE PROGRAM ?AS IS? WITHOUT WARRANTY OF ANY KIND, <br>
 * EITHER EXPRESSED OR IMPLIED, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED <br>
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE. THE <br>
 * ENTIRE RISK AS TO THE QUALITY AND PERFORMANCE OF THE PROGRAM IS WITH YOU. <br>
 * SHOULD THE PROGRAM PROVE DEFECTIVE, YOU ASSUME THE COST OF ALL NECESSARY <br>
 * SERVICING, REPAIR OR CORRECTION.<br>
 * <br>
 * --------------------------------------------------------------------------------<br>
 * <br>
 * --- HISTORY:<br>
 * 2014-10-24  0.5   axel.hahn@iml.unibe.ch<br>
 * 2015-04-08  0.9   axel.hahn@iml.unibe.ch  added sochket test: checkPortTcp<br>
 * 2018-06-29  0.24  axel.hahn@iml.unibe.ch  add file and directory checks<br>
 * 2018-07-17  0.42  axel.hahn@iml.unibe.ch  add port on mysqli check<br>
 * 2018-07-26  0.46  axel.hahn@iml.unibe.ch  fix mysql connection check with empty port param<br>
 * 2018-08-14  0.47  axel.hahn@iml.unibe.ch  appmonitor client: use timeout of 5 sec for tcp socket connections<br>
 * 2018-08-15  0.49  axel.hahn@iml.unibe.ch  cert check: added flag to skip verification<br>
 * 2018-08-23  0.50  axel.hahn@iml.unibe.ch  replace mysqli connect with mysqli real connect (to use a timeout)<br>
 * 2018-08-27  0.52  axel.hahn@iml.unibe.ch  add pdo connect (starting with mysql)<br>
 * 2018-11-05  0.58  axel.hahn@iml.unibe.ch  additional flag in http check to show content<br>
 * 2019-05-31  0.87  axel.hahn@iml.unibe.ch  add timeout as param in connective checks (http, tcp, databases)<br>
 * 2019-06-05  0.88  axel.hahn@iml.unibe.ch  add plugins<br>
 * 2021-10-28  0.93  axel.hahn@iml.unibe.ch  add plugins<br>
 * --------------------------------------------------------------------------------<br>
 * @version 0.93
 * @author Axel Hahn
 * @link TODO
 * @license GPL
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL 3.0
 * @package IML-Appmonitor
 */
class appmonitorcheck {
    // ----------------------------------------------------------------------
    // CONFIG
    // ----------------------------------------------------------------------
    
    /**
     * config container
     * @var array
     */
    protected $_aConfig = array();

    /**
     * data of all checks
     * @var array
     */
    protected $_aData = array();
    

    /**
     * flat array with units for sizes
     * @var array
     */
    protected $_units = array( 'B', 'KB', 'MB', 'GB', 'TB');
    
    /**
     * timeout in sec for tcp socket connections
     * @var type 
     */
    protected $_iTimeoutTcp=5;
    
    /**
     * point to the plugin directory
     * @var string
     */
    protected $_sPluginDir=__DIR__.'/../plugins';

    // ----------------------------------------------------------------------
    // CONSTRUCTOR
    // ----------------------------------------------------------------------

    /**
     * constructor (nothing)
     */
    public function __construct() {
        
    }
    
    // ----------------------------------------------------------------------
    // PRIVATE FUNCTIONS
    // ----------------------------------------------------------------------

    /**
     * internal: create basic array values for metadata
     * @return boolean
     */
    protected function _createDefaultMetadata() {

        $this->_aData = array(
            "name" => $this->_aConfig["name"],
            "description" => $this->_aConfig["description"],
            "parent" => isset($this->_aConfig["parent"]) ? $this->_aConfig["parent"] : false,
            "result" => RESULT_UNKNOWN,
            "value" => false,
            "type" => false,
            "time" => false,
        );
        return true;
    }

    /**
     * set a result value of a check
     * @param type $iResult
     * @return type
     */
    protected function _setResult($iResult) {
        return $this->_aData["result"] = (int) $iResult;
    }

    /**
     * set a result value of a check
     * @param type $iResult
     * @return type
     */
    protected function _setOutput($s) {
        return $this->_aData["value"] = (string) $s;
    }
    
    /**
     * put counter data to result set
     * @param type $aParams
     * @return boolean
     */
    protected function _setCounter($aParams){
        if(is_array($aParams) && count($aParams)){
            foreach(array('type', 'count', 'visual') as $sMyKey){
                if(isset($aParams[$sMyKey])){
                    $this->_aData[$sMyKey]=$aParams[$sMyKey];
                }
            }
        }
        return true;
    }

    /**
     * set result and output 
     * @param integer  $iResult   value; use a RESULT_XYZ constant
     * @param string   $s         message text
     * @param array    $aCounter  optional: counter with array keys type, count, visual
     * @return boolean
     */
    protected function _setReturn($iResult, $s, $aCounter=array()) {
        $this->_setResult($iResult);
        $this->_setOutput($s);
        $this->_setCounter($aCounter);
        return true;
    }
    
    /**
     * check a given array if it contains wanted keys
     * @param array  $aConfig   array to verify
     * @param string $sKeyList  key or keys as comma seprated list 
     * @return boolean
     */
    protected function _checkArrayKeys($aConfig, $sKeyList) {
        foreach (explode(",", $sKeyList) as $sKey) {
            if (!array_key_exists($sKey, $aConfig)) {
                header('HTTP/1.0 503 Service Unavailable');
                die('<h1>503 Service Unavailable</h1>'
                        . '<h2>Details</h2>'
                        .__METHOD__ . " - array of check parameters requires the keys [$sKeyList] - but key <code>$sKey</code> was not found in config array."
                        . "<pre>" . print_r($aConfig, true) .'</pre>'
                );
            }
            if (is_null($aConfig[$sKey])) {
                header('HTTP/1.0 503 Service Unavailable');
                die('<h1>503 Service Unavailable</h1>'
                        . '<h2>Details</h2>'
                        .__METHOD__ . " - key <code>$sKey</code> is empty in config array"
                        . "<pre>" . print_r($aConfig, true) .'</pre>'
                );
            }
        }
        return true;
    }

    // ----------------------------------------------------------------------
    // PUBLIC FUNCTIONS
    // ----------------------------------------------------------------------

    /**
     * perform a check
     * @param type $aConfig
     * Array
     * (
     *     [name] => Dummy
     *     [description] => Dummy Test
     *     [check] => array(
     *         [function] => [check function] // i.e. Simple
     *         [params] => [array]            // optional; arguments for Check function
     *                                        // its keys depend on the function  
     *     )
     * )
     * 
     * @return array
     */
    public function makeCheck($aConfig) {
        $this->_iStart = microtime(true);
        $this->_checkArrayKeys($aConfig, "name,description,check");
        $this->_checkArrayKeys($aConfig["check"], "function");

        $this->_aConfig = $aConfig;
        $this->_createDefaultMetadata();

        $sCheck = preg_replace('/[^a-zA-Z0-9]/', '', $this->_aConfig["check"]["function"]);
        $aParams = array_key_exists("params", $this->_aConfig["check"]) ? $this->_aConfig["check"]["params"] : array();
        
        // try to load as plugin from a plugin file
        $sPluginFile= strtolower($this->_sPluginDir.'/checks/'.$sCheck.'.php');
        // echo "plugin file: $sPluginFile<br>\n";
        $sCheckClass = 'check'.$sCheck;
        if (!class_exists($sCheckClass)){
            if (file_exists($sPluginFile)) {   
                require_once($sPluginFile);
            }
        }

        if (!class_exists($sCheckClass)){
            header('HTTP/1.0 503 Service Unavailable');
            die('<h1>503 Service Unavailable</h1>'
                    . '<h2>Details</h2>'
                    .__METHOD__ . " - check class not found: <code>$sCheckClass</code>"
                    . "<pre>" . print_r($aConfig, true) .'</pre>'
                    ."<h2>Known checks</h2>\n".print_r($this->listChecks(), 1)
            );
        }
            
        $oPlogin = new $sCheckClass;
        $aResponse=$oPlogin->run($aParams); 
        if(!is_array($aResponse)){
            header('HTTP/1.0 503 Service Unavailable');
            die('<h1>503 Service Unavailable</h1>'
                    . '<h2>Details</h2>'
                    .__METHOD__ . " - plugin : $sCheck does not responses an array"
                    . "<pre>INPUT " . print_r($aConfig, true) .'</pre>'
                    . "<pre>RESPONSE " . print_r($aResponse, true) .'</pre>'
            );
        }
        if(count($aResponse)<2){
            header('HTTP/1.0 503 Service Unavailable');
            die('<h1>503 Service Unavailable</h1>'
                    . '<h2>Details</h2>'
                    .__METHOD__ . " - plugin : $sCheck does not responses the minimum of 2 array values"
                    . "<pre>INPUT " . print_r($aConfig, true) .'</pre>'
                    . "<pre>RESPONSE " . print_r($aResponse, true) .'</pre>'
            );
        }
        if(!isset($aResponse[2])){
            $aResponse[2]=array();
        }
        $this->_setReturn($aResponse[0], $aResponse[1], $aResponse[2]);

        $this->_aData['time'] = number_format((microtime(true) - $this->_iStart) * 1000, 3) . 'ms';
        // ... and send response 
        return $this->respond();
    }

    /**
     * list all available check functions. This is a helper class you can call
     * to get an overview over built in functions and plugins. 
     * You get a flat array with all function names.
     * @return array
     */
    public function listChecks() {
        $aReturn = array();
        // return internal protected fuctions named "check[whatever]"
        $class = new ReflectionClass($this);
        foreach ($class->getMethods(ReflectionMethod::IS_PROTECTED) as $oReflectionMethod) {
            if (strpos($oReflectionMethod->name, "check") === 0) {
                $aReturn[(string) $oReflectionMethod->name]=1;
            }
        }
        // return checks from plugins subdir
        foreach(glob($this->_sPluginDir.'/checks/*.php') as $sPluginFile){
            $aReturn[str_replace('.php', '', basename($sPluginFile))] = 1;
        }
        ksort($aReturn);
        return array_keys($aReturn);
    }

    /**
     * final call of class: send response (data array)
     * @return type
     */
    public function respond() {
        return $this->_aData;
    }

    // ----------------------------------------------------------------------
    // CHECK FUNCTIONS (protected)
    // ----------------------------------------------------------------------

    /**
     * helper function: read certificate data
     * called in checkCert()
     * @param string  $sUrl         url to connect
     * @param boolean $bVerifyCert  flag: verify certificate; default: no check
     * @return array
     */
    protected function _certGetInfos($sUrl, $bVerifyCert) {
        $iTimeout=10;
        $aUrldata=parse_url($sUrl);
        $sHost = isset($aUrldata['host']) ? $aUrldata['host'] : false;
        $iPort = isset($aUrldata['port']) ? $aUrldata['port'] : ((isset($aUrldata['scheme']) && $aUrldata['scheme'] === 'https') ? 443 : false);

        $aSsl=array('capture_peer_cert' => true);
        if($bVerifyCert){
            $aSsl['verify_peer']=false;
            $aSsl['verify_peer_name']=false;
        };
        $get = stream_context_create(array('ssl' => $aSsl));
        if(!$get){
            return array('_error' => 'Error: Cannot create stream_context');
        }
        $errno=-1;
        $errstr="stream_socket_client failed.";
        $read = stream_socket_client("ssl://$sHost:$iPort", $errno, $errstr, $iTimeout, STREAM_CLIENT_CONNECT, $get);
        if(!$read){
            return array('_error' => "Error $errno: $errstr; cannot create stream_socket_client with given stream_context to ssl://$sHost:$iPort; you can try to set the flag [verify] to false to check expiration date only.");
        }
        $cert = stream_context_get_params($read);
        if(!$cert){
            return array('_error' => "Error: socket was connected to ssl://$sHost:$iPort - but I cannot read certificate infos with stream_context_get_params ");
        }
        $certinfo = openssl_x509_parse($cert['options']['ssl']['peer_certificate']);
        return $certinfo;
    }


    /**
     * get human readable space value
     * @param type $size
     * @return string
     */
    protected function _getHrSize($size){
        $power = $size > 0 ? floor(log($size, 1024)) : 0;
        return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $this->_units[$power];
    }
    /**
     * get a space in a real value if an integer has added MB|GB|...
     * @param type $sValue
     * @return integer
     */
    protected function _getSize($sValue){
        if(is_int($sValue)){
            return $sValue;
        }
        $power=0;
        foreach($this->_units as $sUnit){
            if (preg_match('/^[0-9\.\ ]*'.$sUnit.'/', $sValue)){
                $i=preg_replace('/([0-9\.]*).*/', '$1', $sValue);
                $iReal=$i*pow(1024, $power);
                // die("FOUND: $sValue with unit ${sUnit} - 1024^$power * $i = $iReal");
                return $iReal;
            }
            $power++;
        }
        header('HTTP/1.0 503 Service Unavailable');
        die('<h1>503 Service Unavailable</h1>'
                . '<h2>Details</h2>'
                .__METHOD__ . " ERROR in space value parameter - there is no size unit in [$sValue] - allowed size units are " . implode('|', $this->_units)
        );
    }


    
    /**
     * compare function for 2 values
     * @param any      $verifyValue  search value
     * @param string   $sCompare     compare function; it is one of
     *                               IS - 
     *                               GE - greater or equal
     *                               GT - greater
     * @param any      $value        existing value
     * @return boolean
     */
    protected function _compare($value, $sCompare, $verifyValue){
        switch ($sCompare){
            case "IS":
                return $value===$verifyValue;
                break;
            case "GE":
                return $value>=$verifyValue;
                break;
            case "GT":
                return $value>$verifyValue;
                break;
            case "HAS":
                return !!(strstr($value, $verifyValue)!==false);
                break;
            default:
                header('HTTP/1.0 503 Service Unavailable');
                die('<h1>503 Service Unavailable</h1>'
                        . '<h2>Details</h2>'
                        .__METHOD__ . " - FATAL ERROR: a compare function [$sCompare] is not implemented (yet)."
                );
                break;
        }
        return false;
    }



}
