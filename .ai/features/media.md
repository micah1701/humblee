# Media Management

## Storage

Files are stored in `storage/` (not web-accessible). The directory must be writable by the web server. Media metadata is stored in `humblee_media` (constant: `_table_media`).

```
storage/
└── [year]/
    └── [month]/
        └── [filename]   ← original or encrypted file
```

## humblee_media Table

| Column | Description |
|---|---|
| `id` | PK |
| `filename` | Name of file on disk |
| `path` | Relative path within `storage/` |
| `mime_type` | MIME type |
| `encrypted` | `0` = plaintext, `1` = encrypted on disk |
| `folder_id` | FK → media folder (0 = root) |
| `created_by` | FK → user ID |
| `file_size` | Bytes |

## Encryption at Rest

Files are optionally encrypted using `Crypto::encrypt()` (XSalsa20-Poly1305). The file on disk is **replaced in-place** when encrypting or decrypting. No separate nonce column — the nonce is prepended to the ciphertext within the file.

```php
// Encrypt a file
$plaintext = file_get_contents($file_path);
$encrypted = Crypto::encrypt($plaintext);
file_put_contents($file_path, $encrypted);
// Update DB: $record->encrypted = 1; $record->save();

// Decrypt for serving
$raw = file_get_contents($file_path);
$plaintext = Crypto::decrypt($raw);
// Stream $plaintext to browser
```

The `encrypted` flag on the `humblee_media` row tells the `Media` controller whether to decrypt before serving.

## Upload Flow

1. `POST core-request/media_files/upload` → `Requests\MediaFiles::upload()`
2. Validate file type and size
3. Generate unique filename, move to `storage/[year]/[month]/`
4. Optionally compress with TinyPNG (`Tools::compressImage()`)
5. Insert row in `humblee_media`
6. Return JSON with the new media record

## Access Control

Serving a media file:
- `GET /media/{id}` → `Humblee\Controller\Media`
- Controller checks `Core::auth('login')` for private media, or serves publicly for public files
- If `$record->encrypted === 1`: decrypts before streaming
- Sets `Content-Type` from `$record->mime_type`, no caching headers for private files

## Media Model

`Humblee\Model\Media` provides:

```php
Media::getAll(int $folder_id): array       // List files in a folder
Media::getById(int $id): object|false      // Single file metadata
Media::delete(int $id): bool               // Delete file + DB row
Media::move(int $id, int $folder_id): bool // Move to different folder
Media::encrypt(int $id): bool              // Encrypt file in place
Media::decrypt(int $id): bool              // Decrypt file in place
```

## TinyPNG Integration

Enabled when `$_ENV['config']['TINIFY_KEY']` is set. Applied automatically on upload for supported image types (JPEG, PNG). The `Tools::compressImage()` method handles this; it's a no-op when the key is absent.
