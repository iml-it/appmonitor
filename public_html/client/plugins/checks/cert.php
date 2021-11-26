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
 * CUSTOM CHECK
 * 
 * Check expiration of ssl certificate
 * ____________________________________________________________________________
 * 
 * USAGE:
 * 
 * $oMonitor->addCheck(
 *     array(
 *         "name" => "SSL cert",
 *         "description" => "Check SSL certificate of my domain",
 *         "check" => array(
 *             "function" => "Cert",
 *             "params" => array(
 *                 "url" => "https://www.example.com",
 *                 "warning" => "21",
 *             ),
 *         ),
 *     )
 * );
 * ____________________________________________________________________________
 * 
 * 2021-10-26  <axel.hahn@iml.unibe.ch>
 * 
 */
class checkCert extends appmonitorcheck{
    /**
     * check SSL certificate 
     * @param array $aParams
     * array(
     *     "url"       optional: url to connect check; default: own protocol + server
     *     "verify"    optional: flag for verification of certificate or check for expiration only; default=true (=verification is on)
     *     "warning"   optional: count of days to warn; default=30
     * )
     * @return boolean
     */
    public function run($aParams) {
        $sUrl = isset($aParams["url"]) 
                ? $aParams["url"] 
                : 'http'. ($_SERVER['HTTPS'] ? 's' : '') . '://' . $_SERVER['SERVER_NAME'] .':' . $_SERVER['SERVER_PORT']
                ;
        $bVerify = isset($aParams["verify"])  ? !!$aParams["verify"] : true;
        $iWarn   = isset($aParams["warning"]) ? (int)($aParams["warning"]) : 30;

        $sMessage="Checked url: $sUrl ... ";
        $certinfo=$this->_certGetInfos($sUrl, $bVerify);
        if(isset($certinfo['_error'])){
            return [
                RESULT_ERROR, 
                $certinfo['_error'] . $sMessage
            ];
        }
        
        $sDNS=isset($certinfo['extensions']['subjectAltName']) ? $certinfo['extensions']['subjectAltName'] : false;
        $sHost=parse_url($sUrl,PHP_URL_HOST);
        if(strstr($sDNS, 'DNS:'.$sHost)===false){
            return [
                RESULT_ERROR, 
                'Wrong certificate: '.$sHost.' is not listed as DNS alias in ['.$sDNS.']  ' . $sMessage
            ];
        }
        
        $iDaysleft = round(($certinfo['validTo_time_t'] - date('U')) / 60 / 60 / 24);
        $sMessage.= 'Issuer: '. $sIssuer=$certinfo['issuer']['O'] 
                . '; valid from: '. date("Y-m-d H:i", $certinfo['validFrom_time_t'])
                . ' to '.date("Y-m-d H:i", $certinfo['validTo_time_t']).' '
                . ( $iDaysleft ? "($iDaysleft days left)" : "expired since ".(-$iDaysleft)." days.")
                ;
        if ($iDaysleft<0) {
            return [
                RESULT_ERROR, 
                'Expired! ' . $sMessage
            ];
        }
        if ($iDaysleft<=$iWarn) {
            return [
                RESULT_WARNING, 
                'Expires soon. ' . $sMessage
            ];
        }
        // echo '<pre>';
        return [
            RESULT_OK, 
            'OK. ' 
                .($bVerify ? 'Certificate is valid. ' : '(Verification is disabled; Check for expiration only.) ' )
                . $sMessage
        ];
    }

}
