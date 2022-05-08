<?php
declare(strict_types=1);

/**
 * Flight router
 * @see https://flightphp.com/learn
 */

# endpoints go here

# start the router
#Flight::start();


/** LEGACY ROUTES */


enforce_login();
if (!defined('LOG_ENTRIES_PER_PAGE')) {
    define('LOG_ENTRIES_PER_PAGE', 100);
}

View::header("Site log");
include SERVER_ROOT.'/sections/log/sphinx.php';
?>

<div>
    <div class="header">
        <h2>Site log</h2>
    </div>

    <div class="box pad">
        <form class="search_form" name="log" action="" method="get">
            <table cellpadding="6" cellspacing="1" border="0" class="layout" width="100%">
                <tr>
                    <td class="label"><strong>Search for:</strong></td>
                    <td>
                        <input type="search" name="search" size="60" <?=(!empty($_GET['search']) ? ' value="'.esc($_GET['search']).'"' : '')?>
                        />
                        &nbsp;
                        <input type="submit" class="button-primary" value="Search log" />
                    </td>
                </tr>
            </table>
        </form>
    </div>

    <?php
  if ($TotalMatches > LOG_ENTRIES_PER_PAGE) { ?>
    <div class="linkbox">
        <?php
  $Pages = Format::get_pages($Page, $TotalMatches, LOG_ENTRIES_PER_PAGE, 9);
  echo $Pages;
  ?>
    </div>
    <?php
}
?>

    <div class="box">
        <table cellpadding="6" cellspacing="1" border="0" class="log_table" id="log_table" width="100%">
            <tr class="colhead">
                <td style="width: 180px;">
                    <strong>Time</strong>
                </td>

                <td>
                    <strong>Message</strong>
                </td>
            </tr>

            <?php if ($QueryStatus) { ?>
            <tr class="nobr">
                <td colspan="2">Search request failed (<?=$QueryError?>).
                </td>
            </tr>
            <?php } elseif (!$db->has_results()) { ?>
            <tr class="nobr">
                <td colspan="2">Nothing found!</td>
            </tr>
            <?php
  }
  
