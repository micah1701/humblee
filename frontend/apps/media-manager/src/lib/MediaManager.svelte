<script lang="ts">
  import { onMount } from 'svelte';
  import type {
    MediaFolder,
    MediaFoldersResponse,
    MediaFile,
    FolderCache,
    AccessRole
  } from './types/media';
  import { createMediaApi } from './services/mediaApi';
  import {
    friendlyFilesize,
    dateFormat,
    quickNotice,
    confirmation
  } from './utils/mediaUtils';

  // Props
  export let hasMediaRole: boolean = true;
  export let isInIframe: boolean = false;
  export let accessRoles: AccessRole[] = [
    { id: 1, name: 'user' },
    { id: 2, name: 'admin' }
  ];
  export let appPath: string = '/';
  export let XHR_PATH: string;
  export let WEB_ROOT: string;

  const mediaApi = createMediaApi(XHR_PATH);

  // State
  let foldersData: MediaFoldersResponse = {};
  let folderCache: FolderCache = {};
  let currentFolderId: number | null = null;
  let currentFolderName: string = "Select a folder to view it's contents";
  let filesData: { [fileId: string]: MediaFile } = {};
  let selectedFile: MediaFile | null = null;
  let showUploadModal: boolean = false;
  let uploading: boolean = false;
  let uploadMessage: string = 'Uploading…';
  let selectedFileId: number | null = null;
  let editingFolderName: boolean = false;
  let editingFileName: boolean = false;
  let editValue: string = '';

  // Drag and drop state
  let isDragover: boolean = false;
  let fileInput: HTMLInputElement;

  // Sorting state
  type SortColumn = 'name' | 'type' | 'upload_date';
  type SortDirection = 'asc' | 'desc';
  let sortColumn: SortColumn = 'upload_date';
  let sortDirection: SortDirection = 'desc';

  $: filesArray = Object.values(filesData).sort((a, b) => {
    let aValue: string | number;
    let bValue: string | number;

    if (sortColumn === 'upload_date') {
      aValue = new Date(a[sortColumn]).getTime();
      bValue = new Date(b[sortColumn]).getTime();
    } else {
      aValue = a[sortColumn].toLowerCase();
      bValue = b[sortColumn].toLowerCase();
    }

    if (aValue < bValue) {
      return sortDirection === 'asc' ? -1 : 1;
    }
    if (aValue > bValue) {
      return sortDirection === 'asc' ? 1 : -1;
    }
    return 0;
  });

  function handleModalEscape(e: KeyboardEvent) {
    if (e.key === 'Escape' && showUploadModal) {
      closeUploadModal();
    }
  }

  function sortBy(column: SortColumn) {
    if (sortColumn === column) {
      sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
      sortColumn = column;
      sortDirection = column === 'upload_date' ? 'desc' : 'asc';
    }
  }

  $: {
    if (showUploadModal) {
      window.addEventListener('keydown', handleModalEscape);
    } else {
      window.removeEventListener('keydown', handleModalEscape);
    }
  }

  onMount(() => {
    loadFolders(true);

    return () => {
      window.removeEventListener('keydown', handleModalEscape);
    };
  });

  async function loadFolders(openFirstFolder: boolean = false) {
    try {
      foldersData = await mediaApi.listMediaFolders();
      if (openFirstFolder) {
        // Open first folder after folders are loaded
        setTimeout(() => {
          const firstFolder = document.querySelector<HTMLAnchorElement>('#folders ul li a');
          if (firstFolder) {
            const folderId = parseInt(firstFolder.dataset.id || '0');
            selectFolder(folderId, firstFolder.textContent || '');
          }
        }, 100);
      }
    } catch (error) {
      console.error('Error loading folders:', error);
      quickNotice('Could not load folders', 'is-danger');
    }
  }

  function generateMenu(data: MediaFoldersResponse, parentId: number): MediaFolder[] {
    const key = parentId.toString();
    return data[key] || [];
  }

  function toggleFolder(event: MouseEvent, hasChildren: boolean) {
    if (!hasChildren) return;

    const target = event.currentTarget as HTMLElement;
    const ul = target.nextElementSibling as HTMLElement;

    if (ul && ul.tagName === 'UL') {
      if (ul.classList.contains('is-closed')) {
        ul.classList.remove('is-closed');
        target.classList.remove('has-closed');
      } else {
        ul.classList.add('is-closed');
        target.classList.add('has-closed');
      }
    }

    event.stopPropagation();
  }

  async function selectFolder(folderId: number, folderName: string) {
    currentFolderId = folderId;
    currentFolderName = folderName;
    selectedFile = null;
    selectedFileId = null;
    await loadFiles(folderId);
  }

  async function loadFiles(folderId: number, updateCache: boolean = false) {
    const folderKey = `folder_${folderId}`;

    if (folderCache[folderKey] && !updateCache) {
      filesData = { ...folderCache[folderKey] };
      return;
    }

    try {
      const response = await mediaApi.listMediaFilesByFolder(folderId);
      if (response.success && response.files) {
        folderCache[folderKey] = response.files;
        filesData = { ...response.files };
      } else {
        filesData = {};
      }
    } catch (error) {
      console.error('Error loading files:', error);
      quickNotice('Could not load files', 'is-danger');
      filesData = {};
    }
  }

  function selectFile(fileId: number) {
    selectedFileId = fileId;
    if (currentFolderId !== null) {
      const folderKey = `folder_${currentFolderId}`;
      selectedFile = folderCache[folderKey]?.[fileId] || null;
    }
  }

  async function addFolder(parentId: number) {
    const parent = parentId === 0 ? 0 : currentFolderId || 0;
    try {
      const response = await mediaApi.createMediaFolder(parent);
      if (response.success) {
        quickNotice('Folder Created');
        await loadFolders(false);
      }
    } catch (error) {
      console.error('Error creating folder:', error);
      quickNotice('Could not create folder', 'is-danger');
    }
  }

  async function deleteFolder() {
    if (!currentFolderId) return;

    confirmation(
      '<strong>You are about to <span class="has-text-danger">PERMANENTLY DELETE</span> this ENTIRE FOLDER</strong><br><p>ALL of the files in this folder will be removed. This may have an adverse effect on any pages that include these files.</p>',
      async () => {
        try {
          const response = await mediaApi.deleteMediaFolder(currentFolderId!);
          if (response.success) {
            quickNotice('Folder Deleted');
            await loadFolders(true);
          } else if (response.errors) {
            quickNotice(response.errors, 'is-danger', 5000);
          } else {
            quickNotice('Could not delete folder', 'is-danger');
          }
        } catch (error) {
          console.error('Error deleting folder:', error);
          quickNotice('Could not delete folder', 'is-danger');
        }
      },
      () => {}
    );
  }

  async function deleteFile() {
    if (!selectedFile) return;

    confirmation(
      '<strong>You are about to <span class="has-text-danger">PERMANENTLY DELETE</span> this file.</strong><br><p>This may have an adverse effect on any pages that include the file.</p>',
      async () => {
        try {
          const response = await mediaApi.deleteMediaFile(selectedFile!.id);
          if (response.success) {
            quickNotice('File Deleted');
            const folderKey = `folder_${currentFolderId}`;
            delete folderCache[folderKey][selectedFile!.id];
            await loadFiles(currentFolderId!);
            selectedFile = null;
            selectedFileId = null;
          } else {
            quickNotice('Could not delete file', 'is-warning');
          }
        } catch (error) {
          console.error('Error deleting file:', error);
          quickNotice('Could not delete file', 'is-danger');
        }
      },
      () => {}
    );
  }

  function startEditFolderName() {
    if (!hasMediaRole) return;
    editingFolderName = true;
    editValue = currentFolderName;
  }

  function startEditFileName() {
    if (!hasMediaRole) return;
    editingFileName = true;
    editValue = selectedFile?.name || '';
  }

  async function saveEditFolderName() {
    if (!currentFolderId || editValue === currentFolderName) {
      editingFolderName = false;
      return;
    }

    try {
      const response = await mediaApi.updateMediaName('folder_name', currentFolderId, editValue);
      if (response.success) {
        currentFolderName = editValue;
        quickNotice('Folder name updated!');
        await loadFolders(false);
      } else {
        quickNotice('Could not save changes', 'is-danger');
      }
    } catch (error) {
      console.error('Error updating folder name:', error);
      quickNotice('Could not save changes', 'is-danger');
    }
    editingFolderName = false;
  }

  async function saveEditFileName() {
    if (!selectedFile || editValue === selectedFile.name) {
      editingFileName = false;
      return;
    }

    try {
      const response = await mediaApi.updateMediaName('file_name', selectedFile.id, editValue);
      if (response.success) {
        selectedFile.name = editValue;
        const folderKey = `folder_${currentFolderId}`;
        if (folderCache[folderKey] && folderCache[folderKey][selectedFile.id]) {
          folderCache[folderKey][selectedFile.id].name = editValue;
        }
        quickNotice('File name updated!');
        filesData = { ...filesData };
      } else {
        quickNotice('Could not save changes', 'is-danger');
      }
    } catch (error) {
      console.error('Error updating file name:', error);
      quickNotice('Could not save changes', 'is-danger');
    }
    editingFileName = false;
  }

  async function handleRoleChange(event: Event) {
    if (!selectedFile) return;

    const target = event.target as HTMLSelectElement;
    const roleId = parseInt(target.value);

    try {
      const response = await mediaApi.updateMediaRole(selectedFile.id, roleId);
      if (response.success) {
        selectedFile.required_role = roleId;
        quickNotice('Access role updated');
      } else {
        quickNotice('Could not save access role', 'is-danger');
      }
    } catch (error) {
      console.error('Error updating role:', error);
      quickNotice('Could not save access role', 'is-danger');
    }
  }

  async function handleEncryptChange(event: Event) {
    if (!selectedFile) return;

    const target = event.target as HTMLInputElement;
    const action = target.checked ? 'encrypt' : 'decrypt';

    try {
      const response = await mediaApi.encryptMedia(selectedFile.id, action);
      if (response.success) {
        selectedFile.encrypted = target.checked ? 1 : 0;
        quickNotice(`File has been ${action}ed`);
      } else {
        quickNotice('Could not change encryption status', 'is-danger');
        target.checked = !target.checked;
      }
    } catch (error) {
      console.error('Error changing encryption:', error);
      quickNotice('Could not change encryption status', 'is-danger');
      target.checked = !target.checked;
    }
  }

  function copyFileLink() {
    if (!selectedFile) return;

    navigator.clipboard.writeText(selectedFile.url).then(() => {
      quickNotice('Copied to clipboard', 'is-info', 1750);
    }).catch(() => {
      quickNotice('Could not copy to clipboard', 'is-danger');
    });
  }

  function selectFileForParent() {
    if (!selectedFile || typeof window === 'undefined') return;

    (window.parent as any).closeMediamanager?.();
    (window.parent as any).unsetEscEvent?.('mediaManager');
    (window.parent as any).handleMediaManagerSelect?.(selectedFile);
  }

  function openUploadModal() {
    showUploadModal = true;
  }

  function closeUploadModal() {
    showUploadModal = false;
    isDragover = false;
    uploading = false;
    if (fileInput) {
      fileInput.value = '';
    }
  }

  function handleDragOver(event: DragEvent) {
    event.preventDefault();
    event.stopPropagation();
    isDragover = true;
  }

  function handleDragLeave(event: DragEvent) {
    event.preventDefault();
    event.stopPropagation();
    isDragover = false;
  }

  function handleDrop(event: DragEvent) {
    event.preventDefault();
    event.stopPropagation();
    isDragover = false;

    if (event.dataTransfer?.files) {
      uploadFiles(event.dataTransfer.files);
    }
  }

  function handleFileSelect(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files) {
      uploadFiles(target.files);
    }
  }

  async function uploadFiles(files: FileList) {
    if (uploading || !currentFolderId) return;

    uploading = true;
    uploadMessage = 'Uploading…';

    const formData = new FormData();
    formData.append('folder_id', currentFolderId.toString());

    for (let i = 0; i < files.length; i++) {
      formData.append('uploaderFiles[]', files[i]);
    }

    // Add compression option if checkbox exists
    const compressionCheckbox = document.querySelector<HTMLInputElement>('input[name="useCompression"]');
    if (compressionCheckbox?.checked) {
      formData.append('useCompression', '1');
    }

    try {
      uploadMessage = 'Processing…';
      const response = await mediaApi.uploadMediaFiles(formData);

      if (response.success && response.errors.length === 0) {
        quickNotice('Upload Complete', 'is-success');
        await loadFiles(currentFolderId, true);
        closeUploadModal();
      } else if (response.success && response.errors.length > 0) {
        quickNotice('Some files were not saved.', 'is-warning');
        await loadFiles(currentFolderId, true);
        closeUploadModal();
      } else {
        quickNotice('Upload Failed', 'is-danger');
        uploading = false;
      }
    } catch (error) {
      console.error('Error uploading files:', error);
      quickNotice('Upload Failed', 'is-danger');
      uploading = false;
    }
  }

  function hasChildren(parentId: number): boolean {
    return generateMenu(foldersData, parentId).length > 0;
  }

  $: showEncryptField = selectedFile && (selectedFile.encrypted === 1 || selectedFile.required_role !== 0);
