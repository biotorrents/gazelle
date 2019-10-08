<?
class Slaves {
  public static function get_level($SlaveID) {
    G::$DB->query("
      SELECT u.Uploaded, u.Downloaded, u.BonusPoints, COUNT(t.UserID)
      FROM users_main AS u
      LEFT JOIN torrents AS t ON u.ID=t.UserID
      WHERE u.ID = $SlaveID");
    list($Upload, $Download, $Points, $Uploads) = G::$DB->next_record();
    return intval(((($Uploads**0.35)*1.5)+1) * max(($Upload+($Points*1000000)-$Download)/(1024**3),1));
  }
};
?>
