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
 * define a start time as UnixTS; used in agetimer
 * @type Number
 */
var iStartTime = getUnixTS();
var iReload = false; // will be set in appmonitorserver_gui->renderHtml()
var iRefreshCounter = 0;

/**
 * filter
 * @type type
 */
var aViewFilters = {};


// ----------------------------------------------------------------------
// HELPER FUNCTIONS
// ----------------------------------------------------------------------

// http://blog.mastykarz.nl/jquery-regex-filter/
jQuery.extend(
        jQuery.expr[':'], {
    regex: function (a, i, m) {
        var r = new RegExp(m[3], 'i');
        return r.test(jQuery(a).text());
    }
}
);

/*
 highlight v4
 Highlights arbitrary terms.
 
 <http://johannburkard.de/blog/programming/javascript/highlight-javascript-text-higlighting-jquery-plugin.html>
 
 MIT license.
 
 Johann Burkard
 <http://johannburkard.de>
 <mailto:jb@eaio.com>
 */

jQuery.fn.highlight = function (pat) {
    function innerHighlight(node, pat) {
        var skip = 0;
        if (node.nodeType == 3) {
            var pos = node.data.toUpperCase().indexOf(pat);
            if (pos >= 0) {
                var spannode = document.createElement('span');
                spannode.className = 'highlight';
                var middlebit = node.splitText(pos);
                var endbit = middlebit.splitText(pat.length);
                var middleclone = middlebit.cloneNode(true);
                spannode.appendChild(middleclone);
                middlebit.parentNode.replaceChild(spannode, middlebit);
                skip = 1;
            }
        } else if (node.nodeType == 1 && node.childNodes && !/(script|style)/i.test(node.tagName)) {
            for (var i = 0; i < node.childNodes.length; ++i) {
                i += innerHighlight(node.childNodes[i], pat);
            }
        }
        return skip;
    }
    return this.length && pat && pat.length ? this.each(function () {
        innerHighlight(this, pat.toUpperCase());
    }) : this;
};

jQuery.fn.removeHighlight = function () {
    return this.find("span.highlight").each(function () {
        this.parentNode.firstChild.nodeName;
        with (this.parentNode) {
            replaceChild(this.firstChild, this);
            normalize();
        }
    }).end();
};



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
    return '';
}

/**
 * get the current unix ts
 * @returns {Number}
 */
function getUnixTS() {
    return Date.now() / 1000;
}

/**
 * manipulate url in the browser address bar
 * location changed by evnt listener hashchange -> see initGuiStuff()
 * 
 * @returns {undefined}
 */
function setAdressbar() {

    var url = location.protocol 
            + '//'+ location.hostname
            + (location.port ? ':'+location.port : '')
            + location.pathname
            + '?'
            + (aViewFilters['tag'] ? '&tag=' + aViewFilters['tag'].join(',') : '')
            + (aViewFilters['divwebs'] ? '&webapp=' + aViewFilters['divwebs'] : '')
            + aViewFilters['tab'];
    
    if(url!==location.href){
        window.history.pushState({
            url: location.hash,
            // content: $('#content').html(),
            filter: aViewFilters
        }, 'Title', url);
    }
}



// ----------------------------------------------------------------------
// FUNCTIONS for filter
// ----------------------------------------------------------------------


/**
 * add a filter form to a table
 * @param {string} s    name of div where to put form
 * @returns {undefined}
 */
function addFilter4Webapps(sTarget) {
    var sValue = aViewFilters[sTarget];
    var sForm = '<form class="form-horizontal frmFilter">\n\
        <div class="col-xs-3">\n\
            <div class="input-group">\n\
                <div class="input-group-addon">\n\
                    <i class="fa fa-filter"></i>\n\
                </div>\n\
                <input type="text" class="form-control" id="eFilter" class="inputtext" value="' + sValue + '" \n\
                    onkeypress="setTextfilter(\'' + sTarget + '\', this.value);" \n\
                    onkeyup="setTextfilter(\'' + sTarget + '\', this.value);" \n\
                    onchange="setTextfilter(\'' + sTarget + '\', this.value);"\n\
                >\n\
            </div>\n\
        </div>\n\
        \n\
        <div class="col-xs-9">\n\
            <span class="tagfilterinfo"></span>\n\
        </div>\n\
        <div style="clear: both;">\n\
    </form>\n\
    ';
    $('#' + sTarget + 'filter').html(sForm);
}

