<?
global $LoggedUser, $SSL;
define('FOOTER_FILE',SERVER_ROOT.'/design/publicfooter.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title><?=display_str($PageTitle)?></title>
	<meta http-equiv="X-UA-Compatible" content="chrome=1; IE=edge" />
	<link rel="shortcut icon" href="favicon.ico?v=<?=md5_file('favicon.ico');?>" />
	<link href="<?=STATIC_SERVER ?>styles/public/style.css?v=<?=filemtime(SERVER_ROOT.'/static/styles/public/style.css')?>" rel="stylesheet" type="text/css" />
	<script src="<?=STATIC_SERVER?>functions/jquery.js" type="text/javascript"></script>
	<script src="<?=STATIC_SERVER?>functions/script_start.js?v=<?=filemtime(SERVER_ROOT.'/static/functions/script_start.js')?>" type="text/javascript"></script>
	<script src="<?=STATIC_SERVER?>functions/ajax.class.js?v=<?=filemtime(SERVER_ROOT.'/static/functions/ajax.class.js')?>" type="text/javascript"></script>
	<script src="<?=STATIC_SERVER?>functions/cookie.class.js?v=<?=filemtime(SERVER_ROOT.'/static/functions/cookie.class.js')?>" type="text/javascript"></script>
	<script src="<?=STATIC_SERVER?>functions/storage.class.js?v=<?=filemtime(SERVER_ROOT.'/static/functions/storage.class.js')?>" type="text/javascript"></script>
	<script src="<?=STATIC_SERVER?>functions/global.js?v=<?=filemtime(SERVER_ROOT.'/static/functions/global.js')?>" type="text/javascript"></script>
<? $img = array_diff(scandir(SERVER_ROOT.'/misc/bg', 1), array('.', '..')); ?>
  <style> #content { background-image: url(<? echo("'/misc/bg/" . $img[rand(0,count($img)-1)] . "'"); ?>); }</style>
</head>
<body>
<div id="head">
</div>
<div id="content">
  <table class="layout" id="maincontent">
    <tr>
      <td align="center" valign="middle">
        <div id="logo">
          <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="login.php">Log in</a></li>
<? if (OPEN_REGISTRATION) { ?>
            <li><a href="register.php">Register</a></li>
<? } ?>
          </ul>
        </div>
<?
