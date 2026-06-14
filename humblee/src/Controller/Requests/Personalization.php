<?php

declare(strict_types=1);

namespace Humblee\Controller\Requests;

use Humblee\Controller\Request;

final class Personalization
{
    public static function list(Request $ctrl): void
    {
        $ctrl->require_role('designer');
        $rows = \ORM::for_table(_table_content_p13n)->order_by_asc('priority')->find_many();
        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'id'          => (int)$row->id,
                'name'        => $row->name,
                'description' => $row->description,
                'active'      => (int)$row->active,
                'criteria'    => $row->criteria,
                'priority'    => (int)$row->priority,
            ];
        }
        Core::json($result);
    }

    public static function save(Request $ctrl): void
    {
        $ctrl->require_role('designer');

        $id   = isset($_POST['id']) && is_numeric($_POST['id']) ? (int)$_POST['id'] : null;
        $name = trim($_POST['name'] ?? '');

        if ($name === '') {
            Core::json(['success' => false, 'errors' => ['Name field cannot be blank']]);
        }

        $criteria = trim($_POST['criteria'] ?? '');
        if ($criteria !== '' && json_decode($criteria) === null) {
            Core::json(['success' => false, 'errors' => ['Invalid criteria format']]);
        }

        $row = ($id !== null)
            ? \ORM::for_table(_table_content_p13n)->find_one($id)
            : \ORM::for_table(_table_content_p13n)->create();

        if (!$row) {
            Core::json(['success' => false, 'errors' => ['Record not found']]);
        }

        $row->name        = htmlspecialchars($name);
        $row->description = htmlspecialchars(trim($_POST['description'] ?? ''));
        $row->active      = isset($_POST['active']) ? (int)$_POST['active'] : 0;
        $row->criteria    = $criteria;

        if ($id === null) {
            $maxPriority = \ORM::for_table(_table_content_p13n)->max('priority') ?? 0;
            $row->priority = (int)$maxPriority + 1;
        }

        $row->save();
        Core::json(['success' => true, 'id' => (int)$row->id]);
    }

    public static function delete(Request $ctrl): void
    {
        $ctrl->require_role('designer');

        if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
            Core::json(['success' => false, 'errors' => ['Invalid or missing id']]);
        }

        $row = \ORM::for_table(_table_content_p13n)->find_one((int)$_POST['id']);
        if (!$row) {
            Core::json(['success' => false, 'errors' => ['Record not found']]);
        }

        $row->delete();
        Core::json(['success' => true]);
    }
}
