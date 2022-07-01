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
 * @version v1
 * @author Axel Hahn
 * @link https://github.com/iml-it/appmonitor
 * @license GPL
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL 3.0
 * @package IML-Appmonitor
 */
class appmonitorserver_api extends appmonitorserver {

    // ----------------------------------------------------------------------
    // pre actions
    // ----------------------------------------------------------------------

    /**
     * get the "api" section from configuration
     * @return array
     */
    public function getApiConfig(){
        $_aTmpCfg=$this->getConfigVars();
        return isset($_aTmpCfg['api']) ? $_aTmpCfg['api'] : [];
    }

    /**
     * get an array with users in the config to apply it on tinyapi init
     * Syntax: username is the key and password hash as value.
     * @return array
     */
    public function getApiUsers(){
        $aReturn=[];
        $_aTmpCfg=$this->getConfigVars();
        foreach($_aTmpCfg['users'] as $sLoopuser=>$aUserdata){
            $aReturn[$sLoopuser]=isset($aUserdata['password']) ? $aUserdata['password'] : false;
        }
        return $aReturn;
    }

    // ----------------------------------------------------------------------
    // /v1/apps/*
    // ----------------------------------------------------------------------

    /**
     * get an array of all applications that match a filter
     * @param  array  $aFilter   filter definitions using AND condition over all filters
     *                           appid   - string of appid
     *                           tags    - array of tags that must match (AND condition)
     *                           website - substring of website
     * @param  string  $outmode  kind of result data
     * @return type
     */
    public function apiGetFilteredApp($aFilter=[],$outmode='all') {
        $aReturn=[];
        $aTmp=[];

        // sort filter items or delete empty key
        if (isset($aFilter['tags']) && is_array($aFilter['tags']) && count($aFilter['tags'])){
            sort($aFilter['tags']);
        } else {
            unset($aFilter['tags']);
        }

        // remove empty items
        foreach(['appid', 'website'] as $sFilterKey){
            if (isset($aFilter[$sFilterKey]) && !$aFilter[$sFilterKey]){
                unset($aFilter[$sFilterKey]);
            }
        }

        // --- reduce apps by app internal data
        foreach($this->_data as $sKey=>$aData){
            $iAdd=0;
            $iRemove=0;

            // on empty filter: add
            if (!count($aFilter)) {
                $iAdd++;
            }

            if (isset($aFilter['appid'])) {
                if ($sKey==$aFilter['appid']){
                    $iAdd++;
                } else {
                    $iRemove++;
                }
            }

            // tags
            if (isset($aFilter['tags'])){
                if(isset($aData['meta']['tags']) ) {
                    foreach ($aFilter['tags'] as $sMustMatch){
                        if(in_array($sMustMatch, $aData['meta']['tags'])){
                            $iAdd++;
                        } else {
                            $iRemove++;
                        }

                    }
                } else {
                    $iRemove++;
                }
            }

            if(isset($aFilter['website'])){
                if(strstr($aData['meta']['website'], $aFilter['website'])){
                    $iAdd++;
                } else {
                    $iRemove++;
                }
            }

            if ($iAdd>0 && !$iRemove){

                // generate a key to sort apps
                // reverse status code to bring errors on top
                $iAppResult=RESULT_ERROR - (isset($aData['result']['result']) ? $aData['result']['result'] : 1);

                // ... and add appname
                $sAppName=$iAppResult.'__'.strtoupper( isset($aData['result']['website']) ? $aData['result']['website'] : 'zzz' ) . '__'.$sKey;

                switch($outmode){

                    // short view of matching apps
                    case 'appid':
                        $aTmp[$sAppName][$sKey]=[
                            'website'=>isset($aData['result']['website']) ? $aData['result']['website'] : false,
                            'url'=>isset($aData['result']['url']) ? $aData['result']['url'] : false,
                        ];
                        break;
                        ;;
                    // return an existing key only
                    case 'checks':
                    case 'meta':
                        $aTmp[$sAppName][$sKey]=isset($aData[$outmode]) ? $aData[$outmode] : false;
                        break;
                        ;;
                    
                    // all
                    default:
                        $aTmp[$sAppName][$sKey]=$aData;
                    ;;
                }

            }
        }
        ksort($aTmp);
        foreach($aTmp as $aApp){
            $sKey=array_keys($aApp)[0];
            $aReturn[$sKey]=$aApp[$sKey];
        }

        return $aReturn;
    }

    // ----------------------------------------------------------------------
    // /v1/tags
    // ----------------------------------------------------------------------

    /**
     * get a flat array with all application ids and website + url
     * as subkeys
     * @return array
     */
    public function apiGetTags() {
        return ['tags'=>$this->_getClientTags()];
    }

}
