<?php

require_once 'appmonitor-server.class.php';
require_once 'render-adminlte.class.php';

/**
 * APPMONITOR SERVER<br>
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
 * TODO:
 * - GUI uses cached data only
 * --------------------------------------------------------------------------------<br>
 * @version 0.66
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
    var $_sVersion = "0.69";

    /**
     * html code for icons in the web gui
     * https://fontawesome.com/v4.7.0/icons/
     * 
     * @var array
     */
    protected $_aIco = array(
        'title' => '<i class="fa fa-th"></i>',
        'welcome' => '<i class="fa fa-flag-o" style="font-size: 500%;float: left; margin: 0 1em 10em 0;"></i>',
        'reload' => '<i class="fa fa-refresh"></i>',
        'allwebapps' => '<i class="fa fa-globe"></i>',
        'webapp' => '<i class="fa fa-cube"></i>',
        'host' => '<i class="fa fa-hdd-o"></i>',
        'check' => '<i class="fa fa-check"></i>',
        'checks' => '<i class="fa fa-list"></i>',
        'notifications' => '<i class="fa fa-bell-o"></i>',
        'setup' => '<i class="fa fa-wrench"></i>',
        'about' => '<i class="fa fa-info-circle"></i>',
        'notify-email' => '<i class="fa fa-envelope-o"></i>',
        'notify-slack' => '<i class="fa fa-slack"></i>',
        'sleepmode-on' => '<i class="fa fa-bed"></i>',
        'sleepmode-off' => '<i class="fa fa-bullhorn"></i>',
        'filter' => '<i class="fa fa-filter"></i>',
        'age' => '<i class="fa fa-clock-o"></i>',
        'time' => '<i class="fa fa-clock-o"></i>',
        'tag' => '<i class="fa fa-tag"></i>',
        'debug' => '<i class="fa fa-bug"></i>',
        'ok' => '<i class="fa fa-check"></i>',
        'info' => '<i class="fa fa-info"></i>',
        'warning' => '<i class="fa fa-warning"></i>',
        'error' => '<i class="fa fa-flash"></i>',
        'add' => '<i class="fa fa-plus"></i>',
        'del' => '<i class="fa fa-minus"></i>',
        'plus' => '<i class="fa fa-plus"></i>',
        'close' => '<i class="fa fa-times"></i>',
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
        if (count($this->_aMessages)) {
            foreach ($this->_aMessages as $aLogentry) {
                $sOut .= '<div class="divlog' . $aLogentry["level"] . '">'
                        . $this->_aIco[$aLogentry["level"]] . ' '
                        . $aLogentry["message"]
                        . ' (' . $aLogentry["level"] . ')'
                        . '</div>';
            }
        }
        if ($sOut) {
            $sOut = '<div id="divmodal"><div class="divdialog">'
                    . $sOut
                    . '<br><a href="#" class="btn " onclick="location.href=\'?\';">' . $this->_aIco["close"] . ' ' . $this->_tr('btn-close') . '</a><br><br>'
                    . '</a></div>';
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

    
    protected function _getAdminLteColorByResult($iResult, $sDefault='') {
        $aAdminLteColorMapping=array(
            RESULT_ERROR=>'red',
            RESULT_WARNING=>'yellow',
            RESULT_UNKNOWN=>'',
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
            RESULT_UNKNOWN=>'',
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
     *                          - color   string   for adminlte; can be false if result is given
     *                          - icon    string   valid $this->_aIco[] or false to use result
     *                          - label   string   text
     *                          - count   integer  counter value
     *                          - more    string   value for bottom line
     *                          - result  string   check result
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
                    'color'=>$this->_getAdminLteColorByResult($aOptions['result'], $aOptions['color']),
                    'icon' => $this->_getIconClass($aOptions['icon'], $aOptions['result']),
                    'number' => $aOptions['count'],
                    'text' => $aOptions['label'],
                    'progressvalue' => isset($aOptions['progressvalue']) ? $aOptions['progressvalue'] : false,
                    'progresstext' => '&nbsp;&nbsp;'.$aOptions['more'],
                )).'</div>'
        ;
        return $sReturn;
        
        // --- OLD
        /*
        foreach (array('count', 'icon', 'label', 'more', 'result') as $sKey) {
            if (!isset($aOptions[$sKey])) {
                $aOptions[$sKey] = false;
            }
        }
        return '<div class="tile'
                . ($aOptions['result'] !== false ? ' result' . $aOptions['result'] : '' )
                . '">'
                . ($aOptions['icon'] ? '<span class="icon">' . $aOptions['icon'] . '</span>' : '' )
                . '<div class="count">' . $aOptions['count'] . '</div>'
                . ($aOptions['label'] ? '<div class="label">' . $aOptions['label'] . '</div>' : '' )
                . ($aOptions['more'] ? '<div class="more">' . $aOptions['more'] . '</div>' : '' )
                // . '<pre>'.print_r($aOptions, 1).'</pre>'
                . '</div>';
         * 
         */
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
                $iDelta=$iLastTimer-$aItem['timestamp'];
                $iLastTimer=$aItem['timestamp'];
                
                $aItem['duration']=$iDelta;
                
                $aReturn['items'][]=$aItem;
                $aReturn['counter'][$aItem['status']]+=$iDelta;
                $iTotal+=$iDelta;
            }
        }
        $aReturn['total']=$iTotal;
        // echo '<pre>' . print_r($aReturn, 1).'</pre>';
        // echo '<pre>' . print_r($aLog, 1).'</pre>';
        return $aReturn;
    }

    /**
     * get html code for tiles of a single webapp
     * 
     * @return string
     */
    protected function _generateChecksTile() {
        $sReturn = '';
        $aCounter = $this->_getCounter();
        // $sReturn.='<pre>'.print_r($aCounter, 1).'</pre>';

        $sMoreChecks = '';
        $iResultChecks=false;
        foreach ($this->_getResultDefs(true) as $i) {
            $sMoreChecks .= ($aCounter['checkresults'][$i] ? '<span class="badge result' . $i . '" title="'.$aCounter['checkresults'][$i] .' x '.$this->_tr('Resulttype-' . $i).'">' . $aCounter['checkresults'][$i] . '</span>' : '');
            if($aCounter['checkresults'][$i] && $iResultChecks===false){
                $iResultChecks=$i;
            }
        }
        return $this->_getTile(array(
                    'result' => $iResultChecks,
                    'count' => $aCounter['checks'],
                    'label' => $this->_aIco['check'] . ' ' . $this->_tr('Checks-total'),
                    'more' => $sMoreChecks
        ));
    }

    /**
     * get html code for tiles of a single webapp
     * 
     * @param string  $sAppId  webapp id
     * @return string
     */
    protected function _generateWebappTiles($sAppId) {
        $aHostdata = $this->_data[$sAppId]['result'];
        $this->oNotifcation->setApp($sAppId);
        $aLast = $this->oNotifcation->getAppLastResult();
        $sSince = $aLast && (int) $aLast['result']['ts'] ? $this->_tr('since') . ' ' . date("Y-m-d H:i", $aLast['result']['ts']) : '';
        $sReturn = '';
        // $sReturn.='<pre>'.print_r($aHostdata, 1).'</pre>';

        $sMoreChecks = '';
        if (isset($aHostdata['summary'])) {
            foreach ($this->_getResultDefs(true) as $i) {
                $sMoreChecks .= ($aHostdata['summary'][$i] ? '<span class="badge result' . $i . '" title="' . $aHostdata['summary'][$i] . ' x ' . $this->_tr('Resulttype-' . $i) . '">' . $aHostdata['summary'][$i] . '</span>' : '');
            }
        }
        $aEmailNotifiers = $this->oNotifcation->setApp($sAppId, $this->_data[$sAppId]);
        $aEmailNotifiers = $this->oNotifcation->getAppNotificationdata('email');
        $aSlackChannels = $this->oNotifcation->getAppNotificationdata('slack', 1);

        // $aPeople=array('email1@example.com', 'email2@example.com');
        $sMoreNotify = (count($aEmailNotifiers) ? '<span title="' . implode("\n", $aEmailNotifiers) . '">' . count($aEmailNotifiers) . ' x ' . $this->_aIco['notify-email'] . '</span> ' : '')
                // .'<pre>'.print_r($this->oNotifcation->getAppNotificationdata(), 1).'</pre>'
                . (count($aSlackChannels) ? '<span title="' . implode("\n", array_keys($aSlackChannels)) . '">' . count($aSlackChannels) . ' x ' . $this->_aIco['notify-slack'] . '</span> ' : '')
        ;
        $iNotifyTargets = count($aEmailNotifiers) + count($aSlackChannels);
        $sSleeping = $this->oNotifcation->isSleeptime();
        $sReturn .= ''
                . (isset($aHostdata['result']) ? $this->_getTile(array(
                    'result' => $aHostdata['result'],
                    'count' => $this->_tr('Resulttype-' . $aHostdata['result']),
                    'label' => $this->_tr('Appstatus'),
                    'more' => $sSince
                )) : '')
                . $this->_getTile(array(
                    'result' => ($aHostdata['httpstatus'] && $aHostdata['httpstatus'] >= 400) ? RESULT_ERROR : false,
                    'count' => $aHostdata['httpstatus'],
                    'label' => $this->_tr('Http-status'),
                ))
                . $this->_getTile(array(
                    'count' => '<span class="timer-age-in-sec">' . (time() - $aHostdata['ts']) . '</span>s',
                    'icon' => $this->_aIco['age'],
                    'label' => $this->_tr('age-of-result'),
                    'more' => $this->_tr('TTL') . '=' . $aHostdata['ttl'] . 's',
                ))
                . (isset($aHostdata['summary']['total']) ? $this->_getTile(array(
                    'count' => $aHostdata['summary']['total'],
                    'icon' => $this->_aIco['check'],
                    'label' => $this->_tr('Checks-on-webapp'),
                    'more' => $sMoreChecks
                )) : '')
                . (isset($this->_data[$sAppId]['meta']['time']) ? $this->_getTile(array(
                    'count' => $this->_data[$sAppId]['meta']['time'],
                    'icon' => $this->_aIco['time'],
                    'label' => $this->_tr('Time-for-all-checks'),
                )) : '')
                . $this->_getTile(array(
                    'result' => $iNotifyTargets ? false : RESULT_WARNING,
                    'count' => $iNotifyTargets,
                    'icon' => $this->_aIco['notifications'],
                    'label' => $this->_tr('Notifications'),
                    'more' => $sMoreNotify
                ))
                . $this->_getTile(array(
                    'result' => ($sSleeping ? RESULT_WARNING : false),
                    'icon' => ($sSleeping ? $this->_aIco['sleepmode-on'] : $this->_aIco['sleepmode-off']),
                    'label' => ($sSleeping ? $this->_tr('Sleepmode-on') : $this->_tr('Sleepmode-off')),
                    'more' => $sSleeping,
                ))
                . '<div style="clear: both;"></div>'
        ;
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

        $sMoreHosts = '';
        
        $iResultApps=false;
        foreach ($this->_getResultDefs(true) as $i) {
            $sMoreHosts .= ($aCounter['appresults'][$i] ? '<span class="badge result' . $i . '" title="' . $aCounter['appresults'][$i] . ' x ' . $this->_tr('Resulttype-' . $i) . '">'.$aCounter['appresults'][$i].'</span>' : '');
            if($aCounter['appresults'][$i] && $iResultApps===false){
                $iResultApps=$i;
            }
        }
        // $sReturn.='<pre>'.print_r($aCounter, 1).'</pre><br>$iResultApps = '.$iResultApps.'<br>$iResultChecks = '.$iResultChecks.'<br>';

        $sSleeping = $this->oNotifcation->isSleeptime();
        $sReturn .= ''
                . $this->_getTile(array(
                    'result' => $iResultApps,
                    'count' => $aCounter['apps'],
                    'icon' => $this->_aIco['webapp'],
                    'label' => $this->_tr('Webapps'),
                    'more' => $sMoreHosts
                ))
                . $this->_getTile(array(
                    'count' => $aCounter['hosts'],
                    'icon' => $this->_aIco['host'],
                    'label' => $this->_tr('Hosts'),
                ))
                . $this->_generateChecksTile()
                . $this->_getTile(array(
                    'result' => ($sSleeping ? RESULT_WARNING : false),
                    'icon' => ($sSleeping ? $this->_aIco['sleepmode-on'] : $this->_aIco['sleepmode-off']),
                    'label' => ($sSleeping ? $this->_tr('Sleepmode-on') : $this->_tr('Sleepmode-off')),
                    'more' => $sSleeping,
                ))
                // . '<div style="clear: both;"></div>'
        ;
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
            . '<a class="btn" href="#divsetup" onclick="setTab(this.hash);">' . $this->_aIco['setup'] . ' ' . $this->_tr('Setup') . '</a>'
        ;
    }


    /**
     * helper: generate html code with all checks.
     * if a hast is given it renders the data for this host only
     * 
     * @param  string  $sUrl  optional filter by url; default: all
     * @return string
     */
    protected function _generateMonitorTable($sUrl = false) {
        $sReturn = '';
        if (!count($this->_data)) {
            return $this->_showWelcomeMessage();
        }

        $sTableClass = $sUrl ? "datatable-hosts" : "datatable-checks";
        $sReturn .= $sUrl 
        ? $this->_generateTableHead(array(
            $this->_tr('Result'),
            // $this->_tr('TTL'),
            $this->_tr('Check'),
            $this->_tr('Description'),
            $this->_tr('Output'),
            $this->_tr('Time'),
        )) : $this->_generateTableHead(array(
            $this->_tr('Result'),
            $this->_tr('Timestamp'),
            $this->_tr('Host'),
            $this->_tr('Webapp'),
            $this->_tr('TTL'),
            $this->_tr('Check'),
            $this->_tr('Description'),
            $this->_tr('Output'),
            $this->_tr('Time'),
        ));
        $sReturn .= '<tbody>';

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
                    /*
                      $sReturn .= '<tr class="result3">'
                      . '<td>?</td>'
                      . '<td>?</td>'
                      . '<td>' . date("Y-m-d H:i:s", $aEntries["result"]["ts"]) . ' (' . (date("U") - $aEntries["result"]["ts"]) . '&nbsp;s)</td>'
                      . '<td>' . $aEntries["result"]["ttl"] . '</td>'
                      . '<td>' . $aEntries["result"]["url"] . '</td>'
                      . '<td>?</td>'
                      . '<td>?</td>'
                      . '<td>' . $aEntries["result"]["error"] . '</td>'
                      . '</tr>';
                     * 
                     */
                } else {

                    foreach ($aEntries["checks"] as $aCheck) {
                        $aTags=isset($aEntries["meta"]["tags"]) ? $aEntries["meta"]["tags"] : false;
                        
                        $sReturn .= '<tr class="result' . $aCheck["result"] . ' tags '.$this->_getCssclassForTag($aTags).'">'
                                ;
                        if (!$sUrl) {
                            $sReturn .= 
                                    '<td class="result result'.$aCheck["result"].'"><span style="display: none;">'.$aCheck['result'].'</span>' . $this->_tr('Resulttype-'.$aCheck["result"]).'</td>'
                                    . '<td>' . date("Y-m-d H:i:s", $aEntries["result"]["ts"]) . ' (<span class="timer-age-in-sec">' . (date("U") - $aEntries["result"]["ts"]) . '</span>&nbsp;s)</td>'
                                    . '<td>' . $aEntries["result"]["host"] . '</td>'
                                    . '<td>' . $aEntries["result"]["website"] . '</td>'
                                    . '<td>' . $aEntries["result"]["ttl"] . '</td>'
                                    ;
                        } else {
                            $sReturn .= '<td class="result result'.$aCheck["result"].'"><span style="display: none;">'.$aCheck['result'].'</span>' . $this->_tr('Resulttype-'.$aCheck["result"]).'</td>';
                        }
                        $sReturn .= // . '<td>' . date("H:i:s", $aEntries["meta"]["ts"]) . ' ' . $this->_hrTime(date("U") - $aEntries["meta"]["ts"]) . '</td>'
                                '<td>' . $aCheck["name"] . '</td>'
                                . '<td>' . $aCheck["description"] . '</td>'
                                . '<td>' . $aCheck["value"] . '</td>'
                                . '<td>' . (isset($aCheck["time"]) ? $aCheck["time"] : '-') . '</td>'
                                . '</tr>';
                    }
                }
            }
        }
        $sReturn .= '</tbody>';
        return '<table class="' . $sTableClass . '">' . $sReturn . '</table>';
    }

    /**
     * get html code for notification log page
     * 
     * @param array   $aLogs         array with logs; if false then all logs will be fetched
     * @param string  $sTableClass   custom classname for the datatable; for custom datatable settings (see functions.js)
     * @return string
     */
    protected function _generateNoftificationlog($aLogs=false, $sTableClass='datatable-notifications') {
        if($aLogs===false){
            $aLogs = $this->oNotifcation->getLogdata();
            rsort($aLogs);
        }
        if(!count($aLogs)){
            return $this->_tr('Notifications-none');
        }

        $sTable = $this->_generateTableHead(array(
                    $this->_tr('Result'),
                    $this->_tr('Timestamp'),
                    $this->_tr('Duration'),
                    $this->_tr('Change'),
                    $this->_tr('Message')
                )) . "\n";
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

            $aTags=isset($this->_data[$aLogentry['appid']]["meta"]["tags"]) ? $this->_data[$aLogentry['appid']]["meta"]["tags"] : false;
            $sTable .= '<tr class="result' . $aLogentry['status'] . ' tags '.$this->_getCssclassForTag($aTags).'">'
                    .'<td class="result' . $aLogentry['status'] . '"><span style="display: none;">'.$aLogentry['status'].'</span>' . $this->_tr('Resulttype-' . $aLogentry['status']) . '</td>'
                    . '<td>' . date("Y-m-d H:i:s", $aLogentry['timestamp']) . '</td>'
                    . '<td>' . round($iDelta/60) . ' min</td>'
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
     * get html code for setup page
     * @return string
     */
    protected function _generateSetup() {
        $sReturn = '';
        $sFormOpenTag = '<form action="?" method="POST">';
        $sReturn .= '<h3>' . $this->_tr('Setup-add-client') . '</h3>'
                . '<p>' . $this->_tr('Setup-add-client-pretext') . '</p>'
                . $sFormOpenTag
                . '<input type="hidden" name="action" value="addurl">'
                . '<input type="text" class="inputtext" name="url" size="70" value="" '
                . 'placeholder="http://[domain]/appmonitor/client/" '
                . 'pattern="http.*://..*" '
                . 'required="required" '
                . '>'
                // . '<a href="?#" class="btn btnadd" onclick="this.parentNode.submit(); return false;"><i class="fa fa-plus"></i> add</a>'
                . '<button class="btn btnadd">' . $this->_aIco['add'].' '.$this->_tr('btn-addUrl') . '</button>'
                . '</form><br>';
        $sReturn .= '<h3>' . $this->_tr('Setup-client-list') . '</h3>'
                . '<div id="divsetupfilter"></div><br>';
        
        foreach ($this->_data as $sAppId => $aData) {
            $iResult = array_key_exists("result", $aData["result"]) ? $aData["result"]["result"] : 3;
            $sUrl = $aData["result"]["url"];
            $sWebsite = array_key_exists("website", $aData["result"]) ? $aData["result"]["website"] : $this->_tr('unknown') . ' (' . $sUrl . ')';
            $sHost = array_key_exists("host", $aData["result"]) ? $aData["result"]["host"] : $this->_tr('unknown');

            $sIdDetails = 'setupdetail' . md5($sAppId);
            $aTags=isset($aData["meta"]["tags"]) ? $aData["meta"]["tags"] : false;
            $sReturn .= '<div class="divhost result' . $iResult . ' tags '.$this->_getCssclassForTag($aTags).'" style="float: none; ">'
                    . '<div style="float: right;">'
                    . $sFormOpenTag
                    . '<input type="hidden" name="action" value="deleteurl">'
                    . '<input type="hidden" name="url" value="' . $sUrl . '">'
                    . '<button class="btn btndel" '
                        . 'onclick="return confirm(\'' . sprintf($this->_tr('btn-deleteUrl-confirm'), $sUrl) . '\')" '
                        . '>' . $this->_aIco['del'].' '.$this->_tr('btn-deleteUrl') 
                    . '</button>'
                    //. '<a href="#" class="btn btndel"><i class="fa fa-minus"></i> delete</a>'
                    . '</form>'
                    . '</div>'
                    . '<button class="btn" onclick="$(\'#' . $sIdDetails . '\').toggle(); return false;">' . $this->_tr('btn-details') . '</button>'
                    . ' ' . $this->_aIco['webapp'] . ' ' . $this->_tr('Webapp') . ' '
                    . $sWebsite
                    . '... '
                    . $this->_aIco['host'] . ' ' . $this->_tr('Host') . ' ' . $sHost . ' '
                    . '<div id="' . $sIdDetails . '" style="display: none;">'
                    . $this->_tr('Url') . ' '
                    . '<a href="' . $sUrl . '" target="_blank">'
                    . $sUrl
                    . '</a><br>'
                    // . '<pre>'.($aData['result']['header'] ? $aData['result']['header'] : $aData['result']['error']).'</pre>'
                    // . '<pre>'.print_r($aData, 1).'</pre>'
                    . '</div>'
                    . '</div>';
        }
        return $sReturn;
    }

    /**
     * gt html code for badged list with errors, warnings, unknown, ok
     * @param string $sAppId  id of app to show
     * @param bool   $bShort  display type short (counter only) or long (with texts)
     * @return string|boolean
     */
    protected function _renderBadgesForWebsite($sAppId, $bShort = false) {
        $iResult = $this->_data[$sAppId]["result"]["result"];
        if (!array_key_exists("summary", $this->_data[$sAppId]["result"])) {
            return false;
        }
        $aEntries = $this->_data[$sAppId]["result"]["summary"];
        // $sHtml = $this->_tr('Result-checks') . ': <strong>' . $aEntries["total"] . '</strong> ';
        $sHtml = '';
        for ($i = 3; $i >= 0; $i--) {
            $sKey = $i;
            if ($aEntries[$sKey] > 0) {
                $sHtml .= '<span class="badge result' . $i . '" title="' . $aEntries[$sKey] . ' x ' . $this->getResultValue($i) . '">' . $aEntries[$sKey] . '</span>';
                if (!$bShort) {
                    $sHtml .= $this->_tr('Resulttype-' . $i) . ' ';
                }
            }
        }
        return $sHtml;
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
        $sShowHide='<br><button class="btn" id="btn-plus-'.$sAppId.'"  onclick="$(\'#'.$sDivMoredetails.'\').slideDown(); $(this).hide(); $(\'#btn-minus-'.$sAppId.'\').show(); return false;"'
                    . '> '.$this->_aIco['plus'].' '.$this->_tr('btn-details').' </button>'
                . '<button class="btn" id="btn-minus-'.$sAppId.'"  onclick="$(\'#'.$sDivMoredetails.'\').slideUp();   $(this).hide(); $(\'#btn-plus-'.$sAppId.'\').show(); return false;" style="display: none;"'
                . '> '.$this->_aIco['close'].' '.$this->_tr('btn-hide-details').' </button>';
        
        if (true ||
                array_key_exists("result", $aEntries) && array_key_exists("result", $aEntries["result"]) && array_key_exists("website", $aEntries["result"]) && array_key_exists("host", $aEntries["result"])
        ) {
            $sTopHeadline=$oA->getSectionHead(
                    '<a href="#divwebs" onclick="setTab(\'#divwebs\')"'
                        . '> ' . $this->_aIco['allwebapps'] . ' '. $this->_tr('All-webapps-header')
                    .'</a> > <nobr>'
                    . $this->_aIco['webapp'] .' '
                    . (isset($aEntries['result']['website']) ? $aEntries['result']['website'] : '?')
                    . '</nobr>'
                    );


                // --- validation of items in client data array
                $aValidatorResult=$this->_checkClientResponse($sAppId);
                if($aValidatorResult){
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

            // --- http status code and chart of response times
            $oResponsetime=new responsetimeRrd($sAppId);
            
            $aResponseTimeData=$oResponsetime->get(200);
            $aChartData=array(
                'label'=>array(),
                'value'=>array(),
                'color'=>array(),
            );
            foreach ($aResponseTimeData as $aItem){
                array_unshift($aChartData['label'], date("Y-m-d H:i:s", $aItem['timestamp']));
                array_unshift($aChartData['value'], $aItem['data']['time']);
                array_unshift($aChartData['color'], $this->_getAdminLteColorByResult($aItem['data']['status']));
                // array_unshift($aChartColor, $aColor[rand(0, 3)]);
            }
            
            $aChart=array(
                'type'=>'bar',
                'xLabel'=>$this->_tr('Chart-time'),
                'yLabel'=>$this->_tr('Chart-responsetime'),
                'data'=>$aChartData,
            );
            // echo '<pre>'.print_r($aChart, 1).'</pre>';
            
            $sHtml .= 
                    $oA->getSectionRow(
                        $oA->getSectionColumn(
                            $oA->getBox(array(
                                // 'label'=>'I am a label.',
                                // 'collapsable'=>true,
                                'title'=>$this->_tr('Http-details'),
                                'text'=> ($aEntries['result']['error'] ? '<div class="result'.RESULT_ERROR.'">' . $this->_tr('Error-message') . ': ' . $aEntries['result']['error'] . '</div><br>' : '')
                                    . ($aEntries['result']['url'] ? $this->_tr('Url') . ': <a href="' . $aEntries['result']['url'] . '" target="_blank">' . $aEntries['result']['url'] . '</a><br>' : '')
                                    . ($aEntries['result']['httpstatus'] ? $this->_tr('Http-status') . ': <strong>' . $aEntries['result']['httpstatus'] . '</strong><br>' : '')
                                    . ($aEntries['result']['header'] ? $this->_tr('Http-header') . ': <pre>' . $aEntries['result']['header'] . '</pre>' : '')
                            )),
                            4
                        )
                        .$oA->getSectionColumn(
                            $oA->getBox(array(
                                // 'label'=>'I am a label.',
                                // 'collapsable'=>true,
                                'title'=>$this->_tr('Chart-graph'),
                                'text'=>'<p>'.$this->_tr('Chart-graph-description').'</p>'
                                    . $this->_renderGraph($aChart)
                                    /*
                                    . $oResponsetime->renderGraph(array(
                                        'xLabel'=>$this->_tr('Chart-time'),
                                        'yLabel'=>$this->_tr('Chart-responsetime'),
                                        'iMax'=>200,
                                    ))
                                     * 
                                     */
                            )),
                            8
                        )
                    )

            ;

            // --- notifications for this webapp
            $aLogs = $this->oNotifcation->getLogdata(array('appid'=>$sAppId));
            rsort($aLogs);
            
            $aUptime=$this->_getUptime($aLogs);
            // echo '<pre>'.print_r($aUptime, 1).'</pre>';
            
            $aChartData=array(
                'label'=>array(),
                'value'=>array(),
                'color'=>array(),
            );
            foreach ($aUptime['counter'] as $iResult=>$iResultCount){
                array_unshift($aChartData['label'], $this->_tr('Resulttype-'.$iResult));
                array_unshift($aChartData['value'], $iResultCount);
                array_unshift($aChartData['color'], $this->_getAdminLteColorByResult($iResult));
                // array_unshift($aChartColor, $aColor[rand(0, 3)]);
            }
            
            $aChartUptime=array(
                'type'=>'pie',
                // 'xLabel'=>$this->_tr('Chart-time'),
                // 'yLabel'=>$this->_tr('Chart-responsetime'),
                'data'=>$aChartData,
            );
            $iFirstentry=count($aLogs)>1 ? $aLogs[count($aLogs)-1]['timestamp'] : date('U');
            $sUptime=($aUptime['total']
                    ? ''
                        . '<strong>'
                            . $this->_tr('Resulttype-'.RESULT_OK) . ': ' . round($aUptime['counter'][RESULT_OK]*100 / $aUptime['total'], 3).' %'
                        . '</strong><br>'
                        . ($aUptime['counter'][RESULT_UNKNOWN] 
                            ? $this->_tr('Resulttype-'.RESULT_UNKNOWN) . ': ' . round($aUptime['counter'][RESULT_UNKNOWN]*100 / $aUptime['total'], 3).' %; '
                                . ' ('.round($aUptime['counter'][RESULT_UNKNOWN]/60).' min)<br>'
                            : ''
                          )
                        . ($aUptime['counter'][RESULT_WARNING] 
                            ? $this->_tr('Resulttype-'.RESULT_WARNING) . ': ' . round($aUptime['counter'][RESULT_WARNING]*100 / $aUptime['total'], 3).' %; '
                                . ' ('.round($aUptime['counter'][RESULT_WARNING]/60).' min)<br>'
                            : ''
                          )
                        . ($aUptime['counter'][RESULT_ERROR]
                            ? $this->_tr('Resulttype-'.RESULT_ERROR) . ': ' . round($aUptime['counter'][RESULT_ERROR]*100 / $aUptime['total'], 3).' %'
                                . ' ('.round($aUptime['counter'][RESULT_ERROR]/60).' min)<br>'
                            : ''
                        )
                    : '-'
                )
                .$this->_renderGraph($aChartUptime)
                ;
            
            $sHtml .= $oA->getSectionRow(
                        $oA->getSectionColumn(
                            $oA->getBox(array(
                                // 'label'=>'I am a label.',
                                // 'collapsable'=>true,
                                'title'=>$this->_tr('Uptime') . ' ('.$this->_tr('since').' '.date('Y-m-d', $iFirstentry).'; ~'.round((date('U')-$iFirstentry)/60/60/24).' d)',
                                'text'=> $sUptime
                            )),
                            4
                        )
                        .$oA->getSectionColumn(
                            $oA->getBox(array(
                                // 'label'=>'I am a label.',
                                // 'collapsable'=>true,
                                'title'=>$this->_tr('Notifications'),
                                'text'=> $this->_generateNoftificationlog($aLogs, 'datatable-notifications-webapp')
                            )),
                            8
                        )
                    );


            // --- debug infos 
            if ($this->_aCfg['debug']) {
                $this->oNotifcation->setApp($sAppId);
                $sDebugContent='';

                /*
                $sDebugContent .= 
                         '<h3>' . $this->_tr('Preview-of-messages') . '</h3>'
                        . '<h4>' . $this->_tr('Preview-replacements') . '</h4>'
                        . '<pre>' . htmlentities(print_r($this->oNotifcation->getMessageReplacements(), 1)) . '</pre>'
                        . '<h4>' . $this->_tr('Preview-emails') . '</h4>'
                ;
                 * 
                 */
                foreach ($this->_getResultDefs() as $i) {
                    $sMgIdPrefix = 'changetype-' . $i;
                    $sDebugContent .= $this->_tr('changetype-' . $i)
                            . '<pre>'
                            . '' . htmlentities(print_r($this->oNotifcation->getReplacedMessage($sMgIdPrefix . '.logmessage'), 1)) . '<hr>'
                            . 'TO: ' . implode('; ', $this->oNotifcation->getAppNotificationdata('email')) . '<br>'
                            . '<strong>' . htmlentities(print_r($this->oNotifcation->getReplacedMessage($sMgIdPrefix . '.email.subject'), 1)) . '</strong><br>'
                            . '' . htmlentities(print_r($this->oNotifcation->getReplacedMessage($sMgIdPrefix . '.email.message'), 1)) . '<br>'
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
                                'text'=>'<pre>' . htmlentities(print_r($this->oNotifcation->getMessageReplacements(), 1)) . '</pre>'
                            
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
        $sHtml=''
            . '<h3>' . $this->_tr('Debug-config') . '</h3>'
            . '<pre>' . print_r($this->_aCfg, true) . '</pre>'
            . '<h3>' . $this->_tr('Debug-urls') . '</h3>'
            . '<pre>' . print_r($this->_urls, true) . '</pre>'
                . '<h3>' . $this->_tr('Debug-clientdata') . '</h3>'
            . '<pre>' . print_r($this->_data, true) . '</pre>'
            // . '<h3>' . $this->_tr('Debug-notificationlog') . '</h3>'
            // . '<pre>' . print_r($this->oNotifcation->getLogdata(), true) . '</pre>'
            . '</div>'
        ;
        // return $sHtml;
        return $oA->getSectionHead($this->_aIco["debug"] . ' ' . $this->_tr('Debug'))
                . '<section class="content">'
                . $oA->getSectionRow($oA->getSectionColumn(
                        $oA->getBox(array(
                            'title'=>$this->_tr('Debug'),
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
    public function generateViewNotifications() {
        $oA=new renderadminlte();
        $sHtml=''
                // . '<h2>' . $this->_aIco["notifications"] . ' ' . $this->_tr('Notifications-header') . '</h2>'
                . $this->_generateNoftificationlog()
                ;
        // return $sHtml;
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
     * return html code for setup page
     * @return string
     */
    public function generateViewSetup() {
        $oA=new renderadminlte();
        return $oA->getSectionHead($this->_aIco["setup"] . ' ' . $this->_tr('Setup'))
                . '<section class="content">'
                . $oA->getSectionRow($oA->getSectionColumn(
                        $oA->getBox(array(
                            'title'=>$this->_tr('Setup'),
                            'text'=>'<div id="divsetup">'
                                . $this->_generateSetup()
                                . '</div>'
                        )),
                        12
                    )).'
                </section>'
                ;
    }

    /**
     * return html code for a view list of websites with colored boxes based on site status
     * @return string
     */
    public function generateViewWeblist() {
        $sReturn = '';
        $oA=new renderadminlte();
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
        foreach ($this->_data as $sAppId => $aEntries) {
            $bHasData = true;
            if (!isset($aEntries["result"]["host"])) {
                $bHasData = false;
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
            $sAppLabel=str_replace('.', '.&shy;', $sWebapp);
            $sAHref='<a href="'.$sDivId.'" onclick="setTab(\''.$sDivId.'\')">';
            
            $aTags=isset($aEntries["meta"]["tags"]) ? $aEntries["meta"]["tags"] : false;
            
            
            // $sOut = '<div class="divhost result' . $aEntries["result"]["result"] . ' tags '.$this->_getCssclassForTag($aTags).'">'
            $sOut = ''
                    . '<div class="col-md-3 col-sm-6 col-xs-12 divhost tags '.$this->_getCssclassForTag($aTags).'">'
                    . ($bHasData 
                        ? 
                            $oA->getWidget(array(
                                'bgcolor'=>$this->_getAdminLteColorByResult($aEntries["result"]["result"]),
                                'icon' => $this->_getIconClass($this->_aIco['host']),
                                'number' => $aEntries['result']['summary']['total'],
                                'text' => $sAHref. $sAppLabel.'</a><br>',
                                'progressvalue' => false,
                                'progresstext' => '&nbsp;&nbsp;' 
                                    .$this->_renderBadgesForWebsite($sAppId, true) 
                                    . $sValidatorinfo
                                    .($aTags ? $this->_getTaglist($aTags) : '')
                                    ,
                            ))
                        : 
                            $oA->getWidget(array(
                                'bgcolor'=>$this->_getAdminLteColorByResult(RESULT_ERROR),
                                'icon' => $this->_getIconClass($this->_aIco['host']),
                                'number' => false,
                                'text' => $sAHref. $sAppLabel.'</a><br>',
                                'progressvalue' => false,
                                'progresstext' => '&nbsp;&nbsp;' 
                                    .($aTags ? $this->_getTaglist($aTags) : '')
                                    .$this->_renderBadgesForWebsite($sAppId, true) 
                                    . $sValidatorinfo
                                    ,
                            ))
                    )
                    . '</div>'
                    ;
            $aAllWebapps[$sTilekey] = $sOut;
        }
        ksort($aAllWebapps);
        // echo '<pre>'.htmlentities(print_r($aHosts, 1)).'</pre>'; die();

        $sReturn='<p>'
                . $this->_tr('All-webapps-hint')
                . '</p>'
                . '<div id="divwebsfilter"></div><br>'
            .'<div id="divwebs">'
            ;
        foreach ($aAllWebapps as $aWebapp) {
            $sReturn .= $aWebapp;
        }
        $sReturn .= '</div>';
        // return '<div id="divwebs">'.$sReturn . '</div>';
        return $sTopHeadline 
                
                . '<section class="content">
                    
                    '.$oA->getSectionRow($sTileList)

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
                    . ' <a href="#" class="tag" title="'.$this->_tr('Tag-filter').': ' .$sSingletag.'" onclick="setTag(\''.$sSingletag.'\'); return false;">'.$this->_aIco['tag'] .' ' . $sSingletag.'</a>';
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
            /*
            $sReturn=$this->_aIco['filter'].' <span>'.$this->_tr('Tag-filter').': </span><select id="selecttag" onchange="setTagClass(this.value)">'
                    . '<option value="">---</option>'
                    . $sOptions
                    . '</select>';
             * 
             */
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
        // return '<a href="' . $sHref . '" class="'.$sclass.'" title="'.$sLabel.'">' . $this->_aIco[$sIcon] . '<span> '.$sLabel.'</span></a>';
        return '<li><a href="' . $sHref . '" class="'.$sclass.'" title="'.strip_tags($sLabel).'">' . $this->_aIco[$sIcon] . '<span> '.$sLabel.'</span></a></li>';
    }


    /**
     * get html code for chartjs graph
     * 
     * @staticvar int $iCounter
     * 
     * @param array $aOptions
     *                  - type   (string)  one of bar|pie|...
     *                  - xLabel (string)  label x-axis 
     *                  - yLabel (string)  label y-axis
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
        
        $sXlabel=isset($aOptions['xLabel']) ? $aOptions['xLabel'] : '';
        $sYlabel=isset($aOptions['yLabel']) ? $aOptions['yLabel'] : '';
        
        $sIdCanvas='canvasChartJs'.$iCounter;
        $sCtx='ctxChartJsRg'.$iCounter;
        $sConfig='configChartJsRg'.$iCounter;

        $sScale=$sXlabel.$sYlabel
                ? ",scales: {
                            xAxes: [{
                                display: true,
                                scaleLabel: {
                                    display: true,
                                    labelString: '$sYlabel'
                                }
                            }],
                            yAxes: [{
                                display: true,
                                scaleLabel: {
                                    display: true,
                                    labelString: '$sYlabel'
                                }
                            }]
                        }"
                : ''
                ;
        
                        
        
        $sHtml = '<div class="graph">'
                . '<canvas id="'.$sIdCanvas.'"></canvas>'
            . '</div><div style="clear: both;"></div>'
            . "<script>
                var ".$sConfig." = {
                    type: '".$aOptions['type']."',
                    data: {
                        labels: ". json_encode(array_values($aOptions['data']['label'])).",
                        datasets: [{
                                label: 'Response time',
                                backgroundColor: ". json_encode(array_values($aOptions['data']['color'])).",
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
                            duration: 0, // general animation time
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
            // .htmlentities($sHtml)
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
            "admin-lte/2.4.8",
            "datatables/1.10.19",
            "font-awesome/4.7.0",
            "jquery/3.3.1",
            "twitter-bootstrap/3.3.7",
            "Chart.js/2.7.2",
            "jquery-sparklines/2.1.2",
        ));
        $oA=new renderadminlte();

        $this->loadClientData(); // required to show tags
        $sHtml = '. . .';
        $sNavi = '';
        $sTitle = $this->_sTitle.' v'.$this->_sVersion;

        $iReload = ((isset($this->_aCfg['pagereload']) && (int) $this->_aCfg['pagereload'] ) ? (int) $this->_aCfg['pagereload'] : 0);
        
        $sNavi .= $this->_renderMenuItem('#divwebs',          'allwebapps', 'allwebapps',    $this->_tr('All-webapps'))
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
        $aReplace['{{PAGE_FOOTER_RIGHT}}']='';
        
        $sHtml = '<!DOCTYPE html>' . "\n"
                . '<html>' . "\n"
                . '<head>' . "\n"
                . '<title>' . $sTitle . '</title>'
                
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
                
                // Font awesome
                . '<link href="' . $oCdn->getFullUrl('font-awesome/4.7.0/css/font-awesome.min.css') . '" rel="stylesheet">'

                // Chart.js
                . '<script src="' . $oCdn->getFullUrl($oCdn->getLibRelpath('Chart.js').'/Chart.min.js') . '" type="text/javascript"></script>'

                // jquery-sparklines
                // . '<script src="' . $oCdn->getFullUrl($oCdn->getLibRelpath('jquery-sparklines').'/jquery.sparkline.min.js') . '" type="text/javascript"></script>'

                // . $oCdn->getHtmlInclude("jquery/3.2.1/jquery.min.js")
                //. $oCdn->getHtmlInclude("datatables/1.10.16/js/jquery.dataTables.min.js")
                // . $oCdn->getHtmlInclude("datatables/1.10.16/css/jquery.dataTables.min.css")
                // . $oCdn->getHtmlInclude("font-awesome/4.7.0/css/font-awesome.css")
                // . $oCdn->getHtmlInclude("Chart.js/2.7.2/Chart.min.js")
                . '<script src="javascript/functions.js"></script>'
                
                . '<link href="themes/' . $sTheme . '/screen.css" rel="stylesheet"/>'
                
                . '</head>' . "\n"
                . str_replace(
                        array_keys($aReplace),
                        array_values($aReplace),
                        file_get_contents(__DIR__ . '/layout-html.tpl')
                  )
                
                /*
                . '<div class="divtop">'
                    . '<div class="divtopheader">'
                        . '<h1>' . $this->_aIco['title'] . ' ' . $sTitle . '</h1>'
                        . '<br>'
                    . '</div>'
                    . '<div class="divtopnavi">'
                        . $sNavi
                    . '</div>'
                . '</div>'
                
                . '<div class="divlog">' . $this->_renderLogs() . '</div>'
                . '<div class="divmain">'
                    . '<div class="outsegment" id="content">'
                    . '' . $sHtml . "\n"
                    . '</div>'
                    . '<div style="clear: both;"></div>'
                . '</div>'
                . '<div class="footer"><a href="' . $this->_sProjectUrl . '" target="_blank">' . $this->_sProjectUrl . '</a></div>'
                */
                . '<script>'
                    . 'var iReload=' . $iReload . '; // reload time in server config is '.$iReload." s\n"
                    . '$(document).ready(function() {'
                        . 'initGuiStuff();'
                    . '} );'."\n"
                    //. ($iReload ? 'window.setTimeout("updateContent()",   ' . ($iReload * 1000) . ');' : '')
                . '</script>' . "\n"
                . '</body></html>';

        return $sHtml;
    }

}
