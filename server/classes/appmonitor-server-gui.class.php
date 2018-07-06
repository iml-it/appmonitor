<?php

require_once 'appmonitor-server.class.php';

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
 * @version 0.36
 * @author Axel Hahn
 * @link TODO
 * @license GPL
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL 3.0
 * @package IML-Appmonitor
 */
class appmonitorserver_gui extends appmonitorserver {

    var $_sProjectUrl = "https://github.com/iml-it/appmonitor";
    var $_sTitle = "Appmonitor Server v0.36";

    /**
     * html code for icons in the web gui
     * https://fontawesome.com/v4.7.0/icons/
     * 
     * @var array
     */
    private $_aIco = array(
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
        'notify-email' => '<i class="fa fa-envelope-o"></i>',
        'notify-slack' => '<i class="fa fa-slack"></i>',
        'sleepmode-on' => '<i class="fa fa-bed"></i>',
        'sleepmode-off' => '<i class="fa fa-bullhorn"></i>',
        'filter' => '<i class="fa fa-filter"></i>',
        'age' => '<i class="fa fa-clock-o"></i>',
        'time' => '<i class="fa fa-clock-o"></i>',
        'httpstatus' => '<i class="fa fa-tag"></i>',
        'debug' => '<i class="fa fa-bug"></i>',
        'ok' => '<i class="fa fa-check"></i>',
        'info' => '<i class="fa fa-info"></i>',
        'warning' => '<i class="fa fa-warning"></i>',
        'error' => '<i class="fa fa-flash"></i>',
        'add' => '',
        'del' => '',
        'plus' => '<i class="fa fa-plus"></i>',
        'close' => '<i class="fa fa-times"></i>',
    );

    // ----------------------------------------------------------------------
    // private functions
    // ----------------------------------------------------------------------

