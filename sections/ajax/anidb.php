<?

if (empty($_GET['aid'])) {
  json_die();
}

$aid = $_GET['aid'];

if ($Cache->get_value('anidb_json_'.$aid)) {
  json_die("success", $Cache->get_value('anidb_json_'.$aid));
} else {

  $anidb_url = 'http://api.anidb.net:9001/httpapi?request=anime&client='.API_KEYS['ANIDB'].'&clientver=1&protover=1&aid='.$aid;

  $crl = curl_init();
  curl_setopt($crl, CURLOPT_URL, $anidb_url);
  curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($crl, CURLOPT_CONNEECTTIMEOUT, 5);
  $ret = curl_exec($crl);
  curl_close($curl);

  $anidb_xml = new SimpleXMLElement(zlib_decode($ret));

  if ($anidb_xml->xpath('/error')) {
    json_die("failure", $anidb_xml->xpath('/error')[0]."");
  }

  $title = $anidb_xml->xpath('//titles/title[@type = "main"]')[0].'';
  $title = (empty($title))?$anidb_xml->xpath('//titles/title[@xml:lang = "en" and @type = "official"]')[0].'':$title;

  $title_jp = $anidb_xml->xpath('//titles/title[@xml:lang = "ja" and @type = "official"]')[0].'';

  $artist = $anidb_xml->xpath('//creators/name[@type = "Animation Work"]')[0].'';

  $year = substr($anidb_xml->startdate, 0, 4);

  $desc = preg_replace('/http:\/\/anidb.net\S+ \[(.*?)\]/', '$1', ($anidb_xml->description).'');

  $json_str = array(
    'id' => $aid,
    'title' => $title,
    'title_jp' => $title_jp,
    'artist' => $artist,
    'year' => $year,
    'description' => $desc
  );

  $Cache->cache_value('anidb_json_'.$aid, $json_str, 86400);

  json_die("success", $json_str);
}

?>
