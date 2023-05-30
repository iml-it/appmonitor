<?php

/**
 * LANG
 * language class
 * - load language file 
 * - get texts by keys - incl. unlimited nested subkeys 
 * --------------------------------------------------------------------------------<br>
 * @author Axel Hahn
 * @link TODO
 * @license GPL
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL 3.0
 * @package IML-Appmonitor
 */
class lang
{

    protected $_sLang = false;   // name of current language
    protected $_aLang = array(); // language texts

    /**
     * constructor
     */
    public function __construct($sLang = '')
    {
        $this->load($sLang);
    }

    // ----------------------------------------------------------------------
    // private functions
    // ----------------------------------------------------------------------
    /**
     * return config dir ... it is one dir up and "config"
     * @return type
     */
    protected function _getConfigDir()
    {
        return dirname(__DIR__) . '/lang';
    }

    // ----------------------------------------------------------------------
    // public functions
    // ----------------------------------------------------------------------

    /**
     * get array with all entriesin language file
     * @return array
     */
    public function getAllEntries()
    {
        return $this->_aLang;
    }

    /**
     * get all available config files as flat array
     * @return array
     */
    public function getAllLanguages()
    {
        $aReturn = array();
        $sDir = $this->_getConfigDir();
        foreach (glob($sDir . "*.json") as $sFile) {
            $aReturn[] = str_replace(".json", "", basename($sFile));
        }
        return $aReturn;
    }

    /**
     * load language texts
     * 
     * @param string $sLang  name of language (without extension "json"
     */
    public function load($sLang)
    {
        $sCfgFile = $this->_getConfigDir() . '/' . $sLang . '.json';
        if (!file_exists($sCfgFile)) {
            die("no lang file " . $sCfgFile);
        } else {
            $this->_aLang = json_decode(file_get_contents($sCfgFile), true);
            $this->_sLang = $sLang;
        }
    }

    /**
     * translate a text with language file
     * @param string $sWord
     * @return string
     */

    /**
     * translate a text with language file and key(s)
     * A found text key in the key has priotity vs global definitions in root level
     * 
     * @param string  $sWord     item to find in language array
     * @param array   $aSubkeys  subkeys to walk in (for nested lang files)
     * @return string
     */
    public function tr($sWord, $aSubkeys = false)
    {
        $aLangBase = $this->_aLang;
        $sTmpPath = '';
        if ($aSubkeys) {
            foreach ($aSubkeys as $sSubkey) {
                $sTmpPath .= ($sTmpPath ? '->' : '') . '[' . $sSubkey . ']';
                if (!isset($aLangBase[$sSubkey])) {
                    return $sWord . ' (unknown path ' . $sTmpPath . ' in ' . $this->_sLang . ')';
                }
                $aLangBase = $aLangBase[$sSubkey];
            }
        }
        // return (array_key_exists($sWord, $this->_aLang)) ? $this->_aLang[$sWord] : $sWord . ' (undefined in ' . $this->_aCfg['lang'] . ')';
        return (array_key_exists($sWord, $aLangBase))
            ? $aLangBase[$sWord]
            : (array_key_exists($sWord, $this->_aLang)
                ? $this->_aLang[$sWord]
                : $sWord . ' (undefined in ' . $sTmpPath . ' ' . $this->_sLang . ')'
            );
    }
}
