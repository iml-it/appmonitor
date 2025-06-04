<?php
/**
 * ======================================================================
 * 
 * base class with database CRUD actions and other general methods for 
 * any custom database objects
 * 
 * ----------------------------------------------------------------------
 * 
 * TODO:
 * 
 * - validate values in method set() - WIP
 * - More useful debugging _wd()
 * - find a sexy name
 * 
 * ----------------------------------------------------------------------
 * Author: Axel Hahn
 * Licence: GNU GPL 3.0
 * ----------------------------------------------------------------------
 * 2023-08-26  0.1  ah  first lines
 * 2025-05-22  ___  ah  add input type "range"
 * ======================================================================
 */

namespace axelhahn;

use Exception, PDO, PDOException;

// for relation table
require_once 'pdo-db-attachments.class.php';
require_once 'pdo-db-relations.class.php';

/**
 * class for basic CRUD actions
 *
 * @author hahn
 */
class pdo_db_base
{

    /**
     * name of the database table
     * @var string
     */
    protected string $_table = '';

    /**
     * object of pdo database instance
     * @var object
     */
    private object $_pdo;

    /**
     * a single object
     * @var array
     */
    protected array $_aItem = [];

    /**
     * flag if $_aItem has a change
     * @var bool
     */
    protected bool $_bChanged = false;

    /**
     * hash for a single announcement item with related data to
     * create database column, draw edit form
     * @var array 
     */
    protected array $_aProperties = [];

    /**
     * relations of the current object
     * @var array
     */
    private array $_aAttachments = [];

    /**
     * relations of the current object
     * @var array
     */
    private array|null $_aRelations = [];

    /**
     * default columns for each object type
     */
    protected array $_aDefaultColumns = [
        'id' => [
            'create' => 'INTEGER primary key autoincrement',
            'index' => true,
            // 'extra' =>  'primary key autoincrement',
            // 'label' => 'ID',                    'descr' => '', 'type' => 'hidden',         'edit' => false,
            'dummyvalue' => 'automatic'
        ],
        'timecreated' => ['create' => 'DATETIME', 'dummyvalue' => 'automatic'],
        'timeupdated' => ['create' => 'DATETIME', 'dummyvalue' => 'automatic'],
        'deleted' => ['create' => 'INTEGER', 'dummyvalue' => '0'],
    ];

    /**
     * database types for create statement
     * links:
     * - https://www.sqlite.org/datatype3.html
     * - https://www.w3schools.com/mysql/mysql_datatypes.asp
     * 
     * @return array
     */
    private array $_aDbTypes = [];

    // ----------------------------------------------------------------------
    // CONSTRUCTOR
    // ----------------------------------------------------------------------

    /**
     * Constructor - sets internal environment variables and checks existence 
     * of the database
     * @param  string $sObjectname  object name to generate a tablename from it
     * @param  string $sDbConfig    database config file
     * @return boolean
     */
    public function __construct(string $sObjectname, object $oDB)
    {

        $this->_table = $this->getTablename($sObjectname);

        $this->_pdo = $oDB;
        if (!$this->_pdo->tableExists($this->_table)) {
            $this->_wd(__METHOD__ . ' Need to create table.');
            if (!$this->_createDbTable()) {
                $this->_wd(__METHOD__ . ' Error creating table.');
                die('ERROR: Unable to create table for [' . $sObjectname . '].');
            }
            ;
        }

        // generate item
        $this->_aRelations = ($sObjectname == 'axelhahn\pdo_db_relations') ? NULL : [];
        $this->new();

        //  return true;
    }

    // ----------------------------------------------------------------------
    // PRIVATE FUNCTIONS
    // ----------------------------------------------------------------------

    /**
     * Get a table name of a given class name
     * @see reverse function _getObjectFromTablename()
     * @param  string  $s      input string to generate a table name from
     * @return string
     */
    public function getTablename(string $s): string
    {
        return basename(str_replace('\\', '/', $s));
    }
    /**
     * Get a class name from a given table name
     * @see reverse function getTablename()
     * @param  string  $s      input string to generate a table name from
     * @return string
     */
    protected function _getObjectFromTablename(string $s): string
    {
        return __NAMESPACE__ . '\\' . $s;
    }

    /**
     * Write debug output if enabled by flag
     * @param  string  $s  string to show
     * @return bool
     */
    protected function _wd(string $s): bool
    {
        return $this->_pdo->_wd($s, $this->_table);
    }

    /**
     * Helper function to insert timestamp for creation and update
     * @return string
     */
    protected function _getCurrentTime(): string
    {
        return date("Y-m-d H:i:s");
    }


    /**
     * Execute a sql statement
     * a wrapper for $this->_pdo->makeQuery() that adds the current table
     * 
     * @param  string  $sSql   sql statement
     * @param  array   $aData  array with data items; if present prepare statement will be executed 
     * @return array|boolean
     */
    public function makeQuery(string $sSql, array $aData = []): array|bool
    {
        $this->_wd(__METHOD__ . " ($sSql, " . (count($aData) ? "DATA[" . count($aData) . "]" : "NODATA") . ")");
        return $this->_pdo->makeQuery($sSql, $aData, $this->_table);
    }


    /**
     * Create database table if it does not exist yet
     * @return bool
     */
    private function _createDbTable(): bool
    {
        if ($this->_pdo->tableExists($this->_table)) {
            $this->_log(
                PB_LOGLEVEL_INFO,
                __METHOD__ . '()',
                "Table [$this->_table] already exists"
            );
            return true;
        }

        $sSql = '';
        $aDB = $this->_pdo->getSpecialties();
        if (!$aDB) {
            $this->_log(
                PB_LOGLEVEL_ERROR,
                __METHOD__ . '()',
                'Unable to get database specifics. Database type not supported: ' . $this->_pdo->driver()
            );
            return false;
        }

        // db columns are default colums + columns for my object
        foreach (array_merge($this->_aDefaultColumns, $this->_aProperties) as $sCol => $aData) {
            if (isset($aData['create'])) {
                $sColumnType = str_ireplace(array_keys($aDB), array_values($aDB), $aData['create']);
                $sSql .= ($sSql ? ",\n" : '')
                    . "    $sCol {$sColumnType}";
                // $sSql .= ($sSql ? ', ' : '')
                //     . "$sCol " . $aData['create']
                //     . (isset($aData['extra']) ? ' ' . $aData['extra'] : '');
            }
        }
        $sSql = "CREATE TABLE $this->_table ($sSql);";
        $this->makeQuery($sSql);
        if (!$this->_pdo->tableExists($this->_table)) {
            $this->_log(
                PB_LOGLEVEL_ERROR,
                __METHOD__ . '()',
                "Unable to create table [' . $this->_table . ']."
            );
            return false;
        }
        $this->_createDbTableIndex();
        // echo __METHOD__ . ' created table ' . $this->_table . '<br>';
        return true;

    }

    /**
     * Create database indexes for wanted columns
     * (property 'index' must be set to true)
     * 
     * @return bool
     */
    protected function _createDbTableIndex():bool
    {
        foreach (array_merge($this->_aDefaultColumns, $this->_aProperties) as $sCol => $aData) {
            if (($aData['index']??false) || ($aData['overview']??false) ) {
                $sIndexType='';
                $sIndexId = "IDX_{$this->_table}_$sCol";

                $sqlIndex = "
                    CREATE {$sIndexType}INDEX IF NOT EXISTS `$sIndexId`
                    ON `{$this->_table}`
                    ( `$sCol` )
                ";
                $this->makeQuery($sqlIndex);
            }
        }
        return true;
    }

