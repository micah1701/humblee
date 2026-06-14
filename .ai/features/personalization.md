# Personalization (p13n)

## Concept

Personalization lets editors create content variants for different audience segments. The same page can display different content depending on which segment the visitor matches.

- `p13n_id = 0` — **default** content, shown when no segment matches
- `p13n_id > 0` — a named audience variant

## Data Model

**`humblee_personalization`** table (audience segments):

| Column | Description |
|---|---|
| `id` | PK — this is the `p13n_id` referenced in content rows |
| `label` | Human-readable segment name (e.g., "Returning Users") |
| `rules` | JSON — visitor matching rules |
| `sort_order` | Evaluation priority (lower = checked first) |

**`humblee_content`** has a `p13n_id` column FK'd to `humblee_personalization.id`.

## Matching Logic

On a public page request, `Template` controller:
1. Loads all active personalization segments ordered by `sort_order`
2. Evaluates visitor attributes (cookie, session, referrer, geo, etc.) against each segment's `rules`
3. Uses the `p13n_id` of the first matching segment (or `0` if none match)
4. Calls `Content::findContent($page_id, $p13n_id)` with the resolved ID

## Content API

```php
// Default content
$content = Content::findContent($page_id, 0);

// Personalized variant
$active_p13n_id = Personalization::resolveForVisitor();
$content = Content::findContent($page_id, $active_p13n_id);
```

Content rows are **per-(page, slot, p13n_id)**. A variant that doesn't have a row for a given slot falls through to the default (`p13n_id = 0`) automatically — check `Content::findContent()` fallback logic.

## Personalization Model

`Humblee\Model\Personalization`:

```php
Personalization::getAll(): array                     // All segments
Personalization::getById(int $id): object|false      // One segment
Personalization::save(array $post): bool             // Create or update
Personalization::delete(int $id): bool               // Remove segment
Personalization::resolveForVisitor(): int            // → p13n_id for current visitor
```

## Admin UI

The `personalization` Svelte app (`frontend/apps/personalization/`) manages audience segments. It's mounted at `admin/personalization` and communicates via `core-request/personalization/*`.

## Editing Variants in the Page Editor

The page editor (`frontend/apps/page-editor/`) shows a variant selector. When the editor switches to a variant, it passes the `p13n_id` to content save/load calls.

```ts
// In page-editor contentApi.ts
const data = new FormData()
data.append('page_id', pageId)
data.append('p13n_id', activePersId)  // 0 = default
data.append('content', value)
// ... + hmac fields
```

## Creating a New Variant

1. Create a segment in the personalization manager (sets rules, label, sort_order)
2. Open the page editor — the new segment appears in the variant dropdown
3. Edit content for that variant — saves a new `humblee_content` row with the segment's `p13n_id`
4. Default content remains untouched at `p13n_id = 0`
