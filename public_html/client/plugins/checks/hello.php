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
 * EXAMPLE CUSTOM CHECK THAT SENDS A HELLO
 * 
 * A plugin always is loaded in clases/appmonitor-checks.class.php
 * Have look there for the used protected classes
 * ____________________________________________________________________________
 * 
 * PARAMS:
 *   message {string}  a custom message to display
 * 
 * USAGE:
 * 
 * $oMonitor->addCheck(
 *     [
 *         "name" => "hello plugin",
 *         "description" => "test a plugin ... plugins/checkHello.php",
 *         "check" => [
 *             "function" => "Hello",
 *             "params" => [
 *                 "message" => "Here I am",
 *             ],
 *         ],
 *     ]
 * );
 * ____________________________________________________________________________
 * 
 * 2019-06-05  <axel.hahn@iml.unibe.ch>
 * 2024-07-23  <axel.hahn@unibe.ch>      php 8 only: use typed variables
 * 2025-03-19  <axel.hahn@unibe.ch>      add validation rules and parameter description
 */
class checkHello extends appmonitorcheck
{
    /**
     * Self documentation and validation rules
     * @var array
     */
    protected array $_aDoc = [
        'name' => 'Plugin Hello',
        'description' => 'Show a simple message',
        'parameters' => [
            'message' => [
                'type' => 'string',
                'required' => true,
                'description' => 'Message to show',
                'default' => "",
                'regex' => '/./',
                'example' => 'Here I am',
            ],
        ],
    ];

    /**
     * Run the check
     * @param array   $aParams
     * @return array
     */
    public function run(array $aParams): array
    {

        // --- (1) verify if array key(s) exist:
        $this->_checkArrayKeys($aParams, "message");


        // --- (2) do something magic


        // --- (3) response
        // see method appmonitorcheck->_setReturn()
        // 
        // {integer} you should use a RESULT_XYZ constant:
        //              RESULT_OK|RESULT_UNKNOWN|RESULT_WARNING|RESULT_ERROR
        // {string}  output text 
        // {array}   optional: counter data
        //              type   => {string} "counter"
        //              count  => {float}  value
        //              visual => {string} one of bar|line|simple (+params)
        //           
        return [
            RESULT_OK,
            'Hello world! My message is: ' . $aParams['message']
        ];
    }
}
