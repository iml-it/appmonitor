<?php
require_once 'htmlelements.class.php';
/**
 * ======================================================================
 * 
 * RENDERER FOR ADNINLTE template https://adminlte.io
 * DOCS: https://adminlte.io/docs/2.4/installation
 * 
 * ======================================================================
 *
 * @author Axel
 */
class renderadminlte {

    /**
     * valid items for colors, boxes ....
     * @var array
     */
    var $_aValidItems=array(
        'bgcolor'=>array(
            'aqua', 
            'green', 
            'red', 
            'yellow'
        ),
        'color'=>array(
            'aqua', 
            'green', 
            'red', 
            'yellow'
        ),
        'type'=>array(
            'danger',   // red
            'info',     // aqua
            'primary',  // blue
            'success',  // green
            'warning',  // yellow
        ),
    );
    
    /**
     * instance of htmlelements
     * @var object
     */
    var $_oHtml=false;
    
    
    // ----------------------------------------------------------------------
    // 
    // PRIVATE FUNCTIONS 
    // 
    // ----------------------------------------------------------------------
    public function __construct() {
        $this->_oHtml=new htmlelements();
        return true;
    }

    // ----------------------------------------------------------------------
    // 
    // PRIVATE FUNCTIONS 
    // 
    // ----------------------------------------------------------------------
    
    /**
     * verify if an item has a correct value
     * @param string  $sType   type; key in $_aValidItems; one of color|type
     * @param string  $sValue  value to check
     */
    private function _checkValue($sType, $sValue){
        if (!$sValue || !array_key_exists($sType, $this->_aValidItems)){
            return false;
        }
        if(array_search($sValue, $this->_aValidItems[$sType])===false){
            die("ERROR: value [$sValue] is not a valid for type [$sType]; it must be one of ".implode("|", $this->_aValidItems[$sType]));
        }
        return true;
    }

    // ----------------------------------------------------------------------
    // 
    // PUBLIC FUNCTIONS
    // SIMPLE HTML ELEMENTS
    // 
    // ----------------------------------------------------------------------
    
    /**
     * return a content Box
     * @param type $aOptions  hash with keys for all options
     *                          - type - one of [none]|danger|info|primary|success|warning
     *                          - solid - one of true|false; default: false
     *                          - collapsable - one of true|false; default: false
     *                          - removable - one of true|false; default: false
     *                          - collapsed - if collapsable - one of true|false; default: false
     *                          - title
     *                          - label
     *                          - text
     *                          - footer
     * @return string
     */
    public function getBox($aOptions) {
        foreach (array('type','solid', 'collapsable', 'collapsed', 'removable', 'title','label', 'text', 'footer') as $sKey){
            if(!isset($aOptions[$sKey])){
                $aOptions[$sKey]=false;
            }
            $this->_checkValue($sKey, $aOptions[$sKey]);
        }
        
        // system icons on top right
        $sToolbox='';
        if($aOptions['label']){
            $sToolbox.='<span class="label label-primary">'.$aOptions['label'].'</span>';
        }
        if($aOptions['collapsable']){
            $sToolbox.='<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
            ';
        }
        if($aOptions['removable']){
            $sToolbox.='<button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>';
        }
        if($sToolbox){
            $sToolbox='<div class="box-tools pull-right">'.$sToolbox.'</div>';
        }
                
        // return box
        return '
        <div class="box'
            .($aOptions['type'] ? ' box-'.$aOptions['type'] : '')
            .($aOptions['solid'] ? ' box-solid' : '')
            .($aOptions['collapsed'] ? ' collapsed-box' : '')
            .'">
            <div class="box-header with-border">
              '.($aOptions['title'] ? '<h3 class="box-title">'.$aOptions['title'].'</h3>' : '').'
              '.$sToolbox.'
            </div>
            <div class="box-body">
              '.$aOptions['text'] .'
            </div>
            <!-- /.box-body -->
              '.($aOptions['footer'] ? '<div class="box-footer">'.$aOptions['footer'].'</div>' : '') .'
            <!-- /.box-footer-->
        </div>
        ';
    }
    
    
    public function getMenuItem($aOptions, $aLinkOptions){
        $sLabel=$this->_oHtml->getTag('a', $aLinkOptions);
        
        // TODO
        // if subelements then add them to $sLabel with recursion
        
        return $this->_oHtml->getTag('li', array(
            'class'=>'treeview',
            'label'=>$sLabel,
        ));
        /*
        return '<li class="treeview">
          <a href="#">
            <i class="fa fa-laptop"></i>
            <span>UI Elements</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <!--
          <ul class="treeview-menu" style="display: none;">
            <li><a href="general.html"><i class="fa fa-circle-o"></i> General</a></li>
            <li class="active"><a href="icons.html"><i class="fa fa-circle-o"></i> Icons</a></li>
            <li><a href="buttons.html"><i class="fa fa-circle-o"></i> Buttons</a></li>
            <li><a href="sliders.html"><i class="fa fa-circle-o"></i> Sliders</a></li>
            <li><a href="timeline.html"><i class="fa fa-circle-o"></i> Timeline</a></li>
            <li><a href="modals.html"><i class="fa fa-circle-o"></i> Modals</a></li>
          </ul>
          -->
        </li>
        ';
         */
    }
    
