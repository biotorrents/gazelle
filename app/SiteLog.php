<?php

declare(strict_types=1);


/**
 * Gazelle\SiteLog
 */

namespace Gazelle;

class SiteLog extends ObjectCrud
{
    # https://jsonapi.org/format/1.2/#document-resource-objects
    public ?int $id = null; # primary key
    public string $type = "site_log"; # database table
    public ?RecursiveCollection $attributes = null;
    public ?RecursiveCollection $relationships = null;

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

    # cache settings
    private string $cachePrefix = "siteLog:";
    private string $cacheDuration = "1 hour";


    /**
     * relationships
     */
    public function relationships(): void
    {
        $app = App::go();

        $this->relationships = new RecursiveCollection([
            "user" => $app->user->readProfile($this->attributes->userId),
        ]);
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
} # class
