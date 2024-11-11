/*

PHP EMAIL CATCHER

- search functionality
- save/ restore view settings

*/

// ----------------------------------------------------------------------
// cnstants
// ----------------------------------------------------------------------
const tableId = 'messagestable';
const searchId = 'search';

const lsVar_prefix = "emailcatcher_";
const lsVar_search = lsVar_prefix + "_search";
const lsVar_header = lsVar_prefix + "_showHeader";
const lsVar_source = lsVar_prefix + "_showSource";

var bViewHeader = lsGet(lsVar_header, 1);
var bViewSource = lsGet(lsVar_source, 0);


// ----------------------------------------------------------------------
// functions
// ----------------------------------------------------------------------

/**
 * Helper load a variable from local storage
 * @param {string} key           key to read from localstorage
 * @param {*}      defaultvalue  default value if "null" or "NaN" was returned
 * @returns 
 */
function lsGet(key, defaultvalue) {
    return localStorage.getItem(key).replace(/^(null|NaN)$/g, defaultvalue);
}

// ----------------------------------------------------------------------

/**
 * read search field and hide non matching rows
 * @returns void
 */
function filterTable() {
    var sFilter = document.getElementById(searchId).value;
    localStorage.setItem(lsVar_search, sFilter);
    var table = document.getElementById(tableId);
    if (!table) {
        return false;
    }
    var rows = table.rows;
    for (var i = 1; i < rows.length; i++) {
        if (rows[i].innerText.toLowerCase().indexOf(sFilter.toLowerCase()) == -1) {
            rows[i].style.display = 'none';
        }
        else {
            rows[i].style.display = 'table-row';
        }
    }
}

// ----------------------------------------------------------------------

/**
 * Show / hide message header
 * @global integer  bViewHeader   flag: view header - 1=yes; 0=no
 */
function viewHideHeader() {
    if (!document.getElementById('msg-header')) {
        return false;
    }
    document.getElementById('msg-header').style.display = bViewHeader ? 'block' : 'none';
    document.getElementById('btn-header').className = bViewHeader ? 'button active' : 'button';
    return true;
}

/**
 * toggle message headers; it inverst bViewHeader and calls viewHideHeader function
 * @global integer  bViewHeader   flag: view header - 1=yes; 0=no
 * @returns true
 */
function toggleViewHeader() {
    bViewHeader = Math.abs(bViewHeader - 1);
    localStorage.setItem(lsVar_header, bViewHeader);
    viewHideHeader();
    return true;
}

/**
 * Show message source or html
 * @global integer  bViewSource   flag: view html or source - 1=show source; 0=show html
 */
function viewSource(bSource) {
    if (!document.getElementById('msg-html')) {
        return false;
    }
    document.getElementById('msg-html').style.display = bSource ? 'none' : 'block';
    document.getElementById('msg-source').style.display = bSource ? 'block' : 'none';

    document.getElementById('btn-html').className = bSource ? 'button' : 'button active';
    document.getElementById('btn-source').className = bSource ? 'button active' : 'button';

    if (bSource != bViewSource) {
        bViewSource = bSource;
        localStorage.setItem(lsVar_source, bSource);
    }
    return true;
}

// ----------------------------------------------------------------------
// main
// ----------------------------------------------------------------------

// --- search field and filter table
document.getElementById(searchId).value = '' + lsGet(lsVar_search, '');

document.getElementById(searchId).addEventListener('keyup', filterTable);
document.getElementById(searchId).addEventListener('keypress', filterTable);
filterTable();

// --- view settings
var bViewHeader = Math.round(lsGet(lsVar_header, 1));
var bViewSource = Math.round(lsGet(lsVar_source, 0));

if (bViewHeader !== 0 && bViewHeader !== 1) {
    bViewHeader = 1;
}

viewHideHeader();
viewSource(bViewSource);

// ----------------------------------------------------------------------
