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

/*
	The following way works perfectly fine, we just foung the APNIC data to be to outdated for us.
*/

/*
if (!check_perms('admin_update_geoip')) {
	die();
}
enforce_login();

ini_set('memory_limit', 1024 * 1024 * 1024);
ini_set('max_execution_time', 3600);

header('Content-type: text/plain');
ob_end_clean();
restore_error_handler();

$Registries[] = 'http://ftp.apnic.net/stats/afrinic/delegated-afrinic-latest'; //Africa
$Registries[] = 'http://ftp.apnic.net/stats/apnic/delegated-apnic-latest'; //Asia & Pacific
$Registries[] = 'http://ftp.apnic.net/stats/arin/delegated-arin-latest'; //North America
$Registries[] = 'http://ftp.apnic.net/stats/lacnic/delegated-lacnic-latest'; //South America
$Registries[] = 'http://ftp.apnic.net/stats/ripe-ncc/delegated-ripencc-latest'; //Europe

$Registries[] = 'ftp://ftp.afrinic.net/pub/stats/afrinic/delegated-afrinic-latest'; //Africa
$Registries[] = 'ftp://ftp.apnic.net/pub/stats/apnic/delegated-apnic-latest'; //Asia & Pacific
$Registries[] = 'ftp://ftp.arin.net/pub/stats/arin/delegated-arin-latest'; //North America
$Registries[] = 'ftp://ftp.lacnic.net/pub/stats/lacnic/delegated-lacnic-latest'; //South America
$Registries[] = 'ftp://ftp.ripe.net/ripe/stats/delegated-ripencc-latest'; //Europe



$Query = array();

foreach ($Registries as $Registry) {
	$CountryData = explode("\n",file_get_contents($Registry));
	foreach ($CountryData as $Country) {
		if (preg_match('/\|([A-Z]{2})\|ipv4\|(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\|(\d+)\|/', $Country, $Matches)) {

			$Start = Tools::ip_to_unsigned($Matches[2]);
			if ($Start == 2147483647) { continue; }

			if (!isset($Current)) {
				$Current = array('StartIP' => $Start, 'EndIP' => $Start + $Matches[3],'Code' => $Matches[1]);
			} elseif ($Current['Code'] == $Matches[1] && $Current['EndIP'] == $Start) {
				$Current['EndIP'] = $Current['EndIP'] + $Matches[3];
			} else {
				$Query[] = "('".$Current['StartIP']."','".$Current['EndIP']."','".$Current['Code']."')";
				$Current = array('StartIP' => $Start, 'EndIP' => $Start + $Matches[3],'Code' => $Matches[1]);
			}
		}
	}
}
$Query[] = "('".$Current['StartIP']."','".$Current['EndIP']."','".$Current['Code']."')";

$DB->query("TRUNCATE TABLE geoip_country");
$DB->query("INSERT INTO geoip_country (StartIP, EndIP, Code) VALUES ".implode(',', $Query));
echo $DB->affected_rows();
*/
