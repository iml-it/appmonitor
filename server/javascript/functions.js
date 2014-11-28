
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
 * @param {link object} oLink
 * @returns {undefined}
 */
function showDiv(sDiv) {
    $(".outsegment").hide();
    $(sDiv).fadeIn(300);
    $(".divtopnavi a").removeClass("active");
    $("a[href*=" + sDiv + "]").addClass("active");
}


function updateContent() {
    $.ajax({
        url: "?updatecontent",
        context: document.body
    }).done(function (data) {
        $(".divmain").html(data);
    });
}
