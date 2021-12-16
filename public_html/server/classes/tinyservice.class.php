<?php

/**
 * tinyservice
 *
 * TODO: check a running PID instead of timeout based on sleep time
 *      MS WINDOWS: tasklist /FI "PID eq [PID]"
 *      *NIX: if(!file_exists('/proc/'.$pid))
 *
 * @author hahn
 */
class tinyservice {

    protected $sTouchfile = false;
    protected $iSleep = false; // seconds
    protected $iStart = false;
    protected $bDebug = false;

    /**
     * initialize tiniservice
     * @param $sAppname       string   app id to prevent starting a script multiuple times
     * @param $iNewSleeptime  integer  idle time between loops
     * @param $sTmpdir        string   custom temp dir
     * @return boolean
     */
    public function __construct($sAppname, $iNewSleeptime = 10, $sTmpdir=false) {
        $this->iStart = microtime(true);
        $this->setAppname($sAppname, $sTmpdir);
        if ($iNewSleeptime) {
            $this->setSleeptime($iNewSleeptime);
        }

        return true;
    }

    /**
     * check if the run file was set. If not the application dies.
     * @return boolean
     */
    protected function _checkTouchfile() {
        if (!$this->sTouchfile) {
            echo "ERROR: error in application. setTouchfile([name of application]) first.\n";
            die();
        }
        return true;
    }

    /**
     * do not allow to run as root (for *NIX systems)
     * @return boolean
     */
    public function denyRoot() {
        if (function_exists("posix_getpwuid")) {
            $processUser = posix_getpwuid(posix_geteuid());
            if ($processUser['name'] == "root") {
                die("ERROR: Do not start the script as user root. Run it as the user of the application\n");
            }
        }
        return true;
    }

    // signal handler - UNTESTED
    public function sigHandler($signo) {
        $this->send("signal $signo received ...");
        switch ($signo) {
            case SIGINT:
            case SIGTERM:
                // actions SIGTERM signal processing
                // fclose($fd_log); // close the log-file
                $this->send("removing touchfile and exiting.");
                unlink($this->sTouchfile); // destroy touch file
                $this->sTouchfile = false;
                echo "Bye.\n";
                exit;
                break;
              case SIGHUP:
              // actions SIGHUP handling
              break;
              default:
                $this->send("No action for signal $signo ... I will continue ...");
        }
    }

    /**
     * set an application name - to create a run file
     *
     * @param string $sAppname  name of the application
     * @param string $sTmpdir   optional: location of temp dir; default: system temp (often /tmp)
     * @return boolean
     */
    public function setAppname($sAppname, $sTmpdir=false) {
        $this->sTouchfile = false;
        $sFilepart = preg_replace('/[^a-z0-9]/i', '_', $sAppname);
        if (!$sFilepart) {
            return false;
        }
        $this->sTouchfile = ($sTmpdir ? $sTmpdir : sys_get_temp_dir()) . '/running_tinyservice_' . $sFilepart . '.run';
        return true;
    }

    /**
     * enable/ disable debug
     *
     * @param boolean $bDebug  flag with true|false
     * @return boolean
     */
    public function setDebug($bDebug) {
        $this->bDebug = $bDebug ? true : false;
        return true;
    }

    /**
     * set a new sleep time
     * @param integer $iNewSleeptime value in seconds
     * @return boolean
     */
    public function setSleeptime($iNewSleeptime) {
        if (!(int) $iNewSleeptime) {
            return false;
        }

        $this->iSleep = $iNewSleeptime;
        return true;
    }

    /**
     * check if application can start. It checks the existance of touch file
     * if it was found then an older file will be ignored.
     *
     * @return boolean
     */
    function canStart() {
        echo "INFO: Run file is $this->sTouchfile\n";
        if (!file_exists($this->sTouchfile)) {
            echo "STATUS: Not running.\n";
            return true;
        }
        $iTS = filemtime($this->sTouchfile);
        $iAge = date('U') - $iTS;
        echo "INFO: Its age is " . $iAge . "s (sleep time is $this->iSleep s)\n";
        if ($iAge > $this->iSleep) {
            echo "STATUS: Not running. Run file is outdated.\n";
            return true;
        }
        echo "STATUS: A service process is running.\n";
        return false;
    }

    /**
     * write the message to a touch file ... as a life sign
     *
     * @param string $sMessage  message text
     * @return boolean
     */
    public function touch($sMessage) {
        $this->_checkTouchfile();
        return file_put_contents($this->sTouchfile, $sMessage);
    }

    /**
     * write a message to STDOUT (if actiated or debug is on) and
     * touch the run file
     *
     * @param string   $sMessage  message text
     * @param boolean $bShow     flag to write to stdout
     * @return boolean
     */
    public function send($sMessage, $bShow = false) {
        $this->_checkTouchfile();
        $sLine = date("Y-m-d H:i:s") . " [" . number_format(microtime(true) - $this->iStart, 4) . "] " . $sMessage . "\n";
        echo ($bShow || $this->bDebug) ? $sLine : '';
        return $this->touch($sLine);
    }

    /**
     * sleep a bit
     *
     * @param boolean $bShow     flag to write to stdout
     * @return boolean
     */
    public function sleep($bShow = false) {
        $this->send("SLEEPING for " . $this->iSleep . " seconds ... ", $bShow);
        sleep($this->iSleep);
        $this->send("Waking up.", $bShow);
        return true;
    }

}
