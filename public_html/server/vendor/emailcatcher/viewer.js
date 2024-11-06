/*

PHP EMAIL CATCHER

search functionality

*/

// ----------------------------------------------------------------------
// cnstants
// ----------------------------------------------------------------------
const tableId = 'messagestable';
const searchId = 'search';
const lsVar = 'searchEmailCatcher';


// ----------------------------------------------------------------------
// functions
// ----------------------------------------------------------------------

/**
 * read search field and hide non matching rows
 * @returns void
 */
function filterTable(){
    var sFilter=document.getElementById(searchId).value;
    localStorage.setItem(lsVar, sFilter);
    var table=document.getElementById(tableId);
    if (!table){
        return false;
    }
    var rows=table.rows;
    for(var i=1;i<rows.length;i++){
        if(rows[i].innerText.toLowerCase().indexOf(sFilter.toLowerCase()) == -1){
            rows[i].style.display='none';
        }
        else{
            rows[i].style.display='table-row';
        }
    }
}


// ----------------------------------------------------------------------
// main
// ----------------------------------------------------------------------

document.getElementById(searchId).value=''+localStorage.getItem(lsVar);

document.getElementById(searchId).addEventListener('keyup', filterTable);
document.getElementById(searchId).addEventListener('keypress', filterTable);
filterTable();

// ----------------------------------------------------------------------
