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
 * CUSTOM CHECK FOR FILE OBJECTS
 * 
 * Check files, directories, links if the exist or not, if they are accessible 
 * or not.
 * ____________________________________________________________________________
 * 
 * 2021-10-26  <axel.hahn@iml.unibe.ch>
 * 2024-07-23  <axel.hahn@unibe.ch>      php 8 only: use typed variables
 * 2025-03-01  <axel.hahn@unibe.ch>      fix check for files that must be absent
 * 2025-03-19  <axel.hahn@unibe.ch>      add validation rules and parameter description
 */
class checkFile extends appmonitorcheck
{
    /**
     * Self documentation and validation rules
     * @var array
     */
    protected array $_aDoc = [
        'name' => 'Plugin File',
        'description' => 'Check existing or absent file objects: files, directories, softlinks',
        'parameters' => [
            'filename' => [
                'type' => 'string',
                'required' => true,
                'description' => 'filename or directory to check',
                'default' => "",
                'regex' => '/./',
                'example' => '/var/www/myfile.txt',
            ],
            'exists' => [
                'type' => 'bool',
                'required' => false,
                'description' => 'true = file object must exist (default); false = file object must not exist',
                'default' => true,
                'example' => "true",
            ],
            'dir' => [
                'type' => 'bool',
                'required' => false,
                'description' => 'If true then check if fileobject is a directory',
                'default' => null,
                'example' => "false",
            ],
            'file' => [
                'type' => 'bool',
                'required' => false,
                'description' => 'If true then check if fileobject is a file',
                'default' => null,
                'example' => "true",
            ],
            'link' => [
                'type' => 'bool',
                'required' => false,
                'description' => 'If true then check if fileobject is a file',
                'default' => null,
                'example' => "true",
            ],
            'executable' => [
                'type' => 'bool',
                'required' => false,
                'description' => 'If true then check if fileobject has executable permissions; if false it must be not executable',
                'default' => null,
                'example' => "false",
            ],
            'readable' => [
                'type' => 'bool',
                'required' => false,
                'description' => 'If true then check if fileobject is readable; if false it must be not readable',
                'default' => null,
                'example' => "true",
            ],
            'writable' => [
                'type' => 'bool',
                'required' => false,
                'description' => 'If true then check if fileobject is writable; ; if false it must be not writable',
                'default' => null,
                'example' => "true",
            ],
        ],
    ];

    /**
     * Get default group of this check
     * @param array   $aParams - see run() method
     * @return string
     */
    public function getGroup(array $aParams = []): string
    {
        $sReturn = 'file';
        if (isset($aParams['dir'])) {
            $sReturn = 'folder';
        }
        foreach (['exists', 'executable', 'readable', 'writable'] as $sFlag) {
            if (isset($aParams[$sFlag]) && !$aParams[$sFlag]) {
                $sReturn = 'deny';
            }
        }
        return $sReturn;
    }

    /**
     * Check a file
     * @param array $aParams
     * [
     *     "filename"    directory that must exist
     *     "exists"      "filename" must exist/ must be absent
     *     "dir"         filetype directory
     *     "file"        filetype file
     *     "link"        filetype symbolic link
     *     "executable"  flag executable
     *     "readable"    flag is readable
     *     "writable"    flag is writable
     * ]
     * @return array
     */
    public function run(array $aParams): array
    {
        $aOK = [];
        $aErrors = [];
        $this->_checkArrayKeys($aParams, "filename");
        $sFile = $aParams["filename"];

        if (isset($aParams['exists'])) {
            $sMyflag = 'exists=' . ($aParams['exists'] ? 'yes' : 'no');
            if (
                file_exists($sFile) && $aParams['exists']
                || !file_exists($sFile) && !$aParams['exists']
            ) {
                $aOK[] = $sMyflag;
            } else {
                $aErrors[] = $sMyflag;
            }
        }
        foreach (['dir', 'executable', 'file', 'link', 'readable', 'writable'] as $sFiletest) {
            if (isset($aParams[$sFiletest])) {
                $sTestCmd = 'return is_' . $sFiletest . '("' . $sFile . '");';
                if (eval ($sTestCmd) && $aParams[$sFiletest]) {
                    $aOK[] = $sFiletest . '=' . ($aParams[$sFiletest] ? 'yes' : 'no');
                } else {
                    $aErrors[] = $sFiletest . '=' . ($aParams[$sFiletest] ? 'yes' : 'no');
                }
            }
        }
        $sMessage = (count($aOK) ? ' flags OK: ' . implode('|', $aOK) : '')
            . ' ' . (count($aErrors) ? ' flags FAILED: ' . implode('|', $aErrors) : '')
        ;
        if (count($aErrors)) {
            return [
                RESULT_ERROR,
                "file test [$sFile] $sMessage"
            ];
        } else {
            return [
                RESULT_OK,
                "file test [$sFile] $sMessage"
            ];
        }
    }

}
