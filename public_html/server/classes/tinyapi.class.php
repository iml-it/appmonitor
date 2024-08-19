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
     * @var  string
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
            $this->allowMethods($aRequirements['methods']);
        }
        if (isset($aRequirements['ips'])) {
            $this->allowIPs($aRequirements['ips']);
        }
        if (isset($aRequirements['users'])) {
            $this->allowUsers($aRequirements['users']);
        }
        if (isset($aRequirements['pretty'])) {
            $this->setPretty($aRequirements['pretty']);
        }
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
        header('Access-Control-Allow-Credentials: true');
        // Access-Control-Allow-Headers
    }



    // ----------------------------------------------------------------------
    // STEP 1
    // ensure requirents
    // ----------------------------------------------------------------------


    /**
     * Set allowed http methods
     * @param  array  aMethods  array of strings containing GET, PUT, POST, DELETE, OPTIONS
     * @return bool
     */
    public function allowMethods(array $aMethods): bool
    {
        $this->_aAllowedMethods = $aMethods;
        if (count($aMethods)){
            header("Access-Control-Allow-Methods: " . implode(", ", $this->_aAllowedMethods));
        }
        return true;
    }

    /**
     * Set allowed ip addresses by a given list of regex
     * @param  array  aIpRegex  array of regex
     * @return bool
     */
    public function allowIPs(array $aIpRegex): bool
    {
        $this->_aAllowedIps = $aIpRegex;
        return true;
    }

    /**
     * Set allowed users
     * @param  array  aUsers  array of allowed users; key= username; value = password hash (BASIC AUTH)
     *                 '*'          =>  false,          - allow anonymous requests
     *                 'apiuser'    => '[passwordhash]' - an api user that can send an basic auth header
     * @return bool
     */
    public function allowUsers(array $aUsers): bool
    {
        $this->_aAllowedUsers = $aUsers;
        return true;
    }
    // ----------------------------------------------------------------------

    /**
     * Check allowed http methods
     * @param  array  aMethods  optional: array of strings containing GET, PUT, POST, DELETE, OPTIONS
     * @return bool
     */
    public function checkMethod(array $aMethods = []): bool
    {
        $this->_sMethod = $_SERVER['REQUEST_METHOD'] ?? false;

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
            $this->sendError(401, 'ERROR: IP ' . $sMyIp . ' is not allowed.');
        }

        return true;
    }


    /**
     * Get an authenticated user and return a detected username as string.
     * Checks are done in that sequence
     * - sent basic auth (user:password); remark it can override the user of a already authenticated user
     * - already made basic auth from $_SERVER
     * - test if anonymous access is allowed
     * Remark: this is a pre check. Your app can make further check like check
     * a role if the found user has access to a function.
     * 
     * @example:
     * $oYourApp->setUser($oTinyApi->checkUser());
     * if (!$oYourApp->hasRole('api')){
     *     $oTinyApi->sendError(403, 'ERROR: Your user has no permission to access the api.');
     *     die();
     * };
     * 
     * @return void|string
     */
    public function checkUser(): string
    {

        // detect a sent basic authentication in request header
        $aHeaders = apache_request_headers();
        if (is_array($aHeaders) && isset($aHeaders['Authorization'])) {
            $sAuthline = preg_replace('/^Basic /', '', $aHeaders['Authorization']);

            $aAuth = explode(':', base64_decode($sAuthline));
            if (is_array($aAuth) && count($aAuth) == 2) {
                list($sGivenUser, $sGivenPw) = $aAuth;

                foreach ($this->_aAllowedUsers as $sLoopuser => $sPwHash) {
                    if ($sLoopuser == $sGivenUser) {
                        if (password_verify($sGivenPw, $sPwHash)) {
                            return $sLoopuser;
                        } else {
                            $this->sendError(403, 'ERROR: Authentication failed.');
                        }
                    }
                }
            }
        }

        // check if a user ist set with basic auth
        foreach (['PHP_AUTH_USER'] as $sUserkey) {
            if (isset($_SERVER[$sUserkey]) && $this->_aAllowBasicAuth) {
                return $_SERVER[$sUserkey];
            }
        }

        // if no user is set, then check as anonymous
        // allow i no user was set ... or user '*' was found
        if (
            !$this->_aAllowedUsers
            || (is_array($this->_aAllowedUsers) && !count($this->_aAllowedUsers))
            || isset($this->_aAllowedUsers['*'])
        ) {
            return '*';
        }

        $this->sendError(403, 'ERROR: A valid user is required.');
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
     * @param  mixed   $data   additional response data
     * @param  string  $sKey   optional: use key 
     * @return boolean
     */
    public function appendData(mixed $aData, string $sKey = ''): bool
    {
        if ($sKey){
            $this->_aData[$sKey] = $aData;
        } else {
            $this->_aData[] = $aData;
        }
        return true;
    }

    /**
     * Set response data; "should" be an array
     * @param  array  $aData  response data
     * @return boolean
     */
    public function setPretty(bool $bPretty): bool
    {
        return $this->_bPretty = !!$bPretty;
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
                $this->_aData['_header'] = 'HTTP/1.1 ' . $iStatusCode . ' ' . $_aHeader[$iStatusCode]['header'];
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