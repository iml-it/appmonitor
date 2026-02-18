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
 * 2026-02-18  axel.hahn@unibe.ch  Lint check using Mago
 */

class app
{

    /**
     * array from objwebapps.lastresult
     * @var array
     */
    protected array $_a = [];

    protected int $_iMinTTL = 60;

    public function __construct(array $aLastresult = [])
    {

    }

    /**
     * Set application data array
     * 
     * @param array $aLastresult  array from objwebapps.lastresult
     * @return void
     */
    public function set(array $aLastresult): void
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
     * @return array  the hostname
     */
    public function get(): array
    {
        return $this->_a;
    }

    /**
     * Return the hostname of the application
     * -> result -> host
     * @see vhost()
     *
     * @return string  the hostname
     */
    public function host(): string
    {
        return (string) ($this->_a["result"]["host"] ?? '');
    }

    /**
     * Return the name of the application
     * -> result -> website
     * 
     * @return string
     */
    public function label(): string
    {
        return (string) ($this->_a["result"]["website"] ?? '');
    }

    /**
     * Return the status of the application
     * as integer eg. RESULT_OK, ...
     * -> result -> result
     * 
     * @return int
     */
    public function status(): int
    {
        return (int) ($this->_a['result']['result'] ?? -1);
    }

    /**
     * Return array of tags
     * meta -> tags
     * 
     * @return array list of tags
     */
    public function tags(): array
    {
        return (array) ($this->_a['meta']['tags'] ?? []);
    }

    /**
     * Return timestamp of the last check request as unix timestamp
     * timestamp
     * 
     * @return int unix timestamp
     */
    public function timestamp(): int
    {
        return (int) ($this->_a['timestamp'] ?? -1);
    }

    /**
     * Get ttl of the application in seconds
     * -> result -> ttl
     * If it is not defined it will return the default ttl value
     * 
     * @return int ttl
     */
    public function ttl(): int
    {
        return (int) ($this->_a['result']['ttl']??$this->_iMinTTL);
    }

    /**
     * Return the monitoring url of the application
     * -> result -> url
     * 
     * @return string
     */
    public function url(): string
    {
        return (string) ($this->_a["result"]["url"] ?? '');
    }

    /**
     * Return the hostname taken from the monitoring url of the application
     * @see host()
     * @return string
     */
    public function vhost(): string
    {
        return parse_url($this->url(), PHP_URL_HOST)??'';
    }

    // ----------------------------------------------------------------------
    // GETTER * CHECKS
    // ----------------------------------------------------------------------

    /**
     * Get array of the checks
     * -> checks
     * 
     * @return array of perfomed checks
     */
    public function checks(): array
    {
        return (array) ($this->_a['checks']??[]);
    }

    // ----------------------------------------------------------------------
    // GETTER * HTTP
    // ----------------------------------------------------------------------

    /**
     * Get the error message and curl error message of a failed request.
     * It is empty if the query was successful
     * -> result -> error
     * 
     * @return string message
     */
    public function http_error(): string 
    {
        return (string) ($this->_a['result']['error'] ?? '');
    }

    /**
     * Get the http response header
     * It is empty if the query failed
     * -> result -> header
     * 
     * @return string message
     */
    public function http_header(): string 
    {
        return (string) ($this->_a['result']['header'] ?? '');
    }

    /**
     * Get the http status code. It is 200 if the request was successful.
     * -> result -> httpstatus
     * 
     * @return int http status code
     */
    public function http_status(): int 
    {
        return (int) ($this->_a['result']['httpstatus'] ?? -1);
    }

    // ----------------------------------------------------------------------
    // GETTER * VERIFY
    // ----------------------------------------------------------------------

    /**
     * get the age of the last result in seconds
     * It returns -1 if -> result -> ts does not exist
     * -> result -> ts
     * 
     * @return int age in seconds
     */
    public function age(): int
    {
        return $this->_a['result']['ts']??false
            ? time() - (int) ($this->_a['result']['ts']??0)
            : -1
        ;
    }

    /**
     * Get bool if the result is outdated = last check is older ttl
     * By default it returns false or true.
     * 
     * If $bAsStatus = true it returns RESULT_...
     * - RESULT_OK:      age < ttl
     * - RESULT_WARNING: age < 2*ttl
     * - RESULT_ERROR:   age >= 2*ttl
     * 
     * @param bool $bAsStatus flag: resturn as integer for status RESULT_...
     *                        default false (return bool)
     * @return bool|int
     */
    public function isOutdated($bAsStatus = false): bool|int
    {
        if(!$bAsStatus){
            return $this->age() <0
                ? true
                : $this->age() > $this->ttl()
                ;
        }

        // ... or as result status

        if($this->age() <0){
            return RESULT_UNKNOWN;
        }
        if ($this->age() < $this->ttl()) {
            return RESULT_OK;
        }
        if ($this->age() < 2 * $this->ttl()) {
            return RESULT_WARNING;
        }
        return RESULT_ERROR;
    }
}