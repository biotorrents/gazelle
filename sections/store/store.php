<?
$UserID = $LoggedUser['ID'];
$PermID = $LoggedUser['PermissionID'];

if (!$LoggedUser['DisableNips']) {
  $PointsRate = 0.5;
  $getTorrents = $DB->query("
    SELECT COUNT(DISTINCT x.fid) AS Torrents,
           SUM(t.Size) AS Size,
           SUM(xs.seedtime) AS Seedtime,
           SUM(t.Seeders) AS Seeders
    FROM users_main AS um
    LEFT JOIN users_info AS i on um.ID = i.UserID
    LEFT JOIN xbt_files_users AS x ON um.ID=x.uid
    LEFT JOIN torrents AS t ON t.ID=x.fid
    LEFT JOIN xbt_snatched AS xs ON x.uid=xs.uid AND x.fid=xs.fid
    WHERE
      um.ID = $UserID
      AND um.Enabled = '1'
      AND x.active = 1
      AND x.completed = 0
      AND x.Remaining = 0
    GROUP BY um.ID");
  if ($DB->has_results()) {
    list($NumTorr, $TSize, $TTime, $TSeeds) = $DB->next_record();
    $PointsRate += (0.55*($NumTorr * (sqrt(($TSize/$NumTorr)/1073741824) * pow(1.5,($TTime/$NumTorr)/(24*365))))) / (max(1, sqrt(($TSeeds/$NumTorr)+4)/3));
  }
  $PointsRate = intval($PointsRate**0.95);
  $PointsPerHour = number_format($PointsRate) . " nips/hour";
  $PointsPerDay = number_format($PointsRate*24) . " nips/day";
} else {
  $PointsPerHour = "0 nips/hour";
  $PointsPerDay = "Nips disabled";
}

//Include the header
View::show_header('Store');
?>
<div class="thin">
  <h2 id="general">Store</h2>
  <div class="box pad">
    <h3 id="lists" style="float: left;">You have <?=number_format($LoggedUser['BonusPoints'])?> nips to spend</h3>
    <h3 id="lists" style="float: right;">You're making <?=$PointsPerHour?> (<?=$PointsPerDay?>)</h3>
    <table width="100%" class="store_table">
      <tr class="colhead">
        <td style="width: 100px;">Item</td>
        <td style="width: 100px;">Cost</td>
        <td style="width: 400px;">Description</td>
      </tr>
      <tr class="row">
        <td class="nobr">
          <a href="store.php?item=upload_1GB">1GiB Upload</a>
        </td>
        <td class="nobr">
          1,000 nips
        </td>
        <td class="nobr">
          Purchase 1GiB of upload
        </td>
      </tr>
      <tr class="row">
        <td class="nobr">
          <a href="store.php?item=upload_10GB">10GiB Upload</a>
        </td>
        <td class="nobr">
          10,000 nips
        </td>
        <td class="nobr">
          Purchase 10GiB of upload
        </td>
      </tr>
      <tr class="row">
        <td class="nobr">
          <a href="store.php?item=upload_100GB">100GiB Upload</a>
        </td>
        <td class="nobr">
          100,000 nips
        </td>
        <td class="nobr">
          Purchase 100GiB of upload
        </td>
      </tr>
      <tr class="row">
        <td class="nobr">
          <a href="store.php?item=upload_1000GB">1,000GiB Upload</a>
        </td>
        <td class="nobr">
          1,000,000 nips
        </td>
        <td class="nobr">
          Purchase 1,000GiB of upload
        </td>
      </tr>
      <tr class="row">
        <td class="nobr">
          <a href="store.php?item=1k_points">1,000 nips</a>
        </td>
        <td class="nobr">
          1GiB Upload
        </td>
        <td class="nobr">
          Purchase 1,000 nips
        </td>
      </tr>
      <tr class="row">
        <td class="nobr">
          <a href="store.php?item=10k_points">10,000 nips</a>
        </td>
        <td class="nobr">
          10GiB Upload
        </td>
        <td class="nobr">
          Purchase 10,000 nips
        </td>
      </tr>
      <tr class="row">
        <td class="nobr">
          <a href="store.php?item=100k_points">100,000 nips</a>
        </td>
        <td class="nobr">
          100GiB Upload
        </td>
        <td class="nobr">
          Purchase 100,000 nips
        </td>
      </tr>
      <tr class="row">
        <td class="nobr">
          <a href="store.php?item=1m_points">1,000,000 nips</a>
        </td>
        <td class="nobr">
          1,000GiB Upload
        </td>
        <td class="nobr">
          Purchase 1,000,000 nips
        </td>
      </tr>
      <tr class="row">
        <td class="nobr">
          <a href="store.php?item=freeleechize">Freeleechize</a>
        </td>
        <td class="nobr">
          20,000 nips
        </td>
        <td class="nobr">
          Make a torrent freeleech (to everyone) for 24 hours
        </td>
      </tr>
      <tr class="row">
        <td class="nobr">
          <a href="store.php?item=title">Custom Title</a>
        </td>
        <td class="nobr">
          50,000 nips
        </td>
        <td class="nobr">
          Purchase a custom title
        </td>
      </tr>
      <tr class="row">
        <td class="nobr">
          <a href="store.php?item=invite">Invite</a>
        </td>
        <td class="nobr">
          100,000 nips
        </td>
        <td class="nobr">
          Purchase an invite for your friend
        </td>
      </tr>
<? switch ($PermID) {
  case USER:
    $To = array('Modest Mounds', '1,000');
    break;
  case MEMBER:
    $To = array('Well Endowed', '10,000');
    break;
  case POWER:
    $To = array('Bombshell', '30,000');
    break;
  case ELITE:
    $To = array('Top Heavy', '60,000');
    break;
  case TORRENT_MASTER:
    $To = array('Titty Monster', '100,000');
    break;
}
if (isset($To)) { ?>
      <tr class="row">
        <td class="nobr">
          <a href="store.php?item=promotion">Promotion</a>
        </td>
        <td class="nobr">
        <?=$To[1]?> nips
        </td>
        <td class="nobr">
          Get promoted to <?=$To[0]?>
        </td>
      </tr>
<? } ?>
      <tr class="row">
        <td class="nobr">
          <a href="store.php?item=become_admin">Become Admin</a>
        </td>
        <td class="nobr">
          4,294,967,296 nips
        </td>
        <td class="nobr">
          Have your class changed to Sysop
        </td>
      </tr>
      <tr class="row">
        <td class="nobr">
          <a href="store.php?item=freeleechpool">Freeleech Pool</a>
        </td>
        <td class="nobr">
        </td>
        <td class="nobr">
          Make a donation to the Freeleech Pool
        </td>
      </tr>
    </table>
    <br />
    <h4>Badges</h4>
    <table width="100%" class="store_table">
      <tr class="colhead">
        <td style="width: 100px;">Badge</td>
        <td style="width: 100px;">Cost</td>
        <!--<td style="width: 400px;">Description</td>-->
      </tr>
<?
$DB->query("
  SELECT ID AS BadgeID, Name, Description
  FROM badges
  WHERE ID IN (100, 101, 102, 103, 104, 105, 106, 107)");

if ($DB->has_results()) {
  $Badges = $DB->to_array();
  foreach ($Badges as $ID => $Badge) {
?>
      <tr class="row">
<?
    if (($ID == 0 || Badges::has_badge($LoggedUser['ID'], $Badges[$ID-1])) && !Badges::has_badge($LoggedUser['ID'], $Badge))
      $BadgeText = '<a href="store.php?item=badge&badge='.$Badge['BadgeID'].'">'.$Badge['Name'].'</a>';
    else
      $BadgeText = $Badge['Name']

?>
        <td class="nobr"><?=Badges::display_badge($Badge)?><span class="badge_name" style="margin-left: 10px;"><?=$BadgeText?></span></td>
        <td class="nobr"><?=$Badge['Description']?></td>
      </tr>
<?
  }
}
?>
    </table>
  </div>
</div>
<? View::show_footer(); ?>
