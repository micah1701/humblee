<?php

declare(strict_types=1);

namespace Humblee\Model;

use Humblee\Foundation\Core;

class Users
{

	/**
	 * Hash a password for storage using Argon2ID
	 */
	public function hashPassword(string $password, int $user_id): string
	{
		return password_hash($password . '-' . $user_id, PASSWORD_ARGON2ID);
	}

	/**
	 * Legacy hash (sodium BLAKE2b) — kept for verifying pre-migration passwords only
	 */
	public function stringToSaltedHash(string $string, int|string $salt): string
	{
		$salted_string = $string . '-' . $salt;
		$crypto = new Crypto;
		return $crypto->genericHash($salted_string);
	}

	/**
	 * Log in as given user by setting session values
	 */
	public function logInSession(int $user_id): void
	{
		$_SESSION[session_key] = [];
		$_SESSION[session_key]['user_id'] = $user_id;
	}

	/**
	 * Update the access log
	 */
	public function accesslog(string $status = ''): void
	{
		$log = \ORM::for_table(_table_accesslog)->create();
		$log->session_id = session_id();
		$log->user_id = $_SESSION[session_key]['user_id'] ?? 0;
		$log->ip_address = $_SERVER['REMOTE_ADDR'];
		$log->ip_geolocation = '';
		$log->user_agent = $_SERVER['HTTP_USER_AGENT'];
		$log->timestamp = date("Y-m-d H:i:s");
		$log->status = $status;
		$log->save();
	}

