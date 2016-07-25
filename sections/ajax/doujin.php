<?

if (empty($_GET['url'])) {
  json_die();
}

$url = $_GET['url'];

$matches = array();
preg_match('/^https?:\/\/g\.e.hentai\.org\/g\/(\d+)\/([\w\d]+)\/?$/', $url, $matches);

$gid = $matches[1] ?? '';
$token = $matches[2] ?? '';

if (empty($gid) || empty($token)) {
  json_die("failure", "Invalid URL");
}

if ($Cache->get_value('doujin_json_'.$gid) && false) {
  json_die("success", $Cache->get_value('doujin_json_'.$gid));
} else {

  $data = json_encode(["method" => "gdata", "gidlist" => [[$gid, $token]], "namespace" => 1]);
  $curl = curl_init('http://g.e-hentai.org/api.php');
  curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
  curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Content-Length: '.strlen($data)]);

  $json = curl_exec($curl);

  if (empty($json)) {
    json_die("failure", "Could not get page");
  }

  $json = json_decode($json, true)["gmetadata"][0];

  $artists = array();
  $tags = array();
  $lang = NULL;
  $circle = NULL;
  foreach ($json["tags"] as $tag) {
    if (strpos($tag, ':') !== false) {
      list($namespace, $tag) = explode(':', $tag);
    } else { $namespace = ''; }

    if ($namespace == "artist") {
      array_push($artists, ucwords($tag));
    } else if ($namespace == "language" && empty($lang)) {
      $lang = ucfirst($tag);
    } else if ($namespace == "group" && empty($circle)) {
      $circle = ucfirst($tag);
    } else {
      if ($namespace) { $tag = $tag.':'.$namespace; }
      array_push($tags, str_replace(' ', '.', $tag));
    }
  }

  $json_str = array(
    'id' => $gid,
    'title' => $json['title'],
    'title_jp' => $json['title_jpn'],
    'artists' => $artists,
    'circle' => $circle,
    'year' => NULL,
    'tags' => $tags,
    'lang' => $lang,
    'pages' => $json['filecount'],
    'description' => '',
    'cover' => $json['thumb']
  );

  $Cache->cache_value('doujin_json_'.$gid, $json_str, 86400);

  json_die("success", $json_str);
}

?>
