<?php
$crypto = new Core_Model_Crypto;
$hmac_pair = $crypto->get_hmac_pair();
?>
<input type="hidden" id="hmac_token" name="hmac_token" value="<?php echo $hmac_pair['message'] ?>">
<input type="hidden" id="hmac_key" name="hmac_key" value="<?php echo $hmac_pair['hmac'] ?>">

<div class="columns">
    <div class="column">
        <a href="/admin/feed" class="icon-text is-size-7 tooltip" data-tooltip="Go back to list of articles without saving any changes"><span class="icon"><i class="fas fa-arrow-circle-left"></i></span><span>Return to Feed List</span></a>

        <h1 class="title">Edit Article</h1>
        <div id="newerRevisionWarning" class="notification is-warning is-hidden">
            <span class="icon"><i class="fa fa-info-circle"></i></span>A more recently saved revision of this content exists.</p>
        </div>
        <div id="revisionDate"></div>
        <div id="revisionStatus"></div>
    </div>

    <div class="column">

        <div class="field">
            <button id="history_button" class="button tooltip is-hidden" data-tooltip="Show revision history" onclick="$(this).addClass('is-hidden'); $('#revisions_list').removeClass('is-hidden'); return false">
                <span class="is-pulled-left">History</span><span class="icon is-pulled-right"><i class="fas fa-history"></i></span>
            </button>
        </div>

        <div id="revisions_list" class="field is-hidden">
            <div class="select">
                <select name="revisionList" id="revisionList"></select>
            </div>
        </div>

        <button class="button is-primary tooltip" id="save" data-tooltip="Save your work, without publishing, for this current revision">
            <span class="icon is-pulled-left"><i class="fas fa-save"></i></span><span class="is-pulled-right">Save Draft</span>
        </button> &nbsp;
        <button class="button is-primary is-outlined tooltip is-hidden" id="saveAsNewDraft" data-tooltip="Save as a new revision, creating restore point for previous draft">
            <span class="icon is-pulled-left"><i class="far fa-save"></i></span><span class="is-pulled-right">Save as New Draft</span>
        </button> &nbsp;
        <button class="button is-primary is-outlined tooltip" id="publish" data-tooltip="Publishing will make this revison the current, live, version">
            <span class="icon is-pulled-left"><i class="fas fa-rocket"></i></span><span class="is-pulled-right">Save &amp; Publish</span>
        </button>


    </div>
</div>
<!-- start of widget editor code -->

