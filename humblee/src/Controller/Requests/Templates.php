<?php

declare(strict_types=1);

namespace Humblee\Controller\Requests;

use Humblee\Controller\Request;

final class Templates
{
    public static function list(Request $ctrl): void
    {
        $ctrl->require_role('designer');
        $rows = \ORM::for_table(_table_templates)->order_by_asc('name')->find_many();
        $result = [];
        foreach ($rows as $row) {
            $entry = [
                'id'          => (int)$row->id,
                'name'        => $row->name,
                'description' => $row->description,
                'page_type'   => $row->page_type,
                'page_meta'   => $row->page_meta,
                'dynamic_uri' => (int)$row->dynamic_uri,
                'available'   => (int)$row->available,
                'blocks'      => $row->blocks,
                // derived fields
                'controller'        => '',
                'controller_action' => '',
                'default_view'      => '',
            ];
            if ($row->page_type === 'controller') {
                $meta = @unserialize($row->page_meta);
                if (is_array($meta)) {
                    $entry['controller']        = $meta['controller'] ?? '';
                    $entry['controller_action'] = $meta['action'] ?? '';
                }
            } elseif ($row->page_type === 'view') {
                $entry['default_view'] = $row->page_meta;
            }
            $result[] = $entry;
        }
        $ctrl->json($result);
    }

    public static function save(Request $ctrl): void
    {
        $ctrl->require_role('designer');

        $id   = isset($_POST['id']) && is_numeric($_POST['id']) ? (int)$_POST['id'] : null;
        $name = trim($_POST['name'] ?? '');

        if ($name === '') {
            $ctrl->json(['success' => false, 'errors' => ['Name field cannot be blank']]);
        }

        $row = ($id !== null)
            ? \ORM::for_table(_table_templates)->find_one($id)
            : \ORM::for_table(_table_templates)->create();

        if (!$row) {
            $ctrl->json(['success' => false, 'errors' => ['Record not found']]);
        }

        $blocks_raw  = $_POST['blocks'] ?? [];
        $page_type   = trim($_POST['page_type'] ?? '');
        $dynamic_uri = isset($_POST['dynamic_uri']) ? 1 : 0;
        $available   = isset($_POST['available'])   ? 1 : 0;

        switch ($page_type) {
            case 'view':
                $page_meta = trim($_POST['default_view'] ?? '');
                break;
            case 'controller':
                $page_meta = serialize([
                    'controller' => trim($_POST['controller'] ?? ''),
                    'action'     => trim($_POST['controller_action'] ?? ''),
                ]);
                break;
            default:
                $page_type = 'default';
                $page_meta = 'tierpage';
        }

        $row->name        = htmlspecialchars($name);
        $row->description = htmlspecialchars(trim($_POST['description'] ?? ''));
        $row->page_type   = $page_type;
        $row->page_meta   = $page_meta;
        $row->dynamic_uri = $dynamic_uri;
        $row->available   = $available;
        $row->blocks      = is_array($blocks_raw) ? implode(',', array_filter($blocks_raw, 'is_numeric')) : '';
        $row->save();

        $ctrl->json(['success' => true, 'id' => (int)$row->id]);
    }

    public static function delete(Request $ctrl): void
    {
        $ctrl->require_role('designer');

        if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
            $ctrl->json(['success' => false, 'errors' => ['Invalid or missing id']]);
        }

        $row = \ORM::for_table(_table_templates)->find_one((int)$_POST['id']);
        if (!$row) {
            $ctrl->json(['success' => false, 'errors' => ['Record not found']]);
        }

        $row->delete();
        $ctrl->json(['success' => true]);
    }
}
