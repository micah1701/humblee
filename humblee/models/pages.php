<?php 

class Core_Model_Pages {
	
	/**
	 * INSERT or UPDATE a page with user submited POST data
	 *
	 * 	$action string	"update" or "add" or "delete"
	 *	$post	ARRAY	$_POST data	(requires "parent_id" for adding a page and "page_id" for updating or deleting a page)
	 * 
	 *	@returns MIXED	page_id (for adding/updating) string "success" (for delete) or error string
	 */
	public function add_or_update($action,$post)
    {	
		// update the pages table with this action
		if($action == "add")
        {
			//make sure a PARENT ID was set
			if(!isset($post['parent_id']) || !is_numeric($post['parent_id']))
			{ 
				return "Invalid Parent ID"; 
			}
			
			//if this is a child page (it has a parent id) make sure the parent isn't the homepage
			if($post['parent_id'] != 0)
	        {		
					$parent = ORM::for_table( _table_pages )->find_one($post['parent_id']);
					if($parent->slug == "" || $parent->slug == "/")
					{
						return "Can not create child page of the homepage or a page without a valid uri slug";
					}
			}
			$page = ORM::for_table( _table_pages )->create();
			$page->parent_id = $post['parent_id'];
		}
        elseif($action == "update" && ( isset($post['page_id']) || is_numeric($post['page_id'])))
        {
			$page = ORM::for_table( _table_pages )->find_one($post['page_id']);
			if(!$page)
			{
				return "Could not find specified page to update.";
			}
        }
        elseif($action == "delete" && ( isset($post['page_id']) || is_numeric($post['page_id'])))
        {
			$page = ORM::for_table( _table_pages )->find_one($post['page_id']);
			if(!$page)
			{
				return "Could not find specified page to delete.";
			}
			$subpages = ORM::for_table( _table_pages)->where('parent_id',$post['page_id'])->find_many();
			if($subpages){
				return ("Can not delete this page becomes it has ".count($subpages)." subpage(s).  Please move or delete child pages first.");
			}
			$page->delete();
			return "success";
        }
        else
        {
        	return "Error: missing page action or page id";
        }

		$page->slug = (isset($post['slug']) && $post['slug'] != "") ? $post['slug'] : "new-".date("YmdHis");
		$page->label = (isset($post['label']) && $post['label'] != "") ? $post['label'] : "New Page";
		$page->display_order = (isset($post['display_order']) && $post['display_order'] != "") ? $post['display_order'] : 0;
		$page->template_id = (isset($post['template_id']) && $post['template_id'] != "") ? $post['template_id'] : 1;
		$page->active = (isset($post['active']) && is_numeric($post['active']) ) ? $post['active'] : 1;
		$page->searchable = (isset($post['searchable']) && is_numeric($post['searchable']) ) ? $post['searchable'] : 1;
		$page->display_in_sitemap = (isset($post['display_in_sitemap']) && is_numeric($post['display_in_sitemap']) ) ? $post['display_in_sitemap'] : 1;
		if(isset($post['required_role']))
		{
			$page->required_role = $post['required_role'];
		}
		$page->save();

		return $page->id(); 
	}
	
