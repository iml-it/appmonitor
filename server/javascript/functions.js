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
 * reload the page and remove the query parameters
 * @returns {undefined}
 */
function reloadPage() {
    if (window.location.search) {
        window.location.href = window.location.pathname + window.location.hash;
    } else {
        window.location.reload();
    }
}

/**
 * manipulate url in the browser address bar
 * 
 * @returns {undefined}
 */
function setAdressbar() {
    // console.log("--- setAdressbar()"); console.log(aViewFilters);

    var url = '?'
        + (aViewFilters['tag'] ? '&tag=' + aViewFilters['tag'] : '')
        + (aViewFilters['divwebs'] ? '&webapp=' + aViewFilters['divwebs'] : '')
        + aViewFilters['tab'];
    window.history.pushState('dummy', 'Title', url);
}

/**
 * update page content - but not on setup page
 * @returns {Boolean}
 */
function updateContent() {
    if (location.hash == '#divsetup') {
        window.setTimeout("updateContent()", 1000);
        return false;
    }
    location.reload();
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
    var sForm = '<form class="frmFilter">\n\
        <i class="fa fa-filter"></i>  <input type="text" id="eFilter" class="inputtext" size="20" value="' + sValue + '" \n\
            onkeypress="setTextfilter(\'' + sTarget + '\', this.value);" \n\
            onkeyup="setTextfilter(\'' + sTarget + '\', this.value);" \n\
            onchange="setTextfilter(\'' + sTarget + '\', this.value);">\n\
        <span class="tagfilterinfo"></span>\n\
        </span>\n\
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
    $('#divwebs .divhost').show();
    $('#divsetup .divhost').show();

    // console.log("--- applyViewFilter()"); console.log(aViewFilters);
    
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
    $('.tagfilterinfo').html(aViewFilters['tag'] ? '<i class="fa fa-tag"></i> ' + aViewFilters['tag'] + ' <a href="#" class="btn btndel" onclick="setTagClass(\'\'); return false;">x</a>' : '');

    // filter hosts
    filterMonitors('divwebs');
    filterMonitors('divsetup');
    
    // show active tab
    showDiv();
    
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
    var filter = aViewFilters[sDiv];
    $(sTarget).removeHighlight();
    if (filter) {
        // $(sTarget+":regex('" + filter + "')").show();
        $(sTarget + ":not(:regex('" + filter + "'))").hide();
        // $("tr").first().show();

        $(sTarget).highlight(filter);
    } 
}


function setTab(sFilter) {
    aViewFilters['tab'] = sFilter;
    applyViewFilter();
}
function setTag(sFilter) {
    aViewFilters['tag'] = sFilter;
    // console.log("--- setTag("+sFilter+")"); console.log(aViewFilters);
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

// ----------------------------------------------------------------------
// FUNCTIONS for divs
// ----------------------------------------------------------------------

/**
 * switch the visible output div and update top navi item
 * @param {string} sDiv
 * @returns {undefined}
 */
function showDiv(sDiv) {
    sDiv = aViewFilters['tab'];
    $(".outsegment").hide();
    $(sDiv).show();
    $(".divtopnavi a").removeClass("active");
    $("a[href='" + sDiv + "']").addClass("active").blur();
    if (sDiv.indexOf('divweb') > 0) {
        $("a[href='#divwebs']").addClass("active");
    }
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
        if (iCurrent > 5) {
            iCurrent = Math.floor(iCurrent / 5) * 5;
        }
        oNewValue = $(this).find("span.current");

        $(oNewValue[0]).html(iCurrent);
        // window.setTimeout("timerAgeInSec()", 1000);
    });
}


/**
 * initialize GUI elements: timer, set tag filter, set active tab+div
 * @returns {undefined}
 */
function initGuiStuff() {

    // activate age timer on tiles
    var oTimerAgeInSec = window.setInterval("timerAgeInSec();", 5000);

    aViewFilters = {
        'tag': getQueryVariable('tag') ? getQueryVariable('tag') : '',
        'tab': window.location.hash ? window.location.hash : '#divwebs',
        'divwebs': getQueryVariable('divwebs'),
        'divsetup': getQueryVariable('divsetup')
    };
    addFilter4Webapps('divwebs');
    addFilter4Webapps('divsetup');

    applyViewFilter();

    // set onclick event for links (navigation bar)
    $("a[href^=\'#\']").click(function () {
        if(this.hash) { 
            setTab(this.hash); 
        }
        return false;
    });

}
