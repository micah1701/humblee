<?php

namespace App\Controller;

use Humblee\Controller\Template;
use Humblee\Foundation\Core;

/**
 * Base controller for pages rendered outside the default site template.
 *
 * Extend this class and implement an action method that returns the full HTML
 * string for the page. The outer wrapper is a bare pass-through, so the view
 * controls the entire document.
 *
 * Example — used as a sub-controller (page_type = "controller" in the CMS):
 *
 *   class MyPage extends Page
 *   {
 *       public function index(array $context): string
 *       {
 *           return $this->render('application/views/my-page.php', [
 *               'title' => 'My Page',
 *           ]);
 *       }
 *   }
 */
class Page extends Template
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplatePath('application/views/templates/bare.php');
    }

    /**
     * Render a view file and return its output as a string.
     *
     * @param string $view  Path relative to the application root (e.g. 'application/views/my-page.php')
     * @param array  $vars  Variables to extract into the view's scope
     */
    protected function render(string $view, array $vars = []): string
    {
        $full_path = _app_server_path . ltrim($view, '/');
        return Core::view($full_path, $vars ?: false) ?: '';
    }
}
