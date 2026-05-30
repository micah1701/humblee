export interface MediaFolder {
  id: number;
  name: string;
}

export interface MediaFoldersResponse {
  [parentId: string]: MediaFolder[];
}

export interface MediaFile {
  id: number;
  folder: number;
  filepath: string;
  name: string;
  type: string;
  size: number;
  required_role: number;
  encrypted: number;
  upload_by: number;
  upload_date: string;
  uploadname: string;
  url: string;
}

export interface MediaFilesResponse {
  success: boolean;
  files: {
    [fileId: string]: MediaFile;
  };
}

export interface AccessRole {
  id: number;
  name: string;
}

export interface UploadResponse {
  success: boolean;
  errors: string[];
}

export interface FolderCache {
  [key: string]: { [fileId: string]: MediaFile };
}
