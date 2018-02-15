<?php

class Draw {

    /**
     * Output a given content block's HTML.
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
            //this content doesn't exist
            echo "";
            return;
        }

        $content = $contentArray[$objectKey]->content;

        $contentJson = json_decode($content);
        $isJson = (json_last_error() === 0);

        if($isJson)
        {
            if(is_array($block_key) && isset($block_key['field_key']) && isset($contentJson->$block_key['field_key']))
            {
                $content = $contentJson->$block_key['field_key'];
            }
            else
            {
                $content = "";
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

    /**
     * Output the <title> and other meta tags set from the "SEO & Meta Tags" content editor block
     */
    public static function metaTags($contentArray=false)
    {
        if(!$contentArray || !is_array($contentArray))
        {
            echo "";
            return;
        }
        if(!isset($contentArray['meta_tags']))
        {
            echo "";
            return false;
        }

        $meta_tags = json_decode($contentArray['meta_tags']->content);

        echo "<title>";
        echo (isset($meta_tags->page_title)) ? $meta_tags->page_title : '';
        echo "</title>\n";

        if(isset($meta_tags->meta_description) && $meta_tags->meta_description != "")
        {
            echo "<meta name=\"description\" content=\"$meta_tags->meta_description\">\n";
        }

        if(isset($meta_tags->og_title) && $meta_tags->og_title != "")
        {
            echo "<meta property=\"og:title\" content=\"$meta_tags->og_title\">\n";
        }

        if(isset($meta_tags->og_description) && $meta_tags->og_description != "")
        {
            echo "<meta property=\"og:description\" content=\"$meta_tags->og_description\">\n";
        }

        if(isset($meta_tags->og_image) && $meta_tags->og_image != "")
        {
            echo "<meta property=\"og:image\" content=\"$meta_tags->og_image\">\n";
        }
    }

}