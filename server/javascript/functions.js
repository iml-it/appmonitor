
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
 * switch the visible output div
 * @param {string} sDiv
 * @returns {undefined}
 */
function showDiv(sDiv) {
    $(".outsegment").hide();
    $(sDiv).fadeIn(300);
    $(".divtopnavi a").removeClass("active");
    $("a[href='" + sDiv + "']").addClass("active");
    if(sDiv.indexOf('divweb')>0){
        $("a[href='#divwebs']").addClass("active");
    }
}


function updateContent() {
    $.ajax({
        url: "?updatecontent",
        context: document.body
    }).done(function (data) {
        $(".divmain").html(data);
    });
}
