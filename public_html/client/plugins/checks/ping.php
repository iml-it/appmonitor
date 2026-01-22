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
 * CHECK PING RESPONSE TIME VIA ICMP
 * ____________________________________________________________________________
 * 
 * 2022-07-05  <axel.hahn@iml.unibe.ch>
 * 2022-09-16  <axel.hahn@iml.unibe.ch>  read error before closing socket.
 * 2022-11-22  <axel.hahn@iml.unibe.ch>  Use exec with detecting MS Win for the ping parameter for count of pings
 * 2024-07-23  <axel.hahn@unibe.ch>      php 8 only: use typed variables
 * 2025-03-19  <axel.hahn@unibe.ch>      add validation rules and parameter description
 * 2026-01-09  <axel.hahn@unibe.ch>      add timout parameter -W in ping command; use default from $_aDoc
 */
class checkPing extends appmonitorcheck
{

    /**
     * Self documentation and validation rules
     * @var array
     */
    protected array $_aDoc = [
        'name' => 'Plugin Ping',
        'description' => 'Check if a given host can be pinged.',
        'parameters' => [
            'host' => [
                'type' => 'string',
                'required' => false,
                'description' => 'Hostname to ping; default: 127.0.0.1',
                'regex' => '/^[a-z0-9\_\-\.]/i',

                // doc
                'default' => "127.0.0.1",
                'example' => 'www.example.com',
            ],
            'timeout' => [
                'type' => 'int',
                'required' => false,
                'description' => 'Timeout for -W parameter; in seconds (on MS Windows in milliseconds); default: 3',
                'regex' => '/^[0-9]*/i',

                // doc
                'default' => 5,
                'example' => 2,
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
     * Check ping to a target
     * @param array $aParams
     * [
     *     host                string   optional hostname to connect; default: 127.0.0.1
     *     timeout             integer  Timeout for -W parameter; in seconds (float) on MS Windows in milliseconds); default: 5
     * ]
     * @return array
     */
    public function run(array $aParams): array
    {
        $sHost = $aParams['host'] ?? $this->_aDoc["parameters"]["host"]["default"];

        $sParamCount = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? "n" : "c";
        $iRepeat = 1;

        $iTimeout= $aParams['timeout']??$this->_aDoc["parameters"]["timeout"]["default"];
        $sParamTimeout = "-W $iTimeout";
        $sCommand = "ping -$sParamCount $iRepeat $sParamTimeout $sHost 2>&1";
        exec($sCommand, $aOut, $iRc);
        $sOut = implode("\n", $aOut);

        if ($iRc > 0) {
            if($iRc==127){
                return [RESULT_UNKNOWN, "ping is not installed.\n$sOut"];
            }
            return [RESULT_ERROR, "ERROR: $iRc ping to $sHost failed ($sCommand).\n$sOut"];
        }
        return [RESULT_OK, "OK: ping to $sHost ($sCommand)\n$sOut"];

        /*
            Socket functions require root :-/

        if (!function_exists('socket_create')){
            return [RESULT_UNKNOWN, "UNKNOWN: Unable to perform ping test. The socket module is not enabled in the php installation."];
        }

        // ICMP ping packet with a pre-calculated checksum
        $package = "\x08\x00\x7d\x4b\x00\x00\x00\x00PingHost";
        $socket  = socket_create(AF_INET, SOCK_RAW, getprotobyname('icmp'));
        // TODO
        if(!$socket){
                die("ERROR: unable to create socket");
        }
        socket_set_option(
            $socket, 
            SOL_SOCKET, 
            SO_RCVTIMEO, 
            [
                "sec"=>(isset($aParams["timeout"]) && (int)$aParams["timeout"]) ? (int)$aParams["timeout"] : $this->_iTimeoutTcp, // timeout in seconds
                "usec"=>0
            ]
        );

        $start = microtime(true);
        socket_connect($socket, $sHost, 0);
        $connect = socket_send($socket, $package, strLen($package), 0);
        if($connect){
            if (socket_read($socket, 255)){
                $result = microtime(true) - $start;
                socket_close($socket);
                return [RESULT_OK, 
                    "OK: ping to $sHost",
                    [
                        'type'=>'counter',
                        'count'=>$result,
                        'visual'=>'line',
                    ]

                ];
            } else {
                $aResult=[RESULT_ERROR, "ERROR: ping to $sHost failed after connect." . socket_strerror(socket_last_error($socket))];
                socket_close($socket);
                return $aResult;
            }
        } else {
            return [RESULT_ERROR, "ERROR: ping to $sHost failed. " . socket_strerror(socket_last_error($socket))];
        }

        */
    }

}
