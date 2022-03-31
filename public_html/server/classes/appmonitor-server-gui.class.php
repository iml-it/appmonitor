<?php

require_once 'appmonitor-server.class.php';
require_once 'render-adminlte.class.php';

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
 * @version 0.108
 * @author Axel Hahn
 * @link TODO
 * @license GPL
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL 3.0
 * @package IML-Appmonitor
 */
class appmonitorserver_gui extends appmonitorserver {

    var $_sProjectUrl = "https://github.com/iml-it/appmonitor";
    var $_sDocUrl = "https://github.com/iml-it/appmonitor/blob/master/readme.md";
    var $_sTitle = "Appmonitor Server";
    var $_sVersion = "0.109";

    /**
     * html code for icons in the web gui
     * https://fontawesome.com/icons?d=gallery&m=free
     * 
     * @var array
     */
    protected $_aIco = array(
        'title' => '<i class="fas fa-th"></i>',
        'welcome' => '<i class="far fa-flag" style="font-size: 500%;float: left; margin: 0 1em 10em 0;"></i>',
        'reload' => '<i class="fas fa-sync"></i>',
        'allwebapps' => '<i class="fas fa-globe"></i>',
        'webapp' => '<i class="fas fa-box-open"></i>',
        'host' => '<i class="far fa-hdd"></i>',
        'url' => '<i class="fas fa-globe"></i>',
        'check' => '<i class="fas fa-check"></i>',
        'checks' => '<i class="fas fa-list"></i>',
        'problems' => '<i class="fas fa-exclamation-triangle"></i>',
        'notifications' => '<i class="far fa-bell"></i>',
        'setup' => '<i class="fas fa-wrench"></i>',
        'about' => '<i class="fas fa-info-circle"></i>',
        'notify-email' => '<i class="far fa-envelope"></i>',
        'notify-slack' => '<i class="fab fa-slack-hash"></i>',
        'sleepmode-on' => '<i class="fas fa-bed"></i>',
        'sleepmode-off' => '<i class="fas fa-bullhorn"></i>',
        'filter' => '<i class="fas fa-filter"></i>',
        'age' => '<i class="far fa-clock"></i>',
        'time' => '<i class="far fa-clock"></i>',
        'tag' => '<i class="fas fa-tag"></i>',
        'debug' => '<i class="fas fa-bug"></i>',
        'ok' => '<i class="fas fa-check"></i>',
        'info' => '<i class="fas fa-info"></i>',
        'warning' => '<i class="fas fa-exclamation-triangle"></i>',
        'unknown' => '<i class="fas fa-exclamation-triangle"></i>',
        'error' => '<i class="fas fa-bolt"></i>',
        'add' => '<i class="fas fa-plus"></i>',
        'del' => '<i class="fas fa-trash"></i>',
        'plus' => '<i class="fas fa-plus"></i>',
        'close' => '<i class="fas fa-times"></i>',
        'save' => '<i class="fas fa-paper-plane"></i>',
    );

    // ----------------------------------------------------------------------
    // protected functions
    // ----------------------------------------------------------------------

    /**
     * get all messages as html output
     * @return string
     */
    protected function _renderLogs() {
        $sOut = '';
        $oA=new renderadminlte();
        if (count($this->_aMessages)) {
            foreach ($this->_aMessages as $aLogentry) {
                $sOut .= $oA->getAlert(array(
                        'type'=>$this->_getAdminLteClassByLoglevel($aLogentry["level"]),
                        'dismissible'=>false,
                        'title'=>$this->_aIco[$aLogentry["level"]],
                        'text'=>$aLogentry["message"]
                        ))
                    ;
            }
        }
        if ($sOut) {
            $sOut = '<div id="divmodal">'
                        . '<div class="divdialog">'
                            . $sOut
                            . '<div style="text-align: center">'
                                . '<a href="#" class="btn btn-primary" onclick="$(\'#divmodal\').hide();return false;">' . $this->_aIco["close"] . ' ' . $this->_tr('btn-close') . '</a><br><br>'
                            . '</div>'
                        . '</div>'
                    . '</div>';
        }
        return $sOut;
    }

    // ----------------------------------------------------------------------
    // setter
    // ----------------------------------------------------------------------

    // ----------------------------------------------------------------------
    // output
    // ----------------------------------------------------------------------


    /**
     * get array with 
     * @param bboolean  $bReverse  optional: reverse; default is false (start with RESULT_OK)
     * @return type
     */
    protected function _getResultDefs($bReverse=false) {
        return $bReverse 
            ? array(
                RESULT_ERROR,
                RESULT_WARNING,
                RESULT_UNKNOWN,
                RESULT_OK,
            ): array(
                RESULT_OK,
                RESULT_UNKNOWN,
                RESULT_WARNING,
                RESULT_ERROR,
            );
    }

    /**
     * helper: generate html code for table header
     * @param array  $aHeaditems  items in header colums
     * @return string
     */
    protected function _generateTableHead($aHeaditems) {
        $sReturn = '';
        foreach ($aHeaditems as $sKey) {
            $sReturn .= '<th>' . $sKey . '</th>';
        }
        return '<thead><tr>' . $sReturn . '</tr></thead>';
    }

    
    protected function _getAdminLteClassByLoglevel($iResult, $sDefault='') {
        $aAdminLteColorMapping=array(
            'error'=>'danger',
            'warning'=>'warning',
            'info'=>'info',
            'ok'=>'success',
        );
        return isset($aAdminLteColorMapping[$iResult]) 
            ? $aAdminLteColorMapping[$iResult]
            : $sDefault
            ;
    }
    protected function _getAdminLteClassByResult($iResult, $sDefault='') {
        $aAdminLteColorMapping=array(
            RESULT_ERROR=>'danger',
            RESULT_WARNING=>'warning',
            RESULT_UNKNOWN=>'gray',
            RESULT_OK=>'success',
        );
        return isset($aAdminLteColorMapping[$iResult]) 
            ? $aAdminLteColorMapping[$iResult]
            : $sDefault
            ;
    }
    protected function _getAdminLteColorByResult($iResult, $sDefault='') {
        $aAdminLteColorMapping=array(
            RESULT_ERROR=>'red',
            RESULT_WARNING=>'orange',
            RESULT_UNKNOWN=>'gray',
            RESULT_OK=>'green',
        );
        return isset($aAdminLteColorMapping[$iResult]) 
            ? $aAdminLteColorMapping[$iResult]
            : $sDefault
            ;
    }
    protected function _getIconByResult($iResult) {
        $aMapping=array(
            RESULT_ERROR=>'error',
            RESULT_WARNING=>'warning',
            RESULT_UNKNOWN=>'unknown',
            RESULT_OK=>'ok',
        );
        return isset($aMapping[$iResult])
            ? $this->_aIco[$aMapping[$iResult]]
            : false
            ;
    }
    
    /**
     * get the css class name from $this->_aIco['NAME'] 
     * 
     * @param string  $sIconCode  html code as $this->_aIco['NAME'] 
     * @return string
     */
    protected function _getIconClass($sIconCode=false, $iResult=false) {
        if(!$sIconCode){
            $sIconCode=$this->_getIconByResult($iResult);
        }
        return preg_replace('/^.*\"(.*)\".*/', '$1', $sIconCode);
    }

    /**
     * get html code for a tile widget
     * 
     * @param array  $aOptions  options array with these keys
     *                          - bgcolor string   for adminlte; color name
     *                          - color   string   for adminlte; color name
     *                          - icon    string   valid $this->_aIco[] or false to use result
     *                          - label   string   text
     *                          - count   integer  counter value
     *                          - more    string   value for bottom line
     *                          - result  string   check result; 
     *                          - progressvalue  integer  value 0..100 for progress bar
     * @param string   $sIcon   icon before label
     * @param string   $sLabel  label
     * @param string   $sMore   more text below a horizontal line
     * @return string
     */
    protected function _getTile($aOptions = array()) {
        $oA=new renderadminlte();
        $sDiv='<div class="col-md-3 col-sm-6 col-xs-12">';
        foreach (array('color', 'count', 'icon', 'label', 'more', 'result') as $sKey) {
            if (!isset($aOptions[$sKey])) {
                $aOptions[$sKey] = false;
            }
        }
        $sReturn = ''
                . $sDiv . $oA->getWidget(array(
                    'bgcolor'=>isset($aOptions['bgcolor']) ? $aOptions['bgcolor'] : false,
                    'color'=>$this->_getAdminLteColorByResult($aOptions['result'], $aOptions['color']),
                    'icon' => $this->_getIconClass($aOptions['icon'], $aOptions['result']),
                    'onclick' =>isset($aOptions['onclick']) ? $aOptions['onclick'] : false,
                    'number' => $aOptions['count'],
                    'text' => $aOptions['label'],
                    'progressvalue' => isset($aOptions['progressvalue']) ? $aOptions['progressvalue'] : false,
                    'progresstext' => '&nbsp;&nbsp;'.$aOptions['more'],
                )).'</div>'
        ;
        return $sReturn;
    }
    
    
    /**
     * calculate times where the app was in a given status and the uptime
     * the values are in seconds
     * 
     *     [counter] => Array
     *        (
     *            [0] => 942457
     *            [1] => 0
     *            [2] => 0
     *            [3] => 292
     *        )
     *     [items] => Array (...)
     *     [total] => [integer]
     * 
     * @param type $aLog
     * @return array
     */
    protected function _getUptime($aLog=array()){
        $aReturn=array('counter'=>array(0=>0, 1=>0, 2=>0, 3=>0), 'items'=>array());
        $iLastTimer=date("U");
        $iTotal=0;
        if(count($aLog)){
            foreach($aLog as $aLogItem){
                $aItem=$aLogItem;
                unset($aItem['result']);
                $iDelta=$iLastTimer-$aItem['timestamp'];
                $iLastTimer=$aItem['timestamp'];
                
                $aItem['duration']=$iDelta;
                
                $aReturn['items'][]=$aItem;
                $aReturn['counter'][$aItem['status']]+=$iDelta;
                $iTotal+=$iDelta;
            }
        }
        $aReturn['total']=$iTotal;
        return $aReturn;
    }


