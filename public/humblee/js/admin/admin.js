/* global $ */

$(document).ready(function(){
    setFooterPosition();
});

$(window).resize(function(){
   setFooterPosition(); 
});

//move footer to bottom if page doesn't have much content
function setFooterPosition()
{
    return false; // lets revist this later
    
    if ($(document.body).height() < $(window).outerHeight() - $("footer").outerHeight() )
    {
        $('footer.footer').attr('style', 'position: fixed!important; bottom: 0px; width: 100%');
    }
    else
    {
        $('footer.footer').attr('style', '');
    }
}


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