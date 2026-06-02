<?php

declare(strict_types=1);

namespace Humblee\Controller;

use Humblee\Foundation\Core;
use Humblee\Model\Users;
use Humblee\Model\Tools;
use Humblee\Model\Content;
use Humblee\Model\Pages;
use Humblee\Model\Media;

/**
 * Core AJAX/XHR request controller
 * Handles CMS-internal AJAX operations: auth, pages, content, media, users
 */
class Request extends Xhr
{

	/**
	 * Send an SMS verification token to the user's cellphone (for phone number validation)
	 */
	public function verify_sms_send(): void
	{
		if (!$_ENV['config']['TWILIO_Enabled']) {
			$this->json(["error" => "Site not configured to use SMS"]);
		}

		$this->require_role('login');

		if (!isset($_POST['cellphone']) || !is_numeric($_POST['cellphone']) || strlen($_POST['cellphone']) != 10) {
			$this->json(["error" => "Invalid or missing 10 digit cellphone number"]);
		}
		$userID = $_SESSION[session_key]['user_id'];

		$token = rand(10000, 99999);

		$previousValidation = \ORM::for_table(_table_validation)
			->where('new_value', $_POST['cellphone'])
			->where('user_id', $userID)
			->where('type', 'sms')
			->find_one();
		if (!$previousValidation) {
			$validation = \ORM::for_table(_table_validation)->create();
			$validation->user_id = $_SESSION[session_key]['user_id'];
			$validation->type = "sms";
		} else {
			if (strtotime($previousValidation->token_created) >= strtotime("-1 minutes")) {
				exit("Error: can not send more than 1 SMS per minute");
			} elseif (strtotime($previousValidation->token_created) >= strtotime("-10 minutes")) {
				$token = $previousValidation->token;
			}

			$validation = $previousValidation;
			$validation->token_accepted = '1970-01-01 00:00:00';
		}

		$tools = new Tools;
		$txtmsg = $_ENV['config']['domain'] . " log in code: " . $token;
		$sms_status = $tools->sendSMS($_POST['cellphone'], $txtmsg);

		if ($sms_status['success']) {
			$validation->new_value = $_POST['cellphone'];
			$validation->token = $token;
			$validation->token_created = gmdate("Y-m-d H:i:s");
			$validation->message_id = $sms_status['message_id'];
			$validation->save();
			echo "success";
		} else {
			echo "error";
		}
	}

	/**
	 * Send an SMS login token for two-factor authentication
	 */
	public function sms_login_code(): void
	{
		if (!$_ENV['config']['TWILIO_Enabled']) {
			$this->json(["error" => "Site not configured to use SMS"]);
		}
		if (!isset($_SESSION[session_key]['sms_login_email']) || $_SESSION[session_key]['sms_login_email'] == "") {
			$this->json(["error" => "Invalid Request"]);
		}

		$username_column = (filter_var($_SESSION[session_key]['sms_login_email'], FILTER_VALIDATE_EMAIL)) ? 'email' : 'username';

		$user = \ORM::for_table(_table_users)
			->where($username_column, $_SESSION[session_key]['sms_login_email'])
			->where('active', 1)
			->find_one();
		if (!$user) {
			exit("Invalid User Account");
		}
		if ($user->cellphone == "" || !is_numeric($user->cellphone)) {
			exit("No phone number associated with this account.");
		}
		if ($user->cellphone_validated != 1) {
			exit("Phone number has not yet been validated.");
		}

		if (
			isset($_SESSION[session_key]['login_token_expires']) &&
			time() < strtotime("-570 seconds", $_SESSION[session_key]['login_token_expires'])
		) {
			exit("Please wait at least 30 seconds before re-sending code");
		}

		if (
			isset($_SESSION[session_key]['login_token']) &&
			isset($_SESSION[session_key]['login_token_expires']) &&
			time() < $_SESSION[session_key]['login_token_expires']
		) {
			$token = $_SESSION[session_key]['login_token'];
		} else {
			$start_point = rand(0, 10);
			$token = strtoupper(substr(md5((string)rand(10000, 999999)), $start_point, 5));

			if (!isset($_SESSION[session_key])) {
				$_SESSION[session_key] = [];
			}

			$_SESSION[session_key]['login_token'] = $token;
		}

		$_SESSION[session_key]['login_token_expires'] = strtotime("+10 minutes");

		$tools = new Tools;
		$txtmsg = $_ENV['config']['domain'] . " log in code: " . $token;
		$sms_status = $tools->sendSMS($user->cellphone, $txtmsg);
		echo ($sms_status['success']) ? "success" : "Message could not be sent.";
	}

