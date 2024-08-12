<?php
/**
 * 
 * IML TINY ROUTER
 * 
 * --------------------------------------------------------------------------------<br>
 * <br>
 * THERE IS NO WARRANTY FOR THE PROGRAM, TO THE EXTENT PERMITTED BY APPLICABLE <br>
 * LAW. EXCEPT WHEN OTHERWISE STATED IN WRITING THE COPYRIGHT HOLDERS AND/OR <br>
 * OTHER PARTIES PROVIDE THE PROGRAM ?AS IS? WITHOUT WARRANTY OF ANY KIND, <br>
 * EITHER EXPRESSED OR IMPLIED, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED <br>
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE. THE <br>
 * ENTIRE RISK AS TO THE QUALITY AND PERFORMANCE OF THE PROGRAM IS WITH YOU. <br>
 * SHOULD THE PROGRAM PROVE DEFECTIVE, YOU ASSUME THE COST OF ALL NECESSARY <br>
 * SERVICING, REPAIR OR CORRECTION.<br>
 * <br>
 * --------------------------------------------------------------------------------<br>
 * @version 1.4
 * @author Axel Hahn
 * @link https://git-repo.iml.unibe.ch/iml-open-source/tinyrouter-php-class
 * @license GPL
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL 3.0
 * @package -
 * 
 * 2024-07-18  axel.hahn@unibe.ch  php 8 only: use typed variables
 **/

namespace iml;

class tinyrouter
{

    /**
     * Requested url
     * @var string
     */
    public string $sUrl = '';

    /**
     * Used http method
     * @var string
     */
    public string $sMethod = '';

    /**
     * Array of defined routes
     * @var array
     */
    public array $aRoutes = [];

    /**
     * Array of matching route and solved vars
     * @var array
     */
    protected array $aMatch = [];

    /**
     * Constructor
     * @param  $aRoutes  array   array of routes
     * @param  $sUrl     string  incoming url
     * @return boolean
     */
    public function __construct(array $aRoutes = [], string $sUrl = '')
    {
        $this->setRoutes($aRoutes);
        $this->setUrl($sUrl);
    }

    // ----------------------------------------------------------------------
    // protected functions
    // ----------------------------------------------------------------------

    /**
     * Detect last matching route item and set it in $this->aMatch
     * if no route matches then it returns false
     * @return bool
     */
    protected function _getRoute(): bool
    {
        $aReturn = [];
        $this->aMatch = [];
        if (!$this->sUrl || !count($this->aRoutes)) {
            return false;
        }
        $aReqParts = $this->getUrlParts();
        foreach ($this->aRoutes as $aRoutecfg) {
            $sRoute = $aRoutecfg[0];
            $aParts = $this->getUrlParts($sRoute);
            if (count($aParts) == count($aReqParts)) {
                $iPart = 0;
                $aVars = [];
                $bFoundRoute = false;
                foreach ($aParts as $sPart) {
                    // detect @varname or @varname:regex in a routing
                    if (isset($sPart[0]) && $sPart[0] == "@") {
                        $sValue = $aReqParts[$iPart];
                        preg_match('/\@([a-z]*):(.*)/', $sPart, $match);

                        if (isset($match[2])) {
                            // if a given regex does not match the value then abort
                            if (!preg_match("/^$match[2]$/", $sValue)) {
                                $bFoundRoute = false;
                                break;
                            }
                        }
                        // store a variable without starting @
                        $aVars[$match[1]] = $sValue;
                    } else {
                        // no @ ... if string of url parts in url and route
                        // do not match then we abort
                        if ($aParts[$iPart] !== $aReqParts[$iPart]) {
                            $bFoundRoute = false;
                            break;
                        }
                    }
                    $bFoundRoute = true;
                    // $aVars[$iPart]=$sValue;
                    $iPart++;
                }
                if ($bFoundRoute) {
                    $aReturn = [
                        "request-method" => (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : false),
                        "request-url" => $this->sUrl,
                        "route" => $aRoutecfg[0],
                        "callback" => $aRoutecfg[1],
                        "vars" => $aVars
                    ];
                }
            }
        }
        $this->aMatch = $aReturn;
        return count($this->aMatch) > 0;
    }

    // ----------------------------------------------------------------------
    // public functions :: setter
    // ----------------------------------------------------------------------

    /**
     * Set routes configuration.
     * It calls the _getRoute method to find the matching route
     * 
     * @param  array  $aRoutes  list of [ route, more params ... ]
     * @return boolean
     */
    public function setRoutes(array $aRoutes = []): bool
    {
        if (is_array($aRoutes) && count($aRoutes)) {
            $this->aRoutes = $aRoutes;
            $this->_getRoute();
        }
        return true;
    }

    /**
     * Set incoming url, add the request behind protocol and domain.
     * It calls the _getRoute method to find the matching route
     * 
     * @param  string  sUrl  url to fetch; /api/v1/productbyid/3424084
     * @return boolean
     */
    public function setUrl(string $sUrl): bool
    {
        $this->sUrl = $sUrl;
        $this->_getRoute();
        return true;
    }

    // ----------------------------------------------------------------------
    // public functions :: getter
    // ----------------------------------------------------------------------

    /**
     * Helper function: get url request parts as array
     * @param  string  $sUrl  url to handle; /api/v1/productbyid/3424084
     * @return array
     */
    public function getUrlParts(string $sUrl = ''): array
    {
        if (!$sUrl) {
            $sUrl = $this->sUrl;
        }
        $aReqParts = explode('/', $sUrl);
        if ($sUrl[0] == '/') {
            array_shift($aReqParts);
        }
        return $aReqParts;
    }

    /**
     * Get last matching route item
     * If no route was matching then it returns []
     * $this->aMatch is set in _getRoute()
     * @see _getRoute()
     * 
     * @return array
     */
    public function getRoute(): array
    {
        return $this->aMatch;
    }

    /**
     * Get the callback item of the matching route
     * If no route was matching it returns false
     * 
     * @return string|boolean
     */
    public function getCallback(): array|string|bool
    {
        return $this->aMatch['callback'] ?? false; 
    }

    /**
     * Get the variables as keys in route parts with starting @ character
     * If no route was matching it returns false
     * 
     * @return array|boolean
     */
    public function getVars()
    {
        return $this->aMatch['vars'] ?? false;
    }
    /**
     * Get a single variable in route parts with starting @ character
     * If no route was matching or the variable key doesn't exist it returns false
     * 
     * @param  string  $sVarname  name of the variable
     * @return string|boolean
     */
    public function getVar(string $sVarname): string|bool
    {
        return $this->aMatch['vars'][$sVarname] ?? false;
    }

    /**
     * Get an array with next level route entries releative to the current route
     * @return array
     */
    public function getSubitems(): array
    {
        $sKey = 'allowed_subkeys';
        $aReturn = [$sKey => []];
        $iCurrent = count($this->getUrlParts());
        foreach ($this->aRoutes as $aRoutecfg) {
            $sRoute = $aRoutecfg[0];
            if (count($this->getUrlParts($sRoute)) - 1 == $iCurrent && strstr($sRoute, $this->aMatch['route'])) {
                $aReturn[$sKey][] = basename($sRoute);
            }
        }
        return $aReturn;
    }
}