/**
 * filter content elements by a given css class
 * @param {string} sTagfilter  css class of a tag (must be tag-[hash])
 * @returns {undefined}
 */
function applyViewFilter() {

    // show everything
    $('.tags').show();

    $('#selecttag option').each(function () {
        this.selected = '';
    });

    var sTaglist = '';
    if (aViewFilters['tag']) {
        for (var i = 0; i < aViewFilters['tag'].length; i++) {
            var sTag = aViewFilters['tag'][i];
            var sTagClass = getClassByClearnameTag(sTag);

            // add to tag list
            sTaglist+='<a href="#" class="btn btn-default" onclick="removeTag(\'' + sTag + '\'); return false;"><i class="fa-solid fa-tag"></i> ' + sTag + ' <i class="fa-solid fa-xmark"></i></a> ';

            // select tag
            $('#selecttag option').each(function () {
                if (this.value === sTagClass) {
                    this.selected = 'selected';
                } 
            });
        
            $('.tags').each(function () {
                if (!$(this).hasClass(sTagClass)) {
                    $(this).hide();
                }
            });
        }
    }
    // show active tag filters
    $('.tagfilterinfo').html(sTaglist);

    // filter hosts
    filterApps('divwebs',  'appname', true);  // app overview
    filterApps('divsetup', 'divhost', false); // page "setup"

    // update url int the browser
    setAdressbar();

    // update top right selectpicker
    $('.selectpicker').selectpicker('refresh');
}

/**
 * callback ... filter the table
 * use addFilterToTable() before.
 * @param  {string}  sDiv         id of a div to search in
 * @param  {string}  sClass       class name to search in
 * @param  {bool}    bIsOverview  flag: is it the overview page (to hide the right element)
 * @returns {undefined}
 */
function filterApps(sDiv, sClass, bIsOverview) {
    var sTarget = '#' + sDiv + ' .'+sClass;

    var filter = aViewFilters[sDiv];
    var oHide=false;
    $(sTarget).removeHighlight();
    if (filter) {
        oHide=bIsOverview 
            ? $(sTarget + ":not(:regex('" + filter + "'))").parent().parent()
            : $(sTarget + ":not(:regex('" + filter + "'))")
            ;
        oHide.hide();
        $(sTarget).highlight(filter);
    }
    return true;
}


/**
 * set the active tab and show the content
 * @param {string} sFilter value of the active tab
 * @returns {undefined}
 */
function setTab(sFilter) {
    aViewFilters['tab'] = sFilter;
    showDiv();
}

// ----------------------------------------------------------------------
// Tags
// ----------------------------------------------------------------------

/**
 * add a single tag to the filter
 * @param {string} sTagname  tag name to add
 */
function addTag(sTagname) {
    if (!aViewFilters['tag']) {
        aViewFilters['tag'] = [];
    }
    if (aViewFilters['tag'].indexOf(sTagname) > -1) {
        return false;
    }
    aViewFilters['tag'].push(sTagname);
    applyViewFilter();
}

function UNUSED_setTagClass(sClass) {
    addTag(getTagByClassname(sClass));
}

/**
 * delete a single tag from the filter
 * @param {string} sTagname  tag name to delete
 */
function removeTag(sTagname){
    for (var i = 0; i < aViewFilters['tag'].length; i++) {
        if (aViewFilters['tag'][i] == sTagname) {
            aViewFilters['tag'].splice(i, 1);
        }
    }
    applyViewFilter();
}

/**
 * tag filter: get css name by given tag name
 * @param {type} sTagname  name of the tag in the dropdown
 * @returns {String}
 */
function getClassByClearnameTag(sTagname) {
    var sReturn = '';
    $('#selecttag option').each(function () {
        if (this.text === sTagname) {
            sReturn = this.value;
        }
    });
    return sReturn;
}