	/**
	 * Validate a user-submitted SMS login code
	 */
	public function sms_login(): void
	{
		if (!$_ENV['config']['TWILIO_Enabled']) {
			$this->json(["error" => "Site not configured to use SMS"]);
		}
		if (!isset($_SESSION[session_key]['sms_login_email']) || $_SESSION[session_key]['sms_login_email'] == "") {
			$this->json(["error" => "You are not authorized to make this request."]);
		}

		$this->require_hmac();

		if (!isset($_POST['sms_token']) || strlen($_POST['sms_token']) != 5) {
			$this->json(["error" => "Missing or malformed verification token"]);
		}

		$users = new Users;
		$login = $users->logIn($_SESSION[session_key]['sms_login_email'], $_POST['sms_token'], true);
		if ($login['access_granted'] === true) {
			$this->json(["success" => true]);
		} else {
			$this->json(["error" => $login['error']]);
		}
	}

	public function recoveryRequestVerification(): void
	{
		if (isset($_SESSION[session_key]['recovery']['message_sent']) && $_SESSION[session_key]['recovery']['message_sent']) {
			$this->json(["success" => false, "error" => "Access Code Already Sent"]);
		}
		if (!isset($_SESSION[session_key]['recovery']['user_id']) || !isset($_SESSION[session_key]['recovery']['token'])) {
			$this->json(["success" => false, "error" => "You session has expired. Please restart the password recovery process"]);
		}
		$user = \ORM::for_table(_table_users)->find_one($_SESSION[session_key]['recovery']['user_id']);
		if (!$user) {
			$this->json(["success" => false, "error" => "User account not found"]);
		}
		if (isset($_POST['method']) && $_POST['method'] == "sms") {
			if (!$_ENV['config']['TWILIO_Enabled']) {
				$this->json(["error" => "Site not configured to use SMS"]);
			}
			$tools = new Tools;
			$txtmsg = $_ENV['config']['domain'] . " access code: " . $_SESSION[session_key]['recovery']['token'];
			$sms_status = $tools->sendSMS($user->cellphone, $txtmsg);
			$_SESSION[session_key]['recovery']['message_sent'] = true;
			$_SESSION[session_key]['recovery']['method'] = "sms";
			$this->json($sms_status);
		} elseif (isset($_POST['method']) && $_POST['method'] == "email") {
			$userObj = new Users;
			if ($userObj->forgotPasswordVerifyEmail($user->email, $user->name, $_SESSION[session_key]['recovery']['token'])) {
				$_SESSION[session_key]['recovery']['message_sent'] = true;
				$_SESSION[session_key]['recovery']['method'] = "email";
				$this->json(["success" => true]);
			} else {
				$this->json(["success" => false, "error" => "There was a system problem generating your recovery e-mail"]);
			}
		} else {
			$this->json(["success" => false, "error" => "Invalid Request"]);
		}
	}

	public function recoverySubmitVerification(): void
	{
		if (!isset($_SESSION[session_key]['recovery']['message_sent']) || !$_SESSION[session_key]['recovery']['message_sent']) {
			$this->json(["success" => false, "error" => "Your session has expired. Please restart the password recovery process."]);
		}
		if (!isset($_SESSION[session_key]['recovery']['user_id']) || !isset($_SESSION[session_key]['recovery']['token'])) {
			$this->json(["success" => false, "error" => "You session has expired. Please restart the password recovery process."]);
		}
		if (!isset($_POST['accessCode']) || $_POST['accessCode'] == "") {
			$this->json(["success" => false, "error" => "Missing Access Code"]);
		}

		if (trim(strtolower($_POST['accessCode'])) != strtolower($_SESSION[session_key]['recovery']['token'])) {
			$this->json(["success" => false, "error" => "Invalid Access code"]);
		} else {
			$_SESSION[session_key]['recovery']['verified'] = true;
			$this->json(["success" => true]);
		}
	}

