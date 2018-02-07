<input type="hidden" id="mediaAccess" value="<?php echo ($hasMediaRole) ? 'media' : 'readonly' ?>">
<div class="columns">
    <div class="column is-one-fifth">
        <p class="is-size-5">Folders</p>
        <hr>
        <div id="folders">Loading</div>
        <?php
        if($hasMediaRole)
        {
        ?>
        <hr>
        <button class="button tooltip addFolder" data-folderparent="0" data-tooltip="Add top level folder">Add Folder</button>
        <?php
        }
        ?>
    </div>
    
    <div class="column" id="files">
        <div class="level">
            <div class="level-left">
                <p class="is-size-5" id="folder_name">Select a folder to view it's contents</p>    
            </div>
            <div class="level-right is-invisible">
            <?php
            if($hasMediaRole)
            {
            ?>
                <button class="button addFolder">Add Subfolder</button>
                &nbsp;
                <button class="button is-info uploadButton">
                    <span class="icon is-pulled-left"><i class="fas fa-upload"></i></span>
                    <span class="is-pulled-right">Upload Files</span>
                </button>
            <?php
            }
            ?>
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
        
        <div class="folderFooter is-invisible">
        <?php
        if($hasMediaRole)
        {
        ?>
            <hr>
            <button class="button deletefolder is-small has-text-grey-light tooltip is-tooltip-right" data-tooltip="Delete this folder and its contents"><span class="icon"><i class="fas fa-trash"></i></span><span class="is-pulled-right">Delete Folder</span></button>             
        <?php
        }
        ?>
        </div>
        
    </div>
    
    <div class="column is-one-quarter" id="file">
        <aside id="fileProperties" class="is-invisible">
            <p class="is-size-5">File Properties</p>
            <hr>
            <div class="card">
                <div class="card-content">
                    <p class="is-size-5 editable-text" id="file_name" data-fileID=""></p>
                <?php
                if($is_in_iframe){
                ?>
                    <button class="button is-success has-text-weight-semibold" id="selectFile">
                        <span class="icon is-pulled-left"><i class="fa fa-check-circle"></i></span>
                        <span class="is-pulled-right">Select this file</span>
                    </button>
                <?php
                }
                ?>
                    <p>Size: <span id="filesize"></span></p>
                    <p>File Type: <span id="filetype"></span></p>
                    <p>Author: <span id="uploadby"></span></p>
                    <p>Date: <span id="uploaddate"></span></p>
                    <div class="field">
                        <label class="label tooltip" for="required_role" data-tooltip="Require user to be logged in to view this file">
                            Access Role:
                            <span class="icon"><i class="fas fa-lock"></i></span>
                        </label> 
                        <div class="control">
                            <div class="select">
                                <select  id="required_role" name="required_role" <?php echo (!$hasMediaRole) ? 'disabled' : '' ?>>
                                    <option value="0" >Public Access (No Login)</option>
                                    <?php foreach($access_roles as $access_role) : ?>
                            		<option value="<?php echo $access_role->id ?>" ><?php echo ucfirst($access_role->name) ?></option>
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
                <footer class="card-footer">
                    <p class="card-footer-item">
                        <a id="fileLink" class="button is-link is-small tooltip" href="" data-tooltip="Open file in browser tab" target="_blank">
                            <span class="is-pulled-left">Open</span>
                            <span class="icon"><i class="fas fa-external-link-alt"></i></span>
                        </a>                        
                    </p>
                    <p class="card-footer-item">
                        <button id="fileLinkCopy" class="button is-info is-small tooltip" data-tooltip="Copy link to clipboard">
                            <span class="is-pulled-left">Copy Link</span>
                            <span class="icon"><i class="fas fa-copy"></i></span>
                        </button>                        
                    </p>
                    <p class="card-footer-item">
                    <?php
                    if($hasMediaRole)
                    {
                    ?>
                        <button class="button deletebutton is-danger is-small is-inverted"><span class="icon"><i class="fas fa-trash"></i></span><span class="is-pulled-right">Delete</span></button>
                    <?php
                    }
                    ?>
                    </p>
                </footer>
                
            </div>    
        </aside>
    </div>

</div>

<?php
if($hasMediaRole)
{
?>
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
            <form id="uploaderForm" class="file" enctype="multipart/form-data">
                <div class="columns">
                    <div class="column is-half">
                        <label class="file-label">
                            <input type="hidden" name="folder_id" id="folder_id">
                            <input class="file-input" id="uploaderFiles" type="file" name="uploaderFiles[]" multiple="multiple">
                            <span class="file-cta">
                                <span class="file-icon">
                                    <i class="fas fa-upload"></i>
                                </span>
                                <span class="file-label">
                                Choose files…
                                </span>
                            </span>
                        </label>       
                    </div>
                    <div class="column is-half">
                        <label class="checkbox is-pulled-right">
                            Use TinyPNG Compression
                            <input name="useCompression" value="1" type="checkbox" CHECKED>
                        </label>   
                    </div>
                </div>
            </form>
        </section>
        
        
        
    </div>
</div>
<?php
}
?>

<?php
if($is_in_iframe){
?>
<script>
    setEscEvent('mediaManager',function () { parent.closeMediamanager(); parent.unsetEscEvent('mediaManager') });
</script>
<?php 
}
?>