<?php
/**
 * ____________________________________________________________________________
 * 
 *  _____ _____ __                   _____         _ _           
 * |     |     |  |      ___ ___ ___|     |___ ___|_| |_ ___ ___ 
 * |-   -| | | |  |__   | .'| . | . | | | | . |   | |  _| . |  _|
 * |_____|_|_|_|_____|  |__,|  _|  _|_|_|_|___|_|_|_|_| |___|_|  
 *                          |_| |_|                              
 *                           _ _         _                                            
 *                       ___| |_|___ ___| |_                                          
 *                      |  _| | | -_|   |  _|                                         
 *                      |___|_|_|___|_|_|_|   
 *                                                               
 * ____________________________________________________________________________
 * 
 * CHECK SWLITE CONNECTION USING PDO
 * ____________________________________________________________________________
 * 
 * 2021-10-27  <axel.hahn@iml.unibe.ch>
 * 2024-07-23  <axel.hahn@unibe.ch>      php 8 only: use typed variables
 * 
 */
class checkSqliteConnect extends appmonitorcheck
{
    /**
     * Get default group of this check
     * @return string
     */
    public function getGroup()
    {
        return 'database';
    }

    /**
     * check sqlite connection
     * @param array $aParams
     * [
     *     db                  string   full path of sqlite file 
     *     timeout             integer  optional timeout in sec; default: 5
     * ]
     * @return array
     */
    public function run($aParams): array
    {
        $this->_checkArrayKeys($aParams, "db");
        if (!file_exists($aParams["db"])) {
            return [
                RESULT_ERROR,
                "ERROR: Sqlite database file " . $aParams["db"] . " does not exist."
            ];
        }
        if (!isset($aParams['user'])) {
            $aParams['user'] = '';
        }
        if (!isset($aParams['password'])) {
            $aParams['password'] = '';
        }
        try {
            // $db = new SQLite3($sqliteDB);
            // $db = new PDO("sqlite:".$sqliteDB);
            $o = new PDO(
                "sqlite:" . $aParams["db"],
                $aParams['user'],
                $aParams['password'],
                [
                    PDO::ATTR_TIMEOUT => (isset($aParams["timeout"]) && (int) $aParams["timeout"]) ? (int) $aParams["timeout"] : $this->_iTimeoutTcp,
                ]
            );
            return [
                RESULT_OK,
                "OK: Sqlite database " . $aParams["db"] . " was connected"
            ];
        } catch (Exception $e) {
            return [
                RESULT_ERROR, 
                "ERROR: Sqlite database " . $aParams["db"] . " was not connected. " . $e->getMessage()
            ];
        }
    }

}
