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
 * - server job that caches all entries
 * - GUI uses cached data only
 * - NAGIOS output
 * --------------------------------------------------------------------------------<br>
 * <br>
 * --- HISTORY:<br>
 * 2018-06-XX  0.15  axel.hahn@iml.unibe.ch   split server class<br>
 * 2018-06-21  0.14  axel.hahn@iml.unibe.ch   use multicurl with parrallel requests; fetch http header; added tiles<br>
 * 2017-06-20  0.12  axel.hahn@iml.unibe.ch   use POST instead of GET<br>
 * 2015-01-20  0.8   axel.hahn@iml.unibe.ch   fixed icons, nagios check<br>
 * 2014-11-27  0.7   axel.hahn@iml.unibe.ch   added icons, lang texts, ...<br>
 * 2014-11-21  0.6   axel.hahn@iml.unibe.ch   added setup functions<br>
 * 2014-10-24  0.5   axel.hahn@iml.unibe.ch<br>
 * --------------------------------------------------------------------------------<br>
 * @version 0.15
 * @author Axel Hahn
 * @link TODO
 * @license GPL
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL 3.0
 * @package IML-Appmonitor
 */
class appmonitorserver_gui extends appmonitorserver{

    var $_sProjectUrl = "https://github.com/iml-it/appmonitor";
    var $_sTitle = "Appmonitor Server GUI v0.17";
    
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
        'webs' => '<i class="fa fa-globe"></i>',
        'host' => '<i class="fa fa-hdd-o"></i>',
        'check' => '<i class="fa fa-check"></i>',
        'checks' => '<i class="fa fa-navicon"></i>',
        'notifications' => '<i class="fa fa-bell-o"></i>',
        'setup' => '<i class="fa fa-wrench"></i>',
        'debug' => '<i class="fa fa-bug"></i>',
        'ok' => '<i class="fa fa-check"></i>',
        'info' => '<i class="fa fa-info"></i>',
        'warning' => '<i class="fa fa-warning"></i>',
        'error' => '<i class="fa fa-flash"></i>',
        'back' => '<i class="fa fa-level-up"></i>',
        'add' => '',
        'del' => '',
        'close' => '',
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

    protected function _generateUrlKey($sUrl){
        return md5($sUrl);
    }
    /**
     * add appmonitor url
     * @param string $sUrl
     * @return boolean
     */
    public function addUrl($sUrl) {
        $sKey=$this->_generateUrlKey($sUrl);
        $this->_urls[$sKey] = $sUrl;
        return true;
    }

    /**
     * remove appmonitor url
     * @param string $sUrl
     * @return boolean
     */
    public function removeUrl($sUrl) {
        $sKey=$this->_generateUrlKey($sUrl);
        if (array_key_exists($sKey, $this->_urls)) {
            unset($this->_urls[$sKey]);
            return true;
        }
        return false;
    }

    /**
     * switch demo mode on off
     * TODO: check how switch demo mode and handle parameters
     * @param type $bBool
     * @return type
     */
    public function setDemoMode($bBool = true) {
        return $this->_bIsDemo = $bBool;
    }

