/* global $, XHR_PATH */
$(document).ready(function(){
   
   loadFolders();
   
    
});

//recursive helper function to draw UL list from JSON data
function generateMenu(data)
{
    var ul = $("<ul>");

        $(data).each(function (i, dir) {
            var li = $('<li>' + dir.name + '</li>');
            ul.append(li);
            
            if (dir.children != null && dir.children.length > 0) {
                li.append(generateMenu(dir.children));
            }
        });
        return ul;
}

function loadFolders(){
    
    $.getJSON(XHR_PATH +'listMediaFolders',function(response)
    {
        $("folders").html(generateMenu(response));
    });
}