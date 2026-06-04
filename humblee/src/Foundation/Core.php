<?php

declare(strict_types=1);

namespace Humblee\Foundation;

use Humblee\Model\Crypto;

class Core
{

	/**
	 * Return String of URL Path (everything after domain.tld but not including additional params)
	 */
	public static function getURI(): string
	{
		if (isset($_SERVER['PATH_INFO'])) {
			$_path_info = $_SERVER['PATH_INFO'];
		} elseif (isset($_SERVER['ORIG_PATH_INFO'])) {
			$_path_info = $_SERVER['ORIG_PATH_INFO'];
		} else {
			$_path_info = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '';
		}

		if (str_starts_with($_path_info, _app_path)) {
			$_path_info = substr($_path_info, strlen(_app_path));
		}

		if (str_starts_with($_path_info, "index.php")) {
			$_path_info = substr($_path_info, strlen("index.php"));
		}

		$uri = (!isset($_path_info) || $_path_info === "" || $_path_info === "public") ? "" : ltrim($_path_info, "/");
		return $uri;
	}

	/**
	 * Return ARRAY of URI parts
	 * eg www.mydomain.com/dir1/dir2/dir3 returns array(0=>'dir1',1=>'dir2',2=>'dir3');
	 */
	public static function getURIparts(bool $include_i18n_segment = false): array
	{
		$_uri_parts = explode("/", ltrim(Core::getURI(), "/"));
		if ($_uri_parts[0] === "public") {
			array_shift($_uri_parts);
		}

		return (!$include_i18n_segment && is_array($_ENV['config']['i18n_segments'])
			&& preg_grep("/$_uri_parts[0]/i", $_ENV['config']['i18n_segments'])
		) ? array_shift($_uri_parts) : $_uri_parts;
	}

	public static function getCurrentI18nSegment(): string
	{
		if (!is_array($_ENV['config']['i18n_segments']) || empty($_ENV['config']['i18n_segments'])) {
			return '';
		}
		$parts = self::getURIparts(true);
		return (!empty($parts[0]) && preg_grep("/$parts[0]/i", $_ENV['config']['i18n_segments'])) ? $parts[0] : '';
	}

	/**
	 * Return the contents of a view file
	 *
	 * $path (str) path to file
	 * $view_variables (optional array) variables to be utilized by the included file
	 */
	public static function view(string $path, array|false $view_variables = false): string|false
	{
		if (!is_file($path)) {
			return false;
		}

		ob_start();
		if ($view_variables) {
			extract($view_variables);
		}
		include $path;
		return ob_get_clean();
	}

	/**
	 * Helper used by Core::auth() to cache roles in user's SESSION
	 */
	private static function cacheUserRoles(): array|false
	{
		$roles = \ORM::for_table(_table_user_roles)
			->distinct()->select('role_id')
			->select('name')
			->join(_table_roles, [_table_user_roles . '.role_id', '=', _table_roles . '.id'])
			->where('user_id', $_SESSION[session_key]['user_id'])
			->find_many();

		if (!$roles) {
			$_SESSION[session_key]['has_roles'] = null;
			unset($_SESSION[session_key]['has_roles']);
			return false;
		}

		foreach ($roles as $role) {
			$_SESSION[session_key]['has_roles'][$role->role_id] = $role->name;
		}

		return $_SESSION[session_key]['has_roles'];
	}

	/**
	 * Check if user has given role
	 *
	 * $required_roles  INT     looks for role by ID
	 *                  STRING  looks for role by name
	 *                  ARRAY   of INT/STRING to match "any" roles
	 */
	public static function auth(int|string|array $required_roles): bool
	{
		if (!isset($_SESSION[session_key]['user_id'])) {
			return false;
		}

		if (!is_array($required_roles)) {
			$required_roles = [$required_roles];
		}

		if (!isset($_SESSION[session_key]['has_roles']) || !$_SESSION[session_key]['has_roles'] || !is_array($_SESSION[session_key]['has_roles'])) {
			$has_roles = Core::cacheUserRoles();

			if (!$has_roles) {
				return false;
			}
		}

		foreach ($required_roles as $required_role) {
			if ((is_numeric($required_role) && array_key_exists($required_role, $_SESSION[session_key]['has_roles'])) || in_array($required_role, $_SESSION[session_key]['has_roles'])) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Forward to another page using a redirect header
	 *
	 * $uri    path to forward to
	 * $status (optional) STRING for status code (eg: "301 Moved Permanently")
	 */
	public static function forward(string $uri = '', string|false $status = false): never
	{
		ob_start();
		if ($status) {
			header("HTTP/1.1 " . $status);
		}
		header("Location: " . rtrim(_app_path, "/") . "/" . ltrim($uri, "/"));
		ob_flush();
		exit();
	}

	/**
	 * Store an arbitrary value in the session namespace
	 */
	public static function setSessionData(string $key, mixed $value): void
	{
		if (!isset($_SESSION[session_key])) {
			$_SESSION[session_key] = [];
		}
		$_SESSION[session_key][$key] = $value;
	}

	/**
	 * Retrieve a value from the session namespace
	 */
	public static function getSessionData(string $key, mixed $default = null): mixed
	{
		return $_SESSION[session_key][$key] ?? $default;
	}

	/**
	 * Set an encrypted remember-me cookie so the session can be restored after timeout
	 * Cookie payload: "$user_id|$expiry_timestamp" encrypted with the site key
	 */
	public static function setRememberToken(int $user_id, int $days = 30): void
	{
		$expiry    = time() + ($days * 86400);
		$crypto    = new Crypto();
		$encrypted = $crypto->encrypt($user_id . '|' . $expiry);
		if ($encrypted === false) {
			return;
		}
		$secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
		setcookie('humblee_remember', base64_encode($encrypted), [
			'expires'  => $expiry,
			'path'     => '/',
			'secure'   => $secure,
			'httponly' => true,
			'samesite' => 'Strict',
		]);
	}

	/**
	 * Validate the remember-me cookie and return the stored user_id, or false if invalid/expired
	 */
	public static function checkRememberToken(): int|false
	{
		if (empty($_COOKIE['humblee_remember'])) {
			return false;
		}
		$encrypted = base64_decode($_COOKIE['humblee_remember'], true);
		if ($encrypted === false) {
			return false;
		}
		$crypto  = new Crypto();
		$payload = $crypto->decrypt($encrypted);
		if ($payload === false) {
			return false;
		}
		$parts = explode('|', $payload, 2);
		if (count($parts) !== 2 || !is_numeric($parts[0]) || !is_numeric($parts[1])) {
			return false;
		}
		if (time() > (int) $parts[1]) {
			static::clearRememberToken();
			return false;
		}
		return (int) $parts[0];
	}

	/**
	 * Delete the remember-me cookie
	 */
	public static function clearRememberToken(): void
	{
		$secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
		setcookie('humblee_remember', '', [
			'expires'  => time() - 3600,
			'path'     => '/',
			'secure'   => $secure,
			'httponly' => true,
			'samesite' => 'Strict',
		]);
		unset($_COOKIE['humblee_remember']);
	}
}
