<?php
/**
 * 
 * IML TINY ROUTER
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
 * @version 1.3
 * @author Axel Hahn
 * @link https://git-repo.iml.unibe.ch/iml-open-source/tinyrouter-php-class
 * @license GPL
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL 3.0
 * @package -
 * 
**/

namespace iml;
class tinyrouter{

    /**
     * requested url
     * @var string
     */
    public $sUrl = '';

    /**
     * used http method
     * @var string
     */
    public $sMethod = '';

    /**
     * array of defined routes
     * @var array
     */
    public $aRoutes = [];

    /**
     * array of matching route and solved vars
     * @var array
     */
    protected $aMatch=[];

    /**
     * constructor
     * @param  $aRoutes  array   array of routes
     * @param  $sUrl     string  incoming url
     * @return boolean
     */
    public function __construct($aRoutes=[],$sUrl=false){
        $this->setRoutes($aRoutes);
        $this->setUrl($sUrl);
        return true;
    }

    // ----------------------------------------------------------------------
    // protected functions
    // ----------------------------------------------------------------------

    /**
     * detect last matching route item
     * if no route matches then it returns false
     * @return array
     */
    protected function _getRoute(){
        $aReturn=[];
        $this->aMatch=[];
        if(!$this->sUrl || !count($this->aRoutes)){
            return false;
        }
        $aReqParts=$this->getUrlParts();
        foreach($this->aRoutes as $aRoutecfg){
            $sRoute=$aRoutecfg[0];
            $aParts=$this->getUrlParts($sRoute);
            if (count($aParts) == count($aReqParts)){
                $iPart=0;
                $aVars=[];
                $bFoundRoute=false;
                foreach($aParts as $sPart){
                    // detect @varname or @varname:regex in a routing
                    if(isset($sPart[0]) && $sPart[0]=="@"){
                        $sValue=$aReqParts[$iPart];
                        preg_match('/\@([a-z]*):(.*)/', $sPart, $match);

                        if(isset($match[2])){
                            // if a given regex does not match the value then abort
                            if(!preg_match("/^$match[2]$/", $sValue)){
                                $bFoundRoute=false;
                                break;
                            }
                        }
                        // store a variable without starting @
                        $aVars[$match[1]]=$sValue;
                    } else {
                        // no @ ... if string of url parts in url and route
                        // do not match then we abort
                        if($aParts[$iPart]!==$aReqParts[$iPart]){
                            $bFoundRoute=false;
                            break;
                        }
                    }
                    $bFoundRoute=true;
                    // $aVars[$iPart]=$sValue;
                    $iPart++;
                }
                if($bFoundRoute){
                    $aReturn=[
                        "request-method"=>(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : false),
                        "request-url"=>$this->sUrl,
                        "route"=>$aRoutecfg[0],
                        "callback"=>$aRoutecfg[1],
                        "vars"=>$aVars
                    ];
                }
            }
        }
        $this->aMatch=count($aReturn) ? $aReturn : false;
        return $this->aMatch;
    }
    
    // ----------------------------------------------------------------------
    // public functions :: setter
    // ----------------------------------------------------------------------

    /**
     * set routes
     * @param  array  $aRoutes  list of [ route, more params ... ]
     * @return boolean
     */
    public function setRoutes($aRoutes=[]){
        if(is_array($aRoutes) && count($aRoutes)){
            $this->aRoutes=$aRoutes;
            $this->_getRoute();
        }
        return true;
    }

    /**
     * set incoming url, add the request behind protocol and domain.
     * @param  string  sUrl  url to fetch; /api/v1/productbyid/3424084
     * @return boolean
     */
    public function setUrl($sUrl){
        $this->sUrl=$sUrl;
        $this->_getRoute();
        return true;
    }

    // ----------------------------------------------------------------------
    // public functions :: getter
    // ----------------------------------------------------------------------

    /**
     * helper function: get url request parts as array
     * @param  string  $sUrl  url to handle; /api/v1/productbyid/3424084
     * @returns array
     */
    public function getUrlParts($sUrl=false){
        if(!$sUrl){
            $sUrl=$this->sUrl;
        }
        $aReqParts=explode('/', $sUrl);
        if ($sUrl[0]=='/'){
            array_shift($aReqParts);
        }
        return $aReqParts;
    }


    /**
     * detect last matching route item
     * if no route matches then it returns false
     * @return array|bool
     */
    public function getRoute(){
        return $this->aMatch;
    }

    /**
     * return the callback iten of the matching route
     * If no route was matching it returns false
     * @return {*}
     */
    public function getCallback(){
        return isset($this->aMatch['callback']) ? $this->aMatch['callback'] : false; 
    }
    /**
     * return the variables as keys in route parts with starting @ character
     * @return array
     */
    public function getVars(){
        return isset($this->aMatch['vars']) ? $this->aMatch['vars'] : false; 
    }
    /**
     * return the variables as keys in route parts with starting @ character
     * @return string
     */
    public function getVar($sVarname){
        return isset($this->aMatch['vars'][$sVarname]) ? $this->aMatch['vars'][$sVarname] : false; 
    }

    /**
     * get an array with next level route entries releative to the current route
     * @return array
     */
    public function getSubitems(){
        $sKey='allowed_subkeys';
        $aReturn=[$sKey=>[]];
        $iCurrent=count($this->getUrlParts());
        foreach($this->aRoutes as $aRoutecfg){
            $sRoute=$aRoutecfg[0];
            if(count($this->getUrlParts($sRoute))-1 == $iCurrent && strstr($sRoute, $this->aMatch['route']) ){
                $aReturn[$sKey][]=basename($sRoute);
            }
        }
        return $aReturn;
    }
}