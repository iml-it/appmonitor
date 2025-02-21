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
    protected int $_iMaxLogentries = 1000;

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


    // ----------------------------------------------------------------------
    // __construct
    // ----------------------------------------------------------------------

    /**
     * Constructor
     * @param string $sId  optional id to set
     */
    public function __construct(string $sId = '')
    {
        global $oDB;
        $this->_oSimplerrd=new objsimplerrd($oDB);
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
        if($this->_iRowid) {
            $this->_oSimplerrd->read($this->_iRowid);
            $cachedata= json_decode($this->_oSimplerrd->get('data'), 1);
            $this->_aLog = (is_array($cachedata)) ? $cachedata : [];
        } else {
            $this->_aLog=[];
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
        if( $this->_oSimplerrd->set('data', json_encode($this->_aLog)) ){
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
        $iMax = $iMax ? $iMax : count($aTmp);
        $iMax = min($iMax, count($aTmp));
        for ($i = 0; $i < $iMax; $i++) {
            $aReturn[] = array_pop($aTmp);
        }
        return $aReturn;
    }

    /**
     * Set id for this rrd value store
     * 
     * @param string $sId
     * @return boolean
     */
    public function setId(string $sId): bool
    {
        
        $sAppid=substr($sId, 0, 32);
        $sCountername=substr($sId, 33, 200);

        /*
        $aSearchresult=$this->_oSimplerrd->search([
                "columns"=>["id"],
                "where"=>"appid = :appid and countername = :countername",
            ],
            [
                "appid" => $sAppid,
                "countername" => $sCountername,
            ]
            );
        $this->_iRowid=$aSearchresult[0]['id']??0;
        */

        if ($this->_oSimplerrd->readByFields([
            "appid" => $sAppid,
            "countername" => $sCountername
        ])) {
            $this->_iRowid=$this->_oSimplerrd->get('id');
        } else {
            $this->_iRowid=0;
            $this->_oSimplerrd->new();
            $this->_oSimplerrd->getitem();
            $this->_oSimplerrd->set('appid', $sAppid);
            $this->_oSimplerrd->set('countername', $sCountername);
    
        }
        
        $this->_getLogs();
        return true;
    }
}