    /**
     * get html code for tiles of a single webapp
     * 
     * @param string  $sAppId  webapp id
     * @return string
     */
    protected function _generateWebappTiles($sAppId) {
        $aHostdata = $this->_data[$sAppId]['result'];
        $this->oNotification->setApp($sAppId);
        $sReturn = '';
        $oA=new renderadminlte();

        $sMoreChecks = '';
        if (isset($aHostdata['summary'])) {
            foreach ($this->_getResultDefs(true) as $i) {
                $sMoreChecks .= ($aHostdata['summary'][$i] 
                        ? $oA->getBadge(array(
                            'bgcolor'=>$this->_getAdminLteColorByResult($i), 
                            'title'=>$aHostdata['summary'][$i] . ' x ' . $this->_tr('Resulttype-' . $i), 
                            'text'=>$aHostdata['summary'][$i]
                        )).' '
                        : ''
                    );
                    //     '<span class="badge result' . $i . '" title="' . $aHostdata['summary'][$i] . ' x ' . $this->_tr('Resulttype-' . $i) . '">' . $aHostdata['summary'][$i] . '</span>' : '');
            }
            $sMoreChecks=$sMoreChecks? '<span style="float: right">'.$sMoreChecks.'</span>' : '';
        }

        foreach($this->_aCfg['view']['appdetails'] as $key=>$bVisibility){
            switch ($key) {
                case 'appstatus':
                    $aLast = $this->oNotification->getAppLastResult();
                    $sSince = $aLast && (int) $aLast['result']['ts'] ? $this->_tr('since') . ' ' . date("Y-m-d H:i", $aLast['result']['ts']) : '';
                    $sReturn .= (isset($aHostdata['result']) && $bVisibility 
                        ? $this->_getTile(array(
                            'result' => $aHostdata['result'],
                            'label' => $this->_tr('Appstatus'),
                            'count' => $this->_tr('Resulttype-' . $aHostdata['result']),
                            'more' => $sSince   
                        )) 
                        : ''
                    );
                break;
                case 'httpcode':
                    $sReturn.= $bVisibility 
                        ? $this->_getTile(array(
                                'result' => ((int)$aHostdata['httpstatus'] == 0 || $aHostdata['httpstatus'] >= 400 ) 
                                    ? RESULT_ERROR 
                                    : false
                                ,
                                'label' => $this->_tr('Http-status'),
                                'count' => $aHostdata['httpstatus'] ? $aHostdata['httpstatus'] : '??',
                            )) 
                        : ''
                    ;
                break;
                case 'age':
                    $bOutdated=isset($aHostdata["outdated"]) && $aHostdata["outdated"];
                    $sReturn.= $bVisibility 
                        ? $this->_getTile(array(
                            'result' => $bOutdated ? RESULT_ERROR : RESULT_OK,
                            'icon' => $this->_aIco['age'],
                            'label' => $this->_tr('age-of-result'),
                            'count' => '<span class="timer-age-in-sec">' . (time() - $aHostdata['ts']) . '</span>s',
                            'more' => $this->_tr('TTL') . '=' . $aHostdata['ttl'] . 's',
                        ))
                        : ''
                    ;
                break;
                case 'checks':
                    $sReturn.= $bVisibility && isset($aHostdata['summary']['total'])
                        ? $this->_getTile(array(
                            'result' => $aHostdata['result'],
                            'icon' => $this->_aIco['check'],
                            'label' => $this->_tr('Checks-on-webapp'),
                            'count' => $aHostdata['summary']['total'] . ($aHostdata['result'] === RESULT_OK ? '' : ' '.$sMoreChecks),
                        ))
                        : ''
                    ;
                break;
                case 'times':
                    $sReturn.= $bVisibility && isset($this->_data[$sAppId]['meta']['time']) 
                        ? $this->_getTile(array(
                            'icon' => $this->_aIco['time'],
                            'label' => $this->_tr('Time-for-all-checks'),
                            'count' => preg_replace('/\.[0-9]*/', '', $this->_data[$sAppId]['meta']['time']),
                        ))
                        : ''
                    ;
                break;
                case 'receiver':
                    $this->oNotification->setApp($sAppId, $this->_data[$sAppId]);
                    $aEmailNotifiers = $this->oNotification->getAppNotificationdata('email');
                    $aSlackChannels = $this->oNotification->getAppNotificationdata('slack', 1);

                    // $aPeople=array('email1@example.com', 'email2@example.com');
                    $sMoreNotify = (count($aEmailNotifiers) ? '<span title="' . implode("\n", $aEmailNotifiers) . '">' . count($aEmailNotifiers) . ' x ' . $this->_aIco['notify-email'] . '</span> ' : '')
                            // .'<pre>'.print_r($this->oNotification->getAppNotificationdata(), 1).'</pre>'
                            . (count($aSlackChannels) ? '<span title="' . implode("\n", array_keys($aSlackChannels)) . '">' . count($aSlackChannels) . ' x ' . $this->_aIco['notify-slack'] . '</span> ' : '')
                    ;
                    $iNotifyTargets = count($aEmailNotifiers) + count($aSlackChannels);
                    $sReturn.= $bVisibility 
                        ? $this->_getTile(array(
                            'result' => $iNotifyTargets ? false : RESULT_WARNING,
                            'icon' => $this->_aIco['notifications'],
                            'label' => $this->_tr('Notify-address'),
                            'count' => $iNotifyTargets,
                            'more' => $sMoreNotify
                        ))
                        : ''
                    ;
                break;
                case 'notification':
                    $sSleeping = $this->oNotification->isSleeptime();
                    $sReturn.= $bVisibility 
                        ? $this->_getTile(array(
                            'result' => ($sSleeping ? RESULT_WARNING : false),
                            'icon' => ($sSleeping ? $this->_aIco['sleepmode-on'] : $this->_aIco['sleepmode-off']),
                            'label' => ($sSleeping ? $this->_tr('Sleepmode-on') : $this->_tr('Sleepmode-off')),
                            'more' => $sSleeping,
                        ))
                        : ''
                    ;
                break;

                default:
                    $sReturn .= $this->_getTile(array(
                            'result' => RESULT_ERROR,
                            'label' => 'ERROR: unknown tile',
                            'count' => $key,
                            'more' => 'config -> view -> appdetails',
                        ))
                        ;
                    break;
            }
        }
        $sReturn .= '<div style="clear: both;"></div>';
        return $sReturn;
    }

    /**
     * get html code for tiles of a webapp overview with all applications
     * 
     * @return string
     */
    protected function _generateWebTiles() {
        $sReturn = '';
        $aCounter = $this->_getCounter();
        $oA=new renderadminlte();

        $sMoreHosts = '';
        
        $iResultApps=false;
        foreach ($this->_getResultDefs(true) as $i) {
            $sMoreHosts .= ($aCounter['appresults'][$i] 
                    ? $oA->getBadge(array(
                            'bgcolor'=>$this->_getAdminLteColorByResult($i), 
                            'title'=>$aCounter['appresults'][$i] . ' x ' . $this->_tr('Resulttype-' . $i), 
                            'text'=>$aCounter['appresults'][$i]
                        )).' '
                    : ''
                );
                //    '<span class="badge result' . $i . '" title="' . $aCounter['appresults'][$i] . ' x ' . $this->_tr('Resulttype-' . $i) . '">'.$aCounter['appresults'][$i].'</span>' : '');
            if($aCounter['appresults'][$i] && $iResultApps===false){
                $iResultApps=$i;
            }
        }
        $sMoreHosts=$sMoreHosts ? '<span style="float: right;">'.$sMoreHosts.'</span>' : '';
        
        foreach($this->_aCfg['view']['overview'] as $key=>$bVisibility){
            switch ($key) {
                case 'webapps':
                    $sReturn .= $bVisibility
                        ? $this->_getTile(array(
                            'onclick'=> 'setTab(\'#divwebs\');',
                            'result' => $iResultApps,
                            'count' => ($iResultApps === RESULT_OK ? '' : ' '.$sMoreHosts). $aCounter['apps'],
                            'icon' => $this->_aIco['webapp'],
                            'label' => $this->_tr('Webapps'),
                        ))
                        : ''
                    ;
                    break;
                case 'hosts':
                    $sReturn .= $bVisibility
                        ? $this->_getTile(array(
                            'count' => $aCounter['hosts'],
                            'icon' => $this->_aIco['host'],
                            'label' => $this->_tr('Hosts'),
                        ))
                        : ''
                    ;
                    break;
                case 'checks':
                    $aCounter = $this->_getCounter();

                    $sMoreChecks = '';
                    $iResultChecks=false;
                    foreach ($this->_getResultDefs(true) as $i) {
                        $sMoreChecks .= ($aCounter['checkresults'][$i] 
                                ? $oA->getBadge(array(
                                        'bgcolor'=>$this->_getAdminLteColorByResult($i), 
                                        'title'=>$aCounter['checkresults'][$i] .' x '.$this->_tr('Resulttype-' . $i), 
                                        'text'=>$aCounter['checkresults'][$i])
                                    ).' '
                                : '');
                        if($aCounter['checkresults'][$i] && $iResultChecks===false){
                            $iResultChecks=$i;
                        }
                    }
                    $sMoreChecks=$sMoreChecks ? '<span style="float: right">'.$sMoreChecks.'</span>' : '';
                    $sReturn.= $bVisibility 
                        ? $this->_getTile(array(
                            'result' => $iResultChecks,
                            'count' => $aCounter['checks'].($iResultChecks === RESULT_OK ? '' : ' '.$sMoreChecks),
                            'label' => $this->_aIco['check'] . ' ' . $this->_tr('Checks-total'),
                            'onclick'=> 'setTab(\'#divproblems\');',
                        ))
                        : ''
                    ;
                break;
                case 'notification':
                    $sSleeping = $this->oNotification->isSleeptime();
                    $sReturn.= $bVisibility 
                        ? $this->_getTile(array(
                            'result' => ($sSleeping ? RESULT_WARNING : false),
                            'icon' => ($sSleeping ? $this->_aIco['sleepmode-on'] : $this->_aIco['sleepmode-off']),
                            'label' => ($sSleeping ? $this->_tr('Sleepmode-on') : $this->_tr('Sleepmode-off')),
                            'more' => $sSleeping,
                        ))
                        : ''
                    ;
                break;

                default:
                    $sReturn .= $this->_getTile(array(
                            'result' => RESULT_ERROR,
                            'label' => 'ERROR: unknown tile',
                            'count' => $key,
                            'more' => 'config -> view -> appdetails',
                        ))
                        ;
                    break;
            }
        }
        $sReturn .= '<div style="clear: both;"></div>';
        
        return $sReturn;
    }

    protected function _checkClientResponse($sAppId){
        if(!isset($this->_data[$sAppId])){
            return false;
        }
        $aErrors=array();
        $aWarnings=array();

        $aData=$this->_data[$sAppId];
        
        // ----- validate section meta
        if (!isset($aData['meta'])){
            $aErrors[]=$this->_tr('msgErr-missing-section-meta');
        } else {
            foreach(array('host', 'website', 'result') as $sMetakey){
                if (!isset($aData['meta'][$sMetakey]) || $aData['meta'][$sMetakey]===false){
                    $aErrors[]=$this->_tr('msgErr-missing-key-meta-'.$sMetakey);
                }
            }
            foreach(array('ttl', 'time', 'notifications') as $sMetakey){
                if (!isset($aData['meta'][$sMetakey])){
                    $aWarnings[]=$this->_tr('msgWarn-missing-key-meta-'.$sMetakey);
                }
            }
            
            if (isset($aData['notifications'])){
                if (
                    !isset($aData['notifications']['email'])
                    || !count($aData['notifications']['email'])
                    || !isset($aData['notifications']['slack'])
                    || !count($aData['notifications']['slack'])
                ){
                    $aWarnings[]=$this->_tr('msgWarn-no-notifications');
                }
            }
        }
        // ----- validate section with checks
        if (!isset($aData['checks'])){
            $aErrors[]=$this->_tr('msgErr-missing-section-checks');
        } else {
            $iCheckCounter=0;
            foreach($aData['checks'] as $aSingleCheck){
                foreach(array('name', 'result') as $sMetakey){
                    if (!isset($aSingleCheck[$sMetakey]) || $aSingleCheck[$sMetakey]===false){
                        $aErrors[]=sprintf($this->_tr('msgErr-missing-key-checks-'.$sMetakey), $iCheckCounter);
                    }
                }
                foreach(array('description', 'value', 'time') as $sMetakey){
                    if (!isset($aSingleCheck[$sMetakey]) || $aSingleCheck[$sMetakey]===false){
                        $aWarnings[]=sprintf($this->_tr('msgWarn-missing-key-checks-'.$sMetakey), $iCheckCounter);
                    }
                }
                $iCheckCounter++;
            }
        }
        
        // ----- return result
        return array(
            'error'=>$aErrors,
            'warning'=>$aWarnings,
        );
    }
    
