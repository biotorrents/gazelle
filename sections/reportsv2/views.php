<?php
/*
 * This page is to outline all of the views built into reports v2.
 * It's used as the main page as it also lists the current reports by type
 * and the current in-progress reports by staff member.
 * All the different views are self explanatory by their names.
 */
if (!check_perms('admin_reports')) {
  error(403);
}

View::header('Reports V2', 'reportsv2');


//Grab owner's ID, just for examples
$db->prepared_query("
  SELECT ID, Username
  FROM users_main
  ORDER BY ID ASC
  LIMIT 1");
list($OwnerID, $Owner) = $db->next_record();
$Owner = esc($Owner);

?>
<div class="header">
  <h2>Reports v2 Information</h2>
<?php include('header.php'); ?>
</div>
<div class="float_clear">
  <div class="two_columns pad">
<?php
$db->prepared_query("
  SELECT
    um.ID,
    um.Username,
    COUNT(r.ID) AS Reports
  FROM reportsv2 AS r
    JOIN users_main AS um ON um.ID = r.ResolverID
  WHERE r.LastChangeTime > NOW() - INTERVAL 24 HOUR
  GROUP BY r.ResolverID
  ORDER BY Reports DESC");
$Results = $db->to_array();
?>
    <h3>Reports resolved in the last 24 hours</h3>
    <table class="box border">
      <tr class="colhead">
        <td class="colhead_dark">Username</td>
        <td class="colhead_dark number_column">Reports</td>
      </tr>
<?php
foreach ($Results as $Result) {
  list($UserID, $Username, $Reports) = $Result;
  if ($Username == $user['Username']) {
    $RowClass = ' class="highlight"';
  } else {
    $RowClass = '';
  }
?>
      <tr<?=$RowClass?>>
        <td><a href="reportsv2.php?view=resolver&amp;id=<?=$UserID?>"><?=$Username?></a></td>
        <td class="number_column"><?=number_format($Reports)?></td>
      </tr>
<?php
}
?>
    </table>
<?php
$db->prepared_query("
  SELECT
    um.ID,
    um.Username,
    COUNT(r.ID) AS Reports
  FROM reportsv2 AS r
    JOIN users_main AS um ON um.ID = r.ResolverID
  WHERE r.LastChangeTime > NOW() - INTERVAL 1 WEEK
  GROUP BY r.ResolverID
  ORDER BY Reports DESC");
$Results = $db->to_array();
?>
    <h3>Reports resolved in the last week</h3>
    <table class="box border">
      <tr class="colhead">
        <td class="colhead_dark">Username</td>
        <td class="colhead_dark number_column">Reports</td>
      </tr>
<?php
foreach ($Results as $Result) {
  list($UserID, $Username, $Reports) = $Result;
  if ($Username == $user['Username']) {
    $RowClass = ' class="highlight"';
  } else {
    $RowClass = '';
  }
?>
      <tr<?=$RowClass?>>
        <td><a href="reportsv2.php?view=resolver&amp;id=<?=$UserID?>"><?=$Username?></a></td>
        <td class="number_column"><?=number_format($Reports)?></td>
      </tr>
<?php
}
?>
    </table>
<?php
$db->prepared_query("
  SELECT
    um.ID,
    um.Username,
    COUNT(r.ID) AS Reports
  FROM reportsv2 AS r
    JOIN users_main AS um ON um.ID = r.ResolverID
  WHERE r.LastChangeTime > NOW() - INTERVAL 1 MONTH
  GROUP BY r.ResolverID
  ORDER BY Reports DESC");
$Results = $db->to_array();
?>
    <h3>Reports resolved in the last month</h3>
    <table class="box border">
      <tr class="colhead">
        <td class="colhead_dark">Username</td>
        <td class="colhead_dark number_column">Reports</td>
      </tr>
<?php
foreach ($Results as $Result) {
  list($UserID, $Username, $Reports) = $Result;
  if ($Username == $user['Username']) {
    $RowClass = ' class="highlight"';
  } else {
    $RowClass = '';
  }
?>
      <tr<?=$RowClass?>>
        <td><a href="reportsv2.php?view=resolver&amp;id=<?=$UserID?>"><?=$Username?></a></td>
        <td class="number_column"><?=number_format($Reports)?></td>
      </tr>
<?php
}
?>
    </table>
<?php
$db->prepared_query("
  SELECT
    um.ID,
    um.Username,
    COUNT(r.ID) AS Reports
  FROM reportsv2 AS r
    JOIN users_main AS um ON um.ID = r.ResolverID
  GROUP BY r.ResolverID
  ORDER BY Reports DESC");
$Results = $db->to_array();
?>
    <h3>Reports resolved ever</h3>
    <table class="box border">
      <tr class="colhead">
        <td class="colhead_dark">Username</td>
        <td class="colhead_dark number_column">Reports</td>
      </tr>
<?php
foreach ($Results as $Result) {
  list($UserID, $Username, $Reports) = $Result;
  if ($Username == $user['Username']) {
    $RowClass = ' class="highlight"';
  } else {
    $RowClass = '';
  }
?>
      <tr<?=$RowClass?>>
        <td><a href="reportsv2.php?view=resolver&amp;id=<?=$UserID?>"><?=$Username?></a></td>
        <td class="number_column"><?=number_format($Reports)?></td>
      </tr>
<?php
}
?>
    </table>
    <h3>Different view modes by person</h3>
    <div class="box pad">
      <strong>By ID of torrent reported:</strong>
      <ul>
        <li>
          Reports of torrents with ID = 1
        </li>
        <li>
          <a href="reportsv2.php?view=torrent&amp;id=1"><?=site_url()?>reportsv2.php?view=torrent&amp;id=1</a>
        </li>
      </ul>
      <strong>By group ID of torrent reported:</strong>
      <ul>
        <li>
          Reports of torrents within the group with ID = 1
        </li>
        <li>
          <a href="reportsv2.php?view=group&amp;id=1"><?=site_url()?>reportsv2.php?view=group&amp;id=1</a>
        </li>
      </ul>
      <strong>By report ID:</strong>
      <ul>
        <li>
          The report with ID = 1
        </li>
        <li>
          <a href="reportsv2.php?view=report&amp;id=1"><?=site_url()?>reportsv2.php?view=report&amp;id=1</a>
        </li>
      </ul>
      <strong>By reporter ID:</strong>
      <ul>
        <li>
          Reports created by <?=$Owner?>
        </li>
        <li>
          <a href="reportsv2.php?view=reporter&amp;id=<?=$OwnerID?>"><?=site_url()?>reportsv2.php?view=reporter&amp;id=<?=$OwnerID?></a>
        </li>
      </ul>
      <strong>By uploader ID:</strong>
      <ul>
        <li>
          Reports for torrents uploaded by <?=$Owner?>
        </li>
        <li>
          <a href="reportsv2.php?view=uploader&amp;id=<?=$OwnerID?>"><?=site_url()?>reportsv2.php?view=uploader&amp;id=<?=$OwnerID?></a>
        </li>
      </ul>
      <strong>By resolver ID:</strong>
      <ul>
        <li>
          Reports for torrents resolved by <?=$Owner?>
        </li>
        <li>
          <a href="reportsv2.php?view=resolver&amp;id=<?=$OwnerID?>"><?=site_url()?>reportsv2.php?view=resolver&amp;id=<?=$OwnerID?></a>
        </li>
      </ul>
      <strong>For browsing anything more complicated than these, use the search feature.</strong>
    </div>
  </div>
  <div class="two_columns pad">
<?php
  $db->prepared_query("
    SELECT
      r.ResolverID,
      um.Username,
      COUNT(r.ID) AS Count
    FROM reportsv2 AS r
      LEFT JOIN users_main AS um ON r.ResolverID = um.ID
    WHERE r.Status = 'InProgress'
    GROUP BY r.ResolverID");

  $Staff = $db->to_array();
?>
    <h3>Currently assigned reports by staff member</h3>
    <table class="box border">
      <tr class="colhead">
        <td class="colhead_dark">Staff Member</td>
        <td class="colhead_dark number_column">Current Count</td>
      </tr>
<?php
  foreach ($Staff as $Array) {
    if ($Array['Username'] == $user['Username']) {
      $RowClass = ' class="highlight"';
    } else {
      $RowClass = '';
    }
?>
      <tr<?=$RowClass?>>
        <td>
          <a href="reportsv2.php?view=staff&amp;id=<?=$Array['ResolverID']?>"><?=esc($Array['Username'])?>'s reports</a>
        </td>
        <td class="number_column"><?=number_format($Array['Count'])?></td>
      </tr>
<?php } ?>
    </table>
    <h3>Different view modes by report type</h3>
<?php
  $db->prepared_query("
    SELECT
      Type,
      COUNT(ID) AS Count
    FROM reportsv2
    WHERE Status = 'New'
    GROUP BY Type");
  $Current = $db->to_array();
  if (!empty($Current)) {
?>
    <table class="box border">
      <tr class="colhead">
        <td class="colhead_dark">Type</td>
        <td class="colhead_dark number_column">Current Count</td>
      </tr>
<?php
    foreach ($Current as $Array) {
      //Ugliness
      foreach ($Types as $Category) {
        if (!empty($Category[$Array['Type']])) {
          $Title = $Category[$Array['Type']]['title'];
          break;
        }
      }
?>
      <tr<?=$Title === 'Urgent' ? ' class="highlight" style="font-weight: bold;"' : ''?>>
        <td>
          <a href="reportsv2.php?view=type&amp;id=<?=esc($Array['Type'])?>"><?=esc($Title)?></a>
        </td>
        <td class="number_column">
          <?=number_format($Array['Count'])?>
        </td>
      </tr>
<?php
    }
  }
?>
    </table>
  </div>
</div>
<?php
View::footer();
?>
