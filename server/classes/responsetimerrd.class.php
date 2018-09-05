<?php

require_once 'lang.class.php';

/**
 * store last N response time to draw a graph
 *
 * @author hahn
 */
class responsetimeRrd {

    protected $_sCacheIdPrefix="responsetime";
    protected $_iMaxLogentries=1000;
    
    /**
     * logdata for detected changes and sent notifications
     * @var array 
     */
    protected $_aLog = false;
    protected $_sAppId = false;

    // ----------------------------------------------------------------------
    // __construct
    // ----------------------------------------------------------------------

    public function __construct($aAppid=false) {
        if($aAppid){
            $this->setApp($aAppid);
        }
        return true;
    }

    public function setApp($aAppid){
        $this->_sAppId=$aAppid;
        $this->_aLog=$this->_getLogs();
        if(!$this->_aLog){
            $this->_aLog=array();
        }
        return true;
    }    

    // ----------------------------------------------------------------------
    // private functions - handle cache of application checkdata
    // ----------------------------------------------------------------------
    /**
     * helper function - limit log to N entries
     * @return boolean
     */
    protected function _cutLogitems(){
        if(count($this->_aLog)>$this->_iMaxLogentries){
            while(count($this->_aLog)>$this->_iMaxLogentries){
                array_shift($this->_aLog);
            }
        }
        return true;
    }
    
    /**
     * get current or last stored client notification data
     * this method also stores current notification data on change
     * @return array
     */
    protected function _getLogs(){
        $oCache=new AhCache($this->_sCacheIdPrefix, $this->_sAppId);
        return $oCache->read();
    }

    protected function _saveLogs(){
        $oCache=new AhCache($this->_sCacheIdPrefix, $this->_sAppId);
        return $oCache->write($this->_aLog);
    }
    
    // ----------------------------------------------------------------------
    // public functions
    // ----------------------------------------------------------------------

    public function add($sStatus, $time, $sMsg){
        $this->_aLog[]=array('timestamp'=>time(), 'status'=>$sStatus, 'time'=>$time, 'message'=>$sMsg);
        $this->_cutLogitems();
        $this->_saveLogs();
    }

    
    /**
     * delete application
     * @param string  $sAppId  app id
     * @return boolean
     */
    public function delete(){
        $oCache=new AhCache($this->_sCacheIdPrefix, $this->_sAppId);
        return $oCache->delete();
    }
    /**
     * delete application
     * @param string  $sAppId  app id
     * @return boolean
     */
    public function get($iMax=false){
        $aReturn=array();
        $aTmp=$this->_aLog;
        if(!$iMax){
            $iMax=$this->_iMaxLogentries;
        }
        for($i=0; $i<$iMax; $i++){
            if (!count($aTmp)){
                break;
            }
            $aReturn[]=array_pop($aTmp);
            // $aReturn[]=$aTmp;
        }
        return $aReturn;
    }
    
    public function renderGraph($aOptions=array()){
        static $iCounter;
        if(!isset($iCounter)){
            $iCounter=0;
        }
        $iCounter++;
        $aChartLabels=array();
        $aChartValues=array();
        $aChartColor=array();
        $aChartLegend=array();
        
        $iMax=isset($aOptions['iMax']) ? (int)$aOptions['iMax'] : 100;
        $sXlabel=isset($aOptions['xLabel']) ? $aOptions['xLabel'] : '';
        $sYlabel=isset($aOptions['yLabel']) ? $aOptions['yLabel'] : '';
        
        $aPerfdata=$this->get($iMax);
                    
        // TODO: put colors to css and parse css style rule
        $aColor=array(
            RESULT_ERROR=>'#f33',
            RESULT_WARNING=>'#fc2',
            RESULT_UNKNOWN=>'#aaa',
            RESULT_OK=>'#093',
        );
        foreach ($aPerfdata as $aItem){
            array_unshift($aChartLabels, date("Y-m-d H:i:s", $aItem['timestamp']));
            array_unshift($aChartValues, $aItem['time']);
            array_unshift($aChartColor, $aColor[$aItem['status']]);
            // array_unshift($aChartColor, $aColor[rand(0, 3)]);
        }
        $sIdCanvas='canvasResponseGraph'.$iCounter;
        $sCtx='ctxRg'.$iCounter;
        $sConfig='configRg'.$iCounter;

        $sHtml = '<div class="tile" style="width:900px;">'
                . '<canvas id="'.$sIdCanvas.'"></canvas>'
            . '</div><div style="clear: both;"></div>'
            . "<script>
                var ".$sConfig." = {
                    type: 'bar',
                    data: {
                        labels: ". json_encode($aChartLabels).",
                        datasets: [{
                                label: 'Response time',
                                backgroundColor: ". json_encode($aChartColor).",
                                data: ". json_encode($aChartValues).",
                                pointRadius: 0,
                                fill: false
                        }]
                    },
                    options: {
                        responsive: true,
                        elements: {
                            line: {
                                tension: 0, // disables bezier curves
                            }
                        },
                        animation: {
                            duration: 0, // general animation time
                        },
                        responsiveAnimationDuration: 0, // animation duration after a resize                                            
                        title: {
                            display: false,
                            text: 'Line Chart'
                        },
                        legend: {
                            display: false,
                            text: 'Line Chart'
                        },
                        tooltips: {
                            mode: 'index',
                            intersect: false,
                        },
                        hover: {
                            animationDuration: 0, // duration of animations when hovering an item
                            mode: 'nearest',
                            intersect: true
                        },
                        scales: {
                            xAxes: [{
                                display: true,
                                scaleLabel: {
                                    display: true,
                                    labelString: '$sXlabel'
                                }
                            }],
                            yAxes: [{
                                display: true,
                                scaleLabel: {
                                    display: true,
                                    labelString: '$sYlabel'
                                }
                            }]
                        }
                    }
            };

            var ".$sCtx." = document.getElementById('".$sIdCanvas."').getContext('2d');
            window.myLine = new Chart(".$sCtx.", ".$sConfig.");
        </script>";
        // --- /chart
       return $sHtml;
    }
}
