<?php

/**
 * simple storages to keep last N items of an object
 *
 * @author hahn
 * 
 * 2024-07-17  axel.hahn@unibe.ch  php 8 only: use typed variables
 */
class simpleRrd
{

    /**
     * Prefix for cached items of this class
     * @var string
     */
    protected string $_sCacheIdPrefix = "rrd";

    /**
     * Maximum number of kept data items
     * @var integer
     */
    protected int $_iMaxLogentries = 1000;

    /**
     * Logdata for detected changes and sent notifications
     * @var array 
     */
    protected array $_aLog = [];

    /**
     * Id of the cache item
     * @var string
     */
    protected string $_sCacheId = '';

    /**
     * Instance of ahcache class
     * @var AhCache
     */
    protected AhCache $_oCache;

    // ----------------------------------------------------------------------
    // __construct
    // ----------------------------------------------------------------------

    /**
     * Constructor
     * @param string $sId  optional id to set
     */
    public function __construct(string $sId = false)
    {
        if ($sId) {
            $this->setId($sId);
        }
    }

    // ----------------------------------------------------------------------
    // protected functions - handle cache of application checkdata
    // ----------------------------------------------------------------------

    /**
     * Helper function - limit log to N entries
     * @return boolean
     */
    protected function _cutLogitems(): bool
    {
        $bHasChange=false;
        if (count($this->_aLog) > $this->_iMaxLogentries) {
            while (count($this->_aLog) > $this->_iMaxLogentries) {
                array_shift($this->_aLog);
                $bHasChange=true;
            }
        }
        return $bHasChange;
    }

    /**
     * Load current or last stored client notification data into $this->_aLog
     * this method also stores current notification data on change
     * 
     * @return bool
     */
    protected function _getLogs(): bool
    {
        $this->_aLog = $this->_oCache->read();
        if (!is_array($this->_aLog)) {
            $this->_aLog = [];
        }
        return true;
    }

    /**
     * save log data
     * @return boolean
     */
    protected function _saveLogs(): bool
    {
        return $this->_oCache->write($this->_aLog);
    }

    // ----------------------------------------------------------------------
    // public functions
    // ----------------------------------------------------------------------

    /**
     * Add data item.
     * This action will limit the count of max items and save it to cache.
     * It returns the success of save action.
     * 
     * @param array $aDataItem  dataitem
     * @return boolean
     */
    public function add($aDataItem): bool
    {
        $this->_getLogs();
        $this->_aLog[] = ['timestamp' => time(), 'data' => $aDataItem];
        $this->_cutLogitems();
        return $this->_saveLogs();
    }

    /**
     * Delete current application
     * @return boolean
     */
    public function delete(): bool
    {
        if (!$this->_sCacheId) {
            return false;
        }
        return $this->_oCache->delete();
    }

    /**
     * Get array with stored items
     * 
     * @param integer  $iMax  optional: limit
     * @return array
     */
    public function get(int $iMax = false): array
    {
        $aReturn = [];
        $this->_getLogs();
        $aTmp = $this->_aLog;
        if (!count($aTmp)) {
            return [];
        }
        $iMax = $iMax ? $iMax : count($aTmp);
        $iMax = min($iMax, count($aTmp));
        for ($i = 0; $i < $iMax; $i++) {
            $aReturn[] = array_pop($aTmp);
            // $aReturn[]=$aTmp;
        }
        return $aReturn;
    }

    /**
     * Set id for this rrd value store
     * 
     * @param string $sId
     * @return boolean
     */
    public function setId($sId): bool
    {
        $this->_sCacheId = $sId;
        $this->_oCache = new AhCache($this->_sCacheIdPrefix, $this->_sCacheId);
        $this->_getLogs();
        return true;
    }
}
