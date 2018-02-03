<?php

/**
 * manage user uploaded media files
 */
 
class Core_Model_Media {
    
    function __construct()
    {
        //folder on server where all the files are stored
        $this->storage_path = _app_server_path."storage/";    
    }
    
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
            //overload result array with additional data
            $file['url'] = _app_path.'media/'.$file['id'].'/'.$file['name'];
            
            $return[$file['id']] = $file;
        }
        return $return;
    }
    
    /**
     * Delete a file
     * 
     * $file MIXED  INTEGER of database row or OBJECT of previously looked up row
     */
    public function deleteFile($file)
    {
        if(is_numeric($file))
        {
            $file = ORM::for_table(_table_media)->find_one($file);
            if(!$file)
            {
                return "File not found";
            }
        }
        elseif(!is_object($file))
        {
           return "Missing or invalid file object";
        }
        
        //delete the file
        if(!unlink($this->storage_path.$file->filepath))
		{
			return "Could not unlink file";
		}
		
		//delete the row from the database
		$file->delete();
        
        return true;
    }


}