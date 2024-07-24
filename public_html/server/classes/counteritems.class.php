<?php

require_once 'simplerrd.class.php';

/**
 * container for all counters of a single app
 * store last N response time to draw a graph
 *
 * @example
 * <code>
 * // INIT
 * $oCounters=new counteritems($sAppId, $sCounterId); 
 * OR
 * $oCounters=new counteritems();
 * $oCounters->setApp($sAppId);
 * $oCounters->setCounter($sCounterId);
 * 
 * // ADD VALUE
 * $oCounters->add([array]);
 * 
 * </code>
 *
 * @author hahn
 * 
 * 2024-07-17  axel.hahn@unibe.ch  php 8 only: use typed variables
 */
class counteritems
{

    // ----- storing counter values

    /**
     * Id of the application
     * @var string
     */
    protected string $_sAppId = '';

    /**
     * Id of the counter
     * @var string
     */
    protected string $_sCounterId = '';

    /**
     * Instance of simpleRrd object to keep N history values
     * @var simpleRrd
     */
    protected simpleRrd $_oSR;


    // ----- save used counters

    /**
     * array of used counterids 
     * @var array
     */
    protected array $_aCounters = [];

    /**
     * Id of the cache item
     * @var string
     */
    protected string $_sCacheId = '';

    /**
     * Prefix of cache ids
     * @var string
     */
    protected string $_sCacheIdPrefix = 'counterids';

    /**
     * instance of ahcache class
     * @var AhCache  
     */
    protected AhCache $_oCache;

    // ----------------------------------------------------------------------
    // __construct
    // ----------------------------------------------------------------------

    /**
     * Constructur with optional app and counter
     * @see settApp
     * @param string $sAppid      optional: id of an app
     * @param string $sCounterId  optional: name of a counter
     */
    public function __construct(string $sAppid = '', string $sCounterId = '')
    {
        if ($sAppid) {
            $this->setApp($sAppid, $sCounterId);
        }
    }

    /**
     * Set application id
     * @param string $sAppid      id of an app
     * @param string $sCounterId  optional: name of a counter
     * @return boolean
     */
    public function setApp(string $sAppid, string $sCounterId = ''): bool
    {
        $this->_sCounterId = false;
        $this->_sAppId = $sAppid;
        $this->_sCacheId = $sAppid;
        $this->_oCache = new AhCache($this->_sCacheIdPrefix, $this->_sCacheId);
        $this->_aCounters = $this->_oCache->read();
        if (!$this->_aCounters) {
            $this->_aCounters = [];
        }
        if ($sCounterId) {
            $this->setCounter($sCounterId);
        }
        return true;
    }

    /**
     * Set counter name
     * @param string $sCounterId  name of a counter
     * @param array  $aMeta       metadata with these keys
     *                            - title  - text above value
     *                            - visual - viaualisation type
     * @return boolean
     */
    public function setCounter(string $sCounterId, array $aMeta = [])
    {
        $this->_sCounterId = $sCounterId;

        unset($this->_oSR);
        if (!$this->_sAppId || !$this->_sCounterId) {
            echo 'FATAL ERROR in ' . __METHOD__ . ' - you need to setApp() before using setCounter()<br>' . "\n";
            return false;
        } else {
            if (!isset($this->_aCounters[$this->_sCounterId]) || count($aMeta)) {
                $this->_aCounters[$this->_sCounterId] = $aMeta;
                $this->_oCache->write($this->_aCounters);
            }
            $this->_oSR = new simpleRrd($this->_sAppId . '-' . $this->_sCounterId);
            return true;
        }
    }
    /**
     * Get all stored counters of the current app
     * @return array
     */
    public function getCounters(): array
    {
        return $this->_aCounters;
    }

    /**
     * Delete a single counter history
     * @param string $sCounterId  delete data of another than the current counter id
     * @return boolean
     */
    public function deleteCounter(string $sCounterId = ''): bool
    {
        if (!$sCounterId) {
            $this->setCounter($sCounterId);
        }
        if (isset($this->_aCounters[$sCounterId])) {
            $this->_oSR->delete();
            unset($this->_aCounters[$sCounterId]);
            $this->_oCache->write($this->_aCounters);
            return true;
        }
        return false;
    }


    // ----------------------------------------------------------------------
    // public functions
    // ----------------------------------------------------------------------

    /**
     * Add a value to the current counter
     * @param array  $aItem  array item to add
     * @return boolean
     */
    public function add(array $aItem): bool
    {
        return $this->_oSR->add($aItem);
    }


    /**
     * Delete all application counters
     * @return boolean
     */
    public function delete(): bool
    {
        $aCounters = $this->getCounters();
        if (count($aCounters)) {
            foreach (array_keys($aCounters) as $sCounterid) {
                $this->deleteCounter($sCounterid);
            }
        }
        return true;
    }
    /**
     * Get last N values
     * @param integer  $iMax  optional: get last N values; default: get all stored values
     * @return array
     */
    public function get(int $iMax = 0): array
    {
        return $this->_oSR->get($iMax);
    }
}
