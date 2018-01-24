/* global $ */

$(document).ready(function(){
   setFooterPosition();
});

$(window).resize(function(){
   setFooterPosition(); 
});

//move footer to bottom if page doesn't have much content
function setFooterPosition(){
    if ($(document.body).height() < $(window).outerHeight() - $("footer").outerHeight() )
    {
        $('footer.footer').attr('style', 'position: fixed!important; bottom: 0px; width: 100%');
    }
    else
    {
        $('footer.footer').attr('style', '');
    }
}