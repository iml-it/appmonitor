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
 * 
 */
class checkSimple extends appmonitorcheck
{

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
     *         - label         string   a label
     *         - value         float    a number
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