    /**
     * Return the array of row data from the 'Pages' table for the given URI
     *
     * @param Route      		$route the URI after the http:///domain-name.com/
	 * @param recursionFlag		$recursionFlag is set to true if the function is being called in recursive loop.
     * @return array   
     *
     * assumes 'pages' table has these columns:
     *    'id'            the given row ID for a page
     *    'parent_id'   the page.id one level higher in the URL heiarchy (value is INT; 0 = page has no parent)
     *    'slug'    the given page's pseudo "filename" in the URL (example: "About-Us")
     */
    public function getPagefromURL($route,$recursionFlag=false)
    {  
   		//$route looks like:  page/subpage1/subpage2/subpage3		
		$route = trim($route,"/");
        
		if($route == "" || $route == trim(_app_path,"/") )
        {  // check if this is the homepage
			$route = "";
			$uri_parts[0] = "/";
			$uri_num_parts = 1;
		}
        else
        {	
			$uri_parts = explode("/",$route); //returns  [0] => page [1] => subpage1 [1] => subpage2 [2] => subpage3
        	$uri_num_parts = count($uri_parts);
		}
   
        if($uri_num_parts == 1)
        { 
            $sql_where = "slug = '{$route}' AND parent_id = 0";	
		}
        else
        {
            $sql_where = "slug = '{$uri_parts[$uri_num_parts -1]}' "; //start with last page in array (-1 because array starts w/ 0)
                 
                $sub_query_braces = "";

                     for($i = $uri_num_parts -2; $i >= 0; $i--)
                     {  //loop backwards through array of subpages.  $i = array key (-2 because we already added -1 above)
                       $check4noParent = ($i==0) ? "AND parent_id = 0" : "";
					   $sql_where.= "AND parent_id = (SELECT id FROM ". _table_pages ." WHERE slug = '{$uri_parts[$i]}' $check4noParent ";
                       $sub_query_braces.= ")";
                     }//end loop through array
                     $sql_where.= $sub_query_braces;                       
        }   
       		
		$results = ORM::for_table( _table_pages )
					  ->where('active',1)
					  ->where_raw( $sql_where )
					  ->find_one();
    
		if(!$results)
        { // if no page was found, maybe the end of the url is really a variable being passed to the controller.  lop it off and try again.
			array_pop($uri_parts);
			if(count($uri_parts) == 0){ return false; } // no page could be found, return FALSE here. controller should return 404
			$route = implode("/",$uri_parts);
			return $this->getPagefromURL($route,true); // recursivly re-run this function (and set $recursionFlag to TRUE)
		}
        else
        {	
			if($recursionFlag)
            {
                $template = ORM::for_table(_table_templates)->select('dynamic_uri')->find_one($results->template_id);
				if($template->dynamic_uri != 1){ return false; } // this page is not set to have pseduo children
			}//end check for $recursionFlag
			
			return $results;
		}
	}
	
	/**
	 * Get the Pages (and subpages) of a given Parent ID
	 *
	 * @param $parent_id	INT		id of parent page
	 * @param $params		ARRAY	('parent_id' = INT ID of top level pages for this tree; defaults to 0 if left out
	 * 								 'generations' => INT of how many recurssions this function should continue with. Leave blank/false for ALL children
	 *								 'selected_pages' => ARRAY (optional) of IDs of specific pages to include at the top level
	 *								 'generate_uri'	=> BOOL, TRUE figures out the full URI for each page and returns it as a STRING in $uri
	 *								+
	 *								 'active_only' => BOOL, TRUE shows only pages marked as 'active'
	 *							  	 'display_in_sitemap_only' => BOOL, TRUE shows only pages with 'display_in_sitemap' set to TRUE								 
	 *							  	OR
	 *							  	 'menu_id' => INT of menu ID from `menus_pages` table
	 *							  	)
	 *
	 * @param $generation	INT		Leave Blank.  Used by recursive calls to this function
	 *
	 * @return	Array of stdClass "page" Objects 				
	 */
	public function getPages($params = array('active_only'=>true,'display_in_sitemap_only'=>true),$generation=0)
    {	
		$menu = array();
		$parent_id = (array_key_exists('parent_id',$params) && is_numeric($params['parent_id'])) ? $params['parent_id'] : 0;
		$params['toplevel_parent'] = ( !isset($params['toplevel_parent']) ) ? $parent_id : $params['toplevel_parent'];
		
		// get page info from `menu_pages` table
		if(array_key_exists('menu_id',$params) && is_numeric($params['menu_id']) )
        {  
			$table = _table_menus_pages;
 			$sql =  'SELECT id as thisID,
					 slug,label,template_id,
					 parent_id as thisParentID,
					 display_in_sitemap,
					 (SELECT COUNT(id) FROM '.$table.' WHERE parent_id = thisID) as children,
					 (SELECT slug FROM '.$table.' WHERE id = thisParentID) as parentName
					 FROM '.$table.' WHERE parent_id = '.$parent_id. $active . $display_in_sitemap .' ORDER BY display_order';	
		}
        else // or get page info from default `pages` table
        {
			$table = _table_pages;
			$active = (array_key_exists('active_only',$params) && $params['active_only']) ? " AND active = 1 " : "";
			$sitemap = (array_key_exists('display_in_sitemap_only',$params) && $params['display_in_sitemap_only']) ? " AND display_in_sitemap = 1 " : "";
 			$sql =  'SELECT id as thisID,
					 slug,label,template_id,
					 parent_id as thisParentID,
					 required_role,
					 active,
					 display_in_sitemap,
					 (SELECT COUNT(id) FROM '.$table.' WHERE parent_id = thisID) as children,
					 (SELECT slug FROM '.$table.' WHERE id = thisParentID) as parentName
					 FROM '.$table.' WHERE parent_id = '.$parent_id. $active . $sitemap .' ORDER BY display_order';	
		}
		
		$pages = ORM::for_table( $table )->raw_query($sql,array())->find_many();
		
		$i=0;
		foreach($pages as $page)
        {
			if( array_key_exists('selected_pages',$params) &&
				is_array($params['selected_pages']) && 
				$page->thisParentID == $params['toplevel_parent'] &&
				!in_array($page->thisID, $params['selected_pages'])	
			){
				continue;
			}
			
			if($i==0){ $generation++; } // every time this function is called and $i is reset to 0, increment the generation count

			if(array_key_exists('generations',$params) && is_numeric($params['generations']) && $generation > $params['generations'])
            {			
				return; 
			}

			if(array_key_exists('generate_uri',$params) && $params['generate_uri'])
			{
				$page->uri = $this->buildLink($page->thisID);
			}
			
			$menu[$i] = $page;
			$menu[$i]->generation = $generation;
			
			if( $page->children > 0)
            {	
				$params['parent_id'] = $page->thisID; //update ID to start this function with on next incursion 
				$menu[$i]->child_pages = $this->getPages($params,$generation); // call this function again
			}
            
			$i++;
		}
		
		return (object)$menu;
	}

