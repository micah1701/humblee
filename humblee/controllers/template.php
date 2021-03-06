<?php

class Core_Controller_Template {

	/**
	 * __construct()
	 * Set info about given page
	 *
	 * Pre-sets the following objects:
	 * $this->page		Object with data about the page
	 * $this->template  Ojbect with data about the template this page is using
	 * $this->content	Associative Array of Objects with any related content (Array Key is the Content Type "Name")
	 *
	 */
	function __construct()
	{
		//get information about what this link does
		$pageObj = new Core_Model_Pages;
		$this->page = $pageObj->getPagefromURL(Core::getURIparts());

		if(!$this->page){  // if page doesn't exist, set some dummy values
			$this->page = new stdClass();
			$this->page->required_role = 0;
			$this->page->id = '';
            $this->page->template_id = 1; // use the default template
		}

		//check if page requires authorization to view
		if($this->page->required_role != 0 && !Core::auth($this->page->required_role))
		{
			if(!isset($_SESSION[session_key]['user_id']))
			{
				// if not logged in at all, forward to login page
				Core::forward("user/login/?fwd=".$uri);
			}
			else
			{
				header('HTTP/1.1 403 Forbidden');
				exit( "<h1>403 Forbidden</h1>You do not have permission to view this page");
			}
		}

		//get any LIVE content entries related to this page
		$contentObj = new Core_Model_Content;
		$contents = $contentObj->findContent($this->page->id);

		//check for "preview mode" content override
		if(	isset($_GET['preview'])
		    && Core::auth('admin') // require "admin" role to view preview.  comment out this line for open access.
		){
			if(preg_match("/[^0-9,]/",$_GET['preview']))
			{
				exit('invalid GET parameters');
			}
			$preview_ids = explode(",",$_GET['preview']);
			$getPreviewContent = ORM::for_table( _table_content )
						  			->join( _table_content_types, array( _table_content.".type_id","=", _table_content_types.".id") )
						  			->where_in( _table_content.'.id', $preview_ids)
									->find_many();
			//override the associative array of content objects
			foreach($getPreviewContent as $prevContent){
				$contents[$prevContent->objectkey] = $prevContent;

				if($prevContent->input_type == "markdown")
				{
					$Parsedown = new Parsedown();
					$contents[$prevContent->objectkey]['content'] = $Parsedown->instance()->text($prevContent->content);
				}
			}
		}

		//set content for use by class
		if(!isset($contents))
		{
			$this->content = false;
		}
		else
		{
			$this->content = $contents;
		}

		//don't cache personalized content
		$personalizedContent = false;
		foreach($this->content as $contentBlock)
		{
			if($contentBlock->p13n_id != "" && $contentBlock->p13n_id != 0)
			{
				$personalizedContent = true;
				break;
			}
		}
		if($personalizedContent)
		{
			header("Cache-Control: private");
		}

        //get data about the template being used
        $this->template = ORM::for_table( _table_templates)->find_one($this->page->template_id);

        // if page data was never found for the given URL, override the "page_type"
        if($this->page->id == "")
        {
            $this->template->page_type = "404";
        }
	}

	//save the template path in a session var so it can be set from a child class
	public function setTemplatePath($path)
	{
		$_SESSION[session_key]['templatepath'] = $path;
	}

	//save an arbitrary variable to the session from a child controller for global use
	public function setSessionVar($key,$value)
	{
		$_SESSION[session_key][$key] = $value;
	}

	public function index()
	{
		$this->setTemplatePath('application/views/templates/template.php');

		switch($this->template->page_type) {

			case "controller" :

				$controller_data = unserialize($this->template->page_meta);
				$controller = "Controller_".ucfirst($controller_data['controller']);

				$sub_controller = new $controller();
				$action = $controller_data['action'];
				$this->template_view = $sub_controller->$action( get_object_vars($this));
			break;

			case "view" :

				$this->template_view = Core::view( _app_server_path."application/views/".$this->template->page_meta.".php",get_object_vars($this));
			break;

			case "default" :

				$this->template_view = Core::view( _app_server_path.'application/views/default.php',get_object_vars($this));
			break;

			case "link" :

				Core::forward($this->template->page_meta);
			break;

			case "301" :

				Core::forward($this->template->page_meta,"301 Moved Permanently");
			break;

			default :
				header('HTTP/1.1 404 Not Found');
				$this->template_view =  Core::view( _app_server_path."application/views/404.php",get_object_vars($this));
		}

		echo Core::view( _app_server_path .$_SESSION[session_key]['templatepath'] ,get_object_vars($this) );
	}

}