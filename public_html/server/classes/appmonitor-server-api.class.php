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
    // /v1/apps
    // ----------------------------------------------------------------------

    /**
     * get a flat array with all application ids and website + url
     * as subkeys
     * @return array
     */
    public function apiApps() {
        return ["id","tags"];
    }
    

    // ----------------------------------------------------------------------
    // /v1/apps/id
    // ----------------------------------------------------------------------

    /**
     * get a flat array with all application ids and website + url
     * as subkeys
     * @return array
     */
    public function apiGetAppIds() {
        $this->_getClientData(true);
        $aReturn=[];
        foreach($this->_data as $sKey=>$aData){
            $aReturn[$sKey]=[
                'website'=>$aData['result']['website'],
                'url'=>$aData['result']['url'],
            ];
        }
        return $aReturn;
    }
    
    /**
     * get an array of all client data; optional filtere by given app id 
     * @param string  $sFilterAppId   filter by app id; default false (all)
     * @return array
     */
    public function apiGetAppAllData($sFilterAppId=false) {
        return $this->_apiGetAppData(false, $sFilterAppId);
    }
    /**
     * get an array of all client checks; optional filtered by given app id 
     * @param string  $sFilterAppId   filter by app id; default false (all)
     * @return type
     */
    public function apiGetAppChecks($sFilterAppId=false) {
        return $this->_apiGetAppData('checks',$sFilterAppId);
    }
    /**
     * get an array of all client metadata; optional filtered by given app id 
     * @param string  $sFilterAppId   filter by app id; default false (all)
     * @return type
     */
    public function apiGetAppMeta($sFilterAppId=false) {
        return $this->_apiGetAppData('meta',$sFilterAppId);
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
    /**
     * get an array of all client metadata; optional filtered by given app id 
     * @param string  $sFilterAppId   filter by app id; default false (all)
     * @return type
     */
    public function __UNUSED__apiGetTroubleItems($sFilterAppId=false) {
        return $this->_apiGetAppData('meta',$sFilterAppId);
    }

}
