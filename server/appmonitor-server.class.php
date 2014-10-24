<?php
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
 * - fetch urls
 * - server job that caches all entries
 * - GUI uses cached data only
 * --------------------------------------------------------------------------------<br>
 * <br>
 * --- HISTORY:<br>
 * 2014-10-24  0.5  axel.hahn@iml.unibe.ch<br>
 * --------------------------------------------------------------------------------<br>
 * @version 0.5
 * @author Axel Hahn
 * @link TODO
 * @license GPL
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL 3.0
 * @package IML-Appmonitor
 */
class appmonitorserver {

    var $_data = array();
    var $_urls = array();
    var $_iTtl = 60;
    var $_iTtlOnError = 3;
    var $_sConfigfile = "appmonitor-server-config.json";
    var $_sTitle = "Appmonitor Server GUI v0.5";

    private $_aMsg=array(
        'noconfig'=>'ERROR: The config file does not exist.<hr>Maybe you just installed the server...<br>You need create the config file %s<br>Go into the directory %s<br>and copy the sample file.',
        'nocheck'=>'ERROR: Sorry, no checks were defined yet.<br>Add urls to check in the config file %s.',
        'nodata'=>'ERROR: Sorry, no response from any website. See Tab <a href="#divall">Overview</a> for details.',
        'missedchecks'=>'ERROR: <strong>%d</strong> check(s) failed. The view is incomplete. See Tab <a href="#divall">Overview</a> for details.',
    );
    
    /**
     * constructor
     */
    public function __construct() {

        // read config
        if (!file_exists(__DIR__ . '/' . $this->_sConfigfile)){
            die(sprintf($this->_aMsg['noconfig'], $this->_sConfigfile, __DIR__ ));
        } else {
            $aCfg = json_decode(file_get_contents(__DIR__ . '/' . $this->_sConfigfile), true);

            if (array_key_exists("urls", $aCfg)){
                // add urls
                foreach ($aCfg["urls"] as $sUrl) {
                    $this->addUrl($sUrl);
                }
            }
        }
    }

    // ----------------------------------------------------------------------
    // private function
    // ----------------------------------------------------------------------

