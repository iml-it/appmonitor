<?php
if (!class_exists('appmonitorcheck')){
    require_once 'appmonitor-checks.class.php';
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
 * APPMONITOR :: CLASS FOR CLIENT CHECKS<br>
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
 * 2014-10-24  0.5    axel.hahn@iml.unibe.ch<br>
 * 2014-11-21  0.6    axel.hahn@iml.unibe.ch  removed meta::ts <br>
 * 2018-08-23  0.50   axel.hahn@iml.unibe.ch  show version<br>
 * 2018-08-24  0.51   axel.hahn@iml.unibe.ch  method to show local status page<br>
 * 2018-08-27  0.52   axel.hahn@iml.unibe.ch  add pdo connect (starting with mysql)<br>
 * 2018-11-05  0.58   axel.hahn@iml.unibe.ch  additional flag in http check to show content<br>
 * 2019-05-31  0.87   axel.hahn@iml.unibe.ch  add timeout as param in connective checks (http, tcp, databases)<br>
 * 2020-05-03  0.110  axel.hahn@iml.unibe.ch  update renderHtmloutput<br>
 * --------------------------------------------------------------------------------<br>
 * @version 0.111
 * @author Axel Hahn
 * @link TODO
 * @license GPL
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL 3.0
 * @package IML-Appmonitor
 */
class appmonitor {

    /**
     * config: default ttl for server before requesting the client check again
     * value is in seconds
     * @var int
     */
    protected $_sVersion = 'php-client-v0.111';

    /**
     * config: default ttl for server before requesting the client check again
     * value is in seconds
     * @var int
     */
    protected $_iDefaultTtl = 300;

    /**
     * internal counter: greatest return value of all checks
     * @var type 
     */
    protected $_iMaxResult = false;

    /**
     * responded metadata of a website
     * @see _createDefaultMetadata()
     * @var array
     */
    protected $_aMeta = array();

    /**
     * repended array of all checks
     * @see addCheck()
     * @var array
     */
    protected $_aChecks = array();
    
    /**
     * for time measurements: start time
     * @var type 
     */
    protected $_iStart = false;
    
    /**
     * constructor: init data
     */
    public function __construct() {
        $this->_createDefaultMetadata();
    }

    // ----------------------------------------------------------------------
    // protected function
    // ----------------------------------------------------------------------

    /**
     * create basic array values for metadata
     * @return boolean
     */
    protected function _createDefaultMetadata() {
        $this->_iStart = microtime(true);
        $this->_aMeta = array(
            "host" => false,
            "website" => false,
            "ttl" => false,
            "result" => false,
            "time" => false,
            "version" => $this->_sVersion,
        );

        // fill with default values
        $this->setHost();
        $this->setWebsite();
        $this->setTTL();
        return true;
    }

    // ----------------------------------------------------------------------
    // setter
    // ----------------------------------------------------------------------

    /**
     * set the physical hostname for metadata; if no host is given then
     * the php_uname("n") will be used to set one.
     * 
     * @param string $s  hostname
     * @return bool
     */
    public function setHost($s = false) {
        if (!$s) {
            $s = php_uname("n");
        }
        return $this->_aMeta["host"] = $s;
    }

    /**
     * set a name for this website or application and its environment 
     * (dev, test, prod); 
     * 
     * If you have several application in subdirectories, i.e. /blog,  /shop...
     * then you should the path or any description to identify them too
     * 
     * if no argument is given the name of HTTP_HOST will be used
     * 
     * @param string $sNewHost  hostname
     * @return bool
     */
    public function setWebsite($s = false) {
        if (!$s && isset($_SERVER["HTTP_HOST"])) {
            $s = $_SERVER["HTTP_HOST"];
        }
        return $this->_aMeta["website"] = $s;
    }

    /**
     * set a ttl value in seconds to define how long a server should not
     * ask again for a new status of this instance
     * 
     * @param int $iTTl TTL value in sec
     * @return boolean
     */
    public function setTTL($iTTl = false) {
        if (!$iTTl) {
            $iTTl = $this->_iDefaultTtl;
        }
        return $this->_aMeta["ttl"] = $iTTl;
    }

    /**
     * set final result in meta data; if no value was given then it
     * sets the biggest value of any check.
     * @param integer  $iResult  set resultcode
     * @return boolean
     */
    public function setResult($iResult = false) {
        if ($iResult === false) {
            $iResult = $this->_iMaxResult; // see addCheck()
        }
        return $this->_aMeta["result"] = $iResult;
    }

    /**
     * add a check array;
     * @param type $aJob
     * @return type
     */
    public function addCheck($aJob = array()) {

        require_once 'appmonitor-checks.class.php';
        $oCheck = new appmonitorcheck();
        $aCheck = $oCheck->makecheck($aJob);
        
        // limit result code
        $iMyResult=isset($aJob['worstresult']) 
                ? min($aCheck["result"], $aJob['worstresult'])
                : $aCheck["result"]
                ;

        if (!$this->_iMaxResult || $iMyResult > $this->_iMaxResult) {
            $this->_iMaxResult = $iMyResult;
        }
        return $this->_aChecks[] = $aCheck;
    }

    /**
     * add an item to notifications meta data
     * 
     * @param string $sType   type ... one of email|slack
     * @param type $sValue    value
     * @param type $sKey      optional key (for key->value instead of list of values)
     * @return boolean
     */
    protected function _addNotification($sType, $sValue, $sKey = false) {
        $sTypeCleaned = preg_replace('/[^a-z]/', '', strtolower($sType));
        if (!isset($this->_aMeta['notifications'])) {
            $this->_aMeta['notifications'] = array();
        }
        if (!isset($this->_aMeta['notifications'][$sTypeCleaned])) {
            $this->_aMeta['notifications'][$sTypeCleaned] = array();
        }
        if ($sKey) {
            $this->_aMeta['notifications'][$sTypeCleaned][$sKey] = $sValue;
        } else {
            $this->_aMeta['notifications'][$sTypeCleaned][] = $sValue;
        }
        return true;
    }

    /**
     * add an email to notifications list
     * 
     * @param string $sEmailAddress  email address to add
     * @return boolean
     */
    public function addEmail($sEmailAddress) {
        return $this->_addNotification('email', $sEmailAddress);
    }

    /**
     * Add slack channel for notification
     * @param string  $sLabel
     * @param string  $sSlackWebhookUrl
     * @return type
     */
    public function addSlackWebhook($sLabel, $sSlackWebhookUrl) {
        return $this->_addNotification('slack', $sSlackWebhookUrl, $sLabel);
    }
    /**
     * add a tag for grouping in the server gui
     * 
     * @param string  $sLabel
     * @param string  $sSlackWebhookUrl
     * @return type
     */
    public function addTag($sTag) {
        if(!isset($this->_aMeta['tags'])){
            $this->_aMeta['tags']=array();
        }
        $this->_aMeta['tags'][]=$sTag;
        return true;
    }

    /**
     * check referers IP address if it matches any entry in the list
     * requires http request; CLI is always allowed
     * On deny this method exits with 403 response
     * 
     * @param array $aAllowedIps  array of allowed ip addresses / ranges
     *                            the ip must match from the beginning, i.e.
     *                            "127.0." will allow requests from 127.0.X.Y
     */
    public function checkIp($aAllowedIps = array()) {
        if (!isset($_SERVER['REMOTE_ADDR']) || !count($aAllowedIps)) {
            return true;
        }
        $sIP = $_SERVER['REMOTE_ADDR'];
        foreach ($aAllowedIps as $sIp2Check) {
            if (strpos($sIP, $sIp2Check) === 0) {
                return true;
            }
        }
        header('HTTP/1.0 403 Forbidden');
        die('ERROR: Your ip address [' . $sIP . '] has no access.');
    }

    /**
     * Check a token
     * requires http request; CLI is always allowed
     * On deny this method exits with 403 response
     * 
     * @param type $sVarname
     * @param type $sToken
     * @return boolean
     */
    public function checkToken($sVarname, $sToken) {
        if (!isset($_GET)) {
            return true;
        }
        if (isset($_GET[$sVarname]) && $_GET[$sVarname] === $sToken) {
            return true;
        }
        header('HTTP/1.0 403 Forbidden');
        die('ERROR: A token is required.');
    }

    // ----------------------------------------------------------------------
    // getter
    // ----------------------------------------------------------------------

    /**
     * list all available check functions. This is a helper class you cann call
     * to get an overview over built in functions. You get a flat array with
     * all function names.
     * @return array
     */
    public function listChecks() {
        require_once 'appmonitor-checks.class.php';
        $oCheck = new appmonitorcheck();
        return $oCheck->listChecks();
    }

    // ----------------------------------------------------------------------
    // checks
    // ----------------------------------------------------------------------

    /**
     * verify array values and in case of an error abort and show all found errors
     */
    protected function _checkData() {
        $aErrors = array();

        if (!count($this->_aChecks)) {
            $aErrors[] = "No checks have been defined.";
        }

        if ($this->_aMeta["result"] === false) {
            $aErrors[] = "method setResult was not used to set a final result for all checks.";
        }

        if (count($aErrors)) {
            $this->abort(
                '<h2>Error: client check is not complete</h2><p>Found errors:</p><ol><li>' . implode('<li>', $aErrors) . '</ol><br><br>'
                // .'Dump of your data so far:<pre>' . json_encode($this->getResults(), JSON_PRETTY_PRINT) . '</pre><hr>'
            );
        }
        return true;
    }

    // ----------------------------------------------------------------------
    // output
    // ----------------------------------------------------------------------

    /**
     * stop processing the client checks and abort with an error
     * @param string $sMessage
     */
    public function abort($sMessage){
        header('HTTP/1.0 503 Service Unavailable');
        die('<h1>503 Service Unavailable</h1>'.$sMessage);
    }
    
    /**
     * get full array for response with metadata and Checks
     * @return type
     */
    public function getResults() {
        return array(
            "meta" => $this->_aMeta,
            "checks" => $this->_aChecks,
        );
    }

    /**
     * output appmonitor values as JSON
     * @param bool  $bPretty     turn on pretty print; default is false
     * @param bool  $bHighlight  print syntax highlighted html code; $bPretty must be true to enable
     */
    public function render($bPretty = false, $bHighlight = false) {
        $this->_checkData();
        $this->_aMeta['time'] = number_format((microtime(true) - $this->_iStart) * 1000, 3) . 'ms';

        // JSON_PRETTY_PRINT reqires PHP 5.4
        if (!defined('JSON_PRETTY_PRINT')) {
            $bPretty = false;
        }
        if (!$bPretty) {
            $bHighlight = false;
            $sOut = json_encode($this->getResults());
        } else {
            $sOut = json_encode($this->getResults(), JSON_PRETTY_PRINT);
            if ($bHighlight) {
                $aMsg = array(
                    0 => "OK",
                    1 => "UNKNOWN",
                    2 => "WARNING",
                    3 => "ERROR"
                );
                foreach (array_keys($aMsg) as $iCode) {
                    $sOut = preg_replace('/(\"result\":\ ' . $iCode . ')/', '$1 <span class="result' . $iCode . '"> &lt;--- ' . $aMsg[$iCode] . ' </span>', $sOut);
                }

                $sOut = preg_replace('/:\ \"(.*)\"/U', ': "<span style="color:#66e;">$1</span>"', $sOut);
                $sOut = preg_replace('/:\ ([0-9]*)/', ': <span style="color:#3a3; font-weight: bold;">$1</span>', $sOut);
                $sOut = preg_replace('/\"(.*)\":/U', '"<span style="color:#840;">$1</span>":', $sOut);

                $sOut = preg_replace('/([{\[])/', '$1<blockquote>', $sOut);
                $sOut = preg_replace('/([}\]])/', '</blockquote>$1', $sOut);
                $sOut = str_replace('    ', '', $sOut);
                // $sOut = preg_replace('/([{}])/', '<span style="color:#a00; ">$1</span>', $sOut);
                // $sOut = preg_replace('/([\[\]])/', '<span style="color:#088; ">$1</span>', $sOut);

                $sOut = '<!DOCTYPE html><html><head>'
                        . '<style>'
                        . 'body{background:#e0e8f8; color:#235; font-family: verdana,arial;}'
                        . 'blockquote{background:rgba(0,0,0,0.03); border-left: 0px solid rgba(0,0,0,0.06); margin: 0 0 0 3em; padding: 0; border-radius: 1em; border-top-left-radius: 0;}'
                        . 'blockquote blockquote:hover{; }'
                        . 'blockquote blockquote blockquote:hover{border-color: #808;}'
                        . 'pre{background:rgba(0,0,0,0.05); padding: 1em; border-radius: 1em;}'
                        . '.result0{background:#aca; border-right: 0em solid #080;}'
                        . '.result1{background:#666; border-right: 0em solid #ccc;}'
                        . '.result2{background:#fc9; border-right: 0em solid #860;}'
                        . '.result3{background:#800; border-right: 0em solid #f00;}'
                        . '</style>'
                        . '<title>' . __CLASS__ . '</title>'
                        . '</head><body>'
                        . '<h1>' . __CLASS__ . ' :: debug</h1>'
                        . '<pre>'
                        . $sOut
                        . '</pre></body></html>';
            }
        }
        if (!$bHighlight) {
            header('Content-type: application/json');
            header('Cache-Control: cache');
            header('max-age: ' . $this->_aMeta["ttl"]);
        }
        echo $sOut;
        return $sOut;
    }
    /**
     * output appmonitor client status as single html page
     * 
     * @example <code>
     * ob_start();<br>
     * require __DIR__ . '/appmonitor/client/index.php';
     * $sJson=ob_get_contents();
     * ob_end_clean();
     * $oMonitor->renderHtmloutput($sJson);
     * </code>
     * 
     * @param string  $sJson  JSON of client output
     */
    public function renderHtmloutput($sJson) {


        header('Content-type: text/html');
        header('Cache-Control: cache');
        header('max-age: ' . $this->_aMeta["ttl"]);
        $aMsg = array(
            0 => "OK",
            1 => "UNKNOWN",
            2 => "WARNING",
            3 => "ERROR"
        );

        // $sOut = print_r($sJson, 1);
        $aData= json_decode($sJson, 1);

        // ----- Ausgabe human readable
        $sOut='';
        $sOut.='<h2>Metadata</h2>'
                . '<div class="meta'.(isset($aData['meta']['result'])  ? ' result'.$aData['meta']['result'] : '' ) .'">'
                . 'Status: '  . (isset($aData['meta']['result'])  ? $aMsg[$aData['meta']['result']] : '?').'<br>'
                . '</div>'
                . 'Host: '    . (isset($aData['meta']['host'])    ? '<span class="string">' . $aData['meta']['host']   .'</span>'    : '?').'<br>'
                . 'Website: ' . (isset($aData['meta']['website']) ? '<span class="string">' . $aData['meta']['website'].'</span>'  : '?').'<br>'
                . 'Execution time: '    . (isset($aData['meta']['time'])    ? '<span class="float">'  . $aData['meta']['time']  .'</span>'  : '?').'<br>'
                . 'Client: '    . (isset($aData['meta']['version'])    ? '<span class="float">'  . $aData['meta']['version']  .'</span>'  : '?').'<br>'

                .'<h2>Checks</h2>'
                ;
        if (isset($aData['checks'][0]) && count($aData['checks'])){
            foreach($aData['checks'] as $aCheck){
               $sOut.= ''
               . '<span class="result'.$aCheck['result'].'"> <strong>'.$aCheck['name'].'</strong></span> <br>'
               . '<div class="check">'
                    . '<div class="description">'
                        . $aCheck['description'].'<br>'
                        . $aCheck['value'].'<br>'
                    . '</div>'
                    . 'Execution time: ' . (isset($aCheck['time']) ? $aCheck['time'] : ' - ').'<br>'
                    . 'Group: '  . (isset($aCheck['group']) ? $aCheck['group'] : '-').'<br>'
                    . 'parent: ' . (isset($aCheck['parent']) ? $aCheck['parent']: '-').'<br>'
                    . 'Status: ' . $aMsg[$aCheck['result']].'<br>'
                   . '</div>'
                   ;
            }
        }
        $sOut.= '<h2>List of farbcodes</h2>';
        foreach ($aMsg as $i=>$sText){
            $sOut.= '<span class="result'.$i.'">'. $sText.'</span> ';
        }
        $sOut.='<h2>Raw result data</h2><pre>'.json_encode($aData, JSON_PRETTY_PRINT).'</pre>';
        $sOut = '<!DOCTYPE html><html><head>'
                . '<style>'
                . 'body{background:#fff; color:#444; font-family: verdana,arial; margin: 3em;}'
                . 'h1{color:#346;}'
                . 'h2{color:#569; margin-top: 1.5em;}'
                . '.check{border: 1px solid; padding: 0.4em; margin-bottom: 2em;}'
                . '.description{font-style: italic; padding: 0.4em 1em;}'
                . '.result0{background:#aca; border-left: 1em solid #080; padding: 0.5em; }'
                . '.result1{background:#ccc; border-left: 1em solid #aaa; padding: 0.5em; }'
                . '.result2{background:#fc9; border-left: 1em solid #860; padding: 0.5em; }'
                . '.result3{background:#f88; border-left: 1em solid #f00; padding: 0.5em; }'
                . '</style>'
                . '<title>' . __CLASS__ . '</title>'
                . '</head><body>'
                . '<h1>' . __CLASS__ . ' :: client status</h1>'
                . $sOut
                . '</body></html>';
        echo $sOut;
        return $sOut;
    }

}
