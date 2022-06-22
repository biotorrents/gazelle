<?php
declare(strict_types=1);

/**
 * Flight router
 * @see https://flightphp.com/learn
 */

# endpoints go here

# start the router
#Flight::start();


/** LEGACY ROUTES */


$ENV = ENV::go();

enforce_login();
define('INDEX_ARTICLE', '1');

function class_list($Selected = 0)
{
    global $Classes, $user;
    $Return = '';

    foreach ($Classes as $ID => $Class) {
        if ($Class['Level'] <= $user['EffectiveClass']) {
            $Return.='<option value="'.$Class['Level'].'"';

            if ($Selected === $Class['Level']) {
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
          require_once "$ENV->SERVER_ROOT/sections/wiki/takecreate.php";
      } else {
          require_once "$ENV->SERVER_ROOT/sections/wiki/create.php";
      }
      break;

    case 'edit':
      if ($_POST['action']) {
          require_once "$ENV->SERVER_ROOT/sections/wiki/takeedit.php";
      } else {
          require_once "$ENV->SERVER_ROOT/sections/wiki/edit.php";
      }
      break;

    case 'delete':
      require_once "$ENV->SERVER_ROOT/sections/wiki/delete.php";
      break;

    case 'revisions':
      require_once "$ENV->SERVER_ROOT/sections/wiki/revisions.php";
      break;

    case 'compare':
      require_once "$ENV->SERVER_ROOT/sections/wiki/compare.php";
      break;

    case 'add_alias':
      require_once "$ENV->SERVER_ROOT/sections/wiki/add_alias.php";
      break;

    case 'delete_alias':
      require_once "$ENV->SERVER_ROOT/sections/wiki/delete_alias.php";
      break;

    case 'browse':
      require_once "$ENV->SERVER_ROOT/sections/wiki/wiki_browse.php";
      break;

    case 'article':
      require_once "$ENV->SERVER_ROOT/sections/wiki/article.php";
      break;
      
    case 'search':
      require_once "$ENV->SERVER_ROOT/sections/wiki/search.php";
      break;
  }
} else {
    $_GET['id'] = INDEX_ARTICLE;
    require_once "$ENV->SERVER_ROOT/sections/wiki/article.php";
}
