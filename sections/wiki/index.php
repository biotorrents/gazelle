<?
enforce_login();


define('INDEX_ARTICLE', '1');


function class_list($Selected = 0) {
  global $Classes, $LoggedUser;
  $Return = '';
  foreach ($Classes as $ID => $Class) {
    if ($Class['Level'] <= $LoggedUser['EffectiveClass']) {
      $Return.='<option value="'.$Class['Level'].'"';
      if ($Selected == $Class['Level']) {
        $Return.=' selected="selected"';
      }
      $Return.='>'.Format::cut_string($Class['Name'], 20, 1).'</option>'."\n";
    }
  }
  reset($Classes);
  return $Return;
}

if (!empty($_REQUEST['action'])) {
  switch ($_REQUEST['action']) {
    case 'create':
      if ($_POST['action']) {
        include(SERVER_ROOT.'/sections/wiki/takecreate.php');
      } else {
        include(SERVER_ROOT.'/sections/wiki/create.php');
      }
      break;
    case 'edit':
      if ($_POST['action']) {
        include(SERVER_ROOT.'/sections/wiki/takeedit.php');
      } else {
        include(SERVER_ROOT.'/sections/wiki/edit.php');
      }
      break;
    case 'delete':
      include(SERVER_ROOT.'/sections/wiki/delete.php');
      break;
    case 'revisions':
      include(SERVER_ROOT.'/sections/wiki/revisions.php');
      break;
    case 'compare':
      include(SERVER_ROOT.'/sections/wiki/compare.php');
      break;
    case 'add_alias':
      include(SERVER_ROOT.'/sections/wiki/add_alias.php');
      break;
    case 'delete_alias':
      include(SERVER_ROOT.'/sections/wiki/delete_alias.php');
      break;
    case 'browse':
      include(SERVER_ROOT.'/sections/wiki/wiki_browse.php');
      break;
    case 'article':
      include(SERVER_ROOT.'/sections/wiki/article.php');
      break;
    case 'search':
      include(SERVER_ROOT.'/sections/wiki/search.php');
      break;
  }
} else {
  $_GET['id'] = INDEX_ARTICLE;
  include(SERVER_ROOT.'/sections/wiki/article.php');
  //include('splash.php');
}
?>
