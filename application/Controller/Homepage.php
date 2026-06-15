<?php

namespace App\Controller;

class Homepage extends Page
{
    function __construct()
    {
        parent::__construct();
        $this->setTemplatePath('application/views/templates/bare.php');
        $this->index([]);
    }

    public function index(array $context = []): string
    {
        return $this->render('application/views/homepage-static.php', $context);
    }
}
