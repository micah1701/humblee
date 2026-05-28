<?php

declare(strict_types=1);

namespace Humblee\Model;

class Media {

    private string $storage_path;

    public function __construct()
    {
        $this->storage_path = _app_server_path."storage/";
    }

    /**
     * Return ARRAY of folders nested by parent ID
     */
    public function listFolders(): array
    {
        $folders = \ORM::for_table(_table_media_folders)->order_by_asc('name')->find_many();
        if(!$folders)
        {
            return [0 => "No Folders"];
        }
    	$result = [];
    	foreach($folders as $folder)
    	{
    		$result[$folder->parent_id][] = ['id' => $folder->id, 'name' => $folder->name];
    	}
        return $result;
    }

    /**
     * Return ARRAY of data for all files in a given folder
     */
    public function listFilesByFolder(int $folder = 0, string $orderBy = 'name'): array
    {
        $files = \ORM::for_table(_table_media)
                    ->select(_table_media.".*")
                    ->select(_table_users.".name", "uploadname")
                    ->join(_table_users, [_table_media.".upload_by", "=", _table_users.".id"])
                    ->where('folder', $folder)
                    ->order_by_asc($orderBy)
                    ->find_array();
        $return = [];
        foreach($files as $file)
        {
            $file['url'] = _app_path.'media/'.$file['id'].'/'.$file['name'];
            unset($file['crypto_nonce']);
            $return[$file['id']] = $file;
        }
        return $return;
    }

    /**
     * Delete a file
     *
     * $file MIXED  INTEGER of database row ID or OBJECT of previously looked up row
     */
    public function deleteFile(int|object $file): bool|string
    {
        if(is_int($file))
        {
            $file = \ORM::for_table(_table_media)->find_one($file);
            if(!$file)
            {
                return "File not found";
            }
        }
        elseif(!is_object($file))
        {
           return "Missing or invalid file object";
        }

        if(file_exists($this->storage_path.$file->filepath))
        {
            if(!unlink($this->storage_path.$file->filepath))
    		{
    			return "Could not unlink file";
    		}
        }

		$file->delete();

        return true;
    }

}
