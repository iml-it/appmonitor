<?php

define("RESULT_OK", 0);
define("RESULT_UNKNOWN", 1);
define("RESULT_WARNING", 2);
define("RESULT_ERROR", 3);

/**
 * APPMONITOR CLIENT CHECKS<br>
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
 * --------------------------------------------------------------------------------<br>
 * @version 0.47
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
    private $_aConfig = array();

    /**
     * data of all checks
     * @var array
     */
    private $_aData = array();
    

    /**
     * flat array with units for sizes
     * @var array
     */
    protected $_units = array( 'B', 'KB', 'MB', 'GB', 'TB');
    
    /**
     * timout in sec for tcp socket connections
     * @var type 
     */
    protected $_iTimeoutTcp=5;

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
     * create basic array values for metadata
     * @return boolean
     */
    private function _createDefaultMetadata() {

        $this->_aData = array(
            "name" => $this->_aConfig["name"],
            "description" => $this->_aConfig["description"],
            "result" => RESULT_UNKNOWN,
            "value" => false,
        );
        return true;
    }

    /**
     * set a result value of a check
     * @param type $iResult
     * @return type
     */
    private function _setResult($iResult) {
        return $this->_aData["result"] = (int) $iResult;
    }

    /**
     * set a result value of a check
     * @param type $iResult
     * @return type
     */
    private function _setOutput($s) {
        return $this->_aData["value"] = (string) $s;
    }

    /**
     * set result and output
     * @param type $iResult
     * @param type $s
     * @return boolean
     */
    private function _setReturn($iResult, $s) {
        $this->_setResult($iResult);
        $this->_setOutput($s);
        return true;
    }

    private function _checkArrayKeys($aConfig, $sKeyList) {
        foreach (explode(",", $sKeyList) as $sKey) {
            if (!array_key_exists($sKey, $aConfig)) {
                header('HTTP/1.0 503 Service Unavailable');
                die('ERROR in ' . __CLASS__ . "<br>array requires the keys [$sKeyList] - but key '$sKey' was not found in config array <pre>" . print_r($aConfig, true));
            }
            if (is_null($aConfig[$sKey])) {
                header('HTTP/1.0 503 Service Unavailable');
                die('ERROR in ' . __CLASS__ . "<br> key '$sKey' is empty in config array <pre>" . print_r($aConfig, true));
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

        $sCheck = "check" . $this->_aConfig["check"]["function"];
        if (!method_exists($this, $sCheck)) {
            header('HTTP/1.0 503 Service Unavailable');
            die(__CLASS__ . " check not found: $sCheck <pre>" . print_r($aConfig, true));
        }
        $aParams = array_key_exists("params", $this->_aConfig["check"]) ? $this->_aConfig["check"]["params"] : array();

        // call the check ...
        call_user_func(array($this, $sCheck), $aParams);

        $this->_aData['time'] = number_format((microtime(true) - $this->_iStart) * 1000, 3) . 'ms';
        // echo "<pre>"; print_r($this->listChecks()); die();
        // ... and send response 
        return $this->respond();
    }

    /**
     * list all available check functions. This is a helper class you cann call
     * to get an overview overbuilt in functions. You get a flat array with
     * all function names.
     * @return array
     */
    public function listChecks() {
        $aReturn = array();
        $class = new ReflectionClass($this);
        foreach ($class->getMethods(ReflectionMethod::IS_PRIVATE) as $oReflectionMethod) {
            if (strpos($oReflectionMethod->name, "check") === 0) {
                $aReturn[] = (string) $oReflectionMethod->name;
            }
        }
        return $aReturn;
    }

    /**
     * final call of class: send response (data array)
     * @return type
     */
    public function respond() {
        return $this->_aData;
    }

    // ----------------------------------------------------------------------
    // CHECK FUNCTIONS (private)
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
     * check SSL certificate 
     * @param array $aParams
     * array(
     *     "url"       optional: url to connect check; default: own protocol + server
     *     "verify"    optional: flag for verification of certificate or check for expiration only; default=true (=verification is on)
     *     "warning"   optional: count of days to warn; default=30
     * )
     * @return boolean
     */
    private function checkCert($aParams) {
        $sUrl = isset($aParams["url"]) 
                ? $aParams["url"] 
                : 'http'. ($_SERVER['HTTPS'] ? 's' : '') . '://' . $_SERVER['SERVER_NAME'] .':' . $_SERVER['SERVER_PORT']
                ;
        $bVerify = isset($aParams["verify"])  ? !!$aParams["verify"] : true;
        $iWarn   = isset($aParams["warning"]) ? (int)($aParams["warning"]) : 30;

        $sMessage="Checked url: $sUrl ... ";
        $certinfo=$this->_certGetInfos($sUrl, $bVerify);
        if(isset($certinfo['_error'])){
            $this->_setReturn(RESULT_ERROR, $certinfo['_error'] . $sMessage);
            return true;
        }
        
        $sDNS=isset($certinfo['extensions']['subjectAltName']) ? $certinfo['extensions']['subjectAltName'] : false;
        $sHost=parse_url($url,PHP_URL_HOST);
        if(strstr($sDNS, 'DNS:'.$sHost)===false){
            $this->_setReturn(RESULT_ERROR, 'Wrong certificate: '.$sHost.' is not listed as DNS alias in ['.$sDNS.']  ' . $sMessage);
            return true;
        }
        
        $iDaysleft = round(($certinfo['validTo_time_t'] - date('U')) / 60 / 60 / 24);
        $sMessage.= 'Issuer: '. $sIssuer=$certinfo['issuer']['O'] 
                . '; valid from: '. date("Y-m-d H:i", $certinfo['validFrom_time_t'])
                . ' to '.date("Y-m-d H:i", $certinfo['validTo_time_t']).' '
                . ( $iDaysleft ? "($iDaysleft days left)" : "expired since ".(-$iDaysleft)." days.")
                ;
        if ($iDaysleft<0) {
            $this->_setReturn(RESULT_ERROR, 'Expired! ' . $sMessage);
            return true;
        }
        if ($iDaysleft<=$iWarn) {
            $this->_setReturn(RESULT_WARNING, 'Expires soon. ' . $sMessage);
            return true;
        }
        // echo '<pre>';
        $this->_setReturn(RESULT_OK, 'OK. ' 
                .($bVerify ? 'Certificate is valid. ' : '(Verification is disabled; Check for expiration only.) ' )
                . $sMessage);
        return true;
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
            if (preg_match('/^[0-9\.]*'.$sUnit.'/', $sValue)){
                $i=preg_replace('/([0-9\.]*).*/', '$1', $sValue);
                $iReal=$i*pow(1024, $power);
                // die("FOUND: $sValue with unit ${sUnit} - 1024^$power * $i = $iReal");
                return $iReal;
            }
            $power++;
        }
        header('HTTP/1.0 503 Service Unavailable');
        die("ERROR in space value parameter - there is no size unit in [$sValue] - allowed size units are " . implode('|', $this->_units));
        return false;
    }

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
    private function checkDiskfree($aParams) {
        $this->_checkArrayKeys($aParams, "directory", "critical");
        
        $sDirectory = $aParams["directory"];
        if(!is_dir($sDirectory)){
            $this->_setReturn(RESULT_ERROR, 'directory [' . $sDirectory . '] does not exist. Maybe it is wrong or is not mounted.');
            return true;
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
                $this->_setReturn(RESULT_OK, $sMessage.' Warning level is not reached yet (still '.$this->_getHrSize($iSpaceLeft-$iWarn).' over warning limit).');
                return true;
            }
            if ($iWarn>$iSpaceLeft && $iCritical<$iSpaceLeft){
                $this->_setReturn(RESULT_WARNING, $sMessage.' Warning level '.$this->_getHrSize($iWarn).' was reached (space is '.$this->_getHrSize($iWarn-$iSpaceLeft).' below warning limit; still '.$this->_getHrSize($iSpaceLeft-$iCritical).' over critical limit).');
                return true;
            }
        }
        // check space
        if ($iCritical<$iSpaceLeft){
            $this->_setReturn(RESULT_OK, $sMessage .' Minimum is not reached yet (still '.$this->_getHrSize($iSpaceLeft-$iCritical).' over critical limit).');
        } else {
            $this->_setReturn(RESULT_ERROR, $sMessage);
        }
        return true;
    }
    /**
     * check a file
     * @param array $aParams
     * array(
     *     "filename"    directory that must exist
     *     "exists"      "filename" must exist/ must be absent
     *     "dir"         filetype directory
     *     "file"        filetype file
     *     "link"        filetype symbolic link
     *     "executable"  flag executable
     *     "readable"    flag is readable
     *     "writable"    flag is writable
     * )
     * @return boolean
     */
    private function checkFile($aParams) {
        $aOK = array();
        $aErrors = array();
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
        foreach (array('dir', 'executable', 'file', 'link', 'readable', 'writable') as $sFiletest) {
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
            $this->_setReturn(RESULT_ERROR, 'file test [' . $sFile . '] ' . $sMessage);
        } else {
            $this->_setReturn(RESULT_OK, 'file test [' . $sFile . '] ' . $sMessage);
        }
        return true;
    }

    /**
     * make http request and test response body
     * @param array $aParams
     * array(
     *     "url"       url to fetch
     *     "contains"  string that must exist in response body
     * )
     * @param integer $iTimeout  value in sec; default: 5sec
     */
    private function checkHttpContent($aParams, $iTimeout = 5) {
        $this->_checkArrayKeys($aParams, "url,contains");
        if (!function_exists("curl_init")) {
            header('HTTP/1.0 503 Service Unavailable');
            die("ERROR: PHP CURL module is not installed.");
        }
        $ch = curl_init($aParams["url"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, $iTimeout);
        $res = curl_exec($ch);
        curl_close($ch);

        if (!$res) {
            $this->_setReturn(RESULT_ERROR, 'ERROR: failed to fetch ' . $aParams["url"] . '.');
        } else {
            if (!strpos($res, $aParams["contains"]) === false) {
                $this->_setReturn(RESULT_OK, 'OK: The text "' . $aParams["contains"] . '" was found in response of ' . $aParams["url"] . '.');
            } else {
                $this->_setReturn(RESULT_ERROR, 'ERROR: The text ' . $aParams["contains"] . ' was NOT found in response of ' . $aParams["url"] . '.');
            }
        }
        return $res;
    }

    /**
     * check mysql connection to a database using mysqli
     * @param array $aParams
     * array(
     *     "server" 
     *     "user" 
     *     "password" 
     *     "db" 
     *     "port"     <<< optional
     * )
     */
    private function checkMysqlConnect($aParams) {
        $this->_checkArrayKeys($aParams, "server,user,password,db");
        $db = (isset($aParams["port"]) && $aParams["port"]) 
                ? mysqli_connect($aParams["server"], $aParams["user"], $aParams["password"], $aParams["db"], $aParams["port"])
                : mysqli_connect($aParams["server"], $aParams["user"], $aParams["password"], $aParams["db"])
                ;
        if ($db) {
            $this->_setReturn(RESULT_OK, "OK: Mysql database " . $aParams["db"] . " was connected");
            mysqli_close($db);
            return true;
        } else {
            $this->_setReturn(RESULT_ERROR, "ERROR: Mysql database " . $aParams["db"] . " was not connected. " . mysqli_connect_error());
            return false;
        }
    }

    /**
     * check if system is listening to a given port
     * @param array $aParams
     * array(
     *     "port" 
     *     "host"  (optional: 127.0.0.1 is default)
     * )
     * @return boolean
     */
    private function checkPortTcp($aParams) {
        $this->_checkArrayKeys($aParams, "port");

        $sHost = array_key_exists('host', $aParams) ? $aParams['host'] : '127.0.0.1';
        $iPort = (int) $aParams['port'];

        // from http://php.net/manual/de/sockets.examples.php

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            $this->_setReturn(RESULT_UNKNOWN, "ERROR: $sHost:$iPort was not checked. socket_create() failed: " . socket_strerror(socket_last_error()));
            return false;
        }
        // set socket timeout
        socket_set_option(
            $socket,
            SOL_SOCKET,  // socket level
            SO_SNDTIMEO, // timeout option
            array(
              "sec"=>$this->_iTimeoutTcp, // timeout in seconds
              "usec"=>0
              )
            );

        $result = socket_connect($socket, $sHost, $iPort);
        if ($result === false) {
            $this->_setReturn(RESULT_ERROR, "ERROR: $sHost:$iPort failed. " . socket_strerror(socket_last_error($socket)));
            socket_close($socket);
            return false;
        } else {
            $this->_setReturn(RESULT_OK, "OK: $sHost:$iPort was connected.");
            socket_close($socket);
            return true;
        }
    }

    /**
     * most simple check: set values
     * @param array $aParams
     * array(
     *     "result" integer; RESUL_nn
     *     "value"  description text
     * )
     */
    private function checkSimple($aParams) {
        $this->_checkArrayKeys($aParams, "result,value");
        return $this->_setReturn((int) $aParams["result"], $aParams["value"]);
    }

    /**
     * check sqlite connection
     * @param array $aParams
     * array(
     *     "db"  full path of sqlite file 
     * )
     * @return boolean
     */
    private function checkSqliteConnect($aParams) {
        $this->_checkArrayKeys($aParams, "db");
        if (!file_exists($aParams["db"])) {
            $this->_setReturn(RESULT_ERROR, "ERROR: Sqlite database file " . $aParams["db"] . " does not exist.");
            return false;
        }
        try {
            // $db = new SQLite3($sqliteDB);
            // $db = new PDO("sqlite:".$sqliteDB);
            $o = new PDO("sqlite:" . $aParams["db"]);
            $this->_setReturn(RESULT_OK, "OK: Sqlite database " . $aParams["db"] . " was connected");
            return true;
        } catch (Exception $e) {
            $this->_setReturn(RESULT_ERROR, "ERROR: Sqlite database " . $aParams["db"] . " was not connected. " . $e->getMessage());
            return false;
        }
    }

}
