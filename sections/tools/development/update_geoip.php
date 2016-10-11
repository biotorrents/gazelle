<?
ini_set('memory_limit', '1G');
set_time_limit(0);

$LIMIT = 1000;

if (!check_perms('site_debug')) {
  error(403);
}

View::show_header();
chdir('/tmp');
// requires wget, unzip, gunzip commands to be installed

// Country section
shell_exec('wget http://geolite.maxmind.com/download/geoip/database/GeoIPCountryCSV.zip');
shell_exec('wget http://geolite.maxmind.com/download/geoip/database/GeoIPv6.csv.gz');
shell_exec('unzip GeoIPCountryCSV.zip');
shell_exec('gunzip GeoIPv6.csv.gz');
shell_exec('cut -d , -f 3-5 GeoIPCountryWhois.csv > GeoIPCountry.csv');
shell_exec('cut -d , -f 3-5 GeoIPv6.csv | tr -d " " >> GeoIPCountry.csv');

if (($fd = fopen('GeoIPCountry.csv', 'r')) !== false) {
  $DB->query("TRUNCATE TABLE geoip_country");
  $Values = array();
  $Count = 0;
  while (($Data = fgetcsv($fd)) !== false) {
    list($StartIP, $EndIP, $CountryID) = $Data;
    $Values[] = "($StartIP, $EndIP, '$CountryID')";
    $Count++;
    if ($Count % $LIMIT == 0) {
      $DB->query("
        INSERT INTO geoip_country (StartIP, EndIP, Code)
        VALUES ".implode(', ', $Values));
      $Values = array();
    }
  }
  if (count($Values) > 0) {
    $DB->query("
      INSERT INTO geoip_country (StartIP, EndIP, Code)
      VALUES ".implode(', ', $Values));
  }
  echo 'GeoIP_Country: There are '.($Count+count($Values)).' entries <br />';
} else {
  echo 'Country Error';
}
shell_exec('rm GeoIPCountryCSV.zip GeoIPv6.csv.gz GeoIPCountryWhois.csv GeoIPv6.csv GeoIPCountry.csv');

// ASN (v4) section
shell_exec('wget http://download.maxmind.com/download/geoip/database/asnum/GeoIPASNum2.zip');
shell_exec('unzip GeoIPASNum2.zip');

if (($fd = fopen('GeoIPASNum2.csv', 'r')) !== false) {
  $DB->query("TRUNCATE TABLE geoip_asn");
  $Values = array();
  $Count = 0;
  while (($Data = fgetcsv($fd)) !== false) {
    list($StartIP, $EndIP, $ASN) = $Data;
    $ASN = substr($ASN, 2, strpos($ASN, ' ') ? strpos($ASN, ' ')-2 : strlen($ASN)-2);
    $Values[] = "(INET6_ATON(INET_NTOA($StartIP)), INET6_ATON(INET_NTOA($EndIP)), $ASN)";
    $Count++;
    if ($Count % $LIMIT == 0) {
      $DB->query("
        INSERT INTO geoip_asn (StartIP, EndIP, ASN)
        VALUES ".implode(', ', $Values));
      $Values = array();
    }
  }
  if (count($Values) > 0) {
    $DB->query("
      INSERT INTO geoip_asn (StartIP, EndIP, ASN)
      VALUES ".implode(', ', $Values));
  }
  echo 'GeoIP_ASN (v4): There are '.($Count+count($Values)).' entries <br />';
} else {
  echo 'ASNv4 Error';
}
shell_exec('rm GeoIPASNum2.zip GeoIPASNum2.csv');

// ASN (v6) section
shell_exec('wget http://download.maxmind.com/download/geoip/database/asnum/GeoIPASNum2v6.zip');
shell_exec('unzip GeoIPASNum2v6.zip');

if (($fd = fopen('GeoIPASNum2v6.csv', 'r')) !== false) {
  $Values = array();
  $Count = 0;
  while (($Data = fgetcsv($fd)) !== false) {
    list($ASN, $StartIP, $EndIP) = $Data;
    $ASN = substr($ASN, 2, strpos($ASN, ' ') ? strpos($ASN, ' ')-2 : strlen($ASN)-2);
    $Values[] = "(INET6_ATON('$StartIP'), INET6_ATON('$EndIP'), $ASN)";
    $Count++;
    if ($Count % $LIMIT == 0) {
      $DB->query("
        INSERT INTO geoip_asn (StartIP, EndIP, ASN)
        VALUES ".implode(', ', $Values));
      $Values = array();
    }
  }
  if (count($Values) > 0) {
    $DB->query("
      INSERT INTO geoip_asn (StartIP, EndIP, ASN)
      VALUES ".implode(', ', $Values));
  }
  echo 'GeoIP_ASN (v6): There are '.($Count+count($Values)).' entries <br />';
} else {
  echo 'ASNv6 Error';
}
shell_exec('rm GeoIPASNum2v6.zip GeoIPASNum2v6.tmp GeoIPASNum2v6.csv');

View::show_footer();