    /**
     * Verify database columns with current object configuration. It shows
     * - missing columns
     * - existing database columns that are not configured
     * - columns with wrong type
     * @return array|bool
     */
    public function verifyColumns(): array|bool
    {

        $this->_wd(__METHOD__);
        $aDbSpecifics = [
            'sqlite' => ['sql' => 'PRAGMA table_info(`' . $this->_table . '`)', 'key4column' => 'name', 'key4type' => 'type',],
            'mysql' => ['sql' => 'DESCRIBE `' . $this->_table . '`;', 'key4column' => 'Field', 'key4type' => 'Type',],
        ];

        $type = $this->_pdo->driver();
        if (!isset($aDbSpecifics[$type])) {
            die("Ooops: " . __CLASS__ . " does not support db type [" . $type . "] yet :-/");
        }

        $result = $this->makeQuery($aDbSpecifics[$type]['sql']);
        // echo '<pre>'; print_r($result); echo '</pre>';
        if (!$result || !count($result)) {
            $this->_log(
                PB_LOGLEVEL_ERROR,
                __METHOD__,
                "[$this->_table] Unable to get table infos by sql query: $aDbSpecifics[$type][sql]."
            );
            return false;
        }
        $aDB = $this->_pdo->getSpecialties();
        if (!$aDB) {
            $this->_log(
                PB_LOGLEVEL_ERROR,
                __METHOD__ . '()',
                'Unable to get database specifics. Database type not supported: ' . $this->_pdo->driver()
            );
            return false;
        }
        $aReturn = ['_result' => ['errors' => 0, 'ok' => 0, 'messages' => []], 'tables' => []];
        $aCols = [];
        $iOK = 0;
        $iErrors = 0;
        $aMessages = [];

        // put names into key
        foreach ($result as $aColumndef) {
            $aCols[$aColumndef[$aDbSpecifics[$type]['key4column']]] = [
                'column' => $aColumndef[$aDbSpecifics[$type]['key4column']],
                'type_current' => strtolower($aColumndef[$aDbSpecifics[$type]['key4type']]),
                'type' => false,
                'type_translated' => false,
            ];
        }

        $aAllTables = array_merge($this->_aDefaultColumns, $this->_aProperties);
        foreach ($aAllTables as $sColumn => $aData) {
            $_sCreateType = preg_replace('/([a-z\(0-0\)]*)\ .*$/', '$1', $aData['create']);
            $aCols[$sColumn]['type'] = $aData['create'];
            $aCols[$sColumn]['type_translated'] = strtolower(str_ireplace(array_keys($aDB), array_values($aDB), $_sCreateType));
            if (!isset($aCols[$sColumn]['type_current'])) {
                $iErrors++;
                $aReturn['tables'][$sColumn] = [
                    'error' => 1,
                    'is' => '-- missing --',
                    'must' => $aCols[$sColumn]['type'],
                ];
                $aMessages[] = 'Database column <strong>' . $sColumn . '</strong>: Column <ins>' . $sColumn . '</ins> needs to be created as <em>' . $aData['create'] . '</em>.';
            } elseif (!isset($aCols[$sColumn]['type_translated']) || $aCols[$sColumn]['type_translated'] !== $aCols[$sColumn]['type_current']) {
                $iErrors++;
                $aReturn['tables'][$sColumn] = [
                    'error' => 1,
                    'is' => $aCols[$sColumn]['type_current'],
                    'must' => $aCols[$sColumn]['type'],
                ];
                $aMessages[] = 'Database column <strong>' . $sColumn . '</strong>: It\'s type is not up to date. Alter column from <del>' . $aCols[$sColumn]['type_current'] . '</del> to <ins>' . $aCols[$sColumn]['type'] . '</ins>';
            } else {
                $iOK++;
                $aReturn['tables'][$sColumn] = [
                    'ok' => 1,
                    'is' => $aCols[$sColumn]['type_current'],
                ];
            }
            ;
        }

        foreach ($aCols as $sColumn => $aData) {
            if (!isset($aAllTables[$sColumn])) {
                $aReturn['tables'][$sColumn] = [
                    'error' => 1,
                ];
                $aMessages[] = 'Database column <strong>' . $sColumn . '</strong>: exists in database but is no property of the object. Verify if you need to execute ALTER TABLE or delete it.';
            }
        }

        $aReturn['_result']['errors'] = $iErrors;
        $aReturn['_result']['ok'] = $iOK;
        $aReturn['_result']['messages'] = $aMessages;

        /*
        echo '<pre style="margin-left: 20em;">'; print_r($result); echo '</pre>';
        echo '<pre style="margin-left: 20em;">'; print_r($aCols); echo '</pre>';
        echo '<pre style="margin-left: 20em;">'; print_r($aReturn); echo '</pre>';
        */

        return $aReturn;

    }

    // ----------------------------------------------------------------------
    // DEBUGGING GETTER
    // ----------------------------------------------------------------------

    // ----------------------------------------------------------------------
    // DEBUGGING SETTER
    // ----------------------------------------------------------------------

    /**
     * Add a log message for current object
     * @param  string  $sLevel    loglevel; one of inf|warn|error
     * @param  string  $sMethod   the method where the error occured
     * @param  string  $sMessage  the error message
     * @return bool
     */
    protected function _log(string $sLevel, string $sMethod, string $sMessage): bool
    {
        return $this->_pdo->_log($sLevel, $this->_table, $sMethod, $sMessage);
    }


    // ----------------------------------------------------------------------
    // CRUD ACTIONS
    // ----------------------------------------------------------------------

    /**
     * Generate a hash for a new empty item
     * @return bool
     */
    public function new(): bool
    {
        $this->_aItem = [];
        $this->_bChanged = true;
        foreach ($this->_aDefaultColumns as $sKey => $aData) {
            $this->_aItem[$sKey] = $aData['dummyvalue'];
        }
        foreach (array_keys($this->_aProperties) as $sKey) {
            $this->_aItem[$sKey] = NULL;
        }
        $this->_aRelations = isset($this->_aRelations) ? [] : NULL;
        return true;
    }

    /**
     * Create a new entry in the database
     * @return bool|integer false on failure or new id on success
     */
    public function create(): bool|int
    {
        $this->_wd(__METHOD__);

        // prepare default columns
        unset($this->_aItem['id']);
        $this->_aItem['timecreated'] = $this->_getCurrentTime();
        $this->_aItem['timeupdated'] = NULL;
        $this->_aItem['deleted'] = 0;

        // create db entry
        $sSql = 'INSERT INTO `' . $this->_table . '` (`' . implode('`, `', array_keys($this->_aItem)) . '`) VALUES (:' . implode(', :', array_keys($this->_aItem)) . ');';
        $result = $this->makeQuery($sSql, $this->_aItem);
        if (is_array($result)) {
            $this->_aItem['id'] = $this->_pdo->db->lastInsertId();
            $this->_bChanged = false;

            // handle lookups
            foreach ($this->_aProperties as $sCol => $aCol) {

                // if coumn is a lookup and a value was set then create relation
                if (isset($aCol['lookup']) && $this->_aItem[$sCol]) {
                    $sTargetTable = $aCol['lookup']['table'];
                    if (!$this->relCreate($sTargetTable, $this->_aItem[$sCol], $sCol)) {
                        $this->_log(
                            PB_LOGLEVEL_ERROR,
                            __METHOD__,
                            "Creation of relation from table [$this->_table\.$sCol] to table $sTargetTable failed."
                        );
                        return false;
                    }
                }
            }
            return $this->id();
        }
        $this->_log(
            PB_LOGLEVEL_ERROR,
            __METHOD__,
            "Creation of new database entry [$this->_table] failed."
        );
        return false;
    }

    /**
     * Read an entry from database by known row id
     * @param int   $iId           row id to read
     * @param bool $bReadRelations read relation too? default: false
     * @return bool|int
     */
    public function read(int $iId, bool $bReadRelations = false): bool
    {
        $this->_wd(__METHOD__);
        return $this->readByFields(
            ['id' => $iId],
            $bReadRelations
        );
    }

