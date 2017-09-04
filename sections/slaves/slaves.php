<?
$DB->query("SELECT OwnerID FROM slaves WHERE UserID = $UserID");
if ($DB->has_results()) {
  list($Owner) = $DB->next_record();
}

$UserLevel = Slaves::get_level($UserID);

$DB->query("SELECT UserID FROM slaves WHERE OwnerID = $UserID");
$Slaves = $DB->collect('UserID');

if (isset($_POST['release'])) {
  if (in_array($_POST['release'], $Slaves)) {
    $DB->query("
      DELETE FROM slaves
      WHERE UserID = ".db_string($_POST['release'])."
      AND OwnerID = '$UserID'");
  }
  $Slaves = array_diff($Slaves, [$_POST['release']]);
}

foreach ($Slaves as $i => $Slave) {
  $Level = slaves::get_level($Slave);
  $Slaves[$i] = ['ID' => $Slave, 'Level' => $Level];
}

View::show_header('Slaves');
?>
<div class="thin">
  <h2>Slavery</h2>
  <div class="box pad">
<?  if (isset($Owner)) { ?>
    <h3>You are owned by <?=Users::format_username($Owner, false, true, true)?></h3>
<?  } else { ?>
    <h3>You are free</h3>
<?  } ?>
  </div>
<?  if (sizeof($Slaves) == 0) { ?>
  <h3>You have no slaves</h3>
<?  } else { ?>
  <h2>Your slaves</h2>
  <div class="box">
    <table>
      <tr class="colhead">
        <td>Slave</td>
        <td>Level</td>
        <td>Release</td>
      </tr>
<?    foreach ($Slaves as $Slave) { ?>
      <tr>
        <td><?=Users::format_username($Slave['ID'], false, true, true)?></td>
        <td><?=number_format($Slave['Level'])?></td>
        <td><form method="post"><button type="submit" name="release" value=<?=$Slave['ID']?>>Release</button></form></td>
      </tr>
<?    } ?>
    </table>
  </div>
<?  } ?>
</div>
<? View::show_footer(); ?>
