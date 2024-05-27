<?php

declare(strict_types=1);


/**
 * user conversations
 */

$app = Gazelle\App::go();

$conversations = Gazelle\Conversations::getForUserId($app->user->core["id"]);
!d($conversations);exit;
