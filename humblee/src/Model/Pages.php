<?php

declare(strict_types=1);

namespace Humblee\Model;

use Humblee\Foundation\Core;

class Pages
{

	/**
	 * INSERT or UPDATE a page with user submitted POST data
	 *
	 * $action string  "update" or "add" or "delete"
	 * $post   ARRAY   $_POST data (requires "parent_id" for adding, "page_id" for updating/deleting)
	 *
	 * @returns MIXED  page_id (for adding/updating), "success" (for delete), or error string
	 */
	public function add_or_update(string $action, array $post): int|string
	{
		if ($action === "add") {
			if (!isset($post['parent_id']) || !is_numeric($post['parent_id'])) {
				return "Invalid Parent ID";
			}

			if ($post['parent_id'] != 0) {
				$parent = \ORM::for_table(_table_pages)->find_one($post['parent_id']);
				if ($parent->slug === "" || $parent->slug === "/") {
					return "Can not create child page of the homepage or a page without a valid uri slug";
				}
			}
			$page = \ORM::for_table(_table_pages)->create();
			$page->parent_id = $post['parent_id'];
		} elseif ($action === "update" && isset($post['page_id']) && is_numeric($post['page_id'])) {
			$page = \ORM::for_table(_table_pages)->find_one((int)$post['page_id']);
			if (!$page) {
				return "Could not find specified page to update.";
			}
		} elseif ($action === "delete" && isset($post['page_id']) && is_numeric($post['page_id'])) {
			$page = \ORM::for_table(_table_pages)->find_one((int)$post['page_id']);
			if (!$page) {
				return "Could not find specified page to delete.";
			}
			$subpages = \ORM::for_table(_table_pages)->where('parent_id', (int)$post['page_id'])->find_many();
			if ($subpages) {
				return "Can not delete this page because it has " . count($subpages) . " subpage(s).  Please move or delete child pages first.";
			}
			$page->delete();
			return "success";
		} else {
			return "Error: missing page action or page id";
		}

		$page->slug = (isset($post['slug']) && $post['slug'] !== "") ? $post['slug'] : "new-" . gmdate("YmdHis");
		$page->label = (isset($post['label']) && $post['label'] !== "") ? $post['label'] : "New Page";
		$page->display_order = (isset($post['display_order']) && $post['display_order'] !== "") ? $post['display_order'] : 0;
		$page->template_id = (isset($post['template_id']) && $post['template_id'] !== "") ? $post['template_id'] : 1;
		$page->active = (isset($post['active']) && is_numeric($post['active'])) ? (int)$post['active'] : 1;
		$page->searchable = (isset($post['searchable']) && is_numeric($post['searchable'])) ? (int)$post['searchable'] : 1;
		$page->display_in_sitemap = (isset($post['display_in_sitemap']) && is_numeric($post['display_in_sitemap'])) ? (int)$post['display_in_sitemap'] : 1;
		$page->required_role = (isset($post['required_role']) && is_numeric($post['required_role'])) ? (int)$post['required_role'] : 0;
		$page->save();

		return (int) $page->id();
	}

	/**
	 * Return the page row from the 'Pages' table for the given URI
	 *
	 * @param array $uri_parts      URI segments after the domain
	 * @param bool  $recursionFlag  Set to true when called recursively
	 * @return object|false
	 */
	public function getPagefromURL(array $uri_parts, bool $recursionFlag = false): object|false
	{
		// Security: validate all slug characters before using in SQL
		foreach ($uri_parts as $part) {
			if ($part !== "/" && $part !== "" && !preg_match('/^[\w\-\.]+$/', $part)) {
				return false;
			}
		}

		$route = implode("/", $uri_parts);

		if ($route === "" || $route === trim(_app_path, "/")) {
			$route = "";
			$uri_parts[0] = "/";
			$uri_num_parts = 1;
		} else {
			$uri_num_parts = count($uri_parts);
		}

		if ($uri_num_parts == 1) {
			$sql_where = "(slug = '{$route}' ";
			$sql_where .= ($route == "") ? " OR slug IS NULL) " : ") ";
			$sql_where .= "AND parent_id = 0";
		} else {
			$sql_where = "slug = '{$uri_parts[$uri_num_parts - 1]}' ";
			$sub_query_braces = "";

			for ($i = $uri_num_parts - 2; $i >= 0; $i--) {
				$check4noParent = ($i == 0) ? "AND parent_id = 0" : "";
				$sql_where .= "AND parent_id = (SELECT id FROM " . _table_pages . " WHERE slug = '{$uri_parts[$i]}' $check4noParent ";
				$sub_query_braces .= ")";
			}
			$sql_where .= $sub_query_braces;
		}

		$results = \ORM::for_table(_table_pages)
			->where('active', 1)
			->where_raw($sql_where)
			->find_one();

		if (!$results) {
			array_pop($uri_parts);
			if (count($uri_parts) === 0) {
				return false;
			}
			return $this->getPagefromURL($uri_parts, true);
		} else {
			if ($recursionFlag) {
				$template = \ORM::for_table(_table_templates)->select('dynamic_uri')->find_one($results->template_id);
				if ($template->dynamic_uri != 1) {
					return false;
				}
			}

			return $results;
		}
	}

