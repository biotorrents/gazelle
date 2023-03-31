<?php
#declare(strict_types=1);


$app = \Gazelle\App::go();

$ENV = ENV::go();

if (!check_perms('admin_manage_ipbans')) {
    error(403);
}

if (isset($_POST['submit'])) {
    authorize();

    $IPA = substr($_POST['start'], 0, strcspn($_POST['start'], '.'));
    if ($_POST['submit'] === 'Delete') { //Delete
        if (!is_numeric($_POST['id']) || $_POST['id'] === '') {
            error(0);
        }
        $app->dbOld->query('DELETE FROM ip_bans WHERE ID='.$_POST['id']);
        $app->cacheOld->delete_value('ip_bans_'.$IPA);
    } else { //Edit & Create, Shared Validation
        $Val->SetFields('start', '1', 'regex', 'You must include the starting IP address.', array('regex'=>'/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/i'));
        $Val->SetFields('end', '1', 'regex', 'You must include the ending IP address.', array('regex'=>'/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/i'));
        $Val->SetFields('notes', '1', 'string', 'You must include the reason for the ban.');
        $Err=$Val->ValidateForm($_POST); // Validate the form
        if ($Err) {
            error($Err);
        }

        $Notes = db_string($_POST['notes']);
        $Start = Tools::ip_to_unsigned($_POST['start']); //Sanitized by Validation regex
        $End = Tools::ip_to_unsigned($_POST['end']); //See above

        if ($_POST['submit'] === 'Edit') { //Edit
            if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
                error(404);
            }
            $app->dbOld->query("
        UPDATE ip_bans
        SET
          FromIP=$Start,
          ToIP='$End',
          Reason='$Notes'
        WHERE ID='".$_POST['id']."'");
        } else { //Create
            $app->dbOld->query("
        INSERT INTO ip_bans
          (FromIP, ToIP, Reason)
        VALUES
          ('$Start','$End', '$Notes')");
        }
        $app->cacheOld->delete_value('ip_bans_'.$IPA);
    }
}

define('BANS_PER_PAGE', '20');
list($Page, $Limit) = Format::page_limit(BANS_PER_PAGE);

$sql = "
  SELECT
    SQL_CALC_FOUND_ROWS
    ID,
    FromIP,
    ToIP,
    Reason
  FROM ip_bans ";

if (!empty($_REQUEST['notes'])) {
    $sql .= "WHERE Reason LIKE '%".db_string($_REQUEST['notes'])."%' ";
}

if (!empty($_REQUEST['ip']) && preg_match("/{$app->env->regexIp}/", $_REQUEST['ip'])) {
    if (!empty($_REQUEST['notes'])) {
        $sql .= "AND '".Tools::ip_to_unsigned($_REQUEST['ip'])."' BETWEEN FromIP AND ToIP ";
    } else {
        $sql .= "WHERE '".Tools::ip_to_unsigned($_REQUEST['ip'])."' BETWEEN FromIP AND ToIP ";
    }
}

$sql .= "ORDER BY FromIP ASC";
$sql .= " LIMIT ".$Limit;
$Bans = $app->dbOld->query($sql);

$app->dbOld->query('SELECT FOUND_ROWS()');
list($Results) = $app->dbOld->next_record();

$PageLinks = Format::get_pages($Page, $Results, BANS_PER_PAGE, 11);

View::header('IP Address Bans');
$app->dbOld->set_query_id($Bans);
?>

<div class="header">
  <h2>IP Address Bans</h2>
</div>
<div>
  <form class="search_form" name="bans" action="" method="get">
    <table cellpadding="6" cellspacing="1" border="0" class="layout border" width="100%">
      <tr>
        <td class="label"><label for="ip">IP address:</label></td>
        <td>
          <input type="hidden" name="action" value="ip_ban" />
          <input type="search" id="ip" name="ip" size="20"
            value="<?=(!empty($_GET['ip']) ? Text::esc($_GET['ip']) : '')?>" />
        </td>
        <td class="label"><label for="notes">Notes:</label></td>
        <td>
          <input type="hidden" name="action" value="ip_ban" />
          <input type="search" id="notes" name="notes" size="60"
            value="<?=(!empty($_GET['notes']) ? Text::esc($_GET['notes']) : '')?>" />
        </td>
        <td>
          <input type="submit" class="button-primary" value="Search" />
        </td>
      </tr>
    </table>
  </form>
</div>
<br />

<h3>Manage</h3>
<div class="linkbox">
  <?=$PageLinks?>
</div>
<table width="100%">
  <tr class="colhead">
    <td colspan="2">
      <span class="tooltip"
        title="The IP addresses specified are &#42;inclusive&#42;. The left box is the beginning of the IP address range, and the right box is the end of the IP address range.">Range</span>
    </td>
    <td>Notes</td>
    <td>Submit</td>
  </tr>
  <tr class="row">
    <form name="ban" action="" method="post">
      <input type="hidden" name="action" value="ip_ban" />
      <input type="hidden" name="auth"
        value="<?=$app->userNew->extra['AuthKey']?>" />
      <td colspan="2">
        <input type="text" size="12" name="start" />
        <input type="text" size="12" name="end" />
      </td>
      <td>
        <input type="text" size="72" name="notes" />
      </td>
      <td>
        <input type="submit" name="submit" class="button-primary" value="Create" />
      </td>
    </form>
  </tr>
  <?php
while (list($ID, $Start, $End, $Reason) = $app->dbOld->next_record()) {
    $Start = long2ip($Start);
    $End = long2ip($End); ?>
  <tr class="row">
    <form class="manage_form" name="ban" action="" method="post">
      <input type="hidden" name="id" value="<?=$ID?>" />
      <input type="hidden" name="action" value="ip_ban" />
      <input type="hidden" name="auth"
        value="<?=$app->userNew->extra['AuthKey']?>" />
      <td colspan="2">
        <input type="text" size="12" name="start"
          value="<?=$Start?>" />
        <input type="text" size="12" name="end" value="<?=$End?>" />
      </td>
      <td>
        <input type="text" size="72" name="notes"
          value="<?=$Reason?>" />
      </td>
      <td>
        <input type="submit" name="submit" value="Edit" />
        <input type="submit" name="submit" value="Delete" />
      </td>
    </form>
  </tr>
  <?php
}
?>
</table>
<div class="linkbox">
  <?=$PageLinks?>
</div>
<?php View::footer();
