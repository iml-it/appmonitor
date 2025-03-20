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
 * SIMPLE CHECK
 * ____________________________________________________________________________
 * 
 * 2021-10-27  <axel.hahn@iml.unibe.ch>
 * 2024-07-23  <axel.hahn@unibe.ch>      php 8 only: use typed variables
 * 2025-03-19  <axel.hahn@unibe.ch>      add validation rules and parameter description
 */
class checkSimple extends appmonitorcheck
{
    /**
     * Self documentation and validation rules
     * @var array
     */
    protected array $_aDoc = [
        'name' => 'Plugin Simple',
        'description' => 'Check loaded php modules',
        'parameters' => [
            'value' => [
                'type' => 'string',
                'required' => true,
                'description' => 'Ouput text to describe the result',
                'default' => "",
                'example' => 'My simple example',
            ],
            'result' => [
                'type' => 'int',
                'required' => true,
                'description' => 'Result value to return',
                'min' => RESULT_OK,
                'max' => RESULT_ERROR,

                // doc
                'default' => RESULT_OK,
                'example' => '0 (for "ok")',
            ],
            'type' => [
                'type' => 'string',
                'required' => false,
                'description' => 'Type of the rendetred tile',
                'oneof' => ["counter"],
                'default' => null,
                'example' => 'counter',
            ],
            'count' => [
                'type' => 'float',
                'required' => false,
                'description' => 'If a count exists in a check then a tile will be rendered as a tile',
                'default' => null,
                'example' => '3.14',
            ],
            'visual' => [
                'type' => 'string',
                'required' => false,
                'description' => 'Visualize count value with "simple", "line", "bar,<width>,<count>"',
                'default' => null,
                'example' => 'bar,3,100',
            ],
        ],
    ];

    /**
     * Most simple check: set given values
     * Use this function to add a counter
     * 
     * @param array $aParams
     * array keys:
     *     value               string   description text
     *     result              integer  RESULT_nn
     * 
     *     brainstorming for a future release
     * 
     *     "counter"  optioal: array of counter values
     *         - type          string   a label
     *         - count         float    a number
     *         - type          string   one of simple | bar | line
     * @return array
     */
    public function run(array $aParams): array
    {
        $this->_checkArrayKeys($aParams, "result,value");
        // $this->_setReturn((int) $aParams["result"], $aParams["value"]);
        $aData = [];
        foreach (['type', 'count', 'visual'] as $sMyKey) {
            if (isset($aParams[$sMyKey])) {
                $aData[$sMyKey] = $aParams[$sMyKey];
            }
        }
        return [
            $aParams["result"],
            $aParams["value"],
            count($aData) ? $aData : false
        ];
    }
}
