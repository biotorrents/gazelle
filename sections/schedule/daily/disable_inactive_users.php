<?php
declare(strict_types=1);

/*
$ENV = ENV::go();

# The SQL query's lines below controls the notification clock
#   AND um.LastAccess < (NOW() - INTERVAL 110 DAY)
#   AND um.LastAccess > (NOW() - INTERVAL 111 DAY)

if (apcu_exists('DBKEY')) {
    # Send email
    $db->query("
    SELECT
      um.`Username`,
      um.`Email`
    FROM
      `users_info` AS ui
    JOIN `users_main` AS um
    ON
      um.`ID` = ui.`UserID`
    LEFT JOIN `users_levels` AS ul
    ON
      ul.`UserID` = um.`ID` AND ul.`PermissionID` = '".CELEB."'
    WHERE
      um.`PermissionID` IN('".USER."', '".MEMBER ."')
      AND um.`LastAccess` <(NOW() - INTERVAL 355 DAY)
      AND um.`LastAccess` >(NOW() - INTERVAL 356 DAY)
      AND um.`LastAccess` IS NOT NULL
      AND ui.`Donor` = '0'
      AND um.`Enabled` != '2'
      AND ul.`UserID` IS NULL
    GROUP BY
      um.`ID`
    ");

    while (list($Username, $Email) = $db->next_record()) {
        $Email = Crypto::decrypt($Email);
        $Body = "Hi $Username,\n\nIt has been almost a year since you used your account at ".site_url().". This is an automated email to inform you that your account will be disabled in 10 days if you do not sign in.";
        App::email($Email, "Your $ENV->siteName account is about to be disabled", $Body);
    }

    # The actual deletion clock
    #   AND um.LastAccess < (NOW() - INTERVAL 120 DAY)
    $db->query("
    SELECT
      um.`ID`
    FROM
      `users_info` AS ui
    JOIN `users_main` AS um
    ON
      um.`ID` = ui.`UserID`
    LEFT JOIN `users_levels` AS ul
    ON
      ul.`UserID` = um.`ID` AND ul.`PermissionID` = '".CELEB."'
    WHERE
      um.`PermissionID` IN('".USER."', '".MEMBER ."')
      AND um.`LastAccess` <(NOW() - INTERVAL 365 DAY)
      AND um.`LastAccess` IS NOT NULL
      AND ui.`Donor` = '0'
      AND um.`Enabled` != '2'
      AND ul.`UserID` IS NULL
    GROUP BY
      um.`ID`
    ");

    if ($db->has_results()) {
        Tools::disable_users($db->collect('ID'), 'Disabled for inactivity.', 3);
    }
}
*/
