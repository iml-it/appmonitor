<?php

class mfaclient
{

    protected array $aConfig = [];
    // protected string $sSessionvarname = "mfaclient";


    // protected array $aLastRequest = [];

    protected string $sUser = "";

    protected bool $bDebug = false;

    /**
     * Intialize mfa client - optional set config and user
     * 
     * @see setConfig
     * @see setUser
     * 
     * @param array $aConfig  optional: configuration with app id and base url
     * @param string $sUser   optional: user id that was logged in
     */
    public function __construct(array $aConfig = [], string $sUser = "")
    {
        if ($aConfig) {
            $this->setConfig($aConfig);
        }
        if ($sUser) {
            $this->setUser($sUser);
        }
    }


    // ----------------------------------------------------------------------
    // private methods
    // ----------------------------------------------------------------------

    /**
     * Make an http get request and return the response body
     * it is called by _makeRequest
     * $aRequest contains subkeys
     * - url               relative urr; part behind api base url
     * - method            one of GET|POST|PUT|DELETE
     * - postdata          for POST only
     * - ignore-ssl-error  flag: if true it willignores ssl verifiction (not recommended)
     * - user, password    authentication with "user:password"
     * 
     * @param array   $aRequest   array with request data
     * @param integer $iTimeout   timeout in seconds
     * @return array ... with subkeys "header" and "body" - or "error" if something went wrong
     */
    protected function _httpRequest(array $aRequest = [], int $iTimeout = 5): array
    {

        if (!function_exists("curl_init")) {
            die("ERROR: PHP CURL module is not installed.");
        }
        // $aConfig = $this->getConfig();

        $ch = curl_init($aRequest['url']);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $aRequest['method']);
        if ($aRequest['method'] === 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $aRequest['postdata']);
        }

        // if ($aConfig['user']) {
        //     curl_setopt($ch, CURLOPT_USERPWD, $aConfig['user'] . ':' . $aConfig['password']);
        // }

        // if (isset($aConfig['ignore-ssl-error']) && $aConfig['ignore-ssl-error']) {
        //     curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        // }

        curl_setopt($ch, CURLOPT_TIMEOUT, $iTimeout);
        curl_setopt($ch, CURLOPT_USERAGENT, 'IML MFA client' . __CLASS__);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // $this->log(__METHOD__ . "Start request $sAwxApiUrl");
        $res = curl_exec($ch);
        if (!$res) {
            $iErrorCode = curl_errno($ch);
            $sErrorMsg = curl_error($ch);
            curl_close($ch);
            return [
                'error' => "Failed to fetch $aRequest[url] - curl error #$iErrorCode: $sErrorMsg"
            ];
        }

        $aReturn = ['info' => curl_getinfo($ch)];
        $aReturn = [];
        curl_close($ch);

        $sHeader = substr($res, 0, curl_getinfo($ch)['header_size']);
        $aReturn['header'] = explode("\n", $sHeader);
        $aReturn['body'] = str_replace($sHeader, "", $res);

        // print_r($aReturn);
        return $aReturn;
    }


    /**
     * Generate a HMAC key
     * 
     * @param string $sMethod
     * @param string $sRequest
     * @param string $sTimestamp
     * @return string
     */
    protected function _getToken(string $sMethod, string $sRequest, string $sTimestamp): string
    {
        return base64_encode(hash_hmac(
            "sha1",
            "{$sMethod}\n{$sRequest}\n{$sTimestamp}",
            $this->aConfig['shared_secret']
        ));
    }

    /**
     * Make an api call to mfa server
     * 
     * @param string $sAction  name of action; one of checks|urls|logout
     * @return array of request and response
     */
    protected function _api($sAction): array
    {
        // $sTimestamp = date("r");
        $sTimestamp = microtime(true);

        $sUrl = $this->aConfig['api'] . "/";
        $sRequest = parse_url($sUrl, PHP_URL_PATH) . '' . parse_url($sUrl, PHP_URL_QUERY);

        $aRequest = [
            "url" => $sUrl,
            "method" => "POST",
            "postdata" => [
                "action" => $sAction,
                "user" => $this->sUser,
                "request" => $sRequest,
                "timestamp" => $sTimestamp,
                "appid" => $this->aConfig['appid'],
                "token" => $this->_getToken("POST", $sRequest, $sTimestamp),
                
                // don't set client ip if gateway ip is needed
                // "ip" => $_SERVER['REMOTE_ADDR']??'',
                
                "useragent" => $_SERVER['HTTP_USER_AGENT']??'',
            ]
        ];
        // store request to see it in debugging output
        $aReturn['request'] = $aRequest;
        $aReturn=[
            'request' => $aRequest,
            'response' => $this->_httpRequest($aRequest),
        ];
        return $aReturn;

    }


    public function jumpform($sUrl, $sSubmit = '<button>Follow me</button>', $sBackUrl='', $sFormId = '')
    {
        // $sTimestamp = date("r");
        $sTimestamp = microtime(true);

        $sRequest = parse_url($sUrl, PHP_URL_PATH) . '' . parse_url($sUrl, PHP_URL_QUERY);

        $sBackUrl = $sBackUrl ?: "http"
            . ""
            . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"
        ;
        $sFormId = $sFormId?:"mfa-form";
        return "<form method=\"POST\" id=\"$sFormId\" action=\"$sUrl\">
                    <input type=\"hidden\" name=\"user\" value=\"" . $this->sUser . "\">
                    <input type=\"hidden\" name=\"appid\" value=\"" . $this->aConfig['appid'] . "\">
                    <input type=\"hidden\" name=\"request\" value=\"$sRequest\">
                    <input type=\"hidden\" name=\"timestamp\" value=\"$sTimestamp\">
                    
                    <input type=\"hidden\" name=\"token\" value=\"" . $this->_getToken("POST", $sRequest, $sTimestamp) . "\">
                    <input type=\"hidden\" name=\"backurl\" value=\"" . $sBackUrl . "\">

                    $sSubmit
                </form>
        ";
    }

    protected function _jump($sUrl, $sSubmit = '<button>Follow me</button>', $sBackUrl=''): string
    {
        $sFormId = "form-" . md5($sBackUrl);
        return $this->jumpform($sUrl, $sSubmit , $sBackUrl, $sFormId )
            ."<script>
                    window.onload = function() {
                        // document.getElementById('$sFormId').submit();
                    }
              </script>";

    }

    protected function _wd(string $sMessage): void
    {
        if($this->bDebug){
            echo __CLASS__ . " - DEBUG: $sMessage<br>\n";
        }
    }

    // ----------------------------------------------------------------------
    // setters
    // ----------------------------------------------------------------------

    /**
     * Enable or disable debugging
     * 
     * @param bool $bDebug  flag: new value for debugging; true = debug enabled
     * @return void
     */
    public function debug(bool $bDebug): void
    {
        $this->bDebug = $bDebug;
    }

    /**
     * Apply a given config with app id and base url
     * 
     * @param array $aConfig  configuration with app id and base url
     * @return void
     */
    public function setConfig(array $aConfig): void
    {
        $this->aConfig = $aConfig;
    }

    /**
     * Set a user id that is logged in
     * 
     * @param string $sUser  user id of current user
     * @return void
     */
    public function setUser(string $sUser)
    {
        $this->sUser = $sUser;
    }

    /**
     * Logout
     * @return void
     */
    public function logout(){
        $this->_api("logout");
        unset($_SESSION['mfa']['user']);
    }

    // ----------------------------------------------------------------------
    // mfa actions
    // ----------------------------------------------------------------------


    public function showHtml($iHttpStatus, $sHtmlcode){
        http_response_code($iHttpStatus);
        die('<!doctype html><html>
        <head><title>MFA server message</title>
        <style>
            body{background:#f8f8f8f; color: #345; font-size: 1.2em; font-family: Arial, Helvetica, sans-serif; margin: 2em;}
            a{color: #44c;}
            button{border-color: 2px solid #ccc ; border-radius: 0.5em; padding: 0.7em;}
            h1{color: #9ab;}
        </style></head>
        <body>'.$sHtmlcode.'</body>
        </html>');
    }

    /**
     * Check MFA server api about user status
     * 
     * @return array
     */
    public function check(): array
    {
        return $this->_api("check");
    }

    /**
     * Check if MFA login is needed and jump to its url
     * @return int
     */
    public function ensure(): int
    {

        if (!isset($_SESSION) || !count($_SESSION)) {
            session_start();
        }
        if (($_SESSION['mfa']['user'] ?? '') == $this->sUser) {
            return 200;
        } else {
            $this->logout();
        }

        $aMfaReturn = $this->check();
        $this->_wd("<pre>DEBUG: ".print_r($aMfaReturn, 1)."</pre>"); 
        // die();
        $aBody = json_decode($aMfaReturn['response']['body'], 1);
        $iHttpStatus = $aBody['status'] ?? -1;

        // die();
        if ($iHttpStatus == 401) {
            $this->showHtml($iHttpStatus, 
                "<h1>MFA server</h1>" . $aBody['message'].'<br><br>'
                .$this->_jump($aBody['url'],'<button>Follow me</button>', )
            );
        }
        if ($iHttpStatus != 200) {
            http_response_code($iHttpStatus);
            $this->showHtml($iHttpStatus, 
                "<h1>MFA server - Error $iHttpStatus</h1>" 
                . ($aBody['error'] ?? 'Invalid response') . "<br>" 
                . ($aBody['message'] ?? 'No valid response was sent back.') . '<br><a href="">Try again</a>'
            );
        }

        $_SESSION['mfa']['user'] = $this->sUser;
        session_write_close();

        return $iHttpStatus;
    }


    /**
     * Open User settings to setup mfa methods
     * @param string $sUrl
     * @param string $sSubmitBtn
     * @return void
     */
    public function openSetup(string $sUrl='', string $sSubmitBtn='<button>MFA Setup</button>', $sBackUrl='')
    {
        if(!$sUrl){
            $aBody=json_decode($this->_api("urls")['response']['body'], 1);
            $sUrl=$aBody['setup']??'';
        }
        if($sUrl){
            $sBackUrl=$sBackUrl?:$_SERVER['HTTP_REFERER'];
            $this->_jump($sUrl, $sSubmitBtn, $sBackUrl);
        }
    }

    /**
     * get list of urls from MFA server
     * 
     * @return array
     */
    public function getUrls(): array
    {
        return $this->_api("urls");
    }


}
