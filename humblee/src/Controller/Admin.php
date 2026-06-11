<?php

declare(strict_types=1);

namespace Humblee\Controller;

use Humblee\Foundation\Core;
use Humblee\Model\Users;
use Humblee\Model\Tools;
use Humblee\Model\Content;
use Humblee\Model\Pages;
use Humblee\Model\Personalization;

class Admin
{

    private array $_uri_parts;
    private Tools $tools;

    // View data — set in action methods and passed to templates via get_object_vars($this)
    public object|false $user            = false;
    public array        $recent_contents = [];
    public array        $contentTypes    = [];
    public array        $p13nVersions    = [];
    public string       $extra_head_code = '';
    public string|false $template_view   = false;
    public array        $access_roles    = [];
    public array        $roles           = [];
    public array        $hidden_users    = [];
    public array        $users           = [];
    public object|false $content         = false;
    public array        $revisions       = [];
    public object|false $content_type    = false;
    public object|false $page_data       = false;
    public object|false $template_data   = false;
    public bool         $is_in_iframe    = false;
    public bool         $isDeveloper     = false;
    public array        $allP13nVersions = [];
    public bool         $hasMediaRole    = false;
    public string       $userTheme       = 'light';
    // Set by Tools::CRUD() via $thisObj
    public array        $errors          = [];
    public array        $crud_all        = [];
    public object|false $crud_selected   = false;

    public function __construct()
    {
        if (!Core::auth(['admin', 'developer'])) {
            Core::forward("/user/login/?fwd=" . Core::getURI());
        }

        $this->_uri_parts = Core::getURIparts();
        $this->tools = new Tools;

        // Load user's theme preference
        if (isset($_SESSION[session_key]['user_id'])) {
            $users = new Users();
            $this->userTheme = $users->getThemePreference($_SESSION[session_key]['user_id']);
        }
    }

    private function getUser(): object|false
    {
        $userObj = new Users;
        return $userObj->profile();
    }

    private function require_role(int|string|array $role): void
    {
        if (!Core::auth($role) && !Core::auth('developer')) {
            header('HTTP/1.1 403 Forbidden');
            $this->template_view = "<h1>403 Forbidden</h1><h3>You do not have access to view this page</h3><p>If you believe this is an error, please see your site administrator.</p>";
            echo Core::view(_app_server_path . 'humblee/views/admin/templates/template.php', get_object_vars($this));
            exit();
        }
    }

