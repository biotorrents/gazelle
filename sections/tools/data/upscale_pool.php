<?php
#declare(strict_types=1);

if (!check_perms('site_view_flow')) {
    error(403);
}

View::header('Upscale Pool');
define('USERS_PER_PAGE', 50);
list($Page, $Limit) = Format::page_limit(USERS_PER_PAGE);

$RS = $db->query("
  SELECT
    SQL_CALC_FOUND_ROWS
    m.ID,
    m.Username,
    m.Uploaded,
    m.Downloaded,
    m.PermissionID,
    m.Enabled,
    i.Donor,
    i.Warned,
    i.JoinDate,
    i.RatioWatchEnds,
    i.RatioWatchDownload,
    m.RequiredRatio
  FROM users_main AS m
    LEFT JOIN users_info AS i ON i.UserID = m.ID
  WHERE i.RatioWatchEnds IS NOT NULL
    AND m.Enabled = '1'
  ORDER BY i.RatioWatchEnds ASC
  LIMIT $Limit");

$db->query('SELECT FOUND_ROWS()');
list($Results) = $db->next_record();

$db->query("
  SELECT COUNT(UserID)
  FROM users_info
  WHERE BanDate IS NOT NULL
    AND BanReason = '2'");

list($TotalDisabled) = $db->next_record();
$db->set_query_id($RS);
?>

<div class="header">
  <h2>Upscale Pool</h2>
</div>

<?php
if ($db->has_results()) {
    ?>
<div class="box pad">
  <p>There are currently <?=Text::float($Results)?> enabled users
    on Ratio Watch and <?=Text::float($TotalDisabled)?> already
    disabled.</p>
</div>

<div class="linkbox">
  <?php
  $Pages = Format::get_pages($Page, $Results, USERS_PER_PAGE, 11);
    echo $Pages; ?>
</div>

<table width="100%">
  <tr class="colhead">
    <td>User</td>
    <td class="number_column">Uploaded</td>
    <td class="number_column">Downloaded</td>
    <td class="number_column">Ratio</td>
    <td class="number_column">Required Ratio</td>
    <td class="number_column">Deficit</td>
    <td class="number_column">Gamble</td>
    <td>Registration Date</td>
    <td>Ratio Watch Ended/Ends</td>
    <td>Lifespan</td>
  </tr>

  <?php
  while (list($UserID, $Username, $Uploaded, $Downloaded, $PermissionID, $Enabled, $Donor, $Warned, $Joined, $RatioWatchEnds, $RatioWatchDownload, $RequiredRatio) = $db->next_record()) {
      ?>
  <tr class="row">
    <td>
      <?=User::format_username($UserID, true, true, true, true)?>
    </td>

    <td class="number_column">
      <?=Format::get_size($Uploaded)?>
    </td>

    <td class="number_column">
      <?=Format::get_size($Downloaded)?>
    </td>

    <td class="number_column">
      <?=Format::get_ratio_html($Uploaded, $Downloaded)?>
    </td>

    <td class="number_column">
      <?=Text::float($RequiredRatio, 2)?>
    </td>

    <td class="number_column">
      <?php if (($Downloaded * $RequiredRatio) > $Uploaded) {
          echo Format::get_size(($Downloaded * $RequiredRatio) - $Uploaded);
      } ?>
    </td>

    <td class="number_column">
      <?=Format::get_size($Downloaded - $RatioWatchDownload)?>
    </td>

    <td>
      <?=time_diff($Joined, 2)?>
    </td>

    <td>
      <?=time_diff($RatioWatchEnds)?>
    </td>

    <td>
      <?//time_diff(strtotime($Joined), strtotime($RatioWatchEnds))?>
    </td>
  </tr>
  <?php
  } ?>
</table>

<div class="linkbox">
  <?= $Pages; ?>
</div>
<?php
} else { ?>
<h2>There are currently no users on ratio watch.</h2>
<?php
}
View::footer();