	/**
	 * Check credentials and log in
	 */
	public function logIn(string $username, string $password, bool $sms_login = false): array
	{
		$username_column = filter_var($username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

		$user = \ORM::for_table(_table_users)
			->where($username_column, $username)
			->where('active', 1)
			->find_one();

		if (!$user) {
			$this->accesslog('Failed: invalid credentials');
			return ['access_granted' => false, 'error' => 'Invalid Username'];
		}

		if ($sms_login) {
			if (
				!isset($_SESSION[session_key]['login_token']) ||
				strtoupper($password) !== $_SESSION[session_key]['login_token']
			) {
				$this->accesslog('Failed: invalid SMS token');
				return ['access_granted' => false, 'error' => 'Invalid SMS Code'];
			}
			if (!isset($_SESSION[session_key]['login_token_expires']) || time() > $_SESSION[session_key]['login_token_expires']) {
				$this->accesslog('Failed: SMS token expired');
				return ['access_granted' => false, 'error' => 'SMS Code Expired'];
			}
		} else {
			$passwordCorrect = false;
			if (password_verify($password . '-' . $user->id, $user->password)) {
				$passwordCorrect = true;
				if (password_needs_rehash($user->password, PASSWORD_ARGON2ID)) {
					$user->password = $this->hashPassword($password, (int)$user->id);
					$user->save();
				}
			} elseif ($this->stringToSaltedHash($password, $user->id) === $user->password) {
				$passwordCorrect = true;
				$user->password = $this->hashPassword($password, (int)$user->id);
				$user->save();
			}
			if (!$passwordCorrect) {
				$this->accesslog('Failed: Invalid Password');
				return ['access_granted' => false, 'error' => 'Invalid Password'];
			}

			if ($_ENV['config']['TWILIO_Enabled'] && $user->use_twofactor_auth == 1) {
				$this->accesslog('Valid password. SMS requested');
				return ['access_granted' => false, 'error' => 'use_twofactor_auth', 'cellphone' => $user->cellphone, 'name' => $user->name, 'email' => $user->email];
			}
		}

		$this->logInSession($user->id);

		$user->logins = $user->logins + 1;
		$user->last_login = date("Y-m-d H:i:s");
		$user->save();

		$log_msg = $sms_login ? 'Accepted SMS' : 'Accepted Password';
		$this->accesslog($log_msg);
		return ['access_granted' => true];
	}

	/**
	 * Log current user out
	 */
	public function logOut(): bool
	{
		session_destroy();
		return true;
	}

	/**
	 * Get user's profile
	 * Returns logged in user unless $user_id is specified
	 */
	public function profile(?int $user_id = null): object|false
	{
		$user_id = is_numeric($user_id) ? $user_id : $_SESSION[session_key]['user_id'];
		return \ORM::for_table(_table_users)->find_one($user_id);
	}

	/**
	 * Get a user's access log
	 * Returns logged in user's log unless $user_id is specified
	 */
	public function access_log(int $limit = 100, ?int $user_id = null): mixed
	{
		$user_id = is_numeric($user_id) ? $user_id : $_SESSION[session_key]['user_id'];
		return \ORM::for_table(_table_accesslog)
			->where('user_id', $user_id)
			->order_by_desc('timestamp')
			->limit($limit)
			->find_many();
	}

	/**
	 * Generate a random plain-text password string
	 */
	public function generatePassword(int $length = 8): string
	{
		$chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
		$pw = "";
		for ($i = 0; $i < $length; $i++) {
			$random = rand(0, strlen($chars) - 1);
			$pw .= $chars[$random];
		}
		return $pw;
	}

	/**
	 * Create a new user
	 */
	public function createUser(string $name, string $email, string $username, string $password = '', string $cellphone = ''): int
	{
		$user = \ORM::for_table(_table_users)->create();
		$user->name = $name;
		$user->username = $username;
		$user->email = $email;
		$user->cellphone = $cellphone;
		$user->password = 'random-temp-password-' . time();
		$user->save();

		$user->password = $this->hashPassword($password, (int)$user->id);
		$user->active = 1;
		$user->save();

		return (int) $user->id;
	}

	/**
	 * Remove all roles for a given user
	 */
	public function stripRoles(int $user_id): bool
	{
		$roles = \ORM::for_table(_table_user_roles)->where('user_id', $user_id)->find_many();
		if (!$roles) {
			return false;
		}
		foreach ($roles as $role) {
			$role->delete();
		}
		return true;
	}

	/**
	 * Add a role for a given user
	 */
	public function addRole(int $user_id, int $role_id): bool
	{
		$role = \ORM::for_table(_table_user_roles)->create();
		$role->user_id = $user_id;
		$role->role_id = $role_id;
		$role->save();
		return true;
	}

	/**
	 * Delete a user
	 *
	 * $complete_removal BOOL
	 *   True  physically deletes the row
	 *   False updates record to remove access (preserves revision history integrity)
	 */
	public function deleteUser(int $user_id, bool $complete_removal = false): bool
	{
		$user = \ORM::for_table(_table_users)->find_one($user_id);
		if (!$user) {
			return false;
		}

		$this->stripRoles($user_id);

		if ($complete_removal) {
			return (bool) $user->delete();
		}
		$user->name = $user->name . " [DELETED USER]";
		$user->username = $user->username . " [DELETED USER]";
		$user->email = $user->email . " [DELETED USER]";
		$user->password = "";
		$user->active = 0;
		$user->save();
		return true;
	}

	/**
	 * Reset a user's password and optionally notify them by email
	 */
	public function resetPassword(int $user_id, string $new_password, bool $sendEmail = true): bool
	{
		$user = \ORM::for_table(_table_users)->find_one($user_id);
		if (!$user) {
			return false;
		}
		$user->password = $this->hashPassword($new_password, $user_id);
		$user->save();

		if ($sendEmail) {
			$from = $_ENV['config']['default_email'];
			$subject = "You've successfully reset your " . $_ENV['config']['domain'] . " password";
			$body = "Hi {$user->name},\n\n";
			$body .= "This message is to notify that the password associated with your account has been reset.\n\n";
			$body .= "If you did not initiate this change, you can recover your account at <a href=\"http://" . $_ENV['config']['domain'] . _app_path . "user/forgotPassword\">" . $_ENV['config']['domain'] . _app_path . "user/forgotPassword</a>\n\n";
			$body .= " Thanks!";

			$tools = new Tools;
			$message = $tools->emailTemplate($subject, nl2br($body), _app_server_path . 'humblee/views/email/notification.php');
			return $tools->sendEmail($user->email, $from, $subject, $message);
		}

		return true;
	}

	/**
	 * Send a registration confirmation email to user
	 */
	public function registrationEmail(string $email, string $name, string $password): bool
	{
		$from = $_ENV['config']['default_email'];
		$subject = $_ENV['config']['domain'] . " Username and Password";
		$body = "Hi {$name},\n\n";
		$body .= "Welcome to " . $_ENV['config']['domain'] . "!\n\n";
		$body .= "Your new account has been created.\n\n";
		$body .= "Username: {$email} \n\n";
		$body .= " To change your password, sign in to " . $_ENV['config']['domain'] . " and update your user profile.\n\n";
		$body .= " Thanks!";

		$tools = new Tools;
		$message = $tools->emailTemplate($subject, nl2br($body), _app_server_path . 'humblee/views/email/notification.php');
		return $tools->sendEmail($email, $from, $subject, $message);
	}

	/**
	 * Send an account verification email for resetting a forgotten password
	 */
	public function forgotPasswordVerifyEmail(string $email, string $name, string $token): bool
	{
		$from = $_ENV['config']['default_email'];
		$subject = $_ENV['config']['domain'] . " verification access code";
		$body = "Hi {$name},\n\n";
		$body .= "Someone has initiated a password reset request for your account at  " . $_ENV['config']['domain'] . "!\n\n";
		$body .= "The one-time temporary access code to complete this request is:<strong> {$token} </strong>\n\n";
		$body .= " If you did not request this, you can ignore and delete this message.\n\n";
		$body .= " Thanks!";

		$tools = new Tools;
		$message = $tools->emailTemplate($subject, nl2br($body), _app_server_path . 'humblee/views/email/notification.php');
		return $tools->sendEmail($email, $from, $subject, $message);
	}
}
