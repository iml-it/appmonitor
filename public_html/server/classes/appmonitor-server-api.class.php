<?php

require_once 'appmonitor-server.class.php';

/**
 * ____________________________________________________________________________
 * 
 *  _____ _____ __                   _____         _ _           
 * |     |     |  |      ___ ___ ___|     |___ ___|_| |_ ___ ___ 
 * |-   -| | | |  |__   | .'| . | . | | | | . |   | |  _| . |  _|
 * |_____|_|_|_|_____|  |__,|  _|  _|_|_|_|___|_|_|_|_| |___|_|  
 *                          |_| |_|                              
 *                                                                                                                             
 *                       ___ ___ ___ _ _ ___ ___                                      
 *                      |_ -| -_|  _| | | -_|  _|                                     
 *                      |___|___|_|  \_/|___|_|                                       
 *                                                               
 * ____________________________________________________________________________
 * 
 * APPMONITOR SERVER<br>
 * <br>
 * THERE IS NO WARRANTY FOR THE PROGRAM, TO THE EXTENT PERMITTED BY APPLICABLE <br>
 * LAW. EXCEPT WHEN OTHERWISE STATED IN WRITING THE COPYRIGHT HOLDERS AND/OR <br>
 * OTHER PARTIES PROVIDE THE PROGRAM "AS IS" WITHOUT WARRANTY OF ANY KIND, <br>
 * EITHER EXPRESSED OR IMPLIED, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED <br>
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE. THE <br>
 * ENTIRE RISK AS TO THE QUALITY AND PERFORMANCE OF THE PROGRAM IS WITH YOU. <br>
 * SHOULD THE PROGRAM PROVE DEFECTIVE, YOU ASSUME THE COST OF ALL NECESSARY <br>
 * SERVICING, REPAIR OR CORRECTION.<br>
 * <br>
 * --------------------------------------------------------------------------------<br>
 * @version v0.137
 * @author Axel Hahn
 * @link https://github.com/iml-it/appmonitor
 * @license GPL
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL 3.0
 * @package IML-Appmonitor
 * --------------------------------------------------------------------------------<br>
 * 2024-07-17  0.137  axel.hahn@unibe.ch  php 8 only: use typed variables
 * 2024-11-14  0.141  axel.hahn@unibe.ch  API access with basic auth and hmac hash key
 * 2025-03-11  0.154  axel.hahn@unibe.ch  add routes wth public keyword in API
 * 2026-02-18  0.178  axel.hahn@unibe.ch  add hard state and last application reposnses
 */
class appmonitorserver_api extends appmonitorserver
{

    // ----------------------------------------------------------------------
    // pre actions
    // ----------------------------------------------------------------------

    /**
     * Get the "api" section from configuration
     * @return array
     */
    public function getApiConfig(): array
    {
        return (array) ($this->_aCfg['api'] ?? []);
    }

    /**
     * Get an array with users in the config to apply it on tinyapi init
     * Syntax: username and keys 'password' and/ or 'secret'
     * Array
     * (
     *     [*] => Array
     *         (
     *             [password] =>
     *         )
     * 
     *     [api] => Array
     *         (
     *             [password] => $2y$10$5E4ZWyul.VdZjpP1.Ff6Le0z0kxu3ix7jnbYhv0Zg5vhvhjdJTOm6
     *         )
     * 
     *     [api-test] => Array
     *         (
     *             [password] =>
     *             [secret] => tryme
     *         )
     * 
     *     [superuser] => Array
     *         (
     *             [password] =>
     *         )
     * 
     * )
     * 
     * @return array
     */
    public function getApiUsers(): array
    {
        $aReturn = [];
        foreach ((array) ($this->_aCfg['users']??[]) as $sLoopuser => $aUserdata) {
            if (
                array_search('api', (array)($aUserdata['roles']??[])) !== false
                || array_search('*', (array) ($aUserdata['roles']??[])) !== false
            ) {
                $aReturn[$sLoopuser]['password'] = $aUserdata['password'] ?? '';
            }
            // $aReturn[$sLoopuser] = $aUserdata['password'] ?? false;
            if ($aUserdata['passwordhash']??false) {
                $aReturn[$sLoopuser]['passwordhash'] = $aUserdata['passwordhash']??'';
            }
            if ($aUserdata['secret']??false) {
                $aReturn[$sLoopuser]['secret'] = $aUserdata['secret']??'';
            }
        }
        // print_r($aReturn);
        return $aReturn;
    }

    // ----------------------------------------------------------------------
    // /health
    // ----------------------------------------------------------------------

    /**
     * Generate JSON to show a health status
     * @return array
     */
    public function apiGetHeatlth(): array
    {
        $aData = $this->getMonitoringData();
        $aReturn = [
            'health' => [
                'status' => isset($aData['return']) ? 'OK' : 'error',
                'statusmessage' => isset($aData['return']) ? 'Appmonitor is up and running.' : 'No monitoring data available',
            ],
            'monitoring' => [
                'status' => '-1',
                'statusmessage' => 'no monitoring data available',
            ],
            // 'raw'=>$aData,
        ];
        if (isset($aData['return'])) {
            $aReturn['monitoring'] = [
                'status' => $aData['return'],
                'statusmessage' => $this->getResultValue((int) $aData["return"]),
                'apps' => [
                    'count' => $aData["results"]["total"]??0,
                    0 => ['count' => (int) ($aData["results"][0]??0), 'label' => $this->getResultValue(0)],
                    1 => ['count' => (int) ($aData["results"][1]??0), 'label' => $this->getResultValue(1)],
                    2 => ['count' => (int) ($aData["results"][2]??0), 'label' => $this->getResultValue(2)],
                    3 => ['count' => (int) ($aData["results"][3]??0), 'label' => $this->getResultValue(3)],
                ]
            ];
        }
        return $aReturn;
    }
    // ----------------------------------------------------------------------
    // /v1/apps/*
    // ----------------------------------------------------------------------