    /**
     * Read item from row by given fields with AND condition
     * Useful for reading item by known uniq single or multiple column values
     *
     * @param array $aColumns
     * @param bool $bReadRelations
     * @return bool
     */
    public function readByFields(array $aColumns, bool $bReadRelations = false): bool
    {
        $this->_wd(__METHOD__);

        $aData = [];
        $Where = [];
        // $Where[] = "1=1";

        $this->new();

        foreach ($aColumns as $skey => $value) {

            if(strstr($value, '%')){
                $Where[] = "AND `$skey` LIKE :$skey ESCAPE '\\'";
                $value = addcslashes($value, '%_');
            } else {
                $Where[] = "AND `$skey` = :$skey";                
            }
            $aData[$skey] = $value;
            if (isset($this->_aProperties[$skey])) {
                $this->set($skey, $value); // pre defined fields on new object
            }
        }

        // cut starting "AND " from 1st condition
        if($Where[0]??false){
            $Where[0] = substr($Where[0], 4);
        }
        $this->_bChanged = false; 

        // search fetches 2 items.
        // 1 is needed to apply value and a 2nd for detection of multiple values
        $result = $this->search(
            [
                'columns' => '*',
                'where' => $Where,
                'limit' => '2'
            ],
            $aData
        );

        if (isset($result[1])) {
            $this->_log(
                PB_LOGLEVEL_WARN,
                __METHOD__,
                "Table [$this->_table] returned multiple items with [" . print_r($aColumns, 1) . "]."
            );
        }

        if (isset($result[0])) {
            $this->_aItem = $result[0];

            // read relation while loading object?
            if ($bReadRelations) {
                $this->_relRead();
            }
            return true;
        }

        $this->_log(
            PB_LOGLEVEL_ERROR,
            __METHOD__,
            "Unable to read [$this->_table] item with [" . print_r($aColumns, 1) . "]."
        );

        return false;
    }

    /**
     * Update entry; the field "id" is required to identify a single row in the table
     * @return int|bool
     */
    public function update(): int|bool
    {
        $this->_wd(__METHOD__);

        if (!$this->_bChanged) {
            $this->_log(
                PB_LOGLEVEL_INFO,
                __METHOD__,
                'Skip database update: dataset was not changed.'
            );
            return false;
        } else {
            // prepare default columns
            $this->_aItem['timeupdated'] = $this->_getCurrentTime();

            // update existing db entry
            $sSql = '';
            foreach (array_keys($this->_aItem) as $sCol) {
                $sSql .= ($sSql ? ', ' : '') . "`$sCol` = :$sCol";
            }
            $sSql = 'UPDATE `' . $this->_table . '` ' . 'SET ' . $sSql . ' WHERE `id` = :id';
            $return = $this->makeQuery($sSql, $this->_aItem);
        }
        if (is_array($return) || !$this->_bChanged) {

            // handle lookups
            if (!$this->_aRelations || !count($this->_aRelations)) {
                $this->_relRead();
            }

            // echo '<pre>'. print_r($this->_aRelations, 1).'</pre>'; // die(__FILE__.':'.__LINE__);

            // loop over lookups in relations
            if (isset($this->_aRelations['_lookups']) && count($this->_aRelations['_lookups'])) {

                foreach ($this->_aRelations['_lookups'] as $sCol => $aRel) {

                    $iItemvalue = $this->get($sCol);
                    if ($iItemvalue) {

                        $iTargetId = $this->_aRelations['_targets'][$aRel['relkey']]['id'] ?? false;
                        if (!$iTargetId) {
                            // create new relation
                            $this->_wd(__METHOD__ . ' create new relation for ' . $sCol);
                            $this->relCreate($this->_aProperties[$sCol]['lookup']['table'], $iItemvalue, $sCol);
                        } else {
                            // if current value is not equal to target id:
                            // update relation
                            if ((int) $iItemvalue !== (int) $iTargetId) {
                                $this->_wd(__METHOD__ . ' updating relation for ' . $sCol . ': ' . $iTargetId . ' --> ' . $iItemvalue);
                                $this->relUpdate($aRel['relkey'], $iItemvalue);
                            } else {
                                $this->_wd('no relation change for ' . $sCol);
                            }
                        }
                    } else {
                        // value in item was deleted -> delete relation too
                        $this->_wd(__METHOD__ . ' deleting relation for ' . $sCol);
                        $this->relDelete($aRel['relkey']);
                    }
                }
            }
            // loop over lookups columns in item
            foreach ($this->_aProperties as $sCol => $aCol) {
                if (isset($aCol['lookup'])) {
                    $sTargetTable = $aCol['lookup']['table'];
                    $iItemvalue = $this->get($sCol);
                    $sRelKey = $this->_getRelationKey($sTargetTable, 0, $sCol);
                    if (!$iItemvalue && isset($this->_aRelations['_targets'][$sRelKey])) {
                        $this->_wd(__METHOD__ . ' Delete unneeded relation ' . $sRelKey);
                        $this->relDelete($sRelKey);
                    }
                }
            }

            $this->_bChanged = false;
            // die(__FILE__.':'.__LINE__);
            return $this->id();
        }
        return false;
    }

    /**
     * Delete entry by a given id or current item
     * @param  integer  $iId   optional: id of the entry to delete; default: delete current item
     * @return bool
     */
    public function delete(int $iId = 0): bool
    {
        $iId = $iId ?: $this->id();
        if ($iId) {
            if ($this->relDeleteAll($iId)) {

                if(method_exists($this, 'hookDelete')){
                    if($iId){
                        $this->read($iId);
                    }
                    if(!$this->{'hookDelete'}()) {
                        $this->_log(
                            PB_LOGLEVEL_ERROR,
                            __METHOD__,
                            "[$this->_table]->hookDelete() failed."
                        );
                        return false;
                    };
                }
    
                $sSql = 'DELETE from `' . $this->_table . '` WHERE `id`=:id';
                $aData = [
                    'id' => $iId,
                ];
                $result = $this->makeQuery($sSql, $aData);
                if (is_array($result)) {
                    if ($iId == $this->id()) {
                        $this->new();
                    }
                    $this->_bChanged = false;
                    return true;
                } else {
                    $this->_log(
                        PB_LOGLEVEL_ERROR,
                        __METHOD__,
                        "[$this->_table] Deletion if item with id [$iId] failed."
                    );
                    return false;
                }
                ;
            } else {
                $this->_log(
                    PB_LOGLEVEL_ERROR,
                    __METHOD__,
                    "[$this->_table] Deletion if relations for id [$iId] failed. Item was not deleted."
                );
            }
        }
        return false;
    }


    // ----------------------------------------------------------------------
    // ACTIONS
    // ----------------------------------------------------------------------

    /**
     * !!! DANGEROUS !!!
     * Drop table of current object type. It deletes all items of a type and
     * removes the schema from database
     * @return bool
     */
    public function flush(): bool
    {
        // - delete relations from_table and to_table
        if (!$this->relFlush()) {
            $this->_log(
                PB_LOGLEVEL_ERROR,
                __METHOD__,
                "Unable to delete all relations."
            );
            return false;
        }
        $sSql = "DROP TABLE IF EXISTS `$this->_table`";
        if (!is_array($this->makeQuery($sSql))) {
            $this->_log(
                PB_LOGLEVEL_ERROR,
                __METHOD__,
                "Unable to drop table [$this->_table]."
            );
            return false;
        }
        return true;
    }

    /**
     * Save item. If id is set, update. Otherwise create.
     * @return bool
     */
    public function save(): bool
    {
        return $this->id()
            ? $this->update()
            : $this->create();
    }

    // ----------------------------------------------------------------------
    // RELATIONS
    // ----------------------------------------------------------------------

    /**
     * Generate a key for a relation to another table and its id.
     * TODO: use id of relation object seems to be possible
     *       see relCreate() - there is a check for possibly duplicate entry
     * 
     * @param  string   $sToTable    target table
     * @param  integer  $iToId       target id
     * @param  string   $sSourceCol  optional: column name with lookup
     * @param  string   $sToCol      optional: column name on target
     * @return string
     */
    protected function _getRelationKey(string $sToTable, int $iToId, string|null $sSourceCol = '', string|null $sToCol = ''): string
    {
        $sReturn = ''
            . (isset($sSourceCol) && $sSourceCol > '')
            ? $sToTable . ':column__' . $sSourceCol
            : ((isset($sToCol) && $sToCol > '')
                ? $sToTable . ':' . $iToId . ':' . $sToCol
                : $sToTable . ':' . $iToId
            )
        ;
        return $sReturn;
    }

