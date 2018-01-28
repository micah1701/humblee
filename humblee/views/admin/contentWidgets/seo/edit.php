<!-- list of input fields below to be saved as content -->
<input type="hidden" name="serialize_fields" value="page_title,meta_description,og_title,og_description,og_image">
<textarea style="display:none" id="edit_content"></textarea>

<section class="hero is-light">
    <div class="section">
        <div class="columns">
            <div class="column">
                
                <div class="field">
                    <label class="label" for="page_title">SEO Page Title:</label> 
                        <div class="control">
                            <input type="text" class="input" id="page_title" name="page_title" value="<?php echo (isset($content_array['page_title'])) ? $content_array['page_title'] : '' ?>">
                            <p>The page title should describe the given page and include the most important words you want to rank for in search results. Note that search engines will only display the first 60-70 characters of this title.</p>
                        </div>
                </div>
                
                <div class="field">
                    <label class="label" for="meta_description">SEO Page Description:</label> 
                        <div class="control">
                            <textarea class="textarea lengthcount" maxlength="300" id="meta_description" name="meta_description"><?php echo $content_array['meta_description'] ?></textarea>
                        </div>
                </div>
                
                <div class="box">
                    <p class="is-size-5">Open Graph Tags</p>
                    <p>Used by Facebook and other social media sharing sites</p>
                    
                    <div class="field">
                        <label class="label" for="og_image">Open Graph Image Path:
                            <span class="icon tooltip is-tooltip-right has-text-info" data-tooltip="Primary photo shown when sharing this page"><i class="fas fa-info-circle"></i></span>
                        </label>
                        <!--
                        <input class="file-input" type="file" id="og_image" name="og_image">
                        <span class="file-cta">
                            <span class="file-icon"><i class="fas fa-upload"></i></span>
                            <span class="file-label">
                                Choose a file…
                            </span>
                        </span>
                        -->
                        <input class="input" id="og_image" name="og_image" placeholder="example: https://<?php echo $_ENV['config']['domain'] ?>/applications/images/your-file.png" value="<?php echo (isset($content_array['og_image'])) ? $content_array['og_image'] : '' ?>">
                        <p class="help">Must be fully qualified URL starting with <em>http</em></p>
                         
                    </div>
                    
                    <div class="field">
                        <label class="label" for="og_title">Open Graph Page Title:</label> 
                            <div class="control">
                                <input type="text" class="input" id="og_title" name="og_title" value="<?php echo (isset($content_array['og_title'])) ? $content_array['og_title'] : '' ?>">
                                <p>If set, this title will replace the page's primary SEO title when sharing on social media</p>
                            </div>
                    </div>
                    
                    <div class="field">
                        <label class="label" for="og_description">Open Graph Page Description:</label> 
                            <div class="control">
                                <textarea class="textarea lengthcount" maxlength="160" id="og_description" name="og_description"><?php echo (isset($content_array['og_description'])) ? $content_array['og_description'] : '' ?></textarea>
                            </div>
                    </div>
                </div>
                
            </div>
        
            <div class="column">
                <div class="box" id="google_sample">
                    <span id="google_sample_title"><?php echo (isset($content_array['page_title'])) ? $content_array['page_title'] : 'Page Title Goes Here' ?></span>
                    <span id="google_sample_url"><?php echo $_SERVER['HTTP_HOST'] . _app_path . ltrim($page_data->url,"/") ?></span>
                    <span id="google_sample_description"><?php echo (isset($content_array['meta_description'])) ? $content_array['meta_description'] : 'Description goes here' ?></span></span>
                    <p>
                        <br>
                        <em>This is a sample of how this page may appear in Google's search results.</em>
                    </p>
                </div>
                
                <div class="box" id="facebook_sample">
                    <span id="facebook_sample_image">
                        <?php if(isset($content_array['og_image']) && $content_array['og_image'] != "")
                        {
                        ?>
                        <img src="<?php echo $content_array['og_image'] ?>">
                        <?php
                        }
                        ?>
                    </span>
                    <span id="facebook_sample_title">
                        <?php
                        if(isset($content_array['og_title']) && $content_array['og_title'] != "")
                        {
                            $og_title_preview = $content_array['og_title'];
                        }
                        elseif(isset($content_array['meta_title']) && $content_array['meta_title'] != "")
                        {
                            $og_title_preview = $content_array['meta_title'];
                        }
                        else
                        {
                            $og_title_preview = rtrim($_SERVER['HTTP_HOST'] . _app_path . trim($page_data->url,"/"),"/");
                        }
                        echo $og_title_preview;
                        ?>
                    </span>
                    <span id="facebook_sample_description">
                        <?php
                        if(isset($content_array['og_description']) && $content_array['og_description'] != "")
                        {
                            $og_description_preview = $content_array['og_description'];
                        }
                        elseif(isset($content_array['meta_description']) && $content_array['meta_description'] != "")
                        {
                            $og_description_preview = $content_array['meta_title'];
                        }
                        else
                        {
                            $og_description_preview = "description goes here but its blank now so this string should be removed";
                        }
                        echo $og_description_preview;
                        ?>
                    </span>
                    <span id="facebook_sample_domain"><?php echo $_ENV['config']['domain'] ?></span>
                    
                    <p>
                        <br>
                        <em>This is a sample of how the page may appear on Facebook</em>
                    </p>
                </div>
                
            </div>
        
        </div>
               
</section>

<?php 
    /**
     * because the .css and .js code for this widget don't live in the /public folder
     * they need to be included in-line in this file
     */
    echo '<style type="text/css">';
    include('edit.css');
    echo '</style>';
    
    echo '<script type="text/javascript">';
    include('edit.js');
    echo '</script>';
?>
