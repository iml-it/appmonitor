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
 *     [
 *         "name" => "SSL cert",
 *         "description" => "Check SSL certificate of my domain",
 *         "check" => [
 *             "function" => "Cert",
 *             "params" => [
 *                 "url" => "https://www.example.com",
 *                 "warning" => "30",
 *             ],
 *         ],
 *     ]
 * );
 * ____________________________________________________________________________
 * 
 * 2021-10-26  <axel.hahn@iml.unibe.ch>
 * 2022-05-02  <axel.hahn@iml.unibe.ch>  set warning to 21 days (old value was 30); add "critical" param
 * 2022-05-03  <axel.hahn@iml.unibe.ch>  critical limit is a warning only (because app is still functional)
 * 2024-07-23  <axel.hahn@unibe.ch>      php 8 only: use typed variables
 * 2025-03-03  <axel.hahn@unibe.ch>      comment block for host check in DND names
 * 2025-03-19  <axel.hahn@unibe.ch>      add validation rules and parameter description
 */
class checkCert extends appmonitorcheck
{

    /**
     * Self documentation and validation rules
     * @var array
     */
    protected array $_aDoc = [
        'name' => 'Plugin Cert',
        'description' => 'Check if a SSL certificate is still valid … and does not expire soon.',
        'parameters' => [
            'url' => [
                'type' => 'string',
                'required' => true,
                'description' => 'Url to check https://[server}[:{port}] or ssl://[server}[:{port}]',
                'default' => null,
                'regex'=>'/^(https|ssl):\/\/[^\s]+/',
                'example' => '',
            ],
            'verify' => [
                'type' => 'bool',
                'required' => false,
                'description' => 'optional: flag verify certificate; default = true',
                'default' => true,
                'example' => "false",
            ],
            'warning' => [
                'type' => 'int',
                'required' => false,
                'description' => 'optional: count of days to warn; default=21',
                'default' => 21,
                'example' => 30,
            ],
            'critical' => [
                'type' => 'int',
                'required' => false,
                'description' => 'optional: count of days to raise critical; default=5',
                'default' => 5,
                'example' => "7",
            ],
        ],
    ];

    /**
     * Get default group of this check
     * @return string
     */
    public function getGroup(): string
    {
        return 'security';
    }

    /**
     * Check SSL certificate 
     * @param array $aParams
     * [
     *     "url"       optional: url to connect check; default: own protocol + server
     *     "verify"    optional: flag for verification of certificate or check for expiration only; default=true (=verification is on)
     *     "warning"   optional: count of days to warn; default=21 (=3 weeks)
     *     "critical"  optional: count of days to raise critical; default=5
     * ]
     * @return array
     */
    public function run(array $aParams): array
    {
        $sUrl = $aParams["url"] ?? 'http' . ($_SERVER['HTTPS'] ? 's' : '') . '://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'];
        $bVerify = isset($aParams["verify"]) ? !!$aParams["verify"] : true;
        $iWarn = isset($aParams["warning"]) ? (int) ($aParams["warning"]) : 21;
        $iCrtitcal = isset($aParams["critical"]) ? (int) ($aParams["critical"]) : 5;

        $sMessage = "Checked url: $sUrl ... ";
        $certinfo = $this->_certGetInfos($sUrl, $bVerify);
        if (isset($certinfo['_error'])) {
            return [
                RESULT_ERROR,
                $certinfo['_error'] . $sMessage
            ];
        }

        /*
        
            unneeded:
            when verify is true (=default) then it cannot connect with wrong certificate
        
        $sDNS = $certinfo['extensions']['subjectAltName'] ?? false;
        $sHost = parse_url($sUrl, PHP_URL_HOST);
        if (strstr($sDNS, "DNS:$sHost") === false) {
            return [
                RESULT_ERROR,
                "Wrong certificate: $sHost is not listed as DNS alias in [$sDNS]. $sMessage"
            ];
        }
        */

        $iDaysleft = round(($certinfo['validTo_time_t'] - date('U')) / 60 / 60 / 24);
        $sMessage .= 'Issuer: ' . $certinfo['issuer']['O']
            . '; valid from: ' . date("Y-m-d H:i", $certinfo['validFrom_time_t'])
            . ' to ' . date("Y-m-d H:i", $certinfo['validTo_time_t']) . ' '
            . ($iDaysleft ? "($iDaysleft days left)" : "expired since " . (-$iDaysleft) . " days.")
        ;
        if ($iDaysleft <= 0) {
            return [
                RESULT_ERROR,
                'Expired! ' . $sMessage
            ];
        }
        if ($iDaysleft <= $iWarn) {
            return [
                RESULT_WARNING,
                ($iDaysleft <= $iCrtitcal
                    ? 'Expires very soon! '
                    : 'Expires soon. '
                ) . $sMessage
            ];
        }
        // echo '<pre>';
        return [
            RESULT_OK,
            'OK. '
            . ($bVerify ? 'Certificate is valid. ' : '(Verification is disabled; Check for expiration only.) ')
            . $sMessage
        ];
    }

}
