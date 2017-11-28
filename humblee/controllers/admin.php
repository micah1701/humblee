<?php

class Core_Controller_Admin {

	function __construct(){
		
		if(!Core::auth('admin') && !Core::auth('developer')){ Core::forward("/user/login/?fwd=".Core::getURI() ); }
		
		if(isset($_POST['hmac_token']) && isset($_POST['hmac_key']))
		{
			if(!Core::check_hmac_pair($_POST['hmac_token'], $_POST['hmac_key']))
			{
				exit("Invalid Machine Authentication Key");
			}
		}
	    
        global $_uri_parts; // array set in index.php.  [0] is the name of the controller, [1] is this function's name, [2] or higher can be any other variable
        $this->_uri_parts = $_uri_parts;
        
        $this->tools = new Core_Model_Tools;
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
            $this->pagebody = "<h1>403 Forbidden</h1><h3>You do not have access to view this page</h3><p>If you believe this is an error, please see your site administrator.</p>";
            echo Core::view( _app_server_path .'humblee/views/admin/template.php',get_object_vars($this) );
            exit();
        }
    }
	
	public function index(){	
		$this->pagebody = Core::view( _app_server_path .'humblee/views/admin/index.php',get_object_vars($this) ); 	
		echo Core::view( _app_server_path .'humblee/views/admin/template.php',get_object_vars($this) ); 
	}
	
	public function pages(){
	    $this->require_role('pages');
        $this->extra_head_code = '<script type="text/javascript" src="'._app_path.'core/assets/js/libs/jquery.ui.nestedSortable.js"></script>';
        $this->extra_head_code.= '<script type="text/javascript" src="'._app_path.'core/assets/js/libs/tooltip.js"></script>';
        $this->extra_head_code.= '<script type="text/javascript" src="'._app_path.'core/assets/js/admin-pages.js"></script>';
       
		$this->pagebody = Core::view( _app_server_path .'humblee/views/admin/pages.php',get_object_vars($this) ); 	
		echo Core::view( _app_server_path .'humblee/views/admin/template.php',get_object_vars($this) ); 
	}
	
	public function edit(){
	    $this->require_role('content');
		
		//process $_POST data on save
		//there will either be one field named "content" or a bunch of arbitrary fields, listed in a field called "serialize_fields"
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
            exit("Fatal error. invalid page request");
        }

		$this->content = ORM::for_table( _table_content )->find_one( $this->_uri_parts[2] );
		if(!$this->content){ exit("ERROR: content not found"); }
		
		$pageObj = new Core_Model_Pages;
		$this->content_type = ORM::for_table( _table_content_types )->find_one($this->content->type_id);
		$this->page_data = ORM::for_table( _table_pages )->find_one( $this->content->page_id);
		$this->page_data->url = $pageObj->buildLink($this->content->page_id); // append object with additional variable
        $this->template_data = ORM::for_table( _table_templates)->find_one($this->page_data->template_id);
		
		$this->pagebody = Core::view( _app_server_path .'humblee/views/admin/edit.php',get_object_vars($this) ); 
        $this->extra_head_code = '<script type="text/javascript" src="'._app_path.'core/libs/ckeditor/ckeditor.js"></script>';
        $this->extra_head_code.= '<script type="text/javascript" src="'._app_path.'core/libs/ckeditor/adapters/jquery.js"></script>';
        $this->extra_head_code.= '<script type="text/javascript" src="'._app_path.'core/assets/js/admin-edit.js"></script>';
        
        $_SESSION['KCFINDER'] = array();
        $_SESSION['KCFINDER']['disabled'] = false;
        	
		echo Core::view( _app_server_path .'humblee/views/admin/template.php',get_object_vars($this) );
	}
	
	public function files()
	{
	    $this->require_role('content');
	    $_SESSION['KCFINDER'] = array();
        $_SESSION['KCFINDER']['disabled'] = false;    
	    $this->pagebody = Core::view( _app_server_path .'humblee/views/admin/filemanager.php',get_object_vars($this) ); 
	    echo Core::view( _app_server_path .'humblee/views/admin/template.php',get_object_vars($this) );
	}
	
	public function users(){
        $this->require_role('users');
        
		$this->pagebody = Core::view( _app_server_path .'humblee/views/admin/users.php',get_object_vars($this) ); 
		echo Core::view( _app_server_path .'humblee/views/admin/template.php',get_object_vars($this) ); 
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