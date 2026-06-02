<?php

declare(strict_types=1);

namespace Humblee\Controller\Requests;

use Humblee\Controller\Request;
use Humblee\Model\Users as UsersModel;

final class Users
{
    public static function remove(Request $ctrl): void
    {
        $ctrl->require_role('users');

        if (!isset($_POST['userID']) || !is_numeric($_POST['userID'])) {
            $ctrl->json(["success" => false, "error" => "invalid or missing user ID"]);
        }
        $userObj = new UsersModel;
        if ($userObj->deleteUser($_POST['userID'])) {
            $ctrl->json(["success" => true]);
        } else {
            $ctrl->json(["success" => false, "error" => "Could not delete the user"]);
        }
    }

    public static function setRoles(Request $ctrl): void
    {
        $ctrl->require_role('users');
        if (!isset($_POST['userID']) || !is_numeric($_POST['userID'])) {
            $ctrl->json(["success" => false, "error" => "invalid or missing user ID"]);
        }
        $userObj = new UsersModel;

        $userObj->stripRoles($_POST['userID']);

        $roles = explode(",", $_POST['roles']);
        foreach ($roles as $role) {
            if (!is_int($role)) {
                continue;
            }
            $userObj->addRole($_POST['userID'], $role);
        }
        $ctrl->json(["success" => true]);
    }
}
