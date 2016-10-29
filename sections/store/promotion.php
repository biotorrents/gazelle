<?
$UserID = $LoggedUser['ID'];
$GiB = 1024*1024*1024;

$Classes = array(
  MEMBER => array(
    'Name'        => 'Modest Mounds', // name
    'Price'        => 1000, // cost in nips
    'MinUpload'    => 10, // minimum upload in GiB
    'MinDownload'  => 1, // minimum download in GiB
    'MinUploads'  => 0, // minimum upload count
    'NonSmall'     => 0, // must have at least this many non-doujin torrents or doujins with more than 50 pages
    'MinRatio'    => 0.7, // minimum ratio
    'TorUnique'   => false // do the uploads have to be unique groups?
  ),
  POWER => array(
    'Name'        => 'Well Endowed',
    'Price'        => 10000,
    'MinUpload'    => 100,
    'MinDownload'  => 25,
    'MinUploads'  => 10,
    'NonSmall'     => 2,
    'MinRatio'    => 1.1,
    'TorUnique'    => false
  ),
  ELITE => array(
    'Name'        => 'Bombshell',
    'Price'        => 30000,
    'MinUpload'    => 500,
    'MinDownload'  => 100,
    'MinUploads'  => 50,
    'NonSmall'     => 12,
    'MinRatio'    => 1.2,
    'TorUnique'    => false
  ),
  TORRENT_MASTER => array(
    'Name'        => 'Top Heavy',
    'Price'        => 60000,
    'MinUpload'    => 1024,
    'MinDownload'  => 250,
    'MinUploads'  => 250,
    'NonSmall'     => 60,
    'MinRatio'    => 1.3,
    'TorUnique'    => false
  ),
  POWER_TM => array(
    'Name'        => 'Titty Monster',
    'Price'        => 100000,
    'MinUpload'    => 1.5*1024,
    'MinDownload'  => 500,
    'MinUploads'  => 500,
    'NonSmall'     => 160,
    'MinRatio'    => 1.5,
    'TorUnique'    => true
  )
);

$To = -1;

$DB->query("
  SELECT PermissionID, BonusPoints, Warned, Uploaded, Downloaded, (Uploaded / Downloaded) AS Ratio, Enabled, COUNT(torrents.ID) AS Uploads, COUNT(DISTINCT torrents.GroupID) AS Groups
  FROM users_main
    JOIN users_info ON users_main.ID = users_info.UserID
    JOIN torrents ON torrents.UserID = users_main.ID
  WHERE users_main.ID = $UserID");

if ($DB->has_results()) {
  list($PermID, $BP, $Warned, $Upload, $Download, $Ratio, $Enabled, $Uploads, $Groups) = $DB->next_record();

  switch ($PermID) {
    case USER:
      $To = MEMBER;
      break;
    case MEMBER:
      $To = POWER;
      break;
    case POWER:
      $To = ELITE;
      break;
    case ELITE:
      $To = TORRENT_MASTER;
      break;
    case TORRENT_MASTER:
      $To = POWER_TM;
      break;
    default:
      $To = -1;
  }

  if ($To == -1) {
    $Err[] = "Your user class is not eligible for promotions";
  } elseif ($Enabled != 1) {
    $Err[] = "This account is disabled, how did you get here?";
  } else {

    if ($Classes[$To]['NonSmall'] > 0) {
      //
      $DB->query("
        SELECT COUNT(torrents.ID)
        FROM torrents
          JOIN torrents_group ON torrents.GroupID = torrents_group.ID
        WHERE (torrents_group.CategoryID != 3
          OR (torrents_group.CategoryID = 3 AND torrents_group.Pages >= 50))
          AND torrents.UserID = $UserID");

      if ($DB->has_results()) {
        list($NonSmall) = $DB->next_record();

        if ($NonSmall < $Classes[$To]['NonSmall']) {
          $Err[] = "You do not have enough large uploads.";
        }
      } else {
        $Err[] = "You do not have enough large uploads.";
      }

    }

    if ($Warned != "0000-00-00 00:00:00") {
      $Err[] = "You cannot be promoted while warned";
    }

    if ($LoggedUser['DisablePromotion']) {
      $Err[] = "You have been banned from purchasing promotions";
    }

    if ($BP < $Classes[$To]['Price']) {
      $Err[] = "Not enough points";
    }

    if ($Ratio < $Classes[$To]['MinRatio']) {
      $Err[] = "Your ratio is too low to be promoted. The minimum ratio required for this promotion is ".$Classes[$To]['MinRatio'].".";
    }

    if ($Upload < $Classes[$To]['MinUpload']*$GiB) {
      if ($Classes[$To]['MinUpload'] >= 1024) {
        $Amount = $Classes[$To]['MinUpload']/1024;
        $Unit = 'TiB';
      } else {
        $Amount = $Classes[$To]['MinUpload'];
        $Unit = 'GiB';
      }
      $Err[] = "You have not uploaded enough to be promoted. The minimum uploaded amount for this promotion is ".$Amount."".$Unit.".";
    }

    if ($Download < $Classes[$To]['MinDownload']*$GiB) {
      $Err[] = "You have not downloaded enough to be promoted. The minimum downloaded amount for this promotion is ".$Classes[$To]['MinDownload']."GiB.";
    }

    if ($Uploads < $Classes[$To]['MinUploads']) {
      $Err[] = "You have not uploaded enough torrents to be promoted. The minimum number of uploaded torrents for this promotion is ".$Classes[$To]['MinUploads'].".";
    }

    if ($Classes[$To]['UniqueTor'] && $Groups < $Classes[$To]['MinUploads']) {
      $Err[] = "You have not uploaded to enough unique torrent groups to be promoted. The minimum number of unique groups for this promotion is ".$Classes[$To]['MinUploads'].".";
    }

    if (!isset($Err)) {
      $DB->query("
        UPDATE users_main
        SET
          BonusPoints = BonusPoints - ".$Classes[$To]['Price'].",
          PermissionID = $To
        WHERE ID = $UserID");
      $DB->query("
        UPDATE users_info
        SET AdminComment = CONCAT('".sqltime()." - Class changed to ".Users::make_class_string($To)." via store purchase\n\n', AdminComment)
        WHERE UserID = $UserID");
      $Cache->delete_value("user_info_$UserID");
      $Cache->delete_value("user_info_heavy_$UserID");
    }
  }
}

View::show_header('Store'); ?>
<div class="thin">
  <h2 id="general">Purchase <?=isset($Err)?"Failed":"Successful"?></h2>
  <div class="box pad" style="padding: 10px 10px 10px 20px;">
    <p><?=isset($Err)?"Error: ".implode("<br />Error: ", $Err):"You have been promoted to ".$Classes[$To]['Name']."!"?></p>
    <p><a href="/store.php">Back to Store</a></p>
  </div>
</div>
<? View::show_footer(); ?>
