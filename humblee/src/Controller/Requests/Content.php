<?php

declare(strict_types=1);

namespace Humblee\Controller\Requests;

use Humblee\Controller\Request;
use Humblee\Model\Content as ContentModel;

final class Content
{
    public static function latestRevisionDate(Request $ctrl): void
    {
        $ctrl->require_role(['content', 'publish']);
        if (!isset($_POST['content_type']) || !is_numeric($_POST['content_type']) || !isset($_POST['page_id']) || !is_numeric($_POST['page_id'])) {
            $ctrl->json(['error' => 'Missing required parameters']);
        }
        $contentObj = new ContentModel;
        $content = $contentObj->listRevisions((int)$_POST['page_id'], (int)$_POST['content_type'], (int)$_POST['p13n_id'], 1);

        if (!$content) {
            $ctrl->json(['error' => 'could not confirm previously saved content']);
        }

        $content = $content[0];
        $latestRevision = ['revision_date' => $content->revision_date, 'live' => $content->live, 'name' => $content->name];
        $ctrl->json(['success' => true, 'content' => $latestRevision]);
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
