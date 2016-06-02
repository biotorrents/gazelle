<?
//------------- Hide old requests ---------------------------------------//

$DB->query("
  UPDATE requests
  SET Visible = 0
  WHERE TimeFilled < (NOW() - INTERVAL 7 DAY)
    AND TimeFilled != '0000-00-00 00:00:00'");
?>