	public function recoveryCancel(): void
	{
		unset($_SESSION[session_key]['recovery']);
		$this->json(["success" => true]);
	}

	/**
	 * Get list of pages with editable content
	 */
	public function loadContentMenu(): void
	{
		$this->require_role('content');
		$pageObj = new Pages;
		$menu = $pageObj->getPages(['active_only' => false, 'display_in_sitemap_only' => false]);
		$li_format = function ($item, $slug, $class) {
			return '<a href="' . _app_path . 'admin/edit/?page_id=' . $item->thisid . '">' . $item->label . '</a>';
		};
		echo $pageObj->drawMenu_UL($menu, ['li_format' => $li_format, 'id_label' => 'contentNav_']);
	}

	/**
	 * Return table of pages for the page manager
	 */
	public function loadPagesTable(): void
	{
		$this->require_role('pages');
		$pages = new Pages;
		$all_pages = $pages->getPages(['active_only' => false, 'display_in_sitemap_only' => false]);
		$li_format = function ($item, $slug, $class) {
			return '<div class="pages_menu_item" data="' . $item->thisid . '"><a ' . $class . ' title="' . $slug . '">' . $item->label . '</a></div>';
		};
		echo $pages->drawMenu_UL($all_pages, ['li_format' => $li_format, 'id_label' => 'pageID_']);
	}

	/**
	 * Return properties for a given page by ID
	 */
	public function getPageProperties(): void
	{
		$this->require_role('pages');
		if (!isset($_POST['page_id']) || !is_numeric($_POST['page_id'])) {
			$this->json(['error' => 'Invalid or missing page ID']);
		}

		$page = \ORM::for_table(_table_pages)->find_one($_POST['page_id']);
		if (!$page) {
			$this->json(['error' => 'Page data not found']);
		}

		$active = ($page->active == 0) ? false : true;
		$searchable = ($page->searchable == 0) ? false : true;
		$display_in_sitemap = ($page->display_in_sitemap == 0) ? false : true;

		$checkTemplate = \ORM::for_table(_table_templates)->select('available')->find_one($page->template_id);

		$array = [
			"success" => true,
			"label" => $page->label,
			"slug" => $page->slug,
			"template_id" => $page->template_id,
			"required_role" => $page->required_role,
			"template_disabled" => ($checkTemplate->available == 0 && !Core::auth(['designer', 'developer'])) ? 1 : 0,
			"active" => $active,
			"display_in_sitemap" => $display_in_sitemap,
			"searchable" => $searchable
		];

		$this->json($array);
	}

	/**
	 * Save properties for a given page
	 */
	public function setPageProperties(): void
	{
		$this->require_role('pages');
		$pages = new Pages;
		$page = $pages->add_or_update("update", $_POST);
		if (is_numeric($page)) {
			$this->json(['success' => true, 'page_id' => $page]);
		}

		$this->json(['error' => $page]);
	}

	public function add_page(): void
	{
		$this->require_role('pages');
		$pages = new Pages;
		$newPage = $pages->add_or_update("add", $_POST);
		if (is_numeric($newPage)) {
			$this->json(['success' => true, 'page_id' => $newPage]);
		}

		$this->json(['error' => $newPage]);
	}

	/**
	 * Delete a page and all of its content
	 */
	public function delete_page(): void
	{
		$this->require_role('pages');
		$pages = new Pages;
		$deletePage = $pages->add_or_update("delete", $_POST);
		if ($deletePage == "success") {
			$contents = \ORM::for_table(_table_content)->where('page_id', $_POST['page_id'])->find_many();
			foreach ($contents as $content) {
				$content->delete();
			}
			$this->json(['success' => true]);
		}

		$this->json(['error' => $deletePage]);
	}

