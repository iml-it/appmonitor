<?php
/**
 * ======================================================================
 * 
 * INIT A PDO OBJECT
 * 
 * ----------------------------------------------------------------------
 * Author: Axel Hahn
 * Licence: GNU GPL 3.0
 * ----------------------------------------------------------------------
 * 2023-08-26  0.1  ah  first lines
 * 2025-08-19  ___  ah  last changes
 * ======================================================================
 */


namespace axelhahn;

use Exception, PDO, PDOException;

require_once 'pdo-db-base.constants.php';

/**
 * Class for a single PDO connection
 *
 * @author hahn
 */
class pdo_db
{

    /**
     * object of pdo database instance
     * @var object
     */
    public object|null $db;

    /**
     * collected array of log messages
     * var @array
     */
    protected array $_aLogmessages = [];

    /**
     * flag: show mysql errors and debug information?
     * @var boolean
     */
    protected bool $_bShowErrors = false;

    /**
     * flag: show mysql errors and debug information?
     * @var boolean
     */
    protected bool $_bDebug = false;

    protected int $_iLastError = -1;
    protected int $_iLastDBError = -1;

    /**
     * executed queries and metadata or error
     * @var array
     */
    public array $_aQueries = [];

    protected bool $_bHtml = false;

    // ----------------------------------------------------------------------

    /**
     * sql statements for different database types
     * @var array
     */
    protected array $_aSql = [
        'sqlite' => [
            "gettables" => 'SELECT name FROM sqlite_master WHERE type = "table" AND name NOT LIKE "sqlite_%";',
            "getcreate" => 'SELECT sql FROM sqlite_master WHERE name = "%s" ',
            'tableexists' => "SELECT name FROM sqlite_master WHERE type ='table' AND name = '%s';",

            'optimize' => [
                'db' => [
                    'VACUUM'
                ],
                'table' => [],
            ],

            'specialties' => [
                'createAppend' => '',

                'canIndex' => true,
                'canIndexUNIQUE' => true,
                'canIndexFULLTEXT' => false,
            ],
        ],
        'mysql' => [
            "gettables" => 'SHOW TABLES;',
            "getcreate" => "SHOW CREATE TABLE `%s`;",
            'tableexists' => "SHOW TABLES LIKE '%s';",

            'optimize' => [
                'db' => [],
                'table' => [
                    'OPTIMIZE TABLE `%s`',
                ]
            ],

            'specialties' => [
                // replacements
                'AUTOINCREMENT' => 'AUTO_INCREMENT',
                'DATETIME' => 'TIMESTAMP',
                'INTEGER' => 'INT(11)',

                'createAppend' => 'CHARACTER SET utf8 COLLATE utf8_general_ci',

                'canIndex' => true,
                'canIndexUNIQUE' => true,
                'canIndexFULLTEXT' => false,
                'canIndexSPACIAL' => false,
            ],
        ]
    ];

    // ----------------------------------------------------------------------
    // CONSTRUCTOR
    // ----------------------------------------------------------------------

    /**
     * Constructor - sets internal environment variables and checks existence 
     * of the database
     * @param  array $aOptions  array with these keys
     *                          - cfgfile {string} file name of config file for db connection
     *                          - db {array} db connection data for PDO with subkeys
     *                                        - dsn eg. 'mysql:host=addons-web-db;dbname=addons-web;charset=utf8'
     *                                              or 'sqlite:'.__DIR__.'/../../../protected/data/my-example-app.sqlite3'
     *                                        - user
     *                                        - password
     *                                        - options
     *                          - showdebug {bool} enable debug? default: false
     *                          - showerrors {bool} enable error messages? default: false
     */
    public function __construct(array $aOptions = [])
    {

        $sDbConfig = (isset($aOptions['cfgfile']) && is_file($aOptions['cfgfile']))
            ? $aOptions['cfgfile']
            : __DIR__ . '/pdo-db.config.php';

        $aDefaults = file_exists($sDbConfig) ? include $sDbConfig : [];

        if (isset($aOptions['showdebug'])) {
            $this->setDebug($aOptions['showdebug']);
        }
        if (isset($aOptions['showerrors'])) {
            $this->showErrors($aOptions['showerrors']);
        }

        if (isset($aOptions['db'])) {
            $aDefaults = $aOptions['db'];
        }

        $this->_bHtml = !!($_SERVER['HTTP_HOST'] ?? false);
        $this->setDatabase($aDefaults);
    }

    // ----------------------------------------------------------------------
    // PRIVATE FUNCTIONS
    // ----------------------------------------------------------------------

    /**
     * Write debug output if enabled by flag
     * @param  string  $s       string to show
     * @param  string  $sTable  optional: table
     * @return bool
     */
    public function _wd(string $s, string $sTable = ''): bool
    {
        if ($this->_bDebug) {
            $sMsg = "DEBUG: " . ($sTable ? "{ $sTable } - " : '') . " $s";
            echo $this->_bHtml
                ? "<div style=\"color: #888; background: #f8f8f8;\">$sMsg</div>"
                : "$sMsg\n"
            ;
        }
        return true;
    }

