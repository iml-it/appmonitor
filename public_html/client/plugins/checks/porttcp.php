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
 * CHECK TCP CONNECTION TO A GIVEN PORT
 * ____________________________________________________________________________
 * 
 * 2021-10-27  <axel.hahn@iml.unibe.ch>
 * 2022-07-05  <axel.hahn@iml.unibe.ch>  send unknown if socket module is not activated.
 * 
 */
class checkPortTcp extends appmonitorcheck{
    /**
     * get default group of this check
     * @param array   $aParams
     * @return array
     */
    public function getGroup(){
        return 'network';
    }

    /**
     * check if system is listening to a given port
     * @param array $aParams
     * array(
     *     port                integer  port
     *     host                string   optional hostname to connect; default: 127.0.0.1
     *     timeout             integer  optional timeout in sec; default: 5
     * )
     * @return boolean
     */
    public function run($aParams) {
        $this->_checkArrayKeys($aParams, "port");

        $sHost = array_key_exists('host', $aParams) ? $aParams['host'] : '127.0.0.1';
        $iPort = (int) $aParams['port'];

        if (!function_exists('socket_create')){
            return [RESULT_UNKNOWN, "UNKNOWN: Unable to perform tcp test. The socket module is not enabled in the php installation."];
        }

        // from http://php.net/manual/de/sockets.examples.php

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            return [RESULT_UNKNOWN, "ERROR: $sHost:$iPort was not checked. socket_create() failed: " . socket_strerror(socket_last_error())];
        }
        // set socket timeout
        socket_set_option(
            $socket,
            SOL_SOCKET,  // socket level
            SO_SNDTIMEO, // timeout option
            array(
              "sec"=>(isset($aParams["timeout"]) && (int)$aParams["timeout"]) ? (int)$aParams["timeout"] : $this->_iTimeoutTcp, // timeout in seconds
              "usec"=>0
              )
            );

        $result = socket_connect($socket, $sHost, $iPort);
        if ($result === false) {
            socket_close($socket);
            return [RESULT_ERROR, "ERROR: $sHost:$iPort failed. " . socket_strerror(socket_last_error($socket))];
        } else {
            socket_close($socket);
            return [RESULT_OK, "OK: $sHost:$iPort was connected."];
        }
    }
    
}
