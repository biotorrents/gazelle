<?php

declare(strict_types=1);

$ENV = ENV::go();

# Prevent demotion on dev site
# (higher perms for testing)
if (!$ENV->dev) {
    $Query = $db->query('
    SELECT ID
    FROM users_main
    WHERE PermissionID IN('.POWER.', '.ELITE.', '.TORRENT_MASTER.')
      AND Uploaded / Downloaded < 0.95
      OR PermissionID IN('.POWER.', '.ELITE.', '.TORRENT_MASTER.')
      AND Uploaded < 25 * 1024 * 1024 * 1024');
    echo "demoted 1\n";

    $db->query('
    UPDATE users_main
    SET PermissionID = '.MEMBER.'
    WHERE PermissionID IN('.POWER.', '.ELITE.', '.TORRENT_MASTER.')
      AND Uploaded / Downloaded < 0.95
      OR PermissionID IN('.POWER.', '.ELITE.', '.TORRENT_MASTER.')
      AND Uploaded < 25 * 1024 * 1024 * 1024');
    $db->set_query_id($Query);

    while (list($UserID) = $db->next_record()) {
        $cache->begin_transaction("user_info_$UserID");
        $cache->update_row(false, array('PermissionID' => MEMBER));
        $cache->commit_transaction(2592000);
        $cache->delete_value("user_info_$UserID");
        $cache->delete_value("user_info_heavy_$UserID");
        Misc::send_pm($UserID, 0, 'You have been demoted to '.User::make_class_string(MEMBER), "You now only meet the requirements for the \"".User::make_class_string(MEMBER)."\" user class.\n\nTo read more about ".$ENV->siteName."'s user classes, read [url=".site_url()."wiki.php?action=article&amp;name=userclasses]this wiki article[/url].");
    }
    echo "demoted 2\n";

    $Query = $db->query('
    SELECT ID
    FROM users_main
    WHERE PermissionID IN('.MEMBER.', '.POWER.', '.ELITE.', '.TORRENT_MASTER.')
      AND Uploaded / Downloaded < 0.65');
    echo "demoted 3\n";

    $db->query('
    UPDATE users_main
    SET PermissionID = '.USER.'
    WHERE PermissionID IN('.MEMBER.', '.POWER.', '.ELITE.', '.TORRENT_MASTER.')
      AND Uploaded / Downloaded < 0.65');
    $db->set_query_id($Query);

    while (list($UserID) = $db->next_record()) {
        $cache->begin_transaction("user_info_$UserID");
        $cache->update_row(false, array('PermissionID' => USER));
        $cache->commit_transaction(2592000);
        $cache->delete_value("user_info_$UserID");
        $cache->delete_value("user_info_heavy_$UserID");
        Misc::send_pm($UserID, 0, 'You have been demoted to '.User::make_class_string(USER), "You now only meet the requirements for the \"".User::make_class_string(USER)."\" user class.\n\nTo read more about ".$ENV->siteName."'s user classes, read [url=".site_url()."wiki.php?action=article&amp;name=userclasses]this wiki article[/url].");
    }
    echo "demoted 4\n";
}
