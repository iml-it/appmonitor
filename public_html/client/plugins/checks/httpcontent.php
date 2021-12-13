<?php
/**
 * ____________________________________________________________________________
 * 
 *  _____ _____ __                   _____         _ _           
 * |     |     |  |      ___ ___ ___|     |___ ___|_| |_ ___ ___ 
 * |-   -| | | |  |__   | .'| . | . | | | | . |   | |  _| . |  _|
 * |_____|_|_|_|_____|  |__,|  _|  _|_|_|_|___|_|_|_|_| |___|_|  
 *                          |_| |_|                              
 *                           _ _         _                                            
 *                       ___| |_|___ ___| |_                                          
 *                      |  _| | | -_|   |  _|                                         
 *                      |___|_|_|___|_|_|_|   
 *                                                               
 * ____________________________________________________________________________
 * 
 * CHECK RESPONSE OF AN HTTP REQUEST
 * ____________________________________________________________________________
 * 
 * 2021-10-26  <axel.hahn@iml.unibe.ch>
 * 
 */
class checkHttpContent extends appmonitorcheck{
    /**
     * get default group of this check
     * @param array   $aParams
     * @return array
     */
    public function getGroup($aParams){
        $sReturn='service';
        if(isset($aParams['status']) && $aParams['status'] > 300 && $aParams['status'] < 500 ){
            $sReturn='deny';
        }
        return $sReturn;
    }

    /**
     * make http request and test response body
     * @param array $aParams
     * array(
     *     url                 string   url to fetch
     *     timeout             integer  optional timeout in sec; default: 5
     *     headeronly          boolean  optional flag to fetch http response herader only; default: false = returns header and body
     *     follow              boolean  optional flag to follow a location; default: false = do not follow
     *     status              integer  test for an expected http status code; if none is given then test fails on status 400 and greater
     *     headercontains      string   test for a string in the http response header; it returns OK if the text was found
     *     headernotcontains   string   test for a string in the http response header; it returns OK if the text was not found
     *     headerregex         string   test for a regex in the http response header; it returns OK if the regex matches; example: "headerregex"=>"/lowercasematch/i"
     *     bodycontains        string   test for a string in the http response body; it returns OK if the text was found
     *     bodynotcontains     string   test for a string in the http response body; it returns OK if the text was not found
     *     bodyregex           string   test for a regex in the http response body; it returns OK if the regex matches; example: "headerregex"=>"/lowercasematch/i"
     * )
     */
    public function run($aParams) {
        $this->_checkArrayKeys($aParams, "url");
        if (!function_exists("curl_init")) {
            header('HTTP/1.0 503 Service Unavailable');
            die("ERROR: PHP CURL module is not installed.");
        }
        $bShowContent = (isset($aParams["content"]) && $aParams["content"]) ? true : false;
        $ch = curl_init($aParams["url"]);
        
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, isset($aParams["headeronly"]) && $aParams["headeronly"]);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, isset($aParams["follow"]) && $aParams["follow"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, (isset($aParams["timeout"]) && (int)$aParams["timeout"]) ? (int)$aParams["timeout"] : $this->_iTimeoutTcp);
        $res = curl_exec($ch);

        if (!$res) {
            $iErrorCode=curl_errno($ch);
            $sErrorMsg=curl_error($ch);
            curl_close($ch);
            return [
                RESULT_ERROR, 'ERROR: failed to fetch ' . $aParams["url"] . ' - curl error #'.$iErrorCode.': '.$sErrorMsg
            ];
        } 
        $sOut='';
        $bError=false;
        
        $aInfos = curl_getinfo($ch);
        /*
            Array
            (
                [url] => https://www.iml.unibe.ch/
                [content_type] => text/html; charset=utf-8
                [http_code] => 200
                [header_size] => 926
                [request_size] => 55
                [filetime] => -1
                [ssl_verify_result] => 20
                [redirect_count] => 0
                [total_time] => 1.812
                [namelookup_time] => 0
                [connect_time] => 0
                [pretransfer_time] => 0.015
                [size_upload] => 0
                [size_download] => 94654
                [speed_download] => 52237
                [speed_upload] => 0
                [download_content_length] => -1
                [upload_content_length] => -1
                [starttransfer_time] => 1.812
                [redirect_time] => 0
                [redirect_url] => 
                [primary_ip] => 130.92.30.80
                [certinfo] => Array
                    (
                    )

                [primary_port] => 443
                [local_ip] => 10.1.30.49
                [local_port] => 63597
            )
         */
        
