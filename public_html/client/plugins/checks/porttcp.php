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
 * 2022-09-16  <axel.hahn@iml.unibe.ch>  read error before closing socket.
 * 2022-12-05  <axel.hahn@unibe.ch>      add @ sign at socket functions to prevent warning
 * 2024-07-23  <axel.hahn@unibe.ch>      php 8 only: use typed variables
 * 2025-03-19  <axel.hahn@unibe.ch>      add validation rules and parameter description
 */
class checkPortTcp extends appmonitorcheck
{
    /**
     * Self documentation and validation rules
     * @var array
     */
    protected array $_aDoc = [
        'name' => 'Plugin PortTcp',
        'description' => 'Check if the local server or another host is listening to a given port number.',
        'parameters' => [
            'host' => [
                'type' => 'string',
                'required' => true,
                'description' => 'optional: hostname to connect to; if unavailable 127.0.0.1 will be tested',
                'regex' => '/./',

                // doc
                'default' => null,
                'example' => 'mysql:host=$aDb[server];port=3306;dbname=$aDb[database]',
            ],
            'port' => [
                'type' => 'int',
                'required' => true,
                'description' => 'port number to check',
                'default' => null,
                'min' => 0,
                'max' => 65535,
                'example' => '22',
            ],
            'timeout' => [
                'type' => 'int',
                'required' => false,
                'description' => 'optional timeout in sec; default: 5',
                'default' => 5,
                'example' => '3',
            ],
        ],
    ];

    /**
     * Get default group of this check
     * @return string
     */
    public function getGroup(): string
    {
        return 'network';
    }

    /**
     * Check if system is listening to a given port
     * @param array $aParams
     * [
     *     port                integer  port
     *     host                string   optional hostname to connect; default: 127.0.0.1
     *     timeout             integer  optional timeout in sec; default: 5
     * ]
     * @return array
     */
    public function run(array $aParams): array
    {
        $this->_checkArrayKeys($aParams, "port");

        $sHost = $aParams['host'] ?? '127.0.0.1';
        $iPort = (int) $aParams['port'];

        if (!function_exists('socket_create')) {
            return [RESULT_UNKNOWN, "UNKNOWN: Unable to perform tcp test. The php-sockets module is not enabled in the php installation."];
        }

        // from http://php.net/manual/de/sockets.examples.php

        $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            return [RESULT_UNKNOWN, "ERROR: $sHost:$iPort was not checked. socket_create() failed: " . socket_strerror(socket_last_error())];
        }
        // set socket timeout
        socket_set_option(
            $socket,
            SOL_SOCKET,  // socket level
            SO_SNDTIMEO, // timeout option
            [
                "sec" => (isset($aParams["timeout"]) && (int) $aParams["timeout"]) ? (int) $aParams["timeout"] : $this->_iTimeoutTcp, // timeout in seconds
                "usec" => 0
            ]
        );

        $result = @socket_connect($socket, $sHost, $iPort);
        if ($result === false) {
            $aResult = [RESULT_ERROR, "ERROR: $sHost:$iPort failed. " . socket_strerror(socket_last_error($socket))];
            socket_close($socket);
            return $aResult;
        } else {
            socket_close($socket);
            return [RESULT_OK, "OK: $sHost:$iPort was connected."];
        }
    }

}
