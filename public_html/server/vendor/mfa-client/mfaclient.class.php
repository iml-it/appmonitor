<?php

/**
 * 
 * MFA CLIENT CLASS
 * 
 * Connect a web app with MFA server
 * 
 * Source: https://git-repo.iml.unibe.ch/iml-open-source/
 * Docs: https://os-docs.iml.unibe.ch/mfa-client/index.html
 * License: GNU GPL 3.0
 * 
 * 2025-06-11  <axel.hahn@unibe.ch>  initial version
 * 2025-06-30  <axel.hahn@unibe.ch>  set version 1.0.1 in user agenmt in http requests
 */
class mfaclient
{

    protected string $_sVersion = "1.0.1";

    protected array $aConfig = [];
    // protected string $sSessionvarname = "mfaclient";


    // protected array $aLastRequest = [];

    protected string $sUser = "";

    protected bool $bDebug = false;

    protected array $aStatus = [];

    /**
     * Intialize mfa client - optional set config and user
     * 
     * @see setConfig
     * @see setUser
     * 
     * @param array $aConfig  optional: configuration with app id and base url
     */
    public function __construct(array $aConfig = [])
    {
        $this->loadConfig();
        if ($aConfig) {
            $this->setConfig($aConfig);
        }
        $this->setUser($this->aConfig['user']??'');
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
        curl_setopt($ch, CURLOPT_USERAGENT, "IML MFA client PHP v$this->_sVersion");
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
     * @param string $sMethod     http method, eg POST
     * @param string $sRequest    request path
     * @param string $sTimestamp  timestamp
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
    protected function _api(string $sAction): array
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
                "username" => $this->sUser,
                "request" => $sRequest,
                "timestamp" => $sTimestamp,
                "appid" => $this->aConfig['appid'],
                "token" => $this->_getToken("POST", $sRequest, $sTimestamp),

                // don't set client ip if gateway ip is needed
                "ip" => $this->getClientIp(),

                "useragent" => $_SERVER['HTTP_USER_AGENT'] ?? '',
            ]
        ];

        $aReturn['request'] = $aRequest;
        $aReturn = [
            'request' => $aRequest,
            'response' => $this->_httpRequest($aRequest),
        ];
        return $aReturn;

    }

    /**
     * Generate html code for jump form.
     * With it a user can jump from current app to mfa server to setup mfa 
     * methods or solve a challenge
     * 
     * @param string $sUrl      url to jump (mfa server setup page or page to solve challenge)
     * @param string $sSubmit   html code for a submit button
     * @param string $sBackUrl  url to return from mfa server to the application
     * @param string $sFormId   form id
     * @return string
     */
    public function jumpform(string $sUrl, string $sSubmit = '<button>Follow me</button>', string $sBackUrl = '', string $sFormId = ''): string
    {
        // $sTimestamp = date("r");
        $sTimestamp = microtime(true); // microtime to have more uniqueness on milliseconds

        $sRequest = parse_url($sUrl, PHP_URL_PATH) . '?' . parse_url($sUrl, PHP_URL_QUERY);

        $sBackUrl = $sBackUrl ?: "http"
            . ""
            . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"
        ;
        $sFormId = $sFormId ?: "mfa-form";
        $sReturn = "<form method=\"POST\" id=\"$sFormId\" action=\"$sUrl\">
                    <input type=\"hidden\" name=\"username\" value=\"" . $this->sUser . "\">
                    <input type=\"hidden\" name=\"appid\" value=\"" . $this->aConfig['appid'] . "\">
                    <input type=\"hidden\" name=\"ip\" value=\"" . $this->getClientIp() . "\">

                    <input type=\"hidden\" name=\"request\" value=\"$sRequest\">
                    <input type=\"hidden\" name=\"timestamp\" value=\"$sTimestamp\">
                    
                    <input type=\"hidden\" name=\"token\" value=\"" . $this->_getToken("POST", $sRequest, $sTimestamp) . "\">
                    <input type=\"hidden\" name=\"backurl\" value=\"" . $sBackUrl . "\">

                    $sSubmit
                </form>
        ";
        $this->_wd(__METHOD__ . '<br>Html code of form with jump button:<pre>' . htmlentities($sReturn) . '</pre>');
        return $sReturn;
    }

    /**
     * Initiate a smooth direct jump from current app to mfa server to setup mfa 
     * methods or solve a challenge.
     * It uses jumpform() to render a form and adds a javascript for an automatic 
     * submit.
     * 
     * @see jumpform()
     * 
     * @param string $sUrl      url to jump (mfa server setup page or page to solve challenge)
     * @param string $sSubmit   html code for a submit button
     * @param string $sBackUrl  url to return from mfa server to the application
     * @return string
     */
    protected function _jump(string $sUrl, string $sSubmit = '<button>Follow me</button>', string $sBackUrl = ''): string
    {
        $sFormId = "form-" . md5($sBackUrl);
        return $this->jumpform($sUrl, $sSubmit, $sBackUrl, $sFormId)
            . ($this->bDebug
                ? ''
                : "<script>
                    window.onload = function() {
                        document.getElementById('$sFormId').submit();
                    }
                </script>"
            )
        ;

    }

    /**
     * Write dubug output.
     * Debug mode must be enabled first.
     * $o->debug(true);
     * 
     * @see debug()
     * 
     * @param string $sMessage
     * @return void
     */
    protected function _wd(string $sMessage): void
    {
        if ($this->bDebug) {
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
     * Load configuration file from current directory
     * @return void
     */
    public function loadConfig(): void
    {
        $sCfgfile= __DIR__ . '/mfaconfig.php';
        if (file_exists($sCfgfile)) {
            $aTmp = include $sCfgfile;
            $this->aConfig = $aTmp??[];
        }
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
     * Logout; unset user in session scope
     *
     * @return void
     */
    public function logout()
    {
        unset($_SESSION['mfa']['user']);
    }

    // ----------------------------------------------------------------------
    // mfa actions
    // ----------------------------------------------------------------------


    /**
     * Show html message and abort to prevent visibility of the app without 
     * solved mfa
     * 
     * @param int $iHttpStatus   http statuscode to set
     * @param string $sHtmlcode  http body to show
     * @return never
     */
    public function showHtml(int $iHttpStatus, string $sHtmlcode)
    {
        if ($this->bDebug) {
            echo "Remark: Cannot set http status [$iHttpStatus] because of debug output<hr>";
        } else {
            http_response_code($iHttpStatus);
        }
        die('<!doctype html><html>
        <head><title>MFA server message</title>
        <style>
            body{background:#f0f5f8; color: #335; font-size: 1.2em; font-family: Arial, Helvetica, sans-serif;}
            a{color: #44c;}
            button{border-color: 2px solid #ccc ; border-radius: 0.5em; padding: 0.7em;}
            div{background:#fff; border-radius: 1em; box-shadow: 0 0 1em #ccc; margin: 4em auto; max-width: 600px; padding: 2em;}
            h1{margin: 0 0 1em;;}
        </style></head>
        <body><div>' . $sHtmlcode . '</div></body>
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
            $this->aStatus[] = 'User still has a valid session after solving a challenge.';
            return 200;
        } else {
            $this->logout();
        }

        foreach(['api', 'appid', 'shared_secret', 'user'] as $sKey){
            if(!isset($this->aConfig[$sKey])){
                $this->aStatus[] = "Skip: Key '$sKey' was not set in config.";
                return 200;
            }
            if(!$this->aConfig[$sKey]){
                $this->aStatus[] = "Skip: Key '$sKey' is empty in config.";
                return 200;
            }
        }


        $aMfaReturn = $this->check();
        $this->_wd(__METHOD__ . "<br>Http request to mfa api<pre>" . print_r($aMfaReturn, 1) . "</pre>");
        $aBody = json_decode($aMfaReturn['response']['body'] ?? '', 1);
        $iHttpStatus = $aBody['status'] ?? -1;

        if ($iHttpStatus == 401) {
            $this->showHtml(
                $iHttpStatus,
                "<h1>MFA server</h1>"
                . "⚠️ " . $aBody['message'] . '<br><br>'
                . $this->_jump($aBody['url'], '<button>Follow me</button>', )
            );
        }
        if ($iHttpStatus != 200) {
            $this->showHtml(
                $iHttpStatus,
                "<h1>MFA server - Error $iHttpStatus</h1>"
                . "❌ <strong>" . ($aBody['error'] ?? 'Invalid API response') . "</strong><br>"
                . ($aBody['message'] ?? 'No valid JSON response was sent back.') . '<br>'
                . ($aMfaReturn['response']['header'][0] ?? '') . '<br>'
                . (($aMfaReturn['response']['error'] ?? '') ? '<br><strong>Curl error:</strong><br>' . $aMfaReturn['response']['error'] . '<br>' : '')
                . '<br><br><a href="">Try again</a>'
                //.'<br><pre>'.print_r($aMfaReturn, 1).'</pre>'
            );
        }

        $this->aStatus[] = 'User solved the session now.';

        $_SESSION['mfa']['user'] = $this->sUser;
        session_write_close();

        return $iHttpStatus;
    }


    /**
     * Get an html button to open mfa setup page
     * 
     * @param string $sSubmitBtn  optional: html code for a submit button; default: '<button>MFA Setup</button>'
     * @param string $sBackUrl    optional: url to return from mfa server to the application; default: current url
     * @return void
     */
    public function getButtonSetup(string $sSubmitBtn = '<button>MFA Setup</button>', $sBackUrl = ''): string
    {
        $aBody = json_decode($this->_api("urls")['response']['body'], 1);
        // print_r($aBody);
        $sUrl = $aBody['setup'] ?? '';
        if ($sUrl) {
            $sBackUrl = $sBackUrl ?: ( "http".(($_SERVER['HTTPS']??'') === 'on' ? "s" : "")."://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
            return $this->jumpform($sUrl, $sSubmitBtn, $sBackUrl);
        } else {
            return $aBody['message']??'';
        }
    }


    /**
     * Open User settings to setup mfa methods
     * 
     * @param string $sUrl        url to open
     * @param string $sSubmitBtn  html code for a submit button
     * @return void
    public function openSetup(string $sUrl = '', string $sSubmitBtn = '<button>MFA Setup</button>', $sBackUrl = '')
    {
        if (!$sUrl) {
            $aBody = json_decode($this->_api("urls")['response']['body'], 1);
            $sUrl = $aBody['setup'] ?? '';
        }
        if ($sUrl) {
            $sBackUrl = $sBackUrl ?: $_SERVER['HTTP_REFERER'];
            $this->_jump($sUrl, $sSubmitBtn, $sBackUrl);
        }
    }
     */

    /**
     * Get IP of current client (to be sent to MFA server)
     * @return string
     */
    public function getClientIp(): string
    {
        $ipaddress = '';
        foreach([
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
            'REMOTE_HOST'
        ] as $sKey){
            if (getenv($sKey))
                $ipaddress = getenv($sKey);
        }
        if (!$ipaddress) {
            $ipaddress = 'UNKNOWN';
        }
        return $ipaddress;
    }

    /**
     * return current config
     * @return array
     */
    public function getConfig(): array
    {
        return $this->aConfig;
    }

    /**
     * return current status
     * @return array
     */
    public function getStatus(): array
    {
        return $this->aStatus;
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

    /**
     * show current status if you want to find out why mfa was skipped
     * @example <code>echo $mfa->showStatus();</code>
     * @return string
     */
    public function showStatus(): string
    {
        return 'MFA status: <ul><li>'
            . implode('</li><li>', $this->aStatus)
            .'</li></ul>'
            ;
    }
}
