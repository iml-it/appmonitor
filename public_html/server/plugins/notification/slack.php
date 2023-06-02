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
 * 2023-06-01 <axel.hahn@unibe.ch>      add error variable; strip html in message
 */

class slackNotification{
    /**
     * last error
     * @var string
     */
    var $sError='';

    /**
     * send slack notifications
     * @param  array  $aOptions  array of options
     */
    static public function send($aOptions){

        // ----- checks
        if(!is_array($aOptions['to']) || !count($aOptions['to'])){
            self::$sError=__METHOD__.'$aOptions key has no key named to to define a slack channel'.PHP_EOL;
            return false; // no slack channel in server config nor app metadata
        }

        // ----- send
        $data=[
            'text'       => self::formatMessage($aOptions['message']),
            'username'   => '[APPMONITOR]',
            'icon_emoji' => false
        ];

        $options = [
            'http' => [
                'header'  => 'Content-type: application/x-www-form-urlencoded\r\n',
                'method'  => 'POST',
                'content' => json_encode($data)
            ]
        ];
        $context  = stream_context_create($options);

        // --- loop over slack targets
        $sSendErrors='';
        foreach($aOptions['to'] as $sLabel=>$sChannelUrl){
            if(!@file_get_contents($sChannelUrl, false, $context)){
                $sSendErrors.= ($sSendErrors ? " | " : __METHOD__.' ' )
                    . 'sending to ' .$sLabel . ' ('.$sChannelUrl.') failed.'
                    ;
            }
        }
        self::$sError=$sSendErrors ? $sSendErrors : self::$sError;

        return !$sSendErrors;
    }
    /**
     * generate final email message body by replacing <br> tagsand then stripping html code
     * @param  string  $sMsg  message text
     * @return string
     */
    static public function formatMessage($sMsg){
        return strip_tags(str_replace('<br>', "\n", $sMsg));
    }

    /**
     * get string with the last error message
     * @return string
     */
    static public function error()
    {
        return self::$sError;
    }

}