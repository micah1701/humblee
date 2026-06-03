<?php

declare(strict_types=1);

namespace Humblee\Controller\Requests;

use Humblee\Controller\Request;
use Humblee\Model\Media;

final class MediaFiles
{
    public static function listFolders(Request $ctrl): void
    {
        $ctrl->require_role(['content', 'media']);
        $media = new Media;
        $ctrl->json($media->listFolders());
    }

    public static function listByFolder(Request $ctrl): void
    {
        $ctrl->require_role(['content', 'media']);
        if (!isset($_GET['folder']) || !is_numeric($_GET['folder'])) {
            $result['error'] = "missing or invalid folder ID";
        }
        $media = new Media;
        $response = ['success' => true, 'files' => $media->listFilesByFolder((int)$_GET['folder'])];
        $ctrl->json($response);
    }

    public static function updateName(Request $ctrl): void
    {
        $ctrl->require_role('media');
        if (!isset($_POST['type']) || !isset($_POST['record']) || !is_numeric($_POST['record'])) {
            $ctrl->json(['error' => 'invalid request']);
        }
        $record = false;
        if ($_POST['type'] == "folder_name") {
            $record = \ORM::for_table(_table_media_folders)->where('id', $_POST['record'])->find_one();
        }
        if ($_POST['type'] == "file_name") {
            $record = \ORM::for_table(_table_media)->where('id', $_POST['record'])->find_one();
        }

        if (!$record) {
            $ctrl->json(['error' => 'record not found']);
            return;
        }

        $record->name = $_POST['value'];
        $record->save();
        $ctrl->json(['success' => true]);
    }

    public static function updateRole(Request $ctrl): void
    {
        $ctrl->require_role('media');
        if (!isset($_POST['file_id']) || !is_numeric($_POST['file_id']) || !isset($_POST['required_role']) || !is_numeric($_POST['required_role'])) {
            exit("Invalid or missing file ID or role type");
        }
        $file = \ORM::for_table(_table_media)->find_one($_POST['file_id']);
        if (!$file) {
            exit("File record not found");
        }
        $file->required_role = (int)$_POST['required_role'];
        $file->save();
        $ctrl->json(['success' => true]);
    }

    public static function encrypt(Request $ctrl): void
    {
        $ctrl->require_role('media');
        if (!isset($_POST['file_id']) || !is_numeric($_POST['file_id']) || !isset($_POST['action'])) {
            exit("Invalid or missing file ID or action");
        }

        $file = \ORM::for_table(_table_media)->find_one($_POST['file_id']);
        if (!$file) {
            exit("File record not found");
        }

        $file_location = _app_server_path . 'storage/' . $file->filepath;
        $file_content = file_get_contents($file_location);

        if ($file_content === false) {
            exit("The file system could not read the requested resource");
        }

        $crypto = new \Humblee\Model\Crypto;
        if ($_POST['action'] == "encrypt") {
            $encrypt = $crypto->encrypt($file_content);
            if ($encrypt === false) {
                exit("Error encrypting file");
            }
            if (!file_put_contents($file_location, $encrypt)) {
                exit("Could not save encrypted text to file");
            } else {
                $file->encrypted = 1;
                $file->save();
                $ctrl->json(["success" => true]);
            }
        } elseif ($_POST['action'] == "decrypt") {
            $decrypt = $crypto->decrypt($file_content);
            if ($decrypt === false) {
                exit("Error decrypting file");
            }
            if (!file_put_contents($file_location, $decrypt)) {
                exit("Could not save decrypted text to file");
            } else {
                $file->encrypted = 0;
                $file->save();
                $ctrl->json(["success" => true]);
            }
        }

        $ctrl->json(["success" => false, "error" => "malformed request"]);
    }

    public static function deleteFile(Request $ctrl): void
    {
        $ctrl->require_role('media');
        if (!isset($_POST['file_id']) || !is_numeric($_POST['file_id'])) {
            exit("Invalid or missing file ID");
        }
        $mediaObj = new Media;
        $delete = $mediaObj->deleteFile((int)$_POST['file_id']);

        if ($delete !== true) {
            $ctrl->json(["success" => false, "error" => $delete]);
        }

        $ctrl->json(["success" => true]);
    }

    public static function createFolder(Request $ctrl): void
    {
        $ctrl->require_role('media');

        $folder = \ORM::for_table(_table_media_folders)->create();
        $folder->name = (isset($_POST['name'])) ? $_POST['name'] : "New Folder";
        $folder->parent_id = (isset($_POST['parent_id']) && is_numeric($_POST['parent_id'])) ? $_POST['parent_id'] : 0;
        $folder->save();

        $ctrl->json(["success" => true, "folder_id" => $folder->id()]);
    }

    public static function deleteFolder(Request $ctrl): void
    {
        $ctrl->require_role('media');
        if (!isset($_POST['folder_id']) || !is_numeric($_POST['folder_id'])) {
            exit("Invalid or missing file ID");
        }

        $children = \ORM::for_table(_table_media_folders)->where('parent_id', (int)$_POST['folder_id'])->find_many();
        if ($children) {
            $ctrl->json(["success" => false, "errors" => "This folder has subfolders and can not be deleted. Delete the child folders first!"]);
        }

        $files = \ORM::for_table(_table_media)->where('folder', (int)$_POST['folder_id'])->find_many();
        $mediaObj = new Media;
        $errors = [];
        foreach ($files as $file) {
            $delete = $mediaObj->deleteFile($file);
            if ($delete !== true) {
                $errors[] = $delete;
            }
        }

        if (count($errors) > 0) {
            $ctrl->json(["success" => false, "errors" => $errors]);
        }

        $folder = \ORM::for_table(_table_media_folders)->find_one($_POST['folder_id']);
        if (!$folder) {
            exit("Folder record not found");
        }

        $folder->delete();
        $ctrl->json(['success' => true]);
    }

