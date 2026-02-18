<?php
/**
 * 
 * TINYAPI
 * 
 * --------------------------------------------------------------------------------<br>
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
 * @version 1.1
 * @author Axel Hahn
 * @link https://github.com/iml-it/appmonitor
 * @license GPL
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL 3.0
 * @package -
 * 
 * 2024-07-18  axel.hahn@unibe.ch  php 8 only: use typed variables
 * 2024-11-14  axel.hahn@unibe.ch  API access with basic auth and hmac hash key
 * 2024-11-15  axel.hahn@unibe.ch  Update hmac hash key; send 401 on authenttication error (before: 403)
 * 2026-02-18  axel.hahn@unibe.ch  do not force http1.1 in response; linting stuff
 **/

namespace iml;

class tinyapi
{

    /**
     * List of allowed methods
     * @var array
     */
    protected array $_aAllowedMethods = ['OPTIONS', 'GET', 'PUT', 'POST', 'DELETE'];

    /**
     * List of regex to allow set of ips; empty list = allow all
     * @var  array
     */
    protected array $_aAllowedIps = [];

    /**
     * List of allowed users with username and optional password hash; empty list = allow all
     * @var array
     */
    protected array $_aAllowedUsers = [
        ['*' => false],          // anonymous requests
        // [ '_' => false ],       // take authenticated user from $_SERVER environemnt

        // OR
        // a client directly sends basic auth data

        // [ 'api1'        => '[passwordhash1]' ],
        // [ 'apiN'        => '[passwordhashN]' ],
    ];

    /**
     * Flag if basic auth is allowed
     * @var bool
     */
    protected bool $_aAllowBasicAuth = true;

    // protected $_aHeaders = [ ];

    /**
     * Response data
     * @var  array
     */
    protected array $_aData = [];

    /**
     * Current method
     * @var string
     */
    protected string $_sMethod = '';

    /**
     * Prettyfy json
     * @var  bool
     */
    protected bool $_bPretty = false;

    /**
     * constructor
     * @param  array  $aRequirements  optional: requirements with subkeys
     *                                  methods 
     *                                  ips
     */
    public function __construct(array $aRequirements = [])
    {
        if (isset($aRequirements['methods'])) {
            $this->allowMethods((array) $aRequirements['methods']);
        }
        if (isset($aRequirements['ips'])) {
            $this->allowIPs((array) $aRequirements['ips']);
        }
        if (isset($aRequirements['users'])) {
            $this->allowUsers((array) $aRequirements['users']);
        }
        if (isset($aRequirements['pretty'])) {
            $this->setPretty((bool) $aRequirements['pretty']);
        }
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
        header('Access-Control-Allow-Credentials: true');
        // Access-Control-Allow-Headers
    }


    protected function _addDebug($sMessage)
    {
        static $i = 0;
        $i++;
        // header("DEBUG-".str_pad($i, 2, '0', STR_PAD_LEFT).": $s");
        header("DEBUG-$i: $sMessage");
    }

    // ----------------------------------------------------------------------
    // STEP 1
    // ensure requirents
    // ----------------------------------------------------------------------


    /**
     * Set allowed http methods
     * @param array  $aMethods  array of strings containing GET, PUT, POST, DELETE, OPTIONS
     * @return bool
     */
    public function allowMethods(array $aMethods): bool
    {
        $this->_aAllowedMethods = $aMethods;
        if (count($aMethods)) {
            header("Access-Control-Allow-Methods: " . implode(", ", $aMethods));
        }
        return true;
    }

    /**
     * Set allowed ip addresses by a given list of regex
     * @param  array  $aIpRegex  array of regex
     * @return bool
     */
    public function allowIPs(array $aIpRegex): bool
    {
        $this->_aAllowedIps = $aIpRegex;
        return true;
    }

    /**
     * Set allowed users
     * @param  array  $aUsers  array of allowed users; key= username ('*' or userid); subkeys: 
     *                        - 'password'; value = password hash (BASIC AUTH) and/ or 
     *                        - 'secret'; clear text for hmac
     * @return bool
     */
    public function allowUsers(array $aUsers): bool
    {
        // echo "DEBUG: " .  __METHOD__ . "(".print_r($aUsers).")";
        $this->_aAllowedUsers = $aUsers;
        return true;
    }
    // ----------------------------------------------------------------------

