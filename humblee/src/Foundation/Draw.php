<?php

declare(strict_types=1);

namespace Humblee\Foundation;

class Draw {

    /**
     * Output a given content block's HTML.
     * If admin with content role is logged in, wrap the output in div for inline editing.
     *
     * $contentArray    (Array) Passed from the controller to the view
     * $block_key       (Mixed) String  - The slot_key of the content block to output
     *                  or      Array   - Contains "block_key", optional "field_key", and optional "plaintext" bool
     */
    public static function content(array $contentArray, string|array $block_key = ''): void
    {
        if(!$block_key || $block_key === "" || !is_array($contentArray))
        {
            echo "";
            return;
        }

        $blockKey = (is_array($block_key) && isset($block_key['block_key'])) ? $block_key['block_key'] : $block_key;

        if(!isset($contentArray[$blockKey]))
        {
            echo "";
            return;
        }

        $content = $contentArray[$blockKey]->content;

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

        if($contentArray[$blockKey]->output_type === "content" && (!isset($block_key['plaintext']) || !$block_key['plaintext']))
        {
            if(Core::auth(['admin', 'content', 'publish', 'developer']))
            {
                echo '<div class="cms_block" data-block-key="'.$blockKey.'" data-block-id="'. $contentArray[$blockKey]->block_id .'" data-content-id="'. $contentArray[$blockKey]->content_id .'" data-p13-id="'. $contentArray[$blockKey]->p13n_id .'" data-block-name="'.$contentArray[$blockKey]->name .'" data-block-description="'. $contentArray[$blockKey]->description .'" data-cmstype="'. $contentArray[$blockKey]->input_type .'" data-template-block-id="'. $contentArray[$blockKey]->template_block_id .'">';
                echo $content;
                echo '</div><!-- end cms block '.$blockKey .' -->';
            }
            else
            {
               echo '<div>'.$content.'</div><!-- end cms block '.$blockKey .' -->';
            }
        }
        else
        {
            echo $content;
        }
    }

    /**
     * Output the <title> and other meta tags set from the "SEO & Meta Tags" content editor block
     */
    public static function metaTags(array|false $contentArray = false): void
    {
        if(!$contentArray || !is_array($contentArray))
        {
            echo "";
            return;
        }
        if(!isset($contentArray['meta_tags']))
        {
            echo "";
            return;
        }

        $meta_tags = json_decode($contentArray['meta_tags']->content);

        echo "<title>";
        echo (isset($meta_tags->page_title)) ? $meta_tags->page_title : '';
        echo "</title>\n";

        if(isset($meta_tags->meta_description) && $meta_tags->meta_description !== "")
        {
            echo "<meta name=\"description\" content=\"$meta_tags->meta_description\">\n";
        }

        if(isset($meta_tags->og_title) && $meta_tags->og_title !== "")
        {
            echo "<meta property=\"og:title\" content=\"$meta_tags->og_title\">\n";
        }

        if(isset($meta_tags->og_description) && $meta_tags->og_description !== "")
        {
            echo "<meta property=\"og:description\" content=\"$meta_tags->og_description\">\n";
        }

        if(isset($meta_tags->og_image) && $meta_tags->og_image !== "")
        {
            echo "<meta property=\"og:image\" content=\"$meta_tags->og_image\">\n";
        }
    }

}
