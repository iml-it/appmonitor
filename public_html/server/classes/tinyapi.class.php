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
 * @version 1.0
 * @author Axel Hahn
 * @link https://github.com/iml-it/appmonitor
 * @license GPL
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL 3.0
 * @package -
 * 
**/

namespace iml;
class tinyapi{

    /**
     * @var  array  list of allowes methods
     */
    protected $_aAllowedMethods = [ 'OPTIONS', 'GET', 'PUT', 'POST', 'DELETE' ];
    /**
     * @var  array  list of regex to allow set of ips; empty list = allow all
     */
    protected $_aAllowedIps = [ ];

    /**
     * @var  array  list of allowed users with username and optional password hash; empty list = allow all
     */
    protected $_aAllowedUsers = [ 
        [ '_basicauth' => true ],
        // [ 'api1'        => '[passwordhash1]' ],
        // [ 'apiN'        => '[passwordhashN]' ],
    ];
    
    protected $_aAllowBasicAuth = false;

    // protected $_aHeaders = [ ];

    /**
     * @var  array  response data
     */
    protected $_aData = [];

    /**
     * @var  string  current method
     */
    protected $_sMethod = false;

    
    /**
     * constructor
     * @param  array  $aRequirements  optional: requirements with subkeys
     *                                  methods 
     *                                  ips
     */
    public function __construct($aRequirements=false){
        if (isset($aRequirements['methods'])){
            $this->allowMethods($aRequirements['methods']);
        }
        if (isset($aRequirements['ips'])){
            $this->allowIPs($aRequirements['ips']);
        }
        if (isset($aRequirements['users'])){
            $this->allowUsers($aRequirements['users']);
        }
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
        // Access-Control-Allow-Headers
        return true;
    }


    
    // ----------------------------------------------------------------------
    // STEP 1
    // ensure requirents
    // ----------------------------------------------------------------------


    /**
     * set allowed http methods
     * @param  array  aMethods  array of strings containing GET, PUT, POST, DELETE, OPTIONS
     * @return bool
     */
    public function allowMethods($aMethods){
        if(is_array($aMethods)){
            $this->_aAllowedMethods=$aMethods;
            header("Access-Control-Allow-Methods: " . implode(", ", $this->_aAllowedMethods) );
        }
        return true;
    }
    /**
     * set allowed ip addresses by a given list of regex
     * @param  array  aIpRegex  array of regex
     * @return bool
     */
    public function allowIPs($aIpRegex){
        if(is_array($aIpRegex)){
            $this->_aAllowedIps=$aIpRegex;
        }
        return true;
    }
    /**
     * set allowed users
     * @param  array  aIpRegex  array of regex
     *                 [ '_basicauth' => true|false       ] - allows all users that are authenticated with basic auth
     *                 [ 'apiuser'    => '[passwordhash]' ] - an api user that can send an basic auth header
     * @return bool
     */
    public function allowUsers($aUsers){
        if(is_array($aUsers)){
            $this->_aAllowedUsers=[];
            foreach($aUsers as $aUser){

                $this->_aAllowedUsers[]=['user'=>array_keys($aUser)[0], 'pw'=>array_values($aUser)[0]];
            }
        }

        return true;
    }
    // ----------------------------------------------------------------------