    /**
     * Check allowed http methods
     * @param  array  $aMethods  optional: array of strings containing GET, PUT, POST, DELETE, OPTIONS
     * @return bool
     */
    public function checkMethod(array $aMethods = []): bool
    {
        $this->_sMethod = $_SERVER['REQUEST_METHOD'] ?? '';

        if (!$this->_sMethod) {
            die("ABORT: http request required.");
        }
        // $this->allowMethods($aMethods);
        if (!in_array($this->_sMethod, $this->_aAllowedMethods)) {
            $this->sendError(400, 'ERROR: Method ' . $this->_sMethod . ' is not supported.');
        }
        return true;
    }
    /**
     * Check allowed ip addresses by a given list of regex.
     * It aborts if no ip address was detected.
     * If access is not allowed it sends a 401 header and aborts.
     * 
     * @return bool
     */
    public function checkIp(): bool
    {

        $sMyIp = '';

        // use first found match as ip address
        foreach (['REMOTE_ADDR', 'HTTP_FORWARDED_FOR'] as $sIpKey) {
            if (isset($_SERVER[$sIpKey])) {
                $sMyIp .= $_SERVER[$sIpKey];
                break;
            }
        }
        if (!$sMyIp) {
            die("ABORT: ip address was not detected.");
        }

        // allow if no ip was given ... or verify if a list of regex exists
        $bAllowed = count($this->_aAllowedIps) ? false : true;
        foreach ($this->_aAllowedIps as $sRegex) {
            if (preg_match("/$sRegex/", $sMyIp)) {
                $bAllowed = true;
                break;
            }
        }
        if (!$bAllowed) {
            $this->sendError(403, 'ERROR: IP ' . $sMyIp . ' is not allowed.');
        }

        return true;
    }


    /**
     * Get an authenticated user and return a detected username as string.
     * Checks are done in that sequence
     * - sent basic auth (base64 encoded <user>:<password>); remark it can override the user of a already authenticated user
     * - sent generated hmac hashsum(<user>:<key>)
     * - already made basic auth from browser
     * - test if anonymous access is allowed
     * Remark: this is a pre check. Your app can make further check like check
     * a role if the found user has access to a function.
     * 
     * @example:
     * $oYourApp->setUser($oTinyApi->checkUser());
     * 
     * @return string
     */
    public function checkUser(): string
    {
        if (
            isset($this->_aAllowedUsers['*'])
            && isset($this->_aAllowedUsers['*']['password'])
            && ($this->_aAllowedUsers['*']['password']??'....') === false
        ) {
            return '*';
        }

        // detect a sent basic authentication in request header
        $aHeaders = apache_request_headers();

        if (is_array($aHeaders) && isset($aHeaders['Authorization'])) {

            $sAuthline = $aHeaders['Authorization'];
            $sAuthtype = false;

            if (strstr($sAuthline, ' ')) {
                list($sAuthtype, $sAuthline) = explode(' ', $sAuthline);
                if ($sAuthtype == "Basic") {
                    $sAuthline = base64_decode($sAuthline);
                }
            }

            $aAuth = explode(':', (string) $sAuthline);
            // $this->_addDebug("auth line -> $sAuthline");

            if (is_array($aAuth) && count($aAuth) == 2) {
                list($sGivenUser, $sGivenKey) = $aAuth;
                // $this->_addDebug("auth type -> '$sAuthtype' .. $sGivenUser : $sGivenKey");
                $sSelUser = isset($this->_aAllowedUsers[$sGivenUser]) ? $sGivenUser : (isset($this->_aAllowedUsers['*']) ? '*' : false);
                if ($sSelUser) {
                    $aSelUserdata = $this->_aAllowedUsers[$sSelUser];


                    switch ($sAuthtype) {
                        case 'Basic':
                            if (
                                isset($aSelUserdata['passwordhash'])
                                // php -r "echo password_hash('<password>', PASSWORD_DEFAULT);"
                                && password_verify($sGivenKey, $aSelUserdata['passwordhash'])
                            ) {
                                // $this->_addDebug("auth type -> '$sAuthtype' .. $sGivenUser : found password hash");
                                return $sSelUser;
                            } else if (isset($aSelUserdata['password']) && $aSelUserdata['password'] == $sGivenKey) {
                                // $this->_addDebug("auth type -> '$sAuthtype' .. $sGivenUser : found password");
                                return $sSelUser;
                            } else {
                                $this->sendError(401, 'ERROR: Basic authentication failed. Wrong password.');
                            }
                            ;
                            ;
                        case 'HMAC-SHA1':
                        default:
                            // check hmac hash ... rebuild it with key of found user
                            if (isset($aSelUserdata['secret'])) {

                                $sGotDate = $aHeaders['Date'] ?? '<missing Date>';
                                $sGotMethod = $_SERVER['REQUEST_METHOD'];
                                $sGotReq = $_SERVER['REQUEST_URI'];

                                $sMyData = "{$sGotMethod}\n{$sGotReq}\n{$sGotDate}";
                                $sMyHash = base64_encode(hash_hmac("sha1", $sMyData, $aSelUserdata['secret']));
                                // $this->_addDebug("hash source: {$sGotDate}");

                                if ($sMyHash !== $sGivenKey) {
                                    // $this->_addDebug("hash is not $sMyHash");
                                    $this->sendError(401, 'ERROR: Authorization failed. Wrong hmac key.');
                                } else {
                                    return $sSelUser;
                                }
                            }

                    }

                }
            }
        }

        // check if a user ist set with basic auth
        foreach (['PHP_AUTH_USER'] as $sUserkey) {
            if (isset($_SERVER[$sUserkey]) && $this->_aAllowBasicAuth) {
                return (string) $_SERVER[$sUserkey];
            }
        }

        $this->sendError(401, 'ERROR: A valid user is required.');
        return 'no-return-value';
    }