    /**
     * get html code to show a welcome message if no webapp was setup so far.
     * @return string
     */
    protected function _showWelcomeMessage() {
        return $this->_aIco["welcome"] . ' ' . $this->_tr('msgErr-nocheck-welcome')
            . '<br>'
            . '<a class="btn btn-primary" href="#divsetup" onclick="setTab(this.hash);">' . $this->_aIco['setup'] . ' ' . $this->_tr('Setup') . '</a>'
        ;
    }

    /**
     * Get an array with group items of the checks
     * @return array
     */
    protected function _getVisualGroups(){
        $iGroup=10000; // starting node id for groups
        $aReturn=[];
        $sBaseUrl=dirname($_SERVER['SCRIPT_NAME']).'/images/icons/';
        foreach([
            'cloud',
            'database',
            'deny',
            'disk',
            'file',
            'folder',
            'monitor',
            'network',
            'security',
            'service',
        ] as $sGroupname){
            $aReturn[$sGroupname]=[ 
                'id'=>$iGroup++,
                'label'=>$this->_tr('group-'.$sGroupname),
                'image'=>$sBaseUrl.$sGroupname.'.png',
            ];
        }
        
        return $aReturn;
    }


    /**
     * get image as data: string to embed 
     * @param  array  $aOptions  hash with option keys
     *                           - bgcolor  background of svg rect
     *                           - width    width of svg rect
     *                           - height   height of svg rect
     *                           - style    style of html div
     *                           - content  html code of div
     * @return string
     */
    protected function _getHtmlInSvg($aOptions){
        $revert = array('%21'=>'!', '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')');
        $svg ='<svg xmlns="http://www.w3.org/2000/svg"'
            . (isset($aOptions['width'])   ? ' width="'.(int)$aOptions['width'].'"'  : '')
            . (isset($aOptions['height'])  ? ' height="'.(int)$aOptions['height'].'"' : '')
        .' >' 
            .'<rect x="0" y="0"'
                . (isset($aOptions['width'])   ? ' width="'.(int)$aOptions['width'].'"'  : '')
                . (isset($aOptions['height'])  ? ' height="'.(int)$aOptions['height'].'"' : '')
                . (isset($aOptions['bgcolor']) ? ' fill="'.$aOptions['bgcolor'].'"'      : '')
                .' stroke-width="20" stroke="#ffffff" >'
            .'</rect>' 
            .'<foreignObject x="15" y="10" width="100%" height="100%">' 
                .'<div xmlns="http://www.w3.org/1999/xhtml"'
                . (isset($aOptions['style']) ? ' style="'.$aOptions['style'].'"' : '')
                .'>' 
                . (isset($aOptions['content']) ? $aOptions['content'] : '')
                ."</div>" 
            .'</foreignObject>'
        .'</svg>'
        ;
        // die($svg);
        // echo '<pre>'.htmlentities($svg).'</pre>'; 
        // die();
        return "data:image/svg+xml;charset=utf-8," . strtr(rawurlencode($svg), $revert);
    }

    /**
     * helper for _generateMonitorGraph: find node id of parent check
     */
    protected function _findNodeId($sNeedle, $sKey, $aNodes){
        foreach ($aNodes as $aNode){
            if (isset($aNode[$sKey]) && $aNode[$sKey]===$sNeedle){
                return $aNode['id'];
            }
        }
        return false;
    }
    /**
     * Get html code for visual view of all checks
     * @return string
     */
    protected function _generateMonitorGraph($sUrl){
        $sReturn='';

        // files with .png must exist in server/images/icons/
        $aParentsCfg=$this->_getVisualGroups();
        $aParents=[];
        $aNodes=[];
        $aEdges=[];
        $iCounter=1;

        $aShapes=[
            RESULT_OK      => [ 'color' => '#aaeeaa', 'width' => 3 ],
            RESULT_UNKNOWN => [ 'color' => '#bbbbbb', 'width' => 3, 'shape'=>'ellipse' ],
            RESULT_WARNING => [ 'color' => '#eeaa22', 'width' => 6, 'shape'=>'dot' ],
            RESULT_ERROR   => [ 'color' => '#ffcccc', 'width' => 9, 'shape'=>'star' ],
        ];

        foreach ($this->_data as $sAppId => $aEntries) {
            // echo '<pre>'.print_r($aEntries,1); die();
            if($sUrl != $aEntries['result']['url']) {
                continue;
            }

            //
            // --- add application node
            //
            $aNodes[]=[ 
                'id'=> 1, 
                'title'=> ''
                    .'<div class="result'.$aEntries['meta']["result"].'">'
                    .'<img src="images/icons/check-'.$aEntries['meta']["result"].'.png">'
                        .$this->_tr('Resulttype-'.$aEntries['meta']["result"])
                        .' - '
                        .'<strong>'.$aEntries['meta']['website'].'</strong><br>'

                    .'</div>'
                    , 
                'label'=> $aEntries['meta']['website'], 
                'shape' => 'box', 
                'widthConstraint' => [ 'maximum' => 300 ],
                /*
                // 'shape'=>'image',
                // 'image'=>"data:image/svg+xml;charset=utf-8," . strtr(rawurlencode($svg), $revert),
                'image__'=>$this->_getHtmlInSvg([
                    'bgcolor'=>$aShapes[$aEntries['meta']['result']]['color'],
                    'width'=>500,
                    'height'=>60,
                    'style'=>'font-size:1.5em; text-align: center; padding: 0.1em; font-weight: bold;',
                    'content'=>''
                        .'<span style="color:black; opacity: 0.5;">' 
                            .$this->_tr('Resulttype-'.$aEntries['meta']["result"]).' - '
                        .'</span>'
                        .'<span style="color:black; text-shadow:0 0 1px #ffffff,0 0 2px #ffffff,0 0 20px #888888; ">' 
                            .$aEntries['meta']['website']
                        .'</span>'
                    ]),
                */
                'color'=>$aShapes[$aEntries['meta']['result']]['color'] ,

                // 'margin' =>[ 'top' => 20, 'right' => 50, 'bottom' => 20, 'left' => 50 ] ,
                'margin' => 20 ,
            ];

            foreach ($aEntries["checks"] as $aCheck) {
                // echo '<pre>'.print_r($aCheck,1); die();
                $iCounter++;
                //
                // --- add check node
                //
                $iCheckId=$iCounter;
                $iParent=1;
                $iGroup=false;
                $aNodes[]=[ 
                    '_check' => $aCheck['name'], // original check name - used for _findNodeId()
                    'id'=> $iCheckId, 
                    'label'=> $aCheck['name'], 
                    'title'=>'<table class="result'.$aCheck["result"].'"><tr>'
                        .'<td align="center">'
                            .'<img src="images/icons/check-'.$aCheck["result"].'.png"><br>'
                            .$this->_tr('Resulttype-'.$aCheck["result"])
                        .'</td><td>'
                            .'&nbsp;&nbsp;&nbsp;&nbsp;'
                        .'</td><td>'
                            .(isset($aCheck['group']) && isset($aParentsCfg[$aCheck['group']]['image']) ? '<img src="'.$aParentsCfg[$aCheck['group']]['image'].'" width="22"> ' : '')
                            .'<strong>'.$aCheck["name"].'</strong><br>'
                            .'<em>'.$aCheck["description"].'</em><br>'
                            ."<br>"
                            .$aCheck['value']
                        .'</td>'
                        .'</tr></table>'
                        ,

                    'shape'=>'image',
                    'image'=>"images/icons/check-".$aCheck["result"].".png",
                ];
                // --- find parent check node

                if(isset($aCheck["parent"]) && $aCheck["parent"]){
                    $iParent=$this->_findNodeId($aCheck["parent"],'_check',$aNodes);
                }
                // --- if a group was given: detect a group connected on parent 
                if(isset($aCheck['group']) && $aCheck['group']) {
                    $sGroup2Detect=$aCheck['group'].'_'.$iParent;
                    $iGroup=$this->_findNodeId($sGroup2Detect,'_group',$aNodes);
                    if(!$iGroup){
                        // create group node
                        $iCounter++;
                        $iGroup=$iCounter;
                        $aNodes[]=[ 
                            '_group' => $aCheck['group'].'_'.$iParent, // group name - used for _findNodeId()
                            'id'=> $iGroup, 
                            'label'=> isset($aParentsCfg[$aCheck['group']]['label']) ? $aParentsCfg[$aCheck['group']]['label'] : '['.$aCheck['group'].']', 
                            'shape'=> isset($aParentsCfg[$aCheck['group']]['image']) ? 'image' : 'box',
                            'image'=> isset($aParentsCfg[$aCheck['group']]['image']) ? $aParentsCfg[$aCheck['group']]['image'] : 'NOIMAGE ' . $aCheck['group'],
                            'opacity'=>0.2
                        ];
                        // connect it with app or perent check
                        $aEdges[]=[ 'from' => $iParent, 
                            'to' => $iGroup, 
                            'color' => [ 'color' => $aShapes[$aCheck['result']]['color'] ], 
                            'width' => $aShapes[$aCheck['result']]['width'] 
                    ];
                    }
                }

                $aEdges[]=[ 'from' => ($iGroup ? $iGroup : $iParent), 
                    'to' => $iCheckId, 
                    'color' => [ 'color' => $aShapes[$aCheck['result']]['color'] ], 
                    'width' => $aShapes[$aCheck['result']]['width'] 
                ];
            }
        }
        // echo '<pre>'.print_r($aParents,1); die();
        // echo '<pre>'.print_r($aEdges,1); die();
        // echo '<pre>'.print_r($aNodes,1); die();
        $sReturn.='
        

        </style>
        <div id="network-toolbar">
            <span id="selView">[]</span>
            <button class="btn btn-default" onclick="oMap.switchViewMode(); return false;">switch View</button>
            <!--
            -->
            <button class="btn btn-default" onclick="oMap.switchViewSize(); return false;">switch Size</button>

        </div>
        <div id="mynetwork"></div>

        <script type="text/javascript">
        
        // GRRR instance must have the name oMap at the moment
        var oMap=new visJsNetworkMap();
        if(!oMap){
            console.log("ERROR: var oMap=new visJsNetworkMap(); failed.")
        } else {
            oMap.setData('.json_encode($aNodes).', '.json_encode($aEdges).');
            oMap.redrawMap();
        }

      </script>        
        ';
        // echo "<pre>" . htmlentities($sReturn); die();
        // echo "<pre>" . htmlentities(json_encode($aNodes)); die();
        return $sReturn;
    }

