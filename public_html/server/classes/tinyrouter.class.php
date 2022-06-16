<?php
/**
 * Axels first router
 * 
 */
class tinyrouter{

    public $sUrl = '';
    public $aRoutes = [];
    public $aMatch = false;

    /**
     * constructor
     * @param  $aRoutes  array   array of routings
     * @param  $sUrl     string  incoming url
     */
    public function __construct($aRoutes=[], $sUrl=false){
        $this->setRoutes($aRoutes);
        $this->setUrl($sUrl);
    }

    /**
     * detect last matching route item
     * if no route matches then it returns false
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
                    if($sPart[0]=="@"){
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
    

    /**
     * set routes
     */
    public function setRoutes($aRoutes=[]){
        if(is_array($aRoutes) && count($aRoutes)){
            $this->aRoutes=$aRoutes;
            $this->_getRoute();
        }
        return true;
    }

    /**
     * set incoming url
     */
    public function setUrl($sUrl){
        $this->sUrl=$sUrl;
        $this->_getRoute();
    }

    /**
     * helper function: get url parts as array
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
     * return the callback of matching route
     * @return string
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
     * @return array
     */
    public function getVar($sVarname){
        return isset($this->aMatch['vars'][$sVarname]) ? $this->aMatch['vars'][$sVarname] : false; 
    }

    /**
     * get an array with next level route entries releative to the current route
     * @return array
     */
    public function getSubitems(){
        $aReturn=[];
        $iCurrent=count($this->getUrlParts());
        foreach($this->aRoutes as $aRoutecfg){
            $sRoute=$this->aMatch['route'][0];
            if(count($this->getUrlParts($sRoute))-1 == $iCurrent && strstr($sRoute, $this->aMatch['route']) ){
                $aReturn[]=basename($sRoute);
            }
        }
        return $aReturn;
    }
}