    /**
     * Get an array of all applications that match a filter
     * 
     * @param  array  $aFilter   filter definitions using AND condition over all filters
     *                           appid   - string of appid
     *                           tags    - array of tags that must match (AND condition)
     *                           website - substring of website
     * @param  string  $outmode  kind of result data; one of appid|checks|meta|all
     * @return array
     */
    public function apiGetFilteredApp(array $aFilter = [], string $outmode = 'all'): array
    {
        $aReturn = [];
        $aTmp = [];

        // sort filter items or delete empty key
        if (isset($aFilter['tags']) && is_array($aFilter['tags']) && count($aFilter['tags'])) {
            sort($aFilter['tags']);
        } else {
            unset($aFilter['tags']);
        }

        // remove empty items
        foreach (['appid', 'website'] as $sFilterKey) {
            if (isset($aFilter[$sFilterKey]) && !$aFilter[$sFilterKey]) {
                unset($aFilter[$sFilterKey]);
            }
        }

        // --- reduce apps by app internal data
        foreach ($this->_data as $sKey => $aData) {
            $iAdd = 0;
            $iRemove = 0;

            // on empty filter: add
            if (!count($aFilter)) {
                $iAdd++;
            }

            if (isset($aFilter['appid'])) {
                if ($sKey === $aFilter['appid']) {
                    $iAdd++;
                } else {
                    $iRemove++;
                }
            }

            // tags
            if (isset($aFilter['tags'])) {
                if (isset($aData['meta']['tags'])) {
                    foreach ((array) $aFilter['tags'] as $sMustMatch) {
                        if (in_array($sMustMatch, (array) $aData['meta']['tags'])) {
                            $iAdd++;
                        } else {
                            $iRemove++;
                        }
                    }
                } else {
                    $iRemove++;
                }
            }

            if (isset($aFilter['website'])) {
                if (strstr((string) ($aData['meta']['website']??''), (string) $aFilter['website'])) {
                    $iAdd++;
                } else {
                    $iRemove++;
                }
            }

            if ($iAdd > 0 && !$iRemove) {

                // generate a key to sort apps
                // reverse status code to bring errors on top
                $iAppResult = RESULT_ERROR - (int)($aData['result']['result'] ?? 1);

                // ... and add appname
                $sAppName = $iAppResult . '__' . strtoupper((string) ($aData['result']['website'] ?? 'zzz')) . '__' . $sKey;

                switch ($outmode) {

                    // short view of matching apps
                    case 'appid':
                        $aTmp[$sAppName][$sKey] = [
                            'website' => $aData['result']['website'] ?? false,
                            // 'url' => $aData['result']['url'] ?? false,
                        ];
                        break;
                        ;
                        ;
                    // return an existing key only
                    case 'checks':
                    case 'meta':
                        $aTmp[$sAppName][$sKey] = $aData[$outmode] ?? false;
                        break;
                        ;

                    case 'public':    
                    case 'all':
                        if($outmode=="public"){
                            $aTmp[$sAppName][$sKey]['meta']=[
                                'host' => $aData['meta']['host'] ?? false,
                                'website' => $aData['meta']['website'] ?? false,
                                'result' => $aData['meta']['result'] ?? false,  // soft state
                                'ttl' => $aData['meta']['ttl'] ?? false,
                            ];
                        } else {
                            $aTmp[$sAppName][$sKey] = $aData;
                        }
                        // get infos from database

                        // $this->_oWebapps->readByFields(['appid' => $sKey]);
                        // $sTsLast = $this->_oWebapps->get("timeupdated") ?? $this->_oWebapps->get("timecreated" );
                        // $aTmp[$sAppName][$sKey]['timestamp'] = (int)date("U", strtotime($sTsLast));

                        $this->oNotification->setApp( (string) $sKey);
                        $aLastNotification=$this->oNotification->getAppLastNotification(); 
                        $aTmp[$sAppName][$sKey]['since'] = $aLastNotification['timestamp']??0; 

                        $rrd = new simpleRrd();

                        // ticket #8697
                        $aTmp[$sAppName][$sKey]['state'] = [
                            'result-soft' => $aData['meta']['result'] ?? false,
                            'result-hard' => $aLastNotification['status'],
                            'result-hard-since' => $aLastNotification['timestamp'],
                            // 'resultcounter' => $aData['result']['resultcounter']??false,
                            'lastresponses' => $rrd->getAllCounters(['appid'=>$sKey, 'countername'=>'_responsetime', 'limit'=>5], true)[$sKey]['_responsetime'],
                        ]
                        ;
                    default:
                        ;
                }
            }
        }
        ksort($aTmp);
        foreach ($aTmp as $aApp) {
            $sKey = array_keys((array) $aApp)[0];
            $aReturn[$sKey] = $aApp[(string) $sKey];
        }

        return $aReturn;
    }

    // ----------------------------------------------------------------------
    // /v1/tags
    // ----------------------------------------------------------------------

    /**
     * Get a flat array with all application ids and website + url
     * as subkeys
     * @return array
     */
    public function apiGetTags(): array
    {
        return ['tags' => $this->_getAllClientTags()];
    }
}
