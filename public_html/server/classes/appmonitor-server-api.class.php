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
        return $this->_aCfg['api'] ?? [];
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
        foreach ($this->_aCfg['users'] as $sLoopuser => $aUserdata) {
            if (
                array_search('api', $aUserdata['roles']) !== false
                || array_search('*', $aUserdata['roles']) !== false
            ) {
                $aReturn[$sLoopuser]['password'] = $aUserdata['password'] ?? false;
            }
            // $aReturn[$sLoopuser] = $aUserdata['password'] ?? false;
            if (isset($aUserdata['passwordhash']) && $aUserdata['passwordhash']) {
                $aReturn[$sLoopuser]['passwordhash'] = $aUserdata['passwordhash'];
            }
            if (isset($aUserdata['secret']) && $aUserdata['secret']) {
                $aReturn[$sLoopuser]['secret'] = $aUserdata['secret'];
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
                'statusmessage' => $this->getResultValue($aData["return"]),
                'apps' => [
                    'count' => $aData["results"]["total"],
                    0 => ['count' => $aData["results"][0], 'label' => $this->getResultValue(0)],
                    1 => ['count' => $aData["results"][1], 'label' => $this->getResultValue(1)],
                    2 => ['count' => $aData["results"][2], 'label' => $this->getResultValue(2)],
                    3 => ['count' => $aData["results"][3], 'label' => $this->getResultValue(3)],
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
                if ($sKey == $aFilter['appid']) {
                    $iAdd++;
                } else {
                    $iRemove++;
                }
            }

            // tags
            if (isset($aFilter['tags'])) {
                if (isset($aData['meta']['tags'])) {
                    foreach ($aFilter['tags'] as $sMustMatch) {
                        if (in_array($sMustMatch, $aData['meta']['tags'])) {
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
                if (strstr($aData['meta']['website'], $aFilter['website'])) {
                    $iAdd++;
                } else {
                    $iRemove++;
                }
            }

            if ($iAdd > 0 && !$iRemove) {

                // generate a key to sort apps
                // reverse status code to bring errors on top
                $iAppResult = RESULT_ERROR - ($aData['result']['result'] ?? 1);

                // ... and add appname
                $sAppName = $iAppResult . '__' . strtoupper($aData['result']['website'] ?? 'zzz') . '__' . $sKey;

                switch ($outmode) {

                    // short view of matching apps
                    case 'appid':
                        $aTmp[$sAppName][$sKey] = [
                            'website' => $aData['result']['website'] ?? false,
                            'url' => $aData['result']['url'] ?? false,
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
                        ;

                    // all
                    default:
                        $aTmp[$sAppName][$sKey] = $aData;
                        ;
                        ;
                }
            }
        }
        ksort($aTmp);
        foreach ($aTmp as $aApp) {
            $sKey = array_keys($aApp)[0];
            $aReturn[$sKey] = $aApp[$sKey];
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