/**
 * tag filter: get clear text name of tag by classname of tagfilter
 * from select box and select 
 * @param {string} sClassname
 * @returns {string}
 */
function getTagByClassname(sClassname) {
    var sReturn = '';
    $('#selecttag option').each(function () {
        if (this.value === sClassname) {
            // this.selected = 'selected';
            sReturn = this.text.replace(/\-\-\-/, '');
        } 
    });
    return sReturn;
}

// ----------------------------------------------------------------------

/**
 * 
 * @param {string} sTarget  filter key to set
 * @param {string} sFilter  text to filter by
 */
function setTextfilter(sTarget, sFilter) {
    aViewFilters[sTarget] = sFilter;
    applyViewFilter();
}

/**
 * show a timer ad a
 * @returns {undefined}
 */
function refreshTimer() {
    if (iRefreshCounter == 0) {
        $('#counter').html(
            '<span>-</span><br>'
            + '<div style="width:100%; float: left;">&nbsp;</div>'
        );
    }
    if (
        location.hash == '#divsetup'
        || location.hash == '#divabout'
    ) {
        return false;
    }
    $('#counter span').html(iReload - iRefreshCounter + 's');
    $('#counter div').css('width', (100 - (iRefreshCounter / iReload * 100)) + '%');
    iRefreshCounter++;

    if (iRefreshCounter > iReload) {
        showDiv();
    }
}


// ----------------------------------------------------------------------
// FUNCTIONS for divs
// ----------------------------------------------------------------------

/**
 * init relative navigation
 * - used on app detail page
 * - this function is called in postLoad()
 * @returns {bool}
 */
function initRelNav(){
    var sButtons='';
    $('div.row').each(function() {
        if (this.id){
            sButtons+='<button class="btn btn-default" onclick="$(\'.row\').hide(); $(\'#'+this.id+'\').show();">'+this.dataset.title+'</button> ';
        }
    });
    if(sButtons){
        sButtons+='<button class="btn btn-default" onclick="$(\'.row\').show();">X</button> ';
        $('#relnavbuttons').html(sButtons);
    }
    return true;
}


/**
 * load content and update top navi item
 * @returns {undefined}
 */