	/**
	 * Update page order and hierarchy from drag-drop UI
	 * Expects $_POST['list_order'] as JSON: {"pageID_5": 0, "pageID_12": 1, ...}
	 */
	public function order_pages(): void
	{
		$this->require_role('pages');
		if (!isset($_POST['list_order']) || $_POST['list_order'] == "") {
			exit("Missing list order post data");
		}

		$list_order = json_decode(urldecode($_POST['list_order']), true);
		if (!is_array($list_order)) {
			exit("Invalid list order data");
		}

		$page_id = [];
		foreach ($list_order as $domId => $level) {
			$id = (int) str_replace('pageID_', '', $domId);
			if ($id > 0) {
				$page_id[$id] = (int) $level;
			}
		}

		$current_parent = 0;
		$last_level = 0;
		$last_id = 0;
		$parent_level = [];
		$order_pointer = [];
		foreach ($page_id as $id => $level) {
			if ($level > $last_level) {
				$parent_level[$last_level] = $current_parent;
				$current_parent = $last_id;
			}
			if ($level < $last_level) {
				$current_parent = $parent_level[$level];
			}
			if ($level == 0) {
				$current_parent = 0;
			}

			$order_pointer[$level] = (isset($order_pointer[$level])) ? $order_pointer[$level] + 1 : 0;

			$orderpage = \ORM::for_table(_table_pages)->find_one($id);
			$orderpage->parent_id = $current_parent;
			$orderpage->display_order = $order_pointer[$current_parent];
			$orderpage->save();

			$last_id = $id;
			$last_level = $level;
		}

		$this->json(['success' => true]);
	}

	public function p13n_order_priorities()
	{
		$this->require_role('designer');
		if (!isset($_POST['list_order']) || !is_array($_POST['list_order'])) {
			$this->json(['success' => false, 'error' => 'malformed request']);
		}
		foreach ($_POST['list_order'] as $priority => $persona_domID) {
			$domID_parts = explode('_', $persona_domID);
			$persona_id = end($domID_parts);
			$p13n = \ORM::for_table(_table_content_p13n)->find_one($persona_id);

			if (!$p13n) {
				$this->json(['success' => false, 'error' => 'critical error: one or more persona\'s were not updated']);
			}

			$p13n->priority = $priority;
			$p13n->save();
		}

		$this->json(['success' => true]);
	}

	/**
	 * Return the most recent content revision date and status for a given block
	 */
	public function latestRevisionDate(): void
	{
		$this->require_role(['content', 'publish']);
		if (!isset($_POST['content_type']) || !is_numeric($_POST['content_type']) || !isset($_POST['page_id']) || !is_numeric($_POST['page_id'])) {
			$this->json(['error' => 'Missing required parameters']);
		}
		$contentObj = new Content;
		$content = $contentObj->listRevisions((int)$_POST['page_id'], (int)$_POST['content_type'], (int)$_POST['p13n_id'], 1);

		if (!$content) {
			$this->json(['error' => 'could not confirm previously saved content']);
		}

		$content = $content[0];
		$latestRevision = ['revision_date' => $content->revision_date, 'live' => $content->live, 'name' => $content->name];
		$this->json(['success' => true, 'content' => $latestRevision]);
	}

	public function listMediaFolders(): void
	{
		$this->require_role(['content', 'media']);
		$media = new Media;
		$this->json($media->listFolders());
	}

	public function listMediaFilesByFolder(): void
	{
		$this->require_role(['content', 'media']);
		if (!isset($_GET['folder']) || !is_numeric($_GET['folder'])) {
			$result['error'] = "missing or invalidfolder ID";
		}
		$media = new Media;
		$response = ['success' => true, 'files' => $media->listFilesByFolder((int)$_GET['folder'])];
		$this->json($response);
	}

	public function updateMediaName(): void
	{
		$this->require_role('media');
		if (!isset($_POST['type']) || !isset($_POST['record']) || !is_numeric($_POST['record'])) {
			$this->json(['error' => 'invalid request']);
		}
		$record = false;
		if ($_POST['type'] == "folder_name") {
			$record = \ORM::for_table(_table_media_folders)->where('id', $_POST['record'])->find_one();
		}
		if ($_POST['type'] == "file_name") {
			$record = \ORM::for_table(_table_media)->where('id', $_POST['record'])->find_one();
		}

		if (!$record) {
			$this->json(['error' => 'record not found']);
			return;
		}

		$record->name = $_POST['value'];
		$record->save();
		$this->json(['success' => true]);
	}

