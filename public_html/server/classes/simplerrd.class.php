<?php
require_once 'dbobjects/simplerrd.php';

/**
 * simple storages to keep last N items of an object
 *
 * @author hahn
 * 
 * 2024-07-23  axel.hahn@unibe.ch  php 8 only: use typed variables
 * 2025-02-21  axel.hahn@unibe.ch  use sqlite as storage
 */
class simpleRrd
{

    /**
     * Maximum number of kept data items
     * @var integer
     */
    protected int $_iMaxLogentries = 100;

    /**
     * Logdata for detected changes and sent notifications
     * @var array 
     */
    protected array $_aLog = [];

    /**
     * pdo object for rrd data
     * @var object
     */
    protected objsimplerrd $_oSimplerrd;

    /**
     * Row id of a set of rrd data identified by app id and counter name
     * @var int
     */
    protected int $_iRowid;

    /**
     * application id
     * @var string
     */
    protected string $_sAppid = '';


    // ----------------------------------------------------------------------
    // __construct
    // ----------------------------------------------------------------------

    /**
     * Constructor
     * @param string $sId  optional id to set
     */
    public function __construct(string $sAppId = '', string $sCounterId = '')
    {
        global $oDB;
        $this->_oSimplerrd = new objsimplerrd($oDB);
        if ($sAppId) {
            $this->setApp($sAppId);
            if ($sCounterId)
                $this->setId($sCounterId);
        }
    }

    // ----------------------------------------------------------------------
    // protected functions - handle cache of application checkdata
    // ----------------------------------------------------------------------

    /**
     * Helper function - limit log to N entries
     * called in add() before saving data
     * @return boolean
     */
    protected function _cutLogitems(): bool
    {
        $bHasChange = false;
        if (count($this->_aLog) > $this->_iMaxLogentries) {
            while (count($this->_aLog) > $this->_iMaxLogentries) {
                array_shift($this->_aLog);
                $bHasChange = true;
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
        if ($this->_iRowid) {
            $this->_oSimplerrd->read($this->_iRowid);
            $cachedata = json_decode($this->_oSimplerrd->get('data'), 1);
            $this->_aLog = (is_array($cachedata)) ? $cachedata : [];
        } else {
            $this->_aLog = [];
        }
        // echo "RRD id $this->_iRowid - ".count($this->_aLog)."<br>\n";
        return true;
    }

    /**
     * save log data
     * @return boolean
     */
    protected function _saveLogs(): bool
    {
        if ($this->_oSimplerrd->set('data', json_encode($this->_aLog))) {
            return $this->_oSimplerrd->save();
        }
        return false;
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
        if (!$this->_iRowid) {
            return false;
        }
        return $this->_oSimplerrd->delete();
    }
    /**
     * Delete current application
     * @return boolean
     */
    public function deleteApp(string $sAppid = ''): bool
    {
        $bReturn = true;
        foreach ($this->getCountersOfApp() as $sCounterid) {
            $this->_oSimplerrd->read($sCounterid);
            if (!$this->_oSimplerrd->delete()) {
                $bReturn = false;
            }
        }
        return $bReturn;
    }

    /**
     * Get array with stored items
     * 
     * @param integer  $iMax  optional: limit
     * @return array
     */
    public function get(int $iMax = 0): array
    {
        $aReturn = [];
        $this->_getLogs();
        $aTmp = $this->_aLog;
        if (!count($aTmp)) {
            return [];
        }
        rsort($aTmp);
        return array_slice($aTmp, 0, min($iMax, count($aTmp)));

        // $iMax = min($iMax, count($aTmp));
        // for ($i = 0; $i < $iMax; $i++) {
        //     $aReturn[] = array_pop($aTmp);
        // }
        // return $aReturn;
    }

    /**
     * Get array of ids of counters of current application
     * @return array
     */
    public function getCountersOfApp(): array
    {
        if (!$this->_sAppid) {
            echo "WARNING: " . __METHOD__ . " was called without setting an appId first." . PHP_EOL;
            return [];
        }
        $aSearchresult = $this->_oSimplerrd->search(
            [
                "columns" => ["id"],
                "where" => "appid = :appid",
            ],
            [
                "appid" => $this->_sAppid,

            ]
        );
        $aReturn = [];
        foreach ($aSearchresult as $aRow) {
            $aReturn[] = $aRow['id'];
        }
        return $aReturn;
    }

    /**
     * Get array of all counters
     * 
     * @return array
     */
    public function getAllCounters(array $aFilter=[]): array
    {

        $aSearchresult = $this->_oSimplerrd->search(
            [
                "columns" => ["id", "appid", "countername", "data"],
                "where" => "appid like :appid AND countername like :countername",
            ],
            [
                "appid" => $aFilter['appid']??'%',
                "countername" => $aFilter['countername']??'%',
            ]
        );
        $aReturn = [];

        $sSortKey1="appid";
        $sSortKey2="countername";
        foreach ($aSearchresult?:[] as $aRow) {
            $aReturn[$aRow[$sSortKey1]][$aRow[$sSortKey2]] = $aRow['data'];
        }
        return $aReturn;
    }

    /**
     * Set an application by its id to set counters for
     * @param string $sAppId
     * @return string
     */
    public function setApp(string $sAppId)
    {
        return $this->_sAppid = $sAppId;
    }

    /**
     * Set id for this rrd value store
     * 
     * @param string $sId
     * @return boolean
     */
    public function setId($sCountername): bool
    {

        if (
            $this->_oSimplerrd->readByFields([
                "appid" => $this->_sAppid,
                "countername" => $sCountername
            ])
        ) {
            $this->_iRowid = $this->_oSimplerrd->get('id');
        } else {
            $this->_iRowid = 0;
            $this->_oSimplerrd->new();
            $this->_oSimplerrd->set('appid', $this->_sAppid);
            $this->_oSimplerrd->set('countername', $sCountername);
        }

        // $this->_getLogs();
        return true;
    }
}