    public function index(): void
    {
        $this->user = $this->getUser();

        $_zero_date = (($_ENV['config']['RDBMS'] ?? 'mysql') === 'pgsql')
            ? '1970-01-01 00:00:00'
            : '0000-00-00 00:00:00';

        $recentContentRows = \ORM::for_table(_table_content)
            ->raw_query("SELECT * FROM " . _table_content . " AS topTable
                         WHERE revision_date != '" . $_zero_date . "'
                         AND content != ''
                         AND revision_date = (SELECT revision_date FROM " . _table_content . "
                                             WHERE page_id = topTable.page_id
                                             AND type_id = topTable.type_id
                                             ORDER BY revision_date DESC LIMIT 1)
                         ORDER BY revision_date DESC LIMIT 10")
            ->find_many();

        $contentTypesMap = [];
        foreach (\ORM::for_table(_table_content_types)->find_many() as $ct) {
            $contentTypesMap[(int)$ct->id] = $ct->name;
        }

        $p13nVersionsMap = [];
        if ($_ENV['config']['use_p13n']) {
            foreach (\ORM::for_table(_table_content_p13n)->find_many() as $p) {
                $p13nVersionsMap[(int)$p->id] = $p->name;
            }
        }

        $recentContents = [];
        foreach ($recentContentRows as $row) {
            $recentPage = \ORM::for_table(_table_pages)->find_one($row->page_id);
            $recentContents[] = [
                'id'           => (int)$row->id,
                'pageId'       => (int)$row->page_id,
                'pageLabel'    => $recentPage ? $recentPage->label : 'Unknown',
                'typeName'     => $contentTypesMap[(int)$row->type_id] ?? 'Unknown',
                'p13nName'     => ($_ENV['config']['use_p13n'] && (int)$row->p13n_id !== 0)
                                    ? ($p13nVersionsMap[(int)$row->p13n_id] ?? null)
                                    : null,
                'live'         => (bool)$row->live,
                'publishDate'  => $row->publish_date,
                'revisionDate' => $row->revision_date,
            ];
        }

        $adminHomeConfig = [
            'xhrPath'        => _app_path . 'core-request/',
            'appPath'        => _app_path,
            'userTheme'      => $this->userTheme,
            'useP13n'        => (bool)$_ENV['config']['use_p13n'],
            'recentContents' => $recentContents,
        ];

        $this->extra_head_code  = '<link rel="stylesheet" href="' . _app_path . 'humblee/js/admin/admin-home/index.css">';
        $this->extra_head_code .= '<script>window.__ADMIN_HOME_CONFIG__ = ' . json_encode($adminHomeConfig, JSON_HEX_TAG | JSON_HEX_APOS) . ';</script>';
        $this->extra_head_code .= '<script type="module" src="' . _app_path . 'humblee/js/admin/admin-home/index.js"></script>';

        $this->template_view = Core::view(_app_server_path . 'humblee/views/admin/index.php', get_object_vars($this));
        echo Core::view(_app_server_path . 'humblee/views/admin/templates/template.php', get_object_vars($this));
    }

    public function pages(): void
    {
        $this->require_role('pages');

        $templateRows = \ORM::for_table(_table_templates)->order_by_asc('name')->find_many();
        $templatesArray = [];
        foreach ($templateRows as $t) {
            $templatesArray[] = ['id' => (int)$t->id, 'name' => $t->name, 'available' => (bool)$t->available];
        }

        $roleRows = \ORM::for_table(_table_roles)->where('role_type', 'access')->find_many();
        $rolesArray = [];
        foreach ($roleRows as $r) {
            $rolesArray[] = ['id' => (int)$r->id, 'name' => $r->name];
        }

        $pagesConfig = [
            'xhrPath'              => _app_path . 'core-request/',
            'templates'            => $templatesArray,
            'roles'                => $rolesArray,
            'isDeveloperOrDesigner' => Core::auth('developer') || Core::auth('designer'),
        ];

        $this->extra_head_code  = '<link rel="stylesheet" href="' . _app_path . 'humblee/js/admin/page-manager/index.css">';
        $this->extra_head_code .= '<script>window.__PAGES_CONFIG__ = ' . json_encode($pagesConfig, JSON_HEX_TAG | JSON_HEX_APOS) . ';</script>';
        $this->extra_head_code .= '<script type="module" src="' . _app_path . 'humblee/js/admin/page-manager/index.js"></script>';

        $this->template_view = Core::view(_app_server_path . 'humblee/views/admin/pages.php', get_object_vars($this));
        echo Core::view(_app_server_path . 'humblee/views/admin/templates/template.php', get_object_vars($this));
    }

    public function edit(): void
    {
        $this->require_role(['content', 'publish']);

        if (isset($_POST['content']) || isset($_POST['serialize_fields'])) {
            $content = new Content();
            $new_content = $content->saveContent($_POST);
            if ($new_content !== false) {
                Core::forward('admin/edit/' . $new_content->id);
            }
        }

        if (!is_numeric($this->_uri_parts[2] ?? null) && isset($_GET['page_id']) && is_numeric($_GET['page_id'])) {
            $p13n_id           = (isset($_GET['p13n_id']) && is_numeric($_GET['p13n_id'])) ? (int)$_GET['p13n_id'] : 0;
            $template_block_id = (isset($_GET['template_block_id']) && is_numeric($_GET['template_block_id'])) ? (int)$_GET['template_block_id'] : 0;

            if ($template_block_id > 0) {
                $tb = \ORM::for_table(_table_template_blocks)->find_one($template_block_id);
                if (!$tb) {
                    exit('<h1>ERROR: template block not found</h1>');
                }
                $content_type = (int)$tb->content_type_id;
            } else {
                $content_type = (isset($_GET['content_type']) && is_numeric($_GET['content_type'])) ? (int)$_GET['content_type'] : 1;
            }

            $content = \ORM::for_table(_table_content)
                ->where('page_id', $_GET['page_id'])
                ->where('type_id', $content_type)
                ->where('p13n_id', $p13n_id)
                ->where('template_block_id', $template_block_id)
                ->order_by_desc('revision_date')
                ->find_one();
            if (!$content) {
                $content = \ORM::for_table(_table_content)->create();
                $content->page_id           = $_GET['page_id'];
                $content->type_id           = $content_type;
                $content->p13n_id           = $p13n_id;
                $content->template_block_id = $template_block_id;
                $content->content           = '';
                $content->revision_date     = gmdate("Y-m-d H:i:s");
                $content->live              = 1;
                $content->updated_by        = $_SESSION[session_key]['user_id'];
                $content->save();
            }
            $frameStatus = isset($_GET['iframe']) ? "?iframe" : "";
            Core::forward('admin/edit/' . $content->id . $frameStatus);
        }

        if (!is_numeric($this->_uri_parts[2] ?? null)) {
            exit('<h1>Fatal error. invalid page request</h1>');
        }

        $this->content = \ORM::for_table(_table_content)->find_one($this->_uri_parts[2]);
        if (!$this->content) {
            exit("<h1>ERROR: content not found</h1>");
        }

        $pageObj = new Pages;
        $contentObj = new Content;

        $currentTemplateBlockId = (int)($this->content->template_block_id ?? 0);
        $this->revisions = $contentObj->listRevisions($this->content->page_id, $this->content->type_id, $this->content->p13n_id, 10, $currentTemplateBlockId);
        $this->content_type = \ORM::for_table(_table_content_types)->find_one($this->content->type_id);
        if (!$this->content_type) {
            exit("<h1>ERROR: content type not found</h1>");
        }
        $this->page_data = \ORM::for_table(_table_pages)->find_one($this->content->page_id);
        if (!$this->page_data) {
            exit("<h1>ERROR: page not found</h1>");
        }
        $this->page_data->url = $pageObj->buildLink((int)$this->content->page_id);
        $this->template_data = \ORM::for_table(_table_templates)->find_one($this->page_data->template_id);
        if (!$this->template_data) {
            exit("<h1>ERROR: template not found</h1>");
        }

        $templateBlockRows = \ORM::for_table(_table_template_blocks)
            ->where('template_id', $this->page_data->template_id)
            ->order_by_asc('sort_order')
            ->find_many();

        $this->is_in_iframe = isset($_GET['iframe']);

        if ($_ENV['config']['use_p13n']) {
            $p13nObj = new Personalization;
            $this->allP13nVersions = $p13nObj->getAll();  // returns array of ORMS with p13n id as key
            $this->allP13nVersions[0] = (object)['id' => 0, 'name' => 'Default (No Personalization)']; //overload a default option into the array for ease of use in the template
        }

        // Gather updated_by user name for display
        $updatedByUser = false;
        if ($this->content->updated_by != 0) {
            $updatedByUser = \ORM::for_table(_table_users)->find_one($this->content->updated_by);
        }

        $allSlotsArray = [];
        foreach ($templateBlockRows as $tb) {
            $ct = \ORM::for_table(_table_content_types)->find_one($tb->content_type_id);
            $allSlotsArray[] = [
                'templateBlockId' => (int)$tb->id,
                'slotKey'         => $tb->slot_key,
                'label'           => $tb->label,
                'contentTypeId'   => (int)$tb->content_type_id,
                'contentTypeName' => $ct ? $ct->name : '',
            ];
        }

        $allP13nVersionsArray = [];
        if ($_ENV['config']['use_p13n']) {
            foreach ($this->allP13nVersions as $p) {
                $allP13nVersionsArray[] = [
                    'id'          => (int)$p->id,
                    'name'        => $p->name,
                    'description' => $p->description ?? '',
                ];
            }
        }

        $revisionsArray = [];
        foreach ($this->revisions as $rev) {
            $revisionsArray[] = [
                'id'           => (int)$rev->id,
                'revisionDate' => $rev->revision_date,
                'publishDate'  => $rev->publish_date,
                'live'         => (bool)$rev->live,
            ];
        }

        // Detect feed widget and generate a fresh HMAC pair for its REST API
        $feedHmac = null;
        $isFeedWidget = $this->content_type->input_type === 'customform'
            && str_contains((string)$this->content_type->input_parameters, 'contentWidgets/feed/edit.php');
        if ($isFeedWidget) {
            $crypto   = new \Humblee\Model\Crypto();
            $hmacPair = $crypto->get_hmac_pair();
            $feedHmac = ['token' => $hmacPair['message'], 'key' => $hmacPair['hmac']];
        }

        $editorConfig = [
            'xhrPath'         => _app_path . 'core-request/',
            'appPath'         => _app_path,
            'isInIframe'      => $this->is_in_iframe,
            'userTheme'       => $this->userTheme,
            'useP13n'         => (bool)$_ENV['config']['use_p13n'],
            'domain'          => $_ENV['config']['domain'] ?? $_SERVER['HTTP_HOST'],
            'content'         => [
                'id'              => (int)$this->content->id,
                'pageId'          => (int)$this->content->page_id,
                'typeId'          => (int)$this->content->type_id,
                'p13nId'          => (int)$this->content->p13n_id,
                'templateBlockId' => $currentTemplateBlockId,
                'content'         => $this->content->content,
                'revisionDate'    => $this->content->revision_date,
                'publishDate'     => $this->content->publish_date,
                'live'            => (bool)$this->content->live,
                'updatedBy'       => (int)$this->content->updated_by,
                'updatedByName'   => $updatedByUser ? ($updatedByUser->name ?? 'Unknown') : '',
            ],
            'contentType'     => [
                'id'              => (int)$this->content_type->id,
                'name'            => $this->content_type->name,
                'description'     => $this->content_type->description ?? '',
                'inputType'       => $this->content_type->input_type,
                'inputParameters' => $this->content_type->input_parameters,
            ],
            'pageData'        => [
                'id'     => (int)$this->page_data->id,
                'label'  => $this->page_data->label,
                'active' => (bool)$this->page_data->active,
                'url'    => $this->page_data->url,
            ],
            'revisions'              => $revisionsArray,
            'allSlots'               => $allSlotsArray,
            'currentTemplateBlockId' => $currentTemplateBlockId,
            'allP13nVersions'        => $allP13nVersionsArray,
            'feedHmac'               => $feedHmac,
        ];

        $this->template_view = Core::view(_app_server_path . 'humblee/views/admin/edit.php', get_object_vars($this));

        $this->extra_head_code  = '<link rel="stylesheet" href="' . _app_path . 'humblee/js/admin/page-editor/index.css">';
        $this->extra_head_code .= '<script>window.__PAGE_EDITOR_CONFIG__ = ' . json_encode($editorConfig, JSON_HEX_TAG | JSON_HEX_APOS) . ';</script>';
        $this->extra_head_code .= '<script type="module" src="' . _app_path . 'humblee/js/admin/page-editor/index.js"></script>';

        // Summernote WYSIWYG — load only when needed
        if ($this->content_type->input_type === 'wysiwyg') {
            $this->extra_head_code .= '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css">';
            $this->extra_head_code .= '<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>';
            $this->extra_head_code .= '<script type="module" src="' . _app_path . 'humblee/js/tools/summernote.js"></script>';
        }

        // Showdown markdown — load only for the feed widget
        if ($isFeedWidget) {
            $this->extra_head_code .= '<script src="https://cdn.jsdelivr.net/npm/showdown@2.1.0/dist/showdown.min.js"></script>';
        }

        $outter_template = $this->is_in_iframe ? 'blank.php' : 'template.php';
        echo Core::view(_app_server_path . 'humblee/views/admin/templates/' . $outter_template, get_object_vars($this));
    }

    public function media(): void
    {
        $this->require_role(['content', 'media']);
        $this->hasMediaRole = Core::auth(['media', 'developer']);

        $this->access_roles = \ORM::for_table(_table_roles)->where('role_type', 'access')->find_many();
        $this->is_in_iframe = isset($_GET['iframe']);

        $this->template_view = Core::view(_app_server_path . 'humblee/views/admin/media.php', get_object_vars($this));

        $this->extra_head_code = '<link rel="stylesheet" href="' . _app_path . 'humblee/js/admin/media-manager/index.css">';
        $this->extra_head_code .= '<script type="module" src="' . _app_path . 'humblee/js/admin/media-manager/index.js"></script>';

        $outter_template = $this->is_in_iframe ? 'blank.php' : 'template.php';
        echo Core::view(_app_server_path . 'humblee/views/admin/templates/' . $outter_template, get_object_vars($this));
    }

    public function users(): void
    {
        $this->require_role('users');

        $available_roles = \ORM::for_table(_table_roles)->find_many();
        $this->roles = [];
        foreach ($available_roles as $role) {
            $this->roles[$role->id] = $role->name;
        }

        $this->isDeveloper = Core::auth('developer');

        $this->extra_head_code  = '<link rel="stylesheet" href="' . _app_path . 'humblee/js/admin/user-manager/index.css">';
        $this->extra_head_code .= '<script type="module" src="' . _app_path . 'humblee/js/admin/user-manager/index.js"></script>';
        $this->template_view = Core::view(_app_server_path . 'humblee/views/admin/users.php', get_object_vars($this));
        echo Core::view(_app_server_path . 'humblee/views/admin/templates/template.php', get_object_vars($this));
    }

    public function blocks(): void
    {
        $this->require_role('designer');
        $this->template_view = Core::view(_app_server_path . 'humblee/views/admin/blocks.php', get_object_vars($this));
        $this->extra_head_code  = '<link rel="stylesheet" href="' . _app_path . 'humblee/js/admin/blocks/index.css">';
        $this->extra_head_code .= '<script type="module" src="' . _app_path . 'humblee/js/admin/blocks/index.js"></script>';
        echo Core::view(_app_server_path . 'humblee/views/admin/templates/template.php', get_object_vars($this));
    }

    public function templates(): void
    {
        $this->require_role('designer');
        $this->template_view = Core::view(_app_server_path . 'humblee/views/admin/templates.php', get_object_vars($this));
        $this->extra_head_code  = '<link rel="stylesheet" href="' . _app_path . 'humblee/js/admin/templates/index.css">';
        $this->extra_head_code .= '<script type="module" src="' . _app_path . 'humblee/js/admin/templates/index.js"></script>';
        echo Core::view(_app_server_path . 'humblee/views/admin/templates/template.php', get_object_vars($this));
    }

    public function personalization(): void
    {
        $this->require_role('designer');

        $this->template_view = Core::view(_app_server_path . 'humblee/views/admin/personalization.php', get_object_vars($this));
        $this->extra_head_code  = '<link rel="stylesheet" href="' . _app_path . 'humblee/js/admin/personalization/index.css">';
        $this->extra_head_code .= '<script type="module" src="' . _app_path . 'humblee/js/admin/personalization/index.js"></script>';
        echo Core::view(_app_server_path . 'humblee/views/admin/templates/template.php', get_object_vars($this));
    }
}
