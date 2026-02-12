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
 * 2022-12-21  <axel.hahn@unibe.ch>      add flag sslverify
 * 2023-07-06  <axel.hahn@unibe.ch>      add flag userpwd
 * 2024-07-23  <axel.hahn@unibe.ch>      php 8 only: use typed variables
 * 2024-11-22  <axel.hahn@unibe.ch>      Return unknown if curl module is not active
 * 2025-03-17  <axel.hahn@unibe.ch>      Fix check for http status code
 * 2025-03-19  <axel.hahn@unibe.ch>      add validation rules and parameter description
 * 2025-12-18  <axel.hahn@unibe.ch>      fix typo; remove final '<br>' in output
 * 2026-02-12  <axel.hahn@unibe.ch>      fix flag sslverify => false
 */
class checkHttpContent extends appmonitorcheck
{
    /**
     * Self documentation and validation rules
     * @var array
     */
    protected array $_aDoc = [
        'name' => 'Plugin HttpContent',
        'description' => 'This check verifies if a given url can be requested. Optionally you can test if it follows wanted rules.',
        'parameters' => [
            'url' => [
                'type' => 'string',
                'required' => true,
                'description' => 'Url to fetch',
                'default' => "",
                'regex' => '/https?:\/\//',
                'example' => 'https://www.example.com/',
            ],
            'userpwd' => [
                'type' => 'string',
                'required' => false,
                'description' => 'User and password; syntax: “[username]:[password]”',
                'regex' => '/.*:.*/',
                'default' => "",
                'example' => "myuser:aVerySecretPassword",
            ],
            'timeout' => [
                'type' => 'int',
                'required' => false,
                'description' => 'Timeout in sec',
                'default' => 5,
                'example' => "10",
            ],
            'headeronly' => [
                'type' => 'bool',
                'required' => false,
                'description' => 'flag to fetch http response herader only (HEAD request); default: false = returns header and body;',
                'default' => false,
                'example' => "true",
            ],
            'follow' => [
                'type' => 'bool',
                'required' => false,
                'description' => 'flag to follow a location; default: false = do not follow; If you set it to true it ries to follow (but this is not a safe method)',
                'default' => false,
                'example' => "true",
            ],
            'sslverify' => [
                'type' => 'bool',
                'required' => false,
                'description' => 'Enable/ disable verification of ssl certificate; default: true (verification is on)',
                'default' => true,
                'example' => "false",
            ],
            'status' => [
                'type' => 'int',
                'required' => false,
                'description' => 'Test for an expected http status code; if none is given then test fails on status 400 and greater.',
                'default' => null,
                'example' => "401",
            ],
            'headercontains' => [
                'type' => 'string',
                'required' => false,
                'description' => 'Test for a string in the http response header; it returns OK if the text was found',
                'default' => null,
                'example' => "Content-Type: text/css",
            ],
            'headernotcontains' => [
                'type' => 'string',
                'required' => false,
                'description' => 'Test for a string in the http response header; it returns OK if the text was not found',
                'default' => null,
                'example' => "Server:",
            ],
            'headerregex' => [
                'type' => 'string',
                'required' => false,
                'description' => 'Test for a regex in the http response header; it returns OK if the regex matches',
                'default' => null,
                'example' => "",
            ],
            'bodycontains' => [
                'type' => 'string',
                'required' => false,
                'description' => 'Test for a string in the http response body; it returns OK if the text was found',
                'default' => null,
                'example' => "Content-Type: text/css",
            ],
            'bodynotcontains' => [
                'type' => 'string',
                'required' => false,
                'description' => 'Test for a string in the http response body; it returns OK if the text was not found',
                'default' => null,
                'example' => "Server:",
            ],
            'bodyregex' => [
                'type' => 'string',
                'required' => false,
                'description' => 'Test for a regex in the http response body; it returns OK if the regex matches',
                'default' => null,
                'example' => "",
            ],
        ],
    ];

    /**
     * Get default group of this check
     * It is a "service" icon or "deny" for expected failures
     * 
     * @param array   $aParams with optional 'status' containing http response code
     * @return string
     */
    public function getGroup(array $aParams = []): string
    {
        $sReturn = 'service';
        if (isset($aParams['status']) && $aParams['status'] > 300 && $aParams['status'] < 500) {
            $sReturn = 'deny';
        }
        return $sReturn;
    }