    /**
     * Generate a key for a relation to another table and its id
     * The tables here are sorted already (see _getRelationSortorder)
     * 
     * @param  string   $sFromTable  table name
     * @param  integer  $iFromId     table id
     * @param  string   $sFromCol    column name
     * @param  string   $sToTable    second table
     * @param  integer  $iToId       second table id
     * @param  string   $sToCol      column name
     * @return string
     */
    protected function _getRelationUuid(string $sFromTable, int $iFromId, string|null $sFromCol, string $sToTable, int $iToId, string|null $sToCol): string
    {
        return md5(
            $sFromTable . ':' . $iFromId . ':' . $sFromCol
            . '-->'
            . $sToTable . ':' . $iToId . ':' . $sToCol
        );
    }

    /**
     * Generate a relation item in the wanted sort order of given tables including uuid
     * The tables here are unsorted
     * 
     * @param  string   $sTable1  first table name
     * @param  integer  $iId1     first table id
     * @param  string   $sTable2  second table
     * @param  integer  $iId2     second table id
     * @return array
     */
    protected function _getRelationSortorder(string $sTable1, int $iId1, string|null $sCol1, string $sTable2, int $iId2, string|null $sCol2): array
    {
        $aReturn = ($sTable1 < $sTable2 || ($sTable1 == $sTable2 && $iId1 < $iId2))
            ? [
                'from_table' => $sTable1,
                'from_id' => $iId1,
                'from_column' => $sCol1,
                'to_table' => $sTable2,
                'to_id' => $iId2,
                'to_column' => $sCol2,
            ]
            : [
                'from_table' => $sTable2,
                'from_id' => $iId2,
                'from_column' => $sCol2,
                'to_table' => $sTable1,
                'to_id' => $iId1,
                'to_column' => $sCol1,
            ]
        ;
        $aReturn['uuid'] = $this->_getRelationUuid($aReturn['from_table'], $aReturn['from_id'], $aReturn['from_column'], $aReturn['to_table'], $aReturn['to_id'], $aReturn['to_column']);
        return $aReturn;
    }

    /**
     * Internal data: add a relation item to current item
     * @param  array $aRelitem relation item array [from_table, from_id, from_column, to_table, to_id, to_columnuuid]
     * @return bool
     */
    protected function _addRelationToItem(array $aRelitem = []): bool
    {
        $this->_wd(__METHOD__ . '()');
        if (!isset($this->_aRelations)) {
            $this->_log(
                PB_LOGLEVEL_ERROR,
                __METHOD__,
                "Relations are not allowed for [$this->_table]"
            );
            return false;
        }
        if (!isset($aRelitem['uuid'])) {
            $this->_log(
                PB_LOGLEVEL_ERROR,
                __METHOD__,
                "Target item is no array or has no key [uuid]"
            );
            return false;
        }
        $aTarget = $aRelitem['from_table'] == $this->_table
            ? [
                'from_column' => $aRelitem['from_column'],
                'to_table' => $aRelitem['to_table'],
                'to_id' => $aRelitem['to_id'],
                'to_column' => $aRelitem['to_column'],
            ] : [
                'from_column' => $aRelitem['to_column'],
                'to_table' => $aRelitem['from_table'],
                'to_id' => $aRelitem['from_id'],
                'to_column' => $aRelitem['from_column'],
            ];
        $sKey = $this->_getRelationKey($aTarget['to_table'], $aTarget['to_id'], $aTarget['from_column'], $aTarget['to_column']);
        $this->_aRelations[$sKey] = [
            'target' => $aTarget,
            'db' => $aRelitem,
        ];
        return true;
    }

    /**
     * Create a relation from the current item to an id of a target object
     * @param  string  $sToTable     target object
     * @param  string  $sToId        id of target object
     * @param  string  $sFromColumn  optional: source column
     * @return bool
     */
    public function relCreate(string $sToTable, int $iToId, string|null $sFromColumn = NULL): bool
    {
        $this->_wd(__METHOD__ . "($sToTable, $iToId, $sFromColumn)");
        if (!$this->id()) {
            $this->_log(
                PB_LOGLEVEL_ERROR,
                __METHOD__ . "($sToTable, $iToId, $sFromColumn)",
                "[$this->_table] The current item was not saved yet. We need an id in a table to create a relation with it."
            );
            return false;
        }
        if (!isset($this->_aRelations)) {
            $this->_log(
                PB_LOGLEVEL_ERROR,
                __METHOD__ . "($sToTable, $iToId, $sFromColumn)",
                "[$this->_table] The relation is disabled."
            );
            return false;
        }

        if (!preg_match('/^[a-z_]*$/', $sToTable)) {
            $this->_log(
                PB_LOGLEVEL_ERROR,
                __METHOD__ . "($sToTable, $iToId)",
                "[$this->_table] The target table was not set."
            );
            return false;
        }
        if (!$this->_pdo->tableExists($sToTable)) {
            $this->_log(
                PB_LOGLEVEL_ERROR,
                __METHOD__ . "($sToTable, $iToId)",
                "The target table [$sToTable] does not exist."
            );
            return false;
        }
        if (!(int) $iToId) {
            $this->_log(
                PB_LOGLEVEL_ERROR,
                __METHOD__ . "($sToTable, $iToId)",
                "[$this->_table] The target id is not set or not valid."
            );
            return false;
        }

        // helper function:
        $aTmp = $this->_getRelationSortorder($this->_table, $this->id(), $sFromColumn, $sToTable, $iToId, NULL);

        $sKey = $this->_getRelationKey($sToTable, $iToId, $sFromColumn);
        if (isset($this->_aRelations[$sKey])) {
            $this->_log(
                PB_LOGLEVEL_ERROR,
                __METHOD__ . "($sToTable, $iToId)",
                "[$this->_table] The relation already exists. It has the key [$sKey]."
            );
            return false;
        }

        $this->_wd(__METHOD__ . " Creating new relation");
        $oRelation = new pdo_db_relations($this->_pdo);

        $oRelation->setItem($aTmp);
        if ($oRelation->save()) {
            $this->_addRelationToItem($aTmp);
            return true;
        }
        // print_r($this->error());
        $this->_log(
            PB_LOGLEVEL_ERROR,
            __METHOD__ . "($sToTable, $iToId)",
            "[$this->_table] Unable to save relation."
        );
        return false;
    }

