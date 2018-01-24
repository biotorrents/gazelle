<?
class Badges {
  /**
   * Given a UserID, returns that user's badges
   *
   * @param int $UserID
   * @return array of BadgeIDs
   */
  public static function get_badges($UserID) {
    return Users::user_info($UserID)['Badges'];
  }

  /**
   * Awards UserID the given BadgeID
   *
   * @param int $UserID
   * @param int $BadgeID
   * @return bool success?
   */
  public static function award_badge($UserID, $BadgeID) {
    if (self::has_badge($UserID, $BadgeID)) {
      return false;
    } else {
      $QueryID = G::$DB->get_query_id();
      G::$DB->query("
        INSERT INTO users_badges
          (UserID, BadgeID)
        VALUES
          ($UserID, $BadgeID)");
      G::$DB->set_query_id($QueryID);

      G::$Cache->delete_value('user_info_'.$UserID);

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
    $Result = [];

    $Badges = self::get_badges($UserID);

    foreach ($Badges as $Badge => $Displayed) {
      if ($Displayed)
        $Result[] = $Badge;
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
  public static function has_badge($UserID, $BadgeID) {
    $Badges = self::get_badges($UserID);

    return array_key_exists($BadgeID, $Badges);
  }

  /**
   * Creates HTML for displaying a badge.
   *
   * @param int $BadgeID
   * @param bool $Tooltip Should HTML contain a tooltip?
   * @return string HTML
   */
  public static function display_badge($BadgeID, $Tooltip = false) {
    $html = "";

    if (($Badges = G::$Cache->get_value('badges')) && array_key_exists($BadgeID, $Badges)) {
      extract($Badges[$BadgeID]);
    } else {
      self::update_badge_cache();
      if (($Badges = G::$Cache->get_value('badges')) && array_key_exists($BadgeID, $Badges)) {
        extract($Badges[$BadgeID]);
      } else {
        global $Debug;
        $Debug->analysis('Invalid BadgeID ' . $BadgeID . ' requested.');
      }
    }

    if ($Tooltip) {
      $html .= '<a class="badge_icon"><img class="tooltip" alt="'.$Name.'" title="'.$Name.'</br>'.$Description.'" src="'.$Icon.'" /></a>';
    } else {
      $html .= '<a class="badge_icon"><img alt="'.$Name.'" title="'.$Name.'" src="'.$Icon.'" /></a>';
    }

    return $html;
  }

  public static function display_badges($BadgeIDs, $Tooltip = false) {
    $html = "";
    foreach ($BadgeIDs as $BadgeID) {
      $html .= self::display_badge($BadgeID, $Tooltip);
    }
    return $html;
  }

  private static function update_badge_cache() {
      $QueryID = G::$DB->get_query_id();

      G::$DB->query("
        SELECT
        ID, Icon, Name, Description
        FROM badges");

      $badges = [];
      if (G::$DB->has_results()) {
        while(list($id, $icon, $name, $description) = G::$DB->next_record()) {
          $badges[$id] = array('Icon' => $icon, 'Name' => $name, 'Description' => $Description);
        }
        G::$Cache->cache_value('badges', $badges);
      }

      G::$DB->set_query_id($QueryID);
  }

  public static function get_all_badges() {
    if (($Badges = G::$Cache->get_value('badges'))) {
      return $Badges;
    } else {
      self::update_badge_cache();
      return G::$Cache->get_value('badges');
    }
  }
}
?>
