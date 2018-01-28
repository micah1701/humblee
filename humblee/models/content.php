<?php

class Core_Model_Content {

    /**
     * Return all Content revisions for a given page's content type
     *
     * $page_id			integer	REQUIRED
     * $content_type	integer	REQUIRED
     * $limit			integer	Maximum number of recent revision to display. 0 returns all.
     */
     public function listRevisions($page_id,$content_type,$max=10){
        if(!is_numeric($content_type) || !is_numeric($page_id) ){ return false; }
	 
        if($max > 0)
        {
			return ORM::for_table(_table_content)->where('page_id',$page_id)->where('type_id',$content_type)->order_by_desc('revision_date')->limit($max)->find_many();
		}
		else
		{
			return ORM::for_table(_table_content)->where('page_id',$page_id)->where('type_id',$content_type)->order_by_desc('revision_date')->find_many();
		}
	}
	
    /**
     * Save Content for a given page and content block
     * 
     * $post	ARRAY required values are:
     *					content_id (last known content id being replaced)
     *					page_id
     *					content_type_id
     *					content (new content)
     *				  optional field:
     *					serialize_fields (a list of arbitrary content fields if there is more than one)
     *					arbitrary fields (listed in the serialize_fields list)
     * 
     * returns the new content object or FALSE if nothing changed
     */
    public function saveContent($post)
	{
		if(!is_numeric($post['content_type_id']) || !is_numeric($post['page_id']) ){ return false; }
		
		// if there was a bunch of fields, turn them into a JSON array and overwrite the "content" field with the array
		if(isset($post['serialize_fields']))
		{
			$fields = explode(",",$post['serialize_fields']);
			$content_array = array();
			foreach($fields as $field)
			{
				$content_array[$field] = (isset($post[$field])) ? $post[$field] : "";	
			}
			
			$post['content'] = json_encode($content_array);
		}
		
		$current_content = ORM::for_table( _table_content)->find_one($post['content_id']); //what the content looked like before it was just edited
		
		$previous_revisions = ORM::for_table ( _table_content)->where('page_id',$post['page_id'])->where('type_id',$post['content_type_id'])->count();
		
		$content = str_replace('$','&#36;',$post['content']); // dollar signs are messy, convert to html equiv
		
		// new content
		if($current_content->content != $content)
		{ 
			//if there is only 1 revision of this content and it is blank then this is the initial save so use this same content ID;  Otherwise, create new record
			$new_content = ($previous_revisions == 1 && trim($current_content->content) == "" ) ? $current_content : ORM::for_table( _table_content)->create();
			
			$new_content->type_id = $post['content_type_id'];
			$new_content->page_id = $post['page_id'];
			$new_content->content = $content;
			$new_content->revision_date = date("Y-m-d H:i:s");
			$new_content->updated_by = $_SESSION[session_key]['user_id']; 
			$new_content->save();
		}
		else
		{
			$new_content = false;
		}
		
		if($post['live'] == "1"){
			
			//dethrown the old live version
			$old_live = ORM::for_table( _table_content )
				->where('page_id',$post['page_id'])
				->where('type_id',$post['content_type_id'])
				->where('live',1)
				->find_one();
			if($old_live)
			{	 
				$old_live->live = 0;
				$old_live->save();
			}
			
			if(!$new_content)
			{
				if($current_content->live == 0)
				{
					$current_content->publish_date = date("Y-m-d H:i:s");		
					$current_content->updated_by = $_SESSION[session_key]['user_id'];	
					$current_content->live = 1;
					$current_content->save();
				}
			}
			else
			{
				$new_content->publish_date = date("Y-m-d H:i:s");	
				$new_content->live = 1;
				$new_content->save();
			}
		} 
		
        return $new_content;	
    }


    /** THIS FUNCTION STILL NEEDS TO BE CONVERTED TO MJMWEB SYSTEM
     * get content obj of given row number when ordering by a given column name
     *
     * $page_id			Integer	REQUIRED
     * $column 			String	
     * $order_number	Interger
     *
     * example usage: $column = "revision_date" AND $order_number = 1 returns most recent revised item
     * note: returns FALSE when there are less rows than the number requested (requesting row 10 when therea re only 4 rows, returns false)
     * 
     */
    private function getRevisionByOrderNumber($page_id,$column="revision_date", $order_number=1){
		if(!is_numeric($page_id)){ return false; }	
		
		$count = ORM::factory('content')->where('page_id','=',$page_id)->count_all();
		if($count < $order_number) { return false; }
		
		$limit = $order_number -1;
		$sql = "SELECT * FROM ".$this->_table_name." WHERE page_id = ".$page_id." ORDER BY ".$column." DESC LIMIT ".$limit .",1"; 		
		$result =  DB::query(Database::SELECT, $sql,1)->execute();   
	 	
		//exit("row #".$result[0]);
		
		return $result[0];
    }
	
    /** THIS FUNCTION STILL NEEDS TO BE CONVERTED TO MJMWEB SYSTEM
     * cleanup revisions
     *
     * $page_id	integer	REQUIRED
     * $keep	integer	number of revisions to save
     *
     */
    public function cleanupRevisions($page_id,$keep=10){
		if(!is_numeric($page_id)){ return false; }	
		
		if($getRevision_N = $this->getRevisionByOrderNumber($page_id,"revision_date",$keep) ){
			
			$oldest_date = $getRevision_N['revision_date'];
			if(!$oldest_date){ return false; }
			
			$sql = "DELETE FROM ".$this->_table_name." 
					  WHERE revision_date < '". $oldest_date ."'
					  	AND page_id = ".$page_id."
						AND publish_date = '0000-00-00 00:00:00' "; 
			return $this->_db->query(Database::DELETE, $sql,1);  
		}
	 
	 }
	 
    /** THIS FUNCTION STILL NEEDS TO BE CONVERTED TO MJMWEB SYSTEM
     * cleanup previously published versions, keeping only N number of revisions
     *
     * $page_id	integer	REQUIRED
     * $keep	integer	number of revisions to save
     *
     */
    public function cleanupPublished($page_id,$keep=10){
        if(!is_numeric($page_id)){ return false; }	 
		
		if($getRevision_N = $this->getRevisionByOrderNumber($page_id,"publish_date",$keep) )
		{
			$oldest_date = $getRevision_N['publish_date'];
			if(!$oldest_date){ return false; }
			
			$sql = "DELETE FROM ".$this->_table_name." 
						  WHERE publish_date < '". $oldest_date ."'
						  AND page_id = ".$page_id."
						  AND publish_date != '0000-00-00 00:00:00' "; 
			return $this->_db->query(Database::DELETE, $sql,1); 
		}
    }
	  
}