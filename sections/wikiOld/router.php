<?php

declare(strict_types=1);

$ENV = \Gazelle\ENV::go();

if (!empty($_REQUEST['action'])) {
    switch ($_REQUEST['action']) {
        case 'create':
            if ($_POST['action']) {
                require_once "$ENV->serverRoot/sections/wikiOld/takecreate.php";
            } else {
                require_once "$ENV->serverRoot/sections/wikiOld/create.php";
            }
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
}
