# Content Blocks & Template Slots

## Data Model

Content is uniquely identified by three keys:

| Key | Type | Meaning |
|---|---|---|
| `page_id` | int | Which page |
| `template_block_id` | int (0 = legacy) | Which slot (0 = no slot, keyed by content type) |
| `p13n_id` | int (0 = default) | Which personalization variant |

## Template Blocks Table (`humblee_template_blocks`)

Maps named slots to a template + content-type pair.

| Column | Description |
|---|---|
| `template_id` | FK → `humblee_templates.id` |
| `content_type_id` | FK → `humblee_content_types.id` |
| `label` | Human-readable name shown in page editor |
| `slot_key` | Immutable after creation — derived from content type's `objectkey` |
| `sort_order` | Display order in the editor |

**`slot_key` generation rules:**
- First slot of a content type: uses the type's `objectkey` unchanged (e.g., `rich_text`)
- Subsequent slots: append counter (e.g., `rich_text_2`, `rich_text_3`)
- Once set, `slot_key` never changes — only `label` and `sort_order` can be updated

## `humblee_content` Table Key Columns

| Column | Values | Meaning |
|---|---|---|
| `template_block_id` | `0` | Legacy — `findContent()` keys by content type's `objectkey` |
| `template_block_id` | `> 0` | Slotted — `findContent()` keys by `slot_key` |

## Content::findContent()

```php
$content = Content::findContent($page_id, $p13n_id);

// Access by slot key:
$content['rich_text']->content    // First rich text slot (or legacy)
$content['rich_text_2']->content  // Second rich text slot

// Each value is an ORM object with at minimum:
// ->content      (the stored value — may be JSON for multi-field blocks)
// ->p13n_id
// ->template_block_id
```

## Content Serialization (Multi-field Blocks)

Some content types store multiple fields in a single `content` column as JSON:

```php
// Saving: backend serializes marked fields
$post['serialize_fields'] = 'title,body,link_url';
// Backend JSON-encodes those fields into $content_array

// Reading: JSON-decode and extract
$fields = json_decode($content_row->content, true);
$title = $fields['title'] ?? '';
$body  = $fields['body']  ?? '';
```

Check the content type's definition to know whether it uses serialized fields.

## Slot Lifecycle

**Create**: When a template is saved, `Requests\Templates` upserts slots:
- Existing slots with matching `template_block_id` are updated (label, sort_order only)
- New slots are inserted; `slot_key` is auto-generated and set once

**Delete**: Removing a slot from the posted list deletes the `template_blocks` row and **orphans** any `humblee_content` rows referencing it (content data is preserved but inaccessible).

**Immutability**: `slot_key` is set at creation and must never be changed — content rows reference it as a logical key.

## Draw Helper (Frontend Rendering)

```php
// In a public-facing PHP view template:
$content = Content::findContent($page_id, $p13n_id);

Draw::content($content, 'rich_text');       // Renders first slot
Draw::content($content, 'rich_text_2');     // Renders second slot
Draw::content($content, ['hero', 'intro']); // Renders multiple
```

`Draw::content()` wraps output in `<div class="cms_block" data-key="...">` when the session user has the `content` role, enabling the admin toolbar's inline edit UI.
