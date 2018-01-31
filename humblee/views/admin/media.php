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
                <button class="button is-primary">Upload File(s)</button>
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