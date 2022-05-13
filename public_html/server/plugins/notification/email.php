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
 * notification plugin :: send emails
 *
 * @author hahn
 * 
 * 2022-05-13 <axel.hahn@iml.unibe.ch>  created
 */

class emailNotification{
    /**
     * send email notification
     * @param  array  $aOptions  array of options
     */
    static public function send($aOptions){

       // ----- checks
       if(!$aOptions['from']){
           return false; // no from address
       }
   
       if(!count($aOptions['to'])){
           return false; // no to adress in server config nor app metadata
       }
   
       // ----- send
       mail(implode(";", $aOptions['to']),  
           utf8_decode(html_entity_decode($aOptions['subject'])), 
           utf8_decode(html_entity_decode($aOptions['message'])),
           "From: " . $aOptions['from'] . "\r\n" 
           . "Reply-To: " . $aOptions['from'] . "\r\n"
   
           . "X-Priority: 1 (Highest)\r\n"
           . "X-MSMail-Priority: High\r\n"
           . "Importance: High\r\n"
   
       );
       return true;
   }
}