    /**
     * Method to read relations for the current object from relations table.
     * It sets the protected var $this->_aRelations.
     * This function is used in methods read() and relRead()
     * @return bool
     */
    protected function _relRead(): bool
    {
        if (!isset($this->_aRelations)) {
            return false;
        }
        $this->_aRelations = [];
        $oRelation = new pdo_db_relations($this->_pdo);
        $aData = ['id' => $this->id()];
        $aRelations = $oRelation->search([
            'columns' => '*',
            'where' => '
                (`from_table`="' . $this->_table . '" AND `from_id`=:id )
                OR 
                (`to_table`="' . $this->_table . '" AND `to_id`=:id )
                AND `deleted`=0',
            'order' => [
                'from_table ASC',
                'from_id ASC',
                'from_column ASC',
                'to_table ASC',
                'to_id ASC',
                'to_column ASC',
            ],
        ], $aData);
        // $this->_aQueries[]=$oRelation->lastquery();
        if (is_array($aRelations) && count($aRelations)) {
            foreach ($aRelations as $aEntry) {
                $aTmp = $this->_getRelationSortorder($aEntry['from_table'], $aEntry['from_id'], $aEntry['from_column'], $aEntry['to_table'], $aEntry['to_id'], $aEntry['to_column']);

                $sTableKey = $this->_table . ':' . $this->id() == $aEntry['from_table'] . ':' . $aEntry['from_id']
                    ? 'to'
                    : 'from';
                $sTableSelfKey = $sTableKey == 'from'
                    ? 'to'
                    : 'from';

                // TODO: use id of relation object seems to be possible
                $sRelKey = $this->_getRelationKey($aTmp[$sTableKey . '_table'], $aTmp[$sTableKey . '_id'], $aTmp[$sTableSelfKey . '_column'], $aTmp[$sTableKey . '_column']);
                // $sRelKey = $aEntry['id'];

                $sSql = 'SELECT * FROM `' . $aEntry[$sTableKey . '_table'] . '` WHERE id=:id AND deleted=0';
                $aData = ['id' => $aEntry[$sTableKey . '_id']];
                $aTargetResult = $this->makeQuery($sSql, $aData);
                if (!isset($aTargetResult[0])) {
                    $this->_log(
                        PB_LOGLEVEL_ERROR, 
                        __METHOD__ . "()", 
                        "[$this->_table] The target id $aData[id] was not found in table " . $aEntry[$sTableKey . '_table']
                    );
                    continue;
                }

                $this->_aRelations['_targets'][$sRelKey] = [
                    'column' => $aEntry[$sTableSelfKey . '_column'] . $aEntry[$sTableKey . '_column'],
                    'table' => $aEntry[$sTableKey . '_table'],
                    'id' => $aEntry[$sTableKey . '_id'],
                    '_relid' => $aEntry['id'],
                    '_target' => $aTargetResult[0],
                ];
            }
        }
        // echo '<pre>_aItem = '; print_r($this->_aItem); echo '<hr>'; print_r($aRelations); echo '<hr>'; print_r($this->_aRelations); die(__FILE__.':'.__LINE__);

        // --- add lookup columns of current object into subkey "_lookups"
        foreach ($this->_aProperties as $sCol => $aCol) {

            // if coumn is a lookup and a value was set:
            if (isset($aCol['lookup']) && $this->_aItem[$sCol]) {

                $sTargetTable = $aCol['lookup']['table'];
                $iTargetId = $this->get($sCol);
                if ($iTargetId) {

                    $sRelKey = $this->_getRelationKey($sTargetTable, $iTargetId, $sCol);


                    $this->_aRelations['_lookups'][$sCol] = [
                        'relkey' => $this->_getRelationKey($sTargetTable, $iTargetId, $sCol),
                        'columns' => $aCol['lookup']['columns'],
                        'value' => isset($this->_aRelations['_targets'][$sRelKey]['_target'])
                            ? $this->getLabel($this->_aRelations['_targets'][$sRelKey]['_target'], $aCol['lookup']['columns'])
                            : '{{needs_to_be_created}}'
                        ,
                    ];
                }

            }
        }

        // echo '<pre>_aItem = '; print_r($this->_aItem); echo '<hr>'; print_r($aRelations); echo '<hr>'; print_r($this->_aRelations); die(__FILE__.':'.__LINE__);
        return true;
    }

    /**
     * Get array with all relations of the current item
     * 
     * @see relReadLookupItem()
     * @see relReadObjects()
     * 
     * @param  array  $aFilter  optional: filter existing relations by table and column
     *                          Keys:
     *                            table => TARGETTABLE  table must match
     *                            column => COLNAME     DEPRECATED - column name must match too; use relReadLookupItem(COLNAME)
     * @return array
     */
    public function relRead(array $aFilter = []): array
    {
        $this->_wd(__METHOD__ . '() reading relations for ' . $this->_table . ' item id ' . $this->id());
        if($aFilter['column']??false){
            $this->_log(
                PB_LOGLEVEL_WARN, 
                __METHOD__ . '()', 
                "The 'column' filter is deprecated. Use relReadLookupItem(COLUMN) instead.");
        }

        if (is_array($this->_aRelations) && !count($this->_aRelations)) {
            $this->_relRead();
        }

        if (isset($aFilter['table'])) {
            $aReturn = [];
            if (isset($this->_aRelations['_targets'])) {
                foreach ($this->_aRelations['_targets'] as $sKey => $aRelation) {
                    if ($aRelation['table'] == $aFilter['table']) {
                        if (
                            !isset($aFilter['column'])
                            || (isset($aFilter['column']) && $aRelation['column'] == $aFilter['column'])
                        ) {
                            $aReturn['_targets'][$sKey] = $aRelation;
                        }
                    }
                }
            }
        } else {
            $aReturn = $this->_aRelations;
        }
        return $aReturn;
    }

    /**
     * Get array of referenced item of a lookup column
     * 
     * @param string $sColumn  name of the lookup column
     * @return array
     */
    public function relReadLookupItem(string $sColumn): array
    {
        if (!isset($this->_aProperties[$sColumn]['lookup']['table'])) {
            throw new Exception(__METHOD__ . ' Column ' . $sColumn . ' is not a lookup column');
        }
        if (!$this->get($sColumn)) {
            return [];
        }

        $sTargetTable = $this->_aProperties[$sColumn]['lookup']['table'];
        $sRelKey = $this->_getRelationKey($sTargetTable, 0, $sColumn);
        return $this->relRead([
            'table' => $this->_aProperties[$sColumn]['lookup']['table'],
            'column' => $sColumn,
        ])['_targets'][$sRelKey]['_target'] ?? [];
    }

    /**
     * Get array of all related objects of a given object type
     * 
     * @param string $sObjectname  name of the object type
     * @return array
     */
    public function relReadObjects(string $sObjectname): array
    {
        $aRel=$this->relRead(['table' => $sObjectname]);
        $aReturn=[];
        foreach($aRel['_targets']??[] as $aTarget){
            $aReturn[]=$aTarget['_target'];
        }
        return $aReturn;
    }

    /**
     * Update a single relation from current item with new value
     * @param  string   $sRelKey     key of the relation; a string like 'table:id'
     * @param  integer  $iItemvalue  new id to on target db
     * @return bool
     */
    public function relUpdate(string $sRelKey, int $iItemvalue): bool
    {
        $this->_wd(__METHOD__ . "($sRelKey, $iItemvalue)");
        if (!isset($this->_aRelations['_targets'][$sRelKey])) {
            $this->_log(
                PB_LOGLEVEL_ERROR, 
                __METHOD__ . "($sRelKey)", 
                "[$this->_table] The given key does not exist."
            );
            return false;
        }
        if (!isset($this->_aRelations['_targets'][$sRelKey]['_relid'])) {
            $this->_log(
                PB_LOGLEVEL_ERROR, 
                __METHOD__ . "($sRelKey)", 
                "[$this->_table] The key [_relid] was not found."
            );
            return false;
        }

        if (
            is_array(
                $this->makeQuery(
                    'UPDATE `pdo_db_relations` SET to_id = :to_id WHERE id = :id',
                    [
                        'id' => $this->_aRelations['_targets'][$sRelKey]['_relid'],
                        'to_id' => $iItemvalue,
                    ]
                )
            )
        ) {
            return true;
        }
        ;
        $this->_log(
            PB_LOGLEVEL_ERROR, 
            __METHOD__ . "($sRelKey, $iItemvalue)", 
            "Unable to update relation."
        );
        return false;
    }

    /**
     * Delete a single relation from current item
     * @param  string  $sRelKey  key of the relation; a string like 'table:id'
     * @return bool
     */
    public function relDelete(string $sRelKey): bool
    {
        if (!isset($this->_aRelations['_targets'][$sRelKey])) {
            $this->_log(
                PB_LOGLEVEL_ERROR, 
                __METHOD__ . "($sRelKey)", 
                "[$this->_table] The given key does not exist."
            );
            return false;
        }
        if (!isset($this->_aRelations['_targets'][$sRelKey]['_relid'])) {
            $this->_log(
                PB_LOGLEVEL_ERROR, 
                __METHOD__ . "($sRelKey)", 
                "[$this->_table] The key [_relid] was not found."
            );
            return false;
        }
        $oRelation = new pdo_db_relations($this->_pdo);
        return $oRelation->delete($this->_aRelations['_targets'][$sRelKey]['_relid']);
    }

