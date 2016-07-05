<?
ini_set('memory_limit', '1G');
set_time_limit(0);

if (!check_perms('site_debug')) {
  error(403);
}

View::show_header();
chdir('/tmp');
//requires wget, unzip, gunzip commands to be installed
shell_exec('wget http://geolite.maxmind.com/download/geoip/database/GeoIPCountryCSV.zip');
shell_exec('wget http://geolite.maxmind.com/download/geoip/database/GeoIPv6.csv.gz');
shell_exec('unzip GeoIPCountryCSV.zip');
shell_exec('gunzip GeoIPv6.csv.gz');
shell_exec('cut -d , -f 3-5 GeoIPCountryWhois.csv > GeoIPCountry.csv');
shell_exec('cut -d , -f 3-5 GeoIPv6.csv | tr -d " " >> GeoIPCountry.csv');
shell_exec('rm GeoIPCountryCSV.zip GeoIPv6.csv.gz GeoIPCountryWhois.csv GeoIPv6.csv');

if (($Blocks = file("GeoIPCountry.csv", FILE_IGNORE_NEW_LINES)) === false) {
  echo 'Error';
}

echo 'There are '.count($Blocks).' blocks';
echo '<br />';

//Because the old code reading a 2mil line database had splitting, we're just gonna keep using it with this much more reasonable 140k line db
$SplitOn = 1000;
$DB->query("TRUNCATE TABLE geoip_country");

$Values = array();
foreach ($Blocks as $Index => $Block) {
  list($StartIP, $EndIP, $CountryID) = explode(",", $Block);
  $StartIP = trim($StartIP, '"');
  $EndIP = trim($EndIP, '"');
  $CountryID = trim($CountryID, '"');
  $Values[] = "('$StartIP', '$EndIP', '".$CountryID."')";
  if ($Index % $SplitOn == 0) {
    $DB->query('
      INSERT INTO geoip_country (StartIP, EndIP, Code)
      VALUES '.implode(', ', $Values));
    $Values = array();
  }
}

if (count($Values) > 0) {
  $DB->query("
    INSERT INTO geoip_country (StartIP, EndIP, Code)
    VALUES ".implode(', ', $Values));
}

View::show_footer();