    /**
     * helper: generate html code with all checks.
     * if a hast is given it renders the data for this host only
     * 
     * @param  string  $sUrl  optional filter by url; default: all
     * @return string
     */
    protected function _generateMonitorTable($sUrl = false, $bHideOk=false) {
        $sReturn = '';
        if (!count($this->_data)) {
            return $this->_showWelcomeMessage();
        }
        $aCheckGroups=$this->_getVisualGroups();
        $sTableClass = $sUrl ? "datatable-hosts" : "datatable-checks";
        $sTableHead = $sUrl 
        ? $this->_generateTableHead(array(
            $this->_tr('Result'),
            // $this->_tr('TTL'),
            $this->_tr('Group'),
            $this->_tr('Check'),
            $this->_tr('Description'),
            $this->_tr('Output'),
            $this->_tr('Count'),
            $this->_tr('Time'),
        )) : $this->_generateTableHead(array(
            $this->_tr('Result'),
            $this->_tr('Timestamp'),
            $this->_tr('Host'),
            $this->_tr('Webapp'),
            $this->_tr('TTL'),
            $this->_tr('Group'),
            $this->_tr('Check'),
            $this->_tr('Description'),
            $this->_tr('Output'),
            $this->_tr('Count'),
            $this->_tr('Time'),
        ));
        // $sReturn .= '<tbody>';

        foreach ($this->_data as $sAppId => $aEntries) {

            // filter if a host was given
            if (!$sUrl ||
                    (
                    array_key_exists("result", $aEntries) && array_key_exists("url", $aEntries["result"]) && $sUrl == $aEntries["result"]["url"]
                    )
            ) {
                if (
                        $aEntries["result"]["error"]
                ) {
                    // NOP
                } else {

                    foreach ($aEntries["checks"] as $aCheck) {
                        $aTags=isset($aEntries["meta"]["tags"]) ? $aEntries["meta"]["tags"] : false;
                        if ($bHideOk && $aCheck["result"] == RESULT_OK ){
                            continue;
                        }
                        $sReturn .= '<tr class="result' . $aCheck["result"] . ' tags '.$this->_getCssclassForTag($aTags).'">'
                                ;
                        if (!$sUrl) {
                            $sReturn .= 
                                    '<td class="result result'.$aCheck["result"].'"><span style="display: none;">'.$aCheck['result'].'</span>' . $this->_tr('Resulttype-'.$aCheck["result"]).'</td>'
                                    . '<td>' . date("Y-m-d H:i:s", $aEntries["result"]["ts"]) . ' (<span class="timer-age-in-sec">' . (date("U") - $aEntries["result"]["ts"]) . '</span>&nbsp;s)</td>'
                                    . '<td>' . $aEntries["result"]["host"] . '</td>'
                                    . '<td><a href="#" onclick="setTab(\''.$this->_getDivIdForApp($sAppId ).'\');">' . $aEntries["result"]["website"] . '</a></td>'
                                    . '<td>' . $aEntries["result"]["ttl"] . '</td>'
                                    ;
                        } else {
                            $sReturn .= '<td class="result result'.$aCheck["result"].'"><span style="display: none;">'.$aCheck['result'].'</span>' . $this->_tr('Resulttype-'.$aCheck["result"]).'</td>';
                        }
                        $sReturn .= ''// . '<td>' . date("H:i:s", $aEntries["meta"]["ts"]) . ' ' . $this->_hrTime(date("U") - $aEntries["meta"]["ts"]) . '</td>'
                                . '<td>' 
                                . (isset($aCheck["group"]) && $aCheck["group"] && isset($aCheckGroups[$aCheck["group"]])
                                    ? '<img src="'.$aCheckGroups[$aCheck["group"]]['image'].'" width="16">&nbsp;' . $aCheckGroups[$aCheck["group"]]['label']
                                    : '-' 
                                )
                                . '<td>' . $aCheck["name"] . '</td>'
                                
                                . '<td>' . $aCheck["description"] . '</td>'
                                . '<td>' . $aCheck["value"] . '</td>'
                                . '<td>' . (isset($aCheck["count"]) ? $aCheck["count"] : '-') . '</td>'
                                . '<td>' . (isset($aCheck["time"]) ? $aCheck["time"] : '-') . '</td>'
                                . '</tr>';
                    }
                }
            }
        }
        return $sReturn
            ? '<table class="' . $sTableClass . '">' .$sTableHead 
                .'<tbody>'. $sReturn . '</tbody>'
                .'</table>'
            : ''
            ;
    }

    /**
     * get html code for notification log page
     * 
     * @param array   $aLogs         array with logs; if false then all logs will be fetched
     * @param string  $sTableClass   custom classname for the datatable; for custom datatable settings (see functions.js)
     * @return string
     */
    protected function _generateNotificationlog($aLogs=false, $sTableClass='datatable-notifications', $bShowDuration=false) {
        if($aLogs===false){
            $aLogs = $this->oNotification->getLogdata();
        }
        if(!count($aLogs)){
            return $this->_tr('Notifications-none');
        }

        $aTH=array(
                    $this->_tr('Result'),
                    $this->_tr('Timestamp'),
                    $this->_tr('Duration'),
                    $this->_tr('Change'),
                    $this->_tr('Message')
                );
        if (!$bShowDuration){
            unset($aTH[2]);
        }
        $sTable = $this->_generateTableHead($aTH) . "\n";
        $sTable .= '<tbody>';

        $aChanges = array();
        $aResults = array();
        $iLastTimer=date("U");
        foreach ($aLogs as $aLogentry) {

            if (!isset($aChanges[$aLogentry['changetype']])) {
                $aChanges[$aLogentry['changetype']] = 0;
            }
            $aChanges[$aLogentry['changetype']] ++;

            if (!isset($aResults[$aLogentry['status']])) {
                $aResults[$aLogentry['status']] = 0;
            }
            $aResults[$aLogentry['status']] ++;
            $iDelta=$iLastTimer-$aLogentry['timestamp'];
            $iLastTimer=$aLogentry['timestamp'];

            // TODO maybe use $this->_getAdminLteColorByResult()
            $aTags=isset($this->_data[$aLogentry['appid']]["meta"]["tags"]) ? $this->_data[$aLogentry['appid']]["meta"]["tags"] : false;
            $sTable .= '<tr class="result' . $aLogentry['status'] . ' tags '.$this->_getCssclassForTag($aTags).'">'
                    .'<td class="result' . $aLogentry['status'] . '"><span style="display: none;">'.$aLogentry['status'].'</span>' . $this->_tr('Resulttype-' . $aLogentry['status']) . '</td>'
                    . '<td>' . date("Y-m-d H:i:s", $aLogentry['timestamp']) . '</td>'
                    . ($bShowDuration ?  '<td>' . round($iDelta/60) . ' min</td>' : '')
                    . '<td>' . $this->_tr('changetype-' . $aLogentry['changetype']) . '</td>'                    
                    . '<td>' . $aLogentry['message'] . '</td>'
                    . '</tr>';
        }
        $sTable .= '</tbody>' . "\n";
        $sTable = '<table class="'.$sTableClass.'">' . "\n" . $sTable . '</table>';

        $sMoreResults = '';
        for ($i = 0; $i <= 4; $i++) {
            $sMoreResults .= (isset($aResults[$i]) ? '<span class="result' . $i . '">' . $aResults[$i] . '</span> x ' . $this->_tr('Resulttype-' . $i) . ' ' : '');
        }
        return $sTable;
    }

    /**
     * get html code for badged list with errors, warnings, unknown, ok
     * @param string $sAppId  id of app to show
     * @param bool   $bShort  display type short (counter only) or long (with texts)
     * @return string|boolean
     */
    protected function _renderBadgesForWebsite($sAppId, $bShort = false) {
        $iResult = $this->_data[$sAppId]["result"]["result"];
        $oA=new renderadminlte();
        if (!array_key_exists("summary", $this->_data[$sAppId]["result"])) {
            return false;
        }
        $aEntries = $this->_data[$sAppId]["result"]["summary"];
        // $sHtml = $this->_tr('Result-checks') . ': <strong>' . $aEntries["total"] . '</strong> ';
        $sHtml = '';
        for ($i = 3; $i >= 0; $i--) {
            $sKey = $i;
            if ($aEntries[$sKey] > 0) {
                // $sHtml .= '<span class="badge result' . $i . '" title="' . $aEntries[$sKey] . ' x ' . $this->getResultValue($i) . '">' . $aEntries[$sKey] . '</span>';
                $sHtml .= $oA->getBadge(array(
                        'bgcolor'=>$this->_getAdminLteColorByResult($i),
                        'title'=>$aEntries[$sKey] . ' x ' . $this->getResultValue($i),
                        'text'=>$aEntries[$sKey],
                    )).' ';
                // '<span class="badge result' . $i . '" title="' . $aEntries[$sKey] . ' x ' . $this->getResultValue($i) . '">' . $aEntries[$sKey] . '</span>';
                if (!$bShort) {
                    $sHtml .= $this->_tr('Resulttype-' . $i) . ' ';
                }
            }
        }
        return $sHtml ? '<span style="float: right">'.$sHtml.'</span>' : '';
    }

    /**
     * get html code to render a counter tile with bars / line / simple
     * 
     * @param string  $sAppId      name of the app
     * @param string  $sCounterId  name of the counter
     * @param array   $aOptions    rendering options with these keys
     *                             - type   string   one of bar|line|simple
     *                             - label  string   label of the counter
     *                             - size   integer  size in rows in adminlte template; default=2
     *                             - items  integer  max. count of rows to show in chart; default=size x 10
     * @return string
     */
    protected function _renderCounter($sAppId, $sCounterId, $aOptions=array()){
        $oA=new renderadminlte();
        $oCounters=new counteritems($sAppId, $sCounterId);

        $aOptions['type']=$aOptions['type']?$aOptions['type']:'bar';
        $aOptions['label']=isset($aOptions['label']) && $aOptions['label']     ? $aOptions['label']    : '';
        $aOptions['size']=isset($aOptions['size'])   && (int)$aOptions['size'] ?(int)$aOptions['size'] : 2;
        $aOptions['items']=isset($aOptions['items']) && (int)$aOptions['items']?(int)$aOptions['items']: $aOptions['size']*10;
                            
        $aResponseTimeData=$oCounters->get($aOptions['items']);
        $aChartData=array(
            'label'=>array(),
            'value'=>array(),
            'color'=>array(),
        );
        foreach ($aResponseTimeData as $aItem){
            if(isset($aItem['data']['value'])){
                array_unshift($aChartData['label'], date("Y-m-d H:i:s", $aItem['timestamp']));
                array_unshift($aChartData['value'], $aItem['data']['value']);
                array_unshift($aChartData['color'], $this->_getAdminLteColorByResult($aItem['data']['status']));
                // array_unshift($aChartColor, $aColor[rand(0, 3)]);
            }
        }

        $sInnerTile='';
        // print_r($aResponseTimeData[0]);
        $iTtl=isset($this->_data[$sAppId]["result"]["ttl"]) ? $this->_data[$sAppId]["result"]["ttl"] : 300;
        $iAge=date('U')-$aResponseTimeData[0]['timestamp'];
        
        // if timer is outdated then delete it
        if($iAge>$iTtl*6){
            $oCounters->deleteCounter($sCounterId);
            return false;
        }
        
        $iLast=$aResponseTimeData[0]['data']['value'];
        $sTopLabel=(isset($aOptions['label']) && $aOptions['label'] ? $aOptions['label'].'<br>' : '') 
            // . '('.$iAge.' - '.$iTtl.')'
            ;
        switch($aOptions['type']){
            case 'simple':
                $sInnerTile.=$sTopLabel.'<br>'
                    . '<div class="graph">'
                        . '<br>'
                        . '<strong>'.$iLast.'</strong><br>'
                        . '<br>'
                    . '</div>';
                break;
            case 'bar':
            case 'line':
                $aChart=array(
                    'type'=>$aOptions['type'],

                    'xGrid'=>false,
                    // 'xLabel'=>$this->_tr('Chart-time'),
                    'xLabel'=>false,
                    'xValue'=>false,

                    'yGrid'=>false,
                    'yLabel'=>$aOptions['label'],
                    'yLabel'=>false,
                    'yValue'=>false,

                    'data'=>$aChartData,
                );
                $sInnerTile.= $sTopLabel
                        . '<strong>'.$iLast.'</strong>'
                        . $this->_renderGraph($aChart)
                        ;
                break;
            default:
                $sInnerTile.= $sTopLabel
                    . '<strong>'.$iLast.'</strong><br>'
                    . '?? type = &quot;'.htmlentities($aOptions['type']).'&quot;'
                    ;
        }
        return $oA->getSectionColumn(
                
                '<div class="box counter"'
                    .(($iAge>$iTtl*2) ? ' style="opacity: '.(0.9-($iAge/$iTtl)*0.05).'"' : '')
                .'>'
                    . '<div class="box-body">'
                        . $sInnerTile
                    . '</div>'
                . '</div>'
            ,
            (int)$aOptions['size']?(int)$aOptions['size']:2
        );
    }
    
