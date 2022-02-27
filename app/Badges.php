<?php
declare(strict_types = 1);

class Badges
{
    /**
     * Given a UserID, returns that user's badges
     *
     * @param int $UserID
     * @return array of BadgeIDs
     */
    public static function get_badges($UserID)
    {
        return Users::user_info($UserID)['Badges'];
    }


    /**
     * Awards UserID the given BadgeID
     *
     * @param int $UserID
     * @param int $BadgeID
     * @return bool success?
     */
    public static function award_badge($UserID, $BadgeID)
    {
        if (self::has_badge($UserID, $BadgeID)) {
            return false;
        } else {
            $QueryID = G::$db->get_query_id();
            G::$db->prepared_query("
            INSERT INTO `users_badges`(`UserID`, `BadgeID`)
            VALUES($UserID, $BadgeID)
            ");

            G::$db->set_query_id($QueryID);
            G::$cache->delete_value("user_info_$UserID");
            return true;
        }
    }


    /**
     * Given a UserID, return that user's displayed badges
     *
     * @param int $UserID
     * @return array of BadgeIDs
     */
    public static function get_displayed_badges($UserID)
    {
        $Result = [];
        $Badges = self::get_badges($UserID);

        foreach ($Badges as $Badge => $Displayed) {
            if ($Displayed) {
                $Result[] = $Badge;
            }
        }
        return $Result;
    }


    /**
     * Returns true if the given user owns the given badge
     *
     * @param int $UserID
     * @param int $BadgeID
     * @return bool
     */
    public static function has_badge($UserID, $BadgeID)
    {
        $Badges = self::get_badges($UserID);
        return (array_key_exists($BadgeID, $Badges)) ?: false;
    }


    /**
     * Creates HTML for displaying a badge.
     *
     * @param int $BadgeID
     * @param bool $Tooltip Should HTML contain a tooltip?
     * @return string HTML
     */
    public static function display_badge($BadgeID, $Tooltip = false)
    {
        $Debug = Debug::go();

        $html = '';
        if (($Badges = G::$cache->get_value('badges')) && array_key_exists($BadgeID, $Badges)) {
            extract($Badges[$BadgeID]);
        } else {
            self::update_badge_cache();
            if (($Badges = G::$cache->get_value('badges')) && array_key_exists($BadgeID, $Badges)) {
                extract($Badges[$BadgeID]);
            }
        }

        if ($Tooltip) {
            $html .= "<a class='badge_icon'><img class='badge tooltip' alt='$Name' title='$Name: $Description' src='$Icon' /></a>";
        } else {
            $html .= "<a class='badge_icon'><img class='badge' alt='$Name' title='$Name' src='$Icon' /></a>";
        }

        return $html;
    }


    /**
     * display_badges()
     */
    public static function display_badges($BadgeIDs, $Tooltip = false)
    {
        $html = '';
        foreach ($BadgeIDs as $BadgeID) {
            $html .= self::display_badge($BadgeID, $Tooltip);
        }
        return $html;
    }


    /**
     * update_badge_cache()
     */
    private static function update_badge_cache()
    {
        $QueryID = G::$db->get_query_id();

        G::$db->prepared_query("
        SELECT
          `ID`,
          `Icon`,
          `Name`,
          `Description`
        FROM
          `badges`
        ");

        $badges = [];
        if (G::$db->has_results()) {
            while (list($id, $icon, $name, $description) = G::$db->next_record()) {
                $badges[$id] = array('Icon' => $icon, 'Name' => $name, 'Description' => $description);
            }
            G::$cache->cache_value('badges', $badges);
        }

        G::$db->set_query_id($QueryID);
    }


    /**
     * get_all_badges()
     */
    public static function get_all_badges()
    {
        if (($Badges = G::$cache->get_value('badges'))) {
            return $Badges;
        } else {
            self::update_badge_cache();
            return G::$cache->get_value('badges');
        }
    }
}
