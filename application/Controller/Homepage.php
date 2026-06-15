<?php

namespace App\Controller;

class Homepage extends Page
{
    public function view(): string
    {
        return $this->render('application/views/homepage-static.php');
    }
}