</script>

<div class="columns">
  <div class="column is-one-fifth">
    <p class="is-size-5">Folders</p>
    <hr />
    <div id="folders">
      {#if Object.keys(foldersData).length > 0}
        <ul class="menu-list">
          {#each generateMenu(foldersData, 0) as folder}
            <li>
              <button
                type="button"
                data-id={folder.id}
                class:is-active={currentFolderId === folder.id}
                class:menu-has-children={hasChildren(folder.id)}
                class:has-closed={hasChildren(folder.id)}
                on:click={(e) => {
                  if (hasChildren(folder.id)) {
                    toggleFolder(e, true);
                  }
                  selectFolder(folder.id, folder.name);
                }}
              >
                {folder.name}
              </button>
              {#if hasChildren(folder.id)}
                <ul class="menu-list is-closed">
                  {#each generateMenu(foldersData, folder.id) as childFolder}
                    <li>
                      <button
                        type="button"
                        data-id={childFolder.id}
                        class:is-active={currentFolderId === childFolder.id}
                        class:menu-has-children={hasChildren(childFolder.id)}
                        class:has-closed={hasChildren(childFolder.id)}
                        on:click={(e) => {
                          if (hasChildren(childFolder.id)) {
                            toggleFolder(e, true);
                          }
                          selectFolder(childFolder.id, childFolder.name);
                        }}
                      >
                        {childFolder.name}
                      </button>
                      {#if hasChildren(childFolder.id)}
                        <ul class="menu-list is-closed">
                          {#each generateMenu(foldersData, childFolder.id) as grandchildFolder}
                            <li>
                              <button
                                type="button"
                                data-id={grandchildFolder.id}
                                class:is-active={currentFolderId === grandchildFolder.id}
                                on:click={() => {
                                  selectFolder(grandchildFolder.id, grandchildFolder.name);
                                }}
                              >
                                {grandchildFolder.name}
                              </button>
                            </li>
                          {/each}
                        </ul>
                      {/if}
                    </li>
                  {/each}
                </ul>
              {/if}
            </li>
          {/each}
        </ul>
      {:else}
        Loading
      {/if}
    </div>
    {#if hasMediaRole}
      <hr />
      <button
        class="button tooltip addFolder"
        data-tooltip="Add top level folder"
        on:click={() => addFolder(0)}
      >
        Add Folder
      </button>
    {/if}
  </div>

  <div class="column" id="files">
    <div class="level">
      <div class="level-left">
        {#if editingFolderName}
          <input
            type="text"
            class="input"
            bind:value={editValue}
            on:blur={saveEditFolderName}
            on:keydown={(e) => e.key === 'Enter' && saveEditFolderName()}
            autofocus
          />
        {:else}
          <button
            type="button"
            class="is-size-5 editable-text"
            class:clickable={hasMediaRole}
            on:click={startEditFolderName}
            disabled={!hasMediaRole}
          >
            {currentFolderName}
          </button>
        {/if}
      </div>
      <div class="level-right" class:is-invisible={currentFolderId === null}>
        {#if hasMediaRole}
          <button class="button addFolder" on:click={() => addFolder(currentFolderId || 0)}>
            Add Subfolder
          </button>
          &nbsp;
          <button class="button is-info uploadButton" on:click={openUploadModal}>
            <span class="icon is-pulled-left"><i class="fas fa-upload"></i></span>
            <span class="is-pulled-right">Upload Files</span>
          </button>
        {/if}
      </div>
    </div>

    <table
      class="table is-fullwidth is-striped is-hoverable"
      class:is-invisible={currentFolderId === null}
    >
      <thead>
        <tr>
          <th
            role="columnheader"
            aria-sort={sortColumn === 'name' ? (sortDirection === 'asc' ? 'ascending' : 'descending') : 'none'}
          >
            <button
              class="sortButton"
              on:click={() => sortBy('name')}
              aria-label="Sort by filename"
            >
              Filename
              {#if sortColumn === 'name'}
                <span class="icon is-small" aria-hidden="true">
                  <i class="fas fa-chevron-{sortDirection === 'asc' ? 'up' : 'down'}"></i>
                </span>
              {/if}
            </button>
          </th>
          <th
            role="columnheader"
            aria-sort={sortColumn === 'type' ? (sortDirection === 'asc' ? 'ascending' : 'descending') : 'none'}
          >
            <button
              class="sortButton"
              on:click={() => sortBy('type')}
              aria-label="Sort by type"
            >
              Type
              {#if sortColumn === 'type'}
                <span class="icon is-small" aria-hidden="true">
                  <i class="fas fa-chevron-{sortDirection === 'asc' ? 'up' : 'down'}"></i>
                </span>
              {/if}
            </button>
          </th>
          <th
            role="columnheader"
            aria-sort={sortColumn === 'upload_date' ? (sortDirection === 'asc' ? 'ascending' : 'descending') : 'none'}
          >
            <button
              class="sortButton"
              on:click={() => sortBy('upload_date')}
              aria-label="Sort by date"
            >
              Date
              {#if sortColumn === 'upload_date'}
                <span class="icon is-small" aria-hidden="true">
                  <i class="fas fa-chevron-{sortDirection === 'asc' ? 'up' : 'down'}"></i>
                </span>
              {/if}
            </button>
          </th>
        </tr>
      </thead>
      <tbody>
        {#if filesArray.length === 0}
          <tr>
            <td colspan="3">Folder is empty</td>
          </tr>
        {:else}
          {#each filesArray as file}
            <tr
              class:is-selected={selectedFileId === file.id}
              on:click={() => selectFile(file.id)}
            >
              <td>{file.name}</td>
              <td>{file.type}</td>
              <td>{dateFormat('m/d/Y', file.upload_date)}</td>
            </tr>
          {/each}
        {/if}
      </tbody>
    </table>

    <div class="folderFooter" class:is-invisible={currentFolderId === null}>
      {#if hasMediaRole}
        <hr />
        <button
          class="button deletefolder is-small has-text-grey-light tooltip is-tooltip-right"
          data-tooltip="Delete this folder and its contents"
          on:click={deleteFolder}
        >
          <span class="icon"><i class="fas fa-trash"></i></span>
          <span class="is-pulled-right">Delete Folder</span>
        </button>
      {/if}
    </div>
  </div>

  <div class="column is-one-quarter" id="file">
    <aside id="fileProperties" class:is-invisible={selectedFile === null}>
      {#if selectedFile}
        <p class="is-size-5">File Properties</p>
        <hr />
        <div class="card">
          <div class="card-content">
            {#if editingFileName}
              <input
                type="text"
                class="input"
                bind:value={editValue}
                on:blur={saveEditFileName}
                on:keydown={(e) => e.key === 'Enter' && saveEditFileName()}
                autofocus
              />
            {:else}
              <button
                type="button"
                class="is-size-5 editable-text"
                class:clickable={hasMediaRole}
                on:click={startEditFileName}
                disabled={!hasMediaRole}
              >
                {selectedFile.name}
              </button>
            {/if}

            {#if isInIframe}
              <button
                class="button is-success has-text-weight-semibold"
                id="selectFile"
                on:click={selectFileForParent}
              >
                <span class="icon is-pulled-left"><i class="fa fa-check-circle"></i></span>
                <span class="is-pulled-right">Select this file</span>
              </button>
            {/if}

            <p>Size: <span>{friendlyFilesize(selectedFile.size)}</span></p>
            <p>File Type: <span>{selectedFile.type}</span></p>
            <p>Author: <span>{selectedFile.uploadname}</span></p>
            <p>Date: <span>{dateFormat('F d, Y h:ia', selectedFile.upload_date)}</span></p>

            <div class="field">
              <label class="label tooltip" for="required_role" data-tooltip="Require user to be logged in to view this file">
                Access Role:
                <span class="icon"><i class="fas fa-lock"></i></span>
              </label>
              <div class="control">
                <div class="select">
                  <select
                    id="required_role"
                    name="required_role"
                    value={selectedFile.required_role}
                    disabled={!hasMediaRole}
                    on:change={handleRoleChange}
                  >
                    <option value={0}>Public Access (No Login)</option>
                    {#each accessRoles as role}
                      <option value={role.id}>{role.name.charAt(0).toUpperCase() + role.name.slice(1)}</option>
                    {/each}
                  </select>
                </div>
              </div>
            </div>

            <div class="field" id="encrypt_field" style:display={showEncryptField ? 'block' : 'none'}>
              <label class="checkbox tooltip has-tooltip-left" data-tooltip="Encrypt stored file at rest">
                <input
                  type="checkbox"
                  id="encrypt"
                  checked={selectedFile.encrypted === 1}
                  disabled={!hasMediaRole}
                  on:change={handleEncryptChange}
                />
                Encrypted
              </label>
            </div>
          </div>

          {#if selectedFile.type.startsWith('image')}
            <div class="card-image">
              <figure id="file_image" class="image is-4x3">
                <img src={`${WEB_ROOT}${appPath}media/${selectedFile.id}/${selectedFile.name}`} alt={selectedFile.name} />
              </figure>
            </div>
          {/if}

          <footer class="card-footer">
            <p class="card-footer-item">
              <a
                id="fileLink"
                class="button is-link is-small tooltip"
                href={WEB_ROOT}{selectedFile.url}
                data-tooltip="Open file in browser tab"
                target="_blank"
              >
                <span class="is-pulled-left">Open</span>
                <span class="icon"><i class="fas fa-external-link-alt"></i></span>
              </a>
            </p>
            <p class="card-footer-item">
              <button
                id="fileLinkCopy"
                class="button is-info is-small tooltip"
                data-tooltip="Copy link to clipboard"
                on:click={copyFileLink}
              >
                <span class="is-pulled-left">Copy Link</span>
                <span class="icon"><i class="fas fa-copy"></i></span>
              </button>
            </p>
            <p class="card-footer-item">
              {#if hasMediaRole}
                <button
                  class="button deletebutton is-danger is-small is-inverted"
                  on:click={deleteFile}
                >
                  <span class="icon"><i class="fas fa-trash"></i></span>
                  <span class="is-pulled-right">Delete</span>
                </button>
              {/if}
            </p>
          </footer>
        </div>
      {/if}
    </aside>
  </div>
</div>

{#if hasMediaRole}
  <div
    class="modal"
    class:is-active={showUploadModal}
    role="dialog"
    aria-modal="true"
    aria-labelledby="upload-modal-title"
  >
    <div class="modal-background" on:click={closeUploadModal} role="presentation"></div>
    <div class="modal-card">
      <header class="modal-card-head">
        <p class="modal-card-title" id="upload-modal-title">Upload Files</p>
        <button class="delete" aria-label="close" on:click={closeUploadModal}></button>
      </header>
      <section class="modal-card-body">
        <div
          class="dropZone is-size-3 has-text-weight-semibold"
          class:is-dragover={isDragover}
          class:is-uploading={uploading}
          on:dragover={handleDragOver}
          on:dragleave={handleDragLeave}
          on:drop={handleDrop}
          role="region"
          aria-label="File upload drop zone"
        >
          {#if uploading}
            <span class="icon"><i class="fas fa-spinner fa-pulse"></i></span>&nbsp;
            <span id="processingMessage">{uploadMessage}</span>
          {:else}
            Drag &amp; Drop
          {/if}
        </div>
        <form id="uploaderForm" class="file" enctype="multipart/form-data">
          <div class="columns">
            <div class="column is-half">
              <label class="file-label">
                <input
                  class="file-input"
                  id="uploaderFiles"
                  type="file"
                  name="uploaderFiles[]"
                  multiple
                  bind:this={fileInput}
                  on:change={handleFileSelect}
                />
                <span class="file-cta">
                  <span class="file-icon">
                    <i class="fas fa-upload"></i>
                  </span>
                  <span class="file-label">Choose files…</span>
                </span>
              </label>
            </div>
            <div class="column is-half">
              <label class="checkbox is-pulled-right tooltip is-tooltip-left" data-tooltip="Apply TinyPNG smart compression to reduce file size">
                Optimize Images
                <input name="useCompression" value="1" type="checkbox" checked />
              </label>
            </div>
          </div>
        </form>
      </section>
    </div>
  </div>
{/if}

<style>
  :global(ul.menu-list) button {
    width: 100%;
    text-align: left;
    background: none;
    border: none;
    cursor: pointer;
    font: inherit;
    padding: 0.5em 0.75em;
    color: inherit;
    border-radius: 0.25rem;
  }

  :global(ul.menu-list) button:hover {
    background-color: whitesmoke;
  }

  :global(ul.menu-list) button.is-active {
    background-color: hsl(229, 53%, 53%);
    color: white;
  }

  :global(ul.menu-list) button.menu-has-children:after {
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    margin-left: 8px;
    content: '\f107';
    float: right;
  }

  :global(ul.menu-list) button.menu-has-children.has-closed:after {
    content: '\f105';
  }

  :global(ul.menu-list.is-closed) {
    display: none;
  }

  #files {
    overflow-y: auto;
  }

  #files :global(table tbody tr) {
    cursor: pointer;
  }

  #file .card-image img {
    margin: 0 auto;
    max-height: 250px;
    width: auto;
  }

  #file #selectFile {
    margin: 20px 0;
  }

  button.editable-text {
    background: none;
    border: none;
    padding: 0;
    font: inherit;
    color: inherit;
    text-align: left;
  }

  button.editable-text:not(:disabled) {
    cursor: pointer;
  }

  button.editable-text:disabled {
    cursor: default;
    opacity: 1;
  }

  button.editable-text.clickable:after {
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    margin-left: 5px;
    content: '\f303';
    font-size: 0.6em;
    vertical-align: super;
  }

  .dropZone {
    text-align: center;
    padding: 50px 0;
    border: 2px #333 dashed;
    border-radius: 20px;
  }

  .dropZone.is-dragover {
    background-color: #f3f3f3 !important;
    border: 2px #23d160 dashed;
    color: #336699;
  }

  .dropZone.is-uploading {
    border: 2px #336699 solid;
  }

  .file {
    margin-top: 20px;
  }
</style>