$Usernames = [];
while (list($ID, $Message, $LogTime) = $db->next_record()) {
    $MessageParts = explode(' ', $Message);
    $Message = '';
    $Color = $Colon = false;

    for ($i = 0, $PartCount = sizeof($MessageParts); $i < $PartCount; $i++) {
        if (strpos($MessageParts[$i], 'https://'.SITE_DOMAIN) === 0) {
            $Offset = strlen('https://'.SITE_DOMAIN.'/');
            $MessageParts[$i] = '<a href="'.substr($MessageParts[$i], $Offset).'">'.substr($MessageParts[$i], $Offset).'</a>';
        }

        switch ($MessageParts[$i]) {
        case 'Torrent':
        case 'torrent':
            $TorrentID = $MessageParts[$i + 1];
            if (is_numeric($TorrentID)) {
                $Message = $Message.' '.$MessageParts[$i]." <a href='torrents.php?torrentid=$TorrentID'>$TorrentID</a>";
                $i++;
            } else {
                $Message = $Message.' '.$MessageParts[$i];
            }
            break;

      case 'Request':
      case 'request':
          $RequestID = $MessageParts[$i + 1];
          if (is_numeric($RequestID)) {
              $Message = $Message.' '.$MessageParts[$i]." <a href='requests.php?action=view&amp;id=$RequestID'>$RequestID</a>";
              $i++;
          } else {
              $Message = $Message.' '.$MessageParts[$i];
          }
          break;

      case 'Artist':
      case 'artist':
          $ArtistID = $MessageParts[$i + 1];
          if (is_numeric($ArtistID)) {
              $Message = $Message.' '.$MessageParts[$i]." <a href='artist.php?id=$ArtistID'>$ArtistID</a>";
              $i++;
          } else {
              $Message = $Message.' '.$MessageParts[$i];
          }
          break;

      case 'group':
      case 'Group':
          $GroupID = $MessageParts[$i + 1];
          if (is_numeric($GroupID)) {
              $Message = $Message.' '.$MessageParts[$i]." <a href='torrents.php?id=$GroupID'>$GroupID</a>";
          } else {
              $Message = $Message.' '.$MessageParts[$i];
          }
          $i++;
          break;

      case 'By':
      case 'by':
          $UserID = 0;
          $User = '';
          $URL = '';
          if ($MessageParts[$i + 1] === 'user') {
              $i++;
              if (is_numeric($MessageParts[$i + 1])) {
                  $UserID = $MessageParts[++$i];
              }
              $URL = "user $UserID (<a href='user.php?id=$UserID'>".substr($MessageParts[++$i], 1, -1).'</a>)';
          } elseif (in_array($MessageParts[$i - 1], ['deleted', 'uploaded', 'edited', 'created', 'recovered'])) {
              $User = $MessageParts[++$i];
              if (substr($User, -1) === ':') {
                  $User = substr($User, 0, -1);
                  $Colon = true;
              }

              if (!isset($Usernames[$User])) {
                  $db->query(
                      "
                  SELECT
                    `ID`
                  FROM
                    `users_main`
                  WHERE
                    `Username` = ?
                  ",
                      $User
                  );

                  list($UserID) = $db->next_record();
                  $Usernames[$User] = $UserID ? $UserID : '';
              } else {
                  $UserID = $Usernames[$User];
              }

              $URL = $Usernames[$User] ? "<a href='user.php?id=$UserID'>$User</a>".($Colon ? ':' : '') : $User;
              if (in_array($MessageParts[$i - 2], ['uploaded', 'edited'])) {
                  $db->query(
                      "
                  SELECT
                    `UserID`,
                    `Anonymous`
                  FROM
                    `torrents`
                  WHERE
                    `ID` = ?
                  ",
                      $MessageParts[1]
                  );

                  if ($db->has_results()) {
                      list($UploaderID, $AnonTorrent) = $db->next_record();
                      if ($AnonTorrent && $UploaderID === $UserID) {
                          $URL = '<em>Anonymous</em>';
                      }
                  }
              }
              $db->set_query_id($Log);
          }
          $Message = "$Message by $URL";
          break;

      case 'Uploaded':
      case 'uploaded':
          if ($Color === false) {
              $Color = 'green';
          }
          $Message = $Message.' '.$MessageParts[$i];
          break;

      case 'Deleted':
      case 'deleted':
          if ($Color === false || $Color === 'green') {
              $Color = 'red';
          }
          $Message = $Message.' '.$MessageParts[$i];
          break;

      case 'Edited':
      case 'edited':
          if ($Color === false) {
              $Color = 'blue';
          }
          $Message = $Message.' '.$MessageParts[$i];
          break;

      case 'Un-filled':
      case 'un-filled':
          if ($Color === false) {
              $Color = '';
          }
          $Message = $Message.' '.$MessageParts[$i];
          break;

      case 'Marked':
      case 'marked':
          if ($i === 1) {
              $User = $MessageParts[$i - 1];
              if (!isset($Usernames[$User])) {
                  $db->query("
                  SELECT
                    `ID`
                  FROM
                    `users_main`
                  WHERE
                    `Username` = _utf8 '".db_string($User)."' COLLATE utf8_bin
                  ");

                  list($UserID) = $db->next_record();
                  $Usernames[$User] = $UserID ? $UserID : '';
                  $db->set_query_id($Log);
              } else {
                  $UserID = $Usernames[$User];
              }

              $URL = $Usernames[$User] ? "<a href='user.php?id=$UserID'>$User</a>" : $User;
              $Message = $URL.' '.$MessageParts[$i];
          } else {
              $Message = $Message.' '.$MessageParts[$i];
          }
          break;

      case 'Collage':
      case 'collage':
          $CollageID = $MessageParts[$i + 1];
          if (is_numeric($CollageID)) {
              $Message = $Message.' '.$MessageParts[$i]." <a href='collages.php?id=$CollageID'>$CollageID</a>";
              $i++;
          } else {
              $Message = $Message.' '.$MessageParts[$i];
          }
          break;

      default:
        $Message = $Message.' '.$MessageParts[$i];
    }
    } ?>

            <tr class="row" id="log_<?=$ID?>">
                <td class="nobr">
                    <?=time_diff($LogTime)?>
                </td>

                <td>
                    <span<?php if ($Color) { ?> style="color: <?=$Color?>;"
                        <?php } ?>><?=$Message?></span>
                </td>
            </tr>
            <?php
}
?>
        </table>
    </div>

    <?php if (isset($Pages)) { ?>
    <div class="linkbox">
        <?=$Pages?>
    </div>
    <?php } ?>
</div>
<?php
View::footer();
