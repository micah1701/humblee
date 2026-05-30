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
    public array        $allContentTypes = [];
    public bool         $is_in_iframe    = false;
    public array        $allP13nVersions = [];
    public bool         $hasMediaRole    = false;
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
        $this->recent_contents = \ORM::for_table(_table_content)
            ->raw_query("SELECT *
                                                    FROM " . _table_content . " AS topTable
                                                    WHERE revision_date != '" . $_zero_date . "'
                                                    AND content != ''
                                                    AND revision_date = (SELECT revision_date
                                                                        FROM " . _table_content . "
                                                                        WHERE page_id = topTable.page_id
                                                                        AND type_id = topTable.type_id
                                                                        ORDER BY revision_date DESC
                                                                        LIMIT 1)
                                                    ORDER BY revision_date DESC
                                                    LIMIT 10")
            ->find_many();
        $getcontentTypes = \ORM::for_table(_table_content_types)->find_many();
        foreach ($getcontentTypes as $getType) {
            $this->contentTypes[$getType->id] = $getType->name;
        }

        if ($_ENV['config']['use_p13n']) {
            $getP13nVersions = \ORM::for_table(_table_content_p13n)->find_many();
            foreach ($getP13nVersions as $p13nVersion) {
                $this->p13nVersions[$p13nVersion->id] = $p13nVersion->name;
            }
        }

        $this->extra_head_code = '<script type="text/javascript" src="' . _app_path . 'humblee/js/admin/index.js"></script>';

        $this->template_view = Core::view(_app_server_path . 'humblee/views/admin/index.php', get_object_vars($this));
        echo Core::view(_app_server_path . 'humblee/views/admin/templates/template.php', get_object_vars($this));
    }

    public function pages(): void
    {
        $this->require_role('pages');

        $this->extra_head_code = '<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js" integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=" crossorigin="anonymous"></script>';
        $this->extra_head_code .= '<link rel="stylesheet" href="https://code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.css">';
        $this->extra_head_code .= '<script src="' . _app_path . 'node_modules/nestedSortable/jquery.mjs.nestedSortable.js"></script>';
        $this->extra_head_code .= '<link rel="stylesheet" type="text/css" href="' . _app_path . 'humblee/css/admin/pages.css">';
        $this->extra_head_code .= '<script type="text/javascript" src="' . _app_path . 'humblee/js/admin/pages.js"></script>';

        $this->access_roles = \ORM::for_table(_table_roles)->where('role_type', 'access')->find_many();
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
            $content_type = (isset($_GET['content_type']) && is_numeric($_GET['content_type'])) ? $_GET['content_type'] : 1;
            $p13n_id = (isset($_GET['p13n_id']) && is_numeric($_GET['p13n_id'])) ? $_GET['p13n_id'] : 0;
            $content = \ORM::for_table(_table_content)
                ->where('page_id', $_GET['page_id'])
                ->where('type_id', $content_type)
                ->where('p13n_id', $p13n_id)
                ->order_by_desc('revision_date')
                ->find_one();
            if (!$content) {
                $content = \ORM::for_table(_table_content)->create();
                $content->page_id = $_GET['page_id'];
                $content->type_id = $content_type;
                $content->p13n_id = $p13n_id;
                $content->content = '';
                $content->revision_date = date("Y-m-d H:i:s");
                $content->updated_by = $_SESSION[session_key]['user_id'];
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

        $this->revisions = $contentObj->listRevisions($this->content->page_id, $this->content->type_id, $this->content->p13n_id);
        $this->content_type = \ORM::for_table(_table_content_types)->find_one($this->content->type_id);
        $this->page_data = \ORM::for_table(_table_pages)->find_one($this->content->page_id);
        if (!$this->page_data) {
            exit("<h1>ERROR: page not found</h1>");
        }
        $this->page_data->url = $pageObj->buildLink((int)$this->content->page_id);
        $this->template_data = \ORM::for_table(_table_templates)->find_one($this->page_data->template_id);
        if (!$this->template_data) {
            exit("<h1>ERROR: template not found</h1>");
        }

        $this->allContentTypes = \ORM::for_table(_table_content_types)->where_in('id', explode(',', $this->template_data->blocks))->order_by_asc('name')->find_many();
        $this->is_in_iframe = isset($_GET['iframe']);

        if ($_ENV['config']['use_p13n']) {
            $p13nObj = new Personalization;
            $this->allP13nVersions = $p13nObj->getAll();
            array_unshift($this->allP13nVersions, (object)['id' => 0, 'name' => 'Default (No Personalization)']);
        }

        $this->template_view = Core::view(_app_server_path . 'humblee/views/admin/edit.php', get_object_vars($this));

        $this->extra_head_code = '<script type="text/javascript" src="' . _app_path . 'humblee/js/tools/dateformat.js"></script>';
        $this->extra_head_code .= '<script type="text/javascript" src="' . _app_path . 'humblee/js/admin/edit.js"></script>';
        $this->extra_head_code .= '<link rel="stylesheet" type="text/css" href="' . _app_path . 'humblee/css/admin/edit.css">';

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

        $this->extra_head_code = '<script type="text/javascript" src="' . _app_path . 'humblee/js/tools/dateformat.js"></script>';
        $this->extra_head_code .= '<script type="text/javascript" src="' . _app_path . 'humblee/js/tools/friendlyfilesize.js"></script>';
        $this->extra_head_code .= '<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/1.5.13/clipboard.min.js"></script>';
        $this->extra_head_code .= '<script type="text/javascript" src="' . _app_path . 'humblee/js/admin/media.js"></script>';
        $this->extra_head_code .= '<link rel="stylesheet" type="text/css" href="' . _app_path . 'humblee/css/admin/media.css">';

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

        $this->hidden_users = ['joe@backdoor.dev'];

        $searchCriteria = isset($_POST['user_search']) ? htmlspecialchars(trim($_POST['user_search'])) : '';
        if (!empty($searchCriteria)) {
            $this->users = \ORM::for_table(_table_users)
                ->where_any_is([
                    ['name' => '%' . $searchCriteria . '%'],
                    ['username' => '%' . $searchCriteria . '%'],
                    ['email' => '%' . $searchCriteria . '%']
                ], 'LIKE')
                ->find_many();
        } else {
            $this->users = \ORM::for_table(_table_users)->find_many();
        }

        $this->extra_head_code = '<script type="text/javascript" src="' . _app_path . 'humblee/js/admin/users.js"></script>';
        $this->template_view = Core::view(_app_server_path . 'humblee/views/admin/users.php', get_object_vars($this));
        echo Core::view(_app_server_path . 'humblee/views/admin/templates/template.php', get_object_vars($this));
    }

    public function blocks(): void
    {
        $this->require_role('designer');
        $params = [
            'id'    => $this->_uri_parts[2] ?? false,
            'table' => _table_content_types,
            'view'  => _app_server_path . 'humblee/views/admin/blocks.php',
            'post'  => !empty($_POST) ? $_POST : false,
            'allow_html' => true,
            'validate' => [
                'name'      => ['if' => fn($val) => $val !== '', 'error_message' => 'Name field cannot be blank'],
                'objectkey' => ['if' => fn($val) => $val !== '', 'error_message' => 'objectKey field cannot be blank']
            ],
            'post_ignore'       => ['submit'],
            'crud_all_order_by' => 'name'
        ];

        $this->extra_head_code = '<script type="text/javascript" src="' . _app_path . 'humblee/js/admin/blocks.js"></script>';
        $this->tools->CRUD($params, $this);
    }

    public function templates(): void
    {
        $this->require_role('designer');

        if (!empty($_POST)) {
            $_POST['blocks'] = isset($_POST['blocks']) ? implode(",", $_POST['blocks']) : '';
            $_POST['dynamic_uri'] = $_POST['dynamic_uri'] ?? 0;
            $_POST['available'] = $_POST['available'] ?? 0;

            if (isset($_POST['page_type'])) {
                switch ($_POST['page_type']) {
                    case 'view':
                        $_POST['page_meta'] = $_POST['default_view'];
                        break;
                    case 'controller':
                        $page_meta['controller'] = $_POST['controller'];
                        $page_meta['action'] = $_POST['controller_action'];
                        $_POST['page_meta'] = serialize($page_meta);
                        break;
                    default:
                        $_POST['page_type'] = 'default';
                        $_POST['page_meta'] = 'tierpage';
                }
            }
        }
        $params = [
            'id'    => $this->_uri_parts[2] ?? false,
            'table' => _table_templates,
            'view'  => _app_server_path . "humblee/views/admin/templates.php",
            'post'  => !empty($_POST) ? $_POST : false,
            'allow_html' => true,
            'validate' => [
                'name' => ['if' => fn($val) => $val !== '', 'error_message' => 'Name field cannot be blank']
            ],
            'post_ignore'       => ['submit', 'controller', 'controller_action', 'default_view'],
            'crud_all_order_by' => 'name'
        ];
        $this->tools->CRUD($params, $this);
    }

    public function personalization(): void
    {
        $this->require_role('designer');

        if (!empty($_POST)) {
            $_POST['active'] = $_POST['active'] ?? 0;
        }

        $params = [
            'id'    => $this->_uri_parts[2] ?? false,
            'table' => _table_content_p13n,
            'view'  => _app_server_path . 'humblee/views/admin/personalization.php',
            'post'  => !empty($_POST) ? $_POST : false,
            'allow_html' => true,
            'validate' => [
                'name'      => ['if' => fn($val) => $val !== '', 'error_message' => 'Name field cannot be blank'],
                'objectkey' => ['if' => fn($val) => $val !== '', 'error_message' => 'objectKey field cannot be blank']
            ],
            'post_ignore'       => ['submit'],
            'crud_all_order_by' => 'name'
        ];

        $this->extra_head_code = '<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js" integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=" crossorigin="anonymous"></script>';
        $this->extra_head_code .= '<link rel="stylesheet" href="https://code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.css">';
        $this->extra_head_code .= '<script type="text/javascript" src="' . _app_path . 'humblee/js/admin/personalization.js"></script>';
        $this->extra_head_code .= '<link rel="stylesheet" type="text/css" href="' . _app_path . 'humblee/css/admin/personalization.css">';

        $p13nObj = new Personalization;
        $this->allP13nVersions = $p13nObj->getAll();

        $this->tools->CRUD($params, $this);
    }
}
