<?
$Servers = array_diff(scandir(SERVER_ROOT.'/misc/heartbeat', 1), array('.', '..'));
View::show_header('Network status');

?>
<h2>Network Status</h2>
<div style="font-size: 0; text-align: center;">
<div class="net_box">
  <div class="head">Webserver</div>
  <div class="box pad center">
    <span class="r10">Online</span>
  </div>
</div>
<?
foreach ($Servers as $Server) {
  $Contents = file_get_contents(SERVER_ROOT.'/misc/heartbeat/'.$Server);
  if (substr($Server, 0, 7) == 'Tracker' || substr($Server, 0, 3) == 'IRC') {
    $Contents = explode("\n", $Contents);
    $Contents = '<span class="'.(((time() - (int)array_slice($Contents, -2)[0]) < 610) ? 'r10">Online' : 'r03">Offline').'</span>'.((substr($Server,0,7)=='Tracker')?'<br><br>'.('Backup From: '.time_diff((int)$Contents[0], 2, false)):'');
  } else if (substr($Server, 0, 6) == 'Backup') {
    $Contents = 'Backup From: '.time_diff((int)$Contents, 2, false);
  }
?>
  <div class="net_box">
    <div class="head"><?=$Server?></div>
    <div class="box pad center">
      <span><?=$Contents?></span>
    </div>
  </div>
  <?
  echo ($Server == 'IRC' ? '<br>' : '');
} ?>
</div>
<? View::show_footer();
