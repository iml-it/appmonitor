/* ######################################################################
 * 
 * APPMONITIR GUI :: JAVASCRIPT FUNCTIONS
 * 
 * ######################################################################
 */


// ----------------------------------------------------------------------
// CONFIG
// ----------------------------------------------------------------------

/**
 * name of the local storage
 * @type String
 */
var sLocalStorageLastTag='sFilterTag';

/**
 * define a start time as UnixTS; used in agetimer
 * @type Number
 */
var iStartTime=getUnixTS();

/**
 * current div
 * @type String|sDiv
 */
var sActiveDiv='';

/**
 * current tag in clear text
 * @type String
 */
var sActiveTag='';

// ----------------------------------------------------------------------
// FUNCTIONS
// ----------------------------------------------------------------------

/**
 * helper function: get value from query parameter of current url
 * @param {string} variable  name of GET variable
 * @returns {var}
 */
function getQueryVariable(variable) {
    var query = window.location.search.substring(1);
    var vars = query.split('&');
    for (var i = 0; i < vars.length; i++) {
        var pair = vars[i].split('=');
        if (decodeURIComponent(pair[0]) === variable) {
            return decodeURIComponent(pair[1]);
        }
    }
    console.log('Query variable %s not found', variable);
}

/**
 * get the current unix ts
 * @returns {Number}
 */
function getUnixTS(){
    return Date.now()/1000;
}

/**
 * reload the page and remove the query parameters
 * @returns {undefined}
 */
function reloadPage() {
    if (window.location.search) {
        window.location.href = window.location.pathname + window.location.hash;
    }
    else {
        window.location.reload();
    }
}

/**
 * manipulate url in the browser address bar
 * 
 * @returns {undefined}
 */
function setAdressbar(){
    var url='?'+(sActiveTag ? 'tag='+sActiveTag : '' ) + sActiveDiv;
    console.log("url = " + url);
    window.history.pushState('dummy', 'Title', url);
}

/**
 * update page content - but not on setup page
 * @returns {Boolean}
 */
function updateContent() {
    if(location.hash=='#divsetup'){
        window.setTimeout("updateContent()", 1000);
        return false;
    }
    location.reload();
}


// ----------------------------------------------------------------------
// FUNCTIONS for tag filter
// ----------------------------------------------------------------------

/**
 * filter content elements by a given css class
 * @param {string} sTagfilter  css class of a tag (must be tag-[hash])
 * @returns {undefined}
 */
function filterForTag(sTagfilter){
    if(sTagfilter && sTagfilter.indexOf('tag-')===0 ){
        $('.tags').hide();
        $('.'+sTagfilter).show();
        sActiveTag=getTagByClassname(sTagfilter);
    } else {
        $('.tags').show();
        sActiveTag='';
    }
    localStorage.setItem(sLocalStorageLastTag,sTagfilter);
    setAdressbar();
}

/**
 * tag filter: get css name by given tag name
 * @param {type} sTagname  name of the tag in the dropdown
 * @returns {String}
 */
function getClassByClearnameTag(sTagname){
    var sReturn='';
    $('#selecttag option').each(function(){
        if(this.text===sTagname){
            this.selected='selected';
            sActiveTag=sTagname;
            sReturn=this.value;
        } else {
            this.selected=false;
        }
    });
    return sReturn;
}

/**
 * tag filter: get clear text name of tag by classname of tagfilter
 * @param {string} sClassname
 * @returns {string}
 */
function getTagByClassname(sClassname){
    $('#selecttag option').each(function(){
        if(this.value===sClassname){
            this.selected='selected';
            sActiveTag=this.text;
        } else {
            this.selected=false;
        }
    });
    return sActiveTag;
}

// ----------------------------------------------------------------------
// FUNCTIONS for divs
// ----------------------------------------------------------------------

/**
 * switch the visible output div
 * @param {string} sDiv
 * @returns {undefined}
 */
function showDiv(sDiv) {
    $(".outsegment").hide();
    // $(sDiv).fadeIn(200);
    $(sDiv).show();
    $(".divtopnavi a").removeClass("active");
    $("a[href='" + sDiv + "']").addClass("active").blur();
    if(sDiv.indexOf('divweb')>0){
        $("a[href='#divwebs']").addClass("active");
    }
    sActiveDiv=sDiv;
    setAdressbar();
}


/**
 * let a counter update its age in sec
 * @returns {undefined}
 */
function timerAgeInSec(){
    var iStart=false;
    $(".timer-age-in-sec").each(function () {
        
        oStartValue=$(this).find("span.start");
        if(oStartValue.length===0){
            iStart=$(this).html();
            $(this).html('<span class="start" style="display: none;">'+iStart+'</span><span class="current"></span>');
        }
        oStartValue=$(this).find("span.start");
        iStart=$(oStartValue[0]).html()/1;
        
        iCurrent=(iStart+Math.floor(getUnixTS()-iStartTime));
        if(iCurrent>5){
            iCurrent=Math.floor(iCurrent/5)*5;
        }
        oNewValue=$(this).find("span.current");
        
        $(oNewValue[0]).html(iCurrent);
        // window.setTimeout("timerAgeInSec()", 1000);
    });
}


/**
 * initialize GUI elements: timer, set tag filter, set active tab+div
 * @returns {undefined}
 */
function initGuiStuff(){
    
    // activate age timer on tiles
    var oTimerAgeInSec=window.setInterval("timerAgeInSec();", 5000);
    
    // set tag filter
    if(getQueryVariable('tag')){
        var sClass=getClassByClearnameTag(getQueryVariable('tag'));
        filterForTag(sClass);
    } else {
        var sFilterTag=localStorage.getItem(sLocalStorageLastTag) ? localStorage.getItem(sLocalStorageLastTag) : '';
        filterForTag(sFilterTag);        
    }
    // set active div
    $("a[href^=\'#\']").click(function() { showDiv( this.hash ); return false; } ); 

}
