<?

if (empty($_GET['url'])) {
  json_die();
}

$url = $_GET['url'];

$matches = array();
preg_match('/^https?:\/\/g?\.?e.hentai\.org\/g\/(\d+)\/([\w\d]+)\/?$/', $url, $matches);

$gid = $matches[1] ?? '';
$token = $matches[2] ?? '';

if (empty($gid) || empty($token)) {
  json_die("failure", "Invalid URL");
}

if ($Cache->get_value('doujin_json_'.$gid)) {
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
  $censored = true;
  foreach ($json["tags"] as $tag) {
    if (strpos($tag, ':') !== false) {
      list($namespace, $tag) = explode(':', $tag);
    } else { $namespace = ''; }

    if ($namespace == "artist") {
      array_push($artists, ucwords($tag));
    } else if ($namespace == "language") {
      $lang = empty($lang) ? ucfirst($tag) : $lang;
    } else if ($namespace == "group") {
      $circle = empty($circle) ? ucfirst($tag) : $circle;
    } else if ($tag == "uncensored") {
      $censored = false;
    } else {
      if ($namespace) { $tag = $tag.':'.$namespace; }
      array_push($tags, str_replace(' ', '.', $tag));
    }
  }
  
  // get the cover for ants
  $cover = $json['thumb'];
  // and let's see if we can replace it with something better
  $gallery_page = file_get_contents($url);
  $re = '/'.preg_quote('-0px 0 no-repeat"><a href="').'(.*)'.preg_quote('"><img alt="01"').'/';
  preg_match($re, $gallery_page, $galmatch);
  // were we able to find the first page of the gallery?
  if ($galmatch[1]) {
	  $image_page = file_get_contents($galmatch[1]);
	  $re = '/'.preg_quote('"><img id="img" src="').'(.*)'.preg_quote('" style=').'/';
	  preg_match($re, $image_page, $imgmatch);
	  // were we able to find the image url?
	  if ($imgmatch[1]) {
	    $cover = $imgmatch[1];
	  }
  }

  $json_str = array(
    'id' => $gid,
    'title' => html_entity_decode($json['title'], ENT_QUOTES),
    'title_jp' => html_entity_decode($json['title_jpn'], ENT_QUOTES),
    'artists' => $artists,
    'circle' => $circle,
    'censored' => $censored,
    'year' => NULL,
    'tags' => $tags,
    'lang' => $lang,
    'pages' => $json['filecount'],
    'description' => '',
    'cover' => $cover
  );

  $Cache->cache_value('doujin_json_'.$gid, $json_str, 86400);

  json_die("success", $json_str);
}

?>
