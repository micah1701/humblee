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
                $meta = json_decode($row->page_meta, true);
                if (!is_array($meta)) {
                    $meta = @unserialize($row->page_meta);
                }
                if (is_array($meta)) {
                    $entry['controller']        = $meta['controller'] ?? '';
                    $entry['controller_action'] = $meta['action'] ?? '';
                }
            } elseif ($row->page_type === 'view') {
                $entry['default_view'] = $row->page_meta;
            }

            // Include template block slots
            $tbRows = \ORM::for_table(_table_template_blocks)
                ->where('template_id', $row->id)
                ->order_by_asc('sort_order')
                ->find_many();
            $templateBlocks = [];
            foreach ($tbRows as $tb) {
                $templateBlocks[] = [
                    'id'            => (int)$tb->id,
                    'contentTypeId' => (int)$tb->content_type_id,
                    'label'         => $tb->label,
                    'slotKey'       => $tb->slot_key,
                    'sortOrder'     => (int)$tb->sort_order,
                ];
            }
            $entry['templateBlocks'] = $templateBlocks;

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

        $postedBlocks = [];
        if (isset($_POST['templateBlocks']) && is_string($_POST['templateBlocks'])) {
            $decoded = json_decode($_POST['templateBlocks'], true);
            if (is_array($decoded)) {
                $postedBlocks = $decoded;
            }
        }

        $page_type   = trim($_POST['page_type'] ?? '');
        $dynamic_uri = isset($_POST['dynamic_uri']) ? 1 : 0;
        $available   = isset($_POST['available'])   ? 1 : 0;

        switch ($page_type) {
            case 'view':
                $page_meta = trim($_POST['default_view'] ?? '');
                break;
            case 'controller':
                $page_meta = json_encode([
                    'controller' => trim($_POST['controller'] ?? ''),
                    'action'     => trim($_POST['controller_action'] ?? ''),
                ]);
                break;
            default:
                $page_type = 'default';
                $page_meta = 'tierpage';
        }

        // Derive the legacy `blocks` string from the posted slot list for backward compat
        $typeIds = array_unique(array_filter(
            array_map(fn($s) => isset($s['contentTypeId']) && is_numeric($s['contentTypeId']) ? (int)$s['contentTypeId'] : 0, $postedBlocks),
            fn($id) => $id > 0
        ));

        $row->name        = htmlspecialchars($name);
        $row->description = htmlspecialchars(trim($_POST['description'] ?? ''));
        $row->page_type   = $page_type;
        $row->page_meta   = $page_meta;
        $row->dynamic_uri = $dynamic_uri;
        $row->available   = $available;
        $row->blocks      = implode(',', $typeIds);
        $row->save();
        $templateId = (int)$row->id;

        // Upsert template block slots
        $postedIds = [];
        foreach ($postedBlocks as $sortOrder => $slot) {
            $ctId   = isset($slot['contentTypeId']) && is_numeric($slot['contentTypeId']) ? (int)$slot['contentTypeId'] : 0;
            $slotId = isset($slot['id']) && is_numeric($slot['id']) ? (int)$slot['id'] : null;

            if ($ctId === 0) {
                continue;
            }

            if ($slotId !== null) {
                // Update existing slot — label and sort_order only; slot_key is immutable
                $tb = \ORM::for_table(_table_template_blocks)->find_one($slotId);
                if ($tb && (int)$tb->template_id === $templateId) {
                    $tb->label      = htmlspecialchars(trim($slot['label'] ?? ''));
                    $tb->sort_order = $sortOrder;
                    $tb->save();
                    $postedIds[] = $slotId;
                }
            } else {
                // New slot — auto-generate slot_key and insert
                $existingCount = \ORM::for_table(_table_template_blocks)
                    ->where('template_id', $templateId)
                    ->where('content_type_id', $ctId)
                    ->count();
                $slotKey = self::generateSlotKey($ctId, $existingCount);

                $tb = \ORM::for_table(_table_template_blocks)->create();
                $tb->template_id     = $templateId;
                $tb->content_type_id = $ctId;
                $tb->label           = htmlspecialchars(trim($slot['label'] ?? ''));
                $tb->slot_key        = $slotKey;
                $tb->sort_order      = $sortOrder;
                $tb->save();
                $postedIds[] = (int)$tb->id;
            }
        }

        // Delete removed slots (any slot for this template whose id was not in the posted list)
        $allExisting = \ORM::for_table(_table_template_blocks)
            ->where('template_id', $templateId)
            ->find_many();
        foreach ($allExisting as $tb) {
            if (!in_array((int)$tb->id, $postedIds, true)) {
                $tb->delete();
            }
        }

        $ctrl->json(['success' => true, 'id' => $templateId]);
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

        // Remove associated template block slots
        \ORM::for_table(_table_template_blocks)
            ->where('template_id', (int)$_POST['id'])
            ->delete_many();

        $row->delete();
        $ctrl->json(['success' => true]);
    }

    private static function generateSlotKey(int $contentTypeId, int $existingCount): string
    {
        $ct = \ORM::for_table(_table_content_types)->find_one($contentTypeId);
        $base = $ct ? trim(preg_replace('/[^a-z0-9]+/', '_', strtolower((string)$ct->name)), '_') : 'block';
        return $existingCount === 0 ? $base : $base . '_' . ($existingCount + 1);
    }
}
