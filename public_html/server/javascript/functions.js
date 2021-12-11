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


const visjsNetOptions = {
    layout: {
        hierarchical: {
          direction: "DU",
          sortMethod: "directed",
        },
    },
    physics: {
        hierarchicalRepulsion: {
          avoidOverlap: +1,
        },
    }
};
    
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
 * 
 * @returns {undefined}
 */
function setAdressbar() {

    var url = location.protocol 
            + '//'+ location.hostname
            + (location.port ? ':'+location.port : '')
            + location.pathname
            + '?'
            + (aViewFilters['tag'] ? '&tag=' + aViewFilters['tag'] : '')
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

    // filter by tag
    if (aViewFilters['tag']) {
        var sTagClass = getClassByClearnameTag(aViewFilters['tag']);
        $('.tags').each(function () {
            if (!$(this).hasClass(sTagClass)) {
                $(this).hide();
            }
        });
    }
    // filter by tag
    $('.tagfilterinfo').html(aViewFilters['tag'] ? '<i class="fa fa-tag"></i> ' + aViewFilters['tag'] + ' <a href="#" class="btn btn-danger" onclick="setTagClass(\'\'); return false;">x</a>' : '');

    // filter hosts
    filterMonitors('divwebs');
    filterMonitors('divsetup');

    // update url int the browser
    setAdressbar();
}


/**
 * callback ... filter the table
 * use addFilterToTable() before.
 * @returns {undefined}
 */
function filterMonitors(sDiv) {
    var sTarget = '#' + sDiv + ' .divhost';
    if (!sTarget) {
        return false;
    }
    var filter = aViewFilters[sDiv];
    $(sTarget).removeHighlight();
    if (filter) {
        $(sTarget + ":not(:regex('" + filter + "'))").hide();
        $(sTarget).highlight(filter);
    }
    return true;
}


function setTab(sFilter) {
    aViewFilters['tab'] = sFilter;
    showDiv();
}
function setTag(sFilter) {
    aViewFilters['tag'] = sFilter;
    applyViewFilter();
}
function setTagClass(sClass) {
    setTag(getTagByClassname(sClass));
}

function setTextfilter(sTarget, sFilter) {
    aViewFilters[sTarget] = sFilter;
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
            this.selected = 'selected';
            sActiveTag = sTagname;
            sReturn = this.value;
        } else {
            this.selected = false;
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
            this.selected = 'selected';
            sReturn = this.text.replace(/\-\-\-/, '');
        } else {
            this.selected = false;
        }
    });
    return sReturn;
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
 * load content and update top navi item
 * @returns {undefined}
 */
function showDiv() {

    var oOut = $('#content');
    var url = './get.php?';

    var sCfgViews = {
        '#divabout': 'viewabout',
        '#divdebug': 'viewdebug',
        '#divnotifications': 'viewnotifications',
        '#divsetup': 'viewsetup'
    };

    var item = 'viewweblist';
    var appid = '';

    var sDiv = aViewFilters['tab'];
    if (sDiv && sDiv.indexOf('divweb-') > 0) {
        item = 'viewweb';
        appid = sDiv.replace(/\#.*\-/, '');
    } else {
        if (sCfgViews[sDiv]) {
            item = sCfgViews[sDiv];
        }

    }
    url += 'item=' + item + (appid ? '&appid=' + appid : '');

    // var sInfo='new content<br>DIV: '+sDiv+'<br>appid: '+appid+'<br>URL: '+url+'<br><hr>';
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
            postLoad(false);
        },
        error: function (data) {
            oOut.css('opacity', 1);
            oOut.html(sInfo + 'Failed :-/' + data);
            postLoad(false);
            /*
             $('#errorlog').html(
             $('#errorlog').html('AJAX error: <a href="' + url + '?' + queryparams + '">' + url + '?' + queryparams + '</a>')
             );
             */
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

}

/**
 * initialize GUI elements: timer, set tag filter, set active tab+div
 * @returns {undefined}
 */
function initGuiStuff() {

    aViewFilters = {
        'tag': getQueryVariable('tag') ? getQueryVariable('tag') : '',
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

    // Revert to a previously saved state
    window.addEventListener('popstate', function(event) {
        if(event.state && event.state.filter){
            aViewFilters=event.state.filter;
            $('#content').html(event.state.content);
            postLoad();
        }
        // TODO: load pages of app detail view
        if(event.state===null){
            // location.reload();
        }
    });
}