    /**
     * return html code for a about page
     * @return string
     */
    public function generateViewAbout() {
        $oA=new renderadminlte();
        $sHtml=''
                // . '<h2>' . $this->_aIco["about"] . ' ' . $this->_tr('About') . '</h2>'
                . sprintf($this->_tr('About-title'), $this->_sTitle).'<br>'
                . '<br>'
                . $this->_tr('About-text').'<br>'
                . '<br>'
                . sprintf($this->_tr('About-projecturl'), $this->_sProjectUrl, $this->_sProjectUrl).'<br>'
                . sprintf($this->_tr('About-docs'), $this->_sDocUrl).'<br>'
                ;
        // return $sHtml;
        return $oA->getSectionHead($this->_aIco["about"] . ' ' . $this->_tr('About'))
                . '<section class="content">'
                    . $oA->getSectionRow($oA->getSectionColumn(
                        $oA->getBox(array(
                            'title'=>$this->_tr('About'),
                            'text'=>$sHtml
                        )),
                        12
                    ))  
                .'</section>'
                ;
    }
    
    /**
     * return html code for a view of monitoring data for a single web app
     * @param string  $sAppId  appid
     * @return string
     */
    public function generateViewApp($sAppId) {
        // $this->loadClientData();
        $oA=new renderadminlte();
        $sHtml = '';
        if(!isset($this->_data[$sAppId])){
            return 'ERROR: appid does not exist: '.htmlentities($sAppId);
        }

        $iCounter=0;
        $aEntries=$this->_data[$sAppId];
        $iCounter++;       
        $sValidationContent='';
        $sDivMoredetails='div-http-'.$sAppId;
        $sShowHide='<br><button class="btn btn-default" id="btn-plus-'.$sAppId.'"  onclick="$(\'#'.$sDivMoredetails.'\').slideDown(); $(this).hide(); $(\'#btn-minus-'.$sAppId.'\').show(); return false;"'
                    . '> '.$this->_aIco['plus'].' '.$this->_tr('btn-details').' </button>'
                . '<button class="btn btn-default" id="btn-minus-'.$sAppId.'"  onclick="$(\'#'.$sDivMoredetails.'\').slideUp();   $(this).hide(); $(\'#btn-plus-'.$sAppId.'\').show(); return false;" style="display: none;"'
                . '> '.$this->_aIco['close'].' '.$this->_tr('btn-hide-details').' </button>';
        
        if (true ||
                array_key_exists("result", $aEntries) && array_key_exists("result", $aEntries["result"]) && array_key_exists("website", $aEntries["result"]) && array_key_exists("host", $aEntries["result"])
        ) {
            $sTopHeadline=$oA->getSectionHead(
                    '<a href="#divwebs" onclick="setTab(\'#divwebs\')"'
                        . '> ' . $this->_aIco['allwebapps'] . ' '. $this->_tr('All-webapps-header')
                    .'</a> > <nobr>'
                    . $this->_aIco['webapp'] .' '
                    . $this->_getAppLabel($sAppId)
                    . '</nobr>'
                    );

            
            // --- validation of items in client data array
            $aValidatorResult=$this->_checkClientResponse($sAppId);
            
            // check if request failed
            if (isset($aEntries['result']['error']) && $aEntries['result']['error']){
                $sValidationContent.= $oA->getAlert(array(
                    'type'=>'danger',
                    'dismissible'=>false,
                    'title'=>$this->_aIco['error'].' '.$this->_tr('Validator-request-error'),
                    'text'=>$aEntries['result']['error']
                    ));
            }

            if(!$sValidationContent && $aValidatorResult){
                foreach($aValidatorResult as $sSection=>$aMessageItems){
                    if(count($aMessageItems)){
                        $sDivContent='';
                        foreach($aMessageItems as $sSingleMessage){
                            $sDivContent.= '- '.$sSingleMessage.'<br>';
                        }
                        $sValidationContent.= $sDivContent 
                            ? $oA->getAlert(array(
                                'type'=>$sSection=='error' ? 'danger' : $sSection,
                                'dismissible'=>false,
                                'title'=>$this->_aIco[$sSection].' '.$this->_tr('Validator-'.$sSection),
                                'text'=>$sDivContent
                                ))
                            : ''
                        ;
                    }
                }
            }
        if (array_key_exists("host", $aEntries["result"])) {

            // --- Counter and graphs
            $oCounters=new counteritems($sAppId);
            /** 
             * @var array
             * 
             */
            $aCounters=$oCounters->getCounters();
            $sCounters='';
            if(count($aCounters)){
                foreach($aCounters as $sCounterId=>$aMeta){
                    if(strpos($sCounterId, 'time')!==0){
                        // echo '<pre>'.print_r($oCounters->get(1), 1).'</pre>';

                        $aMeta['visual']=isset($aMeta['visual']) ? $aMeta['visual'] : 'bar';
                        $aTmp=explode(',', $aMeta['visual']);

                        $sCounters.=$this->_renderCounter($sAppId, $sCounterId, 
                            array(
                                'type'=>isset($aTmp[0]) ? $aTmp[0] : 'bar',
                                'size'=>isset($aTmp[1]) ? $aTmp[1] : false,
                                'items'=>isset($aTmp[2]) ? $aTmp[2] : false,
                                'label'=>isset($aMeta['title']) ? $aMeta['title'] : $sCounterId,
                            )
                        );
                    }
                }
            }

            $sHtml .= $oA->getSectionRow($sCounters);

            // --- graph with checks
            $sHtml .= 
                $oA->getSectionRow(
                    $oA->getSectionColumn(
                        $oA->getBox(array(
                            // 'label'=>'I am a label.',
                            // 'collapsable'=>true,
                            'title'=>$this->_tr('Checks-visualisation'),
                            'text'=>$this->_generateMonitorGraph($aEntries["result"]["url"])
                        ))
                    )
                )
            ;
            // --- table with checks
            $sHtml .= 
                $oA->getSectionRow(
                    $oA->getSectionColumn(
                        $oA->getBox(array(
                            // 'label'=>'I am a label.',
                            // 'collapsable'=>true,
                            'title'=>$this->_tr('Checks'),
                            'text'=>$this->_generateMonitorTable($aEntries["result"]["url"])
                        ))
                    )
                )
            ;
        }


        // --- http status code
        $sStatusIcon=($aEntries['result']['httpstatus']
                ? ($aEntries['result']['httpstatus']>=400
                    ? $this->_aIco['error']
                    : ($aEntries['result']['httpstatus']>=300
                        ? $this->_aIco['warning']
                        : $this->_aIco['ok']
                        )
                    )
                : $this->_aIco['error']
                );

        // --- notifications & uptime for this webapp
        $aLogs = $this->oNotification->getLogdata(array('appid'=>$sAppId));

        $aUptime=$this->_getUptime($aLogs);
        // echo '<pre>'.print_r($aUptime, 1).'</pre>';

        $aChartData=array(
            'label'=>array(),
            'value'=>array(),
            'color'=>array(),
        );
        foreach ($aUptime['counter'] as $iResult=>$iResultCount){
            if($iResultCount){
                array_unshift($aChartData['label'], $this->_tr('Resulttype-'.$iResult));
                array_unshift($aChartData['value'], $iResultCount);
                array_unshift($aChartData['color'], $this->_getAdminLteColorByResult($iResult));
            }
        }

        $aChartUptime=array(
            'type'=>'pie',
            // 'xLabel'=>$this->_tr('Chart-time'),
            // 'yLabel'=>$this->_tr('Chart-responsetime'),
            'data'=>$aChartData,
        );
        $iFirstentry=count($aLogs) ? $aLogs[count($aLogs)-1]['timestamp'] : date('U');

        $sUptime = '';
        if($aUptime['total']){
            $sUptime .= '<table class="table">';
            foreach ($this->_getResultDefs() as $i) {

                $sUptime .= $aUptime['counter'][$i]
                    ?
                    '<tr class="result'.$i.'">'
                        . '<td class="result'.$i.'">'. $this->_tr('Resulttype-'.$i) . '</td>'
                        . '<td style="text-align: right">' . round($aUptime['counter'][$i] / 60).' min</td>'
                        . '<td style="text-align: right"> ' .  number_format($aUptime['counter'][$i]*100 / $aUptime['total'], 3).' %</td>'
                    . '</tr>'
                    : ''
                ;
            }
            $sUptime .= '</table><br>'
                .$this->_renderGraph($aChartUptime)
            ;
        }

        $sHtml .= $oA->getSectionRow(
                    $oA->getSectionColumn(
                        $oA->getBox(array(
                            'title'=>$this->_tr('Notifications'),
                            'text'=> $this->_generateNotificationlog($aLogs, 'datatable-notifications-webapp', true)
                        )),
                        9,
                        'right'
                    )
                    .$oA->getSectionColumn(
                        $oA->getBox(array(
                            'title'=>$this->_tr('Uptime') . ' ('.$this->_tr('since').' '.date('Y-m-d', $iFirstentry).'; ~'.round((date('U')-$iFirstentry)/60/60/24).' d)',
                            'text'=> $sUptime
                        )),
                        3
                    )
                    .$oA->getSectionColumn(
                        $oA->getBox(array(
                            'title'=>$this->_tr('Http-details'),
                            'text'=> ($aEntries['result']['error'] 
                                        ? $oA->getAlert(array(
                                            'type'=>'danger',
                                            'dismissible'=>false,
                                            'text'=>$aEntries['result']['error']
                                        ))
                                        : ''
                                    )
                                . ($aEntries['result']['url'] ? $this->_tr('Url') . ': <a href="' . $aEntries['result']['url'] . '" target="_blank">' . $aEntries['result']['url'] . '</a><br>' : '')
                                . ($aEntries['result']['httpstatus'] ? $this->_tr('Http-status') . ': <strong>' . $sStatusIcon . ' ' . $aEntries['result']['httpstatus'] . '</strong><br>' : '')
                                . ($aEntries['result']['header'] ? $this->_tr('Http-header') . ': <pre>' . $aEntries['result']['header'] . '</pre>' : '')
                        )),
                        3
                    )
                );

                
        // --- debug infos 
        if ($this->_aCfg['debug']) {
            $this->oNotification->setApp($sAppId);
            $sDebugContent='';

            foreach ($this->_getResultDefs() as $i) {
                $sMgIdPrefix = 'changetype-' . $i;
                $sDebugContent .= $this->_tr('changetype-' . $i)
                        . '<pre>'
                        . '' . htmlentities(print_r($this->oNotification->getReplacedMessage($sMgIdPrefix . '.logmessage'), 1)) . '<hr>'
                        . 'TO: ' . implode('; ', $this->oNotification->getAppNotificationdata('email')) . '<br>'
                        . '<strong>' . htmlentities(print_r($this->oNotification->getReplacedMessage($sMgIdPrefix . '.email.subject'), 1)) . '</strong><br>'
                        . '' . htmlentities(print_r($this->oNotification->getReplacedMessage($sMgIdPrefix . '.email.message'), 1)) . '<br>'
                        . '</pre>';
            }

            $sHtml .= $sShowHide. '<div id="'.$sDivMoredetails.'" style="display: none;">'
                . $oA->getSectionRow(
                    $oA->getSectionColumn(
                        $oA->getBox(array(
                            // 'label'=>'I am a label.',
                            // 'collapsable'=>true,
                            // 'collapsed'=>false,
                            'title'=>$this->_tr('Client-source-data'),
                            'text'=>'<pre>' . htmlentities(print_r($aEntries, 1)) . '</pre>'
                        )),
                        12
                    )
                )
                .$oA->getSectionRow($oA->getSectionColumn(
                    $oA->getBox(array(
                            'title'=>$this->_tr('Preview-of-messages'),
                            'text'=>'<pre>' . htmlentities(print_r($this->oNotification->getMessageReplacements(), 1)) . '</pre>'

                    ))
                    , 12))
                .$oA->getSectionRow($oA->getSectionColumn(
                    $oA->getBox(array(
                            'title'=>$this->_tr('Preview-emails'),
                            'text'=>$sDebugContent

                    ))
                    , 12))
                .'</div>';
            }
        }
        return $sTopHeadline 
                
                . '<section class="content">
                    
                    '.$oA->getSectionRow($this->_generateWebappTiles($sAppId)).'<br>'
                    .$sValidationContent
                    .$sHtml.'
                </section>'
                ;
    }
    /**
     * return html code for debug page
     * @return string
     */
    public function generateViewDebug() {
        $oA=new renderadminlte();
        
        $sAlIcons='';
        foreach ($this->_aIco as $sKey=>$sHtmlcode){
            $sAlIcons.='<tr>'
                    . '<td><strong>'.$sKey.'</strong></td>'
                    . '<td align="center">'.preg_replace('/style=\"(.*)\"/u', '', $sHtmlcode).'</td>'
                    . '<td>' . htmlentities($sHtmlcode) . '</td>'
                    . '</tr>';
        }
        return $oA->getSectionHead($this->_aIco["debug"] . ' ' . $this->_tr('Debug'))
                . '<section class="content">'
                . $oA->getSectionRow($oA->getSectionColumn(
                        $oA->getBox(array(
                            'title'=>$this->_tr('Debug-icons'),
                            'text'=>'<table><tr>'
                                . '<th>#</th>'
                                . '<th>' . $this->_tr('Debug-icons-preview') . '</th>'
                                . '<th>' . $this->_tr('Debug-icons-html') . '</th>'
                                . '</tr>'.$sAlIcons.'</table>'
                        )),
                        12
                    ))
                . $oA->getSectionRow($oA->getSectionColumn(
                        $oA->getBox(array(
                            'title'=>$this->_tr('Debug-config'),
                            'text'=>'<pre>' . print_r($this->_aCfg, true) . '</pre>'
                        )),
                        12
                    ))
                . $oA->getSectionRow($oA->getSectionColumn(
                        $oA->getBox(array(
                            'title'=>$this->_tr('Debug-urls'),
                            'text'=>'<pre>' . print_r($this->_urls, true) . '</pre>'
                        )),
                        12
                    ))
                . $oA->getSectionRow($oA->getSectionColumn(
                        $oA->getBox(array(
                            'title'=>$this->_tr('Debug-clientdata'),
                            'text'=>'<pre>' . print_r($this->_data, true) . '</pre>'
                        )),
                        12
                    ))
                .'</section>'
                ;
    }
    /**
     * return html code for notification page
     * @return string
     */
    public function generateViewNotifications() {
        $oA=new renderadminlte();
        $sHtml=$this->_generateNotificationlog();
        return $oA->getSectionHead($this->_aIco["notifications"] . ' ' . $this->_tr('Notifications-header'))
                . '<section class="content">'
                . $oA->getSectionRow($oA->getSectionColumn(
                        $oA->getBox(array(
                            'title'=>$this->_tr('Notifications-header'),
                            'text'=>$sHtml
                        )),
                        12
                    )).'
                </section>'
                ;
    }
    /**
     * return html code for notification page
     * @return string
     */
    public function generateViewProblems() {
        $oA=new renderadminlte();
        $sTable=$this->_generateMonitorTable(
            false, // no url to filter ... =all checks
            true   // hide OK status messages 
        );
        $aWebapps=$this->_generateWeblist(true);

        $sNoDataHtml=isset($aWebapps[false])
            ? implode('', array_values($aWebapps[false]))
            : '';
        $sAppsHtml=isset($aWebapps[true])
            ? implode('', array_values($aWebapps[true]))
            : '';
            // : '<strong>'.$this->_aIco['check'].' '. $this->_tr('Problems-webapps-ok').'</strong>';

        $sChecksHtml=$sTable 
            ? $sTable 
            : '<strong>'.$this->_aIco['check'].' '. $this->_tr('Problems-checks-ok').'</strong>';


        return $oA->getSectionHead($this->_aIco["problems"] . ' ' . $this->_tr('Problems'))
                . '<section class="content">'
                .$oA->getSectionRow($this->_generateWebTiles())
                . '<br>'

                . ("${sNoDataHtml}${sAppsHtml}" 
                    ? $oA->getSectionRow($oA->getSectionColumn(
                        $oA->getBox(array(
                            'title'=>$this->_tr('Problems-webapps-header'),
                            'text'=>
                                $this->_tr('Problems-webapps-hints').'<br><br>'
                                .(isset($aWebapps[false])
                                    ? $this->_aIco['host'].' '.$this->_tr('Problems-webapps-hints-host').'<br>'
                                    : ''
                                )
                                .(isset($aWebapps[true])
                                    ? $this->_aIco['webapp'].' '.$this->_tr('Problems-webapps-hints-package').'<br>'
                                    : ''
                                )
                                .'<br>'
                                .'<div id="divwebsfilter"></div><br>'
                                .'<div id="divwebs">'
                                    . $sNoDataHtml.$sAppsHtml
                                .'</div>'
                        )),
                        12
                    ))
                    : ''
                    )
                . $oA->getSectionRow($oA->getSectionColumn(
                        $oA->getBox(array(
                            'title'=>$this->_tr('Problems-checks-header'),
                            'text'=>$this->_tr('Problems-checks-hints').'<br><br>'.$sChecksHtml
                        )),
                        12
                    ))
                .'
                </section>'
                ;
    }
    
