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
 * CHECK IF NEEDED PHP MODULES ARE INSTALLED
 * ____________________________________________________________________________
 * 
 * 2022-05-06  <axel.hahn@iml.unibe.ch>  first lines
 * 2024-07-23  <axel.hahn@unibe.ch>      php 8 only: use typed variables
 * 2025-03-19  <axel.hahn@unibe.ch>      add validation rules and parameter description
 */
class checkPhpmodules extends appmonitorcheck
{
    /**
     * Self documentation and validation rules
     * @var array
     */
    protected array $_aDoc = [
        'name' => 'Plugin Phpmodules',
        'description' => 'Check loaded php modules',
        'parameters' => [
            'required' => [
                'type' => 'array',
                'required' => true,
                'description' => 'List of php modules that are required',

                // doc
                'default' => [],
                'example' => '["curl", "PDO"]',
            ],
            'optional' => [
                'type' => 'array',
                'required' => false,
                'description' => 'List of php modules that are optional. If one is missing, the status is set to warning.',
                'default' => [],
                'example' => '["gd"]',
            ],
        ],
    ];

    /**
     * Get default group of this check
     * @return string
     */
    public function getGroup(): string
    {
        return 'service';
    }

    /**
     * Check if system is listening to a given port
     * @param array $aParams
     * [
     *     required     array  list of required php modules
     *     optional     array  optional: list of optional php modules
     * ]
     * @return array
     */
    public function run(array $aParams): array
    {
        $sOut = '';
        $bHasError = false;
        $bHasWarning = false;
        // $this->_checkArrayKeys($aParams, "required");

        // --- get all modules
        $aAllMods = get_loaded_extensions(false);

        // --- check required modules
        if (isset($aParams['required']) && count($aParams['required'])) {
            $sOut .= 'Required: ';
            foreach ($aParams['required'] as $sMod) {
                $sOut .= $sMod . '=';
                if (!array_search($sMod, $aAllMods) === false) {
                    $sOut .= 'OK;';
                } else {
                    $bHasError = true;
                    $sOut .= 'MISS;';
                }
            }
        }
        // --- check optional modules
        if (isset($aParams['optional']) && count($aParams['optional'])) {
            $sOut .= ($sOut ? '|' : '') . 'Optional: ';
            foreach ($aParams['optional'] as $sMod) {
                $sOut .= $sMod . '=';
                if (!array_search($sMod, $aAllMods) === false) {
                    $sOut .= 'OK;';
                } else {
                    $bHasWarning = true;
                    $sOut .= 'MISS;';
                }
            }
        }

        // --- return result
        if ($bHasError) {
            return [RESULT_ERROR, "ERROR: " . $sOut];
        }
        if ($bHasWarning) {
            return [RESULT_WARNING, "WARNING: " . $sOut];
        }
        return [RESULT_OK, "OK: " . $sOut];
    }

}
