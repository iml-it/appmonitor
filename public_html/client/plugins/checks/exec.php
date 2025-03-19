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
 * CUSTOM CHECK BASED ON SHELL COMMANDS
 * 
 * Execute a shell command.
 * ____________________________________________________________________________
 * 
 * 2022-09-19  <axel.hahn@iml.unibe.ch>
 * 2024-07-23  <axel.hahn@unibe.ch>      php 8 only: use typed variables
 * 2025-03-19  <axel.hahn@unibe.ch>      add validation rules and parameter description
 */
class checkExec extends appmonitorcheck
{

    /**
     * Self documentation and validation rules
     * @var array
     */
    protected array $_aDoc = [
        'name' => 'Plugin Exec',
        'description' => 'Execute a shell command.',
        'parameters' => [
            'command' => [
                'type' => 'string',
                'required' => true,
                'description' => 'Command line',
                'default' => null,
                'regex'=>'/./',
                'example' => '[command] [parameters] 2>&1',
            ],
            'output' => [
                'type' => 'bool',
                'required' => false,
                'description' => 'size for warning level',
                'default' => 21,
                'example' => "1.25GB",
            ],
            'exitOk' => [
                'type' => 'array',
                'required' => false,
                'description' => 'array of integers for ok exitcodes',
                'default' => null,
                'example' => "",
            ],
            'exitWarn' => [
                'type' => 'array',
                'required' => false,
                'description' => 'array of integers for warning exitcodes',
                'default' => null,
                'example' => "",
            ],
            'exitCritical' => [
                'type' => 'array',
                'required' => false,
                'description' => 'array of integers for critical exitcodes',
                'default' => null,
                'example' => "",
            ],
        ],
    ];

    /**
     * Get default group of this check
     * @return string
     */
    public function getGroup()
    {
        return 'service';
    }

    /**
     * Check execution of a command
     * @param array $aParams
     * [
     *     "command"        {string} command to execute
     *     "output"         {bool}   flag: show output; default: true
     *
     *     "exitOK"         {array}  array of integegers for ok exitcodes
     *     "exitWarn"       {array}  array of integegers for exitcodes with warning
     *     "exitCritical"   {array}  array of integegers for exitcodes that result in an error
     *
     *     // TODO ... MAYBE
     *     "searchOK"       {string} search string that must be found in output
     *     "searchWarn"     {string} if search string is found check returns with warning
     *     "searchCritical" {string} if search string is found check returns with critical
     * ]
     * @return array
     */
    public function run(array $aParams): array
    {
        $this->_checkArrayKeys($aParams, "command");
        $_sCmd = $aParams['command'];
        $_bShowOutput = isset($aParams['output']) ? !!$aParams['output'] : true;

        $_aRcOK = isset($aParams['exitOK']) ? $aParams['exitOK'] : [];
        $_aRcWarning = isset($aParams['exitWarn']) ? $aParams['exitWarn'] : [];
        $_aRcCritical = isset($aParams['exitCritical']) ? $aParams['exitCritical'] : [];

        $_sMode = 'default';
        if (count($_aRcOK) + count($_aRcWarning) + count($_aRcCritical)) {
            $_sMode = 'exitcode';
        }

        exec($_sCmd, $aOutput, $iRc);
        $_sOut = $_bShowOutput ? '<br>' . implode("<br>", $aOutput) : '';

        switch ($_sMode) {
            // non-zero exitcode is an error
            case "default":
                if ($iRc) {
                    return [
                        RESULT_ERROR,
                        'command failed with exitcode ' . $iRc . ': [' . $_sCmd . ']' . $_sOut
                    ];
                } else {
                    return [
                        RESULT_OK,
                        "OK [$_sCmd] $_sOut"
                    ];
                }
                ;
                // break;
                ;

            // handle given custom exitcodes
            case "exitcode":
                if (in_array($iRc, $_aRcCritical)) {
                    return [
                        RESULT_ERROR,
                        "Critical exitcode $iRc detected: [$_sCmd] $_sOut"
                    ];
                }
                if (in_array($iRc, $_aRcWarning)) {
                    return [
                        RESULT_WARNING,
                        "Warning exitcode $iRc detected: [$_sCmd] $_sOut"
                    ];
                }
                if ($iRc == 0 || in_array($iRc, $_aRcOK)) {
                    return [
                        RESULT_OK,
                        "OK exitcode $iRc detected: [$_sCmd] $_sOut"
                    ];
                }
                return [
                    RESULT_UNKNOWN,
                    "UNKNOWN - unhandled exitcode $iRc detected: [$_sCmd] $_sOut"
                ];
            case "search":
                return [
                    RESULT_UNKNOWN,
                    "UNKNOWN method [$_sMode] - is not implemented yet."
                ];
                // break;
                ;
            default:
                return [
                    RESULT_UNKNOWN,
                    'UNKNOWN mode [' . htmlentities($_sMode) . '].'
                ];
        } // switch($_sMode)
    }

}
