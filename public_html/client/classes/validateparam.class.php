<?php
class validateparam
{

    /**
     * Summary of _aValidationDefs
     * @var array
     */
    protected array $_aValidationDefs = [];

    protected bool $_flag_debug = false;


    /**
     * Write debug line if _flag_debug is set to true
     * @param string $s  message to show
     * @return void
     */
    protected function _wd(string $s)
    {
        if ($this->_flag_debug) {
            echo "DEBUG: $s<br>\n";
        }
    }

    /**
     * Include the default validation rules defined in validateparam.settings.php
     */
    public function __construct(){
        // IMPORTANT:
        // This style is not so nice like <variable> = include <file>;
        // keep the include line unchanged - it is detected by installer 
        // CLI client project (amcli)
        include 'validateparam.settings.php';
    }

    /**
     * shared validaten checks for floor and integer
     * 
     * @param array $aOpt  Validation rules
     * @param mixed $value value to verify
     * @return string with found error
     */
    protected function _checkCount(array $aOpt, mixed $value)
    {
        $sError = "";

        if ($aOpt['min'] ?? false) {
            if ($value < $aOpt['min']) {
                $sError .= "Value is too small; minimum is $aOpt[min]. ";
            }
        }
        if ($aOpt['max'] ?? false) {
            if ($value > $aOpt['max']) {
                $sError .= "Value is too big; maximum is $aOpt[max]. ";
            }
        }
        if (isset($aOpt['oneof']) && is_array($aOpt['oneof'])) {
            if (array_search($value, $aOpt['oneof']) == false) {
                $sError .= "Value is invalid. Value doesn't match one of these values " . print_r($aOpt['oneof']);
            }
        }

        return $sError;
    }

    /**
     * Validate a single value. It returns a string with an errormessage.
     * No error means ok.
     * 
     * @param array $aOpt  Validation rules
     * @param mixed $value value to verify
     * @return string with found error
     */
    public function validateValue(array $aOpt, mixed $value): string
    {
        $sError = '';

        if (isset($aOpt['validate'])){

            if ($this->_aValidationDefs[$aOpt['validate']] ?? false) {
                $this->_wd("adding options ".print_r($this->_aValidationDefs[$aOpt['validate']],true));
                $aOpt = array_merge($aOpt, $this->_aValidationDefs[$aOpt['validate']]);
            } else {
                $sError .= "Unknown value in 'validate'='$aOpt[validate]'";
            }
        }
        // check type
        if (isset($aOpt['type'])) {
            $this->_wd("check type $aOpt[type]");
            switch ($aOpt['type']) {
                case 'array':
                    if (!is_array($value)) {
                        $sError .= "Value isn't an array";
                    }
                    break;
                case 'bool':
                    if (!is_bool($value)) {
                        $sError .= "Value '$value' isn't a bool";
                    }
                    break;

                case 'float':
                    if (!is_float($value) && !is_int($value)) {
                        $sError .= "Value isn't a float";
                    } else {
                        $sError .= $this->_checkCount($aOpt, $value);
                    }
                    break;

                case 'int':
                case 'integer':
                    if (!is_int($value)) {
                        $sError .= "Value '$value' isn't an integer";
                    } else {
                        $sError .= $this->_checkCount($aOpt, $value);
                    }
                    break;

                case 'string':
                    if (!is_string($value)) {
                        $sError .= "Value '$value' isn't a string";
                    } else {
                        if ($aOpt['regex'] ?? false) {
                            if (!preg_match($aOpt['regex'], $value)) {
                                $sError .= "Value is invalid. Regex doesn't match: $aOpt[regex]";
                            }
                        }
                        if (isset($aOpt['oneof']) && is_array($aOpt['oneof'])) {
                            if (array_search($value, $aOpt['oneof']) == false) {
                                $sError .= "Value is invalid. Value doesn't match one of these values " . print_r($aOpt['oneof']);
                            }
                        }
                    }
                    break;

                default:
                    $sError .= "ERROR Cannot validate unknown type: '$aOpt[type]'<br>\n";
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
    public function validateArray(array $aDefs, array $aParams, bool $bStrict = true)
    {
        $aErrors = [];

        // echo "<pre>";
        // echo "aDefs = "; print_r($aDefs); 
        // echo "aParams = "; print_r($aParams);
        // echo "<hr>";
        if (!count($aDefs)) {
            return ['Defs' => 'No validation rules given.'];
        }

        $aTmp = $aParams;
        foreach ($aDefs as $sKey => $aOpt) {
            unset($aTmp[$sKey]);
            if ($aOpt['required'] ?? false) {

                $this->_wd("Check MUST $sKey");
                // verify MUST field
                if (!isset($aParams[$sKey])) {
                    $aErrors[$sKey] = "Missing required key '$sKey'";
                } else {
                    $this->_wd("MUST field exists: $sKey");
                    $sError = $this->validateValue($aOpt, $aParams[$sKey]);
                    if ($sError) {
                        $aErrors[$sKey] = $sError;
                    } else {
                        $this->_wd("$sKey was successfully validated.<hr>");
                    }

                }
            }
            if (isset($aOpt['required']) && !$aOpt['required'] && isset($aParams[$sKey])) {
                $this->_wd("Check OPTIONAL $sKey");
                $sError = $this->validateValue($aOpt, $aParams[$sKey]);
                if ($sError) {
                    $aErrors[$sKey] = $sError;
                }
            }
        }
        if ($bStrict && isset($aTmp) && count($aTmp)) {
            foreach (array_keys($aTmp) as $sKey) {
                $aErrors[$sKey] = "Invalid key was found: '$sKey'; allowed keys are '" . implode("', '", array_keys($aDefs ?? [])) . "'";
            }
        }
        $this->_wd("found errors: " . print_r($aErrors, 1));
        return $aErrors;
    }
}