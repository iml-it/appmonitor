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
 * 2024-07-23  <axel.hahn@unibe.ch>      php 8 only: use typed variables
 * 
 */
class checkApacheProcesses extends appmonitorcheck
{

    /**
     * url of server status
     * @var string
     */
    protected string $_sServerStatusUrl = 'http://localhost/server-status';

    /**
     * Warning level in percent
     * @var float
     */
    protected float $_iWarn = 50;

    /**
     * Critical level in percent
     * @var float
     */
    protected float $_iError = 75;

    /**
     * Self documentation (as idea)
     * @return array
     */
    public function explain(): array
    {
        return [
            'name' => 'Plugin ApacheProcesses',
            'descriptionm' => 'Check count running Apache processes',
            'parameters' => [
                'url' => [
                    'type' => 'string',
                    'required' => false,
                    'decsription' => 'Override https server-status page; default is http://localhost/server-status; Use it if the protocol to localhost is not http, but https or if it requires an authentication',
                    'default' => $this->_sServerStatusUrl,
                    'example' => '',
                ],
                'warning' => [
                    'type' => 'float',
                    'required' => false,
                    'decsription' => 'Limit to switch to warning (in percent)',
                    'default' => $this->_iWarn,
                    'example' => 30,
                ],
                'error' => [
                    'type' => 'float',
                    'required' => false,
                    'decsription' => 'Limit to switch to critical (in percent)',
                    'default' => $this->_iError,
                    'example' => 50,
                ],
            ],
        ];
    }

    /**
     * Fetch http server status and return slots, active and waiting processes
     * as array i.e. [total] => 256 \n    [free] => 247\n    [waiting] => 7\n    [active] => 2
     * It returns false if the url is not reachable
     * It returns an empty array if the server status could not be parsed from http response
     * @return array
     */
    protected function _getApacheProcesses(): bool|array
    {
        $sBody = file_get_contents($this->_sServerStatusUrl);
        if (!$sBody) {
            return false;
        }
        $sRegexScoreboard = '/<pre>(.*)\<\/pre\>/U';
        $aScore = [];
        $sStatusNobr = str_replace("\n", "", $sBody);

        if (preg_match_all($sRegexScoreboard, $sStatusNobr, $aTmpTable)) {
            $sScoreString = $aTmpTable[1][0];
            // $aScore['scoreboard']=$sScoreString;
            $aScore['total'] = strlen($sScoreString);
            $aScore['free'] = substr_count($sScoreString, '.');
            $aScore['waiting'] = substr_count($sScoreString, '_');
            $aScore['active'] = $aScore['total'] - $aScore['free'] - $aScore['waiting'];
        }
        return $aScore;
    }

    /**
     * Get default group of this check
     * @return string
     */
    public function getGroup(): string
    {
        return 'monitor';
    }

    /**
     * Implemented method: run the check
     * @param array $aParams  parameters
     * @return array
     */
    public function run(array $aParams)
    {

        // --- (1) verify if array key(s) exist:
        // $this->_checkArrayKeys($aParams, "...");
        if (isset($aParams['url']) && $aParams['url']) {
            $this->_sServerStatusUrl = $aParams['url'];
        }
        if (isset($aParams['warning']) && (int) $aParams['warning']) {
            $this->_iWarn = (int) $aParams['warning'];
        }
        if (isset($aParams['error']) && (int) $aParams['error']) {
            $this->_iError = (int) $aParams['error'];
        }

        // --- (2) do something magic
        $aProcesses = $this->_getApacheProcesses();
        $iActive = $aProcesses ? $aProcesses['active'] : false;

        // set result code
        if ($iActive === false) {
            $iResult = RESULT_UNKNOWN;
        } else {
            $sComment = '';
            $iTotal = $aProcesses['total'];
            $iResult = RESULT_OK;
            if (($iActive / $iTotal * 100) > $this->_iWarn) {
                $iResult = RESULT_WARNING;
                $sComment = "more than warning level $this->_iWarn %";
            } else {
                $sComment = "less than warning level $this->_iWarn %";
            }
            if (($iActive / $iTotal * 100) > $this->_iError) {
                $iResult = RESULT_ERROR;
                $sComment = "more than error level $this->_iError %";
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
            ($iActive === false ? 'Apache httpd server status is not available' : 'apache processes: ' . print_r($aProcesses, 1)) . ' ' . $sComment,
            ($iActive === false
                ? []
                : [
                    'type' => 'counter',
                    'count' => $iActive,
                    'visual' => 'line',
                ]
            )
        ];
    }
}