    /**
     * Make http request and test response header + body
     * @param array $aParams
     * [
     *     url                 string   url to fetch
     *     userpwd             string   set user and password; syntax: "[username]:[password]"
     *     timeout             integer  optional timeout in sec; default: 5
     *     headeronly          boolean  optional flag to fetch http response herader only; default: false = returns header and body
     *     follow              boolean  optional flag to follow a location; default: false = do not follow
     *     sslverify           boolean  flag: enable/ disable verification of ssl certificate; default: true (verification is on)
     *
     *     status              integer  test for an expected http status code; if none is given then test fails on status 400 and greater
     *
     *     headercontains      string   test for a string in the http response header; it returns OK if the text was found
     *     headernotcontains   string   test for a string in the http response header; it returns OK if the text was not found
     *     headerregex         string   test for a regex in the http response header; it returns OK if the regex matches; example: "headerregex"=>"/lowercasematch/i"
     *
     *     bodycontains        string   test for a string in the http response body; it returns OK if the text was found
     *     bodynotcontains     string   test for a string in the http response body; it returns OK if the text was not found
     *     bodyregex           string   test for a regex in the http response body; it returns OK if the regex matches; example: "headerregex"=>"/lowercasematch/i"
     * ]
     */
    public function run(array $aParams)
    {
        $this->_checkArrayKeys($aParams, "url");
        if (!function_exists("curl_init")) {
            return [RESULT_UNKNOWN, "UNKNOWN: Unable to perform http test. The php-curl module is not active."];
        }
        $bShowContent = (isset($aParams["content"]) && $aParams["content"]) ? true : false;
        $ch = curl_init($aParams["url"]);

        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, isset($aParams["headeronly"]) && $aParams["headeronly"]);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, isset($aParams["follow"]) && $aParams["follow"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, (isset($aParams["timeout"]) && (int) $aParams["timeout"]) ? (int) $aParams["timeout"] : $this->_iTimeoutTcp);

        if(!($aParams["sslverify"] ?? true)){
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }

        if (isset($aParams["userpwd"])) {
            curl_setopt($ch, CURLOPT_USERPWD, $aParams["userpwd"]);
        }

        $res = curl_exec($ch);

        if (!$res) {
            $iErrorCode = curl_errno($ch);
            $sErrorMsg = curl_error($ch);
            curl_close($ch);
            return [
                RESULT_ERROR,
                'ERROR: failed to fetch ' . $aParams["url"] . ' - curl error #' . $iErrorCode . ': ' . $sErrorMsg
            ];
        }
        $sOut = '';
        $bError = false;

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

        $aTmp = explode("\r\n\r\n", $res, 2);
        $sHttpHeader = $aTmp[0];
        $sHttpBody = $aTmp[1] ?? false;

        // ---------- check functions

        // --- http status code
        $sOut .= "Http status: $aInfos[http_code] - ";
        if (isset($aParams["status"])) {
            if ($aInfos['http_code'] == $aParams["status"]) {
                $sOut .= "as expected - OK";
            } else {
                $sOut .= "compare failed - not equal $aParams[status]";
                $bError = true;
            }
        } else {
            if ($aInfos['http_code'] >= 400) {
                $sOut .= "Error page detected";
                $bError = true;
            } else {
                $sOut .= "request successful";
            }
        }
        // --- http header
        if (isset($aParams["headercontains"]) && $aParams["headercontains"]) {
            $sOut .= "Http header contains '$aParams[headercontains]' - ";
            if (!strstr($sHttpHeader, $aParams["headercontains"]) === false) {
                $sOut .= "compare OK";
            } else {
                $sOut .= "compare failed";
                $bError = true;
            }
        }
        if (isset($aParams["headernotcontains"]) && $aParams["headernotcontains"]) {
            $sOut .= "Http header does not contain '$aParams[headernotcontains]' - ";
            if (strstr($sHttpHeader, $aParams["headernotcontains"]) === false) {
                $sOut .= "compare OK";
            } else {
                $sOut .= "compare failed";
                $bError = true;
            }
        }
        if (isset($aParams["headerregex"]) && $aParams["headerregex"]) {
            $sOut .= "Http header regex test '$aParams[headerregex]' - ";
            try {
                $bRegex = preg_match($aParams["headerregex"], $sHttpHeader);
                if ($bRegex) {
                    $sOut .= "compare OK";
                } else {
                    $sOut .= "compare failed";
                    $bError = true;
                }
            } catch (Exception $e) {
                $sOut .= "Wrong REGEX<br>" . print_r($e, 1);
                $bError = true;
            }
        }
        // --- http body
        if (isset($aParams["bodycontains"]) && $aParams["bodycontains"]) {
            $sOut .= "Http body contains '$aParams[bodycontains]' - ";
            if (!strstr($sHttpBody, $aParams["bodycontains"]) === false) {
                $sOut .= "compare OK";
            } else {
                $sOut .= "compare failed";
                $bError = true;
            }
        }
        if (isset($aParams["bodynotcontains"]) && $aParams["bodynotcontains"]) {
            $sOut .= "Http body does not contain '$aParams[bodynotcontains]' - ";
            if (strstr($sHttpBody, $aParams["bodynotcontains"]) === false) {
                $sOut .= "compare OK";
            } else {
                $sOut .= "compare failed";
                $bError = true;
            }
        }
        if (isset($aParams["bodyregex"]) && $aParams["bodyregex"]) {
            $sOut .= "Http body regex test '$aParams[bodyregex]' - ";
            try {
                $bRegex = preg_match($aParams["bodyregex"], $sHttpBody);
                if ($bRegex) {
                    $sOut .= "compare OK";
                } else {
                    $sOut .= "compare failed";
                    $bError = true;
                }
            } catch (Exception $e) {
                $sOut .= "Wrong REGEX<br>" . print_r($e, 1) . '<br>';
                $bError = true;
            }
        }

        if (!$bError) {
            return [
                RESULT_OK,
                "OK: http check '$aParams[url]'<br>$sOut"
            ];
        } else {
            return [
                RESULT_ERROR,
                "ERROR: http check '$aParams[url]'<br>$sOut"
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
