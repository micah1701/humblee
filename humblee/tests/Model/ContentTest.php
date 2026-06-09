<?php

declare(strict_types=1);

namespace Tests\Model;

use Humblee\Model\Content;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Content::class)]
class ContentTest extends TestCase
{
    private Content $content;

    /**
     * Runs once for the class: configure an in-memory SQLite database and
     * create the minimal schema that Content's three public methods need.
     * We also pre-populate $_SESSION so Core::auth() returns true without
     * touching the database — it only calls cacheUserRoles() when has_roles
     * is absent from the session, so a seeded session bypasses that entirely.
     */
    public static function setUpBeforeClass(): void
    {
        \ORM::configure('sqlite::memory:');

        $db = \ORM::get_db();

        $db->exec('CREATE TABLE humblee_content (
            id                INTEGER PRIMARY KEY AUTOINCREMENT,
            type_id           INTEGER NOT NULL,
            page_id           INTEGER NOT NULL,
            p13n_id           INTEGER NOT NULL DEFAULT 0,
            template_block_id INTEGER NOT NULL DEFAULT 0,
            content           TEXT    NOT NULL DEFAULT \'\',
            live              INTEGER NOT NULL DEFAULT 0,
            publish_date      TEXT,
            revision_date     TEXT    NOT NULL,
            updated_by        INTEGER NOT NULL
        )');

        $db->exec('CREATE TABLE humblee_template_blocks (
            id              INTEGER PRIMARY KEY AUTOINCREMENT,
            template_id     INTEGER NOT NULL,
            content_type_id INTEGER NOT NULL,
            label           TEXT    NOT NULL DEFAULT \'\',
            slot_key        TEXT    NOT NULL DEFAULT \'\',
            sort_order      INTEGER NOT NULL DEFAULT 0
        )');

        $db->exec('CREATE TABLE humblee_users (
            id   INTEGER PRIMARY KEY,
            name TEXT NOT NULL
        )');

        $db->exec('CREATE TABLE humblee_content_types (
            id         INTEGER PRIMARY KEY,
            input_type TEXT NOT NULL DEFAULT \'text\'
        )');

        $db->exec('CREATE TABLE humblee_content_p13n (
            id INTEGER PRIMARY KEY
        )');

        // Seed a user — required because listRevisions() does an INNER JOIN with users
        $db->exec("INSERT INTO humblee_users (id, name) VALUES (1, 'Test User')");

        // Seed a content type — required because findContent() does an INNER JOIN with content_types
        $db->exec("INSERT INTO humblee_content_types (id, input_type) VALUES (1, 'text')");

        // Pre-seed session so Core::auth() finds has_roles and skips the DB lookup
        $_SESSION[session_key] = [
            'user_id'   => 1,
            'has_roles' => [1 => 'content', 2 => 'publish', 3 => 'developer'],
        ];
    }

    protected function setUp(): void
    {
        // Wipe content and slot rows before every test so tests are fully isolated
        \ORM::get_db()->exec('DELETE FROM humblee_content');
        \ORM::get_db()->exec('DELETE FROM humblee_template_blocks');
        $this->content = new Content();
    }

    // =========================================================================
    // saveContent — input validation (returns false before touching the ORM)
    // =========================================================================

    public function test_save_content_returns_false_for_non_numeric_content_type(): void
    {
        $result = $this->content->saveContent([
            'content_id'      => 0,
            'page_id'         => 1,
            'content_type_id' => 'not-a-number',
            'content'         => 'hello',
            'live'            => '0',
        ]);

        $this->assertFalse($result);
    }

    public function test_save_content_returns_false_for_non_numeric_page_id(): void
    {
        $result = $this->content->saveContent([
            'content_id'      => 0,
            'page_id'         => 'not-a-number',
            'content_type_id' => 1,
            'content'         => 'hello',
            'live'            => '0',
        ]);

        $this->assertFalse($result);
    }

    // =========================================================================
    // saveContent — ORM-dependent paths
    // =========================================================================

