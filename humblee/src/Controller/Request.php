<?php

declare(strict_types=1);

namespace Humblee\Controller;

use Humblee\Foundation\Core;
use Humblee\Model\Crypto;
use Humblee\Model\Users;
use Humblee\Model\Tools;
use Humblee\Controller\Requests\Blocks;
use Humblee\Controller\Requests\Templates;
use Humblee\Controller\Requests\MediaFiles;
use Humblee\Controller\Requests\Users as UsersGroup;
use Humblee\Controller\Requests\Pages;
use Humblee\Controller\Requests\Content;
use Humblee\Controller\Requests\Personalization;

/**
 * Core AJAX/XHR request controller
 * Handles CMS-internal AJAX operations: auth, pages, content, media, users
 */
class Request extends Xhr
{

	// -------------------------------------------------------------------------
	// Group dispatchers — second URI segment routes here; third is the action
	// -------------------------------------------------------------------------

	public function blocks(): void
	{
		$action = Core::getURIparts()[2] ?? '';
		match ($action) {
			'list'   => Blocks::list($this),
			'save'   => Blocks::save($this),
			'delete' => Blocks::delete($this),
			default  => $this->json(['error' => 'Not found'], 404),
		};
	}

	public function templates(): void
	{
		$action = Core::getURIparts()[2] ?? '';
		match ($action) {
			'list'   => Templates::list($this),
			'save'   => Templates::save($this),
			'delete' => Templates::delete($this),
			default  => $this->json(['error' => 'Not found'], 404),
		};
	}

	public function media_files(): void
	{
		$action = Core::getURIparts()[2] ?? '';
		match ($action) {
			'list-folders'   => MediaFiles::listFolders($this),
			'list-by-folder' => MediaFiles::listByFolder($this),
			'update-name'    => MediaFiles::updateName($this),
			'update-role'    => MediaFiles::updateRole($this),
			'encrypt'        => MediaFiles::encrypt($this),
			'delete-file'    => MediaFiles::deleteFile($this),
			'create-folder'  => MediaFiles::createFolder($this),
			'delete-folder'  => MediaFiles::deleteFolder($this),
			'upload'         => MediaFiles::upload($this),
			'migrate-nonces' => MediaFiles::migrateNonces($this),
			default          => $this->json(['error' => 'Not found'], 404),
		};
	}

	public function users(): void
	{
		$action = Core::getURIparts()[2] ?? '';
		match ($action) {
			'list'      => UsersGroup::list($this),
			'remove'    => UsersGroup::remove($this),
			'set-roles' => UsersGroup::setRoles($this),
			default     => $this->json(['error' => 'Not found'], 404),
		};
	}

	public function pages(): void
	{
		$action = Core::getURIparts()[2] ?? '';
		match ($action) {
			'list'              => Pages::list($this),
			'content-list'      => Pages::contentList($this),
			'load-content-menu' => Pages::loadContentMenu($this),
			'load-table'        => Pages::loadTable($this),
			'get-properties'    => Pages::getProperties($this),
			'set-properties'    => Pages::setProperties($this),
			'add'               => Pages::add($this),
			'delete'            => Pages::delete($this),
			'order'             => Pages::order($this),
			default             => $this->json(['error' => 'Not found'], 404),
		};
	}

	public function content(): void
	{
		$action = Core::getURIparts()[2] ?? '';
		match ($action) {
			'latest-revision-date'   => Content::latestRevisionDate($this),
			'p13n-order-priorities'  => Content::p13nOrderPriorities($this),
			'page-map'               => Content::pageMap($this),
			default                  => $this->json(['error' => 'Not found'], 404),
		};
	}

	public function personalization(): void
	{
		$action = Core::getURIparts()[2] ?? '';
		match ($action) {
			'list'   => Personalization::list($this),
			'save'   => Personalization::save($this),
			'delete' => Personalization::delete($this),
			default  => $this->json(['error' => 'Not found'], 404),
		};
	}

	// -------------------------------------------------------------------------
	// Direct methods — auth, toolbar, theme (no sub-grouping)
	// -------------------------------------------------------------------------

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
	 * Check whether the current request has an authenticated session.
	 * Returns a fresh HMAC pair (only when logged out) so the re-login form can POST safely.
	 * No HMAC required — safe for unauthenticated GET requests.
	 */
	public function session_check(): void
	{
		$loggedIn = isset($_SESSION[session_key]['user_id']);
		$response = ['loggedIn' => $loggedIn];
		if (!$loggedIn) {
			$crypto   = new Crypto();
			$pair     = $crypto->get_hmac_pair();
			$response['hmacKey']   = $pair['hmac'];
			$response['hmacToken'] = $pair['message'];
		}
		$this->json($response);
	}

	/**
	 * Re-authenticate an expired session via username + password.
	 * Optionally sets a persistent remember-me cookie when remember=1 is posted.
	 */
	public function session_login(): void
	{
		$this->require_hmac();

		if (!isset($_POST['username']) || !isset($_POST['password'])) {
			$this->json(['success' => false, 'message' => 'Missing credentials'], 400);
		}

		$users = new Users();
		$login = $users->logIn(trim($_POST['username']), $_POST['password']);

		if ($login['access_granted'] !== true) {
			if (($login['error'] ?? '') === 'use_twofactor_auth') {
				$this->json(['success' => false, 'message' => 'Two-factor authentication is required. Please use the login page.']);
			}
			$this->json(['success' => false, 'message' => $login['error'] ?? 'Login failed']);
		}

		if (!empty($_POST['remember'])) {
			Core::setRememberToken((int) $_SESSION[session_key]['user_id']);
		}

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
