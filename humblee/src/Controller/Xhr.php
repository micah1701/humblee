<?php

declare(strict_types=1);

namespace Humblee\Controller;

use Humblee\Foundation\Core;
use Humblee\Model\Crypto;

/**
 * Base controller for AJAX/XHR requests.
 * Sets no-cache headers and provides authorization and JSON response helpers.
 */
class Xhr {

    public function __construct()
    {
        header("Expires: Sat, 07 Apr 1979 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
    }

    /**
     * Ensure the user has the necessary role to access this endpoint
     * $role can be a string (e.g. "login") or an array (e.g. ["admin","customer"])
     */
    public function require_role(int|string|array $role): void
    {
        if(!Core::auth($role) && !Core::auth('developer'))
        {
            header('HTTP/1.1 403 Forbidden');
            exit("<h1>403 Forbidden</h1><h3>You do not have access to view this page</h3><p>If you believe this is an error, please see your site administrator.</p>");
        }
    }

    /**
     * Validate the HMAC machine-authentication token pair from POST
     */
    public function require_hmac(): bool
    {
        if(!isset($_POST['hmac_token']) || !isset($_POST['hmac_key']))
        {
            header('HTTP/1.1 401 Unauthorized');
            exit("<h1>401 Unauthorized</h1><h3>Missing Machine Key</h3><p>If you believe this is an error, please see your site administrator.</p>");
        }

        $crypto = new Crypto;
        if(!$crypto->check_hmac_pair($_POST['hmac_token'], $_POST['hmac_key']))
        {
            header('HTTP/1.1 401 Unauthorized');
            exit("<h1>401 Unauthorized</h1><h3>Invalid Machine Authentication Key</h3>");
        }

        return true;
    }

    /**
     * Set Content-Type to JSON and optionally echo an encoded array
     */
    public function json(array|false $package = false): void
    {
        header('Content-Type: application/json');
        if(is_array($package))
        {
            echo json_encode($package);
            exit();
        }
    }

    public function index(): never
    {
        exit("Error!<br />\n No Request action sent");
    }

}