    /**
     * get all messages as html output
     * @return string
     */
    private function _renderLogs() {
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


    protected function _getResultDefs() {
        return array(
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


    /**
     * get html code for a tile
     * 
     * @param integer  $iCount  big counter
     * @param string   $sIcon   icon before label
     * @param string   $sLabel  label
     * @param string   $sMore   more text below a horizontal line
     * @return string
     */
    protected function _getTile($aOptions = array()) {
        // $iCount, $sIcon='', $sLabel='', $sMore=''
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
        foreach ($this->_getResultDefs() as $i) {
            $sMoreChecks .= ($aCounter['checkresults'][$i] ? '<span class="result' . $i . '">' . $aCounter['checkresults'][$i] . '</span> x ' . $this->_tr('Resulttype-' . $i) . ' ' : '');
        }
        return $this->_getTile(array(
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
            foreach ($this->_getResultDefs() as $i) {
                // $sMoreHosts.=($aCounter['appresults'][$i] ? '<span class="result'.$i.'">'.$aCounter['appresults'][$i].'</span> x '.$this->_tr('Resulttype-'.$i).' ' : '');
                $sMoreChecks .= ($aHostdata['summary'][$i] ? '<span class="result' . $i . '">' . $aHostdata['summary'][$i] . '</span> x ' . $this->_tr('Resulttype-' . $i) . ' ' : '');
            }
        }
        $aEmailNotifiers = $this->oNotifcation->setApp($sAppId, $this->_data[$sAppId]);
        $aEmailNotifiers = $this->oNotifcation->getAppNotificationdata('email');
        $aSlackChannels = $this->oNotifcation->getAppNotificationdata('slack', 1);

        // $aPeople=array('email1@example.com', 'email2@example.com');
        $sMoreNotify = (count($aEmailNotifiers) ? '<span title="' . implode("\n", $aEmailNotifiers) . '">' . count($aEmailNotifiers) . ' x ' . $this->_aIco['notify-email'] . '</span>' : '')
                // .'<pre>'.print_r($this->oNotifcation->getAppNotificationdata(), 1).'</pre>'
                . (count($aSlackChannels) ? '<span title="' . implode("\n", array_keys($aSlackChannels)) . '">' . count($aSlackChannels) . ' x ' . $this->_aIco['notify-slack'] . '</span>' : '')
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
                    'label' => $this->_aIco['age'] . ' ' . $this->_tr('age-of-result'),
                    'more' => $this->_tr('TTL') . '=' . $aHostdata['ttl'] . 's',
                ))
                . (isset($aHostdata['summary']['total']) ? $this->_getTile(array(
                    'count' => $aHostdata['summary']['total'],
                    'label' => $this->_aIco['check'] . ' ' . $this->_tr('Checks-on-webapp'),
                    'more' => $sMoreChecks
                )) : '')
                . (isset($this->_data[$sAppId]['meta']['time']) ? $this->_getTile(array(
                    'count' => $this->_data[$sAppId]['meta']['time'],
                    'label' => $this->_aIco['time'] . ' ' . $this->_tr('Time-for-all-checks'),
                )) : '')
                . $this->_getTile(array(
                    'result' => $iNotifyTargets ? false : RESULT_WARNING,
                    'count' => $iNotifyTargets,
                    'label' => $this->_aIco['notifications'] . ' ' . $this->_tr('Notifications'),
                    'more' => $sMoreNotify
                ))
                . $this->_getTile(array(
                    'result' => ($sSleeping ? RESULT_WARNING : false),
                    'count' => ($sSleeping ? $this->_aIco['sleepmode-on'] : $this->_aIco['sleepmode-off']),
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
        // $sReturn.='<pre>'.print_r($aCounter, 1).'</pre>';

        $sMoreHosts = '';
        foreach ($this->_getResultDefs() as $i) {
            $sMoreHosts .= ($aCounter['appresults'][$i] ? '<span class="result' . $i . '">' . $aCounter['appresults'][$i] . '</span> x ' . $this->_tr('Resulttype-' . $i) . ' ' : '');
        }

        $sSleeping = $this->oNotifcation->isSleeptime();
        $sReturn .= ''
                . $this->_getTile(array(
                    'count' => $aCounter['apps'],
                    'label' => $this->_aIco['webapp'] . ' ' . $this->_tr('Webapps'),
                    'more' => $sMoreHosts
                ))
                . $this->_getTile(array(
                    'count' => $aCounter['hosts'],
                    'label' => $this->_aIco['host'] . ' ' . $this->_tr('Hosts'),
                ))
                . $this->_generateChecksTile()
                . $this->_getTile(array(
                    'result' => ($sSleeping ? RESULT_WARNING : false),
                    'count' => ($sSleeping ? $this->_aIco['sleepmode-on'] : $this->_aIco['sleepmode-off']),
                    'label' => ($sSleeping ? $this->_tr('Sleepmode-on') : $this->_tr('Sleepmode-off')),
                    'more' => $sSleeping,
                ))
                . '<div style="clear: both;"></div>'
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
            $aErrors[]='msgErr-missing-section-checks';
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
    private function _showWelcomeMessage() {
        return '<div>'
                . $this->_aIco["welcome"] . ' ' . $this->_tr('msgErr-nocheck-welcome')
                . '<br>'
                . '<a class="btn" href="#divsetup">' . $this->_aIco['setup'] . ' ' . $this->_tr('Setup') . '</a>'
                . '</div>';
    }

    /**
     * helper: generate list of websites with colored boxes based on site status
     * @return string
     */
    private function _generateWeblist() {
        $sReturn = '';
        $iMiss = 0;
        if (!count($this->_data)) {
            return $this->_showWelcomeMessage();
        }
        // echo '<pre>'.print_r($this->_data, 1).'</pre>';
        $sReturn .= $this->_generateWebTiles();

        $aAllWebapps = array();
        foreach ($this->_data as $sAppId => $aEntries) {
            $bHasData = true;
            if (!isset($aEntries["result"]["host"])) {
                $bHasData = false;
                $iMiss++;
            }

            $aValidaion=$this->_checkClientResponse($sAppId);
            $sValidatorinfo='';
            if($aValidaion){
                foreach($aValidaion as $sSection=>$aMessages){
                    if (count($aValidaion[$sSection])){
                        $sValidatorinfo.='<span class="ico'.$sSection.'" title="'.sprintf($this->_tr('Validator-'.$sSection.'-title'), count($aMessages)) .'">'.$this->_aIco[$sSection].'</span>';
                    }
                }
            }
            $sWebapp = isset($aEntries["result"]["website"]) ? $aEntries["result"]["website"] : parse_url($aEntries['result']['url'], PHP_URL_HOST);
            $sTilekey = 'result-' . (999 - $aEntries["result"]["result"]) . '-' . $sWebapp.$sAppId;
            $aTags=isset($aEntries["meta"]["tags"]) ? $aEntries["meta"]["tags"] : false;
            $sOut = '<div '
                    . 'class="divhost result' . $aEntries["result"]["result"] . ' tags '.$this->_getCssclassForTag($aTags).'" '
                    // . ( $bHasData ? 'onclick="window.location.hash=\'#divweb' . $sKey . '\'; showDiv( \'#divweb' . $sKey . '\' )" style="cursor: pointer;"' : '')
                    . 'onclick="window.location.hash=\'#divweb' . $sAppId . '\'; showDiv( \'#divweb' . $sAppId . '\' )" style="cursor: pointer;"'
                    . '>'
                    . ($bHasData ? 
                            '<span class="icon">'.$this->_aIco['webapp'].'</span>'
                            . '<span style="float: right;">'
                                .$this->_renderBadgesForWebsite($sAppId, true) 
                                . $sValidatorinfo
                            .'</span>'
                            . '<a href="#divweb' . $sAppId . '">' . str_replace('.', '.&shy;', $sWebapp) . '</a><br>'
                            . $this->_aIco['host'] . ' ' . $aEntries["result"]["host"] . ' '
                        : '<span title="' . $aEntries['result']['url'] . "\n" . str_replace('"', '&quot;', $aEntries['result']['error']) . '">'
                            . $this->_aIco['error'] . ' ' . $sWebapp . '<br>'
                            . '</span>'
                    )
                    . '<br>'
                    . '</div>';
            $aAllWebapps[$sTilekey] = $sOut;
        }
        ksort($aAllWebapps);
        // echo '<pre>'.htmlentities(print_r($aHosts, 1)).'</pre>'; die();
        foreach ($aAllWebapps as $aWebapp) {
            $sReturn .= $aWebapp;
        }
        if ($iMiss > 0) {
            // $sReturn = '<div class="diverror">' . $this->_aIco["error"] . ' ' . sprintf($this->_tr('msgErr-missedchecks'), $iMiss) . '</div>' . $sReturn;
        }
        return $sReturn . '<div style="clear;"></div>';
    }

    /**
     * helper: generate html code with all checks.
     * if a hast is given it renders the data for this host only
     * 
     * @param  string  $sUrl  optional filter by url; default: all
     * @return string
     */
    private function _generateMonitorTable($sUrl = false) {
        $sReturn = '';
        if (!count($this->_data)) {
            return $this->_showWelcomeMessage();
        }

        $sTableClass = $sUrl ? "datatable-hosts" : "datatable-checks";
        $sReturn .= $sUrl ? $this->_generateTableHead(array(
                    $this->_tr('Timestamp'),
                    $this->_tr('TTL'),
                    $this->_tr('Check'),
                    $this->_tr('Description'),
                    $this->_tr('Result'),
                    $this->_tr('Output'),
                    $this->_tr('Time'),
                )) : $this->_generateTableHead(array(
                    $this->_tr('Host'),
                    $this->_tr('Webapp'),
                    $this->_tr('Timestamp'),
                    $this->_tr('TTL'),
                    $this->_tr('Check'),
                    $this->_tr('Description'),
                    $this->_tr('Result'),
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
                        
                        $sReturn .= '<tr class="result' . $aCheck["result"] . ' tags '.$this->_getCssclassForTag($aTags).'">';
                        if (!$sUrl) {
                            $sReturn .= '<td>' . $aEntries["result"]["host"] . '</td>'
                                    . '<td>' . $aEntries["result"]["website"] . '</td>';
                        }
                        $sReturn .= // . '<td>' . date("H:i:s", $aEntries["meta"]["ts"]) . ' ' . $this->_hrTime(date("U") - $aEntries["meta"]["ts"]) . '</td>'
                                '<td>' . date("Y-m-d H:i:s", $aEntries["result"]["ts"]) . ' (<span class="timer-age-in-sec">' . (date("U") - $aEntries["result"]["ts"]) . '</span>&nbsp;s)</td>'
                                . '<td>' . $aEntries["result"]["ttl"] . '</td>'
                                . '<td>' . $aCheck["name"] . '</td>'
                                . '<td>' . $aCheck["description"] . '</td>'
                                . '<td>' . $this->_tr('Resulttype-' . $aCheck["result"]) . '</td>'
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
     * @return string
     */
    protected function _generateNoftificationlog() {
        $aLogs = $this->oNotifcation->getLogdata();
        rsort($aLogs);

        $sTable = $this->_generateTableHead(array(
                    $this->_tr('Timestamp'),
                    $this->_tr('Change'),
                    $this->_tr('Result'),
                    $this->_tr('Message')
                )) . "\n";
        $sTable .= '<tbody>';

        $aChanges = array();
        $aResults = array();
        foreach ($aLogs as $aLogentry) {

            if (!isset($aChanges[$aLogentry['changetype']])) {
                $aChanges[$aLogentry['changetype']] = 0;
            }
            $aChanges[$aLogentry['changetype']] ++;

            if (!isset($aResults[$aLogentry['status']])) {
                $aResults[$aLogentry['status']] = 0;
            }
            $aResults[$aLogentry['status']] ++;

            $aTags=isset($this->_data[$aLogentry['appid']]["meta"]["tags"]) ? $this->_data[$aLogentry['appid']]["meta"]["tags"] : false;
            $sTable .= '<tr class="result' . $aLogentry['status'] . ' tags '.$this->_getCssclassForTag($aTags).'">'
                    . '<td>' . date("Y-m-d H:i:s", $aLogentry['timestamp']) . '</td>'
                    . '<td>' . $this->_tr('changetype-' . $aLogentry['changetype']) . '</td>'
                    . '<td>' . $this->_tr('Resulttype-' . $aLogentry['status']) . '</td>'
                    . '<td>' . $aLogentry['message'] . '</td>'
                    . '</tr>';
        }
        $sTable .= '</tbody>' . "\n";
        $sTable = '<table class="datatable-notifications">' . "\n" . $sTable . '</table>';

        $sMoreResults = '';
        for ($i = 0; $i <= 4; $i++) {
            $sMoreResults .= (isset($aResults[$i]) ? '<span class="result' . $i . '">' . $aResults[$i] . '</span> x ' . $this->_tr('Resulttype-' . $i) . ' ' : '');
        }
        return
                /*
                 * a tile seems to be useless
                  $this->_getTile(array(
                  'count'=>count($aLogs),
                  'label'=>$this->_tr('Notifications'),
                  'more'=>$sMoreResults,
                  ))
                 */
                $sTable;
    }

    /**
     * get html code for setup page
     * @return string
     */
    private function _generateSetup() {
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
                . '<input type="submit" class="btn btnadd" value="' . $this->_tr('btn-addUrl') . '">'
                . '</form><br>';
        $sReturn .= '<h3>' . $this->_tr('Setup-client-list') . '</h3>';
        foreach ($this->_data as $sAppId => $aData) {
            $iResult = array_key_exists("result", $aData["result"]) ? $aData["result"]["result"] : 3;
            $sUrl = $aData["result"]["url"];
            $sWebsite = array_key_exists("website", $aData["result"]) ? $aData["result"]["website"] : $this->_tr('unknown') . ' (' . $sUrl . ')';
            $sHost = array_key_exists("host", $aData["result"]) ? $aData["result"]["host"] : $this->_tr('unknown');

            $sIdDetails = 'setupdetail' . md5($sAppId);
            $sReturn .= '<div class="divhost result' . $iResult . '" style="float: none; ">'
                    . '<div style="float: right;">'
                    . $sFormOpenTag
                    . '<input type="hidden" name="action" value="deleteurl">'
                    . '<input type="hidden" name="url" value="' . $sUrl . '">'
                    . '<input type="submit" class="btn btndel" '
                    . 'onclick="return confirm(\'' . sprintf($this->_tr('btn-deleteUrl-confirm'), $sUrl) . '\')" '
                    . 'value="' . $this->_tr('btn-deleteUrl') . '">'
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
    private function _renderBadgesForWebsite($sAppId, $bShort = false) {
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
                $sHtml .= ' <span class="badge result' . $i . '" title="' . $aEntries[$sKey] . ' x ' . $this->getResultValue($i) . '">' . $aEntries[$sKey] . '</span> ';
                if (!$bShort) {
                    $sHtml .= $this->_tr('Resulttype-' . $i) . ' ';
                }
            }
        }
        return $sHtml;
    }

    /**
     * returns a readable result by given integer; i.e. 0=OK, 1=unknown, ...
     * @return string
     */
    public function getResultValue($i) {
        return $this->_tr('Resulttype-' . $i);
    }

    /**
     * render html output of monitoring output (data only)
     * @return string
     */
    public function renderHtmlContent() {
        if (!count($this->_data)) {
            $this->_getClientData();
        }
        $sHtml = '';

        // ----- boxes with all websites
        $sId = 'divwebs';
        $sHtml .= '<div class="outsegment" id="' . $sId . '">'
                . '<h2>' . $this->_aIco["allwebapps"] . ' ' . $this->_tr('All-webapps-header') . '</h2>'
                . $this->_generateWeblist()
                . '</div>';

        // ----- one table per checked client
        foreach ($this->_data as $sAppId => $aEntries) {
            $sId = 'divweb' . $sAppId;
            $sDivMoredetails='div-http-'.$sAppId;
            $sShowHide='<br><button class="btn" id="btn-plus-'.$sAppId.'"  onclick="$(\'#'.$sDivMoredetails.'\').slideDown(); $(this).hide(); $(\'#btn-minus-'.$sAppId.'\').show(); return false;"'
                        . '> '.$this->_aIco['plus'].' '.$this->_tr('btn-details').' </button>'
                    . '<button class="btn" id="btn-minus-'.$sAppId.'"  onclick="$(\'#'.$sDivMoredetails.'\').slideUp();   $(this).hide(); $(\'#btn-plus-'.$sAppId.'\').show(); return false;" style="display: none;"'
                    . '> '.$this->_aIco['close'].' '.$this->_tr('btn-hide-details').' </button>';
            if (!isset($aEntries["result"]["website"])) {
                // echo '<pre>'.print_r($aEntries, 1).'</pre>'; 
            }
            if (true ||
                    array_key_exists("result", $aEntries) && array_key_exists("result", $aEntries["result"]) && array_key_exists("website", $aEntries["result"]) && array_key_exists("host", $aEntries["result"])
            ) {
                $sHtml .= '<div class="outsegment" id="' . $sId . '">'
                        . '<h2>' . $this->_aIco['allwebapps'] . ' <a href="#divwebs">' . $this->_tr('All-webapps-header') . '</a>'
                        . ' .. '
                        . $this->_aIco['webapp']
                        . (isset($aEntries['result']['website']) ? $aEntries['result']['website'] : '?')
                        . '</h2>'
                        . $this->_generateWebappTiles($sAppId)
                ;
                if (array_key_exists("host", $aEntries["result"])) {

                    // --- validation
                    $sValidationContent='';
                    $aValidatorResult=$this->_checkClientResponse($sAppId);
                    if($aValidatorResult){
                        foreach($aValidatorResult as $sSection=>$aMessageItems){
                            if(count($aMessageItems)){
                                $sDivContent='';
                                foreach($aMessageItems as $sSingleMessage){
                                    $sDivContent.=($sDivContent?'':'<strong class="ico'.$sSection.'">'.$this->_aIco[$sSection].' '.$this->_tr('Validator-'.$sSection).'</strong><br>')
                                        . '- '.$sSingleMessage.'<br>';
                                }
                                $sValidationContent.= $sDivContent ? '<div class="div'.$sSection.'">'.$sDivContent.'</div>' : '';
                            }
                        }
                    }
                    $sHtml.=$sValidationContent;
                    // --- /validation

                    $sHtml .= '<h3>' . $this->_tr('Checks') . '</h3>'
                            // TODO: create tabs
                            . $this->_generateMonitorTable($aEntries["result"]["url"])
                    // TODO: Info page for people that get notifications
                    // TODO: Info page status changes
                    // .'DEBUG: <pre>'.print_r($aEntries, 1).'</pre>'
                    ;
                }
                $sHtml .= $sShowHide
                        . '<div id="'.$sDivMoredetails.'" style="display: none;">'
                        . '<h3>' . $this->_tr('Http-details') . '</h3>'
                            . ($aEntries['result']['error'] ? '<div class="result3">' . $this->_tr('Error-message') . ': ' . $aEntries['result']['error'] . '</div><br>' : '')
                            . ($aEntries['result']['url'] ? $this->_tr('Url') . ': <a href="' . $aEntries['result']['url'] . '" target="_blank">' . $aEntries['result']['url'] . '</a><br>' : '')
                            . ($aEntries['result']['httpstatus'] ? $this->_tr('Http-status') . ': <strong>' . $aEntries['result']['httpstatus'] . '</strong><br>' : '')
                            . ($aEntries['result']['header'] ? $this->_tr('Http-header') . ': <pre>' . $aEntries['result']['header'] . '</pre>' : '')
                        . '<h3>' . $this->_tr('Client-source-data') . '</h3>'
                        . '<pre>' . htmlentities(print_r($aEntries, 1)) . '</pre>'
                ;
                if ($this->_aCfg['debug']) {
                    $this->oNotifcation->setApp($sAppId);
                    $sHtml .= '<h3>' . $this->_tr('Preview-of-messages') . '</h3>'
                            . '<h4>' . $this->_tr('Preview-replacements') . '</h4>'
                            . '<pre>' . htmlentities(print_r($this->oNotifcation->getMessageReplacements(), 1)) . '</pre>'
                            . '<h4>' . $this->_tr('Preview-emails') . '</h4>'
                    ;
                    foreach ($this->_getResultDefs() as $i) {
                        $sMgIdPrefix = 'changetype-' . $i;
                        $sHtml .= $this->_tr('changetype-' . $i)
                                . '<pre>'
                                . '' . htmlentities(print_r($this->oNotifcation->getReplacedMessage($sMgIdPrefix . '.logmessage'), 1)) . '<hr>'
                                . 'TO: ' . implode('; ', $this->oNotifcation->getAppNotificationdata('email')) . '<br>'
                                . '<strong>' . htmlentities(print_r($this->oNotifcation->getReplacedMessage($sMgIdPrefix . '.email.subject'), 1)) . '</strong><br>'
                                . '' . htmlentities(print_r($this->oNotifcation->getReplacedMessage($sMgIdPrefix . '.email.message'), 1)) . '<br>'
                                . '</pre>';
                    }
                }
                $sHtml .= '</div></div>';
            }
        }

        // ----- table with all checks from all clients
        $sId = 'divall';
        $sHtml .= '<div class="outsegment" id="' . $sId . '">'
                . '<h2>' . $this->_aIco["checks"] . ' ' . $this->_tr('Checks-header') . '</h2>'
                . (count($this->_data) ? $this->_generateChecksTile() . '<div style="clear: both;"></div>' : '')
                . $this->_generateMonitorTable()
                . '</div>';

        // ----- notifications page
        $sId = 'divnotifications';
        $sHtml .= '<div class="outsegment" id="' . $sId . '">'
                . '<h2>' . $this->_aIco["notifications"] . ' ' . $this->_tr('Notifications-header') . '</h2>'
                . $this->_generateNoftificationlog()
                . '</div>';

        // ----- settings page
        $sId = 'divsetup';
        $sHtml .= '<div class="outsegment" id="' . $sId . '">'
                . '<h2>' . $this->_aIco["setup"] . ' ' . $this->_tr('Setup') . '</h2>'
                . $this->_generateSetup()
                . '</div>';

        // ----- debug tab
        if ($this->_aCfg['debug']) {
            $sId = 'divdebug';
            $sHtml .= '<div class="outsegment" id="' . $sId . '">'
                    . '<h2>' . $this->_aIco["debug"] . ' ' . $this->_tr('Debug') . '</h2>'
                    . '<h3>' . $this->_tr('Debug-config') . '</h3>'
                    . '<pre>' . print_r($this->_aCfg, true) . '</pre>'
                    . '<h3>' . $this->_tr('Debug-urls') . '</h3>'
                    . '<pre>' . print_r($this->_urls, true) . '</pre>'
                        . '<h3>' . $this->_tr('Debug-clientdata') . '</h3>'
                    . '<pre>' . print_r($this->_data, true) . '</pre>'
                    // . '<h3>' . $this->_tr('Debug-notificationlog') . '</h3>'
                    // . '<pre>' . print_r($this->oNotifcation->getLogdata(), true) . '</pre>'
                    . '</div>';
        }
        return $sHtml;
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

    protected function _renderTagfilter(){
        $sReturn='';
        $aTaglist=$this->_getClientTags();
        $sOptions='';
        foreach($aTaglist as $sTag){
            $sOptions.='<option value="'.$this->_getCssclassForTag($sTag).'">'.$sTag.'</a>';
        }
        if($sOptions){
            $sReturn=$this->_aIco['filter'].' '.$this->_tr('Tag-filter').': <select onchange="filterForTag(this.value)">'
                        . '<option value="">---</option>'
                        . $sOptions
                    . '</select>';
        }
        return $sReturn;
    }

    /**
     * render html output of monitoring output (whole page)
     * @return string
     */
    public function renderHtml() {
        require_once 'cdnorlocal.class.php';
        $oCdn = new axelhahn\cdnorlocal();
        $sHtml = $this->renderHtmlContent();
        $sNavi = '';
        $sTitle = $this->_sTitle;

        $sNavi.='<span style="float: right; margin-right: 1.5em;">'.$this->_renderTagfilter().'</span>';
        $iReload = ((isset($this->_aCfg['pagereload']) && (int) $this->_aCfg['pagereload'] ) ? (int) $this->_aCfg['pagereload'] : 0);

        $sNavi .= '<a href="#" class="reload" onclick="reloadPage()"'
                . ($iReload ? ' title="' . sprintf($this->_tr('Reload-every'), $iReload) . '"' : '')
                . '>'
                . $this->_aIco["reload"] . ' ' . $this->_tr('Reload') . ' ('.$this->_tr('age-of-page') . ': <span class="timer-age-in-sec">0</span> s)'
                . ' </a>';

        $sId = 'divwebs';
        $sFirstDiv = $sId;
        $sNavi .= '<a href="#' . $sId . '" class="allwebapps" >' . $this->_aIco['allwebapps'] . ' ' . $this->_tr('All-webapps') . '</a>';

        $sId = 'divall';
        $sNavi .= '<a href="#' . $sId . '" class="checks" >' . $this->_aIco["checks"] . ' ' . $this->_tr('Checks') . '</a>';

        $sId = 'divnotifications';
        $sNavi .= '<a href="#' . $sId . '" class="checks" >' . $this->_aIco["notifications"] . ' ' . $this->_tr('Notifications') . '</a>';

        $sId = 'divsetup';
        $sNavi .= '<a href="#' . $sId . '" class="setup" >' . $this->_aIco["setup"] . ' ' . $this->_tr('Setup') . '</a>';

        if ($this->_aCfg['debug']) {
            $sId = 'divdebug';
            $sNavi .= '<a href="#' . $sId . '"  class="debug" >' . $this->_aIco["debug"] . ' ' . $this->_tr('Debug') . '</a>';
        }

        $sTheme = ( array_key_exists('theme', $this->_aCfg) && $this->_aCfg['theme'] ) ? $this->_aCfg['theme'] : 'default';
        $sHtml = '<!DOCTYPE html>' . "\n"
                . '<html>' . "\n"
                . '<head>' . "\n"
                . '<title>' . $sTitle . '</title>'
                . $oCdn->getHtmlInclude("jquery/3.2.1/jquery.min.js")
                . $oCdn->getHtmlInclude("datatables/1.10.16/js/jquery.dataTables.min.js")
                . $oCdn->getHtmlInclude("datatables/1.10.16/css/jquery.dataTables.min.css")
                . $oCdn->getHtmlInclude("font-awesome/4.7.0/css/font-awesome.css")
                . '<script src="javascript/functions.js"></script>'
                . '<link href="themes/' . $sTheme . '/screen.css" rel="stylesheet"/>'
                . '</head>' . "\n"
                . '<body>' . "\n"
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
                    . '' . $sHtml . "\n"
                . '</div>'
                . '<div class="footer"><a href="' . $this->_sProjectUrl . '" target="_blank">' . $this->_sProjectUrl . '</a></div>'

                . '<script>'
                    . '$(document).ready(function() {'
                    . ' $(\'.datatable\').dataTable( { } ); '
                    . ' $(\'.datatable-checks\').dataTable( { "order": [[ 6, "desc" ]] } ); '
                    . ' $(\'.datatable-hosts\').dataTable( { "order": [[ 4, "desc" ]] } ); '
                    . ' $(\'.datatable-notifications\').dataTable( { "order": [[ 0, "desc" ]] } ); '
                    . 'if (document.location.hash) {'
                    . ' showDiv( document.location.hash ) ; '
                    . '} else {'
                    . ' showDiv( "#' . $sFirstDiv . '" ) ; '
                    . '}'
                    . 'initGuiStuff();'
                    . ''
                    . '} );'
                    . ($iReload ? 'window.setTimeout("updateContent()",   ' . ($iReload * 1000) . ');' : '')
                . '</script>' . "\n"
                . '</body></html>';

        return $sHtml;
    }

}