    private function _renderSelect($aOptions, $sAcive){
        
    }
    /**
     * return html code for setup page
     * @return string
     */
    public function generateViewSetup() {
        $oA=new renderadminlte();
        $sFormOpenTag = '<form action="?#divsetup" class="form-horizontal" method="POST">';
        
        // list of all clients
        $sHostlist='';
        foreach ($this->_data as $sAppId => $aData) {
            $iResult = array_key_exists("result", $aData["result"]) ? $aData["result"]["result"] : 3;
            $sUrl = $aData["result"]["url"];
            $sWebsite = array_key_exists("website", $aData["result"]) ? $aData["result"]["website"] : $this->_tr('unknown') . ' (' . $sUrl . ')';
            $sHost = array_key_exists("host", $aData["result"]) ? $aData["result"]["host"] : $this->_tr('unknown');

            $aTags=isset($aData["meta"]["tags"]) ? $aData["meta"]["tags"] : false;
            $sHostlist .= $oA->getSectionRow($oA->getSectionColumn(
                '<div class="divhost result' . $iResult . ' tags '.$this->_getCssclassForTag($aTags).'" style="float: none; ">'
                . $oA->getBox(array(
                    'title'=>''
                        . $this->_getAppLabel($sAppId),
                    'text'=>''
                        // Button DELETE
                        . '<div style="float: right;">'
                        . $sFormOpenTag
                            . '<input type="hidden" name="action" value="deleteurl">'
                            . '<input type="hidden" name="url" value="' . $sUrl . '">'
                            . '<button class="btn btn-danger" '
                                . 'onclick="return confirm(\'' . sprintf($this->_tr('btn-deleteUrl-confirm'), $sUrl) . '\')" '
                                . '>' . $this->_aIco['del'].' '.$this->_tr('btn-deleteUrl') 
                            . '</button>'
                        . '</form>'
                        . '</div>'
                        // /DELETE

                        .$this->_aIco['url'] . ' <a href="' . $sUrl . '" target="_blank">'. $sUrl. '</a><br>'
                        .$this->_aIco['host'] . ' ' . $this->_tr('Host') . ' ' . $sHost . '<br>'
                ))
                .'</div>',
                12
            ))
            ;
        }
        
        $sSetup=$sFormOpenTag . '<input type="hidden" name="action" value="savesettings">';

        $sSetup.='<h4>'.$this->_tr('hint').'</h4><p>'
                . $this->_tr('settings-hint') 
                . '</p>';
        /*  
        // add elements

        $sSetup.='<button class="btn btn-success" '
                                // . 'onclick="return confirm(\'' . sprintf($this->_tr('btn-deleteUrl-confirm'), $sUrl) . '\')" '
                                . '>' . $this->_aIco['save'].' '.$this->_tr('btn-save') 
                            . '</button>';
        */
        $sSetup.='</form>';
        $sAppId=isset($sAppId) ? $sAppId : 'no-app-id';
        $sDivMoredetails='div-http-'.$sAppId;
        $sShowHide='<br><button class="btn btn-default" id="btn-plus-'.$sAppId.'"  onclick="$(\'#'.$sDivMoredetails.'\').slideDown(); $(this).hide(); $(\'#btn-minus-'.$sAppId.'\').show(); return false;"'
                    . '> '.$this->_aIco['plus'].' '.$this->_tr('btn-details').' </button>'
                . '<button class="btn btn-default" id="btn-minus-'.$sAppId.'"  onclick="$(\'#'.$sDivMoredetails.'\').slideUp();   $(this).hide(); $(\'#btn-plus-'.$sAppId.'\').show(); return false;" style="display: none;"'
                . '> '.$this->_aIco['close'].' '.$this->_tr('btn-hide-details').' </button>';
        
        return $oA->getSectionHead($this->_aIco["setup"] . ' ' . $this->_tr('Setup'))
                . '<section class="content">'

                    . $oA->getSectionRow(
                        $oA->getSectionColumn(
                            // box for adding new client url
                            // box for adding new client url
                            $oA->getBox(array(
                                'title'=>$this->_tr('Setup-configuration'),
                                'text'=> ''
                                    . $sSetup
                                    . $sShowHide
                                    . '<div id="'.$sDivMoredetails.'" style="display: none;">'
                                        . '<pre>'.print_r($this->_aCfg, 1).'</pre>'
                                    . '</div>'

                            )),
                            6
                        )
                        . $oA->getSectionColumn(
                            // box for adding new client url
                            // box for adding new client url
                            $oA->getBox(array(
                                'title'=>$this->_tr('Setup-add-client'),
                                'text'=> '<p>' . $this->_tr('Setup-add-client-pretext') . '</p>'
                                    . $sFormOpenTag
                                    . '<div class="input-group">'
                                        . '<div class="input-group-addon">'
                                            . $this->_aIco['url']
                                        . '</div>'
                                        . '<input type="hidden" name="action" value="addurl">'
                                        . '<input type="text" class="form-control" name="url" size="100" value="" '
                                            . 'placeholder="https://[domain]/appmonitor/client/" '
                                            . 'pattern="http.*://..*" '
                                            . 'required="required" '
                                        . '>'
                                        . '<span class="input-group-btn">'
                                            . '<button class="btn btn-success">' . $this->_aIco['add'].' '.$this->_tr('btn-addUrl') . '</button>'
                                        . '</span>'
                                    . '</div>'
                                    . '</form><br>'

                            )),
                            6
                        )
                        .$oA->getSectionColumn(
                            $oA->getBox(array(
                                'title'=>$this->_tr('Setup-client-list'),
                                'text'=> '<div id="divsetupfilter"></div><br>'
                                    . '<div id="divsetup">'
                                        .$sHostlist
                                    . '</div>'
                            )),
                            6
                    ))
                .'</section>'
                ;
    }