    public function test_save_content_returns_false_when_content_id_not_found(): void
    {
        // find_one(999) finds nothing → $current_content is null → returns false
        $result = $this->content->saveContent([
            'content_id'      => 999,
            'page_id'         => 1,
            'content_type_id' => 1,
            'content'         => 'hello',
            'live'            => '0',
        ]);

        $this->assertFalse($result);
    }

    public function test_save_content_returns_false_when_content_is_unchanged(): void
    {
        $row = $this->insertContentRow(page_id: 1, type_id: 1, content: 'unchanged text');

        $result = $this->content->saveContent([
            'content_id'      => $row->id(),
            'page_id'         => 1,
            'content_type_id' => 1,
            'content'         => 'unchanged text', // same as stored
            'live'            => '0',
        ]);

        $this->assertFalse($result);
    }

    public function test_save_content_creates_new_revision_when_content_changes(): void
    {
        $row = $this->insertContentRow(page_id: 1, type_id: 1, content: 'original text');

        $result = $this->content->saveContent([
            'content_id'      => $row->id(),
            'page_id'         => 1,
            'content_type_id' => 1,
            'content'         => 'updated text',
            'live'            => '0',
        ]);

        $this->assertNotFalse($result);
        $this->assertSame('updated text', $result->content);
        $this->assertSame(0, (int) $result->live);
    }

    public function test_save_content_reuses_first_row_on_initial_save(): void
    {
        // Only one revision exists AND its content is blank → update in place,
        // do not insert a new row.
        $row        = $this->insertContentRow(page_id: 2, type_id: 1, content: '');
        $originalId = $row->id();

        $this->content->saveContent([
            'content_id'      => $originalId,
            'page_id'         => 2,
            'content_type_id' => 1,
            'content'         => 'first real content',
            'live'            => '0',
        ]);

        $count = \ORM::for_table(_table_content)->where('page_id', 2)->count();
        $this->assertSame(1, $count, 'Initial save should reuse the existing row, not create a new one');
    }

    public function test_save_content_serializes_named_fields_into_json(): void
    {
        $row = $this->insertContentRow(page_id: 3, type_id: 1, content: '');

        $result = $this->content->saveContent([
            'content_id'       => $row->id(),
            'page_id'          => 3,
            'content_type_id'  => 1,
            'content'          => '',
            'live'             => '0',
            'serialize_fields' => 'title,body',
            'title'            => 'Hello',
            'body'             => 'World',
        ]);

        $this->assertNotFalse($result);
        $decoded = json_decode($result->content, true);
        $this->assertSame('Hello', $decoded['title']);
        $this->assertSame('World', $decoded['body']);
    }

    public function test_save_content_serialize_fields_uses_empty_string_for_missing_field(): void
    {
        $row = $this->insertContentRow(page_id: 4, type_id: 1, content: '');

        $result = $this->content->saveContent([
            'content_id'       => $row->id(),
            'page_id'          => 4,
            'content_type_id'  => 1,
            'content'          => '',
            'live'             => '0',
            'serialize_fields' => 'title,missing_field',
            'title'            => 'Present',
            // missing_field intentionally absent
        ]);

        $this->assertNotFalse($result);
        $decoded = json_decode($result->content, true);
        $this->assertSame('', $decoded['missing_field']);
    }

    public function test_save_content_defaults_p13n_id_to_zero_when_absent(): void
    {
        $row = $this->insertContentRow(page_id: 5, type_id: 1, content: 'original');

        $result = $this->content->saveContent([
            'content_id'      => $row->id(),
            'page_id'         => 5,
            'content_type_id' => 1,
            'content'         => 'updated',
            'live'            => '0',
            // p13n_id intentionally absent
        ]);

        $this->assertNotFalse($result);
        $this->assertSame(0, (int) $result->p13n_id);
    }

    public function test_save_content_marks_content_live_and_records_publish_date(): void
    {
        $row = $this->insertContentRow(page_id: 6, type_id: 1, content: 'draft');

        $result = $this->content->saveContent([
            'content_id'      => $row->id(),
            'page_id'         => 6,
            'content_type_id' => 1,
            'content'         => 'published version',
            'live'            => '1',
        ]);

        $this->assertNotFalse($result);
        $this->assertSame(1, (int) $result->live);
        $this->assertNotNull($result->publish_date);
    }