    /**
     * Add a log message for current object
     * @param  string  $sLevel    loglevel; one of inf|warn|error
     * @param  string  $sTable    table/ object
     * @param  string  $sMethod   the method where the message comes from
     * @param  string  $sMessage  the error message
     * @return bool
     */
    public function _log(string $sLevel, string $sTable, string $sMethod, string $sMessage): bool
    {
        $this->_aLogmessages[] = [
            'loglevel' => $sLevel,
            'table' => $sTable,
            'method' => $sMethod,
            'message' => $sMessage,
        ];
        if ($sLevel == 'error') {
            $this->_iLastError = count($this->_aLogmessages) - 1;
            if ($this->_bShowErrors) {
                $sMsg = "ERROR: [$sMethod] $sMessage";
                echo $this->_bHtml
                    ? "<div style=\"color: #faa; background: #a00;\">$sMsg</div>"
                    : "$sMsg\n"
                ;
            }
        }
        return true;
    }

    // ----------------------------------------------------------------------
    // SETTER
    // ----------------------------------------------------------------------

    /**
     * Create a PDO connection
     * @param  array $aOptions  array with these keys
     * @return bool
     */
    public function setDatabase(array $aOptions): bool
    {
        $this->db = null;

        // echo '<pre>'.print_r($aOptions, 1).'</pre>';
        if (!$aOptions || !is_array($aOptions)) {
            $this->_log(PB_LOGLEVEL_ERROR, '[DB]', __METHOD__, 'To init a database you need an array as parameter.');
            return false;
        }

        $sDsn = '';
        if (!isset($aOptions['dsn'])) {
            $this->_log(PB_LOGLEVEL_ERROR, '[DB]', __METHOD__, 'No key [dsn] was found in the options.');
            return false;
        } else {
            $sDsn = $aOptions['dsn'];
        }
        try {
            $this->_wd(__METHOD__ . " new PDO($sDsn,[...])");
            $this->db = new PDO(
                $sDsn,
                (isset($aOptions['user']) ? $aOptions['user'] : NULL),
                (isset($aOptions['password']) ? $aOptions['password'] : NULL),
                (isset($aOptions['options']) ? $aOptions['options'] : NULL)
            );
            $type = $this->driver();
            // If the database type is not supported, throw an exception
            if (!isset($this->_aSql[$type])) {
                throw new Exception("Ooops: " . __CLASS__ . " does not support db type [" . $type . "] yet :-/");
            }

        } catch (PDOException $e) {
            $this->_log(PB_LOGLEVEL_ERROR, '[DB]', __METHOD__, 'Failed to initialize the database connection. PDO ERROR: ' . $e->getMessage());
            return false;
        }
        return true;
    }
    /**
     * Enable/ disable debug; database error is visible on enabled debug only
     * @param  bool  $bNewValue  new debug mode; false = off; true = on
     * @return bool
     */
    public function setDebug(bool $bNewValue): bool
    {
        if ($this->_bDebug && !$bNewValue) {
            $this->_wd(__METHOD__ . " - Debug will be turned OFF.");
        }
        $this->_bDebug = !!$bNewValue;
        if ($bNewValue) {
            $this->_wd(__METHOD__ . " - Debug is now ON.");
        }
        return true;
    }

    /**
     * Enable/ disable debug; show error message if they occur
     * @param  string|bool  $bNewValue  new debug mode; false = off; true = on
     * @return bool
     */
    public function showErrors(bool $bNewValue): bool
    {
        $this->_bShowErrors = !!$bNewValue;
        // echo(__METHOD__." - ShowErrors is now ".($this->_bShowErrors ? "ON" : "OFF"));
        $this->_wd(__METHOD__ . " - ShowErrors is now " . ($this->_bShowErrors ? "ON" : "OFF"));
        return true;
    }

    // ----------------------------------------------------------------------
    // GETTER
    // ----------------------------------------------------------------------

