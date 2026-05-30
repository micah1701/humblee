<?php

/**
 * Article Feed Content Widget for Humblee Framework
 * 
 * Base class
 * 
 * @author Micah Murray | github.com/micah1701
 */

class feedRest extends Core_Controller_Xhr
{
    protected $action;
    protected $actionParam;

    public function __construct(array $allowedActions)
    {
        $_uri_parts = Core::getURIparts();
        if (!isset($_uri_parts[2]) || !in_array($_uri_parts[2], $allowedActions)) {
            return ["error" => "Malformed URL. Missing valid feed action", "responseCode" => 400];
        }
        $this->action = $_uri_parts[2];
        $this->actionParam = (isset($_uri_parts[3]) && $_uri_parts[3] != "") ? $_uri_parts[3] : null;
    }

    /**
     * Return list of articles
     * @param $publishedOnly boolean when true, the list contains only the most recently PUBLISHED and ACTIVE articles. False returns all.
     * @param $limit int of the number of articles to return.
     * @return array<ORM> - array of ORM Models for requested list
     */
    protected function _listArticles(bool $publishedOnly = true, int $limit = 30000): array
    {
        $relevantDateField = ($publishedOnly) ? 'publish_date' : 'revision_date';
        $articles = ORM::for_table('humblee_feed_articles')->table_alias('articles')
            ->select('articles.*')
            ->select('articles.id', 'articleId')
            ->select_many(['updated_by_email' => 'user.email', 'updated_by_username' => 'user.username', 'updated_by_name' => 'user.name'])
            ->select_expr(
                'IF(articles.parent_id != 0, 
                 (SELECT id FROM humblee_feed_articles WHERE parent_id = articles.parent_id ORDER BY publish_date DESC, revision_date DESC, display_date DESC limit 1), 
                 (SELECT id FROM humblee_feed_articles WHERE id = articles.id OR parent_id = articles.id ORDER BY publish_date DESC, revision_date DESC, display_date DESC limit 1)
                )',
                'LatestRevisionID'
            );
        if ($publishedOnly) {
            $articles
                ->where_not_equal('publish_date', '0000-00-00 00:00:00')
                ->where_raw('(end_date = \'0000-00-00 00:00:00\' OR end_date is NULL OR end_date >= \'' . date("Y-m-d H:i:s") . '\')')
                ->where_lt('display_date', date("Y-m-d H:i:s"));
        } else {
            $articles
                ->select_expr('(SELECT COUNT(id) FROM humblee_feed_articles WHERE id = articles.id OR id = articles.parent_id OR parent_id = articles.parent_id)', 'totalRevisions');
        }
        $articles
            ->left_outer_join("humblee_users", ["user.id", "=", "articles.updated_by"], 'user')
            ->having_raw('articles.id = LatestRevisionID')
            ->order_by_desc('display_date')
            ->limit($limit);
        $articles = $articles->find_many();
        return $articles;
    }

    /**
     * Return requested article data
     * @param $articleID int primary key for given article revision
     * @param $includeRevisions (optional) when set to true, returns ALL revisions related to the given requested article
     */
    protected function _getArticleById(int $articleId, bool $includeRevisions = false): array
    {
        $requestedArticle = $this->_queryArticles($articleId);

        if (!$requestedArticle || count($requestedArticle) == 0) {
            return ["error" => "Article ID not found.", "statusCode" => 404];
        }

        if ($includeRevisions) {
            if ($requestedArticle[0]->parent_id == 0 || $requestedArticle[0]->parent_id == $requestedArticle[0]->id) {
                $relatedArticleId = $requestedArticle[0]->id; // use this articles id to find articles with it as their "parent_id"
                $articleIdIsParentId = true;
            } else {
                $relatedArticleId = $requestedArticle[0]->parent_id; // use this articles "parent_id" to find others that share this "parent_id" AND the original parent
                $articleIdIsParentId = false;
            }
            $revisions = $this->_queryArticles($relatedArticleId, true, $articleIdIsParentId);
        }

        if (!$includeRevisions || !$revisions || count($revisions) == 0) {
            $revisions = [];
        }
        $articles = array_merge($requestedArticle, $revisions);
        $articlesOrderByPublishDate = $this->_sortORM($articles, "publish_date", "DESC");
        $articlesOrderByRevisionDate = $this->_sortORM($articles, "revision_date", "DESC");
        $articlesByKeyOrderedByPublishDate = [];
        $articlesByKeyOrderedByrevisionDate = [];
        foreach ($articlesOrderByPublishDate as $article) {
            $articlesByKeyOrderedByPublishDate[$article->id] = $article;
        }
        foreach ($articlesOrderByRevisionDate as $article) {
            $articlesByKeyOrderedByrevisionDate[$article->id] = $article;
        }
        $newestPublished = reset($articlesOrderByPublishDate); // first article in the array
        $newestRevision = reset($articlesOrderByRevisionDate); // ibid

        $result = ["id" => $articleId];
        $revisionIndex = 0;
        foreach ($articlesByKeyOrderedByrevisionDate as $article) {

            $result["revisions"][$revisionIndex] = [
                "id" => $article->id,
                "publish_date" => $article->publish_date,
                "revision_date" => $article->revision_date,
                "status" => $this->articleStatus($article),
                "first_edition" => ($article->parent_id == $article->id || $article->parent_id == 0) ? true : $article->parent_id,
                "latest_published" => ($newestPublished->id == $article->id) ? true : $newestPublished->id,
                "latest_revision" => ($newestRevision->id == $article->id) ? true : $newestRevision->id,
                "updated_by" => $article->updated_by,
                "updated_by_name" => $article->updated_by_name,
                "updated_by_email" => $article->updated_by_email
            ];

            if ($article->id == $articleId) {
                $result["selected"] = $revisionIndex;
                $result["revisions"][$revisionIndex]["contents"] = json_decode($article->content);
            }

            $revisionIndex++;
        }

        return $result;
    }

    /**
     * helper function to get an article and (optionally) all other revisions
     * 
     * @param $articleId = Article ID to search for 
     * @param $findOtherVersions - FALSE returns one article with given ID. FALSE Returns ALL articles with articleID as their parent_id
     * @param $articleIdIsParentId - FALSE returns both articles with the given article Id OR parent_id 
     *                             - TRUE if the given article ID should only find other articles with it as their "parent_id"
     * 
     */
    private function _queryArticles(int $articleId, bool $findOtherVersions = false, bool $articleIdIsParentId = false): array|false
    {
        if ($articleId == null) {
            return false;
        }
        $articles = ORM::for_table('humblee_feed_articles') //->table_alias('article')
            ->select('humblee_feed_articles.*')
            ->select('humblee_feed_articles.id', 'id')
            ->select_many(['updated_by_email' => 'user.email', 'updated_by_username' => 'user.username', 'updated_by_name' => 'user.name']);
        if ($findOtherVersions) {
            if ($articleIdIsParentId) {
                $articles->where("humblee_feed_articles.parent_id", $articleId)
                    ->where_not_equal("humblee_feed_articles.id", $articleId);
            } else {
                $articles->where_any_is([
                    ["humblee_feed_articles.parent_id" => $articleId],
                    ["humblee_feed_articles.id" => $articleId]
                ]);
            }
        } else {
            $articles->where('humblee_feed_articles.id', $articleId);
        }
        $articles->left_outer_join("humblee_users", ["user.id", "=", "humblee_feed_articles.updated_by"], 'user')
            ->order_by_desc('publish_date')
            ->order_by_desc('revision_date');

        return $articles->find_many();
    }

    //helper function to determine the publication status of an article
    private function articleStatus(ORM $article): string
    {
        if ($article->publish_date == null || $article->publish_date == "0000-00-00 00:00:00") {
            return "Draft";
        }
        if (strtotime($article->publish_date) > time()) {
            return "Published Future";
        }
        if ($article->end_date != null && $article->end_date != "0000-00-00 00:00:00" && strtotime($article->end_date) < time()
        ) {
            return "Published Expired";
        }

        return "Published";
    }

    /**
     * @param array<ORM> $ORM
     * @param string $key - name of ORM object key to use as array key
     */

    private function _sortORM(array $ORM, string $key, string|bool $order = "ASC"): array
    {
        $sorted = [];
        foreach ($ORM as $record) {
            $sorted[$record->{$key}] = $record;
        }

        if (!$order) {
            return $sorted;
        } else {
            ($order == "DESC") ? krsort($sorted) : ksort($sorted);
            return $sorted;
        }
    }
}
