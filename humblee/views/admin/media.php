<div class="columns">
    <div class="column is-one-fifth">
        <p class="is-size-5">Folders</p>
        <hr>
        <div id="folders">Loading</div>
    </div>
    
    <div class="column" id="files">
        <div class="level">
            <div class="level-left">
                <p class="is-size-5" id="folder_name">Select a folder to view it's contents</p>    
            </div>
            <div class="level-right is-invisible">
                <button class="button">Add Subfolder</button>
                &nbsp;
                <button class="button is-primary uploadButton">Upload File(s)</button>
            </div>
        </div>
        
        <table class="table is-invisible is-fullwidth is-striped is-hoverable">
            <thead>
                <th>Filename</th>
                <th>Type</th>
                <th>Date</th>
            </thead>
            <tbody>
                
            </tbody>
        </table>
    </div>
    
    <div class="column is-one-quarter" id="file">
        <aside id="fileProperties" class="is-invisible">
            <p class="is-size-5">File Properties</p>
            <hr>
            <div class="card">
                
                <div class="card-content">
                    <p class="is-size-5 editable-text" id="file_name"></p>
                    <p>Size: <span id="filesize"></span></p>
                    <p>File Type: <span id="filetype"></span></p>
                    <p>Author: <span id="uploadby"></span></p>
                    <p>Date: <span id="uploaddate"></span></p>
                    <div class="field">
                        <label class="label" for="required_role">Privacy:</label> 
                        <div class="control">
                            <div class="select">
                                <select  id="required_role" name="required_role">
                                    <option value="0" >Public Access (No Login)</option>
                                    <?php foreach($access_roles as $access_role) : ?>
                            		<option value="<?php echo $access_role->id ?>" ><?php echo $access_role->name ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-image">
                    <figure id="file_image" class="image is-4x3">
                        <img>
                    </figure>
                </div>
            </div>    
        </aside>
    </div>

</div>

<div id="uploaderModal" class="modal">
    <div class="modal-background"></div>
    <div class="modal-card">
        <header class="modal-card-head">
            <p class="modal-card-title">Upload Files</p>
            <button class="delete" aria-label="close"></button>
        </header>
        <section class="modal-card-body">
            <div class="dropZone is-size-3 has-text-weight-semibold">
                Drag &amp; Drop
            </div>
            <form class="file">
                <label class="file-label">
                    <input type="hidden" name="folder_id" id="folder_id">
                    <input class="file-input" type="file" name="uploaderFiles[]" multiple>
                    <span class="file-cta">
                        <span class="file-icon">
                            <i class="fas fa-upload"></i>
                        </span>
                        <span class="file-label">
                        Choose files…
                        </span>
                    </span>
                </label>
            </form>
        </section>

    </div>
<!--    
    <div class="modal-content">
        <div class="box">
            <div class="box__input">
                <input class="box__file" type="file" name="files[]" id="uploadFile" data-multiple-caption="{count} files selected" multiple />
                <label for="uploadFile"><strong>Choose a file</strong><span class="box__dragndrop"> or drag it here</span>.</label>
                <button class="button box__button" type="submit">Upload</button>
            </div>
            <div class="box__uploading">Uploading&hellip;</div>
        </div>            
    </div>
    <button class="modal-close is-large" aria-label="close"></button>
-->
</div>