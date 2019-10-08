<?
global $LoggedUser;
define('FOOTER_FILE',SERVER_ROOT.'/design/publicfooter.php');
?>
<!DOCTYPE html>
<html>
<head>
  <title><?=display_str($PageTitle)?></title>
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#461640">
  <link rel="shortcut icon" href="favicon.ico?v=<?=md5_file('favicon.ico');?>">
  <link rel="manifest" href="/manifest.php">
  <link href="<?=STATIC_SERVER ?>styles/public/style.css?v=<?=filemtime(SERVER_ROOT.'/static/styles/public/style.css')?>" rel="stylesheet" type="text/css">
<?
  $Scripts = ['jquery', 'global', 'ajax.class', 'cookie.class', 'storage.class', 'public', 'u2f'];
  foreach($Scripts as $Script) {
    if (($ScriptStats = G::$Cache->get_value("script_stats_$Script")) === false || $ScriptStats['mtime'] != filemtime(SERVER_ROOT.STATIC_SERVER."functions/$Script.js")) {
      $ScriptStats['mtime'] = filemtime(SERVER_ROOT.STATIC_SERVER."functions/$Script.js");
      $ScriptStats['hash'] = base64_encode(hash_file(INTEGRITY_ALGO, SERVER_ROOT.STATIC_SERVER."functions/$Script.js", true));
      $ScriptStats['algo'] = INTEGRITY_ALGO;
      G::$Cache->cache_value("script_stats_$Script", $ScriptStats);
    }
?>
    <script src="<?=STATIC_SERVER."functions/$Script.js?v=$ScriptStats[mtime]"?>" type="text/javascript" integrity="<?="$ScriptStats[algo]-$ScriptStats[hash]"?>"></script>
<?
  }
  $img = array_diff(scandir(SERVER_ROOT.'/misc/bg', 1), array('.', '..')); ?>
  <meta name="bg_data" content="<?=$img[rand(0,count($img)-1)]?>">
</head>
<body>
<div id="head"><span>
<a href="login.php">Log in</a>
<? if (OPEN_REGISTRATION) { ?>
 | <a href="register.php">Register</a>
<? } ?>
</span></div>
<div id="content">
  <table class="layout" id="maincontent">
    <tr>
      <td class="centered">
        <a href="index.php"><div id="logo"></div></a>
<?
