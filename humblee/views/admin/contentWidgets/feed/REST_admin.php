<?php

/**
 * Article Feed Content Widget for Humblee Framework
 * 
 * CMS Admin functions for managing Article Feed
 * 
 * @author Micah Murray | github.com/micah1701
 */
include('REST.php');
class feedRest_admin extends feedRest
{
    protected $validActions = ["article", "save", "list"];

    public function __construct()
    {
        //sets the "action" verb as well as any "action parameter"
        parent::__construct($this->validActions);
    }

    public function run(): array
    {
        switch ($this->action) {
            case "article":
                return $this->_getArticleById($this->actionParam, true);
                break;

            case "save":
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    return ["error" => "Invalid Request Method", "statusCode" => 405];
                }
                if (!Core::auth('content') && !Core::auth('developer')) {
                    return ["error" => "You do not have permission to save articles", "statusCode" => 403];
                }
                $input = file_get_contents("php://input");
                $saveData = json_decode($input);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return ["error" => "JSON Invalid or Malformed. " . json_last_error()];
                }
                if ($saveData->publish == true && !Core::auth('publish') && !Core::auth('developer')) {
                    return ["error" => "You do not have permission to publish articles", "statusCode" => 403];
                }

                $_POST["hmac_token"] = $saveData->hmac_token;
                $_POST["hmac_key"] = $saveData->hmac_key;
                $this->require_hmac();

                return $this->saveArticle($saveData);
                break;

            case "list":
                if (!Core::auth('content') && !Core::auth('developer')) {
                    return ["error" => "Requires elevated role.", "statusCode" => 403];
                }
                $list = $this->_listArticles(false);
                $result = [];
                foreach ($list as $article) {
                    $contents = json_decode($article->content);
                    $result[] = [
                        "display_date" => $article->display_date,
                        "end_date" => $article->end_date,
                        "author" => $article->updated_by_name,
                        "revision_date" => $article->revision_date,
                        "publish_date" => $article->publish_date,
                        "headline" => $contents->headline,
                        "content" => substr($contents->content, 0, 200),
                        "template" => $contents->template,
                        "id" => $article->id,
                        "parent_id" => $article->parent_id,
                        "revisions" => $article->totalRevisions
                    ];
                }
                // return ["lastQuery" => ORM::get_last_statement()];
                return $this->json($result);
                break;

            default:
                return ["error" => "Malformed URL. Invalid feed action.", "statusCode" => 400];
        }
    }

    private function saveArticle($saveData): array
    {
        $articleEdits = $saveData->articleEdits->contents;
        $newContent_as_string = json_encode($articleEdits);
        $isDirty = true; // assume changes have been made since the last revision (or that this is a new article)
        $updatesExisting = false; // assume new article, unless changed below

        // check if this is an update to an existing draft
        if (is_numeric($saveData->id) && $saveData->id > 0) {
            $updatesExisting = true;
            $draft = ORM::for_table("humblee_feed_articles")->find_one($saveData->id);
            if (!$draft) {
                return ["success" => false, "Error" => "Attempting to update article revision number \"" . $saveData->id . "\" but it can not be found."];
            }
            // check if submitted draft has changed from its last save
            // (if the user is just publishing a previously saved draft, there would be no new changes and we don't need to create a new article revision)
            $isDirty = (md5($draft->content) != md5($newContent_as_string));
        }

        if (!$isDirty && !$saveData->publish) {
            //there were no changes and we're not publishing, so we're done here.
            return ["success" => true, "new_id" => $saveData->id, "Note" => "No changes made"];
        }

        // If a draft thats never been published is being saved, don't create a new revision.
        // UNLESS there is new content AND
        // the draft is being published, 
        // or if its a completely new article,
        // or if it is a previously published draft
        // or if "newDraft" is true.
        if (
            $isDirty
            && ($saveData->publish == true
                || $saveData->id == 0
                || ($updatesExisting && $draft->publish_date != '0000-00-00 00:00:00')
                || (isset($saveData->newDraft) && $saveData->newDraft == true))
        ) {
            $draft = ORM::for_table("humblee_feed_articles")->create();
            if (!$updatesExisting) {
                $draft->parent_id = 0;
            } else {
                $draft->parent_id = ($saveData->parent_id != 0) ? $saveData->parent_id : $saveData->id;
            }
        }

        // only update the display_date if the user supplied a "release date"
        $draft->display_date = (trim($articleEdits->display_date) != "")
            ? $articleEdits->display_date
            : null;

        // only update the end_date if the user supplied an "archive date"
        $draft->end_date = ($articleEdits->end_date != "")
            ? $articleEdits->end_date
            : null;

        //if user set a "release date" (eg, "display_date") then use that, otherwise, display date is "now"
        $draft->display_date = (trim($articleEdits->display_date) != "")
            ? $articleEdits->display_date
            : date("Y-m-d H:i:s");

        //if publishing, set the publish date and make sure there is a valid display date
        if ($saveData->publish == true) {

            $draft->publish_date = date("Y-m-d H:i:s");

            //set a default display_date incase the user didn't supply one (defaults to now) unless changed below
            if ($draft->display_date == null || $draft->display_date == "0000-00-00 00:00:00") {
                $draft->display_date = date("Y-m-d H:i:s");
            }
        }

        $draft->updated_by = $_SESSION[session_key]['user_id'];
        $draft->content = $newContent_as_string;
        $draft->save();
        return ["success" => true, "isDirty" => $isDirty, "new_id" => $draft->id()];
    }
}
