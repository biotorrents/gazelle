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


$ENV = \Gazelle\ENV::go();

enforce_login();
define('INDEX_ARTICLE', '1');

function class_list($Selected = 0)
{
    $app = \Gazelle\App::go();

    global $Classes;
    $Return = '';

    foreach ($Classes as $ID => $Class) {
        if ($Class['Level'] <= $app->user->extra['EffectiveClass']) {
            $Return .= '<option value="'.$Class['Level'].'"';

            if ($Selected === $Class['Level']) {
                $Return .= ' selected="selected"';
            }
            $Return .= '>'.\Gazelle\Text::limit($Class['Name'], 20).'</option>'."\n";
        }
    }

    reset($Classes);
    return $Return;
}

if (!empty($_REQUEST['action'])) {
    switch ($_REQUEST['action']) {
        case 'create':
            if ($_POST['action']) {
                require_once "$ENV->serverRoot/sections/wikiOld/takecreate.php";
            } else {
                require_once "$ENV->serverRoot/sections/wikiOld/create.php";
            }
            break;

        case 'edit':
            if ($_POST['action']) {
                require_once "$ENV->serverRoot/sections/wikiOld/takeedit.php";
            } else {
                require_once "$ENV->serverRoot/sections/wikiOld/edit.php";
            }
            break;

        case 'delete':
            require_once "$ENV->serverRoot/sections/wikiOld/delete.php";
            break;

        case 'revisions':
            require_once "$ENV->serverRoot/sections/wikiOld/revisions.php";
            break;

        case 'compare':
            require_once "$ENV->serverRoot/sections/wikiOld/compare.php";
            break;

        case 'add_alias':
            require_once "$ENV->serverRoot/sections/wikiOld/add_alias.php";
            break;

        case 'delete_alias':
            require_once "$ENV->serverRoot/sections/wikiOld/delete_alias.php";
            break;

        case 'browse':
            require_once "$ENV->serverRoot/sections/wikiOld/wiki_browse.php";
            break;

        case 'article':
            require_once "$ENV->serverRoot/sections/wikiOld/article.php";
            break;

        case 'search':
            require_once "$ENV->serverRoot/sections/wikiOld/search.php";
            break;
    }
} else {
    $_GET['id'] = INDEX_ARTICLE;
    require_once "$ENV->serverRoot/sections/wikiOld/article.php";
}