    // ----------------------------------------------------------------------
    // output
    // ----------------------------------------------------------------------


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
     * helper function for overview of all web apps
     * 
     * @return type
     */
    protected function _getCounter() {
        $iCountApps=0;
        $iCountChecks=0;
        $aResults=array(0,0,0,0);
        $aCheckResults=array(0,0,0,0);
        $aServers=array();
        foreach ($this->_data as $sKey => $aEntries) {
            $iCountApps++;// count of webapps
            $aResults[$aEntries['result']['result']]++;// counter by result of app
            if (isset($aEntries['result']['host']) && $aEntries['result']['host']){
                $aServers[$aEntries['result']['host']]=true;// helper array to count hosts
            }

            // count of checks
            if(isset($this->_data[$sKey]["result"]["summary"])){
                $aChecks = $this->_data[$sKey]["result"]["summary"];
                $iCountChecks+=$aChecks["total"];
                for ($i=0; $i<4; $i++){
                    $aCheckResults[$i]+=$aChecks[$i];
                }
            }
        }
        return array(
            'apps'=>$iCountApps,
            'hosts'=>count($aServers),
            'appresults'=>$aResults,
            'checks'=>$iCountChecks,
            'checks'=>$iCountChecks,
            'checkresults'=>$aCheckResults
        );
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
    protected function _getTile($iCount, $sIcon='', $sLabel='', $sMore='') {
        return '<div class="tile">'
            . ($sIcon ? '<span class="icon">'.$sIcon.'</span>' : '' )
            . '<div class="count">'.$iCount.'</div>'
            . ($sLabel ? '<div class="label">'.$sLabel.'</div>' : '' )
            . ($sMore ? '<div class="more">'.$sMore .'</div>' : '' )
        . '</div>';
    }
    
    /**
     * get html code for tiles of a single webapp
     * 
     * @param string  $sKey  webapp id
     * @return string
     */
    protected function _generateWebappTiles($sKey) {
        $aHostdata=$this->_data[$sKey]['result'];
        $this->oNotifcation->setApp($sKey);
        $aLast=$this->oNotifcation->getAppLastResult(); 
        $sSince=$aLast && (int)$aLast['result']['ts'] ? $this->_tr('since') . ' '.date("Y-m-d H:i", $aLast['result']['ts']) : '';
        $sReturn='';
        // $sReturn.='<pre>'.print_r($aHostdata, 1).'</pre>';
        
        $sMoreChecks='';
        for($i=0; $i<4; $i++){
            // $sMoreHosts.=($aCounter['appresults'][$i] ? '<span class="result'.$i.'">'.$aCounter['appresults'][$i].'</span> x '.$this->_tr('Resulttype-'.$i).' ' : '');
            $sMoreChecks.=(isset($aHostdata['summary'][$i]) ? '<span class="result'.$i.'">'.$aHostdata['summary'][$i].'</span> x '.$this->_tr('Resulttype-'.$i).' ' : '');
        }
        
        $sReturn.=''
                .(isset($aHostdata['result'])           ? $this->_getTile('<span class="result'.$aHostdata['result'].'">'.$this->_tr('Resulttype-'.$aHostdata['result']).'</span>', '', $this->_tr('Appstatus'), $sSince) : '')
                .(isset($aHostdata['summary']['total']) ? $this->_getTile($aHostdata['summary']['total'], '', $this->_aIco['check'].' '.$this->_tr('Checks'), $sMoreChecks): '')
                .'<div style="clear: both;"></div>'
                ;
        return $sReturn;
    }
    /**
     * get html code for tiles of a webapp overview with all applications
     * 
     * @return string
     */
    protected function _generateWebTiles() {
        $sReturn='';
        $aCounter=$this->_getCounter();
        // $sReturn.='<pre>'.print_r($aCounter, 1).'</pre>';
        
        $sMoreHosts='';
        $sMoreChecks='';
        for($i=0; $i<4; $i++){
            $sMoreHosts.=($aCounter['appresults'][$i] ? '<span class="result'.$i.'">'.$aCounter['appresults'][$i].'</span> x '.$this->_tr('Resulttype-'.$i).' ' : '');
            $sMoreChecks.=($aCounter['checkresults'][$i] ? '<span class="result'.$i.'">'.$aCounter['checkresults'][$i].'</span> x '.$this->_tr('Resulttype-'.$i).' ' : '');
        }
        
        $sReturn.=''
                .$this->_getTile($aCounter['hosts'], '', $this->_aIco['host'].' '.$this->_tr('Hosts'))
                .$this->_getTile($aCounter['apps'], '', $this->_aIco['webs'].' '.$this->_tr('Webs'), $sMoreHosts)
                .$this->_getTile($aCounter['checks'], '', $this->_aIco['check'].' '.$this->_tr('Checks'), $sMoreChecks)
                .'<div style="clear: both;"></div>'
                ;
        return $sReturn;
    }
    
    /**
     * get html code to show a welcome message if no webapp was setup so far.
     * @return string
     */
    private function _showWelcomeMessage() {
        return '<div>' 
            . $this->_aIco["welcome"] . ' ' . $this->_tr('msgErr-nocheck-welcome') 
            . '<br>'
            . '<a class="btn" href="#divsetup">'.$this->_aIco['setup'].' '.$this->_tr('Setup').'</a>'
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
        
        foreach ($this->_data as $sKey => $aEntries) {
            $bHasData=true;
            if(!$aEntries['result']['httpstatus']){
                $bHasData=false;
                $iMiss++;
            }
            $sReturn .= '<div '
                            . 'class="divhost result' . $aEntries["result"]["result"] . '" '
                            // . ( $bHasData ? 'onclick="window.location.hash=\'#divweb' . $sKey . '\'; showDiv( \'#divweb' . $sKey . '\' )" style="cursor: pointer;"' : '')
                            . 'onclick="window.location.hash=\'#divweb' . $sKey . '\'; showDiv( \'#divweb' . $sKey . '\' )" style="cursor: pointer;"'
                    . '>'
                        
                        . ($bHasData 
                                ? '<a href="#divweb' . $sKey . '">' . $aEntries["result"]["website"].'</a><br>'
                                    . $this->_aIco['host'] .' '. $aEntries["result"]["host"] . ' '. $this->_renderBadgesForWebsite($sKey, true)
                                : '<span title="'.$aEntries['result']['url']."\n".$aEntries['result']['error'].'">'
                                        .$this->_aIco['error'] .' '. parse_url($aEntries['result']['url'], PHP_URL_HOST) .'<br>'
                                  .'</span>'
                            )
                        . '<br>'
                    . '</div>';
        }
        if (!$sReturn) {
            return '<div class="diverror">' . $this->_aIco["error"] . ' ' . sprintf($this->_tr('msgErr-nodata'), $this->_getConfigDir() . '/' . $this->_sConfigfile) . '</div>';
        }
        if ($iMiss > 0) {
            // $sReturn = '<div class="diverror">' . $this->_aIco["error"] . ' ' . sprintf($this->_tr('msgErr-missedchecks'), $iMiss) . '</div>' . $sReturn;
        }
        return $sReturn . '<div style="clear;"><br><br></div>';
    }

    /**
     * helper: generate html code with all checks.
     * if a hast is given it renders the data for this host only
     * 
     * @param  string  $sHost  optional hostname (as filter); default: all hosts
     * @return string
     */
    private function _generateMonitorTable($sHost = false) {
        $sReturn = '';
        if (!count($this->_data)) {
            return $this->_showWelcomeMessage();
        }

        $sTableClass = $sHost ? "datatable-hosts" : "datatable-checks";
        $sReturn .= $sHost ? $this->_generateTableHead(array(
                    $this->_tr('Timestamp'),
                    $this->_tr('TTL'),
                    $this->_tr('Check'),
                    $this->_tr('Description'),
                    $this->_tr('Result'),
                    $this->_tr('Output'),
                )) : $this->_generateTableHead(array(
                    $this->_tr('Host'),
                    $this->_tr('Website'),
                    $this->_tr('Timestamp'),
                    $this->_tr('TTL'),
                    $this->_tr('Check'),
                    $this->_tr('Description'),
                    $this->_tr('Result'),
                    $this->_tr('Output'),
        ));
        $sReturn .= '<tbody>';

        foreach ($this->_data as $sKey => $aEntries) {

            // filter if a host was given
            if (!$sHost ||
                    (
                    array_key_exists("result", $aEntries) && array_key_exists("host", $aEntries["result"]) && $sHost == $aEntries["result"]["host"]
                    )
            ) {

                if (
                        $aEntries["result"]["error"]
                        
                        // !array_key_exists("result", $aEntries)
                        /*
                          || !array_key_exists("host", $aEntries["meta"])
                          || !array_key_exists("host", $aEntries["website"])
                         * 
                         */
                        // || !array_key_exists("checks", $aEntries) || !count($aEntries["checks"])
                ) {
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
                } else {
                    foreach ($aEntries["checks"] as $aCheck) {
                        $sReturn .= '<tr class="result' . $aCheck["result"] . '">';
                        if (!$sHost) {
                            $sReturn .= '<td>' . $aEntries["result"]["host"] . '</td>'
                                    . '<td>' . $aEntries["result"]["website"] . '</td>';
                        }
                        $sReturn .= // . '<td>' . date("H:i:s", $aEntries["meta"]["ts"]) . ' ' . $this->_hrTime(date("U") - $aEntries["meta"]["ts"]) . '</td>'
                                '<td>' . date("Y-m-d H:i:s", $aEntries["result"]["ts"]) . ' (' . (date("U") - $aEntries["result"]["ts"]) . '&nbsp;s)</td>'
                                . '<td>' . $aEntries["result"]["ttl"] . '</td>'
                                . '<td>' . $aCheck["name"] . '</td>'
                                . '<td>' . $aCheck["description"] . '</td>'
                                . '<td>' . $aCheck["result"] . '</td>'
                                . '<td>' . $aCheck["value"] . '</td>'
                                . '</tr>';
                    }
                }
            }
        }
        $sReturn.='</tbody>';
        return '<table class="' . $sTableClass . '">' . $sReturn . '</table>';
    }

    /**
     * get html code for notification log page
     * @return string
     */
    protected function _generateNoftificationlog(){
        $aLogs=$this->oNotifcation->getLogdata();
        rsort($aLogs);
        
        $sReturn = $this->_generateTableHead(array(
                    $this->_tr('Timestamp'),
                    $this->_tr('Change'),
                    $this->_tr('Result'),
                    $this->_tr('Message')
                ))."\n";
        $sReturn .= '<tbody>';
        foreach ($aLogs as $aLogentry) {
            $sReturn .= '<tr class="result' . $aLogentry['status'] . '">'
                . '<td>' . date("Y-m-d H:i:s", $aLogentry['timestamp']) . '</td>'
                . '<td>' . $this->_tr('changetype-'.$aLogentry['changetype']) . '</td>'
                . '<td>' . $this->_tr('Resulttype-'.$aLogentry['status']) . '</td>'
                . '<td>' . $aLogentry['message'] . '</td>'
            . '</tr>';
        }
        $sReturn.='</tbody>'."\n";
        
        $sReturn='<table class="datatable-notifications">' ."\n" . $sReturn . '</table>';
        
        // $sReturn.='<pre>'. htmlentities($sReturn).'</pre>';
        return $sReturn;
        
    }
    
    /**
     * get html code for setup page
     * @return string
     */
    private function _generateSetup() {
        $sReturn = '';
        $sFormOpenTag = '<form action="?" method="POST">';
        $sReturn .= '<h3>' . $this->_tr('Setup-client-list') . '</h3>';
        foreach ($this->_data as $sKey => $aData) {
            $iResult = array_key_exists("result", $aData["result"]) ? $aData["result"]["result"] : 3;
            $sUrl = $aData["result"]["url"];
            $sWebsite = array_key_exists("website", $aData["result"]) ? $aData["result"]["website"] : $this->_tr('unknown') . ' ('.$sUrl.')';
            $sHost = array_key_exists("host", $aData["result"]) ? $aData["result"]["host"] : $this->_tr('unknown');

            $sIdDetails='setupdetail'.md5($sKey);
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
                    
                        . '<button class="btn" onclick="$(\'#'.$sIdDetails.'\').toggle(); return false;">'.$this->_tr('btn-details').'</button>'
                    . ' ' . $this->_aIco['webs'] . ' ' . $this->_tr('Website') . ' '
                        . $sWebsite
                        . '... ' 
                        . $this->_aIco['host'] . ' ' . $this->_tr('Host') . ' ' . $sHost.' '
                        
                        . '<div id="'.$sIdDetails.'" style="display: none;">'
                            . $this->_tr('Url') . ' '
                            . '<a href="' . $sUrl . '" target="_blank">'
                                    . $sUrl
                            . '</a><br>'
                            . '<pre>'.($aData['result']['header'] ? $aData['result']['header'] : $aData['result']['error']).'</pre>'
                            // . '<pre>'.print_r($aData, 1).'</pre>'
                        .'</div>'
                    . '</div>';
        }
        $sReturn .= '<br><br><h3>' . $this->_tr('Setup-add-client') . '</h3>';
        $sReturn .= '<p>' . $this->_tr('Setup-add-client-pretext') . '</p>'
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
        return $sReturn;
    }

    /**
     * gt html code for badged list with errors, warnings, unknown, ok
     * @param string $sKey    key of the check (hashed url)
     * @param bool   $bShort  display type short (counter only) or long (with texts)
     * @return string|boolean
     */
    private function _renderBadgesForWebsite($sKey, $bShort = false) {
        $iResult = $this->_data[$sKey]["result"]["result"];
        if (!array_key_exists("summary", $this->_data[$sKey]["result"])) {
            return false;
        }
        $aEntries = $this->_data[$sKey]["result"]["summary"];
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
     * get all client data and final result as array
     * @param   string  $sHost  filter by given hostname
     * @return  array
     */
    public function getMonitoringData($sHost = false) {

        $aReturn = array();
        $iMaxReturn = 0;
        $aMessages = array();
        $aResults = array();

        if (!count($this->_data) || true) {
            $this->_getClientData();
        }

        // print_r($this->_data);

        if (!count($this->_data)) {
            return array(
                'return' => 3,
                'messages' => array($this->_tr('msgErr-nocheck'))
            );
        }
        foreach ($this->_data as $sKey => $aEntries) {

            // filter if a host was given
            if (!$sHost ||
                    (
                    array_key_exists("result", $aEntries) && array_key_exists("host", $aEntries["result"]) && $sHost == $aEntries["result"]["host"]
                    )
            ) {

                if (
                        !array_key_exists("result", $aEntries)
                        /*
                          || !array_key_exists("host", $aEntries["meta"])
                          || !array_key_exists("host", $aEntries["website"])
                         * 
                         */ || !array_key_exists("checks", $aEntries) || !count($aEntries["checks"])
                ) {
                    if ($iMaxReturn < 3)
                        $iMaxReturn = 3;
                    $aMessages[] = $this->_tr('msgErr-Http-request-failed') . ' (' . $aEntries["result"]["url"] . ')';
                } else {
                    if ($iMaxReturn < $aEntries["result"]["result"])
                        $iMaxReturn = $aEntries["result"]["result"];
                    $aMessages[] = $aEntries["result"]["host"] . ': ' . $aEntries["result"]["result"];
                    foreach ($aEntries["result"]["summary"] as $key => $value) {
                        if (!array_key_exists($key, $aResults)) {
                            $aResults[$key] = 0;
                        }
                        $aResults[$key] += $value;
                    }
                }
            }
        }
        return array(
            'return' => $iMaxReturn,
            'messages' => $aMessages,
            'results' => $aResults,
        );
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
                . '<h2>' . $this->_aIco["webs"] . ' ' . $this->_tr('Webs-header') . '</h2>'
                . $this->_generateWeblist()
                . '</div>';

        // ----- one table per checked client
        foreach ($this->_data as $sKey => $aEntries) {
            $sId = 'divweb' . $sKey;
            if(!isset($aEntries["result"]["website"])){
                // echo '<pre>'.print_r($aEntries, 1).'</pre>'; 
            }
            if (true ||
                    array_key_exists("result", $aEntries) 
                    && array_key_exists("result", $aEntries["result"]) 
                    && array_key_exists("website", $aEntries["result"]) 
                    && array_key_exists("host", $aEntries["result"])
            ) {
                $sHtml .= '<div class="outsegment" id="' . $sId . '">'
                        . '<h2>' . $this->_aIco["webs"] . ' <a href="#divwebs">' . $this->_tr('Webs-header').'</a>'
                            . ' > ' . ' ' 
                            . (isset($aEntries["result"]["website"]) ? $aEntries["result"]["website"] : '?')
                        . '</h2>'
                        /*
                        . '<div class="divhost result' . $aEntries["result"]["result"] . '" style="float: none;">'
                        . '<a href="#divwebs" class="btn">' . $this->_aIco['back'] . ' ' . $this->_tr('btn-back') . '</a> '
                        . $this->_renderBadgesForWebsite($sKey) .'<br>'
                        . '</div>'
                         * 
                         */
                        . $this->_generateWebappTiles($sKey)
                        // . '<br><a href="#divwebs" class="btn">' . $this->_aIco['back'] . ' ' . $this->_tr('btn-back') . '</a> '
                        // . '<br><br><br>'
                        ;
                if (array_key_exists("host", $aEntries["result"])) {
                    
                    $sHtml .= '<h3>'.$this->_tr('Checks').'</h3>'
                            // TODO: create tabs
                            . $this->_generateMonitorTable($aEntries["result"]["host"])
                            // TODO: Info page for people that get notifications
                            // TODO: Info page status changes
                            // .'DEBUG: <pre>'.print_r($aEntries, 1).'</pre>'
                            ;

                }
                $sHtml .= '<h3>'.$this->_tr('Http-details').'</h3>'
                        . ($aEntries['result']['error']      ? $this->_tr('Error-message'). ': ' . $aEntries['result']['error'].'<br>': '')
                        . ($aEntries['result']['httpstatus'] ? $this->_tr('Http-status'). ': <strong>' . $aEntries['result']['httpstatus'].'</strong><br>': '')
                        . ($aEntries['result']['header']     ? $this->_tr('Http-header'). ': <pre>' . $aEntries['result']['header'].'</pre>': '')
                        // . '<pre>'.print_r($aEntries["result"], 1).'</pre>'
                        . '</div>';
            }
        }

        // ----- table with all checks from all clients
        $sId = 'divall';
        $sHtml .= '<div class="outsegment" id="' . $sId . '">'
                . '<h2>' . $this->_aIco["checks"] . ' ' . $this->_tr('Checks-header') . '</h2>'
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
                    . '<h3>' . $this->_tr('Debug-notificationlog') . '</h3>'
                    . '<pre>' . print_r($this->oNotifcation->getLogdata(), true) . '</pre>'
                    . '</div>';
        }
        return $sHtml;
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


        $sNavi .= '<a href="#" class="reload" onclick="reloadPage()">' . $this->_aIco["reload"] . ' ' . $this->_tr('Reload') . '</a>';

        $sId = 'divwebs';
        $sFirstDiv = $sId;
        $sNavi .= '<a href="#' . $sId . '" class="webs" >' . $this->_aIco["webs"] . ' ' . $this->_tr('Webs') . '</a>';

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
                . '<link href="themes/' . $sTheme . '.css" rel="stylesheet"/>'
                . '</head>' . "\n"
                . '<body>' . "\n"
                . '<div class="divtop">'
                . '<div class="divtopheader">'
                . '<span style="float: right; margin-right: 1.5em;">' . sprintf($this->_tr('generated-at'), date("Y-m-d H:i:s")) . '</span>'
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
                . '$("a[href^=\'#\']").click(function() { showDiv( this.hash ) } ); '
                . '/* window.setTimeout("updateContent()", 5000); */'
                . '} );'
                . '</script>' . "\n"
                . '</body></html>';

        return $sHtml;
    }

}
