<?php
#declare(strict_types=1);

# Headers, cache, etc.
$debug = false;

if (empty($_GET['cn'])) {
  json_die();
}

$cn = strtoupper($_GET['cn']);

if (!strpos($cn, '-')) {
  preg_match('/\d/', $cn, $m, PREG_OFFSET_CAPTURE);
  if ($m) { $cn = substr_replace($cn, '-', $m[0][1], 0); }
}

if (!$debug && $Cache->get_value('jav_fill_json_'.$cn)) {
  json_die('success', $Cache->get_value('jav_fill_json_'.$cn));
} else {

  # Query the API
  # todo: Validate to change $db

/* todo
 * switch $category:
 *   case 'DNA' || 'RNA':
 *     if $number = refseq_regex:
 *        $db = 'refseq';
 *     break;
 *   case 'Protein':
 *     if $number = uniprot_regex:
 *        $db = 'uniprot';
 *     break;
 *   default:
 *     error 'invalid number';
 *     break;
 */
$id = 'NM_001183340.1';

# Assemble the esearch URL
$base = 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/';
$url = $base . "esummary.fcgi?db=$db&id=$id&version=2.0";

# Post the esearch URL
$output = file_get_contents($url);

# Parse WebEnv and QueryKey
#$web = $1 if ($output =~ /<WebEnv>(\S+)<\/WebEnv>/);
#$key = $1 if ($output =~ /<QueryKey>(\d+)<\/QueryKey>/);

### Include this code for ESearch-ESummary
# Assemble the esummary URL
$url = $base . "esummary.fcgi?db=$db&query_key=$key&WebEnv=$web";

# Post the esummary URL
$docsums = file_get_contents($url);
echo "$docsums";

### Include this code for ESearch-EFetch
# Assemble the efetch URL
$url = $base . "efetch.fcgi?db=$db&query_key=$key&WebEnv=$web";
$url .= "&rettype=abstract&retmode=text";

# Post the efetch URL
$data = file_get_contents($url);
echo "$data";

  /*
  $jlib_jp_url = ('http://www.javlibrary.com/ja/vl_searchbyid.php?keyword='.$cn);
  $jlib_en_url = ('http://www.javlibrary.com/en/vl_searchbyid.php?keyword='.$cn);
  $jdb_url     = ('http://javdatabase.com/movies/'.$cn.'/');

  $jlib_page_jp = file_get_contents($jlib_jp_url);
  $jlib_page_en = file_get_contents($jlib_en_url);
  $jdb_page     = file_get_contents($jdb_url);

  if ($jlib_page_en) {
    $jlib_dom_en = new DOMDocument();
    $jlib_dom_en->loadHTML($jlib_page_en);
    $jlib_en = new DOMXPath($jlib_dom_en);

    // Check if we're still on the search page and fix it if so
    if($jlib_en->query("//a[starts-with(@title, \"$cn\")]")->item(0)) {
      $href = substr($jlib_en->query("//a[starts-with(@title, \"$cn\")]")->item(0)->getAttribute('href'),1);
      $jlib_page_en = file_get_contents('http://www.javlibrary.com/en/'.$href);
      $jlib_page_jp = file_get_contents('http://www.javlibrary.com/ja/'.$href);
      $jlib_dom_en->loadHTML($jlib_page_en);
      $jlib_en = new DOMXPath($jlib_dom_en);
      // If the provided CN was so bad that search provided a different match, die
      if(strtoupper($jlib_en->query('//*[@id="video_id"]/table/tr/td[2]')->item(0)->nodeValue) != $cn) {
        json_die('failure', 'Movie not found');
      }
    }
  }
  if ($jlib_page_jp) {
    $jlib_dom_jp = new DOMDocument();
    $jlib_dom_jp->loadHTML($jlib_page_jp);
    $jlib_jp = new DOMXPath($jlib_dom_jp);
  }
  if ($jdb_page) {
    $jdb_dom = new DOMDocument();
    $jdb_dom->loadHTML($jdb_page);
    $jdb = new DOMXPath($jdb_dom);
  }

  list($idols, $genres, $screens, $title, $title_jp, $year, $studio, $label, $desc, $image) = array([],[],[],'','','','','','','');

  if (!$jdb_page && !$jlib_page_jp && !$jlib_page_en) {
    json_die('failure', 'Movie not found');
  }

  $degraded = false;

  if ($jlib_page_jp && $jlib_jp->query('//*[@id="video_title"]')['length']) {
    $title_jp = $jlib_jp->query('//*[@id="video_title"]/h3/a')->item(0)->nodeValue;
    $title_jp = substr($title_jp, strlen($cn) + 1);
  } else {
    $degraded = true;
  }
  if ($jlib_page_en && $jlib_en->query('//*[@id="video_title"]')['length']) {
    $title = $jlib_en->query('//*[@id="video_title"]/h3/a')->item(0)->nodeValue;
    $title = substr($title, strlen($cn) + 1);
    $idols = [];
    foreach ($jlib_en->query('//*[starts-with(@id, "cast")]/span[1]/a') as $idol) {
      $idols[] = $idol->nodeValue;
    }
    $year = $jlib_en->query('//*[@id="video_date"]/table/tr/td[2]')->item(0)->nodeValue;
    $year = explode('-', $year)[0];
    $studio = $jlib_en->query('//*[starts-with(@id, "maker")]/a')->item(0)->nodeValue;
    $label = $jlib_en->query('//*[starts-with(@id, "label")]/a')->item(0)->nodeValue;
    $image = $jlib_en->query('//*[@id="video_jacket_img"]')->item(0)->getAttribute('src');
    $comments = "";
    foreach ($jlib_en->query('//*[@class="comment"]//*[@class="t"]//textarea') as $comment) {
      $comments .= ($comment->nodeValue).' ';
    }
    preg_match_all("/\[img\b[^\]]*\]([^\[]*?)\[\/img\](?!\[\/url)/is", $comments, $screens_t);
    if (isset($screens_t[1])) {
      $screens = $screens_t[1];
      function f($s) { return !(preg_match('/(rapidgator)|(uploaded)|(javsecret)|(\.gif)|(google)|(thumb)|(imgur)|(fileboom)|(openload)/', $s)); }
      $screens = array_values(array_filter($screens, f));
    }
    if (preg_match('/http:\/\/imagetwist.com\/\S*jpg.html/', $comments, $twist)) {
      $twist_t = file_get_contents($twist[0]);
      $twist = new DOMDocument();
      $twist->loadHTML($twist_t);
      $twist = new DOMXPath($twist);
      if ($twist->query('//img[@class="pic"]')->item(0)) {
        $screens[] =  $twist->query('//img[@class="pic"]')->item(0)->getAttribute('src');
      }
    }
    $desc = '';
    $genres = [];
    foreach ($jlib_en->query('//*[starts-with(@id, "genre")]/a') as $genre) {
      $genres[] =  str_replace(' ', '.', strtolower($genre->nodeValue));
    }
  } else {
    $degraded = true;
  }
  if ($jdb_page) {
    if (!$title) {
      $title = trim(substr($jdb->query("//h1[contains(@class, 'entry-title')]")[0]->nodeValue, strlen($cn) + 3));
    }
    if (!$studio) {
      $studio = $jdb->query("//b[contains(., 'Studio:')]")[0]->nextSibling->nodeValue;
    }
    if (!$label) {
      $label = $jdb->query("//b[contains(., 'Label:')]")[0]->nextSibling->nodeValue;
    }
    if (!$idols) {
      $idols_raw = $jdb->query("//b[contains(., 'Idol(s): ')]")[0]->nextSibling;

      for ($i = 0; $i < 10; $i++) {
        if ($idols_raw->tagName == "a") {
          $idol_name = $idols_raw->nodeValue;
          $idol_lower = strtolower(str_replace(' ', '-', $idol_name));
          // ensure it's actually an idol name
          if (strpos($idols_raw->attributes->item(0)->nodeValue, '.com/idols/' . $idol_lower) !== false) {
            $idols[] = $idols_raw->nodeValue;
          }
        }
        $idols_raw = $idols_raw->nextSibling;
      }
    }
    if (!$year) {
      $year = substr($jdb->query("//b[contains(., 'Release Date:')]")[0]->nextSibling->nodeValue, 1, 4);
    }
    if (!$image) {
      $image = $jdb->query("//img[contains(@alt, ' download or stream.')]")->item(0)->getAttribute('src');
    }
    if (substr($image, 0, 2) == '//') {
      $image = 'https:'.$image;
    }
    if (!$desc) {
      // Shit neither of the sites have descriptions
      $desc = '';
    }
  }

  if (!($title || $idols || $year || $studio || $label || $genres)) {
    json_die('failure', 'Movie not found');
  }

  // Only show "genres" we have tags for
  if (!$Cache->get_value('genre_tags')) {
    $DB->query('
      SELECT Name
      FROM tags
      WHERE TagType = \'genre\'
      ORDER BY Name');
    $Cache->cache_value('genre_tags', $DB->collect('Name'), 3600 * 6);
  }
  $genres = array_values(array_intersect(array_values($Cache->get_value('genre_tags')), str_replace('_','.',array_values(Tags::remove_aliases(array('include' => str_replace('.','_',$genres)))['include']))));

  $json = array(
    'cn'          => $cn,
    'title'       => ($title ? $title : ''),
    'title_jp'    => ($title_jp ? $title_jp : ''),
    'idols'       => ($idols ? $idols : []),
    'year'        => ($year ? $year : ''),
    'studio'      => ($studio ? $studio : ''),
    'label'       => ($label ? $label : ''),
    'image'       => ($image ? $image : ''),
    'description' => ($desc ? $desc : ''),
    'tags'        => ($genres ? $genres : []),
    'screens'     => ($screens ? $screens : []),
    'degraded'    => $degraded
  );

  $Cache->cache_value('jav_fill_json_'.$cn, $json, 86400);

  json_die('success', $json);
*/
}
