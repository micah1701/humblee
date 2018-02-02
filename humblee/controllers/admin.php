<?php

class Core_Controller_Admin {

	function __construct(){
		
		if(!Core::auth(array('admin','developer')))
		{ 
            Core::forward("/user/login/?fwd=".Core::getURI() ); 
		}
		
        global $_uri_parts; // array set in index.php.  [0] is the name of the controller, [1] is this function's name, [2] or higher can be any other variable
        $this->_uri_parts = $_uri_parts;
        
        $this->tools = new Core_Model_Tools;
    }
    
    /**
     * return an object of the logged in user's data
     */
    private function getUser()
    {
        $userObj = new Core_Model_Users;
        return $userObj->profile();
    }
    
    /**
     * call this from any function below to ensure the user has the necessary role required to access the given page
     * 
     */
    private function require_role($role)
    {
        if(!Core::auth($role) && !Core::auth('developer') )
        {
         	header('HTTP/1.1 403 Forbidden');  
            $this->template_view = "<h1>403 Forbidden</h1><h3>You do not have access to view this page</h3><p>If you believe this is an error, please see your site administrator.</p>";
            echo Core::view( _app_server_path .'humblee/views/admin/templates/template.php',get_object_vars($this) );
            exit();
        }
    }
	
	public function index(){
	    $this->user = $this->getUser();
        $this->recent_contents = ORM::for_table(_table_content)
                                    ->raw_query("SELECT *
                                                    FROM "._table_content." AS topTable
                                                    WHERE revision_date != '0000-00-00 00:00:00' 
                                                    AND content != '' 
                                                    AND revision_date = (SELECT revision_date
                                                                        FROM "._table_content." 
                                                                        WHERE page_id = topTable.page_id 
                                                                        AND type_id = topTable.type_id 
                                                                        ORDER BY revision_date DESC 
                                                                        LIMIT 1) 
                                                    ORDER BY revision_date DESC
                                                    LIMIT 10")
                                    ->find_many();    
        $getcontentTypes = ORM::for_table(_table_content_types)->find_many();
        foreach($getcontentTypes as $getType)
        {
            $this->contentTypes[$getType->id] = $getType->name;
        }
	    
	    $this->extra_head_code = '<script type="text/javascript" src="'._app_path.'humblee/js/admin/index.js"></script>';
	    
		$this->template_view = Core::view( _app_server_path .'humblee/views/admin/index.php',get_object_vars($this) ); 	
		echo Core::view( _app_server_path .'humblee/views/admin/templates/template.php',get_object_vars($this) ); 
	}
	
	public function pages(){
	    $this->require_role('pages');
	    
	    //jquery ui library & nestedSortable extension
	    $this->extra_head_code = '<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js" integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=" crossorigin="anonymous"></script>';
	    $this->extra_head_code.= '<link rel="stylesheet" href="https://code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.css">';
	    $this->extra_head_code.= '<script src="'. _app_path .'node_modules/nestedSortable/jquery.mjs.nestedSortable.js"></script>';
	    
	    //css and js for the page manager
	    $this->extra_head_code.= '<link rel="stylesheet" type="text/css" href="'._app_path.'humblee/css/admin/pages.css">';
        $this->extra_head_code.= '<script type="text/javascript" src="'._app_path.'humblee/js/admin/pages.js"></script>';
        
        $this->access_roles = ORM::for_table(_table_roles)->where('role_type','access')->find_many();
		$this->template_view = Core::view( _app_server_path .'humblee/views/admin/pages.php',get_object_vars($this) ); 	
		echo Core::view( _app_server_path .'humblee/views/admin/templates/template.php',get_object_vars($this) ); 
	}
	
	public function edit(){
	    $this->require_role('content');
	    
	    //process $_POST data on save
		//there will either be one field named "content" or a bunch of arbitrary fields listed in a field called "serialize_fields"
		if(isset($_POST['content']) || isset($_POST['serialize_fields']) )
		{
			$content = new Core_Model_Content();
			$new_content = $content->saveContent($_POST);
			if($new_content !== false)
			{
				Core::forward('admin/edit/'.$new_content->id); //forward to new page				
			}
		} //end $_POST processing
		
        // if given content ID is not passed in URL but a page ID is passed
        // attempt to find the most recent block or create a new one
        if(!is_numeric($this->_uri_parts[2]) && isset($_GET['page_id']) && is_numeric($_GET['page_id']))
        {
            $content_type = (isset($_GET['content_type']) && is_numeric($_GET['content_type'])) ? $_GET['content_type'] : 1;
            $content = ORM::for_table(_table_content)
                        ->where('page_id',$_GET['page_id'])
                        ->where('type_id',$content_type)
                        ->order_by_desc('revision_date')
                        ->find_one();            
            if(!$content)
            {
                $content = ORM::for_table(_table_content)->create();
                $content->page_id = $_GET['page_id'];
                $content->type_id = $content_type;
                $content->revision_date = date("Y-m-d H:i:s");
                $content->updated_by = $_SESSION[session_key]['user_id'];
                $content->save();
            }
            Core::forward('admin/edit/'.$content->id);                        
        }

        if(!is_numeric($this->_uri_parts[2]))
        {
            exit('<h1>Fatal error. invalid page request</h1>');
        }

		$this->content = ORM::for_table( _table_content )->find_one( $this->_uri_parts[2] );
		if(!$this->content)
		{ 
            exit("<h1>ERROR: content not found</h1>");
		}
		
		$pageObj = new Core_Model_Pages;
		$contentObj = new Core_Model_Content;
		
		$this->revisions = $contentObj->listRevisions($this->content->page_id,$this->content->type_id);
		$this->content_type = ORM::for_table( _table_content_types )->find_one($this->content->type_id);
		$this->page_data = ORM::for_table( _table_pages )->find_one( $this->content->page_id);
		$this->page_data->url = $pageObj->buildLink($this->content->page_id); // append object with additional variable
        $this->template_data = ORM::for_table( _table_templates)->find_one($this->page_data->template_id);
        $this->allContentTypes = ORM::for_table(_table_content_types)->where_in('id',explode(',',$this->template_data->blocks))->order_by_asc('name')->find_many();	
		
		$this->template_view = Core::view( _app_server_path .'humblee/views/admin/edit.php',get_object_vars($this) ); 
        
        $this->extra_head_code = '<script type="text/javascript" src="'._app_path.'tools/dateformat.js"></script>';
        $this->extra_head_code.= '<script type="text/javascript" src="'._app_path.'humblee/js/admin/edit.js"></script>';
        
		echo Core::view( _app_server_path .'humblee/views/admin/templates/template.php',get_object_vars($this) );
	}
	
	public function media()
	{
	    $this->require_role('content');
	    
	    $this->access_roles = ORM::for_table(_table_roles)->where('role_type','access')->find_many();
	    
	    $this->template_view = Core::view( _app_server_path .'humblee/views/admin/media.php',get_object_vars($this) );
	    
	    $this->extra_head_code = '<script type="text/javascript" src="'._app_path.'tools/dateformat.js"></script>';
	    $this->extra_head_code.= '<script type="text/javascript" src="'._app_path.'tools/friendlyfilesize.js"></script>';
	    $this->extra_head_code.= '<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/1.5.13/clipboard.min.js"></script>';
	    $this->extra_head_code.= '<script type="text/javascript" src="'._app_path.'humblee/js/admin/media.js"></script>';
	    $this->extra_head_code.= '<link rel="stylesheet" type="text/css" href="'._app_path.'humblee/css/admin/media.css">';
	    echo Core::view( _app_server_path .'humblee/views/admin/templates/template.php',get_object_vars($this) );
	}
	
	public function users(){
        $this->require_role('users');
        
		$this->template_view = Core::view( _app_server_path .'humblee/views/admin/users.php',get_object_vars($this) ); 
		echo Core::view( _app_server_path .'humblee/views/admin/templates/template.php',get_object_vars($this) ); 
	}
    
    public function blocks(){
        $this->require_role('designer');
        $params = array("id"=> (isset($this->_uri_parts[2])) ? $this->_uri_parts[2] : false,
                        "table"=> _table_content_types,
                        "view" => _app_server_path."humblee/views/admin/blocks.php", 
                        "post" => (isset($_POST) && count($_POST) > 0) ? $_POST : false,
                        "allow_html" =>true,
                        "validation" => array('name'=>array('if'=>'$val == ""','error_message'=>'Name field cannot be blank'),
                                              'objectkey'=>array('if'=>'$val == ""','error_message'=>'objectKey field cannot be blank')
                                        ),
                        "post_ignore" => array("submit"),
                        "crud_all_order_by" => "name"
                        );
                        
        $this->extra_head_code = '<script type="text/javascript" src="'._app_path.'core/assets/js/admin-blocks.js"></script>';
        $this->tools->CRUD($params,$this ); 
    }
	
    public function templates(){
        $this->require_role('designer');
		
		if(isset($_POST) && count($_POST) > 0 )
		{
			$_POST['blocks'] = (isset($_POST['blocks'])) ? implode(",", $_POST['blocks']) : '';
			$_POST['dynamic_uri'] = (isset($_POST['dynamic_uri'])) ? $_POST['dynamic_uri'] : 0;
			
			if(isset($_POST['page_type']))
			{	
				switch ($_POST['page_type']) {
					case 'view' :
						$_POST['page_meta'] = $_POST['default_view'];
					break;
					case 'controller' :
						$page_meta['controller'] = $_POST['controller'];
						$page_meta['action'] = $_POST['controller_action'];
						$_POST['page_meta'] = serialize( $page_meta );
					break;
					default:
						$_POST['page_type'] = 'default';
						$_POST['page_meta'] = 'tierpage';
				}
			}
		}
        $params = array("id"=> (isset($this->_uri_parts[2])) ? $this->_uri_parts[2] : false,
                        "table"=> _table_templates,
                        "view" => _app_server_path."humblee/views/admin/template_tool.php", 
                        "post" => (isset($_POST) && count($_POST) > 0) ? $_POST : false,
                        "allow_html" =>true,
                        "validation" => array('name'=>array('if'=>'$val == ""','error_message'=>'Name field cannot be blank')
                                        ),
                        "post_ignore" => array("submit","controller","controller_action","default_view"),
                        "crud_all_order_by" => "name"
                        );
        $this->tools->CRUD($params,$this ); 
    }
    
    public function spending(){
        $this->require_role('designer');
        $params = array("id"=> (isset($this->_uri_parts[2])) ? $this->_uri_parts[2] : false,
                        "table"=> "spending",
                        "view" => _app_server_path."humblee/views/admin/spending.php", 
                        "post" => (isset($_POST) && count($_POST) > 0) ? $_POST : false,
                        "allow_html" =>true,
                        "validation" => array('amount'=>array('if'=>'$val == ""','error_message'=>'Name field cannot be blank')
                                              ),
                        "post_ignore" => array("submit"),
                        "crud_all_order_by" => "datetime"
                        );
                        
        $this->tools->CRUD($params,$this ); 
    }
}