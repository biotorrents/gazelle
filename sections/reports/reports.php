<?php
/************************************************************************
 ************************************************************************/
if (!check_perms('admin_reports') && !check_perms('project_team') && !check_perms('site_moderate_forums')) {
    error(404);
}

// Number of reports per page
define('REPORTS_PER_PAGE', '10');

list($Page, $Limit) = Format::page_limit(REPORTS_PER_PAGE);

include(serverRoot . '/sections/reports/array.php');

// Header
View::header('Reports', 'reports');

if (isset($_GET['id']) && $_GET['id'] && is_number($_GET['id'])) {
    $View = 'Single report';
    $Where = 'r.ID = ' . $_GET['id'];
} elseif (empty($_GET['view'])) {
    $View = 'New';
    $Where = "Status = 'New'";
} else {
    $View = $_GET['view'];
    switch ($_GET['view']) {
    case 'old':
      $Where = "Status = 'Resolved'";
      break;
    default:
      error(404);
      break;
  }
}

if (!check_perms('admin_reports')) {
    if (check_perms('project_team')) {
        $Where .= " AND Type = 'request_update'";
    }
    if (check_perms('site_moderate_forums')) {
        $Where .= " AND Type IN('comment', 'post', 'thread')";
    }
}

$Reports = $db->query("
  SELECT
    SQL_CALC_FOUND_ROWS
    r.ID,
    r.UserID,
    um.Username,
    r.ThingID,
    r.Type,
    r.ReportedTime,
    r.Reason,
    r.Status,
    r.ClaimerID,
    r.Notes,
    r.ResolverID
  FROM reports AS r
    JOIN users_main AS um ON r.UserID = um.ID
  WHERE $Where
  ORDER BY ReportedTime DESC
  LIMIT $Limit");

// Number of results (for pagination)
$db->query('SELECT FOUND_ROWS()');
list($Results) = $db->next_record();

// Done with the number of results. Move $db back to the result set for the reports
$db->set_query_id($Reports);

// Start printing stuff
?>
<div class="thin">
  <div class="header">
    <h2>Active Reports</h2>
    <div class="linkbox">
      <a href="reports.php">New</a> |
      <a href="reports.php?view=old">Old</a> |
      <a href="reports.php?action=stats">Stats</a>
    </div>
  </div>
  <div class="linkbox">
<?php
// pagination
    $Pages = Format::get_pages($Page, $Results, REPORTS_PER_PAGE, 11);
    echo $Pages;
    ?>
  </div>
<?php
  while (list($ReportID, $SnitchID, $SnitchName, $ThingID, $Short, $ReportedTime, $Reason, $Status, $ClaimerID, $Notes, $ResolverID) = $db->next_record()) {
      $Type = $Types[$Short];
      $Reference = "reports.php?id=$ReportID#report$ReportID"; ?>
    <div id="report_<?=$ReportID?>" style="margin-bottom: 1em;" class="pending_report_v1 box pad">
      <table cellpadding="5" id="report_<?=$ReportID?>">
        <tr>
          <td><strong><a href="<?=$Reference?>">Report #<?=$ReportID?></a></strong></td>
          <td>
            <strong><?=$Type['title']?></strong> was reported by <a href="user.php?id=<?=$SnitchID?>"><?=$SnitchName?></a> <?=time_diff($ReportedTime)?>
            <a href="reports.php?action=compose&amp;to=<?=$SnitchID?>&amp;reportid=<?=$ReportID?>&amp;type=<?=$Short?>&amp;thingid=<?=$ThingID?>" class="brackets">Contact</a>
          </td>
        </tr>
        <tr>
          <td class="center" colspan="2">
            <strong>
<?php              switch ($Short) {
                case 'user':
                  $db->query("
                    SELECT Username
                    FROM users_main
                    WHERE ID = $ThingID");
                  if (!$db->has_results()) {
                      echo 'No user with the reported ID found';
                  } else {
                      list($Username) = $db->next_record();
                      echo "<a href=\"user.php?id=$ThingID\">" . Text::esc($Username) . '</a>';
                  }
                  break;
                case 'request':
                case 'request_update':
                  $db->query("
                    SELECT Title
                    FROM requests
                    WHERE ID = $ThingID");
                  if (!$db->has_results()) {
                      echo 'No request with the reported ID found';
                  } else {
                      list($Name) = $db->next_record();
                      echo "<a href=\"requests.php?action=view&amp;id=$ThingID\">" . Text::esc($Name) . '</a>';
                  }
                  break;
                case 'collage':
                  $db->query("
                    SELECT Name
                    FROM collages
                    WHERE ID = $ThingID");
                  if (!$db->has_results()) {
                      echo 'No collage with the reported ID found';
                  } else {
                      list($Name) = $db->next_record();
                      echo "<a href=\"collages.php?id=$ThingID\">" . Text::esc($Name) . '</a>';
                  }
                  break;
                case 'thread':
                  $db->query("
                    SELECT Title
                    FROM forums_topics
                    WHERE ID = $ThingID");
                  if (!$db->has_results()) {
                      echo 'No forum thread with the reported ID found';
                  } else {
                      list($Title) = $db->next_record();
                      echo "<a href=\"forums.php?action=viewthread&amp;threadid=$ThingID\">" . Text::esc($Title) . '</a>';
                  }
                  break;
                case 'post':
                  if (isset($user['PostsPerPage'])) {
                      $PerPage = $user['PostsPerPage'];
                  } else {
                      $PerPage = POSTS_PER_PAGE;
                  }
                  $db->query("
                    SELECT
                      p.ID,
                      p.Body,
                      p.TopicID,
                      (
                        SELECT COUNT(p2.ID)
                        FROM forums_posts AS p2
                        WHERE p2.TopicID = p.TopicID
                          AND p2.ID <= p.ID
                      ) AS PostNum
                    FROM forums_posts AS p
                    WHERE p.ID = $ThingID");
                  if (!$db->has_results()) {
                      echo 'No forum post with the reported ID found';
                  } else {
                      list($PostID, $Body, $TopicID, $PostNum) = $db->next_record();
                      echo "<a href=\"forums.php?action=viewthread&amp;threadid=$TopicID&amp;post=$PostNum#post$PostID\">FORUM POST ID #$PostID</a>";
                  }
                  break;
                case 'comment':
                  $db->query("
                    SELECT 1
                    FROM comments
                    WHERE ID = $ThingID");
                  if (!$db->has_results()) {
                      echo 'No comment with the reported ID found';
                  } else {
                      echo "<a href=\"comments.php?action=jump&amp;postid=$ThingID\">COMMENT</a>";
                  }
                  break;
              } ?>
            </strong>
          </td>
        </tr>
        <tr>
          <td colspan="2"><?=Text::parse($Reason)?></td>
        </tr>
        <tr>
          <td colspan="2">
<?php          if ($ClaimerID == $user['ID']) { ?>
            <span id="claimed_<?=$ReportID?>">Claimed by <?=User::format_username($ClaimerID, false, false, false, false)?> <a href="#" onclick="unClaim(<?=$ReportID?>); return false;" class="brackets">Unclaim</a></span>
<?php          } elseif ($ClaimerID) { ?>
            <span id="claimed_<?=$ReportID?>">Claimed by <?=User::format_username($ClaimerID, false, false, false, false)?></span>
<?php          } else { ?>
            <a href="#" id="claim_<?=$ReportID?>" onclick="claim(<?=$ReportID?>); return false;" class="brackets">Claim</a>
<?php          } ?>
            &nbsp;&nbsp;
            <a href="#" onclick="toggleNotes(<?=$ReportID?>); return false;" class="brackets">Toggle notes</a>

            <div id="notes_div_<?=$ReportID?>" style="display: <?=empty($Notes) ? 'none' : 'block'; ?>;">
              <textarea cols="50" rows="3" id="notes_<?=$ReportID?>"><?=$Notes?></textarea>
              <br />
              <input type="submit" onclick="saveNotes(<?=$ReportID?>)" value="Save" />
            </div>
          </td>
        </tr>
<?php      if ($Status != 'Resolved') { ?>
        <tr>
          <td class="center" colspan="2">
            <form id="report_form_<?=$ReportID?>" action="">
              <input type="hidden" name="reportid" value="<?=$ReportID?>" />
              <input type="hidden" name="auth" value="<?=$user['AuthKey']?>" />
              <input type="submit" onclick="return resolve(<?=$ReportID?>, <?=(($ClaimerID == $user['ID'] || !$ClaimerID) ? 'true' : 'false')?>)" name="submit" value="Resolve" />
            </form>
          </td>
        </tr>
<?php
      } else {
          $ResolverInfo = User::user_info($ResolverID); ?>
        <tr>
          <td colspan="2">
            Resolved by <a href="users.php?id=<?=$ResolverID?>"><?=$ResolverInfo['Username']?></a>
          </td>
        </tr>
<?php
      } ?>
      </table>
    </div>
<?php
    $db->set_query_id($Reports);
  }
  ?>
  <div class="linkbox">
<?php
    echo $Pages;
    ?>
  </div>
</div>
<?php
View::footer();
?>
