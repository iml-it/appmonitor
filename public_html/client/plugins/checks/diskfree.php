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
 * CHECK FOR FREE DISKSPACE
 * ____________________________________________________________________________
 * 
 * 2021-10-26  <axel.hahn@iml.unibe.ch>
 * 2024-07-23  <axel.hahn@unibe.ch>      php 8 only: use typed variables
 * 2025-01-02  <www.axel-hahn.de>        update output
 * 2025-03-19  <axel.hahn@unibe.ch>      add validation rules and parameter description
 */
class checkDiskfree extends appmonitorcheck
{

    /**
     * Self documentation and validation rules
     * @var array
     */
    protected array $_aDoc = [
        'name' => 'Plugin Diskfree',
        'description' => 'Check if a given filesystem / directory that it has enough space.',
        'parameters' => [
            'directory' => [
                'type' => 'string',
                'required' => true,
                'description' => 'directory to check',
                'default' => null,
                'regex'=>'^https?:\/\/[^\s]+',
                'example' => '',
            ],
            'warning' => [
                'type' => 'string',
                'required' => false,
                'description' => 'size for warning level',
                'default' => 21,
                'example' => "1.25GB",
            ],
            'critical' => [
                'type' => 'string',
                'required' => true,
                'description' => 'size for critical level',
                'default' => 5,
                'example' => "500.7MB",
            ],
        ],
    ];

    /**
     * Get default group of this check
     * @return string
     */
    public function getGroup(): string
    {
        return 'disk';
    }

    /**
     * Check free disk space on a given directory
     * @param array $aParams
     * [
     *     "directory"   directory that must exist
     *     "warning"     space for warning (optional)
     *     "critical"    minimal space
     * ]
     * @return array
     */
    public function run(array $aParams): array
    {
        $this->_checkArrayKeys($aParams, "directory,critical");

        $sDirectory = $aParams["directory"];
        if (!is_dir($sDirectory)) {
            return [
                RESULT_ERROR,
                "directory [$sDirectory] does not exist. Maybe it is wrong or is not mounted."
            ];
        }

        $iWarn = isset($aParams["warning"]) ? $this->_getSize($aParams["warning"]) : false;
        $iCritical = $this->_getSize($aParams["critical"]);
        $iSpaceLeft = disk_free_space($sDirectory);

        $sMessage = $this->_getHrSize($iSpaceLeft) . ' left in [' . $sDirectory . '].';

        if ($iWarn) {
            if ($iWarn <= $iCritical) {
                header('HTTP/1.0 503 Service Unavailable');
                die("ERROR in a Diskfree check - warning value must be larger than critical.<pre>" . print_r($aParams, true));
            }
            if ($iWarn < $iSpaceLeft) {
                return [
                    RESULT_OK,
                    "$sMessage Warning level is not reached yet (still " . $this->_getHrSize($iSpaceLeft - $iWarn) . " over warning limit)."
                ];
            }
            if ($iWarn > $iSpaceLeft && $iCritical < $iSpaceLeft) {
                return [
                    RESULT_WARNING,
                    $sMessage . ' Warning level ' . $this->_getHrSize($iWarn) . ' was reached (space is ' . $this->_getHrSize($iWarn - $iSpaceLeft) . ' below warning limit; still ' . $this->_getHrSize($iSpaceLeft - $iCritical) . ' over critical limit).'
                ];
            }
        }
        // check space
        if ($iCritical < $iSpaceLeft) {
            return [RESULT_OK, $sMessage . ' Minimum is not reached yet (still ' . $this->_getHrSize($iSpaceLeft - $iCritical) . ' over critical limit).'];
        } else {
            return [RESULT_ERROR, $sMessage];
        }
    }

}
