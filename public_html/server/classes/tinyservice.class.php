<?php
/**
 * tinyservice
 *
 * TODO: check a running PID instead of timeout based on sleep time
 *      MS WINDOWS: tasklist /FI "PID eq [PID]"
 *      *NIX: if(!file_exists('/proc/'.$pid))
 *
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
 * @version 1.0
 * @author Axel Hahn
 * @link https://github.com/iml-it/appmonitor
 * @license GPL
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL 3.0
 * @package IML-Appmonitor
 * 
 * 2024-07-17  1.1  axel.hahn@unibe.ch  php 8 only: use typed variables
 */
class tinyservice
{

    /**
     * Filename of a touch file
     * @var string
     */
    protected string $sTouchfile = '';

    /**
     * Number of seconds to sleep between loops
     * @var int
     */
    protected int $iSleep = 3; // seconds


    /**
     * starting tome of the service
     * @var float
     */
    protected float $iStart = 0;

    /**
     * Flag to show debug messages
     * @var boolean
     */
    protected bool $bDebug = false;

    /**
     * Initialize tiniservice
     * 
     * @param $sAppname       string   app id to prevent starting a script multiuple times
     * @param $iNewSleeptime  integer  idle time between loops
     * @param $sTmpdir        string   custom temp dir
     * @return boolean
     */
    public function __construct(string $sAppname, int $iNewSleeptime = 10, string $sTmpdir = '')
    {
        $this->iStart = microtime(true);
        $this->setAppname($sAppname, $sTmpdir);
        if ($iNewSleeptime) {
            $this->setSleeptime($iNewSleeptime);
        }
    }

    /**
     * Check if the run file was set. If not the application dies.
     * @return boolean
     */
    protected function _checkTouchfile(): bool
    {
        if (!$this->sTouchfile) {
            echo "ERROR: error in application. setTouchfile([name of application]) first.\n";
            die();
        }
        return true;
    }

    /**
     * Do not allow to run as root (for *NIX systems)
     * @return boolean
     */
    public function denyRoot(): bool
    {
        if (function_exists("posix_getpwuid")) {
            $processUser = posix_getpwuid(posix_geteuid());
            if ($processUser['name'] == "root") {
                die("ERROR: Do not start the script as user root. Run it as the user of the application\n");
            }
        }
        return true;
    }

    /**
     * Signal handler - UNTESTED
     * 
     * @param int $signo sent signal
     * @return void
     */
    public function sigHandler(int $signo): void
    {
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
     * Set an application name.
     * It is used to create a run file.
     *
     * @param string $sAppname  name of the application
     * @param string $sTmpdir   optional: location of temp dir; default: system temp (often /tmp)
     * @return boolean
     */
    public function setAppname(string $sAppname, string $sTmpdir = ''): bool
    {
        $this->sTouchfile = '';
        $sFilepart = preg_replace('/[^a-z0-9]/i', '_', $sAppname);
        if (!$sFilepart) {
            return false;
        }
        $this->sTouchfile = ($sTmpdir ? $sTmpdir : sys_get_temp_dir()) . '/running_tinyservice_' . $sFilepart . '.run';
        return true;
    }

    /**
     * Enable/ disable debug
     *
     * @param boolean $bDebug  flag with true|false
     * @return boolean
     */
    public function setDebug(bool $bDebug): bool
    {
        $this->bDebug = $bDebug ? true : false;
        return true;
    }

    /**
     * Set a new sleep time
     * 
     * @param integer $iNewSleeptime value in seconds
     * @return boolean
     */
    public function setSleeptime(int $iNewSleeptime): bool
    {
        if (!(int) $iNewSleeptime) {
            return false;
        }

        $this->iSleep = $iNewSleeptime;
        return true;
    }

    /**
     * Check if application can start. It checks the existance of touch file
     * if it was found then an older file will be ignored.
     *
     * @return boolean
     */
    function canStart(): bool
    {
        // echo "INFO: Run file is $this->sTouchfile\n";
        if (!file_exists($this->sTouchfile)) {
            echo "STATUS: Not running.\n";
            return true;
        }
        $iTS = filemtime($this->sTouchfile);
        $iAge = date('U') - $iTS;
        // echo "INFO: Its age is " . $iAge . "s (sleep time is $this->iSleep s)\n";
        if ($iAge > $this->iSleep) {
            echo "STATUS: Not running. Run file is outdated.\n";
            return true;
        }
        echo "STATUS: A service process is running.\n";
        return false;
    }

    /**
     * Write the message to a touch file ... as a life sign
     *
     * @param string $sMessage  message text
     * @return boolean
     */
    public function touch(string $sMessage): bool
    {
        $this->_checkTouchfile();
        return file_put_contents($this->sTouchfile, $sMessage);
    }

    /**
     * Write a message to STDOUT (if actiated or debug is on) and
     * touch the run file
     *
     * @param string   $sMessage  message text
     * @param boolean $bShow     flag to write to stdout
     * @return boolean
     */
    public function send(string $sMessage, bool $bShow = false): bool
    {
        $this->_checkTouchfile();
        $sLine = date("Y-m-d H:i:s") . " [" . number_format(microtime(true) - $this->iStart, 4) . "] " . $sMessage . "\n";
        echo ($bShow || $this->bDebug) ? $sLine : '';
        return $this->touch($sLine);
    }

    /**
     * Sleep a bit
     *
     * @param boolean $bShow     flag to write to stdout
     * @return boolean
     */
    public function sleep(bool $bShow = false): bool
    {
        $this->send("SLEEPING for " . $this->iSleep . " seconds ... ", $bShow);
        sleep($this->iSleep);
        $this->send("Waking up.", $bShow);
        return true;
    }

}
