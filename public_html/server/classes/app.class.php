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
 * WIP Getter class for application data of a single application
 *
 * @author hahn
 * 
 * 2025-12-10  axel.hahn@unibe.ch  first lines
 */

class app
{

    /**
     * array from objwebapps.lastresult
     * @var array
     */
    protected $_a = [];

    protected $_iMinTTL = 60;

    public function __construct(array $aLastresult = [])
    {

    }

    /**
     * Set application data array
     * 
     * @param array $aLastresult  array from objwebapps.lastresult
     * @return void
     */
    public function set(array $aLastresult)
    {
        $this->_a = $aLastresult;
    }

    // ----------------------------------------------------------------------
    // GETTER * META
    //
    //    "result": {
    //        "result": 0,
    //        "host": "4b437c3a07e8",
    //        "website": "Appmonitor server",
    //        "summary": {
    //            "total": 16,
    //            "0": 14,
    //            "1": 0,
    //            "2": 1,
    //            "3": 1
    //        },
    //        "ts": 1765359237,
    //        "url": "https:\/\/proxy\/client\/check-appmonitor-server.php",
    //        "ttl": 300,
    //        "header": "HTTP\/2 200 \r\nx-powered-by: PHP\/8.4.15\r\ncache-control: cache\r\nmax-age: 300\r\nstrict-transport-security: max-age=63072000; includeSubDomains; preload\r\nx-frame-options: SAMEORIGIN\r\nx-content-type-options: nosniff\r\nx-xss-protection: 1; mode=block\r\nfeature-policy: sync-xhr 'self'\r\nreferrer-policy: strict-origin-when-cross-origin\r\ncontent-type: application\/json\r\ndate: Wed, 10 Dec 2025 09:33:57 GMT\r\nserver: Apache\/2.4.65 (Debian)",
    //        "headerarray": {
    //            "_status": "HTTP\/2 200 ",
    //            "_statuscode": "200",
    //            "x-powered-by": " PHP\/8.4.15",
    //            "cache-control": " cache",
    //            "max-age": " 300",
    //            "strict-transport-security": " max-age=63072000; includeSubDomains; preload",
    //            "x-frame-options": " SAMEORIGIN",
    //            "x-content-type-options": " nosniff",
    //            "x-xss-protection": " 1; mode=block",
    //            "feature-policy": " sync-xhr 'self'",
    //            "referrer-policy": " strict-origin-when-cross-origin",
    //            "content-type": " application\/json",
    //            "date": " Wed, 10 Dec 2025 09:33:57 GMT",
    //            "server": " Apache\/2.4.65 (Debian)"
    //        },
    //        "httpstatus": 200,
    //        "error": false,
    //        "curlerrorcode": 0,
    //        "curlerrormsg": "",
    //        "resultcounter": [
    //            14,
    //            0,
    //            0,
    //            0
    //        ]
    //    },
    // ----------------------------------------------------------------------

    /**
     * Return set application data
     *
     * @return string  the hostname
     */
    public function get(): array
    {
        return $this->_a;
    }

    /**
     * Return the hostname of the application
     *
     * @return string  the hostname
     */
    public function host(): string
    {
        return $this->_a["result"]["host"];
    }

    /**
     * Return the name of the application
     * 
     * @return string
     */
    public function label(): string
    {
        return $this->_a["result"]["website"] ?? '';
    }

    /**
     * Return the status of the application
     * as integer eg. RESULT_OK, ...
     * 
     * @return int
     */
    public function status(): int
    {
        return $this->_a['result']['result'] ?? -1;
    }

    /**
     * Return array of tags
     * 
     * @return array
     */
    public function tags(): array
    {
        return $this->_a['result']['tags'] ?? [];
    }

    /**
     * Return the monitoring url of the application
     * 
     * @return string
     */
    public function url(): string
    {
        return $this->_a["result"]["url"] ?? '';
    }

    // ----------------------------------------------------------------------
    // GETTER * CHECKS
    // ----------------------------------------------------------------------

    /**
     * Get array of the checks
     * 
     * @return array of perfomed checks
     */
    public function checks(): array
    {
        return $this->_a['result']['checks'];
    }

    // ----------------------------------------------------------------------
    // GETTER * VERIFY
    // ----------------------------------------------------------------------

    /**
     * get the age of the last result in seconds
     * 
     * @return int
     */
    public function age(): int
    {
        return $this->_a['result']['ts']??false
            ? time() - $this->_a['result']['ts'] 
            : 0
        ;
    }

    /**
     * Get bool if the result is outdated = last check is older ttl
     * By default it returns false or true.
     * If $bAsStatus = true it returns RESULT_...
     * - RESULT_OK:      age < ttl
     * - RESULT_WARNING: age < 2*ttl
     * - RESULT_ERROR:   age >= 2*ttl
     * 
     * @param bool $bAsStatus flag: resturn as integer for status RESULT_...
     *                        default false (return bool)
     * @return bool
     */
    public function isOutdated($bAsStatus = false): bool|int
    {
        if(!$bAsStatus){
            return $this->age() > $this->_a['result']['ttl'] ?? false;
        }

        // ... or as result status

        if ($this->age() < $this->_a['result']['ttl'] ?? 0) {
            return RESULT_OK;
        }
        if ($this->age() < 2*max($this->_a['result']['ttl']??0, $this->_iMinTTL)) {
            return RESULT_WARNING;
        }
        return RESULT_ERROR;
    }
}