/* global $ */

$(document).ready(function(){

   setFooterPosition()

   loadContentMenu();
   
   $("#toggleContentEditDrawer").on("click",function(){
       toggleContentEditDrawer(300);
   })
   
    
});

$(window).resize(function(){
   setFooterPosition(); 
});

//move footer to bottom if page doesn't have much content
function setFooterPosition(){
    if ($(document.body).height() < $(window).outerHeight() - $("footer").outerHeight() )
    {
        $('footer').attr('style', 'position: fixed!important; bottom: 0px; width: 100%');
    }
    else
    {
        $('footer').attr('style', '');
    }
}

// load the menu of editable content
function loadContentMenu(){
    $.get(XHR_PATH +'loadContentMenu',function(data){
        $("#contentMenu").html(data);
        $("#contentMenu ul").addClass("menu-list");
    });
}

// show or hide the editable content menu
function toggleContentEditDrawer(animateTime)
{
    var drawer = $("#editnav"),
        drawerWidth = drawer.outerWidth(),
        drawerClosed = 30;
        if(drawerWidth  != drawerClosed) // drawer is open, so close it
        {
            drawer.animate({width: drawerClosed+'px' },animateTime);
            $("#contentMenu, #editNav h3").fadeOut('fast');
        }
        else
        {
            drawer.animate({width:'25%'},animateTime);
            $("#contentMenu, #editNav h3").fadeIn(animateTime+500);
        }
}