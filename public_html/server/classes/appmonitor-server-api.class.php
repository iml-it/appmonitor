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
 * @author Axel Hahn
 * @link TODO
 * @license GPL
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL 3.0
 * @package IML-Appmonitor
 */
class appmonitorserver_api extends appmonitorserver {


    // ----------------------------------------------------------------------
    // /v1/apps
    // ----------------------------------------------------------------------

    /**
     * get array with application data 
     * 
     * @param string  $sKey           filter key i.e. "meta"; default false (all)
     * @param string  $sFilterAppId   filter by app id; default false (all)
     * @return array
     */
    protected function _apiGetAppData($sKey=false, $sFilterAppId=false) {
        $this->_getClientData(true); // get data; true = use cache
        $aReturn=array();
        // echo 'ALL client data<pre>'.print_r($this->_data, 1).'</pre>';
        // echo '<br>$sKey = '.$sKey.'<br>';
        if ($sFilterAppId && !isset($this->_data[$sFilterAppId])){
            $aReturn=['error'=>'App id was not found', 'http' => '404'];
        } else {
            foreach($this->_data as $sAppId=>$aData){
                if($sAppId===$sFilterAppId){
                    $aReturn=$sKey 
                        ? $aData[$sKey] 
                        : $aData;
                } else {
                    $aReturn[$sAppId]=$sKey 
                        ? $aData[$sKey] 
                        : $aData;
                }
            }
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

                // add something to the result set based on outnode
                switch($outmode){

                    // short view of matching apps
                    case 'appid':
                        $aReturn[$sKey]=[
                            'website'=>$aData['result']['website'],
                            'url'=>$aData['result']['url'],
                        ];
                        break;
                        ;;
                    // return an existing key only
                    case 'checks':
                    case 'meta':
                        $aReturn[$sKey]=$aData[$outmode];
                        break;
                        ;;
                    
                    // all
                    default:
                        $aReturn[$sKey]=$aData;
                    ;;
                }
            }
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
        return $this->_getClientTags();
    }
    // ----------------------------------------------------------------------
    // TODO CHECK
    // ----------------------------------------------------------------------
    /**
     * get an array of all client metadata; optional filtered by given app id 
     * @param string  $sFilterAppId   filter by app id; default false (all)
     * @return type
     */
    public function __UNUSED__apiGetTroubleItems($sFilterAppId=false) {
        return $this->_apiGetAppData('meta',$sFilterAppId);
    }

}
