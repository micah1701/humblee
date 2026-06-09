<?php

declare(strict_types=1);

namespace Humblee\Controller\Requests;

use Humblee\Controller\Request;
use Humblee\Model\Content as ContentModel;
use Humblee\Model\Personalization;

final class Content
{
    public static function latestRevisionDate(Request $ctrl): void
    {
        $ctrl->require_role(['content', 'publish']);
        if (!isset($_POST['content_type']) || !is_numeric($_POST['content_type']) || !isset($_POST['page_id']) || !is_numeric($_POST['page_id'])) {
            $ctrl->json(['error' => 'Missing required parameters']);
        }
        $template_block_id = (isset($_POST['template_block_id']) && is_numeric($_POST['template_block_id']))
            ? (int)$_POST['template_block_id']
            : 0;
        $contentObj = new ContentModel;
        $content = $contentObj->listRevisions((int)$_POST['page_id'], (int)$_POST['content_type'], (int)$_POST['p13n_id'], 1, $template_block_id);

        if (!$content) {
            $ctrl->json(['error' => 'could not confirm previously saved content']);
        }

        $content = $content[0];
        $latestRevision = ['revision_date' => $content->revision_date, 'live' => $content->live, 'name' => $content->name];
        $ctrl->json(['success' => true, 'content' => $latestRevision]);
    }

    public static function pageMap(Request $ctrl): void
    {
        $ctrl->require_role(['content', 'developer']);

        $page_id = isset($_POST['page_id']) && is_numeric($_POST['page_id']) ? (int)$_POST['page_id'] : 0;
        if (!$page_id) {
            $ctrl->json(['error' => 'Missing or invalid page_id']);
        }

        $page = \ORM::for_table(_table_pages)->find_one($page_id);
        if (!$page) {
            $ctrl->json(['error' => 'Page not found']);
        }

        $template = \ORM::for_table(_table_templates)->find_one($page->template_id);

        // Get template slots (modern) or fall back to legacy blocks string
        $templateBlockRows = \ORM::for_table(_table_template_blocks)
            ->where('template_id', $page->template_id)
            ->order_by_asc('sort_order')
            ->find_many();

        $slots = [];
        if (!empty($templateBlockRows)) {
            foreach ($templateBlockRows as $tb) {
                $ct = \ORM::for_table(_table_content_types)->find_one($tb->content_type_id);
                $slots[] = [
                    'templateBlockId' => (int)$tb->id,
                    'slotKey'         => $tb->slot_key,
                    'label'           => $tb->label,
                    'contentTypeId'   => (int)$tb->content_type_id,
                    'contentTypeName' => $ct ? $ct->name : '',
                ];
            }
        } elseif ($template && $template->blocks) {
            $typeIds = array_filter(explode(',', (string)$template->blocks), 'is_numeric');
            if (!empty($typeIds)) {
                $contentTypes = \ORM::for_table(_table_content_types)
                    ->where_in('id', array_map('intval', $typeIds))
                    ->order_by_asc('name')
                    ->find_many();
                foreach ($contentTypes as $ct) {
                    // Negative templateBlockId signals legacy content (keyed by type_id, not slot)
                    $slots[] = [
                        'templateBlockId' => -(int)$ct->id,
                        'slotKey'         => $ct->objectkey,
                        'label'           => $ct->name,
                        'contentTypeId'   => (int)$ct->id,
                        'contentTypeName' => $ct->name,
                    ];
                }
            }
        }

        // P13n versions — always include default (id=0)
        $p13nVersions = [['id' => 0, 'name' => 'Default']];
        if ($_ENV['config']['use_p13n']) {
            $p13nRows = \ORM::for_table(_table_content_p13n)->order_by_asc('priority')->find_many();
            foreach ($p13nRows as $p) {
                $p13nVersions[] = ['id' => (int)$p->id, 'name' => $p->name];
            }
        }

        // Latest content record per (templateBlockId, p13nId) combination
        $contentRows = \ORM::for_table(_table_content)
            ->where('page_id', $page_id)
            ->order_by_desc('revision_date')
            ->find_many();

        // Reverse lookup: content_type_id → [templateBlockId, …] for modern slots only.
        // Used to remap legacy rows (template_block_id=0) that were created before template
        // blocks were added to the template — they still load fine on the public side via
        // findContent()'s legacy path, but would otherwise never match a modern slot key.
        $typeToModernSlots = [];
        foreach ($slots as $slot) {
            if ($slot['templateBlockId'] > 0) {
                $typeToModernSlots[$slot['contentTypeId']][] = $slot['templateBlockId'];
            }
        }

        $contentMap = [];
        foreach ($contentRows as $row) {
            $tbId = (int)$row->template_block_id;
            if ($tbId === 0) {
                // Legacy row: remap to the modern slot when there is exactly one unambiguous match.
                $matchingSlots = $typeToModernSlots[(int)$row->type_id] ?? [];
                $normalizedTbId = (count($matchingSlots) === 1)
                    ? $matchingSlots[0]
                    : -(int)$row->type_id;
            } else {
                $normalizedTbId = $tbId;
            }
            $key = $normalizedTbId . '_' . (int)$row->p13n_id;
            if (!isset($contentMap[$key])) {
                $contentMap[$key] = [
                    'id'              => (int)$row->id,
                    'typeId'          => (int)$row->type_id,
                    'templateBlockId' => $normalizedTbId,
                    'p13nId'          => (int)$row->p13n_id,
                    'live'            => (bool)$row->live,
                    'revisionDate'    => $row->revision_date,
                    'hasContent'      => ($row->content !== '' && $row->content !== null),
                ];
            }
        }

        $ctrl->json([
            'pageId'         => $page_id,
            'pageLabel'      => $page->label,
            'slots'          => $slots,
            'p13nVersions'   => $p13nVersions,
            'contentRecords' => array_values($contentMap),
        ]);
    }

    public static function p13nOrderPriorities(Request $ctrl): void
    {
        $ctrl->require_role('designer');
        if (!isset($_POST['list_order']) || !is_array($_POST['list_order'])) {
            $ctrl->json(['success' => false, 'error' => 'malformed request']);
        }
        foreach ($_POST['list_order'] as $priority => $persona_domID) {
            $domID_parts = explode('_', $persona_domID);
            $persona_id = end($domID_parts);
            $p13n = \ORM::for_table(_table_content_p13n)->find_one($persona_id);

            if (!$p13n) {
                $ctrl->json(['success' => false, 'error' => 'critical error: one or more persona\'s were not updated']);
            }

            $p13n->priority = $priority;
            $p13n->save();
        }

        $ctrl->json(['success' => true]);
    }
}