    /**
     * check allowed http methods
     * @param  array  aMethods  optional: array of strings containing GET, PUT, POST, DELETE, OPTIONS
     * @return bool
     */
    public function checkMethod($aMethods=false){
        $this->_sMethod=isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : false;

        if(!$this->_sMethod){
            die("ABORT: http request required.");
        }
        $this->allowMethods($aMethods);
        if (!in_array($this->_sMethod, $this->_aAllowedMethods)){
            $this->sendError(400, 'ERROR: Method '.$this->_sMethod.' is not supported.');
        }
        return true;
    }
    /**
     * check allowed ip addresses by a given list of regex
     * @param  array  aIpRegex  optional: array of regex
     * @return bool
     */
    public function checkIp($aIpRegex=false){
        $this->allowIPs($aIpRegex);

        $sMyIp='';

        // use first found match as ip address
        foreach([ 'REMOTE_ADDR', 'HTTP_FORWARDED_FOR' ] as $sIpKey){
            if(isset($_SERVER[$sIpKey])){
                $sMyIp.=$_SERVER[$sIpKey];
                break;
            }
        }
        if(!$sMyIp){
            die("ABORT: ip address was not detected.");
        }
    
        // allow if no ip was given ... or verify if a list of regex exists
        $bAllowed=count($this->_aAllowedIps) ? false : true;
        foreach($this->_aAllowedIps as $sRegex) {
            if (preg_match("/$sRegex/", $sMyIp)){
                $bAllowed=true;
                break;
            }
        }
        if(!$bAllowed){
            $this->sendError(401, 'ERROR: IP '.$sMyIp.' is not allowed.');
        }

        return true;
    }


    public function checkUser(){

        $aHeaders=apache_request_headers();
        if(is_array($aHeaders) && isset($aHeaders['Authorization'])){
            $sAuthline=preg_replace('/^Basic /','', $aHeaders['Authorization']);
            // $this->sendJson($sAuthline);
            
            $aAuth=explode(':', base64_decode($sAuthline));
            if(is_array($aAuth) && count($aAuth)==2){
                list($sGivenUser, $sGivenPw)=$aAuth;

                foreach($this->_aAllowedUsers as $aLoopUser){
                    $sLoopuser=$aLoopUser['user'];
                    $sPwHash=$aLoopUser['pw'];
                    if($sLoopuser==$sGivenUser && password_verify($sGivenPw, $sPwHash)){
                        return $sLoopuser;
                    }
                }
            }
        } 

        // check if a user ist set with basic auth
        foreach([ 'PHP_AUTH_USER' ] as $sUserkey) {
            if (isset($_SERVER[$sUserkey]) && $this->_aAllowBasicAuth) {
                return $_SERVER[$sUserkey];
            }
        }

        // if no user is set, then allow as anonymous
        if(!$this->_aAllowedUsers || ( is_array($this->_aAllowedUsers) && !count($this->_aAllowedUsers) )){
            return 'anonymous';
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

    public function setData($aData){
        $this->_aData=$aData;
        return true;
    }
    public function appendData($aData, $sKey=false){
        if ($sKey){
            $this->aData[$sKey]=$aData;
        } else {
            $this->aData[]=$aData;
        }
        return true;
    }

    // ----------------------------------------------------------------------
    // send response
    // ----------------------------------------------------------------------

    /**
     * send error message using the sendJson method.
     * @param  integer  $iHttpstatus     http statuscode
     * @param  string   $sErrormessage   string with error message
     */
    public function sendError($iHttpstatus, $sErrormessage){
        return $this->sendJson([
            'http'=>$iHttpstatus, 
            'error'=>$sErrormessage,
        ]);
        die();
    }

    /**
     * send API response:
     * set content type in http response header and transform data to json
     * @param  array  $aData  array of data to send
     */
    public function sendJson($aData=false){
        $_aHeader=[
            '400'=>['header'=>'Bad request'],
            '401'=>['header'=>'Not autorized'],
            '403'=>['header'=>'Forbidden'],
            '404'=>['header'=>'Not Found']
        ];
        if($aData){
            $this->setData($aData);
        }
        header('Content-Type: application/json');
        if(isset($this->_aData['http'])){
            $iStatusCode=$this->_aData['http'];
            if(isset($_aHeader[$iStatusCode]['header'])){
                $this->_aData['_header']='HTTP/1.1 '. $iStatusCode.' '.$_aHeader[$iStatusCode]['header'];
                // do not send non 200 header if auth was sent 
                // header($this->_aData['_header']);
            }
        }
        echo json_encode($this->_aData, JSON_PRETTY_PRINT);
        die();
    }
}