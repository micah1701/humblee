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
            header('HTTP/1.1 403 Forbidden');
            exit("<h1>403 Forbidden</h1><h3>You do not have access to view this page</h3><p>If you believe this is an error, please see your site administrator.</p>");
        }
    }

    /**
     * Validate the HMAC machine-authentication token pair from POST
     */
    public function require_hmac(): bool
    {
        if (!isset($_POST['hmac_token']) || !isset($_POST['hmac_key'])) {
            header('HTTP/1.1 401 Unauthorized');
            exit("<h1>401 Unauthorized</h1><h3>Missing Machine Key</h3><p>If you believe this is an error, please see your site administrator.</p>");
        }

        $crypto = new Crypto;
        if (!$crypto->check_hmac_pair($_POST['hmac_token'], $_POST['hmac_key'])) {
            header('HTTP/1.1 401 Unauthorized');
            exit("<h1>401 Unauthorized</h1><h3>Invalid Machine Authentication Key</h3>");
        }

        return true;
    }

    /**
     * if outputting json_encode() data, call $this->json() to set this header
     * optionally pass a string or array to return
     */
    public function json(array|string $package, int $status = 200): void
    {
        if (!is_array($package)) {
            $package = [$package];
        }

        $asJSON = json_encode($package);
        $errorCode = json_last_error();

        // if json_encode fails do to a UTF8 error, attempt to fix the content and try again
        // This is a failsafe that doesn't work very well.
        // DO NO RELY ON THIS:
        // Instead, make sure the database default ecoding is correct (for the Schema, each table, and every column)
        if ($errorCode === JSON_ERROR_UTF8) {
            $package = $this->encodeToUTF8($package);
            $asJSON = json_encode($package);
            $errorCode = json_last_error();
        }

        // cool, no errors. return the JSON content to the client
        if ($errorCode === JSON_ERROR_NONE) {
            http_response_code($status);
            header('Content-Type: application/json');
            echo $asJSON;
            exit();
        }

        // aw snap! this is a problem
        echo "Could not convert result to JSON";
        switch ($errorCode) {
            case JSON_ERROR_DEPTH:
                echo ' - Maximum stack depth exceeded';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                echo ' - Underflow or the modes mismatch';
                break;
            case JSON_ERROR_CTRL_CHAR:
                echo ' - Unexpected control character found';
                break;
            case JSON_ERROR_SYNTAX:
                echo ' - Syntax error, malformed JSON';
                break;
            case JSON_ERROR_UTF8:
                echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
            default:
                echo ' - Unknown error';
                break;
        }
        exit();
    }

    /**
     * Recursively Convert ALL strings in a nested array or object to UTF-8 encoding 
     */
    public function encodeToUTF8(array|object $array_or_obect, $showOriginalStringOnError = false): array|object
    {
        // $possibleEncodings = mb_list_encodings();
        $possibleEncodings =  ['ASCII', 'UTF-8', 'ISO-8859-1'];
        foreach ($array_or_obect as $key => $value) {

            if (is_array($value) || is_object($value)) {
                if (is_array($array_or_obect)) {
                    $array_or_obect[$key] = $this->encodeToUTF8($value);
                } elseif (is_object($array_or_obect)) {
                    $array_or_obect->{$key} = $this->encodeToUTF8($value);
                }
            } else {
                if ($value == null) {
                    continue;
                }
                $encoding_from = mb_detect_encoding($value, $possibleEncodings, true);

                if (is_array($array_or_obect)) {
                    $array_or_obect[$key] = ($encoding_from != "UTF-8") ? mb_convert_encoding($value, 'UTF-8', $encoding_from) : $value;
                    if ($array_or_obect[$key] == false && $showOriginalStringOnError == true) {
                        $array_or_obect[$key] = $value;
                    }
                } elseif (is_object($array_or_obect)) {
                    $array_or_obect->{$key} = ($encoding_from != "UTF-8") ? mb_convert_encoding($value, 'UTF-8', $encoding_from) : $value;
                    if (!$array_or_obect->{$key} && $showOriginalStringOnError) {
                        $array_or_obect->{$key} = $value;
                    }
                }
            }
        }
        return $array_or_obect;
    }

    public function index()
    {
        exit("Error!<br />\n No Request action sent");
    }
}