        curl_close($ch);
        
        $aTmp=explode("\r\n\r\n", $res, 2);
        $sHttpHeader=$aTmp[0];
        $sHttpBody=isset($aTmp[1]) ? $aTmp[1] : false;
        
        // ---------- check functions
        
        // --- http status code
        $sOut.="Http status: ".$aInfos['http_code']." - ";
        if(isset($aParams["status"])){
            if($aInfos['http_code'] === $aParams["status"]){
                $sOut.="compare OK<br>";
            } else {
                $sOut.="compare failed<br>";
                $bError=true;
            }
        } else {
            if($aInfos['http_code'] >= 400){
                $sOut.="Error page detected<br>";
                $bError=true;
            } else {
                $sOut.="request successful<br>";
            }
        }
        // --- http header
        if(isset($aParams["headercontains"]) && $aParams["headercontains"]){
            $sOut.="Http header contains &quot;".$aParams["headercontains"]."&quot; - ";
            if(!strstr($sHttpHeader, $aParams["headercontains"])===false){
                $sOut.="compare OK<br>";
            } else {
                $sOut.="compare failed<br>";
                $bError=true;
            }
        }
        if(isset($aParams["headernotcontains"]) && $aParams["headernotcontains"]){
            $sOut.="Http header does not contain &quot;".$aParams["headernotcontains"]."&quot; - ";
            if(strstr($sHttpHeader, $aParams["headernotcontains"])===false){
                $sOut.="compare OK<br>";
            } else {
                $sOut.="compare failed<br>";
                $bError=true;
            }
        }
        if(isset($aParams["headerregex"]) && $aParams["headerregex"]){
            $sOut.="Http header regex test &quot;".$aParams["headerregex"]."&quot; - ";
            try{
                $bRegex=preg_match($aParams["headerregex"], $sHttpHeader);
                if($bRegex){
                    $sOut.="compare OK<br>";
                } else {
                    $sOut.="compare failed<br>";
                    $bError=true;
                }
            } 
            catch(Exception $e){
                $sOut.="Wrong REGEX<br>" . print_r($e, 1).'<br>';
                $bError=true;
            }
        }
        // --- http body
        if(isset($aParams["bodycontains"]) && $aParams["bodycontains"]){
            $sOut.="Http body contains &quot;".$aParams["bodycontains"]."&quot; - ";
            if(!strstr($sHttpBody, $aParams["bodycontains"])===false){
                $sOut.="compare OK<br>";
            } else {
                $sOut.="compare failed<br>";
                $bError=true;
            }
        }
        if(isset($aParams["bodynotcontains"]) && $aParams["bodynotcontains"]){
            $sOut.="Http body does not contain &quot;".$aParams["bodynotcontains"]."&quot; - ";
            if(strstr($sHttpBody, $aParams["bodynotcontains"])===false){
                $sOut.="compare OK<br>";
            } else {
                $sOut.="compare failed<br>";
                $bError=true;
            }
        }
        if(isset($aParams["bodyregex"]) && $aParams["bodyregex"]){
            $sOut.="Http body regex test &quot;".$aParams["bodyregex"]."&quot; - ";
            try{
                $bRegex=preg_match($aParams["bodyregex"], $sHttpBody);
                if($bRegex){
                    $sOut.="compare OK<br>";
                } else {
                    $sOut.="compare failed<br>";
                    $bError=true;
                }
            } 
            catch(Exception $e){
                $sOut.="Wrong REGEX<br>" . print_r($e, 1).'<br>';
                $bError=true;
            }
        }
        
        if (!$bError) {
            return [
                RESULT_OK, 
                'OK: http check "' . $aParams["url"] . '".<br>'.$sOut
            ];
        } else {
            return [
                RESULT_ERROR, 
                'ERROR: http check "' . $aParams["url"] . '".<br>'.$sOut
            ];
        }

        /*
        echo '<pre>'; 
        echo $sOut."<hr>";
        echo "<hr>HEADER: ".htmlentities($sHttpHeader)."<hr>";
        print_r($aParams); print_r($aInfos); 
        // echo htmlentities($sHttpBody);
        die();
         */
    }

}
