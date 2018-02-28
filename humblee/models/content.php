<?php

class Core_Model_Content {

	private function requireContentRoles()
	{
		if(!Core::auth(array('content','publish','developer')))
		{
			exit("You do not have permission to access this function.");
		}
	}

    /**
     * Return all Content revisions for a given page's content type
     * includes name of user who saved content
     *
     * $page_id			integer	REQUIRED
     * $content_type	integer	REQUIRED
     * $limit			integer	Maximum number of recent revision to display. 0 or false returns all(up to 9999).
     */
	public function listRevisions($page_id,$content_type,$p13n_id=0,$max=10){
		$this->requireContentRoles();

        if(!is_numeric($content_type) || !is_numeric($page_id) ){ return false; }

        $limit = ($max && $max > 0) ? $max : 9999;
		return ORM::for_table(_table_content)
					->select(_table_content.'.*')
					->select(_table_users.'.name')
					->join( _table_users, array( _table_content.'.updated_by', '=', _table_users.'.id'))
					->where('page_id',$page_id)
					->where('type_id',$content_type)
					->where('p13n_id',$p13n_id)
					->order_by_desc('revision_date')
					->limit($max)
					->find_many();
	}

    /**
     * Save Content for a given page, content block and p13n version
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
		$this->requireContentRoles();

		if(!is_numeric($post['content_type_id']) || !is_numeric($post['page_id']) ){ return false; }
		if(!isset($post['p13n_id'])) {
			$post['p13n_id'] = 0;
		}

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

		$previous_revisions = ORM::for_table ( _table_content)
								->where('page_id',$post['page_id'])
								->where('type_id',$post['content_type_id'])
								->where('p13n_id',$post['p13n_id'])
								->count();

		$content = $post['content'];

		// new content
		if($current_content->content != $content)
		{
			//if there is only 1 revision of this content and it is blank then this is the initial save so use this same content ID;  Otherwise, create new record
			$new_content = ($previous_revisions == 1 && trim($current_content->content) == "" ) ? $current_content : ORM::for_table( _table_content)->create();

			$new_content->type_id = $post['content_type_id'];
			$new_content->page_id = $post['page_id'];
			$new_content->p13n_id = $post['p13n_id'];
			$new_content->content = $content;
			$new_content->revision_date = date("Y-m-d H:i:s");
			$new_content->updated_by = $_SESSION[session_key]['user_id'];
			$new_content->save();
		}
		else
		{
			$new_content = false;
		}

		if($post['live'] == "1" && Core::auth(array('publish','developer'))){

			//dethrown the old live version
			$old_live = ORM::for_table( _table_content )
				->where('page_id',$post['page_id'])
				->where('type_id',$post['content_type_id'])
				->where('p13n_id',$post['p13n_id'])
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


	/**
	 * Find all of the appropriate live content for a given page
	 *
	 * returns array of objects
	 */
	public function findContent($page_id)
	{

		// if using personalization, find applicable versions of the content
		if($_ENV['config']['use_p13n'])
		{
			$p13nObj = new Core_Model_P13n();
			$p13n_versions = $p13nObj->getAll(true,true);
			$p13n_versions[] = 0; // add default non-p13n version at the end in case none of the returned IDs have content
		}
		else
		{
			//just use default non-p13n version
			$p13n_versions = array(0);
		}

		$getContent = ORM::for_table( _table_content )
					  ->select(_table_content.'.*')
					  ->select(_table_content.'.id', 'content_id')
					  ->select(_table_content_types.'.*')
					  ->select(_table_content_types.'.id', 'block_id')
					  ->select(_table_content_p13n. '.id', 'p13n_id')
					  ->join( _table_content_types, array( _table_content.".type_id","=", _table_content_types.".id") )
					  ->left_outer_join( _table_content_p13n,  array( _table_content.".p13n_id","=", _table_content_p13n. ".id") )
					  ->where('page_id',$page_id)
					  ->where_in(_table_content. '.p13n_id', $p13n_versions)
					  ->where('live',1)
					  ->find_many();

		//create associative array of content objects.  key is the content_type "name"
		$contents = array();
		foreach($getContent as $content)
		{
			$contents[$content->objectkey] = $content;

			if($content->input_type == "markdown")
			{
				$Parsedown = new Parsedown();
				$contents[$content->objectkey]['content'] = $Parsedown->instance()->text($contents[$content->objectkey]['content']);
			}
		}

		return $contents;
	}

}