    /**
     * Delete all relations of a single item
     * called by delete(ID) before deleting the item itself
     * @param  integer  $iId  if of an item; default: false (=current item)
     * @return bool
     */
    public function relDeleteAll(int $iId = 0): bool
    {
        $this->_wd(__METHOD__ . "($iId)");

        // when not deleting the current - but a given - id --> store current item + its relations
        if ($iId && $iId !== $this->id()) {
            $tmpItem = $this->_aItem;
            $tmpRel = $this->_aRelations;
            $this->read($iId, true);
        }

        // echo 'Relations: <pre>'.print_r($this->_aRelations, 1).'</pre>'; 
        $bOK = true;
        if (isset($this->_aRelations['_targets'])) {
            foreach (array_keys($this->_aRelations['_targets']) as $sRelKey) {
                $this->_log(
                    PB_LOGLEVEL_INFO, 
                    __METHOD__ . "()", 
                    "Start this->relDelete('$sRelKey')."
                );
                if (!$this->relDelete($sRelKey)) {
                    $bOK = false;
                    break;
                }
                ;
            }
        }
        // restore current item + its relations when not deleting the current but a given id
        if (isset($tmpItem)) {
            $this->_aItem = $tmpItem;
            $this->_aRelations = $tmpRel;
        }
        return $bOK;
    }

    /**
     * Delete all relations of current object type.
     * Called by flush() before deleting all items of a type.
     * @return bool
     */
    public function relFlush(): bool
    {
        if (!$this->_pdo->tableExists("pdo_db_relations")) {
            return true;
        }
        $sSql = 'DELETE FROM `pdo_db_relations` WHERE `from_table`="' . $this->_table . '" OR `to_table`="' . $this->_table . '"';
        return is_array($this->makeQuery($sSql));
    }

    // ----------------------------------------------------------------------
    // GETTER
    // ----------------------------------------------------------------------
    /**
     * Get count of existing items
     * @return integer
     */
    public function count(): int
    {
        $aTmp = $this->makeQuery('SELECT count(id) AS count FROM `' . $this->_table . '` WHERE deleted=0');
        return isset($aTmp[0]['count']) ? $aTmp[0]['count'] : 0;
    }

    /**
     * Get a single property of an item.
     * opposite function of set(KEY, VALUE)
     * @param  string  $sKey2Get  key of your object to set
     * @return mixed
     */
    public function get(string $sKey2Get): mixed
    {
        if (array_key_exists($sKey2Get, $this->_aItem)) {
            return $this->_aItem[$sKey2Get];
        } else {
            return false;
        }
    }

    /**
     * Get array of attribute names
     * @param  bool  $bWithValues  flag: including values? default: false
     * @return array
     */
    public function getAttributes(bool $bWithValues = false): array
    {
        return $bWithValues
            ? $this->_aProperties
            : array_keys($this->_aProperties);
    }

    /**
     * Get array of main attributes to show in overview or to select a relation 
     * @return array
     */
    public function getBasicAttributes(): array
    {
        $aReturn = [];
        foreach ($this->_aProperties as $sKey => $aDefs) {
            if (isset($aDefs['overview']) && $aDefs['overview']) {
                $aReturn[] = $sKey;
            }
        }
        $aReturn[] = 'id';
        if (count($aReturn) == 1) {
            $this->_log(
                PB_LOGLEVEL_WARN, 
                __METHOD__, 
                "The object has no defined overview flag on any attribute"
            );
        }
        return $aReturn;
    }

    /**
     * Get a single line for a database row description
     *
     * It fetches the basic attributes of the item and creates a single line string
     * with values of the item, separated by dashes.
     * If the item has no data, it returns false.
     *
     * @param  array  $aItem  optional: item data; default: current item
     * @return bool|string
     */
    public function getDescriptionLine(array $aItem = []): bool|string
    {
        // Fetch the item data, if not given
        $aItem = count($aItem) ? $aItem : $this->_aItem;

        // If the item has no data, return false
        if (!$aItem) {
            return false;
        }

        // Create a single line string with values of the item, separated by dashes
        $sReturn = '';
        foreach ($this->getBasicAttributes() as $sKey) {
            $sReturn .= $sKey !== 'id' ? $aItem[$sKey] . ' - ' : '';
        }

        // Remove the trailing dash
        return rtrim($sReturn, ' - ');
    }

    /**
     * Get a label for the item.
     * It fetches the basic attributes if needed. 
     * Alternatively it uses the id
     * 
     * @param  array  $aItem     optional: item data; default: current item
     * @param  array  $aColumns  optional: array of columns to show; default: basic attributes
     * @return mixed bool|string
     */
    public function getLabel(array $aItem = [], array $aColumns = []): string
    {
        // which columns to look for?
        $aDefaultColumns = ['label', 'displayname'];

        if (!count($aItem)) {
            $aItem = $this->_aItem;
            if (!$aColumns) {
                $aColumns = [$this->getBasicAttributes()[0]];
            }
        } else {
            if (!$aColumns) {
                foreach ($aDefaultColumns as $sCol2Test) {
                    if (isset($aItem[$sCol2Test])) {
                        $aColumns = [$sCol2Test];
                        break;
                    }
                }
            }
        }

        if (!count($aItem)) {
            return false;
        }

        $sReturn = '';
        if (!$aColumns) {
            $sReturn .= '#' . $aItem['id'];
        } else {
            foreach ($aColumns as $sCol) {
                $sReturn .= ($sReturn ? ', ' : '')
                    . ($aItem[$sCol] ? $aItem[$sCol] : '?')
                ;
            }
        }
        return $sReturn;
    }

    /**
     * For 1:1 lookups: get the label of the related item by a given column.
     * It fetches the current value of the column and returns the label of the
     * connected item of the lookup table
     * 
     * @param string  $sColumn  name of the lookup column
     * @return string|bool
     */
    public function getRelLabel(string $sColumn): string|bool
    {
        $aItem = $this->relReadLookupItem($sColumn);
        return $this->getLabel($aItem);
    }

    /**
     * Get current item as an array
     * @return array
     */
    public function getItem(): array
    {
        return $this->_aItem;
    }

