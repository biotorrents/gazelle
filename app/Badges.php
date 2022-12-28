<?php

declare(strict_types=1);


/**
 * Badges
 */

class Badges
{
    /**
     * get_badges
     *
     * Given a userId, returns that user's badges.
     *
     * @param int $userId
     * @return array of badgeId's
     */
    public static function get_badges(int $userId): array
    {
        $app = App::go();

        $query = "select badgeId, displayed from users_badges where userId = ?";
        $ref = $app->dbNew->multi($query, [$userId]);

        $data = [];
        foreach ($ref as $row) {
            $key = $row["badgeId"];
            $data[$key] = $row["displayed"];
        }

        return $data;
    }


    /**
     * award_badge
     *
     * Awards a userId a badgeId.
     *
     * @param int $userId
     * @param int $badgeId
     * @return bool success?
     */
    public static function award_badge(int $userId, int $badgeId): bool
    {
        $app = App::go();

        if (self::has_badge($userId, $badgeId)) {
            return false;
        }

        $query = "insert into users_badges (userId, badgeId) values (?, ?)";
        $app->dbNew->do($query, [$userId, $badgeId]);

        return true;
    }


    /**
     * get_displayed_badges
     *
     * Given a userId, return that user's displayed badges.
     *
     * @param int $userId
     * @return array of badgeId's
     */
    public static function get_displayed_badges(int $userId): array
    {
        $data = [];
        $badges = self::get_badges($userId);

        foreach ($badges as $id => $displayed) {
            if ($displayed) {
                $data[] = $id;
            }
        }

        return $data;
    }


    /**
     * has_badge
     *
     * Returns true if a user owns a badge.
     *
     * @param int $userId
     * @param int $badgeId
     * @return bool
     */
    public static function has_badge(int $userId, int $badgeId): bool
    {
        $badges = self::get_badges($userId);

        return array_key_exists($badgeId, $badges);
    }


    /**
     * display_badge
     *
     * Creates HTML for displaying a badge.
     *
     * @param int $badgeId
     * @param bool $tooltip should the html contain a tooltip?
     * @return string html
     */
    public static function display_badge(int $badgeId, bool $tooltip = true): string
    {
        $app = App::go();

        $query = "select * from badges where id = ?";
        $row = $app->dbNew->row($query, [$badgeId]);

        if ($tooltip) {
            $html = "<img class='badge' alt='{$row["Name"]}: {$row["Description"]}' title='{$row["Name"]}: {$row["Description"]}' src='{$row["Icon"]}' />";
        } else {
            $html = "<img class='badge' alt='{$row["Name"]}: {$row["Description"]}' src='{$row["Icon"]}' />"; # no title
        }

        return $html;
    }


    /**
     * display_badges
     *
     * This should be a flexbox but idgaf.
     */
    public static function display_badges(array $badgeIds, $tooltip = false): string
    {
        $data = [];

        foreach ($badgeIds as $badgeId) {
            $data[] = self::display_badge($badgeId, $tooltip);
        }

        return implode("&emsp;", $data);
    }


    /**
     * get_all_badges
     */
    public static function get_all_badges(): array
    {
        $app = App::go();

        $query = "select * from badges";
        $ref = $app->dbNew->multi($query, []);

        return $ref;
    }
}
