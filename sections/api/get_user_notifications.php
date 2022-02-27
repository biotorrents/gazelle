<?php
#declare(strict_types=1);

$Skip = [];
$Skip[] = db_string($_GET['skip']);

$NotificationsManager = new NotificationsManager($user['ID'], $Skip);
json_die('success', $NotificationsManager->get_notifications());
