/* global $ */

$(document).ready(function(){

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
    
    var html = '<div id="quickNotice" style="position: absolute; z-index: 100; width: 100%; text-align: center" class="notification '+cssClass+'">';
        html+= message;
        html+= '</div>';
    
    $('body').append(html);
    var notice = $("#quickNotice"),
        startPosition = (notice.innerHeight()) * -1;
        console.log('postion: '+startPosition);
        notice.css({'bottom':startPosition+'px'});
        notice.animate({bottom:0},300);
        setTimeout(function(){
            notice.animate({bottom:startPosition+'px'},500,function(){
                notice.remove();    
            });
        },timeOnScreen);
}