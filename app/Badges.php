<?php

declare(strict_types=1);


/**
 * Gazelle\Badges
 */

namespace Gazelle;

class Badges
{
    /**
     * getBadges
     *
     * Given a userId, returns that user's badges.
     *
     * @param int $userId
     * @return array of badgeId's
     */
    public static function getBadges(int $userId): array
    {
        $app = App::go();

        $query = "select badgeId, displayed from users_badges where userId = ?";
        $ref = $app->dbNew->multi($query, [$userId]);

        # foreach() argument must be of type array|object, null given
        if (!$ref) {
            return [];
        }

        $data = [];
        foreach ($ref as $row) {
            $key = $row["badgeId"];
            $data[$key] = $row["displayed"];
        }

        return $data;
    }


    /**
     * awardBadge
     *
     * Awards a userId a badgeId.
     *
     * @param int $userId
     * @param int $badgeId
     * @return bool success?
     */
    public static function awardBadge(int $userId, int $badgeId): bool
    {
        $app = App::go();

        if (self::hasBadge($userId, $badgeId)) {
            return false;
        }

        $query = "insert into users_badges (userId, badgeId) values (?, ?)";
        $app->dbNew->do($query, [$userId, $badgeId]);

        return true;
    }


    /**
     * getDisplayedBadges
     *
     * Given a userId, return that user's displayed badges.
     *
     * @param int $userId
     * @return array of badgeId's
     */
    public static function getDisplayedBadges(int $userId): array
    {
        $data = [];
        $badges = self::getBadges($userId);

        foreach ($badges as $id => $displayed) {
            if (!empty($displayed)) {
                $data[] = $id;
            }
        }

        return $data;
    }


    /**
     * hasBadge
     *
     * Returns true if a user owns a badge.
     *
     * @param int $userId
     * @param int $badgeId
     * @return bool
     */
    public static function hasBadge(int $userId, int $badgeId): bool
    {
        $badges = self::getBadges($userId);

        return array_key_exists($badgeId, $badges);
    }


    /**
     * displayBadge
     *
     * Creates HTML for displaying a badge.
     *
     * @param int $badgeId
     * @param bool $tooltip
     * @return ?string html
     */
    public static function displayBadge(int $badgeId, bool $tooltip = true): ?string
    {
        $app = App::go();

        $query = "select * from badges where id = ?";
        $row = $app->dbNew->row($query, [$badgeId]);

        if (!$row) {
            return null;
        }

        if ($tooltip) {
            return "<span class='badge tooltip' title='{$row["name"]}: {$row["description"]}'>{$row["icon"]}</span>";
        } else {
            return "<span class='badge'>{$row["icon"]}</span>";
        }
    }


    /**
     * badgeDescription
     *
     * Get a badge's description.
     *
     * @param int $badgeId
     * @return ?string
     */
    public static function badgeDescription(int $badgeId): ?string
    {
        $app = App::go();

        $query = "select name, description from badges where id = ?";
        $row = $app->dbNew->row($query, [$badgeId]);

        if (!$row) {
            return null;
        }

        return "{$row["name"]}: {$row["description"]}";
    }


    /**
     * displayBadges
     *
     * Given an array of badgeId's, returns HTML for displaying them.
     *
     * @param array $badgeIds
     * @return string html
     */
    public static function displayBadges(array $badgeIds): string
    {
        $data = [];

        foreach ($badgeIds as $badgeId) {
            $data[] = self::displayBadge($badgeId);
        }

        $data = implode("", $data);
        $data = "<span class='badges'>{$data}</span>";

        return $data;
    }


    /**
     * getAllBadges
     *
     * Returns all badges.
     *
     * @return array of badge data
     */
    public static function getAllBadges(): array
    {
        $app = App::go();

        $query = "select * from badges";
        $ref = $app->dbNew->multi($query, []);

        return $ref;
    }
}