    public function test_save_content_clears_previous_live_flag_when_publishing_new_revision(): void
    {
        // Simulate two revisions: the first is already live
        $this->insertContentRow(page_id: 7, type_id: 1, content: 'old live', live: 1);
        $second = $this->insertContentRow(page_id: 7, type_id: 1, content: 'new draft', live: 0);

        $this->content->saveContent([
            'content_id'      => $second->id(),
            'page_id'         => 7,
            'content_type_id' => 1,
            'content'         => 'new published',
            'live'            => '1',
        ]);

        // Old live row must no longer be live
        $liveCount = \ORM::for_table(_table_content)
            ->where('page_id', 7)
            ->where('live', 1)
            ->count();
        $this->assertSame(1, $liveCount, 'Only one revision should be live at a time');
    }

    // =========================================================================
    // saveContent — template_block_id slot isolation
    // =========================================================================

    public function test_save_content_stores_template_block_id_on_new_row(): void
    {
        $row = $this->insertContentRow(page_id: 30, type_id: 1, content: 'original', template_block_id: 5);

        $result = $this->content->saveContent([
            'content_id'        => $row->id(),
            'page_id'           => 30,
            'content_type_id'   => 1,
            'template_block_id' => '5',
            'content'           => 'updated slot content',
            'live'              => '0',
        ]);

        $this->assertNotFalse($result);
        $this->assertSame(5, (int) $result->template_block_id);
    }

    public function test_save_content_two_slots_same_type_are_independent(): void
    {
        // Slot 1 is live; slot 2 is also live — publishing a new revision in slot 1
        // must not clear the live flag in slot 2.
        $slotA_live = $this->insertContentRow(page_id: 31, type_id: 1, content: 'slot A live', live: 1, template_block_id: 1);
        $slotA_draft = $this->insertContentRow(page_id: 31, type_id: 1, content: 'slot A draft', live: 0, template_block_id: 1);
        $this->insertContentRow(page_id: 31, type_id: 1, content: 'slot B live', live: 1, template_block_id: 2);

        $this->content->saveContent([
            'content_id'        => $slotA_draft->id(),
            'page_id'           => 31,
            'content_type_id'   => 1,
            'template_block_id' => '1',
            'content'           => 'slot A new published',
            'live'              => '1',
        ]);

        // Slot B's live row should be untouched
        $slotBLiveCount = \ORM::for_table(_table_content)
            ->where('page_id', 31)
            ->where('template_block_id', 2)
            ->where('live', 1)
            ->count();
        $this->assertSame(1, $slotBLiveCount, 'Publishing slot A must not affect slot B live flag');

        // Slot A should also have exactly one live row (the new one)
        $slotALiveCount = \ORM::for_table(_table_content)
            ->where('page_id', 31)
            ->where('template_block_id', 1)
            ->where('live', 1)
            ->count();
        $this->assertSame(1, $slotALiveCount, 'Only one revision in slot A should be live');
    }

    // =========================================================================
    // listRevisions
    // =========================================================================

    public function test_list_revisions_returns_empty_for_page_with_no_content(): void
    {
        $result = $this->content->listRevisions(page_id: 999, content_type: 1);

        $this->assertEmpty($result);
    }

    public function test_list_revisions_returns_all_revisions_for_page(): void
    {
        $this->insertContentRow(page_id: 10, type_id: 1, content: 'rev 1', revision_date: '2024-01-01 00:00:00');
        $this->insertContentRow(page_id: 10, type_id: 1, content: 'rev 2', revision_date: '2024-01-02 00:00:00');

        $result = $this->content->listRevisions(page_id: 10, content_type: 1);

        $this->assertCount(2, $result);
    }

    public function test_list_revisions_respects_max_limit(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->insertContentRow(page_id: 11, type_id: 1, content: "rev $i");
        }

        $result = $this->content->listRevisions(page_id: 11, content_type: 1, max: 3);

