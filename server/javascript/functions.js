/* ######################################################################
 * 
 * APPMONITIR GUI :: JAVASCRIPT FUNCTIONS
 * 
 * ######################################################################
 */


// ----------------------------------------------------------------------
// CONFIG
// ----------------------------------------------------------------------

var sLocalStorageLastTag='sFilterTag';

/**
 * define a start time as UnixTS; used in agetimer
 * @type Number
 */
var iStartTime=getUnixTS();


// ----------------------------------------------------------------------
// FUNCTIONS
// ----------------------------------------------------------------------

/**
 * relaod the page and remove the query parameters
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
 * filter content elements by a given css class
 * @param {string} sTagfilter  css class of a tag (must be tag-[hash])
 * @returns {undefined}
 */
function filterForTag(sTagfilter){
    if(sTagfilter && sTagfilter.indexOf('tag-')===0 ){
        $('.tags').hide();
        $('.'+sTagfilter).show();
        
    } else {
        $('.tags').show();
    }
    localStorage.setItem(sLocalStorageLastTag,sTagfilter);
}


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
    window.history.pushState('dummy', 'Title', sDiv);
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

/**
 * get the current unix ts
 * @returns {Number}
 */
function getUnixTS(){
    return Date.now()/1000;
}


/**
 * let a counter update its age in sec
 * @returns {undefined}
 */
function timerAgeInSec(){
    var iStart=false;
    $(".timer-age-in-sec").each(function () {
        
        oStartValue=$(this).find("span.start");
        if(oStartValue.length==0){
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

function initGuiStuff(){
    var oTimerAgeInSec=window.setInterval("timerAgeInSec();", 5000);
    $("a[href^=\'#\']").click(function() { showDiv( this.hash ); return false; } ); 
    var sFilterTag=localStorage.getItem(sLocalStorageLastTag) ? localStorage.getItem(sLocalStorageLastTag) : '';
    filterForTag(sFilterTag);
}

