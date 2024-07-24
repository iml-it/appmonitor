<?php
if (!class_exists('appmonitorcheck')) {
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
 * 2023-07-06  0.128  axel.hahn@unibe.ch      update httpcontent check<br>
 * 2024-07-19  0.137  axel.hahn@unibe.ch      php 8 only: use typed variables
 * --------------------------------------------------------------------------------<br>
 * @version 0.137
 * @author Axel Hahn
 * @link TODO
 * @license GPL
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL 3.0
 * @package IML-Appmonitor
 */
class appmonitor
{

    /**
     * Name and Version number
     * @var string
     */
    protected string $_sVersion = 'php-client-v0.137';

    /**
     * config: default ttl for server before requesting the client check again
     * value is in seconds
     * @var int
     */
    protected int $_iDefaultTtl = 300;

    /**
     * internal counter: greatest return value of all checks
     * @var integer 
     */
    protected int $_iMaxResult = -1;

    /**
     * responded metadata of a website
     * @see _createDefaultMetadata()
     * @var array
     */
    protected array $_aMeta = [];

    /**
     * Response array of all checks
     * @see addCheck()
     * @var array
     */
    protected array $_aChecks = [];

    /**
     * for time measurements: start time
     * @var float 
     */
    protected float $_iStart = 0;

    /**
     * constructor: init data
     */
    public function __construct()
    {
        $this->_createDefaultMetadata();
    }

    // ----------------------------------------------------------------------
    // protected function
    // ----------------------------------------------------------------------

    /**
     * Create basic array values for metadata
     * @return boolean
     */
    protected function _createDefaultMetadata(): bool
    {
        $this->_iStart = microtime(true);
        $this->_aMeta = [
            "host" => false,
            "website" => false,
            "ttl" => false,
            "result" => false,
            "time" => false,
            "version" => $this->_sVersion,
        ];

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
     * Set the physical hostname for metadata; if no host is given then
     * the php_uname("n") will be used to set one.
     * 
     * @param string $s  hostname
     * @return bool
     */
    public function setHost(string $s = ''): bool
    {
        if (!$s) {
            $s = php_uname("n");
        }
        if (!$s) {
            return false;
        }
        $this->_aMeta["host"] = $s;
        return true;
    }

    /**
     * Set a name for this website or application and its environment 
     * (dev, test, prod); 
     * 
     * If you have several application in subdirectories, i.e. /blog,  /shop...
     * then you should the path or any description to identify them too
     * 
     * if no argument is given the name of HTTP_HOST will be used
     * 
     * @param string $sWebsite  Name of the website or web application
     * @return boolean
     */
    public function setWebsite($sWebsite = ''): bool
    {
        if (!$sWebsite && isset($_SERVER["HTTP_HOST"])) {
            $sWebsite = $_SERVER["HTTP_HOST"];
        }
        if (!$sWebsite) {
            return false;
        }
        $this->_aMeta["website"] = $sWebsite;
        return true;
    }

    /**
     * set a ttl value in seconds to define how long a server should not
     * ask again for a new status of this instance
     * 
     * @param int $iTTl TTL value in sec
     * @return boolean
     */
    public function setTTL($iTTl = 0)
    {
        if ($iTTl == 0) {
            $iTTl = $this->_iDefaultTtl;
        }
        return $this->_aMeta["ttl"] = $iTTl;
    }

    /**
     * Set final result in meta data; if no value was given then it
     * sets the biggest value of any check.
     * 
     * @param integer  $iResult  set resultcode; one of RESULT_OK|RESULT_WARNING|RESULT_ERROR|RESULT_UNKNOWN
     * @return boolean
     */
    public function setResult(int $iResult = -1): bool
    {
        if ($iResult === -1) {
            $iResult = $this->_iMaxResult; // see addCheck()
        }
        $this->_aMeta["result"] = $iResult;
        return true;
    }

    /**
     * Add a check array
     * @param array  $aJob  array with check data
     * @return boolean
     */
    public function addCheck($aJob = []): bool
    {

        require_once 'appmonitor-checks.class.php';
        $oCheck = new appmonitorcheck();
        $aCheck = $oCheck->makecheck($aJob);

        // limit result code
        $iMyResult = isset($aJob['worstresult'])
            ? min($aCheck["result"], $aJob['worstresult'])
            : $aCheck["result"]
        ;

        if (!$this->_iMaxResult || $iMyResult > $this->_iMaxResult) {
            $this->_iMaxResult = $iMyResult;
        }
        $this->_aChecks[] = $aCheck;
        return true;
    }

    /**
     * Add an item to notifications meta data
     * @see addEmail()
     * @see addSlack()
     * 
     * @param string $sType   type ... one of email|slack
     * @param string $sValue    value
     * @param string $sKey      optional key (for key->value instead of list of values)
     * @return boolean
     */
    protected function _addNotification(string $sType, string $sValue, string $sKey = ''): bool
    {
        $sTypeCleaned = preg_replace('/[^a-z]/', '', strtolower($sType));
        if (!isset($this->_aMeta['notifications'])) {
            $this->_aMeta['notifications'] = [];
        }
        if (!isset($this->_aMeta['notifications'][$sTypeCleaned])) {
            $this->_aMeta['notifications'][$sTypeCleaned] = [];
        }
        if ($sKey) {
            $this->_aMeta['notifications'][$sTypeCleaned][$sKey] = $sValue;
        } else {
            $this->_aMeta['notifications'][$sTypeCleaned][] = $sValue;
        }
        return true;
    }

    /**
     * Add an email to notifications list
     * 
     * @param string $sEmailAddress  email address to add
     * @return boolean
     */
    public function addEmail(string $sEmailAddress)
    {
        return $this->_addNotification('email', $sEmailAddress);
    }

    /**
     * Add slack channel for notification
     * @param string  $sLabel
     * @param string  $sSlackWebhookUrl
     * @return boolean
     */
    public function addSlackWebhook(string $sLabel, string $sSlackWebhookUrl): bool
    {
        return $this->_addNotification('slack', $sSlackWebhookUrl, $sLabel);
    }

    /**
     * Add a tag for grouping in the server gui.
     * Spaces will be replaced with underscore
     * 
     * @param string  $sTag  tag to add
     * @return boolean
     */
    public function addTag(string $sTag): bool
    {
        if (!isset($this->_aMeta['tags'])) {
            $this->_aMeta['tags'] = [];
        }
        $this->_aMeta['tags'][] = str_replace(' ', '_', $sTag);
        return true;
    }

    /**
     * Check referers IP address if it matches any entry in the list
     * requires http request; CLI is always allowed
     * On deny this method exits with 403 response
     * 
     * @param array $aAllowedIps  array of allowed ip addresses / ranges
     *                            the ip must match from the beginning, i.e.
     *                            "127.0." will allow requests from 127.0.X.Y
     * @return boolean
     */
    public function checkIp(array $aAllowedIps = []): bool
    {
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
     * @param string  $sVarname  name of GET variable
     * @param string  $sToken    value
     * @return boolean
     */
    public function checkToken(string $sVarname, string $sToken): bool
    {
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
    public function listChecks(): array
    {
        require_once 'appmonitor-checks.class.php';
        $oCheck = new appmonitorcheck();
        return $oCheck->listChecks();
    }

    // ----------------------------------------------------------------------
    // checks
    // ----------------------------------------------------------------------

    /**
     * verify array values and in case of an error abort and show all found errors
     * @return boolean
     */
    protected function _checkData(): bool
    {
        $aErrors = [];

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
     * Stop processing the client checks and abort with an error
     * @param string $sMessage  text to show after a 503 headline
     * @return void
     */
    public function abort(string $sMessage): void
    {
        header('HTTP/1.0 503 Service Unavailable');
        die('<h1>503 Service Unavailable</h1>' . $sMessage);
    }

    /**
     * Get full array for response with metadata and checks
     * @return array
     */
    public function getResults(): array
    {
        return [
            "meta" => $this->_aMeta,
            "checks" => $this->_aChecks,
        ];
    }

    /**
     * Send http response with header and appmonitor JSON data
     * @return string
     */
    public function render(): string
    {
        $this->_checkData();
        $this->_aMeta['time'] = number_format((microtime(true) - $this->_iStart) * 1000, 3) . 'ms';
        $sOut=json_encode($this->getResults());

        header('Content-type: application/json');
        header('Cache-Control: cache');
        header('max-age: ' . $this->_aMeta["ttl"]);
        echo $sOut;
        return $sOut;
    }

    /**
     * Output appmonitor client status as single html page
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
     * @return string
     */
    public function renderHtmloutput(string $sJson): string
    {

        header('Content-type: text/html');
        header('Cache-Control: cache');
        header('max-age: ' . $this->_aMeta["ttl"]);
        $aMsg = [
            0 => "OK",
            1 => "UNKNOWN",
            2 => "WARNING",
            3 => "ERROR"
        ];

        // $sOut = print_r($sJson, 1);
        $aData = json_decode($sJson, 1);

        // ----- Ausgabe human readable
        $sOut = '';
        $sOut .= ''
            . '<h2>Metadata</h2>'
            . '<div class="meta' . (isset($aData['meta']['result']) ? ' result' . $aData['meta']['result'] : '') . '">'
            . 'Status: ' . (isset($aData['meta']['result']) ? $aMsg[$aData['meta']['result']] : '?') . '<br>'
            . '</div>'
            . 'Host: ' . (isset($aData['meta']['host']) ? '<span class="string">' . $aData['meta']['host'] . '</span>' : '?') . '<br>'
            . 'Website: ' . (isset($aData['meta']['website']) ? '<span class="string">' . $aData['meta']['website'] . '</span>' : '?') . '<br>'
            . 'Execution time: ' . (isset($aData['meta']['time']) ? '<span class="float">' . $aData['meta']['time'] . '</span>' : '?') . '<br>'
            . 'Client: ' . (isset($aData['meta']['version']) ? '<span class="string">' . $aData['meta']['version'] . '</span>' : '?') . '<br>'

            . '<h2>Checks</h2>'
        ;
        if (isset($aData['checks'][0]) && count($aData['checks'])) {
            foreach ($aData['checks'] as $aCheck) {
                $sOut .= ''
                    . '<span class="result' . $aCheck['result'] . '"> <strong>' . $aCheck['name'] . '</strong></span> <br>'
                    . '<div class="check">'
                    . '<div class="description">'
                    . $aCheck['description'] . '<br>'
                    . $aCheck['value'] . '<br>'
                    . '</div>'
                    . 'Execution time: <span class="float">' . (isset($aCheck['time']) ? $aCheck['time'] : ' - ') . '</span><br>'
                    . 'Group: <span class="string">' . (isset($aCheck['group']) ? $aCheck['group'] : '-') . '</span><br>'
                    . 'parent: <span class="string">' . (isset($aCheck['parent']) ? $aCheck['parent'] : '-') . '</span><br>'
                    . 'Status: ' . $aMsg[$aCheck['result']] . '<br>'
                    . '</div>'
                ;
            }
        }
        $sOut .= '<h2>List of farbcodes</h2>';
        foreach ($aMsg as $i => $sText) {
            $sOut .= '<span class="result' . $i . '">' . $sText . '</span> ';
        }

        $sRaw=json_encode($aData, JSON_PRETTY_PRINT);
        $sRaw = preg_replace('/:\ \"(.*)\"/U', ': "<span class="string">$1</span>"', $sRaw);
        $sRaw = preg_replace('/:\ ([0-9]*)/', ': <span class="int">$1</span>', $sRaw);
        $sRaw = preg_replace('/\"(.*)\":/U', '"<span class="key">$1</span>":', $sRaw);

        $sOut .= '<h2>Raw result data</h2><pre id="raw">' . $sRaw . '</pre>';


        $sOut = '<!DOCTYPE html><html><head>'
            . '<style>'
            . 'body{background:#eee; color:#444; font-family: verdana,arial; margin: 0; }'
            . 'body>div#content{background: #fff; border-radius: 2em; border: 4px solid #abc; box-shadow: 0.5em 0.5em 2em #aaa; margin: 2em 10%; padding: 2em;}'
            . 'h1{color:#346; margin: 0;}'
            . 'h2{color:#569; margin-top: 2em;}'
            . 'pre{background:#f4f4f8; padding: 1em; overflow-x:auto; }'
            . '#raw .key{color:#808;}'
            . '#raw .int{color:#3a3; font-weight: bold;}'
            . '#raw .string{color:#66e;}'
            . '.check{border: 1px solid #ccc; padding: 0.4em; margin-bottom: 2em;}'
            . '.description{font-style: italic; padding: 0.4em 1em;}'
            . '.float{color:#080;}'
            . '.meta{margin-bottom: 1em;}'
            . '.result0{background:#aca; border-left: 1em solid #080; padding: 0.5em; }'
            . '.result1{background:#ccc; border-left: 1em solid #aaa; padding: 0.5em; }'
            . '.result2{background:#fc9; border-left: 1em solid #860; padding: 0.5em; }'
            . '.result3{background:#f88; border-left: 1em solid #f00; padding: 0.5em; }'
            . '.string{color:#338;}'
            . '</style>'
            . '<title>' . __CLASS__ . '</title>'
            . '</head><body>'
            . '<div id="content">'
            . '<h1>' . __CLASS__ . ' :: client status</h1>'
            . $sOut
            . '</div>'
            . '</body></html>';
        return $sOut;
    }

}