<section class="section" style="padding-top: 2em; border-top: 1px lightgray solid;">
    <div class="columns">
        <div class="column">

            <div class="field">
                <label class="label" for="article_headline">Headline</label>
                <div class="control">
                    <input type="text" class="input lengthcount" maxlength="50" id="article_headline" name="article_headline" value="<?php echo (isset($content_array['headline'])) ? $content_array['headline'] : '' ?>">
                </div>
            </div>

            <div class="field" id="dateline_container">
                <label class="label" for="article_dateline">Dateline
                    <span class="icon tooltip is-tooltip-right has-text-info" data-tooltip="Optional Subheading"><i class="fas fa-info-circle"></i></span>
                </label>
                <div class="control">
                    <input type="text" class="input lengthcount" placeholder="example: <?php echo date("d F Y"); ?> &mdash; Windsor, CT" maxlength="50" id="article_dateline" name="article_dateline" value="<?php echo (isset($content_array['dateline'])) ? $content_array['dateline'] : '' ?>">
                </div>
            </div>

            <div class="field">
                <label class="label" for="article_content">Content</label>
                <div class="control">
                    <textarea class="textarea useMarkdown" id="article_content" name="article_content" style="height: 250px"><?php echo (isset($content_array['content'])) ? $content_array['content'] : '' ?></textarea>
                </div>
                <p class="help">Formatting allowed using basic <a href="https://www.markdownguide.org/basic-syntax/" target="_blank">Markdown</a></p>
            </div>

            <div class="box" id="image_container">
                <p class="is-size-5 collapseTrigger" data-collapse-target="collapseImage">Article Image</p>
                <div id="collapseImage" class="is-collapsible collapsed">

                    <div class=" field">
                        <label class="label" for="article_image_src">Image Path:</label>

                        <div class="field has-addons">
                            <div class="control" style="width: 100%">
                                <input class="input" id="article_image_src" name="article_image_src" placeholder="example: https://<?php echo $_ENV['config']['domain'] ?>/applications/images/your-file.png" value="<?php echo (isset($content_array['image']['path'])) ? $content_array['image']['path'] : '' ?>">
                            </div>
                            <div class="control">
                                <button id="article_image_picker" class="button is-info has-text-weight-semibold article_image_picker">
                                    <span class="icon"><i class="fa fa-image"></i></span>&nbsp;
                                    Select Image
                                </button>
                            </div>
                        </div>

                        <p class="help">Must be fully qualified URL starting with <em>https://</em></p>
                    </div>

                    <div class="field">
                        <label class="label" for="article_image_altText">Accessibility Description
                            <span class="icon tooltip is-tooltip-right has-text-info" data-tooltip="\" Alt\" tags are descriptive texts viewed by a screen reader"><i class="fas fa-info-circle"></i></span>
                        </label>
                        <div class="control">
                            <input type="text" class="input" id="article_image_altText" name="article_image_altText" value="<?php echo (isset($content_array['image']['alt'])) ? $content_array['image']['alt'] : '' ?>">
                            <p class="help">example: "CEO Scott Luce Portrait"</p>
                        </div>
                    </div>
                </div>

            </div>

            <div class="box" id="link_container">
                <p class="is-size-5 collapseTrigger" data-collapse-target="collapseLink">Article Link</p>
                <div id="collapseLink" class="is-collapsible collapsed">
                    <div class="field">
                        <label class="label" for="article_link_url">Link URL:</label>
                        <input class="input" id="article_link_url" name="article_link_url" placeholder="example: https://<?php echo $_ENV['config']['domain'] ?>/applications/images/your-file.png" value="<?php echo (isset($content_array['link']['url'])) ? $content_array['link']['url'] : '' ?>">
                        <p class="help">Internal links to this site can start with a forward-slash, eg "<em>/human-resources/forms</em>."<br>External links MUST begin with <em>http</em></p>
                    </div>
                    <div class="field">
                        <label class="label" for="article_link_label">Label
                            <span class="icon tooltip is-tooltip-right has-text-info" data-tooltip="User friendly descriptive text"><i class="fas fa-info-circle"></i></span>
                        </label>
                        <input class="input" id="article_link_label" name="article_link_label" value=" <?php echo (isset($content_array['link']['label'])) ? $content_array['link']['label'] : '' ?>">
                        <p class="help">example: <em>"Learn More"</em> or <em>"View the Full Story"</em></p>
                    </div>
                    <div class="field is-hidden">
                        <label class="label" for="">Button Style Class</label>
                        <div class="control note-editing-area">
                            <input type="text" id="article_link_buttonClass" value="magenta">
                            <br><br>
                            <button id="selectButtonMagenta" class="button magenta" onclick="$('#article_link_buttonClass').val('magenta'); return false">Magenta</button>
                            <button id="selectButtonBlue" class="button blue" onclick="$('#article_link_buttonClass').val('blue'); return false">SCA Blue</button>
                            <button id="selectButtonYellow" class="button yellow" onclick="$('#article_link_buttonClass').val('yellow'); return false">Yellow</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="column">

            <div class="columns">

                <div class="column">
                    <div class="field">
                        <label for="article_template">Select Template</label>
                        <div class="control">
                            <select class="select" style="width: 100%; font-size: 1rem; margin-top: 1px" id="article_template" name="article_template">
                                <option value="default">Default</option>
                                <option value="profile">Profile</option>
                                <option value="cta">Call to Action</option>
                                <option value="highlight">Quick Highlight</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="column">
                    <label for="article_display_date">Release Date
                        <span class="icon tooltip is-tooltip-right has-text-info" data-tooltip="Optional. Article will not appear until the set future date.">
                            <i class="fas fa-info-circle"></i>
                        </span>
                    </label>
                    <input class="input" id="article_display_date">
                    <span class="help">Format: YYYY-MM-DD HH:MM:SS</help>
                </div>
                <div class="column">
                    <label for="article_end_date">Archive Date
                        <span class="icon tooltip is-tooltip-right has-text-info" data-tooltip="Optional. A date in the past will remove this article from the feed.">
                            <i class="fas fa-info-circle"></i>
                        </span>
                    </label>
                    <input class="input" id="article_end_date">
                    <span class="help">Format: YYYY-MM-DD HH:MM:SS</help>
                </div>
            </div>

            <div class="note-editing-area content">

                <div id="preview_template_default" class="template">
                    <div class="help">Default Template &mdash; General Posts. Dateline, Image, and Link are optional.</div>
                    <div class="announcement card content">
                        <div class="card-content">
                            <h2 class="preview_headline"><?php echo (isset($content_array['headline'])) ? $content_array['headline'] : '' ?></h2>
                            <span class="preview_dateline is-hidden" style="font-weight: bold"><?php echo (isset($content_array['dateline'])) ? $content_array['dateline'] :  '' ?></span>
                            <p class="preview_content"><?php echo (isset($content_array['content'])) ? $content_array['content'] : '' ?></p>
                            <a class="preview_link is-hidden"><span class="preview_link_label"></span></a>

                            <img class="preview_image is-hidden" />
                        </div>
                    </div>
                </div>

                <div id="preview_template_profile" class="template is-hidden">
                    <div class="help">Profile Template &mdash; Image is smaller and within the body, no Dateline and Link is optional. </div>
                    <div class="announcement card content">
                        <div class="card-content">
                            <h2 class="preview_headline"><?php echo (isset($content_array['headline'])) ? $content_array['headline'] : '' ?></h2>
                            <img class="preview_image is-hidden" style="float: right; padding: 0 0 10px 10px; max-width: 45%; width:100%; border-radius: 0 20%">
                            <p class="preview_content"><?php echo (isset($content_array['content'])) ? $content_array['content'] : '' ?></p>
                            <a class="preview_link is-hidden"><span class="preview_link_label"></span></a>
                        </div>
                    </div>
                </div>

                <div id="preview_template_cta" class="template is-hidden">
                    <div class="help">CTA Template &mdash; Bold Header, Link becomes a button. Dateline and Image optional.</div>
                    <div class="announcement card content">
                        <div class="card-header bg-navy">
                            <h2 class="preview_headline"><?php echo (isset($content_array['headline'])) ? $content_array['headline'] : '' ?></h2>
                        </div>
                        <div class="card-content">
                            <span class="preview_dateline is-hidden" style="font-weight: bold"><?php echo (isset($content_array['dateline'])) ? $content_array['dateline'] :  '' ?></span>
                            <p class="preview_content"><?php echo (isset($content_array['content'])) ? $content_array['content'] : '' ?></p>
                            <a class="preview_link is-hidden button magenta"><span class="preview_link_label"></span></a>
                            <img class="preview_image is-hidden" />
                        </div>
                    </div>
                </div>

                <div id="preview_template_highlight" class="template is-hidden">
                    <div class="help">Quick Highlight Template &mdash; Eye catching Header, no Dateline or Link and Image is optional.</div>
                    <div class="announcement card content">
                        <div class="card-header bg-magenta">
                            <h2 class="preview_headline"><?php echo (isset($content_array['headline'])) ? $content_array['headline'] : '' ?></h2>
                        </div>
                        <div class="card-content">
                            <p class="preview_content"><?php echo (isset($content_array['content'])) ? $content_array['content'] : '' ?></p>
                            <img class="preview_image is-hidden" />
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

</section>



<script src="/node_modules/showdown/dist/showdown.js"></script>
<!--note: edit.js is compiled from ./edit.ts -->
<script src="/humblee/js/admin/contentWidgets/feed/edit.js" type="module"></script>

<style type="text/css">
    /* additional inline css specific to this widget */
    .card {
        min-height: 300px;
        max-height: 750px;
        overflow-y: auto;
        overflow-x: hidden;
        width: 100%;
        max-width: 486px;
        margin: 20px auto;
    }

    .card .card-content {
        width: 100%;
        max-width: 486px;
    }

    .preview_link {
        display: inline-block;
        margin-bottom: 20px;
    }

    .preview_link:not(.button) {
        font-size: 1.2em;
        font-weight: normal;
    }

    .preview_link:not(.button)::after {
        font-size: .9rem;
        font-weight: normal;
    }
</style>