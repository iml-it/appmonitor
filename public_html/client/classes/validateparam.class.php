<?php
class validateparam {

    protected array $_aRegexDefs=[
        'website' => '/https?:\/\//'
    ];

    protected bool $_flag_debug = false;


    protected function _wd($s)
    {
        if($this->_flag_debug){
            echo "DEBUG: $s<br>\n";
        }
    }

    /**
     * Validate a single value. It returns a string with an errormessage.
     * No error means ok.
     * 
     * @param array $aOpt  Validation rules
     * @param mixed $value value to verify
     * @return string with found error
     */
    public function validateValue($aOpt, $value){
        $sError='';

        // check type
        if(isset($aOpt['type'])){
            $this->_wd("check type $aOpt[type]");
            switch ($aOpt['type']){
                case 'array':
                    if (!is_array($value)){
                        $sError.="Value isn't an array";
                    }
                    break;
                case 'bool':
                    if (!is_bool($value)){
                        $sError.="Value '$value' isn't a bool";
                    }
                    break;
                case 'float':
                    if (!is_float($value)){
                        $sError.="Value isn't a float";
                    }
                    break;
    
                case 'int':
                case 'integer':
                    if (!is_int($value)){
                        $sError.="Value '$value' isn't an integer";
                    } else {
                        if($aOpt['min']??false){
                            if($value<$aOpt['min']){
                                $sError.="Value is too small; minimum is $aOpt[min]";
                            }
                        }
                        if($aOpt['max']??false){
                            if($value>$aOpt['max']){
                                $sError.="Value is too big; maximum is $aOpt[max]";
                            }
                        }
                    }
                    break;
    
                case 'string':
                    if (!is_string($value) ){
                        $sError.="Value '$value' isn't a string";
                    } else {
                        if($aOpt['regex']??false){
                            if(!preg_match($aOpt['regex'], $value)){
                                $sError.="Value is invalid. Regex doesn't match: $aOpt[regex]";
                            }
                        }
                        if(isset($aOpt['oneof']) && is_array($aOpt['oneof'])){
                            if(array_search($value, $aOpt['oneof'])==false){
                                $sError.="Value is invalid. Value doesn't match one of these values ".print_r($aOpt['oneof']);
                            }
                        }
                    }
                    break;

                default:
                    echo "SKIP valdation - unknown type: $aOpt[type]<br>\n";
            }
        }
        // if string: verify regex

        return $sError;
    }

    /**
     * Validate an array of parameter definitions. It returns an array of all error messages
     * No error / an empty array means ok.
     * 
     * @param mixed $aDefs    array with definitions
     * @param mixed $aParams  array of given values
     * @param mixed $bStrict  flag: strict checking to detect wrong keys.
     * @return array with errors
     */
    public function validateArray($aDefs, $aParams, $bStrict = true)
    {
        $aErrors=[];
        // echo "<pre>";
        // print_r($aParams);
        // print_r($aDefs); 
        // echo "<hr>";

        $aTmp=$aParams;
        foreach($aDefs as $sKey => $aOpt){
            unset($aTmp[$sKey]);
            if ($aOpt['required']??false){

                $this->_wd("Check MUST $sKey");
                // verify MUST field
                if(!isset($aParams[$sKey])){
                    $aErrors[$sKey]="Missing required key '$sKey'";
                } else {
                    $this->_wd("MUST field exists: $sKey");
                    $sError=$this->validateValue($aOpt, $aParams[$sKey]);
                    if($sError){
                        $aErrors[$sKey]=$sError;
                    } else {
                        $this->_wd("$sKey was successfully validated.<hr>");
                    }
                    
                }
            }
            if(isset($aOpt['required']) && !$aOpt['required'] && isset($aParams[$sKey])){
                $this->_wd("Check OPTIONAL $sKey");
                $sError=$this->validateValue($aOpt, $aParams[$sKey]);
                if($sError){
                    $aErrors[$sKey]=$sError;
                }
            }
        }
        if($bStrict && count($aTmp)){
            foreach(array_keys($aTmp) as $sKey)
            {
                $aErrors[$sKey]="Invalid key was found: '$sKey'; allowed keys are '".implode("', '", array_keys($aDefs))."'";
            }
        }
        $this->_wd("found errors: " . print_r($aErrors, 1));
        return $aErrors;
    }
}