	/**
	 * DRAW HTML Menu as an Unordered List (UL) from a pages Object
	 *
	 * $pages		OBJECT	data from getPages()
	 * $params		ARRAY (option)
	 *  'li_format'	STRING	string of contents to be inserted into each list element
	 *						including variables from the menu object or this function
	 *  'id_label' 	STRING	(optional) text to be prepended to the page ID and included as the <li> DOM ID
	 *  'thisID'	INT		ID of the calling page
	 *  'currentChildrenOnly' BOOL	if set to TRUE, will only show childsubpages if the current page is one of those pages (requires 'thisID' to be set)
	 *  'hasChildrenClass'	STRING	custom class name if a given item has children
	 *  'currentPageClass'	STRING  custom class name if a given item is the current page (requires 'thisID' to be set)
	 *  'currentPageParentClass'	STRING	custom class name a given item is the parent of the current page (requires 'thisID' to be set)
	 *
	 * $slugRoot	STRING	used by recursive calls to this function
	 * $html		STRING 	used by recursive calls to this function
	 *
	 */
	public function drawMenu_UL($pages, $params=array(), $slugRoot="",$html="") 
	{
		if(count( (array)$pages ) > 0)
		{		
			$html.= "<ul>\n";
		  	foreach($pages as $item)
		  	{
			
				// if this is drawing a menu thats starts with a sub page, the URL scheme needs to start with that pages URL
				if( $slugRoot == "" && $item->thisParentID != 0 )
				{
					$slugRoot = $this->buildLink($item->thisParentID);
				}
				
				if(isset($item->slug))
				{
					$newSlug = $slugRoot."/".$item->slug;
				}else{
					$newSlug = $slugRoot; // keep the same path
				}	
				
				$item->label = preg_replace("/&/","&amp;",$item->label); //make ampersands HTML friendly
				
				$params['li_format'] = (array_key_exists('li_format',$params) ) ? $params['li_format'] : '<a href=\"$newSlug\" $drawClass >$item->label</a>';
				
				$currentChildrenOnly = (array_key_exists('currentChildrenOnly',$params) ) ? $params['currentChildrenOnly'] : false;
				
				$currentParent = false; // assume $item is not the parent of the current page (unless changed below)
				
				$drawClass = 'class="';
				
					if( $item->children > 0  && count( (array)$item->child_pages ) > 0 )
					{
						$drawClass.= (array_key_exists('hasChildrenClass',$params) ) ? $params['hasChildrenClass'].' ' : 'menu_hasChildren ';
					
						if( array_key_exists('thisID',$params))
						{
							$crumbs = $this->getBreadcrumbs($params['thisID']);
							foreach($crumbs as $crumb)
							{
								if( $item->thisID == $crumb['id'] )
								{
									$drawClass.= (array_key_exists('currentPageParentClass',$params) ) ? $params['currentPageParentClass'].' ' : 'menu_currentPageParent ';
									$currentParent = true;
									continue;
								}
							}	
						}
					}
				
					if( array_key_exists('thisID',$params) && $item->thisID == $params['thisID'] )
					{
						$drawClass.= (array_key_exists('currentPageClass',$params) ) ? $params['currentPageClass'].' ' : 'menu_currentPage ';
					}
				
				$drawClass.= '"';
				
				$html.= (array_key_exists('id_label',$params) ) ? '<li id="'.$params['id_label'].$item->thisID.'" '.$drawClass.'>' : '<li '.$drawClass.'>';
						
				eval("\$li_content = \"$params[li_format]\";");
				$html.= $li_content;
				
				if ($item->children > 0 
				&& count( (array)$item->child_pages ) > 0
				&& (!$currentChildrenOnly ||$currentParent )) // if 'currentChildrenOnly' is TRUE only draw the children if this $item is a parent of the current page
				{ 
					$html = $this->drawMenu_UL($item->child_pages, $params, $newSlug,$html);
				}		
				
				$html.= " </li>\n";
			  
			  }
		  $html.= "</ul>\n";
		} 
		return (array_key_exists('as_array',$params) && $params['as_array']) ? $as_array : $html;
	}
	