    /**
     * Return or guess the form type of a given attribute
     * If $this->_aProperties[$sAttr]['attr'] was defined then it returns that value.
     * Otherwise the type will be guessed based on the attribute name or create statement.
     * 
     * Guess behaviour by create statement
     * - text -> textarea
     * - varchar -> input type text; maxsize is size of varchar
     * - varchar with more than 1024 byte -> textarea
     * 
     * If attribute starts with 
     *   - "color"    -> input with type "color"
     *   - "date"     -> input with type "date"
     *   - "datetime" -> input with type "datetime-local"
     *   - "email"    -> input with type "email"
     *   - "html"     -> textarea with type "html"
     *   - "month"    -> input with type "month"    !! check browser compatibility
     *   - "number"   -> input with type "number"
     *   - "password" -> input with type "password" !! additional logic required
     *   - "range"    -> input with type "range"
     *   - "tel"      -> input with type "tel"
     *   - "time"     -> input with type "time"
     *   - "url"      -> input with type "url"
     *   - "week"     -> input with type "week"     !! check browser compatibility
     * 
     * @param  string  $sAttr  name of the property
     * @return array|bool
     */
    public function getFormtype(string $sAttr): array|bool
    {
        if (!isset($this->_aProperties[$sAttr])) {
            $this->_log(
                PB_LOGLEVEL_WARN, 
                __METHOD__ . '(' . $sAttr . ')', 
                "Attribute [$sAttr] does not exist"
            );
            return false;
        }

        // guess html form type by attribute name
        // https://www.w3schools.com/html/html_form_input_types.asp
        $aColumnMatcher = [
            ['regex' => '/^color/',    'tag' => 'input',    'type' => 'color'],
            ['regex' => '/^date/',     'tag' => 'input',    'type' => 'date'],
            ['regex' => '/^datetime/', 'tag' => 'input',    'type' => 'datetime-local'],
            ['regex' => '/^email/',    'tag' => 'input',    'type' => 'email'],
            ['regex' => '/^html/',     'tag' => 'textarea', 'type' => 'html'],
            ['regex' => '/^month/',    'tag' => 'input',    'type' => 'month'],
            ['regex' => '/^number/',   'tag' => 'input',    'type' => 'number'],
            ['regex' => '/^password/', 'tag' => 'input',    'type' => 'password'], // TODO: add dummy password in value
            ['regex' => '/^range/',    'tag' => 'input',    'type' => 'range'],
            ['regex' => '/^tel/',      'tag' => 'input',    'type' => 'tel'],
            ['regex' => '/^time/',     'tag' => 'input',    'type' => 'time'],
            ['regex' => '/^url/',      'tag' => 'input',    'type' => 'url'],
            ['regex' => '/^week/',     'tag' => 'input',    'type' => 'week'],
        ];

        $aReturn = [];
        $aReturn['debug'] = [];

        // set given attributes
        if (isset($this->_aProperties[$sAttr]['attr'])) {
            $aReturn = $this->_aProperties[$sAttr]['attr'];
            $aReturn['debug']['_given_attr'] = $this->_aProperties[$sAttr]['attr'];
        }

        // force: set forced attributes 
        if (isset($this->_aProperties[$sAttr]['force'])) {
            $aReturn['debug']['_forced_attr'] = $this->_aProperties[$sAttr]['force'];
            $aReturn = $this->_aProperties[$sAttr]['force'];
        } else {

            // force: set select for lookup
            if (isset($this->_aProperties[$sAttr]['lookup'])) {
                $aReturn['debug']['_force_lookup'] = 1;
                $aLookup = $this->_aProperties[$sAttr]['lookup'];

                // verify lookup data
                if (!isset($aLookup['columns'])) {
                    $this->_log(
                        PB_LOGLEVEL_ERROR, 
                        __METHOD__ . '(' . $sAttr . ')', 
                        "No key [columns] in lookup for object key [$sAttr] to define labels for dropdown.<br>Suggestion: add<br>\"columns\" => \"label\""
                    );
                    return false;
                }
                if (!isset($aLookup['value'])) {
                    $aLookup['value'] = 'id';
                    // $this->_log(PB_LOGLEVEL_ERROR, __METHOD__ . '(' . $sAttr . ')', 'No key [value] in lookup for object key '.$sAttr.' to define values for dropdown.<br>Suggestion: add<br>"value" => "id"<br>in<pre>'.print_r($aLookup, 1).'</pre>');
                    // return false;                            
                }

                /*
                'lookup'=> [
                    'table'=>'objusers',           // which table to connect
                    'columns'=>[ 'displayname' ],  // what volumn show in option fields
                    'value'=>[ 'id' ],             // what column put as value
                    'where'=>'',                   // where clause
                    'size'=> '10',                 // size for select box (1=dropdown)
                    'bootstrap-select' => true     // use bootstrap-select plugin?
                ]
                */

                $sSql = 'SELECT ' . implode(',', $aLookup['columns']) . ', ' . $aLookup['value']
                    . ' FROM ' . $aLookup['table']
                    . (isset($aLookup['where']) && $aLookup['where'] ? ' WHERE ' . $aLookup['where'] : '')
                    . ' ORDER BY ' . implode(' ASC ,', $aLookup['columns']) . ' ASC'
                    . ''
                ;
                // echo "DEBUG: sSql = $sSql<br>";
                $aLookupdata = $this->makeQuery($sSql);
                $aReturn['tag'] = 'select';
                $aReturn['bootstrap-select'] = $aLookup['bootstrap-select'] ?? false;

                unset($aReturn['type']);
                $aReturn['size'] = isset($aLookup['size']) && (int) $aLookup['size'] ? (int) $aLookup['size'] : 1;

                // generate option tags for select box
                $aOptions = [];

                // get relations that match the wanted lookup table and the current column

                // loop over all entries of the looked up table
                if ($aLookupdata) {
                    $aOptions[] = [
                        'value' => '',
                        'label' => '--- {{select_relation_item}} ---',
                    ];
                    foreach ($aLookupdata as $aOptionItem) {
                        $bSelected = $aOptionItem[$aLookup['value']] === $this->get($sAttr);

                        $aOptions[] = [
                            // 'value'=>$aLookup['table'].':'.$aOptionItem['id'],
                            'value' => $aOptionItem[$aLookup['value']],
                            'label' => $this->getLabel($aOptionItem, $aLookup['columns']),
                        ];
                        if ($bSelected) {
                            $aOptions[count($aOptions) - 1]['selected'] = true;
                        }
                    }
                } else {
                    $aOptions[] = [
                        'value' => '',
                        'label' => '--- {{select_no_data_set}} ---',
                    ];
                }
                $aReturn['options'] = $aOptions;

                // echo '<pre>';
                // print_r($aLookupdata); die();
                // print_r($aReturn); die();

            } else {
                $sCreate = $this->_aProperties[$sAttr]['create'];
                // everything before an optional "(" 
                $sBasetype = strtolower(preg_replace('/\(.*$/', '', $sCreate));
                $iSize = (int) strtolower(preg_replace('/.*\((.*)\)$/', '$1', $sCreate));

                $aReturn['debug']['_dbtable_create'] = $sCreate;
                $aReturn['debug']['_basetype'] = $sBasetype;
                $aReturn['debug']['_size'] = $iSize;

                switch ($sBasetype) {
                    case 'int':
                    case 'integer':
                        $aReturn['tag'] ??= 'input';
                        $aReturn['type'] ??= 'number';
                        break;
                        ;
                    case 'text':
                        $aReturn['tag'] ??= 'textarea';
                        $aReturn['rows'] ??= 5;
                        break;
                        ;
                    case 'varchar':
                        if ($iSize) {
                            if ($iSize > 1024) {
                                $aReturn['tag'] ??= 'textarea';
                                $aReturn['maxlength'] ??= $iSize;
                                $aReturn['rows'] ??= 5;
                            } else {
                                $aReturn['tag'] ??= 'input';
                                $aReturn['type'] ??= 'text';
                                $aReturn['maxlength'] ??= $iSize;
                            }
                        } else {
                            $aReturn['tag'] ??= 'input';
                            $aReturn['type'] ??= 'text';
                        }
                        break;
                        ;
                    default:
                        break;
                        ;
                }

                foreach ($aColumnMatcher as $aMatchdata) {
                    if (preg_match($aMatchdata['regex'], $sAttr)) {
                        $aReturn['debug']['_match'] = $aMatchdata;
                        $aReturn['tag'] = $aMatchdata['tag'];
                        $aReturn['type'] = $aMatchdata['type'];
                    }
                }
            }

        }

        $aReturn['name'] = $sAttr;
        $aReturn['label']??=$sAttr;

        $aReturn['markup-pre'] = $this->_aProperties[$sAttr]['markup-pre'] ?? null;
        $aReturn['markup-post'] = $this->_aProperties[$sAttr]['markup-post'] ?? null;

        // if (isset($aReturn['required']) && $aReturn['required']) {
        //     $aReturn['label'] .= ' <span class="required">*</span>';
        // }
  
        // DEBUG:
        // $aReturn['title']=$sAttr . ' --> '.(isset($aReturn['debug']) ? print_r($aReturn['debug'], 1) : 'NO DEBUG');

        return $aReturn;
    }

    /**
     * Get bool if the current dataset item was changed
     * @return bool
     */
    public function hasChange(): bool
    {
        return $this->_bChanged;
    }

    /**
     * Get id of the current item as integer
     * it returns false if there is no id
     * @return int|bool
     */
    public function id(): int|bool
    {
        return (int) $this->_aItem['id'] ? (int) $this->_aItem['id'] : false;
    }

    /**
     * Get current table
     * @return string
     */
    public function getTable(): string
    {
        return $this->_table;
    }

    // ----------------------------------------------------------------------
    // SEARCH
    // ----------------------------------------------------------------------