    /**
     * make an http get request and return the response body
     * @param string $url
     * @return string
     */
    private function _httpGet($url, $iTimeout = 5) {
        if (!function_exists("curl_init")) {
            return file_get_contents($sUrl);
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, $iTimeout);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

    /**
     * get all client data; it fetches all given urls
     * @return boolean
     */
    private function _getClientData() {
        require_once 'cache.class.php';

        $this->_data = array();
        foreach ($this->_urls as $sKey => $sUrl) {
            $oCache = new AhCache("appmonitor-server", $sUrl);
            if ($oCache->isExpired()) {
                // Cache existiert nicht oder ist veraltet 
                $aClientData = json_decode($this->_httpGet($sUrl), true);
                $iTtl = $this->_iTtl;
                if (!is_array($aClientData)) {
                    $iTtl = $this->_iTtlOnError;
                    $aClientData=array();
                } else {
                    if (
                            is_array($aClientData) && array_key_exists("ttl", $aClientData["meta"]) && $aClientData["meta"]["ttl"]
                    ) {
                        $iTtl = (int) $aClientData["meta"]["ttl"];
                    }
                }
                // fix missing values
                if (!array_key_exists("meta", $aClientData)){
                    $aClientData["meta"]=array();
                }
                if (!array_key_exists("url", $aClientData["meta"])){
                    $aClientData["meta"]["url"] = $sUrl;
                }
                if (!array_key_exists("ts", $aClientData["meta"])){
                    $aClientData["meta"]["ts"] = date("U");
                }
                if (!array_key_exists("ttl", $aClientData["meta"])){
                    $aClientData["meta"]["ttl"] = $iTtl;
                }

                // write cache
                $oCache->write($aClientData, $iTtl);
                $aClientData["meta"]["fromcache"] = false;
            } else {
                // Cache-Daten lesen 
                $aClientData = $oCache->read();
                $aClientData["meta"]["fromcache"] = true;
            }
            $this->_data[$sKey] = $aClientData;
        }
        return true;
    }

    // ----------------------------------------------------------------------
    // setter
    // ----------------------------------------------------------------------

    /**
     * add appmonitor url
     * @param string $sUrl
     * @return boolean
     */
    public function addUrl($sUrl) {
        $this->_urls[md5($sUrl)] = $sUrl;
        // $this->_urls[$sUrl] = $sUrl;
        return true;
    }

    /**
     * remove appmonitor url
     * @param string $sUrl
     * @return boolean
     */
    public function removeUrl($sUrl) {
        if (array_key_exists(md5($sUrl), $this->_urls)) {
            unset($this->_urls[md5($sUrl)]);
            return true;
        }
        return false;
    }

    // ----------------------------------------------------------------------
    // output
    // ----------------------------------------------------------------------

    /**
     * get human readable time
     * @param int $iSec  seconds
     * @return string
     */
    private function _hrTime($iSec) {
        $sReturn = '';
        $sReturn = $iSec . " sec";
        if ($iSec > 60) {
            $sReturn = round($iSec / 60) . " min";
        }
        if ($iSec > 60 * 60 * 2) {
            $sReturn = round($iSec / (60 * 60)) . " h";
        }
        if ($iSec > 60 * 60 * 24 * 2) {
            $sReturn = round($iSec / (60 * 60 * 24)) . " d";
        }
        return ' (' . $sReturn . ' ago)';
    }

    /**
     * helper: generate html code for table header
     * @param array  $aHeaditems  items in header colums
     * @return string
     */
    private function _generateTableHead($aHeaditems) {
        $sReturn = '';
        foreach ($aHeaditems as $sKey) {
            $sReturn.='<th>' . $sKey . '</th>';
        }
        return '<thead><tr>' . $sReturn . '</tr></thead>';
    }

    /**
     * helper: generate list of websites with colored boxes based on site status
     * @return string
     */
    private function _generateWeblist() {
        $sReturn = '';
        $iMiss=0;
        if (!count($this->_data)) {
            return '<div class="diverror">' . sprintf($this->_aMsg['nocheck'], __DIR__ . '/'.$this->_sConfigfile) . '</div>';
        }
        foreach ($this->_data as $sKey => $aEntries) {
            if (array_key_exists("meta", $aEntries) && array_key_exists("result", $aEntries["meta"]) && array_key_exists("website", $aEntries["meta"]) && array_key_exists("host", $aEntries["meta"])
            ){
                $sReturn.='<div class="divhost result' . $aEntries["meta"]["result"] . '">'
                        . '<a href="#divweb' . $sKey . '">' . $aEntries["meta"]["website"] . '</a><br>'
                        . $aEntries["meta"]["host"] . '<br>'
                        . 'Checks: ' . count($aEntries["checks"])
                        . '</div>';
            } else {
                $iMiss++;
            }
        }
        if (!$sReturn){
            return '<div class="diverror">' . sprintf($this->_aMsg['nodata'], __DIR__ . '/'.$this->_sConfigfile) . '</div>';
        }
        if ($iMiss>0){
            $sReturn = '<div class="diverror">' . sprintf($this->_aMsg['missedchecks'], $iMiss). '</div>' .$sReturn ;
        }
        return $sReturn . '<div style="clear;"><br><br></div>';
    }

    /**
     * helper: generate html code with all checks.
     * if a hast is given it renders the data for this host only
     * @param  string  $sHost  optional hostname (as filter); default: all hosts
     * @return string
     */
    private function _generateMonitorTable($sHost = false) {
        $sReturn = '';
        if (!count($this->_data)) {
            return '<div class="diverror">' . sprintf($this->_aMsg['nocheck'], __DIR__ . '/'.$this->_sConfigfile) . '</div>';
        }

        $sReturn.=$this->_generateTableHead(array(
            "Host",
            "Website",
            "Timestamp",
            "TTL",
            "Check",
            "Description",
            "Result",
            "Output",
        ));
        $sReturn.='<tbody>';

        foreach ($this->_data as $sKey => $aEntries) {

            // filter if a host was given
            if (!$sHost || 
                    (
                    array_key_exists("meta", $aEntries)
                    && array_key_exists("host", $aEntries["meta"])
                    && $sHost == $aEntries["meta"]["host"]
                    )
            ) {

                if (
                        !array_key_exists("meta", $aEntries)
                        /*
                          || !array_key_exists("host", $aEntries["meta"])
                          || !array_key_exists("host", $aEntries["website"])
                         * 
                         */
                        || !array_key_exists("checks", $aEntries)
                        || !count($aEntries["checks"])
                ) {
                    $sReturn.='<tr class="result2">'
                            . '<td>?</td>'
                            . '<td>?</td>'
                            . '<td>' . date("Y-m-d H:i:s", $aEntries["meta"]["ts"]) . ' (' . (date("U") - $aEntries["meta"]["ts"]) . '&nbsp;s)</td>'
                            . '<td>' . $aEntries["meta"]["ttl"] . '</td>'
                            . '<td>' . $aEntries["meta"]["url"] . '</td>'
                            . '<td>?</td>'
                            . '<td>?</td>'
                            . '<td>Http Request to appmonitor failed.</td>'
                            . '</tr>';
                } else {
                    foreach ($aEntries["checks"] as $aCheck) {
                        $sReturn.='<tr class="result' . $aCheck["result"] . '">'
                                . '<td>' . $aEntries["meta"]["host"] . '</td>'
                                . '<td>' . $aEntries["meta"]["website"] . '</td>'
                                // . '<td>' . date("H:i:s", $aEntries["meta"]["ts"]) . ' ' . $this->_hrTime(date("U") - $aEntries["meta"]["ts"]) . '</td>'
                                . '<td>' . date("Y-m-d H:i:s", $aEntries["meta"]["ts"]) . ' (' . (date("U") - $aEntries["meta"]["ts"]) . '&nbsp;s)</td>'
                                . '<td>' . $aEntries["meta"]["ttl"] . '</td>'
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
        return '<table class="datatable">' . $sReturn . '</table>';
    }

    /**
     * render html output of monitoring output (data only)
     * @return string
     */
    public function renderHtmlContent() {
        if (!count($this->_data)) {
            $this->_getClientData();
        }

        $sId = 'divall';
        $sHtml = '<div class="outsegment" id="' . $sId . '">'
                . '<h2>Overview with all Checks</h2>'
                . $this->_generateMonitorTable()
                . '</div>';

        $sId = 'divwebs';
        $sHtml.='<div class="outsegment" id="' . $sId . '">'
                . '<h2>Monitor :: Webs</h2>'
                . $this->_generateWeblist()
                . '</div>';


        foreach ($this->_data as $sKey => $aEntries) {
            $sId = 'divweb' . $sKey;
            if (array_key_exists("meta", $aEntries) && array_key_exists("result", $aEntries["meta"]) && array_key_exists("website", $aEntries["meta"]) && array_key_exists("host", $aEntries["meta"])
            )
                $sHtml.='<div class="outsegment" id="' . $sId . '">'
                        . '<h2>Monitor :: Webs :: ' . $aEntries["meta"]["website"] . ' on ' . $aEntries["meta"]["host"] . '</h2>'
                        . '<a href="#divwebs">back</a><br><br>'
                        . $this->_generateMonitorTable($aEntries["meta"]["host"])
                        . '</div>';
        }


        $sId = 'divdebug';
        $sHtml.='<div class="outsegment" id="' . $sId . '">'
                . '<h2>Debug</h2>'
                . '<pre>' . print_r($this->_data, true) . '</pre>'
                . '</div>';
        return $sHtml;
    }

    /**
     * render html output of monitoring output (whole page)
     * @return string
     */
    public function renderHtml() {
        $sHtml = $this->renderHtmlContent();

        $sTitle = $this->_sTitle;

        $sId = 'divall';
        $sFirstDiv = $sId;
        $sNavi = '<a href="#' . $sId . '">Overview</a>';

        $sId = 'divwebs';
        $sNavi.='<a href="#' . $sId . '">Webs</a>';

        $sId = 'divdebug';
        $sNavi.='<a href="#' . $sId . '" >Debug</a>';

        // die($sHtml);
        $sHtml = '<!DOCTYPE html>' . "\n"
                . '<html>' . "\n"
                . '<head>' . "\n"
                . '<title>' . $sTitle . '</title>'
                . '<script type="text/javascript" src="http://code.jquery.com/jquery-1.11.1.min.js"></script>' . "\n"
                . '<script type="text/javascript" src="http://cdn.datatables.net/1.10.2/js/jquery.dataTables.min.js"></script>' . "\n"
                . '<link href="http://cdn.datatables.net/1.10.2/css/jquery.dataTables.css" rel="stylesheet"/>'
                . '<style>'
                . 'body{background:#f8f8f8; color:#223; font-family:"arial"; margin: 0; font-size: 90%;}'
                . 'a{color:#67c;}'
                . 'h1{color:#68c; margin: 0 0.5em 0 0; float: left;}'
                . 'h2{padding-top: 5em; color:#46a;}'
                . 'table.dataTable tbody tr:hover{background:#f0f4ff;}'
                . '.divtop{position: fixed; top: 0; z-index: 1000; width: 100%; padding: 0; }'
                . '.divtopheader{background:#222; color:#888; width: 100%; padding: 1em; opacity:1; }'
                . '.divtopnavi{background:#888; color:#111; width: 100%; padding: 0.5em 1em 0.3em ; opacity:0.9; }'
                . '.divtopnavi a{color:#fff; text-decoration:none;padding: 0.3em; margin-right: 0.3em; border-radius: 0.5em 0.5em 0 0;}'
                . '.divtopnavi a:hover{background:#999;}'
                . '.divtopnavi a.active{background:#fff; color:#338;}'
                . '.divmain{margin: 0 3%;}'
                . '.divsourceinfo{background:#ddd; padding: 1em; }'
                . '.divhost{float: left; padding: 0.5em; border: 1px solid #ccc; margin: 0 1em 1em 0; border-radius: 0.5em;}'
                . '.footer{border-top: 5px solid #33c; margin-top: 20em; padding: 0.2em 1em; background: #333; color:#888;position: fixed; bottom: 0;width: 100%; }'
                . '.result0{background:#efe !important;}'
                . '.result1{background:#fff8d0 !important;}'
                . '.result2{background:#fdd !important;}'
                
                // warnings and error messages
                . '.divwarning{background:#fff0c0 !important; margin: 0 0 2em; padding: 1em;}'
                . '.diverror{background:#fdd !important; margin: 0 0 2em; padding: 1em;}'
                
                . '</style>'
                . '</head>' . "\n"
                . '<body>' . "\n"
                . '<div class="divtop">'
                . '<div class="divtopheader">'
                . '<h1>' . $sTitle . '</h1>'
                . 'generated at ' . date("Y-m-d H:i:s") . '<br>'
                . '</div>'
                . '<div class="divtopnavi">'
                . $sNavi
                . '</div>'
                . '</div>'
                . '<div class="divmain">'
                . '' . $sHtml . "\n"
                . '</div>'
                . '<div class="footer">http://www.iml.unibe.de</div>'
                . '<script>'
                . 'function updateContent(){'
                . '$.ajax({
                    url: "?updatecontent",
                    context: document.body
                    }).done(function(data) {
                    $( ".divmain" ).html( data );
                    });'
                . '}'
                . 'function showDiv(sDiv, oLink ){'
                . '$(".outsegment").hide(); '
                . '$(sDiv).fadeIn(); '
                . '$(".divtopnavi a").removeClass("active");'
                . '$("a[href*="+sDiv+"]").addClass("active");'
                . '}'
                . '$(document).ready(function() {'
                . ' $(\'.datatable\').dataTable( { "order": [[ 0, "desc" ]] } ); '
                . 'if (document.location.hash) {'
                . ' showDiv( document.location.hash ) ; '
                . '} else {'
                . ' showDiv( "#' . $sFirstDiv . '" ) ; '
                . '}'
                . '$("a[href*=#]").click(function() { showDiv( this.hash ) } ); '
                . '/* window.setTimeout("updateContent()", 5000); */'
                . '} );'
                . '</script>' . "\n"
                . '</body></html>';

        return $sHtml;
    }

}
