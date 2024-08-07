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
 * CHECK DATABASE CONNECTION USING PDO
 * ____________________________________________________________________________
 * 
 * 2021-10-27  <axel.hahn@iml.unibe.ch>
 * 2024-07-23  <axel.hahn@unibe.ch>      php 8 only: use typed variables
 * 
 */
class checkPdoConnect extends appmonitorcheck
{
    /**
     * Get default group of this check
     * @return string
     */
    public function getGroup(): string
    {
        return 'database';
    }
    /**
     * Check connection to a database using pdo
     * see http://php.net/manual/en/pdo.drivers.php
     * 
     * @param array $aParams
     * [
     *     connect             string   connect string
     *     user                string   db user
     *     password            string   password for db user
     *     timeout             integer  optional timeout in sec; default: 5
     * ]
     * @return array
     */
    public function run(array $aParams): array
    {
        $this->_checkArrayKeys($aParams, "connect,user,password");

        try {
            $db = new PDO(
                $aParams['connect'],
                $aParams['user'],
                $aParams['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

                    // timeout
                    // Not all drivers support this option; mysqli does
                    PDO::ATTR_TIMEOUT => (isset($aParams["timeout"]) && (int) $aParams["timeout"]) ? (int) $aParams["timeout"] : $this->_iTimeoutTcp,
                    // mssql
                    // PDO::SQLSRV_ATTR_QUERY_TIMEOUT => $this->_iTimeoutTcp,  
                ]
            );
            $db = null;
            return [RESULT_OK, "OK: Database was connected with PDO " . $aParams['connect']];
        } catch (PDOException $e) {
            return [RESULT_ERROR, "ERROR: Database was not connected " . $aParams['connect'] . " was not connected. Error " . $e->getMessage()];
        }
    }

}
