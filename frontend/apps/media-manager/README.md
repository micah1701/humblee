# Media Manager Svelte Component

This is a complete conversion of the [Humblee CMS](https://github.com/micah1701/humblee)'s media manager front-end from PHP/jQuery to a modern Svelte + TypeScript component.

## Features Converted

All original features have been preserved:

- ✅ **Folder Tree Navigation** - Hierarchical folder structure with expand/collapse
- ✅ **File Listing** - Display files in selected folder with sorting
- ✅ **File Preview** - Image preview in sidebar
- ✅ **Inline Editing** - Click to edit folder/file names
- ✅ **File Upload** - Drag & drop or file picker with multiple file support
- ✅ **Access Control** - Role-based file access settings
- ✅ **Encryption Toggle** - Mark files as encrypted
- ✅ **File Management** - Delete files and folders with confirmation
- ✅ **Copy to Clipboard** - Copy file URLs
- ✅ **Permission System** - Show/hide controls based on `hasMediaRole`
- ✅ **Iframe Mode** - Support for embedding in iframe with file selection callback
- ✅ **Folder Cache** - Client-side caching to reduce API calls
- ✅ **Responsive Layout** - Bulma-based responsive columns
- ✅ **Upload Modal** - Modal dialog with drag-and-drop zone
- ✅ **TinyPNG Option** - Optional image compression checkbox

## Accessibility Features

The component includes comprehensive accessibility support:

- **Keyboard Navigation**
  - `Enter` key to save inline edits for folders and files
  - `Escape` key to close the upload modal
  - Full keyboard support for all interactive elements

- **ARIA Attributes**
  - Sortable table headers with `aria-sort` states (ascending/descending/none)
  - Descriptive `aria-label` attributes on all interactive buttons
  - Proper `role` attributes (dialog, columnheader, region, presentation)
  - `aria-modal` and `aria-labelledby` on modal dialogs
  - `aria-hidden` on decorative icons

- **Screen Reader Support**
  - Semantic HTML structure with proper heading hierarchy
  - Descriptive labels for all form controls
  - Status updates announced via visual feedback

- **Focus Management**
  - Clear visual focus indicators
  - Logical tab order throughout the interface
  - Autofocus on edit inputs for quick access (inline editing)

## Recent Changes

### v1.1.0
- Fixed select menu display issue where `required_role: 0` (Public Access) appeared blank
  - Changed hardcoded option value from string `"0"` to numeric `{0}` to match data type
- Added comprehensive accessibility features (ARIA labels, keyboard navigation, screen reader support)
- Improved modal dialog accessibility with proper ARIA attributes
- Enhanced table sorting with ARIA sort indicators

## File Structure

```
src/
├── lib/
│   ├── MediaManager.svelte      # Main component
│   ├── types/
│   │   └── media.ts             # TypeScript interfaces
│   ├── services/
│   │   └── mediaApi.ts          # API service layer
│   └── utils/
│       └── mediaUtils.ts        # Helper functions
└── App.svelte                    # Demo/usage example
```

## Usage

### Basic Usage

```svelte
<script lang="ts">
  import MediaManager from './lib/MediaManager.svelte';
  import type { AccessRole } from './lib/types/media';

  const hasMediaRole = true;
  const isInIframe = false;

  // REQUIRED: Base URL for your application (where media files are hosted)
  const WEB_ROOT = 'https://example.com';

  // REQUIRED: Base path for API endpoints (must end with /)
  const XHR_PATH = 'https://api.example.com/xhr/';

  const accessRoles: AccessRole[] = [
    { id: 1, name: 'user' },
    { id: 2, name: 'admin' }
  ];
</script>

<MediaManager
  {hasMediaRole}
  {isInIframe}
  {accessRoles}
  {XHR_PATH}
  {WEB_ROOT}
  appPath="/"
/>
```

### Configuration Constants

#### `WEB_ROOT` (required)
The base URL where your media files are hosted. Used for constructing file URLs for display and download.

**Example:**
```typescript
const WEB_ROOT = 'https://intranet.example.com';
```

Files will be accessible at: `${WEB_ROOT}${appPath}media/${fileId}/${filename}`

#### `XHR_PATH` (required)
The base path for all API endpoints. Must end with a trailing slash (`/`).

**Example:**
```typescript
const XHR_PATH = 'https://api.example.com/xhr/';
```

All API calls will be made to endpoints like:
- `${XHR_PATH}listMediaFolders`
- `${XHR_PATH}uploadMediaFiles`
- etc.

### Props

- `hasMediaRole` (boolean, default: `true`) - Whether user has edit permissions
- `isInIframe` (boolean, default: `false`) - Enable iframe mode with "Select File" button
- `accessRoles` (AccessRole[], required) - Array of access role objects for file access control
- `appPath` (string, default: `"/"`) - Application path segment for file URLs
- `XHR_PATH` (string, required) - Base path for API endpoints
- `WEB_ROOT` (string, required) - Base URL for media file hosting

## API Endpoints Required

Your backend should provide these endpoints (relative to `XHR_PATH`):

- `GET listMediaFolders` - Returns folder hierarchy
- `GET listMediaFilesByFolder?folder={id}` - Returns files in folder
- `POST createMediaFolder` - Creates new folder (params: `parent_id`)
- `POST deleteMediaFolder` - Deletes folder (params: `folder_id`)
- `POST deleteMediaFile` - Deletes file (params: `file_id`)
- `POST updateMediaName` - Updates folder/file name (params: `type`, `record`, `value`)
- `POST updateMediaRole` - Updates file access role (params: `file_id`, `required_role`)
- `POST encryptMedia` - Toggles file encryption (params: `file_id`, `action`)
- `POST uploadMediaFiles` - Handles file uploads (multipart/form-data)

**Note:** All endpoints are prefixed with the `XHR_PATH` constant. For example, if `XHR_PATH = 'https://api.example.com/xhr/'`, the full URL for listing folders would be `https://api.example.com/xhr/listMediaFolders`.

## Iframe Mode

When `isInIframe={true}`, the component shows a "Select this file" button. The parent window must implement:

```javascript
// In parent window
function handleMediaManagerSelect(fileData) {
  console.log('Selected file:', fileData);
  // Do something with the file data
}

function closeMediamanager() {
  // Close the iframe/modal
}

function unsetEscEvent(eventName) {
  // Clean up escape key handler
}
```

## Styling

The component uses:
- **Bulma CSS Framework** (included via CDN in `index.html`)
- **Font Awesome 6** (included via CDN in `index.html`)
- **Custom SCSS** (component-scoped styles)
- **Custom tooltip CSS** (in `app.css`)

## Development

```bash
# Install dependencies
npm install

# Start dev server
npm run dev

# Build for production
npm run build
```

## Author

Micah Murray [@micah1701](https://github.com/micah1701)

Proudly refactored in a single evening using [Bolt](https://bolt.new/?rid=w4jgxz).