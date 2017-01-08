<?
class Badges {
  /**
   * Given a UserID, returns that user's badges
   *
   * @param int $UserID
   * @return array of BadgeIDs
   */
  public static function get_badges($UserID) {
    $Result = array();

    if (G::$Cache->get_value('user_badges_'.$UserID)) {
      return G::$Cache->get_value('user_badges_'.$UserID);
    }

    $QueryID = G::$DB->get_query_id();
    G::$DB->query("
      SELECT BadgeID, Displayed
      FROM users_badges
      WHERE UserID = ".$UserID);

    if (G::$DB->has_results()) {
      while (list($BadgeID, $Displayed) = G::$DB->next_record()) {
        $Result[] = array('BadgeID' => $BadgeID, 'Displayed' => $Displayed);
      }
    }

    G::$DB->set_query_id($QueryID);

    G::$Cache->cache_value('user_badges_'.$UserID, $Result);

    return $Result;
  }

  /**
   * Awards UserID the given BadgeID
   *
   * @param int $UserID
   * @param int $BadgeID
   * @return bool success?
   */
  public static function award_badge($UserID, $BadgeID) {
    if (self::has_badge($UserID, array('BadgeID' => $BadgeID))) {
      return false;
    } else {
      $QueryID = G::$DB->get_query_id();
      G::$DB->query("
        INSERT INTO users_badges
          (UserID, BadgeID)
        VALUES
          ($UserID, $BadgeID)");
      G::$DB->set_query_id($QueryID);

      G::$Cache->delete_value('user_badges_'.$UserID);

      return true;
    }
  }

  /**
   * Given a UserID, return that user's displayed badges
   *
   * @param int $UserID
   * @return array of BadgeIDs
   */
  public static function get_displayed_badges($UserID) {
    $Result = array();

    $Badges = self::get_badges($UserID);

    foreach ($Badges as $Badge) {
      if ($Badge['Displayed'])
        $Result[] = $Badge;
    }
    return $Result;
  }

  /**
   * Returns true if the given user owns the given badge
   *
   * @param int $UserID
   * @param $Badge
   * @return bool
   */
  public static function has_badge($UserID, $Badge) {
    $Badges = self::get_badges($UserID);

    foreach ($Badges as $B) {
      if ($B['BadgeID'] == $Badge['BadgeID'])
        return true;
    }

    return false;
  }

  /**
   * Creates HTML for displaying a badge.
   *
   * @param $Badge
   * @param bool $Tooltip Should HTML contain a tooltip?
   * @return string HTML
   */
  public static function display_badge($Badge, $Tooltip = false) {
    $html = "";

    if (G::$Cache->get_value('badge_'.$Badge['BadgeID'])) {
      extract(G::$Cache->get_value('badge_'.$Badge['BadgeID']));
    }
    if (!isset($Icon)) {
      $QueryID = G::$DB->get_query_id();
      G::$DB->query("
        SELECT
        Icon, Name, Description
        FROM badges
        WHERE ID = ".$Badge['BadgeID']);

      if (G::$DB->has_results()) {
        list($Icon, $Name, $Description) = G::$DB->next_record();
        G::$Cache->cache_value('badge_'.$Badge['BadgeID'], array('Icon' => $Icon, 'Name' => $Name, 'Description' => $Description));
      }

      G::$DB->set_query_id($QueryID);

    }

    if (isset($Icon)) {
      if ($Tooltip) {
        $html .= "<a class='badge_icon'><img class='tooltip' title='$Name</br>$Description' src='$Icon' /></a>";
      } else {
        $html .= "<a class='badge_icon'><img title='$Name' src='$Icon' /></a>";
      }
    }

    return $html;
  }

  public static function display_badges($Badges, $Tooltip = false) {
    $html = "";
    foreach ($Badges as $Badge) {
      $html .= self::display_badge($Badge, $Tooltip);
    }
    return $html;
  }
}
?>