	/**
	 * Get the Pages (and subpages) of a given Parent ID
	 *
	 * @param array $params    Options: parent_id, generations, selected_pages, generate_uri,
	 *                         active_only, display_in_sitemap_only, menu_id
	 * @param int   $generation Leave blank — used by recursive calls
	 * @return object
	 */
	public function getPages(array $params = ['active_only' => true, 'display_in_sitemap_only' => true], int $generation = 0): object
	{
		$menu = [];
		$parent_id = (array_key_exists('parent_id', $params) && is_numeric($params['parent_id'])) ? $params['parent_id'] : 0;
		$params['toplevel_parent'] = $params['toplevel_parent'] ?? $parent_id;

		if (array_key_exists('menu_id', $params) && is_numeric($params['menu_id'])) {
			$table = _table_menus_pages;
			$active = (array_key_exists('active_only', $params) && $params['active_only']) ? " AND m.active = 1 " : "";
			$sitemap = (array_key_exists('display_in_sitemap_only', $params) && $params['display_in_sitemap_only']) ? " AND m.display_in_sitemap = 1 " : "";
			$sql = 'SELECT m.id as thisid,
					 m.slug, m.label, m.template_id,
					 m.parent_id as thisparentid,
					 m.display_in_sitemap,
					 children.cnt as children,
					 parent.slug as parentname
					 FROM ' . $table . ' m
					 LEFT JOIN (SELECT parent_id, COUNT(id) as cnt FROM ' . $table . ' GROUP BY parent_id) children ON children.parent_id = m.id
					 LEFT JOIN ' . $table . ' parent ON parent.id = m.parent_id
					 WHERE m.parent_id = ' . $parent_id . $active . $sitemap . ' ORDER BY m.display_order';
		} else {
			$table = _table_pages;
			$active = (array_key_exists('active_only', $params) && $params['active_only']) ? " AND p.active = 1 " : "";
			$sitemap = (array_key_exists('display_in_sitemap_only', $params) && $params['display_in_sitemap_only']) ? " AND p.display_in_sitemap = 1 " : "";
			$sql = 'SELECT p.id as thisid,
					 p.slug, p.label, p.template_id,
					 p.parent_id as thisparentid,
					 p.required_role,
					 p.active,
					 p.display_in_sitemap,
					 children.cnt as children,
					 parent.slug as parentname
					 FROM ' . $table . ' p
					 LEFT JOIN (SELECT parent_id, COUNT(id) as cnt FROM ' . $table . ' GROUP BY parent_id) children ON children.parent_id = p.id
					 LEFT JOIN ' . $table . ' parent ON parent.id = p.parent_id
					 WHERE p.parent_id = ' . $parent_id . $active . $sitemap . ' ORDER BY p.display_order';
		}

		$pages = \ORM::for_table($table)->raw_query($sql, [])->find_many();

		$i = 0;
		foreach ($pages as $page) {
			if (
				array_key_exists('selected_pages', $params) &&
				is_array($params['selected_pages']) &&
				$page->thisparentid == $params['toplevel_parent'] &&
				!in_array($page->thisid, $params['selected_pages'])
			) {
				continue;
			}

			if ($i === 0) {
				$generation++;
			}

			if (array_key_exists('generations', $params) && is_numeric($params['generations']) && $generation > $params['generations']) {
				return (object)$menu;
			}

			if (array_key_exists('generate_uri', $params) && $params['generate_uri']) {
				$page->uri = $this->buildLink($page->thisid);
			}

			$menu[$i] = $page;
			$menu[$i]->generation = $generation;

			if ($page->children > 0) {
				$params['parent_id'] = $page->thisid;
				$menu[$i]->child_pages = $this->getPages($params, $generation);
			}

			$i++;
		}

		return (object)$menu;
	}