	public function updateMediaRole(): void
	{
		$this->require_role('media');
		if (!isset($_POST['file_id']) || !is_numeric($_POST['file_id']) || !isset($_POST['required_role']) || !is_numeric($_POST['required_role'])) {
			exit("Invalid or missing file ID or role type");
		}
		$file = \ORM::for_table(_table_media)->find_one($_POST['file_id']);
		if (!$file) {
			exit("File record not found");
		}
		$file->required_role = (int)$_POST['required_role'];
		$file->save();
		$this->json(['success' => true]);
	}

	public function encryptMedia(): void
	{
		$this->require_role('media');
		if (!isset($_POST['file_id']) || !is_numeric($_POST['file_id']) || !isset($_POST['action'])) {
			exit("Invalid or missing file ID or action");
		}

		$file = \ORM::for_table(_table_media)->find_one($_POST['file_id']);
		if (!$file) {
			exit("File record not found");
		}

		$file_location = _app_server_path . 'storage/' . $file->filepath;
		$file_content = file_get_contents($file_location);

		if ($file_content === false) {
			exit("The file system could not read the requested resource");
		}

		$crypto = new \Humblee\Model\Crypto;
		if ($_POST['action'] == "encrypt") {
			$encrypt = $crypto->encrypt($file_content);
			if ($encrypt === false) {
				exit("Error encrypting file");
			}
			if (!file_put_contents($file_location, $encrypt)) {
				exit("Could not save encrypted text to file");
			} else {
				$file->encrypted = 1;
				$file->save();
				$this->json(["success" => true]);
			}
		} elseif ($_POST['action'] == "decrypt") {
			$decrypt = $crypto->decrypt($file_content);
			if ($decrypt === false) {
				exit("Error decrypting file");
			}
			if (!file_put_contents($file_location, $decrypt)) {
				exit("Could not save decrypted text to file");
			} else {
				$file->encrypted = 0;
				$file->save();
				$this->json(["success" => true]);
			}
		}

		$this->json(["success" => false, "error" => "malformed request"]);
	}

	public function deleteMediaFile(): void
	{
		$this->require_role('media');
		if (!isset($_POST['file_id']) || !is_numeric($_POST['file_id'])) {
			exit("Invalid or missing file ID");
		}
		$mediaObj = new Media;
		$delete = $mediaObj->deleteFile((int)$_POST['file_id']);

		if ($delete !== true) {
			$this->json(["success" => false, "error" => $delete]);
		}

		$this->json(["success" => true]);
	}

	public function createMediaFolder(): void
	{
		$this->require_role('media');

		$folder = \ORM::for_table(_table_media_folders)->create();
		$folder->name = (isset($_POST['name'])) ? $_POST['name'] : "New Folder";
		$folder->parent_id = (isset($_POST['parent_id']) && is_numeric($_POST['parent_id'])) ? $_POST['parent_id'] : 0;
		$folder->save();

		$this->json(["success" => true, "folder_id" => $folder->id()]);
	}

	public function deleteMediaFolder(): void
	{
		$this->require_role('media');
		if (!isset($_POST['folder_id']) || !is_numeric($_POST['folder_id'])) {
			exit("Invalid or missing file ID");
		}

		$children = \ORM::for_table(_table_media_folders)->where('parent_id', (int)$_POST['folder_id'])->find_many();
		if ($children) {
			$this->json(["success" => false, "errors" => "This folder has subfolders and can not be deleted. Delete the child folders first!"]);
		}

		$files = \ORM::for_table(_table_media)->where('folder', (int)$_POST['folder_id'])->find_many();
		$mediaObj = new Media;
		$errors = [];
		foreach ($files as $file) {
			$delete = $mediaObj->deleteFile($file);
			if ($delete !== true) {
				$errors[] = $delete;
			}
		}

		if (count($errors) > 0) {
			$this->json(["success" => false, "errors" => $errors]);
		}

		$folder = \ORM::for_table(_table_media_folders)->find_one($_POST['folder_id']);
		if (!$folder) {
			exit("Folder record not found");
		}

		$folder->delete();
		$this->json(['success' => true]);
	}