        $this->assertCount(3, $result);
    }

    public function test_list_revisions_does_not_return_other_pages_content(): void
    {
        $this->insertContentRow(page_id: 12, type_id: 1, content: 'page 12 content');
        $this->insertContentRow(page_id: 13, type_id: 1, content: 'page 13 content');

        $result = $this->content->listRevisions(page_id: 12, content_type: 1);

        $this->assertCount(1, $result);
    }

    public function test_list_revisions_scoped_to_template_block_id(): void
    {
        // Two revisions for the same page and type but different template_block_ids
        $this->insertContentRow(page_id: 40, type_id: 1, content: 'slot 1 rev', template_block_id: 1);
        $this->insertContentRow(page_id: 40, type_id: 1, content: 'slot 2 rev', template_block_id: 2);

        $result = $this->content->listRevisions(page_id: 40, content_type: 1, template_block_id: 1);

        $this->assertCount(1, $result);
        $this->assertSame('slot 1 rev', $result[0]->content);
    }

    // =========================================================================
    // findContent
    // =========================================================================

    public function test_find_content_returns_empty_array_for_page_with_no_content(): void
    {
        $result = $this->content->findContent(999);

        $this->assertSame([], $result);
    }

    public function test_find_content_returns_empty_array_when_content_is_not_live(): void
    {
        $this->insertContentRow(page_id: 20, type_id: 1, content: 'draft', live: 0);

        $result = $this->content->findContent(20);

        $this->assertSame([], $result);
    }

    public function test_find_content_returns_live_content_keyed_by_slot_key(): void
    {
        \ORM::get_db()->exec("INSERT INTO humblee_template_blocks
            (id, template_id, content_type_id, label, slot_key, sort_order)
            VALUES (1, 1, 1, 'Main', 'main_content', 0)");

        \ORM::get_db()->exec("INSERT INTO humblee_content
            (type_id, page_id, p13n_id, template_block_id, content, live, revision_date, updated_by)
            VALUES (1, 21, 0, 1, 'live content', 1, '2024-01-01 00:00:00', 1)");

        $result = $this->content->findContent(21);

        $this->assertArrayHasKey('main_content', $result);
        $this->assertSame('live content', $result['main_content']->content);
    }

    public function test_find_content_does_not_return_other_pages_content(): void
    {
        \ORM::get_db()->exec("INSERT INTO humblee_template_blocks
            (id, template_id, content_type_id, label, slot_key, sort_order)
            VALUES (2, 1, 1, 'Main', 'main_content', 0)");

        \ORM::get_db()->exec("INSERT INTO humblee_content
            (type_id, page_id, p13n_id, template_block_id, content, live, revision_date, updated_by)
            VALUES (1, 22, 0, 2, 'page 22 live', 1, '2024-01-01 00:00:00', 1)");

        $result = $this->content->findContent(23); // different page

        $this->assertSame([], $result);
    }

    public function test_find_content_returns_slotted_content_keyed_by_slot_key(): void
    {
        // Insert a template block slot
        \ORM::get_db()->exec("INSERT INTO humblee_template_blocks
            (id, template_id, content_type_id, label, slot_key, sort_order)
            VALUES (10, 1, 1, 'Sidebar', 'main_content_2', 1)");

        // Insert live content referencing that slot
        \ORM::get_db()->exec("INSERT INTO humblee_content
            (type_id, page_id, p13n_id, template_block_id, content, live, revision_date, updated_by)
            VALUES (1, 50, 0, 10, 'sidebar content', 1, '2024-01-01 00:00:00', 1)");

        $result = $this->content->findContent(50);

        $this->assertArrayHasKey('main_content_2', $result);
        $this->assertSame('sidebar content', $result['main_content_2']->content);
    }

    // =========================================================================
    // Helper
    // =========================================================================

    private function insertContentRow(
        int    $page_id,
        int    $type_id,
        string $content           = '',
        int    $live              = 0,
        string $revision_date     = '2024-01-01 00:00:00',
        int    $template_block_id = 0
    ): \ORM {
        $row                    = \ORM::for_table(_table_content)->create();
        $row->type_id           = $type_id;
        $row->page_id           = $page_id;
        $row->p13n_id           = 0;
        $row->template_block_id = $template_block_id;
        $row->content           = $content;
        $row->live              = $live;
        $row->publish_date      = null;
        $row->revision_date     = $revision_date;
        $row->updated_by        = 1;
        $row->save();
        return $row;
    }
}