    /**
	 * generate an array of parent pages making up a given page's "file path" location
	 *
	 * returns $array[page_label] => "page_slug"  for each parent page
	 *
	 */
    public function getBreadcrumbs($page_id)
    {
       $parent = $page_id;	 
	   $go = true;  
	   while ($go)
       {
	   $result = ORM::for_table( _table_pages)->find_one($parent);
	     
		 if($result)
         {
		 	$breadcrumbs[]= array("label"=>$result->label,"slug"=>$result->slug,"id"=>$result->id,"parent_id"=>$result->parent_id);
		 	$go = ( $result->parent_id == 0) ? false : true;
		 	$parent = $result->parent_id;
		 }
         else
         {
			 $breadcrumbs[]['ERROR! URI NOT FOUND ON SITE'] = "#__BROKEN_INTERNAL_LINK";
			 $go = false; // stop because there is an error
		 }
	   }	   
       return array_reverse($breadcrumbs);
    }		
	
	/**
    * return URI of given page
	*
	*/
   public function buildLink($page_id)
   {   
	  $breadcrumb_array = $this->getBreadcrumbs($page_id);
      $full_route = "";
	  foreach($breadcrumb_array as $crumb){
		$full_route.= ($crumb['slug'] != "/") ? "/" : ""; //don't add "/" pre-fix if the route in question is the homepage with a pre-set route of "/"
		$full_route.=  $crumb['slug'];	
	  }	  
     return $full_route; 
    }

	/**
	 * DRAW HTML Breadcrumbs as inline list
	 *
	 * page_id	INT	
	 * 
	 */
	public function drawBreadcrumbs($page_id,$delimiter=" &gt; ")
    {	
		$breadcrumbs = $this->getBreadcrumbs($page_id);
		$crumb_html = '';
		$crumb_levels = count($breadcrumbs);
		$crumb_path = ""; // root of website
		$i = 1;
		  foreach($breadcrumbs as $crumb){	
			if($i == $crumb_levels){			
				$crumb_html.= $crumb['label'];
			}else{
				$crumb_path.= '/'.$crumb['slug'];
				$crumb_html.= '<a href="'.$crumb_path.'">'.$crumb['label'].'</a>'.$delimiter;
			}
			
		  $i++;
		}
		
		return $crumb_html;
	}

}