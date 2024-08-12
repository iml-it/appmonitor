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
 * 
 * 2024-07-19  axel.hahn@unibe.ch  php 8 only: use typed variables
 */
class lang
{

    /**
     * Current language
     * @var 
     */
    protected string $_sLang = '';

    /**
     * Array of language texts
     * @var array
     */
    protected array $_aLang = [];

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
     * @return string
     */
    protected function _getConfigDir(): string
    {
        return dirname(__DIR__) . '/lang';
    }

    // ----------------------------------------------------------------------
    // public functions
    // ----------------------------------------------------------------------

    /**
     * Get array with all entries in language file
     * @return array
     */
    public function getAllEntries(): array
    {
        return $this->_aLang;
    }

    /**
     * Get all available config files as flat array
     * @return array
     */
    public function getAllLanguages(): array
    {
        $aReturn = [];
        $sDir = $this->_getConfigDir();
        foreach (glob("$sDir/*.json") as $sFile) {
            $aReturn[] = str_replace(".json", "", basename($sFile));
        }
        return $aReturn;
    }

    /**
     * Load language texts with given language name. 
     * It loads the texts from the language json file and updates the value 
     * for current language.
     * It returns false if the given language doesn't exist
     * 
     * @param string $sLang  name of language (without extension "json")
     * @return boolean
     */
    public function load(string $sLang): bool
    {
        $sCfgFile = $this->_getConfigDir() . '/' . $sLang . '.json';
        if (!file_exists($sCfgFile)) {
            return false;
        } else {
            $this->_aLang = json_decode(file_get_contents($sCfgFile), true);
            $this->_sLang = $sLang;
            return true;
        }
    }

    /**
     * Translate a text with language file and key(s)
     * A found text key in the key has priotity vs global definitions in root level
     * 
     * @param string  $sWord     item to find in language array
     * @param array   $aSubkeys  subkeys to walk in (for nested lang files); eg ["gui"]
     * @return string
     */
    public function tr(string $sWord, $aSubkeys = false)
    {
        $aLangBase = $this->_aLang;
        $sTmpPath = '';
        if ($aSubkeys) {
            foreach ($aSubkeys as $sSubkey) {
                $sTmpPath .= ($sTmpPath ? '->' : '') . '[' . $sSubkey . ']';
                if (!isset($aLangBase[$sSubkey])) {
                    return "$sWord  (unknown path $sTmpPath in $this->_sLang)";
                }
                $aLangBase = $aLangBase[$sSubkey];
            }
        }
        return $aLangBase[$sWord]
            ?? ($this->_aLang[$sWord] ?? "$sWord (undefined in $sTmpPath $this->_sLang)")
            ;
    }
}