    /**
     * Get name of the current driver, eg. "mysql" or "sqlite"
     * If database is initialized yet it returns false
     * @return string|bool
     */
    public function driver(): string|bool
    {
        return $this->db ? $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) : false;
    }

    /**
     * Get specialties of database properties for creating tables
     * @return array
     */
    public function getSpecialties(): array
    {
        return $this->_aSql[$this->driver()]['specialties'] ?? false;
    }

    /**
     * Get the last error message (from a query or a failed method).
     * 
     * @example:
     * to get the last failed database query use lastquery check
     * <code>if($o->error()) { echo $o->lastquery()['error']}</code>
     *
     * @see lastquery()
     * @return string
     */
    public function error(): string
    {
        if ($this->_iLastError >= 0) {
            return $this->_aLogmessages[$this->_iLastError]['message'];
        }
        return '';
    }

    /**
     * Get the last query as array that can have these keys
     *   - method  {string}  name of the method that triggered the query
     *   - sql     {string}  executed sql query
     *   - data    {array}   optional: data array (when using prepare statement)
     *   - time    {float}   optional: execution time in ms
     *   - records {integer} optional: count of returned records on SELECT or affected rows on INSERT, UPDATE or DELETE
     *   - error   {string}  optional:PDO error message
     *
     * @example:
     * to get the last failed database query use lastquery check
     * <code>if($o->error()) { echo $o->lastquery()['error']}</code>
     *
     * @see error()
     * @param bool $bLastError  optional: flag to return the last failed query
     * @return array|bool
     */
    public function lastquery(bool $bLastError = false): array|bool
    {
        if ($bLastError) {
            return $this->_iLastDBError === false
                ? false
                : $this->_aQueries[$this->_iLastDBError]
            ;
        }
        if (count($this->_aQueries)) {
            return $this->_aQueries[count($this->_aQueries) - 1];
        }
        return false;
    }

    /**
     * Get an array with all log messages
     * @return array
     */
    public function logs(): array
    {
        return $this->_aLogmessages;
    }

    /**
     * Get an array with all queries. Each entry can have these keys:
     *   - method  {string}  name of the method that triggered the query
     *   - sql     {string}  executed sql query
     *   - data    {array}   optional: data array (when using prepare statement)
     *   - time    {float}   execution time in ms
     *   - records {integer} count of returned records on SELECT or affected rows on INSERT, UPDATE or DELETE
     *   - error   {string}  optional:PDO error message
     * @return array
     */
    public function queries(): array
    {
        return $this->_aQueries;
    }

    // ----------------------------------------------------------------------
    // db functions
    // ----------------------------------------------------------------------

    /**
     * Check if a table exists in the current database.
     *
     * @param string $table Table to search for.
     * @return bool TRUE if table exists, FALSE if no table found.
     */
    function tableExists(string $table): bool
    {
        // Output debug information
        $this->_wd(__METHOD__);

        // Get the database type
        $type = $this->driver();

        // If the database type is not supported, throw an exception
        if (!isset($this->_aSql[$type]['tableexists'])) {
            throw new Exception("Ooops: " . __CLASS__ . " has no SQL for [tableexists] for type [" . $type . "] yet :-/");
        }

        // Check table
        $result = $this->makeQuery(sprintf($this->_aSql[$type]['tableexists'], $table, 1));
        return $result ? (bool) count($result) : false;
    }

    /**
     * Get an array with all table names
     * @return array
     */
    public function showTables(): array
    {
        // $_aTableList = $this->makeQuery($this->_aSql[$_sDriver]['gettables']);
        $type = $this->driver();
        // If the database type is not supported, throw an exception
        if (!isset($this->_aSql[$type]['gettables'])) {
            throw new Exception("Ooops: " . __CLASS__ . " has no SQL for [gettables] for type [" . $type . "] yet :-/");
        }

        // TODO: use makeQuery() to see it in log
        // difficulty: query result is incompatible FETCH_ASSOC
        $odbtables = $this->db->query($this->_aSql[$type]['gettables']);
        $_aTableList = $odbtables->fetchAll(PDO::FETCH_COLUMN);
        return $_aTableList;
    }
    /**
     * Execute a sql statement and put metadata / error messages into the log
     * @param  string  $sSql   sql statement
     * @param  array   $aData  array with data items; if present prepare statement will be executed 
     * @param  string  $_table optional: table name to add to log
     * @return array|bool
     */
    public function makeQuery(string $sSql, array $aData = [], string $_table = ''): array|bool
    {
        $this->_wd(__METHOD__ . " ($sSql, " . (count($aData) ? "DATA[" . count($aData) . "]" : "NODATA") . ")");
        $aLastQuery = ['method' => __METHOD__, 'sql' => $sSql];
        $_timestart = microtime(true);
        try {
            if (is_array($aData) && count($aData)) {
                $aLastQuery['data'] = $aData;
                $result = $this->db->prepare($sSql);
                $result->execute($aData);
            } else {
                $result = $this->db->query($sSql);
            }
            $aLastQuery['time'] = number_format((float) (microtime(true) - $_timestart) / 1000, 3);
        } catch (PDOException $e) {
            $aLastQuery['error'] = 'PDO ERROR: ' . $e->getMessage();
            $this->_log(
                PB_LOGLEVEL_ERROR,
                $_table,
                __METHOD__,
                "{$_table} Query [$sSql] failed:" . $aLastQuery['error'] . ' See $DB->queries().'
            );
            $this->_aQueries[] = $aLastQuery;
            $this->_iLastDBError = (count($this->_aQueries) - 1);

            return false;
        }
        $_aData = $result->fetchAll(PDO::FETCH_ASSOC);
        $aLastQuery['records'] = count($_aData) ?: $result->rowCount();

        $this->_aQueries[] = $aLastQuery;
        return $_aData;
    }

    /**
     * Optimize database.
     * The performed actions for it depend on the database type.
     * @return bool|array
     */
    function optimize(): bool|array
    {
        $this->_wd(__METHOD__);
        if (!$this->db) {
            $this->_log(PB_LOGLEVEL_WARN, '[DB]', __METHOD__, 'Cannot optimize. Database was not set yet.');
            return false;
        }
        $_sDriver = $this->driver();
        if (!isset($this->_aSql[$_sDriver])) {
            $this->_log(PB_LOGLEVEL_WARN, '[DB]', __METHOD__, 'Cannot optimize. Unknown database driver "' . $_sDriver . '".');
            return false;
        }

        $aResults = [];

        if ($this->_aSql[$_sDriver]['optimize']['db']) {
            $aResults['db'] = [];
            foreach ($this->_aSql[$_sDriver]['optimize']['db'] as $sSqlTemplate) {
                $sSql = $sSqlTemplate;
                $this->_wd(__METHOD__ . ' Optimizing DB - ' . $sSql);
                $result = $this->db->query($sSql);
                $aResults['db'] = [
                    'sql' => $sSql,
                    'result' => $result->fetchAll(PDO::FETCH_ASSOC),
                ];
            }
        }

        if ($this->_aSql[$_sDriver]['optimize']['table']) {
            $_aTableList = $this->showTables();
            foreach ($_aTableList as $sTablename) {
                $aResults['table__' . $sTablename] = [];
                foreach ($this->_aSql[$_sDriver]['optimize']['table'] as $sSqlTemplate) {

                    $sSql = sprintf($sSqlTemplate, $sTablename);
                    $this->_wd(__METHOD__ . ' Optimizing table ' . $sTablename . ' - ' . $sSql);
                    $result = $this->db->query($sSql);
                    $aResults['table__' . $sTablename][] = [
                        'sql' => $sSql,
                        'result' => $result->fetchAll(PDO::FETCH_ASSOC),
                    ];
                }
            }
        }
        return $aResults;
    }

    /**
     * Dump a database to an array.
     * Optional it can write a json file to disk
     * 
     * @see import()
     * @param string $sOutfile  optional: output file name
     * @param array  $aTables   optional: array of tables to dump; default: false (dumps all tables)
     * @return mixed  array of data on success or false on error
     */
    public function dump_old(string $sOutfile = '', array $aTables = []): array|bool
    {

        $aResult = [];
        $aResult['timestamp'] = date("Y-m-d H:i:s");
        $aResult['driver'] = $this->driver();
        $aResult['tables'] = [];

        $this->_wd(__METHOD__);
        if (!$this->db) {
            $this->_log(PB_LOGLEVEL_WARN, '[DB]', __METHOD__, 'Cannot dump. Database was not set yet.');
            return false;
        }
        $_sDriver = $this->driver();
        if (!isset($this->_aSql[$_sDriver])) {
            $this->_log(PB_LOGLEVEL_WARN, '[DB]', __METHOD__, 'Cannot dump. Unknown database driver "' . $_sDriver . '".');
            return false;
        }

        // ----- get all tables
        $_aTableList = count($aTables) ? $aTables : $this->showTables();
        if (!$_aTableList || !count($_aTableList)) {
            $this->_log(PB_LOGLEVEL_WARN, '[DB]', __METHOD__, 'Cannot dump. No tables were found.');
            return false;
        }
        // ----- read each table
        foreach ($_aTableList as $sTablename) {
            $this->_wd(__METHOD__ . ' Reading table ' . $sTablename);
            $aResult[$sTablename] = [];

            $sSqlCreate = sprintf($this->_aSql[$this->driver()]['getcreate'], $sTablename, 1);
            $oCreate = $this->db->query($sSqlCreate);
            // $oCreate = $this->db->query('SELECT sql FROM sqlite_master');
            $aResult['tables'][$sTablename]['create'] = $oCreate->fetchAll(PDO::FETCH_COLUMN)[0];

            $odbtables = $this->db->query('SELECT * FROM `' . $sTablename . '` ');
            $aResult['tables'][$sTablename]['data'] = $odbtables->fetchAll(PDO::FETCH_ASSOC);
        }

        // ----- optional: write to file
        if ($sOutfile) {
            $this->_wd(__METHOD__ . ' Writing to ' . $sOutfile);
            if (!is_dir(dirname($sOutfile))) {
                $this->_log(PB_LOGLEVEL_ERROR, '[DB]', __METHOD__, 'Dump successful. Directory "' . dirname($sOutfile) . '" does not exist. Output file cannot be written.');
                return false;
            } else {
                if (!file_put_contents($sOutfile, json_encode($aResult, JSON_PRETTY_PRINT))) {
                    $this->_log(PB_LOGLEVEL_ERROR, '[DB]', __METHOD__, 'Unable to write to file "' . $sOutfile . '" after successful dumping.');
                    return false;
                }
                ;
            }
        }
        return $aResult;
    }

    /**
     * Helper function for dump(): append a line to a given file
     * @param string  $sFile     filename to write
     * @param string  $sContent  textline to write (without trailing "\n") 
     * @param integer $flags     optional: file_put_contents flags; default: 0 (= create new file or overwrite it)
     * @return bool
     */
    protected function _write2File($sFile, $sContent, $flags = 0)
    {
        if (!file_put_contents($sFile, "$sContent\n", $flags)) {
            $this->_log(PB_LOGLEVEL_ERROR, '[DB]', "dump", "Unable to write /append to file '$sFile'.");
            return false;
        }
        return true;
    }

    /**
     * Dump a database to an array.
     * Optional it can write a json file to disk
     * 
     * @see import()
     * @param string $sOutfile  optional: output file name
     * @param array  $aTables   optional: array of tables to dump; default: false (dumps all tables)
     * @return mixed  array of data on success or false on error
     */
    public function dump(string $sOutfile = '', array $aTables = []): array|bool
    {
        $this->_wd(__METHOD__ . ' Writing to ' . $sOutfile . ' ' . (count($aTables) ? 'tables: ' . implode(', ', $aTables) : '(all tables)'));

        // ----- checks
        if (!$this->db) {
            $this->_log(PB_LOGLEVEL_WARN, '[DB]', __METHOD__, 'Cannot dump. Database was not set yet.');
            return false;
        }
        $_sDriver = $this->driver();
        if (!isset($this->_aSql[$_sDriver])) {
            $this->_log(PB_LOGLEVEL_WARN, '[DB]', __METHOD__, 'Cannot dump. Unknown database driver "' . $_sDriver . '".');
            return false;
        }

        if (!is_dir(dirname($sOutfile))) {
            $this->_log(PB_LOGLEVEL_ERROR, '[DB]', __METHOD__, 'Directory "' . dirname($sOutfile) . '" does not exist. Output file cannot be written.');
            return false;
        }

        // ----- get all tables
        $_aTableList = count($aTables) ? $aTables : $this->showTables();
        if (!$_aTableList || !count($_aTableList)) {
            $this->_log(PB_LOGLEVEL_WARN, '[DB]', __METHOD__, 'Cannot dump. No tables were found.');
            return false;
        }

        // ----- start
        $aMeta = [
            ':meta:' => [
                'timestamp' => date("Y-m-d H:i:s"),
                'driver' => $this->driver(),
                'tables' => $_aTableList
            ]
        ];
        if (!$this->_write2File($sOutfile, json_encode($aMeta))) {
            return false;
        }

        // ----- read each table
        foreach ($_aTableList as $sTablename) {
            $this->_wd(__METHOD__ . ' Reading table ' . $sTablename);

            $sSqlCreate = sprintf($this->_aSql[$this->driver()]['getcreate'], $sTablename, 1);
            $oCreate = $this->db->query($sSqlCreate);
            $sCreateStatement = $oCreate->fetchAll(PDO::FETCH_COLUMN)[0];
            if (!preg_match('/^CREATE TABLE/i', $sCreateStatement)) {
                if (!$this->_write2File($sOutfile, "; Table [$sTablename] does not exist yet.", FILE_APPEND)) {
                    return false;
                }

            } else {

                $aMeta = [
                    $sTablename => [
                        'create' => $sCreateStatement,
                    ],
                ];
                // file_put_contents($sOutfile, json_encode($aMeta)."\n", FILE_APPEND);
                if (!$this->_write2File($sOutfile, json_encode($aMeta), FILE_APPEND)) {
                    return false;
                }


                $odbtables = $this->db->query('SELECT * FROM `' . $sTablename . '` ');
                $iRow = 0;
                foreach ($odbtables->fetchAll(PDO::FETCH_ASSOC) as $aRow) {
                    $iRow++;
                    if ($iRow == 1) {
                        $aMeta = [
                            $sTablename => [
                                'columns' => array_keys($aRow),
                            ],
                        ];
                        // file_put_contents($sOutfile, json_encode($aMeta)."\n", FILE_APPEND);
                        if (!$this->_write2File($sOutfile, json_encode($aMeta), FILE_APPEND)) {
                            return false;
                        }

                    }
                    $aMeta = [
                        $sTablename => [
                            'data' => array_values($aRow),
                        ],
                    ];
                    // file_put_contents($sOutfile, str_replace(PHP_EOL,"\n", json_encode($aMeta))."\n", FILE_APPEND);
                    if (!$this->_write2File($sOutfile, json_encode($aMeta), FILE_APPEND)) {
                        return false;
                    }

                }
            }

        }

        // ----- finish
        $aMeta = [
            ':done:' => [
                'timestamp' => date("Y-m-d H:i:s"),
            ]
        ];
        // file_put_contents($sOutfile, json_encode($aMeta)."\n", FILE_APPEND);
        if (!$this->_write2File($sOutfile, json_encode($aMeta), FILE_APPEND)) {
            return false;
        }

        // echo '<pre>';
        // echo file_get_contents($sOutfile);
        // echo '</pre>';
        // unlink($sOutfile);

        return true;
    }

    /**
     * Import data from a json file; reverse function of dump()
     * TODO: handle options array
     * 
     * @example:
     * $aOptions = [
     *     'global' => [
     *         'drop' => false,
     *         'create' => true, // create table if it does not exist
     *         'import' => true,
     *     ],
     *     // when given, only these tables will be imported
     *     'tables' => [
     *         'table1' => [
     *              // optionally: override global settings
     *             'drop' => false,
     *             'create-if-not-exists' => true,
     *             'import' => true,
     *         ],
     *         'tableN' => [
     *             ...
     *         ]
     ]
     * @see dump()
     * @param  string   $sFile     json file to import
     * @param  array    $aOptions  UNUSED optional: options array with these keys
     *                               - 'global' {array}  options for all tables 
     *                               - 'tables' {array}  options for all tables 
     * @return boolean
     */
    public function import_old(string $sFile, array $aOptions = []): bool
    {
        $this->_wd(__METHOD__);
        if (!$this->db) {
            $this->_log(PB_LOGLEVEL_WARN, '[DB]', __METHOD__, 'Cannot import. Database was not set yet.');
            return false;
        }
        if (!file_exists($sFile)) {
            $this->_log(PB_LOGLEVEL_ERROR, '[DB]', __METHOD__, 'Cannot import. Given file does not extist [' . $sFile . '].');
            return false;
        }
        $aResult = json_decode(file_get_contents($sFile), true);
        if (!$aResult) {
            $this->_log(PB_LOGLEVEL_WARN, '[DB]', __METHOD__, 'Cannot import. No data in file.');
            return false;
        }

        // ----- read each table
        foreach ($aResult['tables'] as $sTablename => $aTable) {
            $this->_wd(__METHOD__ . ' Importing table ' . $sTablename);

            // (1) if table exists then skip creation
            if ($this->tableExists($sTablename)) {
                $this->_log(PB_LOGLEVEL_INFO, '[DB]', __METHOD__, 'Table [' . $sTablename . '] already exists. Skipping.');
            } else {
                $sSql = $aTable['create'];
                if (!$this->makeQuery($sSql)) {
                    $this->_log(PB_LOGLEVEL_ERROR, '[DB]', __METHOD__, 'Creation of missing able failed.');
                    return false;
                }
            }

            // (2) insert data item by item
            foreach ($aTable['data'] as $aRow) {

                $aData = [];
                foreach ($aRow as $k => $v) {
                    $aData[$k] = $v === "" ? null : $v;
                }
                $sSql = 'INSERT INTO `' . $sTablename . '` (' . implode(',', array_keys($aRow)) . ') VALUES (:' . implode(', :', array_keys($aRow)) . ');';
                $this->makeQuery($sSql, $aData);
            }
        }
        return true;
    }

    /**
     * helper function for import() method to handle the bolk import
     * 
     * @param string $sAction     Name of action; one of reset|add|flush
     * @param array  $aRow        for add: new data row
     * @param int    $iLimit      limit of rows before starting INSERT statement
     * @param string $sSqlInsert  sql base statement without values
     * @return bool
     */
    protected function _importBulk($sAction, $aRow=[], $iLimit=1, $sSqlInsert=""){
        static $sSqlData;
        static $aData;
        static $iRows;
        switch ($sAction){
            case 'reset':
                $sSqlData = '';
                $aData = [];
                $iRows = 0;
                break;
            case 'add':
                // $aData[] = $aRow;
                $sSqlData.= ($sSqlData ? ', ' : '') . '(';
                $id=$aRow[0];
                $iCol=0;
                $sSqlRow='';
                foreach($aRow as $val){
                    $iCol++;
                    $datakey="id_{$id}__col_{$iCol}";
                    $sSqlRow.= ($sSqlRow ? ", ": "") . ":$datakey";
                    $aData[$datakey]=$val;
                }
                $sSqlData.=$sSqlRow . ") ";
                $iRows++;
                break;
            case 'flush':
                break;
            default:
                $this->_log(PB_LOGLEVEL_ERROR, '[DB]', __METHOD__, " Unknown action $sAction");
                return false;
        }

        if(( $iRows>=$iLimit || $sAction=='flush') && count($aData)){
            $this->_log(PB_LOGLEVEL_INFO, '[DB]', __METHOD__, " Flushing / importing bulk data after $iRows datasets - $sAction");
            $sSql="$sSqlInsert $sSqlData";
            $aReturn = $this->makeQuery($sSql, $aData);
            if ($aReturn === false) {
                return false;
            }

            $this->_importBulk('reset');

            return true;
        }
        return true;
    }

    /**
     * Import data from a json file; reverse function of dump()
     * TODO: handle options array
     * 
     * @example:
     * $aOptions = [
     *     'global' => [
     *         'drop' => true,
     *         'create' => true,
     *         'import' => true,
     *         'rows2instert' => 30
     *     ],
     *     // TODO: add options for each table
     *     // when given, only these tables will be imported
     *     'tables' => [
     *         'table1' => [
     *              // optionally: override global settings
     *             'drop' => false,
     *             'create' => true,
     *             'import' => true,
     *         ],
     *         'tableN' => [
     *             ...
     *         ]
     ]
     * @see dump()
     * @param  string   $sFile     json file to import
     * @param  array    $aOptions  UNUSED optional: options array with these keys
     *                               - 'global' {array}  options for all tables 
     *                                       'drop'   bool  Drop a table before input; default: true
     *                                       'create' bool  Create a table if not exists; default: true
     *                                       'import' bool  Import data; default: true
     *                               - 'tables' {array}  options for all tables - TODO
     * @return boolean
     */
    public function import(string $sFile, array $aOptions = []): bool
    {
        $this->_wd(__METHOD__);
        if (!$this->db) {
            $this->_log(PB_LOGLEVEL_WARN, '[DB]', __METHOD__, 'Cannot import. Database was not set yet.');
            return false;
        }
        if (!file_exists($sFile)) {
            $this->_log(PB_LOGLEVEL_ERROR, '[DB]', __METHOD__, 'Cannot import. Given file does not extist [' . $sFile . '].');
            return false;
        }

        $aOpt = [
            'global' => [
                'drop' => $aOptions['global']['drop'] ?? true,
                'create' => $aOptions['global']['create'] ?? true,
                'import' => $aOptions['global']['import'] ?? true,
                'rows2instert' => $aOptions['global']['rows2instert'] ?? 30,
            ],
            // when given, only these tables will be imported
            'tables' => [],
        ];

        $aColumns = [];
        $iLine = 0;
        $iRows2insert = $aOpt['global']['rows2instert'];
        $bCheckUpdateOrInsert = false;
        $sSqlInsertBase='';
        foreach (file($sFile) as $jsonLine) {
            $iLine++;
            $aTmp = json_decode($jsonLine, true);
            if (!is_array($aTmp)) {
                continue;
            }
            $sTablename = array_key_first($aTmp);
            $aData = $aTmp[$sTablename];

            switch ($sTablename) {
                case ':meta:':
                    $this->_wd(__METHOD__ . ": Metadata $jsonLine");
                    break;
                case ':done:':
                    $this->_wd(__METHOD__ . ": Last line was reached. $jsonLine");
                    $this->_log(PB_LOGLEVEL_INFO, '[DB]', __METHOD__, "Last line was reached. $jsonLine.");

                    if(!$this->_importBulk('flush', [], 1, $sSqlInsertBase)){
                        $this->_log(PB_LOGLEVEL_ERROR, '[DB]', __METHOD__, " Import failed. Stopped on <br>query $sSql <br>Line: $iLine: $jsonLine<br>" . $this->error());
                        return false;
                    }

                    break;
                default:
                    $sType = array_key_first($aData);

                    $this->_wd(__METHOD__ . ": table $sTablename ($sType)");
                    switch ($sType) {
                        case 'create':

                            // get Create statement for current object with current database driver
                            $sSqlCreate = sprintf($this->_aSql[$this->driver()]['getcreate'], $sTablename, 1);
                            $oCreate = $this->db->query($sSqlCreate);
                            $sSqlCreateTable = $oCreate->fetchAll(PDO::FETCH_COLUMN)[0]??'';

                            if(!$sSqlCreateTable){
                                $sSqlCreateTable = $aData[$sType];
                            }

                            if ($aOpt['global']['drop'] ?? false) {
                                if ($this->makeQuery('DROP TABLE IF EXISTS `' . $sTablename . '`') === false) {
                                    $this->_log(PB_LOGLEVEL_ERROR, '[DB]', __METHOD__, "Dropping of table $sTablename failed.");
                                    return false;
                                }
                            }

                            if ($this->tableExists($sTablename)) {
                                $this->_wd(__METHOD__ . "Table [$sTablename] already exists. Skipping.");
                                $iRows2insert=1;
                                $bCheckUpdateOrInsert=true;
                            } else {
                                if ($aOpt['global']['create'] ?? false) {

                                    if ($this->makeQuery($sSqlCreateTable) === false) {
                                        $this->_log(PB_LOGLEVEL_ERROR, '[DB]', __METHOD__, "Creation of missing table [$sTablename] failed.");
                                        return false;
                                    }
                                } else {
                                    $this->_log(PB_LOGLEVEL_ERROR, '[DB]', __METHOD__, "Table [$sTablename] does not exist and creation flag 'global'->'create' was not set to true.");
                                    return false;
                                }
                            }
                            break;
                        case 'columns':
                            $this->_wd(__METHOD__ . "Table [$sTablename] set columns " . print_r($aData[$sType], 1));

                            if($sSqlInsertBase){
                                if(!$this->_importBulk('flush', $aData[$sType], 1, $sSqlInsertBase)){
                                    $this->_log(PB_LOGLEVEL_ERROR, '[DB]', __METHOD__, " Import failed. Stopped on <br>query $sSql <br>Line: $iLine: $jsonLine<br>" . $this->error());
                                    return false;
                                }
                                $sSqlInsertBase='';
                            }

                            $aColumns = $aData[$sType];
                            $sSqlInsertBase='INSERT INTO `' . $sTablename . '` '
                                            .'(' . implode(',', $aColumns) . ') VALUES '
                                            ;
                            if(!$this->_importBulk('reset', [], 1, "")){
                            }
                            break;
                        case 'data':
                            if ($aOpt['global']['import'] ?? false) {
                                if($iRows2insert==1){
                                    
                                    $aRow = [];
                                    $iCol = 0;
                                    foreach ($aColumns as $sColname) {
                                        $aRow[$sColname] = $aData[$sType][$iCol];
                                        $iCol++;
                                    }                                

                                    $sSql = $sSqlInsertBase
                                        .' (:' . implode(', :', $aColumns) . ')'
                                        ;
                                    if($bCheckUpdateOrInsert) {
                                        $sSqlTest = "SELECT id FROM `$sTablename` WHERE id=:id;";
                                        if ($this->makeQuery($sSqlTest, [':id' => $aRow['id']])) {
                                            // id exists ... we need to update
                                            $sValues = '';
                                            $aUpdateColumns = $aColumns;
                                            unset($aUpdateColumns[0]);// remove "id"
                                            foreach ($aUpdateColumns as $sColname) {
                                                $sValues .= ($sValues ? ', ' : '') . "`$sColname` = :$sColname";
                                            }
                                            $sSql = "UPDATE `$sTablename` SET $sValues WHERE `id` = :id";
                                        }
                                    }
                                    $aReturn = $this->makeQuery($sSql, $aRow);
                                    if ($aReturn === false) {
                                        $this->_log(PB_LOGLEVEL_ERROR, '[DB]', __METHOD__, " Import failed. Stopped on <br>query $sSql <br>Line: $iLine: $jsonLine<br>" . $this->error());
                                        return false;
                                    }
                                } else {
                                    if(!$this->_importBulk('add', $aData[$sType], $iRows2insert, $sSqlInsertBase)){
                                        $this->_log(PB_LOGLEVEL_ERROR, '[DB]', __METHOD__, " Import failed. Stopped on <br>query $sSql <br>Line: $iLine: $jsonLine<br>" . $this->error());
                                        return false;
                                    }
                                }
                            }
                            break;
                        default:
                            $this->_log(PB_LOGLEVEL_ERROR, '[DB]', __METHOD__, " unknown type $sType <br>Line: $iLine: $jsonLine");
                    }
                    break;
            }
        }
        return true;
    }

    /**
     * Analyze given backup file and generate a summary
     * If an error occured then the key 'error' will be set
     * If OK you get the list of tables and count of datasets to import
     * The returned array is like this:
     * 
     * [
     *     'file' => <filename>,
     *     'completed' => <bool>,
     *     'meta' => <array>,
     *     'rows' => [
     *         <table1> => <rowcount>,
     *         <tableN> => <rowcount>,
     *      ],
     *     'counters' => [
     *         'tables' => <number_of_tables>,
     *         'roes' => <number_of_rows_total>,
     *      ],
     * ]
     * 
     * ... or if an error occured:
     * [
     *     'file' => <filename>,
     *     'completed' => false,
     *     'error' => <string>,
     * ]
     * 
     * @param string $sFile
     * @return array
     */
    public function dumpAnalyzer(string $sFile): array
    {
        $aReturn = [
            'file' => $sFile,
            'completed' => false,
        ];
        $this->_wd(__METHOD__);
        if (!$this->db) {
            $aReturn['error'] = 'Database was not set yet.';
            $this->_log(PB_LOGLEVEL_WARN, '[DB]', __METHOD__, $aReturn['error']);
            return $aReturn;
        }
        if (!file_exists($sFile)) {
            $aReturn['error'] = 'File does not extist.';
            $this->_log(PB_LOGLEVEL_ERROR, '[DB]', __METHOD__, 'Given file does not extist [' . $sFile . '].');
            return $aReturn;
        }

        $iRows = 0;
        foreach (file($sFile) as $jsonLine) {
            $aTmp = json_decode($jsonLine, true);
            if (!is_array($aTmp)) {
                continue;
            }
            $sTablename = array_key_first($aTmp);
            $aData = $aTmp[$sTablename];
            switch ($sTablename) {
                case ':meta:':
                    $this->_wd(__METHOD__ . ": Metadata $jsonLine");
                    $aReturn['meta'] = $aData;
                    break;
                case ':done:':
                    $aReturn['completed'] = $aData;
                default:
                    $sType = array_key_first($aData);
                    if ($sType == 'create') {
                        $aReturn['rows'][$sTablename] = 0;
                    }
                    if ($sType == 'data') {
                        $aReturn['rows'][$sTablename]++;
                        $iRows++;
                    }
                    break;
            }
        }
        $aReturn['counters']=[
            'tables'=>count($aReturn['rows']??[]),
            'rows'=>$iRows,
        ];
        return $aReturn;
    }
}

// ----------------------------------------------------------------------
