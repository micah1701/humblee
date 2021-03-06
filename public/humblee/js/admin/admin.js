/* global $ */

$(document).ready(function(){
    var menuOpen = false;
    $(".navbar .navbar-burger").on("click",function(){
       if(!menuOpen)
       {
           $(this).addClass('is-active');
           $(".navbar-menu").addClass('is-active');
           menuOpen = true;
       }
       else
       {
           $(this).removeClass('is-active');
           $(".navbar-menu").removeClass('is-active');
           menuOpen = false
       }
    });

    responsiveTables();
});

$(window).resize(function(){

});

/**
 * Register and listen for multiple "Esc" key presses
 * https://gist.github.com/micah1701/510cdde498bcaee192715b23fabc168e
 */
var escEvents = new Array();

    function setEscEvent(eventName, eventFunction){
        escEvents.push({ eventName: eventName, eventFunction: eventFunction });
    }

    function unsetEscEvent(eventName)
    {
      $.each(escEvents, function (index, value) {
          if (value['eventName'] == eventName)
          {
              escEvents.splice(index);
          }
      });
    }

    function runEscEvent()
    {
        var lastEvent = escEvents.length - 1;
        eval(escEvents[lastEvent].eventFunction());
        unsetEscEvent(escEvents[lastEvent].eventName);
    }

    // This is the listener for the escape key to be pressed
    $(document).on( 'keyup', function ( event ){
        event.preventDefault();
        if (escEvents.length > 0 && event.keyCode === 27) {
            runEscEvent();
        }
    });

/**
 * Custom "confirmation" alert box
 */
function confirmation(message,callbackConfirm,callbackCancel)
{
    $("#confirmationBox .modal-card-body .media-content").html(message);
    $("#confirmationBox").addClass('is-active');
    $("#confirmationBox #confirmButton").on("click",callbackConfirm);
    $("#confirmationBox button.cancel").on("click",callbackCancel);

    //register ESC key and other ways to close the modal
    setEscEvent('confirmationBox',function () { confirmationClose() });
    $("#confirmationBox button").on("click",function(){
        confirmationClose();
        unsetEscEvent('confirmationBox');
    });
}
    function confirmationClose()
    {
        $("#confirmationBox").removeClass('is-active');
    	$("#confirmationBox #confirmButton").off("click"); // unbind the "onclick" events
    	$("#confirmationBox button").off('click');
    }

/**
 * display a quick notification box that slides up from the bottom for a few seconds
 */
function quickNotice(message,cssClass,timeOnScreen)
{
    cssClass = (cssClass) ? cssClass : 'is-success';
    timeOnScreen = (timeOnScreen) ? timeOnScreen : 3000;
    $("#quickNotice").remove(); // remove any notice that's still showing before creating a new one.

    var html = '<div id="quickNotice" style="position: absolute; z-index: 100; width: 100%" class="notification has-text-centered has-text-weight-semibold '+cssClass+'">';
        html+= message;
        html+= '</div>';

    $('body').append(html);
    var notice = $("#quickNotice"),
        startPosition = (notice.innerHeight()) * -1;
        notice.css({'bottom':startPosition+'px'});
        notice.animate({bottom:0},300);
        setTimeout(function(){
            notice.animate({bottom:startPosition+'px'},500,function(){
                notice.remove();
            });
        },timeOnScreen);
}

function responsiveTables(){
    $('table').each(function(){
        var element = $(this);
       if(element.width() > $(window).width()){
           element.wrap('<div style="width: 94%; overflow-x: scroll">');
       }
    });
}