	private function reArrayFiles(array &$file_post): array
	{
		$file_ary = [];
		$file_count = count($file_post['name']);
		$file_keys = array_keys($file_post);

		for ($i = 0; $i < $file_count; $i++) {
			foreach ($file_keys as $key) {
				$file_ary[$i][$key] = $file_post[$key][$i];
			}
		}

		return $file_ary;
	}

	public function uploadMediaFiles(): void
	{
		$this->require_role('media');
		$errors = [];

		if (!$_FILES['uploaderFiles']) {
			$errors[] = "No Files Uploaded";
			$files = [];
		} else {
			$files = $this->reArrayFiles($_FILES['uploaderFiles']);
		}

		$totalFiles = count($files);
		$savedFiles = 0;

		foreach ($files as $file) {
			$cleanFilename = filter_var($file['name'], FILTER_SANITIZE_URL);
			$cleanFilename = str_replace(" ", "-", $cleanFilename);

			$fileRecord = \ORM::for_table(_table_media)->create();
			$fileRecord->folder = (isset($_POST['folder_id']) && is_numeric($_POST['folder_id'])) ? (int)$_POST['folder_id'] : 0;
			$fileRecord->name = $cleanFilename;
			$fileRecord->encrypted = 0;
			$fileRecord->crypto_nonce = "";
			$fileRecord->required_role = 0;
			$fileRecord->size = $file['size'];
			$fileRecord->type = $file['type'];
			$fileRecord->upload_by = (isset($_SESSION[session_key]['user_id'])) ? (int)$_SESSION[session_key]['user_id'] : 0;
			$fileRecord->upload_date = gmdate("Y-m-d H:i:s");

			$nameParts = explode(".", $file['name']);
			$storageName = gmdate("YmdHis") . substr(md5($cleanFilename), 0, 6) . "." . strtolower(array_pop($nameParts));

			if ($file['error'] == 0) {
				if (
					stripos($file['type'], 'image') !== false && $_ENV['config']['TINYPNG_Enabled']
					&& isset($_POST['useCompression']) && $_POST['useCompression'] == 1
				) {
					try {
						\Tinify\setKey($_ENV['config']['TINYPNG_API_Key']);
						$source = \Tinify\fromFile($file['tmp_name']);
						$source->toFile(_app_server_path . "storage/" . $storageName);

						$fileRecord->filepath = $storageName;
						$fileRecord->size = $source->result()->size();
						$fileRecord->type = $source->result()->mediaType();
						$fileRecord->save();
						$savedFiles++;
					} catch (\Tinify\Exception $e) {
						$errors[] = $e->getMessage();
						$fileRecord->delete();
					}
				} else {
					if (move_uploaded_file($file['tmp_name'], _app_server_path . "storage/" . $storageName)) {
						$fileRecord->filepath = $storageName;
						$fileRecord->save();
						$savedFiles++;
					} else {
						$errors[] = 'could not store file ' . $file['name'];
						$fileRecord = null;
						continue;
					}
				}
			} else {
				$errors[] = ["error" => 'there was a problem with the file: ' . $file['name'], "code" => $file['error']];
				$fileRecord = null;
				continue;
			}
		}

		$success = ($savedFiles > 0) ? true : false;
		$this->json(["success" => $success, "errors" => $errors, "filesReceived" => $totalFiles, "filesSaved" => $savedFiles]);
	}

	public function removeUser(): void
	{
		$this->require_role('users');

		if (!isset($_POST['userID']) || !is_numeric($_POST['userID'])) {
			$this->json(["success" => false, "error" => "invalid or missing user ID"]);
		}
		$userObj = new Users;
		if ($userObj->deleteUser($_POST['userID'])) {
			$this->json(["success" => true]);
		} else {
			$this->json(["success" => false, "error" => "Could not delete the user"]);
		}
	}

	public function setUserRoles(): void
	{
		$this->require_role('users');
		if (!isset($_POST['userID']) || !is_numeric($_POST['userID'])) {
			$this->json(["success" => false, "error" => "invalid or missing user ID"]);
		}
		$userObj = new Users;

		$userObj->stripRoles($_POST['userID']);

		$roles = explode(",", $_POST['roles']);
		foreach ($roles as $role) {
			if (!is_int($role)) {
				continue;
			}
			$userObj->addRole($_POST['userID'], $role);
		}
		$this->json(["success" => true]);
	}

