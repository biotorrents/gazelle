<?

if (empty($_GET['url'])) {
  json_die();
}

$url = $_GET['url'];

$matches = array();
preg_match('/^https?:\/\/nhentai.net\/g\/(\d+)\/?$/', $url, $matches);

$id = (isset($matches[1])) ? $matches[1] : '';

if (empty($id)) {
  json_die("failure", "Invalid URL");
}

if ($Cache->get_value('doujin_json_'.$id)) {
  json_die("success", $Cache->get_value('doujin_json_'.$id));
} else {

  $url = 'http://nhentai.net/g/' . $id . '/json';

  $json = file_get_contents($url);

  if (empty($json)) {
    json_die("failure", "Could not get page");
  }

  $json = json_decode($json, true);

  $artists = array();
  $tags = array();
  $lang = NULL;
  foreach ($json["tags"] as $tag) {
    if ($tag[1] == "artist")
      array_push($artists, ucwords($tag[2]));
    elseif ($tag[1] == "tag")
      array_push($tags, str_replace(' ', '.', $tag[2]));
    elseif ($tag[1] == "language")
      $lang = ucfirst($tag[2]);
  }

  switch($json['images']['cover']['t']) {
    case 'j':
      $covertype = "jpg";
      break;
    case 'p':
      $covertype = "png";
      break;
  }
  $cover = 'http://i.nhentai.net/galleries/'.$json['media_id'].'/1.'.$covertype;

  $json_str = array(
    'id' => $id,
    'title' => $json['title']['english'],
    'title_jp' => $json['title']['japanese'],
    'artists' => $artists,
    'year' => NULL,
    'tags' => $tags,
    'lang' => $lang,
    'pages' => $json['num_pages'],
    'description' => '',
    'cover' => $cover
  );

  $Cache->cache_value('doujin_json_'.$id, $json_str, 86400);

  json_die("success", $json_str);
}

?>
