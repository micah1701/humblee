<?php

declare(strict_types=1);

namespace Humblee\Foundation;

class Core {

	/**
	 * Return String of URL Path (everything after domain.tld but not including additional params)
	 */
	public static function getURI(): string
    {
		if(isset($_SERVER['PATH_INFO']))
        {
			$_path_info = $_SERVER['PATH_INFO'];
		}
        elseif(isset($_SERVER['ORIG_PATH_INFO']))
        {
			$_path_info = $_SERVER['ORIG_PATH_INFO'];
 		}
        else
        {
			$_path_info = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '';
		}

		if(str_starts_with($_path_info, _app_path))
        {
  			$_path_info = substr($_path_info, strlen(_app_path));
		}

        if(str_starts_with($_path_info, "index.php"))
        {
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
		if($_uri_parts[0] === "public")
		{
			array_shift($_uri_parts);
		}

		if(!$include_i18n_segment && is_array($_ENV['config']['i18n_segments']) && preg_grep("/$_uri_parts[0]/i", $_ENV['config']['i18n_segments']))
		{
			array_shift($_uri_parts);
		}

		return $_uri_parts;
	}

	/**
	 * Return the contents of a view file
	 *
	 * $path (str) path to file
	 * $view_variables (optional array) variables to be utilized by the included file
	 */
	public static function view(string $path, array|false $view_variables = false): string|false
    {
		if(!is_file($path))
        {
        	return false;
        }

		ob_start();
		if($view_variables)
        {
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
					->join(_table_roles, [_table_user_roles.'.role_id', '=', _table_roles.'.id'])
					->where('user_id', $_SESSION[session_key]['user_id'])
					->find_many();

		if(!$roles)
		{
			$_SESSION[session_key]['has_roles'] = null;
			unset($_SESSION[session_key]['has_roles']);
			return false;
		}

		foreach($roles as $role)
		{
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
		if(!isset($_SESSION[session_key]['user_id']))
        {
            return false;
        }

        if(!is_array($required_roles))
        {
        	$required_roles = [$required_roles];
        }

        if(!isset($_SESSION[session_key]['has_roles']) || !$_SESSION[session_key]['has_roles'] || !is_array($_SESSION[session_key]['has_roles']))
		{
			$has_roles = Core::cacheUserRoles();

			if(!$has_roles)
			{
				return false;
			}
		}

        foreach($required_roles as $required_role)
        {
			if((is_numeric($required_role) && array_key_exists($required_role, $_SESSION[session_key]['has_roles'])) || in_array($required_role, $_SESSION[session_key]['has_roles']))
			{
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
		if($status)
		{
			header("HTTP/1.1 ".$status);
		}
		header("Location: ".rtrim(_app_path, "/") . "/" . ltrim($uri, "/"));
		ob_flush();
		exit();
	}

}