    /**
     * return a small Box
     * @param type $aOptions  hash with keys for all options
     *                          - type - icon color one of [none]|aqua|green|yellow|red
     *                          - color - icon color one of [none]|aqua|green|yellow|red
     *                          - title
     *                          - text
     *                          - icon - icon on the right
     *                          - footer - footer text
     *                          - url
     * @return string
     */
    public function getSmallBox($aOptions) {
        foreach (array('bgcolor','color', 'title', 'text', 'icon', 'footer') as $sKey){
            if(!isset($aOptions[$sKey])){
                $aOptions[$sKey]=false;
            }
            $this->_checkValue($sKey, $aOptions[$sKey]);
        }
        if(!$aOptions['url']){
            $aOptions['url']='#';
        }
        return '<div class="small-box'.($aOptions['bgcolor'] ? ' bg-'.$aOptions['bgcolor'] : '').'">
            <div class="inner">
                '.($aOptions['title'] ? '<h3>'.$aOptions['title'].'</h3>' : '').'
                '.($aOptions['text'] ? '<p>'.$aOptions['text'].'</p>' : '').'
            </div>
            '.($aOptions['icon'] ? '<div class="icon">'.$this->_oHtml->getIcon($aOptions['icon']).'</div>' : '').'
            '.($aOptions['footer'] 
                    ? '<a href="'.$aOptions['url'].'" class="small-box-footer"'
                        . '>'.$aOptions['footer'].' <i class="fa fa-arrow-circle-right"></i>'
                    . '</a>' : '').'
        </div>';
        
    }
    /**
     * return a widget
     * @param type $aOptions  hash with keys for all options
     *                          - bgcolor - icon color one of aqua|green|yellow|red
     *                          - color - icon color one of aqua|green|yellow|red
     *                          - icon
     *                          - text
     *                          - number
     *                          - progressvalue - 0..100
     *                          - progresstext  - text for progress
     * @return string
     */
    public function getWidget($aOptions=array()){
        foreach (array('bgcolor','color', 'text', 'number','icon') as $sKey){
            if(!isset($aOptions[$sKey])){
                $aOptions[$sKey]=false;
            }
            $this->_checkValue($sKey, $aOptions[$sKey]);
        }
        return '<div class="info-box bg-'.$aOptions['bgcolor'].'">
            <span class="info-box-icon bg-'.$aOptions['color'].'">'.($this->_oHtml->getIcon($aOptions['icon'])).'</span>

            <div class="info-box-content">
              <span class="info-box-text">'.$aOptions['text'].'</span>
              <span class="info-box-number">'.$aOptions['number'].'</span>
            </div>
            '.
                (is_int($aOptions['progressvalue'])
                    ? '<div class="progress">
                            <div class="progress-bar" style="width: '.$aOptions['progressvalue'].'%"></div>
                        </div>
                        '
                    :'')
                .(isset($aOptions['progresstext']) 
                    ? '<span class="progress-description">'.$aOptions['progresstext'].'</span>'
                    : ''
                )
            .'
            <!-- /.info-box-content -->
        </div>';
    }
    
    /**
     * get html content for a column div element inside a row
     * 
     * @param string   $sContent  html content to show
     * @param integer  $iColums   optional: count of columns; defauklt is 12 (full width)
     * @return string
     */
    public function getSectionColumn($sContent=false, $iColums=12){
        return '<div class="col-md-'.$iColums.'">'.$sContent.'</div>';
    }
    
    /**
     * get html code for a new content row
     * 
     * @param string   $sContent  html content to show
     * @return string
     */
    public function getSectionRow($sContent=false){
        return '<div class="row">'.$sContent.'</div>';
    }

    /**
     * get html code for headline of page content
     * 
     * @param string  $sHeadline  headline as html
     * @return type
     */
    public function getSectionHead($sHeadline){
        return '
            <section class="content-header">
              <h1>
                '.$sHeadline.'
              </h1>
              <!--

              BREADCRUMB TOP RIGHT

              <ol class="breadcrumb">
                <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
                <li class="active">Here</li>
              </ol>
              -->
            </section>
        ';
    }

    /**
     * get html code to init content section 
     * 
     * @param string   $sContent  html content to show
     * @return string
     */
    public function getSectionContent($sContent){
        return '
            <!-- Main content -->
            <section class="content container-fluid">

              <!--------------------------
                | Your Page Content Here |
                -------------------------->

                '.$sContent.'

            </section>
            <!-- /.content -->
        ';
    }
}
