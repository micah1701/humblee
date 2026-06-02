<?php

declare(strict_types=1);

namespace Humblee\Controller\Requests;

use Humblee\Controller\Request;

final class Blocks
{
    public static function list(Request $ctrl): void
    {
        $ctrl->require_role('designer');
        $rows = \ORM::for_table(_table_content_types)->order_by_asc('name')->find_many();
        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'id'               => (int)$row->id,
                'name'             => $row->name,
                'objectkey'        => $row->objectkey,
                'description'      => $row->description,
                'output_type'      => $row->output_type,
                'input_type'       => $row->input_type,
                'input_parameters' => $row->input_parameters,
            ];
        }
        $ctrl->json($result);
    }

    public static function save(Request $ctrl): void
    {
        $ctrl->require_role('designer');

        $id   = isset($_POST['id']) && is_numeric($_POST['id']) ? (int)$_POST['id'] : null;
        $name = trim($_POST['name'] ?? '');
        $key  = trim($_POST['objectkey'] ?? '');

        if ($name === '') {
            $ctrl->json(['success' => false, 'errors' => ['Name field cannot be blank']]);
        }
        if ($key === '') {
            $ctrl->json(['success' => false, 'errors' => ['objectKey field cannot be blank']]);
        }

        $row = ($id !== null)
            ? \ORM::for_table(_table_content_types)->find_one($id)
            : \ORM::for_table(_table_content_types)->create();

        if (!$row) {
            $ctrl->json(['success' => false, 'errors' => ['Record not found']]);
        }

        $row->name             = htmlspecialchars($name);
        $row->objectkey        = htmlspecialchars($key);
        $row->description      = htmlspecialchars(trim($_POST['description'] ?? ''));
        $row->output_type      = htmlspecialchars(trim($_POST['output_type'] ?? ''));
        $row->input_type       = htmlspecialchars(trim($_POST['input_type'] ?? ''));
        $row->input_parameters = trim($_POST['input_parameters'] ?? '');
        $row->required_role    = isset($_POST['required_role']) && is_numeric($_POST['required_role']) ? (int)$_POST['required_role'] : 0;
        $row->save();

        $ctrl->json(['success' => true, 'id' => (int)$row->id]);
    }

    public static function delete(Request $ctrl): void
    {
        $ctrl->require_role('designer');

        if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
            $ctrl->json(['success' => false, 'errors' => ['Invalid or missing id']]);
        }

        $row = \ORM::for_table(_table_content_types)->find_one((int)$_POST['id']);
        if (!$row) {
            $ctrl->json(['success' => false, 'errors' => ['Record not found']]);
        }

        $row->delete();
        $ctrl->json(['success' => true]);
    }
}
