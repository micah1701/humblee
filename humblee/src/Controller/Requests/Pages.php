<?php

declare(strict_types=1);

namespace Humblee\Controller\Requests;

use Humblee\Controller\Request;
use Humblee\Foundation\Core;
use Humblee\Model\Pages as PagesModel;

final class Pages
{
    public static function list(Request $ctrl): void
    {
        $ctrl->require_role('pages');
        $rows = \ORM::for_table(_table_pages)->order_by_asc('display_order')->find_many();
        $result = [];
        foreach ($rows as $page) {
            $result[] = [
                'id'               => (int)$page->id,
                'label'            => (string)$page->label,
                'slug'             => (string)$page->slug,
                'parentId'         => (int)$page->parent_id,
                'displayOrder'     => (int)$page->display_order,
                'active'           => (bool)$page->active,
                'displayInSitemap' => (bool)$page->display_in_sitemap,
                'templateId'       => (int)$page->template_id,
                'requiredRole'     => (int)$page->required_role,
            ];
        }
        $ctrl->json($result);
    }

    public static function loadContentMenu(Request $ctrl): void
    {
        $ctrl->require_role('content');
        $pageObj = new PagesModel;
        $menu = $pageObj->getPages(['active_only' => false, 'display_in_sitemap_only' => false]);
        $li_format = function ($item, $slug, $class) {
            return '<a href="' . _app_path . 'admin/edit/?page_id=' . $item->thisid . '">' . $item->label . '</a>';
        };
        echo $pageObj->drawMenu_UL($menu, ['li_format' => $li_format, 'id_label' => 'contentNav_']);
    }

    public static function loadTable(Request $ctrl): void
    {
        $ctrl->require_role('pages');
        $pages = new PagesModel;
        $all_pages = $pages->getPages(['active_only' => false, 'display_in_sitemap_only' => false]);
        $li_format = function ($item, $slug, $class) {
            return '<div class="pages_menu_item" data="' . $item->thisid . '"><a ' . $class . ' title="' . $slug . '">' . $item->label . '</a></div>';
        };
        echo $pages->drawMenu_UL($all_pages, ['li_format' => $li_format, 'id_label' => 'pageID_']);
    }

    public static function getProperties(Request $ctrl): void
    {
        $ctrl->require_role('pages');
        if (!isset($_POST['page_id']) || !is_numeric($_POST['page_id'])) {
            $ctrl->json(['error' => 'Invalid or missing page ID']);
        }

        $page = \ORM::for_table(_table_pages)->find_one($_POST['page_id']);
        if (!$page) {
            $ctrl->json(['error' => 'Page data not found']);
        }

        $active = ($page->active == 0) ? false : true;
        $searchable = ($page->searchable == 0) ? false : true;
        $display_in_sitemap = ($page->display_in_sitemap == 0) ? false : true;

        $checkTemplate = \ORM::for_table(_table_templates)->select('available')->find_one($page->template_id);

        $array = [
            "success" => true,
            "label" => $page->label,
            "slug" => $page->slug,
            "template_id" => $page->template_id,
            "required_role" => $page->required_role,
            "template_disabled" => ($checkTemplate->available == 0 && !Core::auth(['designer', 'developer'])) ? 1 : 0,
            "active" => $active,
            "display_in_sitemap" => $display_in_sitemap,
            "searchable" => $searchable
        ];

        $ctrl->json($array);
    }

    public static function setProperties(Request $ctrl): void
    {
        $ctrl->require_role('pages');
        $pages = new PagesModel;
        $page = $pages->add_or_update("update", $_POST);
        if (is_numeric($page)) {
            $ctrl->json(['success' => true, 'page_id' => $page]);
        }

        $ctrl->json(['error' => $page]);
    }

    public static function add(Request $ctrl): void
    {
        $ctrl->require_role('pages');
        $pages = new PagesModel;
        $newPage = $pages->add_or_update("add", $_POST);
        if (is_numeric($newPage)) {
            $ctrl->json(['success' => true, 'page_id' => $newPage]);
        }

        $ctrl->json(['error' => $newPage]);
    }

    public static function delete(Request $ctrl): void
    {
        $ctrl->require_role('pages');
        $pages = new PagesModel;
        $deletePage = $pages->add_or_update("delete", $_POST);
        if ($deletePage == "success") {
            $contents = \ORM::for_table(_table_content)->where('page_id', $_POST['page_id'])->find_many();
            foreach ($contents as $content) {
                $content->delete();
            }
            $ctrl->json(['success' => true]);
        }

        $ctrl->json(['error' => $deletePage]);
    }

    public static function order(Request $ctrl): void
    {
        $ctrl->require_role('pages');
        if (!isset($_POST['list_order']) || $_POST['list_order'] == "") {
            exit("Missing list order post data");
        }

        $list_order = json_decode(urldecode($_POST['list_order']), true);
        if (!is_array($list_order)) {
            exit("Invalid list order data");
        }

        $page_id = [];
        foreach ($list_order as $domId => $level) {
            $id = (int) str_replace('pageID_', '', $domId);
            if ($id > 0) {
                $page_id[$id] = (int) $level;
            }
        }

        $current_parent = 0;
        $last_level = 0;
        $last_id = 0;
        $parent_level = [];
        $order_pointer = [];
        foreach ($page_id as $id => $level) {
            if ($level > $last_level) {
                $parent_level[$last_level] = $current_parent;
                $current_parent = $last_id;
            }
            if ($level < $last_level) {
                $current_parent = $parent_level[$level];
            }
            if ($level == 0) {
                $current_parent = 0;
            }

            $order_pointer[$level] = (isset($order_pointer[$level])) ? $order_pointer[$level] + 1 : 0;

            $orderpage = \ORM::for_table(_table_pages)->find_one($id);
            $orderpage->parent_id = $current_parent;
            $orderpage->display_order = $order_pointer[$current_parent];
            $orderpage->save();

            $last_id = $id;
            $last_level = $level;
        }

        $ctrl->json(['success' => true]);
    }
}
