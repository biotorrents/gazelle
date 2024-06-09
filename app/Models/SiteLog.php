<?php

declare(strict_types=1);


/**
 * Gazelle\SiteLog
 */

namespace Gazelle;

class SiteLog extends ObjectCrud
{
    # https://jsonapi.org/format/1.2/#document-resource-objects
    public static ?string $type = "siteLog"; # resource name
    protected ?string $table = "site_log"; # database table

    # cache settings
    protected ?string $cachePrefix = "siteLog:";

    # actions allowed to be written to the database
    public static array $allowedActions = ["create", "read", "update", "delete"];

    # ["database" => "display"]
    protected array $maps = [
        "id" => "id",
        "userId" => "userId",
        "contentId" => "contentId",
        "contentType" => "contentType",
        "action" => "action",
        "description" => "description",
        "created_at" => "createdAt",
        "updated_at" => "updatedAt",
        "deleted_at" => "deletedAt",
    ];


    /** methods */


    /**
     * search
     *
     * Search the site log.
     *
     * @param string $search
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function searchDatabase(string $search, int $offset = 0, int $limit = 20): array
    {
        $app = App::go();

        $words = explode(" ", $search);
        $query = "select * from site_log where description like ? order by created_at desc limit $offset, $limit";
        $ref = $app->dbNew->multi($query, ["%" . implode("%", $words) . "%"]);

        $data = [];
        foreach ($ref as $row) {
            $data[] = [
                "id" => $row["id"],
                "userId" => $row["userId"],
                "contentId" => $row["contentId"],
                "contentType" => $row["contentType"],
                "action" => $row["action"],
                "description" => $row["description"],
                "createdAt" => $row["created_at"],
                "updatedAt" => $row["updated_at"],
                "deletedAt" => $row["deleted_at"],
            ];
        }

        return $data;
    }


    /**
     * getUserActions
     *
     * Get all actions for a user.
     *
     * @param ?int $userId
     * @return array
     */
    public function getUserActions(?int $userId): array
    {
        $app = App::go();

        # default to the current user
        if (!$userId && !empty($app->user->core)) {
            $userId = $app->user->core["id"];
        }

        $query = "select * from site_log where userId = ? order by created_at desc";
        $ref = $app->dbNew->multi($query, [$userId]);

        $data = [];
        foreach ($ref as $row) {
            $data[] = [
                "id" => $row["id"],
                "userId" => $row["userId"],
                "contentId" => $row["contentId"],
                "contentType" => $row["contentType"],
                "action" => $row["action"],
                "description" => $row["description"],
                "createdAt" => $row["created_at"],
                "updatedAt" => $row["updated_at"],
                "deletedAt" => $row["deleted_at"],
            ];
        }

        return $data;
    }


    /** language transformations */


    /**
     * toString
     *
     * Turns a site log entry into a sentence.
     *
     * @return string
     */
    public function toString(): string
    {
        $app = App::go();

        return "{$this->user->core["username"]} {$this->action} the {$this->contentType} {$this->description}";
    }


    /**
     * pastTense
     *
     * Turns an action into past tense.
     *
     * @param string $action
     * @return string
     */
    public static function pastTense(string $action): string
    {
        return match ($action) {
            "create" => "created",
            "read" => "read",
            "update" => "updated",
            "delete" => "deleted",
            default => throw new Exception("invalid action"),
        };
    }


    /**
     * singular
     *
     * Turns a contentType into singular form.
     *
     * @param string $type
     * @return string
     */
    public static function singular(string $type): string
    {
        return match ($type) {
            "torrents" => "torrent",
            "groups" => "group",
            "creators" => "creator",
            "collages" => "collage",
            "requests" => "request",
            default => throw new Exception("invalid type"),
        };
    }
} # class
