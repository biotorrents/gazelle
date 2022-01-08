<?php
#declare(strict_types=1);

/************************************************************************
||------------|| User IP history page ||---------------------------||

This page lists previous IPs a user has connected to the site with. It
gets called if $_GET['action'] == 'ips'.

It also requires $_GET['userid'] in order to get the data for the correct
user.

************************************************************************/

define('IPS_PER_PAGE', 25);

if (!check_perms('users_mod')) {
    error(403);
}

$UserID = $_GET['userid'];
if (!is_number($UserID)) {
    error(404);
}

$DB->query("
  SELECT um.Username,
    p.Level AS Class
  FROM users_main AS um
    LEFT JOIN permissions AS p ON p.ID = um.PermissionID
  WHERE um.ID = $UserID");
list($Username, $Class) = $DB->next_record();

if (!check_perms('users_view_ips', $Class)) {
    error(403);
}

$UsersOnly = $_GET['usersonly'];

View::show_header("Tracker IP address history for $Username");
?>
<script type="text/javascript">
  function ShowIPs(rowname) {
    $('tr[name="' + rowname + '"]').gtoggle();
  }
</script>
<?php
list($Page, $Limit) = Format::page_limit(IPS_PER_PAGE);

$Perms = \Permissions::get_permissions_for_user($UserID);
if ($Perms['site_disable_ip_history']) {
    $Limit = 0;
}

$TrackerIps = $DB->query("
  SELECT IP, fid, tstamp
  FROM xbt_snatched
  WHERE uid = $UserID
    AND IP != ''
  ORDER BY tstamp DESC
  LIMIT $Limit");

$DB->query('SELECT FOUND_ROWS()');
list($NumResults) = $DB->next_record();
$DB->set_query_id($TrackerIps);

$Pages = Format::get_pages($Page, $NumResults, IPS_PER_PAGE, 9);

?>
<div>
  <div class="header">
    <h2>Tracker IP address history for <a
        href="user.php?id=<?=$UserID?>"><?=$Username?></a></h2>
  </div>
  <div class="linkbox"><?=$Pages?>
  </div>
  <table>
    <tr class="colhead">
      <td>IP address</td>
      <td>Torrent</td>
      <td>Time</td>
    </tr>
    <?php
$Results = $DB->to_array();
foreach ($Results as $Index => $Result) {
    list($IP, $TorrentID, $Time) = $Result; ?>
    <tr class="row">
      <td>
        <?=$IP?><br /><?=Tools::get_host_by_ajax($IP)?>
        <a href="http://whatismyipaddress.com/ip/<?=display_str($IP)?>"
          class="brackets tooltip" title="Search WIMIA.com">WI</a>
      </td>
      <td><a href="torrents.php?torrentid=<?=$TorrentID?>"><?=$TorrentID?></a></td>
      <td><?=date('Y-m-d g:i:s', $Time)?>
      </td>
    </tr>
    <?php
}
?>
  </table>
  <div class="linkbox">
    <?=$Pages?>
  </div>
</div>

<?php View::show_footer();
