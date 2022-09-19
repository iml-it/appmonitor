/**
 * 
 * HELPER CLASS to render network maps with VisJs
 * 
 * initial lines... I need to write some clean methods to remove 
 * hardcoded values 
 * 
 */
var visJsNetworkMap = function(){

    // ----------------------------------------------------------------------
    // SETTINGS
    // ----------------------------------------------------------------------

    // GRRR ... sorry for that
    this.name='oMap'; 
    this.sDomIdMap='mynetwork';
    this.sDomIdSelect='selView';
    // /GRRR

    this.container=document.getElementById(this.sDomIdMap);
    this.network;
    this.nodes = new vis.DataSet();
    this.edges = new vis.DataSet();
    
    this.bViewFullsize=false;
    this.sViewmode="UD";
    this.aViewmodes={
        "LR":{
            label: "&lt;",
            levelSeparation: 250,
            nodeDistance: 130
        },
        "UD":{
            label: "^",
            levelSeparation: 150,
            nodeDistance: 200
        }
    };
    this.visjsNetOptions = false;
    
    // ----------------------------------------------------------------------
    //
    // METHODS
    //
    // ----------------------------------------------------------------------


    // ----------------------------------------------------------------------
    // store and read variables
    // ----------------------------------------------------------------------

    this._getVarKey = function(sName){
        // the same settings for all apps:
        return location.pathname+'__visJsNetworkMap__'+sName;

        // individual settings per app:
        // has issues when location was not rewritten yet, i.e. coming from 
        // webapp overview to app detail page
        // return location.href+'__visJsNetworkMap__'+sName;
    }

    /**
     * save a given variable in localstorage
     * @param {string} sName  name of the var (will be used in the key)
     * @param {*}      value  value to store
     * @returns bool
     */
    this._saveVar = function(sName, value){
        return localStorage.setItem(this._getVarKey(sName), value );
    }
    /**
     * read a variable from localstorage
     * @param {*} sName  name of the var to read
     * @returns 
     */
    this._getVar = function(sName){
        // console.log("_getVar() with key " + this._getVarKey(sName));
        return localStorage.getItem(this._getVarKey(sName));
    }

    /**
     * helper: update visJs network option (because it has variables in it)
     */
    this._updateVisOptions = function (){
        this.visjsNetOptions = {
            layout: {
                hierarchical: {
                  direction: this.sViewmode,
                  sortMethod: "directed",
                  levelSeparation: this.aViewmodes[this.sViewmode]["levelSeparation"] 
                },
            },
            nodes: {
                shadow: { color: "#cccccc" }
            },
            physics: {
                hierarchicalRepulsion: {
                  nodeDistance: this.aViewmodes[this.sViewmode]["nodeDistance"]
                }
              },
            edges: {
                smooth: {
                  type: "cubicBezier",
                  forceDirection:
                    (this.sViewmode == "UD" || this.sViewmode == "DU")
                        ? "vertical"
                        : "horizontal",
                  roundness: 0.4,
                },
              }
        };
    }

    // ----------------------------------------------------------------------
    // network map
    // ----------------------------------------------------------------------

    /**
     * set objects of nodes and edges in the network
     * @param {object} nodesData 
     * @param {object} edgesData 
     */
    this.setData = function(nodesData, edgesData) {
        this.network = null;
        this.nodes.clear();
        this.edges.clear();
        this.nodes.add(nodesData);
        this.edges.add(edgesData);
    }

    /**
     * redraw visJs network map on div with id this.sDomIdMap
     * and update select
     */
    this.redrawMap = function() {
        this._updateVisOptions();
        
        // removed because fullscreen was added
        // this.container.className=( this.bViewFullsize===true || this.bViewFullsize==="true")  ? 'large':'';

        // console.log("viewMode=" + this.sViewmode + "; fullsize=" + this.bViewFullsize);
        network = new vis.Network(
            this.container,
            { nodes: this.nodes, edges: this.edges }, 
            this.visjsNetOptions
        );        
        // this.renderSelectView();
    }
    
    // ----------------------------------------------------------------------
    // switch view and size
    // ----------------------------------------------------------------------

    /**
     * render html code for dropdown for views and put it to this.sDomIdSelect
     */
    this.renderSelectView = function() {
        var sHtml="<select onchange=\""+this.name+".switchViewMode(this.value);\">";
        for (s in this.aViewmodes){
            sHtml+="<option value=\""+s+"\"" + (s===this.sViewmode ? " selected=\"selected\"" : "" ) + ">"+this.aViewmodes[s]["label"]+"</option>"
        }
        sHtml+="</select>";
    
        var oSpan=document.getElementById(this.sDomIdSelect);
        oSpan.innerHTML=sHtml;
    }
    

    /**
     * switch direction of the tree
     * @param {string} sNewView  new direction; one of UD | LR 
     */
    this.switchViewMode = function (sNewView) {
        if(sNewView){
            this.sViewmode=sNewView;
        } else {
            this.sViewmode=(this.sViewmode==="LR" ? "UD" : "LR");
        }
        this._saveVar("this.sViewmode", this.sViewmode);
        this.redrawMap();
    };

    /**
     * DEPRECATED - removed because fullscreen was added
     * change size of the map by adding/ removing css class "large"
     */
    this.switchViewSize = function () {
        this.bViewFullsize=!this.bViewFullsize;
        this._saveVar("this.bViewFullsize", this.bViewFullsize);
        this.redrawMap();
    }

    // ----------------------------------------------------------------------
    // FULL SCREEN
    // ----------------------------------------------------------------------

    /**
     * toggle full screen given domid (=container of network)
     * @param {*} idContainer 
     * @returns 
     */
    this.toggleFullscreen = function(idContainer) {
        var oContainer=document.getElementById(idContainer);

        // detect if view is 100% already
        bToFull=!document.fullscreenElement;
        // var bToFull=(this.container.className=='');
        
        // set to full screen?
        if(bToFull){
            if (oContainer.requestFullscreen) {
                oContainer.requestFullscreen();
            } else if (oContainer.webkitRequestFullscreen) { /* Safari */
                oContainer.webkitRequestFullscreen();
            } else if (oContainer.msRequestFullscreen) { /* IE11 */
                oContainer.msRequestFullscreen();
            }
        } else {
            try {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) { /* Safari */
                    document.webkitExitFullscreen();
                } else if (document.msExitFullscreen) { /* IE11 */
                    document.msExitFullscreen();
                }
                    
            } catch (error) {
                // 
            }

        }
        // this.container.className=document.fullscreenElement ? 'fullscreen' : '';
        this.container.className=bToFull ? 'fullscreen' : '';

        this.redrawMap();
        return true;
    }

    // ----------------------------------------------------------------------
    // MAIN
    // ----------------------------------------------------------------------

    /*
    if (arguments[0]) {
        this.setConfig(arguments[0]);
    }
    */

    // removed because fullscreen was added
    // this.bViewFullsize=this._getVar("this.bViewFullsize") ? this._getVar("this.bViewFullsize") : false;
    this.bViewFullscreen=this._getVar("this.bViewFullscreen") ? this._getVar("this.bViewFullscreen") : false;
    
    this._updateVisOptions();

    return true;    
}