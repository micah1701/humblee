<?php

declare(strict_types=1);

namespace Humblee\Model;

use Humblee\Foundation\Core;

class Content
{

	private function requireContentRoles(): void
	{
		if (!Core::auth(['content', 'publish', 'developer'])) {
			exit("You do not have permission to access this function.");
		}
	}

	/**
	 * Return all Content revisions for a given page's content type
	 * Includes name of user who saved content
	 *
	 * $page_id            integer REQUIRED
	 * $content_type       integer REQUIRED
	 * $p13n_id            integer optional personalization version ID (0 = default)
	 * $max                integer Maximum number of recent revisions to display (0 = all, up to 9999)
	 * $template_block_id  integer optional slot ID (0 = legacy)
	 */
	public function listRevisions(int $page_id, int $content_type, int $p13n_id = 0, int $max = 10, int $template_block_id = 0): mixed
	{
		$this->requireContentRoles();

		$limit = ($max > 0) ? $max : 9999;
		return \ORM::for_table(_table_content)
			->select(_table_content . '.*')
			->select(_table_users . '.name')
			->join(_table_users, [_table_content . '.updated_by', '=', _table_users . '.id'])
			->where('page_id', $page_id)
			->where('type_id', $content_type)
			->where('p13n_id', $p13n_id)
			->where('template_block_id', $template_block_id)
			->order_by_desc('revision_date')
			->limit($limit)
			->find_many();
	}

	/**
	 * Save Content for a given page, content block and p13n version
	 *
	 * $post ARRAY required values:
	 *          content_id (last known content id being replaced)
	 *          page_id
	 *          content_type_id
	 *          content (new content)
	 *        optional:
	 *          serialize_fields (comma-separated list of field names to JSON-encode into content)
	 *          template_block_id (slot ID, 0 = legacy)
	 *          arbitrary fields (listed in serialize_fields)
	 *
	 * Returns the new content object or FALSE if nothing changed
	 */
	public function saveContent(array $post): object|false
	{
		$this->requireContentRoles();

		if (!is_numeric($post['content_type_id']) || !is_numeric($post['page_id'])) {
			return false;
		}
		if (!isset($post['p13n_id'])) {
			$post['p13n_id'] = 0;
		}

		$template_block_id = (isset($post['template_block_id']) && is_numeric($post['template_block_id']))
			? (int)$post['template_block_id']
			: 0;

		if (isset($post['serialize_fields'])) {
			$fields = explode(",", $post['serialize_fields']);
			$content_array = [];
			foreach ($fields as $field) {
				$content_array[$field] = $post[$field] ?? "";
			}
			$post['content'] = json_encode($content_array);
		}

		$current_content = \ORM::for_table(_table_content)->find_one($post['content_id']);
		$content = $post['content'];

		$previous_revisions = \ORM::for_table(_table_content)
			->where('page_id', $post['page_id'])
			->where('type_id', $post['content_type_id'])
			->where('p13n_id', $post['p13n_id'])
			->where('template_block_id', $template_block_id)
			->count();

		if ($current_content && $current_content->content !== $content) {
			// If there is only 1 revision and it is blank, this is the initial save — reuse the same row
			$new_content = ($previous_revisions == 1 && trim($current_content->content) === "") ? $current_content : \ORM::for_table(_table_content)->create();

			$new_content->type_id           = $post['content_type_id'];
			$new_content->page_id           = $post['page_id'];
			$new_content->p13n_id           = $post['p13n_id'];
			$new_content->template_block_id = $template_block_id;
			$new_content->content           = $content;
			$new_content->live              = 0;
			$new_content->publish_date      = null;
			$new_content->revision_date     = gmdate("Y-m-d H:i:s");
			$new_content->updated_by        = $_SESSION[session_key]['user_id'];
			$new_content->save();
		} else {
			$new_content = false;
		}

		if ($post['live'] == "1" && Core::auth(['publish', 'developer'])) {
			$old_live = \ORM::for_table(_table_content)
				->where('page_id', $post['page_id'])
				->where('type_id', $post['content_type_id'])
				->where('p13n_id', $post['p13n_id'])
				->where('template_block_id', $template_block_id)
				->where('live', 1)
				->find_one();
			if ($old_live) {
				$old_live->live = 0;
				$old_live->save();
			}

			if (!$new_content) {
				if ($current_content && $current_content->live == 0) {
					$current_content->publish_date = gmdate("Y-m-d H:i:s");
					$current_content->updated_by   = $_SESSION[session_key]['user_id'];
					$current_content->live         = 1;
					$current_content->save();
				}
			} else {
				$new_content->publish_date = gmdate("Y-m-d H:i:s");
				$new_content->live         = 1;
				$new_content->save();
			}
		}

		return $new_content;
	}


	/**
	 * Find all live content for a given page
	 * Returns associative array of content objects keyed by slot_key
	 */
	public function findContent(int $page_id): array
	{
		if ($_ENV['config']['use_p13n']) {
			$p13nObj = new Personalization();
			$p13n_matching = $p13nObj->getAll(true, true);

			if (!empty($p13n_matching)) {
				$published = \ORM::for_table(_table_content)
					->where('page_id', $page_id)
					->where_not_null('publish_date')
					->where_in('p13n_id', $p13n_matching)
					->select('p13n_id')
					->find_many();
				$published_ids = array_unique(array_map(fn($r) => (int)$r->p13n_id, $published));
				$p13n_versions = array_values(array_filter($p13n_matching, fn($id) => in_array((int)$id, $published_ids, true)));
			} else {
				$p13n_versions = [];
			}

			$p13n_versions[] = 0;
		} else {
			$p13n_versions = [0];
		}

		$rows = \ORM::for_table(_table_content)
			->select(_table_content . '.*')
			->select(_table_content . '.id', 'content_id')
			->select(_table_content_types . '.*')
			->select(_table_content_types . '.id', 'block_id')
			->select(_table_content_p13n . '.id', 'p13n_id')
			->select(_table_template_blocks . '.slot_key', 'slot_key')
			->select(_table_template_blocks . '.label', 'slot_label')
			->select(_table_template_blocks . '.id', 'template_block_id')
			->join(_table_content_types, [_table_content . ".type_id", "=", _table_content_types . ".id"])
			->join(_table_template_blocks, [_table_content . ".template_block_id", "=", _table_template_blocks . ".id"])
			->left_outer_join(_table_content_p13n, [_table_content . ".p13n_id", "=", _table_content_p13n . ".id"])
			->where('page_id', $page_id)
			->where_in(_table_content . '.p13n_id', $p13n_versions)
			->where('live', 1)
			->find_many();

		$contents = [];

		foreach ($rows as $content) {
			$key = $content->slot_key;
			$contents[$key] = $content;
			if ($content->input_type === "markdown") {
				$Parsedown = new \Parsedown();
				$contents[$key]['content'] = $Parsedown->instance()->text($contents[$key]['content']);
			}
		}

		return $contents;
	}
}
