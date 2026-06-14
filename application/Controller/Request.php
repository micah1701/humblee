<?php

namespace App\Controller;

use Humblee\Controller\Xhr;
use Humblee\Foundation\Core;

/**
 * Application-level AJAX request controller.
 *
 * Extend this class to add custom AJAX endpoints. Extend Xhr, which sends
 * no-cache headers and provides require_role() and json() helpers.
 */
class Request extends Xhr
{

    public function getUserProfile(): void
    {
        $this->require_role('login');
        $user = \ORM::for_table(_table_users)->where('id', $_SESSION[session_key]['user_id'])->find_array();
        Core::json($user[0]);
    }
}