    // ----------------------------------------------------------------------
    // STEP 2
    // get data from app
    // ----------------------------------------------------------------------


    // ----------------------------------------------------------------------
    // STEP 3
    // set/ append response data
    // ----------------------------------------------------------------------

    /**
     * Set response data; "should" be an array
     * @param  array  $aData  response data
     * @return boolean
     */
    public function setData(array $aData): bool
    {
        $this->_aData = $aData;
        return true;
    }

    /**
     * Append response data
     * If no key as 2nd param is given the given array will be added as new array element.
     * With a given key the key will be used to set data (existing key will be replaced)
     * 
     * @param  mixed   $aData  additional response data
     * @param  string  $sKey   optional: use key 
     * @return boolean
     */
    public function appendData(mixed $aData, string $sKey = ''): bool
    {
        if ($sKey) {
            $this->_aData[$sKey] = $aData;
        } else {
            $this->_aData[] = $aData;
        }
        return true;
    }

    /**
     * Prettyfy JSON response
     * @param  bool  $bPretty  flag: bool to prettyfy; true=yes; false=send compressed data
     * @return boolean
     */
    public function setPretty(bool $bPretty): bool
    {
        return $this->_bPretty = $bPretty;
    }

    // ----------------------------------------------------------------------
    // send response
    // ----------------------------------------------------------------------

    /**
     * If http method is OPTIONS, send json and stop.
     * @return void
     */
    public function stopIfOptions(): void
    {
        if ($this->_sMethod == 'OPTIONS') {
            $this->sendJson();
        }
    }

    /**
     * Send error message using the sendJson method.
     * @param  integer  $iHttpstatus     http statuscode
     * @param  string   $sErrormessage   string with error message
     * @return void
     */
    public function sendError(int $iHttpstatus, string $sErrormessage): void
    {
        $this->sendJson([
            'http' => $iHttpstatus,
            'error' => $sErrormessage,
        ]);
    }

    /**
     * Send API response:
     * set content type in http response header and transform data to json
     * and stop.
     * @param  array  $aData  array of data to send
     * @return void
     */
    public function sendJson(array $aData = []): void
    {
        $_aHeader = [
            '400' => ['header' => 'Bad request'],
            '401' => ['header' => 'Not autorized'],
            '403' => ['header' => 'Forbidden'],
            '404' => ['header' => 'Not Found']
        ];
        if (count($aData)) {
            $this->setData($aData);
        }
        header('Content-Type: application/json');
        if (isset($this->_aData['http'])) {
            $iStatusCode = $this->_aData['http'];
            if (isset($_aHeader[$iStatusCode]['header'])) {
                $this->_aData['_header'] = $_SERVER['SERVER_PROTOCOL'] . " $iStatusCode ".(string) $_aHeader[$iStatusCode]['header'];
                // do not send non 200 header if method is OPTIONS
                if ($this->_sMethod !== 'OPTIONS') {
                    header($this->_aData['_header']);
                }
            }
        }
        $iJsonOptions = $this->_bPretty ? JSON_PRETTY_PRINT : 0;
        echo json_encode($this->_aData, $iJsonOptions);
        die();
    }
}