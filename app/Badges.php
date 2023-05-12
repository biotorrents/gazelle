<?php

declare(strict_types=1);


/**
 * Badges
 */

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
        $app = \Gazelle\App::go();

        $query = "select badgeId, displayed from users_badges where userId = ?";
        $ref = $app->dbNew->multi($query, [$userId]);

        $data = [];
        foreach ($ref as $row) {
            $key = $row["badgeId"];
            $data[$key] = $row["displayed"];
        }

        #!d($data);exit;
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
        $app = \Gazelle\App::go();

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

        #!d($data);exit;
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
     * @param bool $tooltip should the html contain a tooltip?
     * @return ?string html
     */
    public static function displayBadge(int $badgeId, bool $tooltip = true): ?string
    {
        $app = \Gazelle\App::go();

        $query = "select * from badges where id = ?";
        $row = $app->dbNew->row($query, [$badgeId]);
        #!d($row);exit;

        /*
        if (!$row) {
            return null;
        }
        */

        if ($tooltip) {
            $html = "<img class='badge' alt='{$row["Name"]}: {$row["Description"]}' title='{$row["Name"]}: {$row["Description"]}' src='{$row["Icon"]}'>";
        } else {
            $html = "<img class='badge' alt='{$row["Name"]}: {$row["Description"]}' src='{$row["Icon"]}'>"; # no title
        }

        return $html;
    }


    /**
     * displayBadges
     */
    public static function displayBadges(array $badgeIds, $tooltip = false): string
    {
        $data = [];

        foreach ($badgeIds as $badgeId) {
            $data[] = self::displayBadge($badgeId, $tooltip);
        }

        $data = implode("", $data);
        $data = "<span class='badges'>{$data}</span>";

        return $data;
    }


    /**
     * getAllBadges
     */
    public static function getAllBadges(): array
    {
        $app = \Gazelle\App::go();

        $query = "select * from badges";
        $ref = $app->dbNew->multi($query, []);

        return $ref;
    }
}