	/**
	 * Draw HTML Menu as an Unordered List (UL) from a pages object
	 *
	 * $pages   OBJECT  data from getPages()
	 * $params  ARRAY (optional)
	 *  'li_format'             CALLABLE  receives ($item, $slug, $class) and returns HTML string for each list item
	 *  'id_label'              STRING    text prepended to page ID for the <li> DOM ID
	 *  'thisID'                INT       ID of the calling page
	 *  'currentChildrenOnly'   BOOL      only show child subpages if current page is one of them
	 *  'hasChildrenClass'      STRING    custom class if item has children
	 *  'currentPageClass'      STRING    custom class for current page item
	 *  'currentPageParentClass' STRING   custom class for parent of current page
	 *
	 * $slugRoot STRING  used by recursive calls
	 * $html     STRING  used by recursive calls
	 */
	public function drawMenu_UL(object $pages, array $params = [], string $slugRoot = "", string $html = ""): string
	{
		if (count((array)$pages) > 0) {
			$html .= "<ul>\n";
			foreach ($pages as $item) {
				if ($slugRoot === "" && $item->thisparentid != 0) {
					$slugRoot = $this->buildLink($item->thisparentid);
				}

				$newSlug = isset($item->slug) ? $slugRoot . "/" . $item->slug : $slugRoot;

				$item->label = preg_replace("/&/", "&amp;", $item->label);

				$defaultFormat = function ($item, $slug, $class) {
					return '<a href="' . $slug . '" ' . $class . '>' . $item->label . '</a>';
				};
				$liFormat = (array_key_exists('li_format', $params) && is_callable($params['li_format'])) ? $params['li_format'] : $defaultFormat;

				$currentChildrenOnly = $params['currentChildrenOnly'] ?? false;
				$currentParent = false;
				$drawClass = 'class="';

				if ($item->children > 0 && count((array)$item->child_pages) > 0) {
					$drawClass .= array_key_exists('hasChildrenClass', $params) ? $params['hasChildrenClass'] . ' ' : 'menu_hasChildren ';

					if (array_key_exists('thisID', $params)) {
						$crumbs = $this->getBreadcrumbs($params['thisID']);
						foreach ($crumbs as $crumb) {
							if ($item->thisid == $crumb['id']) {
								$drawClass .= array_key_exists('currentPageParentClass', $params) ? $params['currentPageParentClass'] . ' ' : 'menu_currentPageParent ';
								$currentParent = true;
								continue;
							}
						}
					}
				}

				if (array_key_exists('thisID', $params) && $item->thisid == $params['thisID']) {
					$drawClass .= array_key_exists('currentPageClass', $params) ? $params['currentPageClass'] . ' ' : 'menu_currentPage ';
				}

				$drawClass .= '"';

				$html .= array_key_exists('id_label', $params) ? '<li id="' . $params['id_label'] . $item->thisid . '" ' . $drawClass . '>' : '<li ' . $drawClass . '>';
				$html .= $liFormat($item, $newSlug, $drawClass);

				if ($item->children > 0 && count((array)$item->child_pages) > 0 && (!$currentChildrenOnly || $currentParent)) {
					$html = $this->drawMenu_UL($item->child_pages, $params, $newSlug, $html);
				}

				$html .= " </li>\n";
			}
			$html .= "</ul>\n";
		}
		return $html;
	}

	/**
	 * Generate an array of parent pages making up a given page's "file path" location
	 * Returns array of ['label', 'slug', 'id', 'parent_id'] for each parent page
	 */
	public function getBreadcrumbs(int $page_id): array
	{
		$parent = $page_id;
		$go = true;
		$breadcrumbs = [];
		while ($go) {
			$result = \ORM::for_table(_table_pages)->find_one($parent);

			if ($result) {
				$breadcrumbs[] = ['label' => $result->label, 'slug' => $result->slug, 'id' => $result->id, 'parent_id' => $result->parent_id];
				$go = ($result->parent_id == 0) ? false : true;
				$parent = $result->parent_id;
			} else {
				$breadcrumbs[]['ERROR! URI NOT FOUND ON SITE'] = "#__BROKEN_INTERNAL_LINK";
				$go = false;
			}
		}
		return array_reverse($breadcrumbs);
	}

	/**
	 * Return URI of given page
	 */
	public function buildLink(int $page_id): string
	{
		$breadcrumb_array = $this->getBreadcrumbs($page_id);
		$full_route = "";
		foreach ($breadcrumb_array as $crumb) {
			$full_route .= ($crumb['slug'] !== "/") ? "/" : "";
			$full_route .= $crumb['slug'];
		}
		return $full_route;
	}

	/**
	 * Draw HTML Breadcrumbs as inline list
	 */
	public function drawBreadcrumbs(int $page_id, string $delimiter = " &gt; "): string
	{
		$breadcrumbs = $this->getBreadcrumbs($page_id);
		$crumb_html = '';
		$crumb_levels = count($breadcrumbs);
		$crumb_path = "";
		$i = 1;
		foreach ($breadcrumbs as $crumb) {
			if ($i === $crumb_levels) {
				$crumb_html .= $crumb['label'];
			} else {
				$crumb_path .= '/' . $crumb['slug'];
				$crumb_html .= '<a href="' . $crumb_path . '">' . $crumb['label'] . '</a>' . $delimiter;
			}
			$i++;
		}

		return $crumb_html;
	}
}
