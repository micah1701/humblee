<?php

/**
 * manage user uploaded media files
 */
 
class Core_Model_Media {
    
    /**
     * Return ARRAY of folders nested by parent ID
     */
    public function listFolders()
    {
        $folders = ORM::for_table(_table_media_folders)->order_by_asc('name')->find_many();
        if(!$folders)
        {
            return array(0=>"No Folders");
        }
    	$result = array();
    	foreach($folders as $folder)
    	{
    		$result[$folder->parent_id][] = array("id"=>$folder->id, "name"=>$folder->name);
    	}
        return $result;        
    }
    
    /**
     * Return ARRAY of data for all files in a given folder
     */
    public function listFilesByFolder($folder=0,$orderBy='name')
    {
        $files = ORM::for_table(_table_media)
                    ->select(_table_media.".*")
                    ->select(_table_users.".name","uploadname")
                    ->join(_table_users,array(_table_media.".upload_by","=",_table_users.".id"))
                    ->where('folder',$folder)
                    ->order_by_asc($orderBy)
                    ->find_array();
        $return = array();
        foreach($files as $file)
        {
            $return[$file['id']] = $file;
        }
        return $return;
    }


}