    /**
     * Search for items in the current table
     * You should use ":<placeholder>" in your sql statements to use
     * prepared statements
     * 
     * @param  array  $aOptions  array with search options
     *                          - columns - array|string
     *                          - where   - array|string
     *                          - order   - array|string
     *                          - limit   - string
     * @param  array  $aData    array with values for prepared statement
     * @return array|bool
     */
    public function search(array $aOptions = [], array $aData = []): array|bool
    {

        $sColumns = '';
        if (isset($aOptions['columns'])) {
            if (is_array($aOptions['columns'])) {
                $sColumns .= implode(",", $aOptions['columns']);
            }
            if (is_string($aOptions['columns'])) {
                $sColumns .= $aOptions['columns'];
            }
        } else {
            $sColumns .= '* ';
        }

        $sWhere = '';
        if (isset($aOptions['where'])) {
            if (is_array($aOptions['where']) && count($aOptions['where'])) {
                foreach ($aOptions['where'] as $sStatement) {
                    $sWhere .= $sStatement . ' ';
                }
            }
            if (is_string($aOptions['where']) && $aOptions['where']) {
                $sWhere .= $aOptions['where'] . ' ';
            }
        }
        $sOrder = '';
        if (isset($aOptions['order'])) {
            if (is_array($aOptions['order']) && count($aOptions['order'])) {
                foreach ($aOptions['order'] as $sStatement) {
                    $sOrder .= ($sOrder ? ', ' : '')
                        . $sStatement . ' ';
                }
                $sOrder = 'ORDER BY ' . $sOrder;
            }
            if (is_string($aOptions['order']) && $aOptions['order']) {
                $sOrder .= $aOptions['order'] . ' ';
            }
        }
        $sLimit = '';
        if (isset($aOptions['limit'])) {
            if (is_string($aOptions['limit']) && $aOptions['limit']) {
                $sLimit .= 'LIMIT ' . $aOptions['limit'] . ' ';
            }
        }

        $sSql = 'SELECT ' . $sColumns
            . ' FROM `' . $this->_table . '` '
            . ($sWhere ? 'WHERE ' . $sWhere . ' ' : '')
            . $sOrder
            . $sLimit;
        $result = $this->makeQuery($sSql, $aData);
        if (is_array($result) && count($result)) {
            return $result;
        }
        return false;
    }

    // ----------------------------------------------------------------------
    // SETTER
    // ----------------------------------------------------------------------

    /**
     * When using a form any not filled input value is an empty string.
     * This helper fixes non string values.
     * 
     * @param  string  $sKey2Set  key of your object to set
     * @param  mixed   $value     new value to set
     * @return mixed
     */
    protected function _fixEmptyPostData(string $sKey2Set, mixed $value): mixed
    {
        if (!isset($_POST[$sKey2Set]) || $value !== "") {
            return $value;
        }
        // empty values coming from forms -> set it to NULL
        $_sCreate = strtolower($this->_aProperties[$sKey2Set]['create']);
        switch ($_sCreate) {
            case 'date':
            case 'datetime':
            case 'int':
            case 'integer':
            case 'num':
            case 'real':
            case 'timestamp':
                $value = NULL;
                break;
                ;
        }
        return $value;
    }

    /**
     * Validate a new value to be set on a property and return bool for success
     * - The general fields (id, timecreated, timeupdated, delete) cannot be set.
     * - validate a field if validate_is set a type or auto detected by "create" key in property
     * - validate a field if validate_regex set regex
     * This method is called in set() but can be executed on its own
     * 
     * @see set()
     * @throws \Exception
     *
     * @param  string  $sKey2Set  key of your object to set
     * @param  mixed   $value     new value to set
     * @return bool
     */
    public function validate(string $sKey2Set, mixed $value): bool
    {

        if (isset($this->_aProperties[$sKey2Set])) {
            $value = $this->_fixEmptyPostData($sKey2Set, $value);

            $_bValError = false;
            $_bValOK = true;

            $_bRequired = $this->_aProperties[$sKey2Set]['required'] ?? false;
            $_sValidate = $this->_aProperties[$sKey2Set]['validate_is'] ?? false;

            // echo "-- validation for attribute '$sKey2Set' => '$value'<br>";
            if ($_bRequired && is_null($value)) {
                $this->_log(
                    PB_LOGLEVEL_ERROR, 
                    __METHOD__, 
                    "[$this->_table] value for [$sKey2Set] is reqired."
                );
                return false;
            }
            if ($_bRequired || !is_null($value)) {

                if ($_sValidate) {
                    // echo "Check $sFunc($value) ... ";
                    switch ($_sValidate) {
                        case 'string':
                            $_bValOK = $_bValOK && is_string($value);
                            $_bValError = $_bValError || !is_string($value);
                            break;
                        case 'integer':
                            $_bValOK = $_bValOK && ctype_digit(strval($value));
                            $_bValError = $_bValError || !ctype_digit(strval($value));
                            break;
                        case 'date':
                            $_bValOK = $_bValOK && strtotime($value);
                            $_bValError = $_bValError || !strtotime($value);
                            break;
                        case 'datetime':
                            $_bValOK = $_bValOK && strtotime($value);
                            $_bValError = $_bValError || !strtotime($value);
                            break;
                        default:
                            throw new Exception(__METHOD__ . " - ERROR: The key [$sKey2Set] cannot be validated with [$_sValidate] - this type is not supported yet.");
                    }
                } else {
                    // echo "Skip 'validate_is'<br>";
                }

                if (isset($this->_aProperties[$sKey2Set]['validate_regex'])) {
                    // echo "Check Regex ".$this->_aProperties[$sKey2Set]['validate_regex']."<br>";
                    $_bValOK = $_bValOK && preg_match($this->_aProperties[$sKey2Set]['validate_regex'], $value);
                    $_bValError = $_bValError || !preg_match($this->_aProperties[$sKey2Set]['validate_regex'], $value);
                } else {
                    // echo "Skip 'validate_regex'<br>";
                }
            }

            // echo "--> OK: " .($_bValOK ? 'true':'false')." | Error: ".($_bValError ? 'true':'false')."<br>";
            if ($_bValOK && !$_bValError) {
                return true;
            } else {
                $this->_log(
                    PB_LOGLEVEL_ERROR, 
                    __METHOD__, 
                    "[$this->_table]  validation for new value '$sKey2Set' failed."
                );
            }
        } else {
            throw new Exception(__METHOD__ . " - ERROR: The unknown key [$sKey2Set] cannot be set for [$this->_table].");
        }
        return false;
    }

    /**
     * Set a single property of an item.
     * - The general fields (id, timecreated, timeupdated, delete) cannot be set.
     * - validate a field if validate_is set a tyoe
     * - validate a field if validate_regex set regex
     * Opposite function of get()
     * 
     * @param  string  $sKey2Set  key of your object to set
     * @param  mixed   $value     new value to set
     * @return bool
     */
    public function set(string $sKey2Set, mixed $value): bool
    {
        $value = $this->_fixEmptyPostData($sKey2Set, $value);

        if ($this->validate($sKey2Set, $value)) {
            if ($this->_aItem[$sKey2Set] !== $value) {
                $this->_bChanged = true;
                $this->_aItem[$sKey2Set] = $value;
            } else {
                // no new value
                $this->_log(
                    PB_LOGLEVEL_INFO, 
                    __METHOD__, 
                    "[$this->_table] value for '$sKey2Set' is unchanged."
                );
            }
            return true;
        } else {
            $this->_log(
                PB_LOGLEVEL_ERROR, 
                __METHOD__, 
                "[$this->_table] value for '$sKey2Set' was not set because validaten failed"
            );
        }
        return false;
    }

    /**
     * Set new values for an item.
     * The general fields (id, created, updated, delete) cannot be set.
     * Opposite function if getItem()
     * @param  array  $aNewValues  new values to set; a subset of this->_aItem
     * @return bool
     */
    public function setItem(array $aNewValues): bool
    {
        $bReturn = true;
        foreach (array_keys($aNewValues) as $sKey) {
            if (!isset($this->_aDefaultColumns[$sKey])) {

                $bDoSet = true;
                if ($aNewValues[$sKey] === false) {
                    if (!preg_match('/(text|char)/i', $this->_aProperties[$sKey]['create'])) {
                        $bDoSet = false;
                    }
                }
                if ($bDoSet) {
                    $bReturn = $bReturn && $this->set($sKey, $aNewValues[$sKey]);
                }
            }
        }
        // return $this->save();
        return $bReturn;
    }
}

// ----------------------------------------------------------------------
