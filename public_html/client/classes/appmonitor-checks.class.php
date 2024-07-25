<?php

if (!defined('RESULT_OK')) {
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
 * 2021-12-14  0.93  axel.hahn@iml.unibe.ch  split plugins into single files; added key group in a check<br>
 * 2023-06-02  0.125 axel.hahn@unibe.ch      replace array_key_exists for better readability
 * 2024-07-22  0.137 axel.hahn@unibe.ch      php 8 only: use typed variables
 * --------------------------------------------------------------------------------<br>
 * @version 0.137
 * @author Axel Hahn
 * @link TODO
 * @license GPL
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL 3.0
 * @package IML-Appmonitor
 */
class appmonitorcheck
{
    // ----------------------------------------------------------------------
    // CONFIG
    // ----------------------------------------------------------------------

    /**
     * starting time using microtime
     * @var float
     */
    protected float $_iStart = 0;

    /**
     * config container
     * @var array
     */
    protected array $_aConfig = [];

    /**
     * data of all checks
     * @var array
     */
    protected array $_aData = [];

    /**
     * flat array with units for sizes
     * @var array
     */
    protected array $_units = ['B', 'KB', 'MB', 'GB', 'TB'];

    /**
     * timeout in sec for tcp socket connections
     * @var integer
     */
    protected int $_iTimeoutTcp = 5;

    /**
     * point to the plugin directory
     * @var string
     */
    protected string $_sPluginDir = __DIR__ . '/../plugins';

    // ----------------------------------------------------------------------
    // CONSTRUCTOR
    // ----------------------------------------------------------------------

    /**
     * Constructor (nothing here)
     */
    public function __construct()
    {

    }

    // ----------------------------------------------------------------------
    // PRIVATE FUNCTIONS
    // ----------------------------------------------------------------------

    /**
     * Internal: create basic array values for metadata
     * @return boolean
     */
    protected function _createDefaultMetadata(): bool
    {

        $this->_aData = [
            "name" => $this->_aConfig["name"],
            "description" => $this->_aConfig["description"],
            "group" => isset($this->_aConfig["group"]) ? $this->_aConfig["group"] : false,
            "parent" => isset($this->_aConfig["parent"]) ? $this->_aConfig["parent"] : false,
            "result" => RESULT_UNKNOWN,
            "value" => false,
            "type" => false,
            "time" => false,
        ];
        return true;
    }

    /**
     * Set the result value of a check
     * @param integer  $iResult  result code; one of RESULT_OK|RESULT_WARNING|RESULT_ERROR|RESULT_UNKNOWN
     * @return bool
     */
    protected function _setResult(int $iResult): bool
    {
        $this->_aData["result"] = (int) $iResult;
        return true;
    }

    /**
     * Set a result value of a check
     * @param string  $s  value; message text for this result
     * @return bool
     */
    protected function _setOutput(string $s): bool
    {
        $this->_aData["value"] = $s;
        return true;
    }

    /**
     * Put counter data to result set
     * @param array  $aParams  array  with possible keys type, count, visual
     * @return boolean
     */
    protected function _setCounter(array $aParams): bool
    {
        if (is_array($aParams) && count($aParams)) {
            foreach (['type', 'count', 'visual'] as $sMyKey) {
                if (isset($aParams[$sMyKey])) {
                    $this->_aData[$sMyKey] = $aParams[$sMyKey];
                }
            }
        }
        return true;
    }

    /**
     * Set result and output 
     * @param integer  $iResult   result code; one of RESULT_OK|RESULT_WARNING|RESULT_ERROR|RESULT_UNKNOWN
     * @param string   $s         message text
     * @param array    $aCounter  optional: counter with array keys type, count, visual
     * @return boolean
     */
    protected function _setReturn(int $iResult, string $s, array $aCounter = [])
    {
        $this->_setResult($iResult);
        $this->_setOutput($s);
        $this->_setCounter($aCounter);
        return true;
    }

    /**
     * Check a given array if it contains wanted keys
     * @param array  $aConfig   array to verify
     * @param string $sKeyList  key or keys as comma seprated list 
     * @return boolean
     */
    protected function _checkArrayKeys($aConfig, $sKeyList)
    {
        foreach (explode(",", $sKeyList) as $sKey) {
            if (!isset($aConfig[$sKey])) {
                header('HTTP/1.0 503 Service Unavailable');
                die('<h1>503 Service Unavailable</h1>'
                    . '<h2>Details</h2>'
                    . __METHOD__ . " - array of check parameters requires the keys [$sKeyList] - but key <code>$sKey</code> was not found in config array."
                    . "<pre>" . print_r($aConfig, true) . '</pre>'
                );
            }
            if (is_null($aConfig[$sKey])) {
                header('HTTP/1.0 503 Service Unavailable');
                die('<h1>503 Service Unavailable</h1>'
                    . '<h2>Details</h2>'
                    . __METHOD__ . " - key <code>$sKey</code> is empty in config array"
                    . "<pre>" . print_r($aConfig, true) . '</pre>'
                );
            }
        }
        return true;
    }

    // ----------------------------------------------------------------------
    // PUBLIC FUNCTIONS
    // ----------------------------------------------------------------------

    /**
     * Perform a check
     * @param array $aConfig  configuration array for a check, eg.
     * <code>
     * [
     *     [name] => Dummy
     *     [description] => Dummy Test
     *     [check] => [
     *         [function] => [check function] // i.e. Simple
     *         [params] => [array]            // optional; arguments for Check function
     *                                        // its keys depend on the function  
     *     ]
     * ]
     * </code>
     * @return array
     */
    public function makeCheck(array $aConfig): array
    {
        $this->_iStart = microtime(true);
        $this->_checkArrayKeys($aConfig, "name,description,check");
        $this->_checkArrayKeys($aConfig["check"], "function");

        $this->_aConfig = $aConfig;
        $this->_createDefaultMetadata();

        $sCheck = preg_replace('/[^a-zA-Z0-9]/', '', $this->_aConfig["check"]["function"]);
        $aParams = $this->_aConfig["check"]["params"] ?? [];

        // try to load as plugin from a plugin file
        $sPluginFile = strtolower($this->_sPluginDir . '/checks/' . $sCheck . '.php');
        // echo "plugin file: $sPluginFile<br>\n";
        $sCheckClass = 'check' . $sCheck;
        if (!class_exists($sCheckClass)) {
            if (file_exists($sPluginFile)) {
                require_once ($sPluginFile);
            }
        }

        if (!class_exists($sCheckClass)) {
            header('HTTP/1.0 503 Service Unavailable');
            die('<h1>503 Service Unavailable</h1>'
                . '<h2>Details</h2>'
                . __METHOD__ . " - check class not found: <code>$sCheckClass</code>"
                . "<pre>" . print_r($aConfig, true) . '</pre>'
                . "<h2>Known checks</h2>\n" . print_r($this->listChecks(), 1)
            );
        }

        $oPlugin = new $sCheckClass;
        $aResponse = $oPlugin->run($aParams);
        if (!is_array($aResponse)) {
            header('HTTP/1.0 503 Service Unavailable');
            die('<h1>503 Service Unavailable</h1>'
                . '<h2>Details</h2>'
                . __METHOD__ . " - plugin : $sCheck does not responses an array"
                . "<pre>INPUT " . print_r($aConfig, true) . '</pre>'
                . "<pre>RESPONSE " . print_r($aResponse, true) . '</pre>'
            );
        }
        if (count($aResponse) < 2) {
            header('HTTP/1.0 503 Service Unavailable');
            die('<h1>503 Service Unavailable</h1>'
                . '<h2>Details</h2>'
                . __METHOD__ . " - plugin : $sCheck does not responses the minimum of 2 array values"
                . "<pre>INPUT " . print_r($aConfig, true) . '</pre>'
                . "<pre>RESPONSE " . print_r($aResponse, true) . '</pre>'
            );
        }
        if (!isset($aResponse[2]) || !$aResponse[2]) {
            $aResponse[2] = [];
        }
        $this->_setReturn($aResponse[0], $aResponse[1], $aResponse[2]);
        if (!$this->_aData['group'] && method_exists($oPlugin, "getGroup")) {
            $this->_aData['group'] = $oPlugin->getGroup($aParams);
        }

        $this->_aData['time'] = number_format((microtime(true) - $this->_iStart) * 1000, 3) . 'ms';
        // ... and send response 
        return $this->respond();
    }

    /**
     * List all available checks. This is a helper class you can call
     * to get an overview over built in functions and plugins. 
     * You get a flat array with all function names.
     * @return array
     */
    public function listChecks(): array
    {
        $aReturn = [];
        // return internal protected fuctions named "check[whatever]"
        $class = new ReflectionClass($this);
        foreach ($class->getMethods(ReflectionMethod::IS_PROTECTED) as $oReflectionMethod) {
            if (strpos($oReflectionMethod->name, "check") === 0) {
                $aReturn[(string) $oReflectionMethod->name] = 1;
            }
        }
        // return checks from plugins subdir
        foreach (glob($this->_sPluginDir . '/checks/*.php') as $sPluginFile) {
            $aReturn[str_replace('.php', '', basename($sPluginFile))] = 1;
        }
        ksort($aReturn);
        return array_keys($aReturn);
    }

    /**
     * Final call of class: send response (data array)
     * @return array
     */
    public function respond()
    {
        return $this->_aData;
    }

    // ----------------------------------------------------------------------
    // CHECK FUNCTIONS (protected)
    // ----------------------------------------------------------------------

    /**
     * Helper function: read certificate data
     * called in checkCert()
     * 
     * @param string  $sUrl         url to connect
     * @param boolean $bVerifyCert  flag: verify certificate; default: no check
     * @return array
     */
    protected function _certGetInfos(string $sUrl, bool $bVerifyCert): array
    {
        $iTimeout = 10;
        $aUrldata = parse_url($sUrl);
        $sHost = isset($aUrldata['host']) ? $aUrldata['host'] : false;
        $iPort = isset($aUrldata['port']) ? $aUrldata['port'] : ((isset($aUrldata['scheme']) && $aUrldata['scheme'] === 'https') ? 443 : false);

        $aSsl = ['capture_peer_cert' => true];
        if ($bVerifyCert) {
            $aSsl['verify_peer'] = false;
            $aSsl['verify_peer_name'] = false;
        }
        ;
        $get = stream_context_create(['ssl' => $aSsl]);
        if (!$get) {
            return ['_error' => 'Error: Cannot create stream_context'];
        }
        $errno = -1;
        $errstr = "stream_socket_client failed.";
        $read = stream_socket_client("ssl://$sHost:$iPort", $errno, $errstr, $iTimeout, STREAM_CLIENT_CONNECT, $get);
        if (!$read) {
            return ['_error' => "Error $errno: $errstr; cannot create stream_socket_client with given stream_context to ssl://$sHost:$iPort; you can try to set the flag [verify] to false to check expiration date only."];
        }
        $cert = stream_context_get_params($read);
        if (!$cert) {
            return ['_error' => "Error: socket was connected to ssl://$sHost:$iPort - but I cannot read certificate infos with stream_context_get_params "];
        }
        return openssl_x509_parse($cert['options']['ssl']['peer_certificate']);
    }


    /**
     * Get human readable space value
     * @param integer $size
     * @return string
     */
    protected function _getHrSize(int $size): string
    {
        $power = $size > 0 ? floor(log($size, 1024)) : 0;
        return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $this->_units[$power];
    }

    /**
     * get a space in a real value if an integer has added MB|GB|...
     * @param string $sValue
     * @return integer
     */
    protected function _getSize(string $sValue): int
    {
        if (is_int($sValue)) {
            return $sValue;
        }
        $power = 0;
        foreach ($this->_units as $sUnit) {
            if (preg_match('/^[0-9\.\ ]*' . $sUnit . '/', $sValue)) {
                $i = preg_replace('/([0-9\.]*).*/', '$1', $sValue);
                $iReal = $i * pow(1024, $power);
                // die("FOUND: $sValue with unit ${sUnit} - 1024^$power * $i = $iReal");
                return $iReal;
            }
            $power++;
        }
        header('HTTP/1.0 503 Service Unavailable');
        die('<h1>503 Service Unavailable</h1>'
            . '<h2>Details</h2>'
            . __METHOD__ . " ERROR in space value parameter - there is no size unit in [$sValue] - allowed size units are " . implode('|', $this->_units)
        );
    }

}
