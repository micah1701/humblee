<?php

/**
 * Article Feed Content Widget for Humblee Framework
 * 
 * Public functions for managing Article Feed
 * Register Article Feed app by a reference to this file in the public REST controller
 * @example 
 * <?php
 * include_once '
 * 
 * @author Micah Murray | github.com/micah1701
 */
include('REST.php');
class feedRest_public extends feedRest
{
    protected $validActions = ["article", "feed"];

    public function __construct()
    {
        parent::__construct($this->validActions);
    }

    public function run()
    {
        switch ($this->action) {
            case "article":
                return $this->_getArticleById($this->actionParam);
                break;
            case "feed":
                $list = $this->_listArticles(true);
                $result = [];
                foreach ($list as $article) {
                    $result[] = [
                        "display_date" => $article->display_date,
                        "updated_by_name" => $article->updated_by_name,
                        "revision_date" => $article->revision_date,
                        "publish_date" => $article->publish_date,
                        "contents" => json_decode($article->content)
                    ];
                }
                $this->json($result);
                break;
            default:
                return ["error" => "Malformed URL. Invalid feed action.", "statusCode" => 400];
        }
    }
}
