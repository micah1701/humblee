<?php

class Draw {
    
    /**
     * Out put a given content block's HTML.
     * If admin with content role is logged in, wrap the output in div for inline editing
     * 
     * $contentArray    (Array) Passed from the controller to the view and now passed to this function
     * $block_key       (Mixed) String  - The "object_key" name of the content block to be output
     *                  or      Array   - An Array containing the "block_key", the "field_Key" (the key value in the json object) and "plaintext" BOOL value if content should be wrapped in HTML div or not.
     */
    public static function content($contentArray, $block_key="")
    {
        if(!$block_key || $block_key == "" || !is_array($contentArray))
        {
            echo "";
            return;
        }

        $objectKey = (is_array($block_key) && isset($block_key['block_key'])) ? $block_key['block_key'] : $block_key;
        
        if(!isset($contentArray[$objectKey]))
        {
            echo "Invalid Block Object Key";
            return;
        }
        
        $content = $contentArray[$objectKey]->content;
        
        $contentJson = json_decode($content);
        $isJson = (json_last_error() === 0);
        
        if($isJson)
        {
            if(is_array($block_key) && isset($block_key['field_key']))
            {
                $content = $contentJson->$block_key["field_key"];
            }
            else
            {
                $content = "MJM WEB ERROR: Be sure to pass a specific field_key to display. ". print_r($contentJson,true);
            }
        }
        
        if($contentArray[$objectKey]->output_type == "content" && (!isset($block_key['plaintext']) || !$block_key['plaintext'])) // wrap visible content in a div.
        {
            if(Core::auth('admin') && Core::auth('content') || Core::auth('developer'))
            {
                echo '<div class="cms_block" data-block-key="'.$objectKey.'" data-block-id="'. $contentArray[$objectKey]->id .'" data-block-name="'. $contentArray[$objectKey]->name .'" data-block-description="'. $contentArray[$objectKey]->description .'" data-cmstype="'. $contentArray[$objectKey]->input_type .'">';
                echo $content;
                echo '</div><!-- end cms block '.$objectKey .' -->';
            }
            else
            {
               echo '<div>'.$content.'</div><!-- end cms block '.$objectKey .' -->'; 
            }
        }
        else // content is meta data or not visible on screen
        {
            echo $content;   
        }
        
    }
    
}