	public function toolbarLoader(): void
	{
		$this->require_role('admin');
		$user = \ORM::for_table(_table_users)->select('name')->find_one($_SESSION[session_key]['user_id']);
		$this->json([
			"_app_path" => _app_path,
			"js_load" => _app_path . "humblee/js/admin/toolbar.js?time=" . time(),
			"name" => $user->name
		]);
	}

	/**
	 * One-time migration: prepend stored DB nonces into their encrypted files on disk,
	 * then clear the crypto_nonce column. Run this once before deploying the new
	 * encrypt/decrypt signatures that embed the nonce in the file payload.
	 */
	public function migrateNonces(): void
	{
		$this->require_role('admin');

		$files = \ORM::for_table(_table_media)
			->where('encrypted', 1)
			->where_not_equal('crypto_nonce', '')
			->find_many();

		$migrated = 0;
		$errors   = [];

		foreach ($files as $file) {
			$nonce = $file->crypto_nonce;

			if (strlen($nonce) !== SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
				$errors[] = "File ID {$file->id}: unexpected nonce length (" . strlen($nonce) . " bytes), skipped";
				continue;
			}

			$file_location = _app_server_path . 'storage/' . $file->filepath;
			$ciphertext    = file_get_contents($file_location);

			if ($ciphertext === false) {
				$errors[] = "File ID {$file->id}: could not read from disk, skipped";
				continue;
			}

			if (!file_put_contents($file_location, $nonce . $ciphertext)) {
				$errors[] = "File ID {$file->id}: could not write migrated payload to disk, skipped";
				continue;
			}

			$file->crypto_nonce = '';
			$file->save();
			$migrated++;
		}

		$this->json([
			"success"  => empty($errors),
			"migrated" => $migrated,
			"errors"   => $errors,
		]);
	}

	// -------------------------------------------------------------------------
	// Designer tools: Blocks (humblee_content_types)
	// -------------------------------------------------------------------------

	public function listBlocks(): void
	{
		$this->require_role('designer');
		$rows = \ORM::for_table(_table_content_types)->order_by_asc('name')->find_many();
		$result = [];
		foreach ($rows as $row) {
			$result[] = [
				'id'               => (int)$row->id,
				'name'             => $row->name,
				'objectkey'        => $row->objectkey,
				'description'      => $row->description,
				'output_type'      => $row->output_type,
				'input_type'       => $row->input_type,
				'input_parameters' => $row->input_parameters,
			];
		}
		$this->json($result);
	}

	public function saveBlock(): void
	{
		$this->require_role('designer');

		$id   = isset($_POST['id']) && is_numeric($_POST['id']) ? (int)$_POST['id'] : null;
		$name = trim($_POST['name'] ?? '');
		$key  = trim($_POST['objectkey'] ?? '');

		if ($name === '') {
			$this->json(['success' => false, 'errors' => ['Name field cannot be blank']]);
		}
		if ($key === '') {
			$this->json(['success' => false, 'errors' => ['objectKey field cannot be blank']]);
		}

		$row = ($id !== null)
			? \ORM::for_table(_table_content_types)->find_one($id)
			: \ORM::for_table(_table_content_types)->create();

		if (!$row) {
			$this->json(['success' => false, 'errors' => ['Record not found']]);
		}

		$row->name             = htmlspecialchars($name);
		$row->objectkey        = htmlspecialchars($key);
		$row->description      = htmlspecialchars(trim($_POST['description'] ?? ''));
		$row->output_type      = htmlspecialchars(trim($_POST['output_type'] ?? ''));
		$row->input_type       = htmlspecialchars(trim($_POST['input_type'] ?? ''));
		$row->input_parameters = trim($_POST['input_parameters'] ?? '');
		$row->save();

		$this->json(['success' => true, 'id' => (int)$row->id]);
	}

	public function deleteBlock(): void
	{
		$this->require_role('designer');

		if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
			$this->json(['success' => false, 'errors' => ['Invalid or missing id']]);
		}

		$row = \ORM::for_table(_table_content_types)->find_one((int)$_POST['id']);
		if (!$row) {
			$this->json(['success' => false, 'errors' => ['Record not found']]);
		}

