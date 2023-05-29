<?php

declare(strict_types=1);


/**
 * cycle auth keys
 */

$app = \Gazelle\App::go();

$query = "
    update users_info set
    authKey = md5(concat(authKey, rand(), ?, sha1(concat(rand(), rand(), ?))))
";
$app->dbNew->do($query, [ \Gazelle\Text::random(), \Gazelle\Text::random() ]);