    public static function upload(Request $ctrl): void
    {
        $ctrl->require_role('media');
        $errors = [];

        if (!$_FILES['uploaderFiles']) {
            $errors[] = "No Files Uploaded";
            $files = [];
        } else {
            $files = self::reArrayFiles($_FILES['uploaderFiles']);
        }

        $totalFiles = count($files);
        $savedFiles = 0;

        foreach ($files as $file) {
            $cleanFilename = filter_var($file['name'], FILTER_SANITIZE_URL);
            $cleanFilename = str_replace(" ", "-", $cleanFilename);

            $fileRecord = \ORM::for_table(_table_media)->create();
            $fileRecord->folder = (isset($_POST['folder_id']) && is_numeric($_POST['folder_id'])) ? (int)$_POST['folder_id'] : 0;
            $fileRecord->name = $cleanFilename;
            $fileRecord->encrypted = 0;
            $fileRecord->crypto_nonce = "";
            $fileRecord->required_role = 0;
            $fileRecord->size = $file['size'];
            $fileRecord->type = $file['type'];
            $fileRecord->upload_by = (isset($_SESSION[session_key]['user_id'])) ? (int)$_SESSION[session_key]['user_id'] : 0;
            $fileRecord->upload_date = gmdate("Y-m-d H:i:s");

            $nameParts = explode(".", $file['name']);
            $storageName = gmdate("YmdHis") . substr(md5($cleanFilename), 0, 6) . "." . strtolower(array_pop($nameParts));

            if ($file['error'] == 0) {
                if (
                    stripos($file['type'], 'image') !== false && $_ENV['config']['TINYPNG_Enabled']
                    && isset($_POST['useCompression']) && $_POST['useCompression'] == 1
                ) {
                    try {
                        \Tinify\setKey($_ENV['config']['TINYPNG_API_Key']);
                        $source = \Tinify\fromFile($file['tmp_name']);
                        if ($_ENV['config']['TINYPNG_Max_Width'] && $source->result()->width() > $_ENV['config']['TINYPNG_Max_Width']) {
                            $source = $source->resize([
                                "method" => "scale",
                                "width" => $_ENV['config']['TINYPNG_Max_Width']
                            ]);
                        }
                        $source->toFile(_app_server_path . "storage/" . $storageName);

                        $fileRecord->filepath = $storageName;
                        $fileRecord->size = $source->result()->size();
                        $fileRecord->type = $source->result()->mediaType();
                        $fileRecord->save();
                        $savedFiles++;
                    } catch (\Tinify\Exception $e) {
                        $errors[] = $e->getMessage();
                        $fileRecord->delete();
                    }
                } else {
                    if (move_uploaded_file($file['tmp_name'], _app_server_path . "storage/" . $storageName)) {
                        $fileRecord->filepath = $storageName;
                        $fileRecord->save();
                        $savedFiles++;
                    } else {
                        $errors[] = 'could not store file ' . $file['name'];
                        $fileRecord = null;
                        continue;
                    }
                }
            } else {
                $errors[] = ["error" => 'there was a problem with the file: ' . $file['name'], "code" => $file['error']];
                $fileRecord = null;
                continue;
            }
        }

        $success = ($savedFiles > 0) ? true : false;
        $ctrl->json(["success" => $success, "errors" => $errors, "filesReceived" => $totalFiles, "filesSaved" => $savedFiles]);
    }

    public static function migrateNonces(Request $ctrl): void
    {
        $ctrl->require_role('admin');

        $files = \ORM::for_table(_table_media)
            ->where('encrypted', 1)
            ->where_not_equal('crypto_nonce', '')
            ->find_many();

        $migrated = 0;
        $errors   = [];

        foreach ($files as $file) {
            $nonce = $file->crypto_nonce;

            if (strlen($nonce) !== SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
                $errors[] = "File ID {$file->id}: unexpected nonce length (" . strlen($nonce) . " bytes), skipped";
                continue;
            }

            $file_location = _app_server_path . 'storage/' . $file->filepath;
            $ciphertext    = file_get_contents($file_location);

            if ($ciphertext === false) {
                $errors[] = "File ID {$file->id}: could not read from disk, skipped";
                continue;
            }

            if (!file_put_contents($file_location, $nonce . $ciphertext)) {
                $errors[] = "File ID {$file->id}: could not write migrated payload to disk, skipped";
                continue;
            }

            $file->crypto_nonce = '';
            $file->save();
            $migrated++;
        }

        $ctrl->json([
            "success"  => empty($errors),
            "migrated" => $migrated,
            "errors"   => $errors,
        ]);
    }

    private static function reArrayFiles(array &$file_post): array
    {
        $file_ary = [];
        $file_count = count($file_post['name']);
        $file_keys = array_keys($file_post);

        for ($i = 0; $i < $file_count; $i++) {
            foreach ($file_keys as $key) {
                $file_ary[$i][$key] = $file_post[$key][$i];
            }
        }

        return $file_ary;
    }
}
