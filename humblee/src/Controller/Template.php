<?php

declare(strict_types=1);

namespace Humblee\Controller;

use Humblee\Foundation\Core;
use Humblee\Model\Pages;
use Humblee\Model\Content;

/**
 * Default controller — handles rendering of all standard site pages
 *
 * Resolves the URL to a page record, checks authorization, loads content blocks,
 * determines page type and renders the appropriate view.
 *
 * Pre-sets the following properties:
 *   $this->page      Object with data about the page
 *   $this->template  Object with data about the template this page uses
 *   $this->content   Associative Array of Objects with live content (keyed by slot_key)
 */
class Template
{

	public object|false $page          = false;
	public array|false  $content       = false;
	public object|false $template      = false;
	public string|false $template_view = false;

	public function __construct()
	{
		$pageObj = new Pages;
		$this->page = $pageObj->getPagefromURL(Core::getURIparts());

		if (!$this->page) {
			$this->page = new \stdClass();
			$this->page->required_role = 0;
			$this->page->id = '';
			$this->page->template_id = 1;
		}

		if ($this->page->required_role != 0 && !Core::auth($this->page->required_role)) {
			if (!isset($_SESSION[session_key]['user_id'])) {
				Core::forward("user/login/?fwd=" . Core::getURI());
			} else {
				header('HTTP/1.1 403 Forbidden');
				exit("<h1>403 Forbidden</h1>You do not have permission to view this page");
			}
		}

		$contentObj = new Content;
		$contents = $contentObj->findContent((int)$this->page->id);

		if (isset($_GET['preview']) && Core::auth('admin')) {
			if (preg_match("/[^0-9,]/", $_GET['preview'])) {
				exit('invalid GET parameters');
			}
			$preview_ids = explode(",", $_GET['preview']);
			$getPreviewContent = \ORM::for_table(_table_content)
				->select(_table_content . '.*')
				->select(_table_content_types . '.input_type', 'input_type')
				->select(_table_template_blocks . '.slot_key', 'slot_key')
				->join(_table_content_types, [_table_content . ".type_id", "=", _table_content_types . ".id"])
				->join(_table_template_blocks, [_table_content . ".template_block_id", "=", _table_template_blocks . ".id"])
				->where_in(_table_content . '.id', $preview_ids)
				->find_many();
			foreach ($getPreviewContent as $prevContent) {
				$key = $prevContent->slot_key;
				$contents[$key] = $prevContent;

				if ($prevContent->input_type === "markdown") {
					$Parsedown = new \Parsedown();
					$contents[$key]['content'] = $Parsedown->instance()->text($prevContent->content);
				}
			}
		}

		$this->content = !empty($contents) ? $contents : false;

		$personalizedContent = false;
		if (is_array($this->content)) {
			foreach ($this->content as $contentBlock) {
				if ($contentBlock->p13n_id !== "" && $contentBlock->p13n_id != 0) {
					$personalizedContent = true;
					break;
				}
			}
		}
		if ($personalizedContent) {
			header("Cache-Control: private");
		}

		$this->template = \ORM::for_table(_table_templates)->find_one($this->page->template_id);

		if ($this->page->id === "") {
			$this->template->page_type = "404";
		}
	}

	/**
	 * Save the template path in session so it can be set from a child class
	 */
	public function setTemplatePath(string $path): void
	{
		$_SESSION[session_key]['templatepath'] = $path;
	}

	/**
	 * Save an arbitrary variable to the session from a child controller for global use
	 */
	public function setSessionVar(string $key, mixed $value): void
	{
		$_SESSION[session_key][$key] = $value;
	}

	public function index(): void
	{
		$this->setTemplatePath('application/views/templates/template.php');

		switch ($this->template->page_type) {

			case "controller":

				$controller_data = json_decode($this->template->page_meta, true) ?: @unserialize($this->template->page_meta);
				$controller = "App\\Controller\\" . ucfirst($controller_data['controller']);

				$sub_controller = new $controller();
				$action = $controller_data['action'];
				$this->template_view = $sub_controller->$action(get_object_vars($this));
				break;

			case "view":

				$this->template_view = Core::view(_app_server_path . "application/views/" . $this->template->page_meta . ".php", get_object_vars($this));
				break;

			case "default":

				$this->template_view = Core::view(_app_server_path . 'application/views/default.php', get_object_vars($this));
				break;

			case "link":

				Core::forward($this->template->page_meta);
				break;

			case "301":

				Core::forward($this->template->page_meta, "301 Moved Permanently");
				break;

			default:
				header('HTTP/1.1 404 Not Found');
				$this->template_view = Core::view(_app_server_path . "application/views/404.php", get_object_vars($this));
		}

		echo Core::view(_app_server_path . $_SESSION[session_key]['templatepath'], get_object_vars($this));
	}
}