		$row->delete();
		$this->json(['success' => true]);
	}

	// -------------------------------------------------------------------------
	// Designer tools: Templates (humblee_templates)
	// -------------------------------------------------------------------------

	public function listTemplates(): void
	{
		$this->require_role('designer');
		$rows = \ORM::for_table(_table_templates)->order_by_asc('name')->find_many();
		$result = [];
		foreach ($rows as $row) {
			$entry = [
				'id'          => (int)$row->id,
				'name'        => $row->name,
				'description' => $row->description,
				'page_type'   => $row->page_type,
				'page_meta'   => $row->page_meta,
				'dynamic_uri' => (int)$row->dynamic_uri,
				'available'   => (int)$row->available,
				'blocks'      => $row->blocks,
				// derived fields
				'controller'        => '',
				'controller_action' => '',
				'default_view'      => '',
			];
			if ($row->page_type === 'controller') {
				$meta = @unserialize($row->page_meta);
				if (is_array($meta)) {
					$entry['controller']        = $meta['controller'] ?? '';
					$entry['controller_action'] = $meta['action'] ?? '';
				}
			} elseif ($row->page_type === 'view') {
				$entry['default_view'] = $row->page_meta;
			}
			$result[] = $entry;
		}
		$this->json($result);
	}

	public function saveTemplate(): void
	{
		$this->require_role('designer');

		$id   = isset($_POST['id']) && is_numeric($_POST['id']) ? (int)$_POST['id'] : null;
		$name = trim($_POST['name'] ?? '');

		if ($name === '') {
			$this->json(['success' => false, 'errors' => ['Name field cannot be blank']]);
		}

		$row = ($id !== null)
			? \ORM::for_table(_table_templates)->find_one($id)
			: \ORM::for_table(_table_templates)->create();

		if (!$row) {
			$this->json(['success' => false, 'errors' => ['Record not found']]);
		}

		$blocks_raw  = $_POST['blocks'] ?? [];
		$page_type   = trim($_POST['page_type'] ?? '');
		$dynamic_uri = isset($_POST['dynamic_uri']) ? 1 : 0;
		$available   = isset($_POST['available'])   ? 1 : 0;

		switch ($page_type) {
			case 'view':
				$page_meta = trim($_POST['default_view'] ?? '');
				break;
			case 'controller':
				$page_meta = serialize([
					'controller' => trim($_POST['controller'] ?? ''),
					'action'     => trim($_POST['controller_action'] ?? ''),
				]);
				break;
			default:
				$page_type = 'default';
				$page_meta = 'tierpage';
		}

		$row->name        = htmlspecialchars($name);
		$row->description = htmlspecialchars(trim($_POST['description'] ?? ''));
		$row->page_type   = $page_type;
		$row->page_meta   = $page_meta;
		$row->dynamic_uri = $dynamic_uri;
		$row->available   = $available;
		$row->blocks      = is_array($blocks_raw) ? implode(',', array_filter($blocks_raw, 'is_numeric')) : '';
		$row->save();

		$this->json(['success' => true, 'id' => (int)$row->id]);
	}

	public function deleteTemplate(): void
	{
		$this->require_role('designer');

		if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
			$this->json(['success' => false, 'errors' => ['Invalid or missing id']]);
		}

		$row = \ORM::for_table(_table_templates)->find_one((int)$_POST['id']);
		if (!$row) {
			$this->json(['success' => false, 'errors' => ['Record not found']]);
		}

		$row->delete();
		$this->json(['success' => true]);
	}

	/**
	 * Set user's theme preference (light/dark/system)
	 */
	public function setThemePreference(): void
	{
		$this->require_role('login');
		$this->require_hmac();

		if (!isset($_POST['theme']) || !is_string($_POST['theme'])) {
			$this->json(["error" => "Missing or invalid theme parameter"]);
		}

		$users = new Users();
		$user_id = $_SESSION[session_key]['user_id'];
		$theme = $_POST['theme'];

		if ($users->setThemePreference($user_id, $theme)) {
			$this->json(["success" => true, "theme" => $theme]);
		} else {
			$this->json(["error" => "Failed to update theme preference"], 400);
		}
	}
}
