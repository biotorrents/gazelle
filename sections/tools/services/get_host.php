<?
if (isset($_SERVER['http_if_modified_since'])) {
  header('Status: 304 Not Modified');
  die();
}

header('Expires: '.date('D, d-M-Y H:i:s \U\T\C', time() + 3600 * 24 * 120)); //120 days
header('Last-Modified: '.date('D, d-M-Y H:i:s \U\T\C', time()));

if (!check_perms('users_view_ips')) {
  die('Access denied.');
}
if (empty($_GET['ip'])) {
  die('No IP given.');
}
$IP = $_GET['ip'];

$Delimiter = $IP[strcspn($IP, ':.')];
$OctOrHextets = explode($Delimiter, $IP);


if ($Delimiter == '.' && sizeof($OctOrHextets) == 4) { // IPv4
  if ( ($OctOrHextets[0] == 127 || $OctOrHextets[0] == 10)
    || ($OctOrHextets[0] == 192 && $OctOrHextets[1] == 168)
    || ($OctOrHextets[0] == 172 && ($OctOrHextets[1] >= 16 && $OctOrHextets[1] <= 32))
  ) {
    die('Invalid IPv4 address.');
  }
  foreach($OctOrHextets as $Octet) {
    if ($Octet > 255 || $Octet < 0) {
      die('Invalid IPv4 address.');
    }
  }
} else if (sizeof($OctOrHextets) <= 8) { // IPv6
  foreach($OctOrHextets as $Hextet) {
    if (strlen($Hextet) > 4) {
      die('Invalid IPv6 address.');
    }
  }
} else {
  die('Invalid IP address.');
}

$Host = Tools::lookup_ip($IP);

if ($Host === '') {
  trigger_error('Tools::get_host_by_ajax() command failed with no output, ensure that the host command exists on your system and accepts the argument -W');
} elseif ($Host === false) {
  print 'Could not retrieve host.';
} else {
  print $Host;
}
