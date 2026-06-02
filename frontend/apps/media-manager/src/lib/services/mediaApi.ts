import type {
  MediaFoldersResponse,
  MediaFilesResponse,
  UploadResponse
} from '../types/media';

export const createMediaApi = (xhrPath: string) => ({
  async listMediaFolders(): Promise<MediaFoldersResponse> {
    const response = await fetch(`${xhrPath}media_files/list-folders`);
    return response.json();
  },

  async listMediaFilesByFolder(folderId: number): Promise<MediaFilesResponse> {
    const response = await fetch(`${xhrPath}media_files/list-by-folder?folder=${folderId}`);
    return response.json();
  },

  async createMediaFolder(parentId: number): Promise<{ success: boolean }> {
    const response = await fetch(`${xhrPath}media_files/create-folder`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `parent_id=${parentId}`
    });
    return response.json();
  },

  async deleteMediaFolder(folderId: number): Promise<{ success: boolean; errors?: string }> {
    const response = await fetch(`${xhrPath}media_files/delete-folder`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `folder_id=${folderId}`
    });
    return response.json();
  },

  async deleteMediaFile(fileId: number): Promise<{ success: boolean }> {
    const response = await fetch(`${xhrPath}media_files/delete-file`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `file_id=${fileId}`
    });
    return response.json();
  },

  async updateMediaName(
    type: string,
    recordId: number,
    value: string
  ): Promise<{ success: boolean }> {
    const response = await fetch(`${xhrPath}media_files/update-name`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `type=${type}&record=${recordId}&value=${encodeURIComponent(value)}`
    });
    return response.json();
  },

  async updateMediaRole(
    fileId: number,
    requiredRole: number
  ): Promise<{ success: boolean }> {
    const response = await fetch(`${xhrPath}media_files/update-role`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `file_id=${fileId}&required_role=${requiredRole}`
    });
    return response.json();
  },

  async encryptMedia(
    fileId: number,
    action: 'encrypt' | 'decrypt'
  ): Promise<{ success: boolean }> {
    const response = await fetch(`${xhrPath}media_files/encrypt`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `file_id=${fileId}&action=${action}`
    });
    return response.json();
  },

  async uploadMediaFiles(formData: FormData): Promise<UploadResponse> {
    const response = await fetch(`${xhrPath}media_files/upload`, {
      method: 'POST',
      body: formData
    });
    return response.json();
  }
});