    function _generateWeblist($bSkipOk=false){
        $oA=new renderadminlte();
        $aAllWebapps=[];
        foreach ($this->_data as $sAppId => $aEntries) {
            $bHasData = true;
            if (!isset($aEntries["result"]["host"])) {
                $bHasData = false;
            }
            if ($bSkipOk && $aEntries["result"]["result"] == RESULT_OK){
                continue;
            }
            // echo 'DEBUG <pre>'.print_r($aEntries, 1).'</pre>';
            $aValidaion=$this->_checkClientResponse($sAppId);
            $sValidatorinfo='';
            if($aValidaion){
                foreach($aValidaion as $sSection=>$aMessages){
                    if (count($aValidaion[$sSection])){
                        $sValidatorinfo.='<span class="ico'.$sSection.'" title="'.sprintf($this->_tr('Validator-'.$sSection.'-title'), count($aMessages)) .'">'.$this->_aIco[$sSection].'</span>';
                    }
                }
            }
            $sWebapp = $aEntries["result"]["website"];
            $sTilekey = 'result-' . (999 - $aEntries["result"]["result"]) . '-' . $sWebapp.$sAppId;
            $sDivId=$this->_getDivIdForApp($sAppId);    
            $sAppLabel=str_replace('.', '.&shy;', $this->_getAppLabel($sAppId));
            $sOnclick='setTab(\''.$sDivId.'\')';
            $sAHref='<a href="'.$sDivId.'" onclick="'.$sOnclick.'">';
            
            $aTags=isset($aEntries["meta"]["tags"]) ? $aEntries["meta"]["tags"] : false;
            
            
            // $sOut = '<div class="divhost result' . $aEntries["result"]["result"] . ' tags '.$this->_getCssclassForTag($aTags).'">'
            $sOut = ''
                    . '<div class="col-md-3 col-sm-6 col-xs-12 divhost tags '.$this->_getCssclassForTag($aTags).'">'
                    . ($bHasData 
                        ? 
                            $oA->getWidget(array(
                                'onclick'=>$sOnclick,
                                'bgcolor'=>$this->_getAdminLteColorByResult($aEntries["result"]["result"]),
                                'icon' => $this->_getIconClass($this->_aIco['webapp']),
                                'number' => $aEntries['result']['summary']['total']
                                                . ($aEntries["result"]["result"] === RESULT_OK ? '' : ' '.$this->_renderBadgesForWebsite($sAppId, true)),
                                'text' => $sAppLabel.'<br>',
                                'number' => ($aEntries["result"]["result"] === RESULT_OK ? '' : ' '.$this->_renderBadgesForWebsite($sAppId, true))
                                            . $aEntries['result']['summary']['total'],
                                'progressvalue' => false,
                                'progresstext' => '&nbsp;&nbsp;' 
                                    . $sValidatorinfo
                                    .($aTags ? $this->_getTaglist($aTags) : '')
                                    ,
                            ))
                        : 
                            $oA->getWidget(array(
                                'onclick'=>$sOnclick,
                                'bgcolor'=>$this->_getAdminLteColorByResult(RESULT_ERROR),
                                'icon' => $this->_getIconClass($this->_aIco['host']),
                                'number' => $this->_renderBadgesForWebsite($sAppId, true),
                                'text' => $sAppLabel.'<br>',
                                'progressvalue' => false,
                                'progresstext' => '&nbsp;&nbsp;' 
                                    .($aTags ? $this->_getTaglist($aTags) : '')
                                    . $sValidatorinfo
                                    ,
                            ))
                    )
                    . '</div>'
                    ;
            $aAllWebapps[$bHasData][$sTilekey] = $sOut;
        }
        foreach([false, true] as $sKey){
            if (isset($aAllWebapps[$sKey])) {
                ksort($aAllWebapps[$sKey]);
            }
        }
        return $aAllWebapps;
    }

    /**
     * return html code for a view list of websites with colored boxes based on site status
     * @return string
     */
    public function generateViewWeblist() {
        $sReturn = '';
        $oA=new renderadminlte();
        /**
         * @var string
         */
        $sTopHeadline=$oA->getSectionHead($this->_aIco["allwebapps"] . ' ' . $this->_tr('All-webapps-header'));
        $aAllWebapps = array();
        
        if (!count($this->_data)) {
            return $sTopHeadline
                . '<section class="content">'.
                    $oA->getSectionRow(
                        $oA->getSectionColumn(
                            $oA->getBox(
                                array(
                                    'text'=> $this->_showWelcomeMessage()
                                )
                            )
                        )
                    )
                .'</section>';
        }

        $sTileList=$this->_generateWebTiles();
        $aAllWebapps=$this->_generateWeblist();
        // echo '<pre>'.htmlentities(print_r($aHosts, 1)).'</pre>'; die();

        $sReturn='<p>'
                . $this->_tr('All-webapps-hint')
                . '</p>'
                . '<div id="divwebsfilter"></div><br>'
            .'<div id="divwebs">'
            ;
        $aMergedWebapps=[];
        foreach([false, true] as $sKey){
            if (isset($aAllWebapps[$sKey])){
                $aMergedWebapps=array_merge($aMergedWebapps, $aAllWebapps[$sKey]);
            }
        }
        if(isset($aAllWebapps))
        foreach ($aMergedWebapps as $aWebapp) {
            $sReturn .= $aWebapp;
        }
        $sReturn .= '</div>';

        return $sTopHeadline 
                
                . '<section class="content">
                    
                    '.$oA->getSectionRow($sTileList)
                    . '<br>'

                    . $oA->getSectionRow(
                        $oA->getSectionColumn(
                            $oA->getBox(array(
                                // 'label'=>'I am a label.',
                                // 'collapsable'=>true,
                                // 'collapsed'=>false,
                                // 'title'=>$this->_tr('Client-source-data'),
                                'title'=> strip_tags($sTopHeadline),
                                'text'=>$sReturn
                            )),
                            12
                        )
                    )
                .'
                </section>'
                ;
        // return $sReturn;
    }
    
    /**
     * returns a readable result by given integer; i.e. 0=OK, 1=unknown, ...
     * @return string
     */
    public function getResultValue($i) {
        return $this->_tr('Resulttype-' . $i);
    }

    /**
     * load monitoring data ... if not done yet
     * @return boolean
     */
    public function loadClientData(){
        if (!count($this->_data)) {
            $this->_getClientData();
        }
        return true;
    }


    /**
     * helper: get a name for the div of app data
     * it is used to build an url; the "-" will be used to parse the app id
     * 
     * @param type $sAppid
     * @return type
     */
    protected function _getDivIdForApp($sAppid) {
        return '#divweb-'.$sAppid;
    }
    
    /**
     * get a flat array of tags sent from all clients
     * @return array
     */
    protected function _getClientTags(){
        $aTags=array();
        foreach ($this->_data as $aEntries) {
            if (isset($aEntries['meta']['tags'])){
                foreach($aEntries['meta']['tags'] as $sTag){
                    $aTags[]=$sTag;
                }
            }
        }
        sort($aTags);
        $aTags = array_unique($aTags);
        return $aTags;
    }

    /**
     * get name for css class of a tag
     * 
     * @param string|array $sTag
     * @return type
     */
    protected function _getCssclassForTag($sTag){
        if(is_string($sTag)){
            return $this->_getCssclassForTag(array($sTag));
            // return 'tag-'.md5($sTag);
        }
        if(is_array($sTag) && count($sTag)){
            $sReturn='';
            foreach($sTag as $sSingletag){
                $sReturn.=($sReturn ? ' ' : '')
                    . 'tag-'.md5($sSingletag);
            }
            return $sReturn;
        }
        return false;
    }
    /**
     * get name for css class of a tag
     * 
     * @param string|array $aTags
     * @return type
     */
    protected function _getTaglist($aTags){
        if(is_array($aTags) && count($aTags)){
            $sReturn='';
            foreach($aTags as $sSingletag){
                $sReturn.=($sReturn ? ' ' : '')
                    . ' <a href="#" class="tag" title="'.$this->_tr('Tag-filter').': ' .$sSingletag.'" '
                        . 'onclick="setTag(\''.$sSingletag.'\'); return false;"'
                        . '>'.$this->_aIco['tag'] .' ' . $sSingletag.'</a>'
                        ;
            }
            return $sReturn;
        }
        return false;
    }

    /**
     * render the dropdown with all application tags 
     * 
     * @return string
     */
    protected function _renderTagfilter(){
        $sReturn='';
        $aTaglist=$this->_getClientTags();
        $sOptions='';
        foreach($aTaglist as $sTag){
            $sOptions.='<option value="'.$this->_getCssclassForTag($sTag).'">'.$sTag.'</option>';
            }
        if($sOptions){
            $sReturn='<div class="form-group"><label for="selecttag">'.$this->_aIco['filter'].' <span>'.$this->_tr('Tag-filter').'</label>'
                        . ' '
                        . '<select id="selecttag" onchange="setTagClass(this.value)">'
                        . '<option value="">---</option>'
                        . $sOptions
                    . '</select></div>';
        }
        return $sReturn;
    }

    /**
     * render a single menu item for the top navigation
     * 
     * @param string $sHref   href atribute
     * @param string $sclass  css class of a tag
     * @param string $sIcon   icon of clickable label
     * @param string $sLabel  label of the link (and title as well)
     * @return string
     */
    protected function _renderMenuItem($sHref, $sclass, $sIcon, $sLabel){
        return '<li><a href="' . $sHref . '" class="'.$sclass.'" title="'.strip_tags($sLabel).'">' . $this->_aIco[$sIcon] . '<span>&nbsp; '.$sLabel.'</span></a></li>';
    }


