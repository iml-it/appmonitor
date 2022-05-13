<?php
/**
 * ____________________________________________________________________________
 * 
 *  _____ _____ __                   _____         _ _           
 * |     |     |  |      ___ ___ ___|     |___ ___|_| |_ ___ ___ 
 * |-   -| | | |  |__   | .'| . | . | | | | . |   | |  _| . |  _|
 * |_____|_|_|_|_____|  |__,|  _|  _|_|_|_|___|_|_|_|_| |___|_|  
 *                          |_| |_|                              
 *                                                                                                                             
 *                       ___ ___ ___ _ _ ___ ___                                      
 *                      |_ -| -_|  _| | | -_|  _|                                     
 *                      |___|___|_|  \_/|___|_|                                       
 *                                                               
 * ____________________________________________________________________________
 * 
 * notification plugin :: send slack messages
 *
 * @author hahn
 * 
 * 2022-05-13 <axel.hahn@iml.unibe.ch>  created
 */

 /**
 * send slack notifications
 * @param  array  $aOptions  array of options
 */
function send_slack($aOptions){

    // ----- checks
    if(!is_array($aOptions['to']) || !count($aOptions['to'])){
        return false; // no slack channel in server config nor app metadata
    }

    // ----- send
    $data=array(
        'text'       => $aOptions['message'],
        'username'   => '[APPMONITOR]',
        'icon_emoji' => false
    );

    $options = array(
        'http' => array(
        'header'  => 'Content-type: application/x-www-form-urlencoded\r\n',
        'method'  => 'POST',
        'content' => json_encode($data)
        )
    );
    $context  = stream_context_create($options);

    // --- loop over slack targets
    foreach($aOptions['to'] as $sLabel=>$sChannelUrl){
        @file_get_contents($sChannelUrl, false, $context);
    }

    return true;
}
