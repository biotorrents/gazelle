<?php

#declare(strict_types=1);

$Skip = [];
$Skip[] = db_string($_GET['skip']);

$NotificationsManager = new NotificationsManager($app->user->core['id'], $Skip);
json_die('success', $NotificationsManager->get_notifications());
