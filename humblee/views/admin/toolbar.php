<script type="text/javascript">
$(document).ready(function(){
   $.getJSON("<?php echo _app_path ?>core-request/toolbarLoader",function(toolbarData){
       if(toolbarData !== false)
       {
           window.toolbarData = toolbarData;
           $.getScript(toolbarData.js_load);
       }
    });
});
</script>