function showDiv() {

    var oOut = $('#content');
    var oError = $('#errorajax');
    var url = './get.php?';

    var aCfgViews = {
        '#divabout': 'viewabout',
        '#divdebug': 'viewdebug',
        '#divnotifications': 'viewnotifications',
        '#divproblems': 'viewproblems',
        '#divsetup': 'viewsetup'
    };

    var item = 'viewweblist';
    var appid = '';
    var count = 100; // see public_html/server/classes/appmonitor-server-gui.class.php -> public function generateViewNotifications()

    var sDiv = aViewFilters['tab'];
    if (sDiv && sDiv.indexOf('divweb-') > 0) {
        item = 'viewweb';
        appid = sDiv.replace(/\#.*\-/, '');
    } else if (sDiv && sDiv.indexOf('notifications-') > 0) {
        item = 'viewnotifications';
        count = sDiv.replace(/\#.*\-/, '');
        if(count=="all"){
            count=false;
        }
    } else {
        if (aCfgViews[sDiv]) {
            item = aCfgViews[sDiv];
        }

    }
    url += 'item=' + item + (appid ? '&appid=' + appid : '')+ (count ? '&count=' + count : '');

    var sInfo='new content<br>DIV: '+sDiv+'<br>appid: '+appid+'<br>URL: '+url+'<br><hr>';
    var sInfo = '';
    // oOut.html(sInfo);

    iStartTime = getUnixTS();
    iRefreshCounter = 0;

    oOut.css('opacity', '0.2');
    $('a.reload i').addClass('fa-spin');
    jQuery.ajax({
        url: url,
        // data: queryparams,
        type: 'GET',
        success: function (data) {
            oOut.css('opacity', 1);
            oOut.html(sInfo + data);
            oError.hide();
            postLoad(false);
        },
        error: function (data) {
            oOut.css('opacity', 1);
            oOut.html(sInfo + '<br>' 
                + '<h2>'+data.status + ' - ' + data.statusText + '</h2>' 
                + data.responseText.replace(/<[^>]*>/g, "")+'<br><br>'
            );
            oError.show();
            postLoad(false);
        }
    });
}

/**
 * let a counter update its age in sec
 * @returns {undefined}
 */
function timerAgeInSec() {
    var iStart = false;
    $(".timer-age-in-sec").each(function () {

        oStartValue = $(this).find("span.start");
        if (oStartValue.length === 0) {
            iStart = $(this).html();
            $(this).html('<span class="start" style="display: none;">' + iStart + '</span><span class="current"></span>');
        }
        oStartValue = $(this).find("span.start");
        iStart = $(oStartValue[0]).html() / 1;

        iCurrent = (iStart + Math.floor(getUnixTS() - iStartTime));
        if (iCurrent > 10) {
            iCurrent = Math.floor(iCurrent / 5) * 5;
            // iCurrent = iCurrent;
        }
        oNewValue = $(this).find("span.current");

        $(oNewValue[0]).html(iCurrent);
        // window.setTimeout("timerAgeInSec()", 1000);
    });
}

function postLoad(bIsFirstload) {
    if (bIsFirstload) {
        return true;
    }

    var sDiv = aViewFilters['tab'];
    /*
    $(".divtopnavi a").removeClass("active");
    $("a[href='" + sDiv + "']").addClass("active");
    $("nav a").blur();
    */
    $('a.reload i').removeClass('fa-spin');
    $(".sidebar-menu li").removeClass("active");
    $("a[href='" + sDiv + "']").parent().addClass("active");
    $("a").blur();
    if (sDiv.indexOf('divweb') > 0) {
        $("a[href='#divwebs']").parent().addClass("active");
    }

    addFilter4Webapps('divwebs');
    addFilter4Webapps('divsetup');

    applyViewFilter();

    $('.datatable').dataTable({});
    $('.datatable-checks').dataTable({"order": [[0, "desc"]]});
    $('.datatable-hosts').dataTable({"order": [[0, "desc"]], "aLengthMenu":[[50,-1],[50,"---"]]});
    $('.datatable-notifications-webapp').dataTable({'order': [[1, 'desc']]});
    $('.datatable-notifications').dataTable({'order': [[1, 'desc']], "aLengthMenu":[[25,100,-1],[25,100,"---"]]});

    // copy problem badges from tile to menu
    if ($('#badgetile_allapps')){
        $('#menubagde_allapps').html( $('#badgetile_allapps').html() );
    }
    if ($('#badgetile_problems')){
        $('#menubagde_problems').html( $('#badgetile_problems').html() );
    }
    if($('#txtTotalstatus').html()){
        $('#totalstatus').html( $('#txtTotalstatus').html() );
    }

    initRelNav();

}

/**
 * initialize GUI elements: timer, set tag filter, set active tab+div
 * @returns {undefined}
 */
function initGuiStuff() {

    aViewFilters = {
        'tag': getQueryVariable('tag') ? getQueryVariable('tag').split(',') : [],
        'tab': window.location.hash ? window.location.hash : '#divwebs',
        'divwebs': getQueryVariable('divwebs'),
        'divsetup': getQueryVariable('divsetup')
    };

    // set onclick event for links (navigation bar)
    $(".sidebar-menu a[href^=\'#\']").click(function () {
        if (this.hash) {
            setTab(this.hash);
        }
        return false;
    });

    // show the content
    showDiv();

    // reload timer
    window.setInterval('refreshTimer()', 1000);

    // activate age timer on tiles
    window.setInterval("timerAgeInSec();", 1000);

    // react on browser navigation buttons and url change by setAdressbar()
    window.addEventListener('hashchange', function(event) {
        setTab(window.location.hash);
    });

    // callback: tag filter
    $('#selecttag').on('changed.bs.select', function (e, clickedIndex, isSelected, previousValue) {
        aViewFilters['tag']= [];
        var aClasses=$('#selecttag').selectpicker('val');
        for (var i = 0; i < aClasses.length; i++) {
            aViewFilters['tag'].push(getTagByClassname(aClasses[i]));
        }
        applyViewFilter();
    
      });    
}
