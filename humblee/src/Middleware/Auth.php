<?php

declare(strict_types=1);

namespace Humblee\Middleware;

use Humblee\Foundation\Core;
use Humblee\Model\Users;

class Auth implements Contract
{
    public function handle(Package $package): void
    {
        if (!isset($_SESSION[session_key]['user_id'])) {
            $uid = Core::checkRememberToken();
            if ($uid !== false) {
                (new Users())->logInSession($uid);
                Core::setRememberToken($uid); // slide the expiry window forward
            }
        }
    }
}
