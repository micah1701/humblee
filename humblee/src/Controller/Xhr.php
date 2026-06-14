<?php

declare(strict_types=1);

namespace Humblee\Controller;

use Humblee\Foundation\Core;
use Humblee\Model\Crypto;

/**
 * Base controller for AJAX/XHR requests.
 * Sets no-cache headers and provides authorization and JSON response helpers.
 */
class Xhr
{

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
        if (!Core::auth($role) && !Core::auth('developer')) {
            Core::json([
                'error' => 'Forbidden',
                'message' => 'You do not have access to view this page. If you believe this is an error, please see your site administrator.'
            ], 403);
        }
    }

    /**
     * Validate the HMAC machine-authentication token pair from POST
     */
    public function require_hmac(bool $exitOnFailure = true): bool
    {
        if (!isset($_POST['hmac_token']) || !isset($_POST['hmac_key'])) {
            Core::json([
                'error' => 'Unauthorized',
                'message' => 'Missing Machine Key'
            ], 401);
            if ($exitOnFailure) {
                exit();
            }
            return false;
        }

        $crypto = new Crypto;
        if (!$crypto->check_hmac_pair($_POST['hmac_token'], $_POST['hmac_key'])) {
            Core::json([
                'error' => 'Unauthorized',
                'message' => 'Invalid Machine Authentication Key'
            ], 401);
            if ($exitOnFailure) {
                exit();
            }
            return false;
        }

        return true;
    }

    /**
     * Recursively Convert ALL strings in a nested array or object to UTF-8 encoding 
     */
    public function encodeToUTF8(array|object $array_or_obect, $showOriginalStringOnError = false): array|object
    {
        // $possibleEncodings = mb_list_encodings();
        $possibleEncodings =  ['ASCII', 'UTF-8', 'ISO-8859-1'];
        foreach ($array_or_obect as $key => $value) {

            if (is_array($array_or_obect)) {
                $array_or_obect[$key] = $this->encodeToUTF8($value);
                continue;
            }
            if (is_object($array_or_obect)) {
                $array_or_obect->{$key} = $this->encodeToUTF8($value);
                continue;
            }
            if ($value == null) {
                continue;
            }

            $encoding_from = mb_detect_encoding($value, $possibleEncodings, true);

            if (is_array($array_or_obect)) {
                $array_or_obect[$key] = ($encoding_from != "UTF-8") ? mb_convert_encoding($value, 'UTF-8', $encoding_from) : $value;
                if ($array_or_obect[$key] == false && $showOriginalStringOnError == true) {
                    $array_or_obect[$key] = $value;
                }
                continue;
            }
            if (is_object($array_or_obect)) {
                $array_or_obect->{$key} = ($encoding_from != "UTF-8") ? mb_convert_encoding($value, 'UTF-8', $encoding_from) : $value;
                if (!$array_or_obect->{$key} && $showOriginalStringOnError) {
                    $array_or_obect->{$key} = $value;
                }
                continue;
            }
        }
        return $array_or_obect;
    }

    public function index()
    {
        Core::json([
            'error' => 'Bad Request',
            'message' => 'No action specified'
        ], 400);
    }
}
