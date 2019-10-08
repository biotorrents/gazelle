<?
//------------- Disable inactive user accounts --------------------------//

if (apcu_exists('DBKEY')) {
  // Send email
  $DB->query("
    SELECT um.Username, um.Email
    FROM users_info AS ui
      JOIN users_main AS um ON um.ID = ui.UserID
      LEFT JOIN users_levels AS ul ON ul.UserID = um.ID AND ul.PermissionID = '".CELEB."'
    WHERE um.PermissionID IN ('".USER."', '".MEMBER ."')
      AND um.LastAccess < (NOW() - INTERVAL 110 DAY)
      AND um.LastAccess > (NOW() - INTERVAL 111 DAY)
      AND um.LastAccess IS NOT NULL
      AND ui.Donor = '0'
      AND um.Enabled != '2'
      AND ul.UserID IS NULL
    GROUP BY um.ID");
  while (list($Username, $Email) = $DB->next_record()) {
    $Email = Crypto::decrypt($Email);
    $Body = "Hi $Username,\n\nIt has been almost 4 months since you used your account at ".site_url().". This is an automated email to inform you that your account will be disabled in 10 days if you do not sign in.";
    Misc::send_email($Email, 'Your '.SITE_NAME.' account is about to be disabled', $Body);
  }

  $DB->query("
    SELECT um.ID
    FROM users_info AS ui
      JOIN users_main AS um ON um.ID = ui.UserID
      LEFT JOIN users_levels AS ul ON ul.UserID = um.ID AND ul.PermissionID = '".CELEB."'
    WHERE um.PermissionID IN ('".USER."', '".MEMBER ."')
      AND um.LastAccess < (NOW() - INTERVAL 120 DAY)
      AND um.LastAccess IS NOT NULL
      AND ui.Donor = '0'
      AND um.Enabled != '2'
      AND ul.UserID IS NULL
    GROUP BY um.ID");
  if ($DB->has_results()) {
    Tools::disable_users($DB->collect('ID'), 'Disabled for inactivity.', 3);
  }
}
?>