    /**
     * get html code for chartjs graph
     * 
     * @staticvar int $iCounter
     * 
     * @param array $aOptions
     *                  - type   (string)  one of bar|pie|...
     *                  - xValue (bool)    flag: show grif on x axis
     *                  - yValue (bool)    flag: show grif on y axis
     *                  - xLabel (string)  label x-axis 
     *                  - yLabel (string)  label y-axis
     *                  - xValue (bool)    flag: show x values on axis
     *                  - yValue (bool)    flag: show y values on axis
     *                  - data   (array)   data items
     *                       - label  (string)
     *                       - value  (float)
     *                       - color  (integer)  RESULT_CODE
     * @return string
     */
    protected function _renderGraph($aOptions=array()){
        static $iCounter;
        if(!isset($iCounter)){
            $iCounter=0;
        }
        $iCounter++;
        $bIsPie=($aOptions['type']==='pie');
        $aOptions['xLabel']=isset($aOptions['xLabel']) ? $aOptions['xLabel'] : '';
        $aOptions['yLabel']=isset($aOptions['yLabel']) ? $aOptions['yLabel'] : '';
        $aOptions['xValue']=isset($aOptions['xValue']) ? $aOptions['xValue'] : ($bIsPie ? false : true);
        $aOptions['yValue']=isset($aOptions['yValue']) ? $aOptions['yValue'] : ($bIsPie ? false : true);
        $aOptions['xGrid']=isset($aOptions['xGrid']) ? $aOptions['xGrid'] : ($bIsPie ? false : true);
        $aOptions['yGrid']=isset($aOptions['yGrid']) ? $aOptions['yGrid'] : ($bIsPie ? false : true);
        
        $sIdCanvas='canvasChartJs'.$iCounter;
        $sCtx='ctxChartJsRg'.$iCounter;
        $sConfig='configChartJsRg'.$iCounter;

        $sScale=",scales: {
                            xAxes: [{
                                display: ".($aOptions['xGrid']||$aOptions['xLabel']||$aOptions['xValue'] ? 'true' : 'false').",
                                gridLines: { ".($aOptions['xGrid'] ? 'display: true, drawOnChartArea: true' : 'display: false, drawBorder: false ')." },
                                ".(!$aOptions['xValue'] ? 'ticks: { callback: function(dataLabel, index) { return \'\' } },' : '')."
                                scaleLabel: {
                                    display: ".($aOptions['xLabel'] ? 'true':'false') .",
                                    labelString: '".$aOptions['xLabel']."'
                                }
                            }],
                            yAxes: [{
                                display: ".($aOptions['yGrid']||$aOptions['yLabel']||$aOptions['yValue'] ? 'true' : 'false').",
                                gridLines: { ".($aOptions['yGrid'] ? 'display: true, drawOnChartArea: true' : 'display: false, drawBorder: false ')." },
                                ".(!$aOptions['yValue'] ? 'ticks: { callback: function(dataLabel, index) { return \'\' } },' : '')."
                                scaleLabel: {
                                    display: ".($aOptions['yLabel'] ? 'true':'false') .",
                                    labelString: '".$aOptions['yLabel']."'
                                },
                                ticks: {
                                    beginAtZero: true
                                }
                            }]
                        }"
                ;
        
                        
        
        $sHtml = '<div class="graph">'
                . '<canvas id="'.$sIdCanvas.'"></canvas>'
            . '</div><div style="clear: both;"></div>'
            . "<script>
                var ".$sConfig." = {
                    type: '".$aOptions['type']."',
                    data: {
                        "
                        .(isset($aOptions['data']['label']) ? "labels: ". json_encode(array_values($aOptions['data']['label'])).", ": "")
                        ."
                        datasets: [{
                                label: '".$aOptions['yLabel']."',
                                backgroundColor: ". json_encode(array_values($aOptions['data']['color'])).",
                                borderColor: ". json_encode(array_values($aOptions['data']['color'])).",
                                data: ". json_encode(array_values($aOptions['data']['value'])).",
                                pointRadius: 0,
                                fill: false
                        }]
                    },
                    options: {
                        responsive: true,
                        elements: {
                            line: {
                                tension: 0, // disables bezier curves
                            }
                        },
                        animation: {
                            duration: 200, // general animation time [ms]
                        },
                        responsiveAnimationDuration: 0, // animation duration after a resize                                            
                        title: {
                            display: false,
                            text: 'Line Chart'
                        },
                        legend: {
                            display: false,
                            text: 'Line Chart'
                        },
                        tooltips: {
                            mode: 'index',
                            intersect: false,
                        },
                        hover: {
                            animationDuration: 0, // duration of animations when hovering an item
                            mode: 'nearest',
                            intersect: true
                        }

                        $sScale
                    }
            };

            var ".$sCtx." = document.getElementById('".$sIdCanvas."').getContext('2d');
            window.myLine = new Chart(".$sCtx.", ".$sConfig.");
        </script>";
        // --- /chart
        return ''
            // .'<pre>'.htmlentities($sHtml).'</pre>'
            .$sHtml
        ;
    }

    /**
     * render html output of monitoring output (whole page)
     * @return string
     */
    public function renderHtml() {
        require_once 'cdnorlocal.class.php';
        $oCdn = new axelhahn\cdnorlocal();
    
        $oCdn = new axelhahn\cdnorlocal(array(
            'vendordir'=>__DIR__ . '/../vendor', 
            'vendorurl'=>'./vendor/', 
            'debug'=>0
        ));
        $oCdn->setLibs(array(
            "admin-lte/2.4.10",
            "datatables/1.10.21",
            "font-awesome/5.15.4",
            "jquery/3.6.0",
            "twitter-bootstrap/3.4.1",
            "Chart.js/2.7.2",
            "vis/4.21.0",
            // "x-editable/1.5.0",
        ));
        $oA=new renderadminlte();

        $this->loadClientData(); // required to show tags
        $sHtml = '. . .';
        $sNavi = '';
        $sTitle = $this->_sTitle.' v'.$this->_sVersion;

        $iReload = ((isset($this->_aCfg['pagereload']) && (int) $this->_aCfg['pagereload'] ) ? (int) $this->_aCfg['pagereload'] : 0);
        
        $sNavi .= $this->_renderMenuItem('#divwebs',          'allwebapps', 'allwebapps',    $this->_tr('All-webapps'))
                . $this->_renderMenuItem('#divproblems',      'problems',   'problems',      $this->_tr('Problems'))
                . $this->_renderMenuItem('#divnotifications', 'checks',     'notifications', $this->_tr('Notifications'))
                . $this->_renderMenuItem('#divsetup',         'setup',      'setup',         $this->_tr('Setup'))
                . $this->_renderMenuItem('#divabout',         'about',      'about',         $this->_tr('About'))
                . ($this->_aCfg['debug']
                    ? $this->_renderMenuItem('#divdebug',     'debug',      'debug',         $this->_tr('Debug'))
                    : ''
                )
            
                .'<li>'
                    . '<br><br><a href="#" class="reload" onclick="showDiv(); return false;"'
                    . ($iReload ? ' title="' . sprintf($this->_tr('Reload-every'), $iReload) . '"' : '')
                    . '>'
                    . $this->_aIco["reload"] 
                    . ' '
                    . '<span id="counter" style="display: inline-block; width: 2.5em;"></span>'
                    . '<span>' 
                        . $this->_tr('Reload') 
                        // . ' ('.$this->_tr('age-of-page') . ': <span class="timer-age-in-sec">0</span> s)'
                    . ' </span>'
                . '</a></li>'
                // . '</nav>'
                ;

        $sTheme = ( array_key_exists('theme', $this->_aCfg) && $this->_aCfg['theme'] ) ? $this->_aCfg['theme'] : 'default';
                

        $aReplace=array();

        // colorset and layout of adminlte
        $aReplace['{{PAGE_SKIN}}']=isset($this->_aCfg['skin']) ? $this->_aCfg['skin'] : 'skin-purple';
        $aReplace['{{PAGE_LAYOUT}}']=isset($this->_aCfg['layout']) ? $this->_aCfg['layout'] : 'sidebar-mini';
        
        // $aReplace['{{PAGE_HEADER}}']=$oA->getSectionHead($this->_aIco['title'] . ' ' . $sTitle);
        $aReplace['{{PAGE_HEADER}}']='';
        $aReplace['{{TOP_TITLE_MINI}}']='<b>A</b>M';
        $aReplace['{{TOP_TITLE}}']='<b>App</b>Monitor <span>v'.$this->_sVersion.'</span>';
        $aReplace['{{NAVI_TOP_RIGHT}}']='<li><span class="tagfilter">'.$this->_renderTagfilter().'</span></li>';
        $aReplace['{{NAVI_LEFT}}']=$sNavi;
        $aReplace['{{PAGE_BODY}}']=''
                .'<div class="outsegment" id="content">'
                    . '' . $sHtml . "\n"
                    . '</div>'
                .'<div class="divlog">' . $this->_renderLogs() . '</div>'
                ;
        
        $aReplace['{{PAGE_FOOTER_LEFT}}']='<a href="' . $this->_sProjectUrl . '" target="_blank">' . $this->_sProjectUrl . '</a>';
        $aReplace['{{PAGE_FOOTER_RIGHT}}']=''
                .'<script>'
                    . 'var iReload=' . $iReload . '; // reload time in server config is '.$iReload." s\n"
                    . '$(document).ready(function() {'
                        . 'initGuiStuff();'
                    . '} );'."\n"
                . '</script>' . "\n"
                ;
        
        $sHtml = '<!DOCTYPE html>' . "\n"
                . '<html>' . "\n"
                . '<head>' . "\n"
                . '<title>' . $sTitle . '</title>'
                . '<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>'
                . '<meta http-equiv="refresh" content="3600">'
                
                // jQuery
                . '<script src="' . $oCdn->getFullUrl($oCdn->getLibRelpath('jquery')."/jquery.min.js") . '"></script>' . "\n"
                
                // datatables
                . '<script src="' . $oCdn->getFullUrl($oCdn->getLibRelpath('datatables')."/js/jquery.dataTables.min.js") . '"></script>' . "\n"
                . '<link rel="stylesheet" href="' . $oCdn->getFullUrl($oCdn->getLibRelpath('datatables')."/css/jquery.dataTables.min.css") . '">' . "\n"

                // Admin LTE
                . '<script src="' . $oCdn->getFullUrl($oCdn->getLibRelpath('admin-lte')."/js/adminlte.min.js") . '" type="text/javascript"></script>' . "\n"
                . '<link rel="stylesheet" href="' . $oCdn->getFullUrl($oCdn->getLibRelpath('admin-lte')."/css/AdminLTE.min.css") . '">' . "\n"
                . '<link rel="stylesheet" href="' . $oCdn->getFullUrl($oCdn->getLibRelpath('admin-lte')."/css/skins/_all-skins.min.css") . '">' . "\n"

                // Bootstrap    
                . '<link href="' . $oCdn->getFullUrl($oCdn->getLibRelpath('twitter-bootstrap').'/css/bootstrap.min.css') . '" rel="stylesheet">'
                // . '<link href="' . $oCdn->getFullUrl($oCdn->getLibRelpath('twitter-bootstrap').'/css/bootstrap-theme.min.css') . '" rel="stylesheet">'
                . '<script src="' . $oCdn->getFullUrl($oCdn->getLibRelpath('twitter-bootstrap').'/js/bootstrap.min.js') . '" type="text/javascript"></script>'
                
                // x-editable
                // . '<link href="' . $oCdn->getFullUrl($oCdn->getLibRelpath('x-editable').'/bootstrap3-editable/css/bootstrap-editable.css') . '" rel="stylesheet">'
                // . '<script src="' . $oCdn->getFullUrl($oCdn->getLibRelpath('x-editable').'/bootstrap3-editable/js/bootstrap-editable.min.js') . '" type="text/javascript"></script>'
                
                // Font awesome
                . '<link href="' . $oCdn->getFullUrl($oCdn->getLibRelpath('font-awesome').'/css/all.min.css') . '" rel="stylesheet">'

                // Chart.js
                . '<script src="' . $oCdn->getFullUrl($oCdn->getLibRelpath('Chart.js').'/Chart.min.js') . '" type="text/javascript"></script>'

                // @since v0.99: vis (visjs.org)
                . '<script src="' . $oCdn->getFullUrl($oCdn->getLibRelpath('vis').'/vis.min.js') . '" type="text/javascript"></script>'
                . '<link href="'  . $oCdn->getFullUrl($oCdn->getLibRelpath('vis').'/vis-network.min.css') . '" rel="stylesheet">'
        

                . '<script src="javascript/visjs-network.class.js"></script>'
                . '<script src="javascript/functions.js"></script>'

                . '<link href="themes/' . $sTheme . '/screen.css" rel="stylesheet"/>'
                
                . '</head>' . "\n"
                . str_replace(
                        array_keys($aReplace),
                        array_values($aReplace),
                        file_get_contents(__DIR__ . '/layout-html.tpl')
                  )


                // . '</body></html>'
                ;

        return $sHtml;
    }

}
