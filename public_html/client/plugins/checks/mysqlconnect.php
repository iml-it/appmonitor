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
 * CHECK DATABASE CONNECTION WITH MYSQLI
 * ____________________________________________________________________________
 * 
 * 2021-10-27  <axel.hahn@iml.unibe.ch>
 * 
 */
class checkMysqlConnect extends appmonitorcheck{
    /**
     * check mysql connection to a database using mysqli realconnect
     * @param array $aParams
     * array(
     *     server              string   database hostname / ip address
     *     user                string   db user
     *     password            string   password for db user
     *     db                  string   schema / database name
     *     port                integer  optional: port
     *     timeout             integer  optional timeout in sec; default: 5
     * )
     */
    public function run($aParams) {
        $this->_checkArrayKeys($aParams, "server,user,password,db");
        $mysqli=mysqli_init();
        if(!$mysqli){
            return [RESULT_ERROR, 'ERROR: mysqli_init failed.'];
        }
        if (!$mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, (isset($aParams["timeout"]) && (int)$aParams["timeout"]) ? (int)$aParams["timeout"] : $this->_iTimeoutTcp)) {
            return [RESULT_ERROR, 'ERROR: setting mysqli_options failed.'];
        }

        $db = (isset($aParams["port"]) && $aParams["port"]) 
                ? $mysqli->real_connect($aParams["server"], $aParams["user"], $aParams["password"], $aParams["db"], $aParams["port"])
                : $mysqli->real_connect($aParams["server"], $aParams["user"], $aParams["password"], $aParams["db"])
                ;
        if ($db) {
            $mysqli->close();
            return [RESULT_OK, "OK: Mysql database " . $aParams["db"] . " was connected"];
            return true;
        } else {
            return [
                RESULT_ERROR, 
                "ERROR: Mysql database " . $aParams["db"] . " was not connected. Error ".mysqli_connect_errno() .": ". mysqli_connect_error()
            ];
        }
    }
}
