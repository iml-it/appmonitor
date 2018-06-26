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

    //put your code here

    protected $sTouchfile = false;
    protected $iSleep = false; // seconds
    protected $iStart = false;
    protected $bDebug = false;

    public function __construct($sAppname, $iNewSleeptime = 10) {
        $this->iStart = microtime(true);
        $this->setAppname($sAppname);
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

    // signal handler - UNTESTED 
    public function sigHandler($signo) {
        $this->send("signal $signo received ...");
        switch ($signo) {
            case SIGTERM:
                // actions SIGTERM signal processing
                // fclose($fd_log); // close the log-file
                $this->send("handle SIGTERM ... exiting");
                unlink($this->sTouchfile); // destroy touch file
                $this->sTouchfile=false;
                exit;
                break;
            /*
            case SIGHUP:
                // actions SIGHUP handling
                init_data(); // reread the configuration file and initialize the data again
                break;
            default:
            // Other signals, information about errors
             * 
             */
        }
    }

    /**
     * set an application name - to create a run file
     * 
     * @param string $sAppname  name of the application
     * @return boolean
     */
    public function setAppname($sAppname) {
        $this->sTouchfile = false;
        $sFile = preg_replace('/[^a-z0-9]/i', '', $sAppname);
        if (!$sFile) {
            return false;
        }
        $this->sTouchfile = sys_get_temp_dir() . '/running_tinyservice_' . $sFile . '.run';
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
        if (!file_exists($this->sTouchfile)) {
            return true;
        }
        $iTS = filemtime($this->sTouchfile);
        $iAge = date('U') - $iTS;
        if ($iAge > $this->iSleep) {
            echo "INFO outdated run file - it is " . $iAge . "s old. Ignoring it ... and starting.\n";
            return true;
        }
        echo "ERROR: run file was found - it is " . $iAge . "s old. "
                . "A process seems to run already. "
                . "Or you need to wait up to ".($this->iSleep - $iAge)." seconds.\n"
                . "$this->sTouchfile\n\nits content:\n";
        echo file_get_contents($this->sTouchfile) . "\n\n";
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
    public function sleep($bShow=false){
        for ($i=0; $i<$this->iSleep; $i++){
            $this->send("SLEEPING for ".$this->iSleep." seconds ... ".($this->iSleep-$i). " seconds left", $bShow);
            sleep(1);
        }
        $this->send("Waking up.", $bShow);
        return true;
    }
}
