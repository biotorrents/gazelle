<?php

#declare(strict_types=1);

$app = \Gazelle\App::go();

// Are they being tricky blighters?
if (!$_POST['userid'] || !is_numeric($_POST['userid'])) {
    error(404);
} elseif (!check_perms('users_mod')) {
    error(403);
}
authorize();
// End checking for moronity

if (!apcu_exists('DBKEY')) {
    error('Decrypt database first');
}

$ENV = \Gazelle\ENV::go();
$UserID = $_POST['userid'];
$DeleteKeys = false;

// Variables for database input
$Class = (int)$_POST['Class'];
$Username = db_string($_POST['Username']);
$Title = db_string($_POST['Title']);
$AdminComment = db_string($_POST['AdminComment']);
$Donor = isset($_POST['Donor']) ? 1 : 0;
$Artist = isset($_POST['Artist']) ? 1 : 0;

$SecondaryClasses = isset($_POST['secondary_classes']) ? $_POST['secondary_classes'] : [];
foreach ($SecondaryClasses as $i => $Val) {
    if (!is_numeric($Val)) {
        unset($SecondaryClasses[$i]);
    }
}

$Visible = isset($_POST['Visible']) ? 1 : 0;
$Invites = (int)$_POST['Invites'];
$SupportFor = db_string($_POST['SupportFor']);
$Pass = $_POST['ChangePassword'];
$Warned = isset($_POST['Warned']) ? 1 : 0;

if (isset($_POST['Uploaded']) && isset($_POST['Downloaded'])) {
    $Uploaded = ($_POST['Uploaded'] === '' ? 0 : $_POST['Uploaded']);
    if ($Arithmetic = strpbrk($Uploaded, '+-')) {
        $Uploaded += max(-$Uploaded, Format::get_bytes($Arithmetic));
    }

    $Downloaded = ($_POST['Downloaded'] === '' ? 0 : $_POST['Downloaded']);
    if ($Arithmetic = strpbrk($Downloaded, '+-')) {
        $Downloaded += max(-$Downloaded, Format::get_bytes($Arithmetic));
    }

    if (!is_numeric($Uploaded) || !is_numeric($Downloaded)) {
        error(0);
    }
}

$BonusPoints = isset($_POST['BonusPoints']) ? $_POST['BonusPoints'] : 0;
if (!is_numeric($BonusPoints)) {
    error(0);
}

$FLTokens = isset($_POST['FLTokens']) ? $_POST['FLTokens'] : 0;
if (!is_numeric($FLTokens)) {
    error(0);
}

$Badges = isset($_POST['badges']) ? $_POST['badges'] : [];

$WarnLength = (int)$_POST['WarnLength'];
$ExtendWarning = (int)$_POST['ExtendWarning'];
$ReduceWarning = (int)$_POST['ReduceWarning'];
$WarnReason = $_POST['WarnReason'];
$UserReason = $_POST['UserReason'];

$DisableAvatar = isset($_POST['DisableAvatar']) ? 1 : 0;
$DisableInvites = isset($_POST['DisableInvites']) ? 1 : 0;
$DisablePosting = isset($_POST['DisablePosting']) ? 1 : 0;
$DisableForums = isset($_POST['DisableForums']) ? 1 : 0;
$DisableTagging = isset($_POST['DisableTagging']) ? 1 : 0;
$DisableUpload = isset($_POST['DisableUpload']) ? 1 : 0;
$DisableWiki = isset($_POST['DisableWiki']) ? 1 : 0;
$DisablePM = isset($_POST['DisablePM']) ? 1 : 0;
$DisablePoints = isset($_POST['DisablePoints']) ? 1 : 0;
$DisablePromotion = isset($_POST['DisablePromotion']) ? 1 : 0;
$DisableIRC = isset($_POST['DisableIRC']) ? 1 : 0;
$DisableRequests = isset($_POST['DisableRequests']) ? 1 : 0;
$DisableLeech = isset($_POST['DisableLeech']) ? 0 : 1;

$LockedAccount = isset($_POST['LockAccount']) ? 1 : 0;
$LockType = $_POST['LockReason'];

$RestrictedForums = db_string(trim($_POST['RestrictedForums']));
$PermittedForums = db_string(trim($_POST['PermittedForums']));
$EnableUser = (int) $_POST['UserStatus'];
$ResetRatioWatch = isset($_POST['ResetRatioWatch']) ? 1 : 0;
$ResetPasskey = isset($_POST['ResetPasskey']) ? 1 : 0;
$ResetAuthkey = isset($_POST['ResetAuthkey']) ? 1 : 0;

$SendHackedMail = isset($_POST['SendHackedMail']) ? 1 : 0;
if ($SendHackedMail && !empty($_POST['HackedEmail'])) {
    $HackedEmail = $_POST['HackedEmail'];
} else {
    $SendHackedMail = false;
}

$MergeStatsFrom = db_string($_POST['MergeStatsFrom']);
$Reason = db_string($_POST['Reason']);
$HeavyUpdates = [];
$LightUpdates = [];

// Get user info from the database
$app->dbOld->query("
  SELECT
    m.Username,
    m.IP,
    m.Email,
    m.PermissionID,
    p.Level AS Class,
    m.Title,
    m.Enabled,
    m.Uploaded,
    m.Downloaded,
    m.Invites,
    m.can_leech,
    m.Visible,
    i.AdminComment,
    m.torrent_pass,
    i.Donor,
    i.Artist,
    i.Warned,
    i.SupportFor,
    i.RestrictedForums,
    i.PermittedForums,
    DisableAvatar,
    DisableInvites,
    DisablePosting,
    DisableForums,
    DisableTagging,
    DisableUpload,
    DisableWiki,
    DisablePM,
    DisablePoints,
    DisablePromotion,
    DisableIRC,
    DisableRequests,
    m.RequiredRatio,
    m.FLTokens,
    m.BonusPoints,
    i.RatioWatchEnds,
    la.Type,
    SHA1(i.AdminComment) AS CommentHash,
    GROUP_CONCAT(l.PermissionID SEPARATOR ',') AS SecondaryClasses
  FROM users_main AS m
    JOIN users_info AS i ON i.UserID = m.ID
    LEFT JOIN permissions AS p ON p.ID = m.PermissionID
    LEFT JOIN users_levels AS l ON l.UserID = m.ID
    LEFT JOIN locked_accounts AS la ON la.UserID = m.ID
  WHERE m.ID = $UserID
  GROUP BY m.ID");

if (!$app->dbOld->has_results()) { // If user doesn't exist
    Http::redirect("log.php?search=User+$UserID");
}

$Cur = $app->dbOld->next_record(MYSQLI_ASSOC, false);
if ($_POST['comment_hash'] != $Cur['CommentHash']) {
    error("Somebody else has moderated this user since you loaded it. Please go back and refresh the page.");
}

// NOW that we know the class of the current user, we can see if one staff member is trying to hax0r us
if (!check_perms('users_mod', $Cur['Class'])) {
    // Son of a fucking bitch
    error(403);
    error();
}

// If we're deleting the user, we can ignore all the other crap
if ($_POST['UserStatus'] === 'delete' && check_perms('users_delete_users')) {
    Misc::write_log("User account $UserID (" . $Cur['Username'] . ") was deleted by " . $app->user->core['username']);

    $app->dbOld->query("
      DELETE FROM users_main
      WHERE id = $UserID");

    $app->dbOld->query("
      DELETE FROM users_info
      WHERE UserID = $UserID");

    $app->cache->delete("user_info_$UserID");
    Tracker::update_tracker('remove_user', array('passkey' => $Cur['torrent_pass']));

    Http::redirect("log.php?search=User+$UserID");
    error();
}

// User was not deleted. Perform other stuff.
$UpdateSet = [];
$EditSummary = [];
$TrackerUserUpdates = array('passkey' => $Cur['torrent_pass']);

$QueryID = $app->dbOld->get_query_id();

if ($LockType == '---' || $LockedAccount == 0) {
    if ($Cur['Type']) {
        $app->dbOld->query("DELETE FROM locked_accounts WHERE UserID = '" . $UserID . "'");
        $EditSummary[] = 'Account unlocked';
        $app->cache->delete('user_' . $Cur['torrent_pass']);
    }
} elseif (!$Cur['Type'] || $Cur['Type'] != $LockType) {
    $app->dbOld->query("INSERT INTO locked_accounts (UserID, Type)
                VALUES ('" . $UserID . "', '" . $LockType . "')
                ON DUPLICATE KEY UPDATE Type = '" . $LockType . "'");
    $app->cache->delete('user_' . $Cur['torrent_pass']);

    if ($Cur['Type'] != $LockType) {
        $EditSummary[] = 'Account lock reason changed to ' . $LockType;
    } else {
        $EditSummary[] = 'Account locked (' . $LockType . ')';
    }
}

$app->cache->delete("user_info_" . $UserID);
$app->dbOld->set_query_id($QueryID);

if ($_POST['ResetRatioWatch'] && check_perms('users_edit_reset_keys')) {
    $app->dbOld->query("
      UPDATE users_info
      SET RatioWatchEnds = NULL, RatioWatchDownload = '0', RatioWatchTimes = '0'
      WHERE UserID = '$UserID'");
    $EditSummary[] = 'RatioWatch history reset';
}

if ($_POST['ResetSnatchList'] && check_perms('users_edit_reset_keys')) {
    $app->dbOld->query("
      DELETE FROM xbt_snatched
      WHERE uid = '$UserID'");
    $EditSummary[] = 'Snatch list cleared';
    $app->cache->delete("recent_snatches_$UserID");
}

if ($_POST['ResetDownloadList'] && check_perms('users_edit_reset_keys')) {
    $app->dbOld->query("
      DELETE FROM users_downloads
      WHERE UserID = '$UserID'");
    $EditSummary[] = 'Download list cleared';
}

if (($_POST['ResetSession'] || $_POST['LogOut']) && check_perms('users_logout')) {
    $app->cache->delete("user_info_$UserID");
    $app->cache->delete("user_info_heavy_$UserID");
    $app->cache->delete("user_stats_$UserID");
    $app->cache->delete("enabled_$UserID");

    if ($_POST['LogOut']) {
        $app->dbOld->query("
          SELECT SessionID
          FROM users_sessions
          WHERE UserID = '$UserID'");

        while (list($SessionID) = $app->dbOld->next_record()) {
            $app->cache->delete("session_{$UserID}_$SessionID");
        }
        $app->cache->delete("users_sessions_$UserID");

        $app->dbOld->query("
          DELETE FROM users_sessions
          WHERE UserID = '$UserID'");
    }
}

// Start building SQL query and edit summary
if ($Classes[$Class]['Level'] != $Cur['Class']
  && (
      ($Classes[$Class]['Level'] < $app->user->extra['Class'] && check_perms('users_promote_below', $Cur['Class']))
    || ($Classes[$Class]['Level'] <= $app->user->extra['Class'] && check_perms('users_promote_to', $Cur['Class'] - 1))
  )
) {
    $UpdateSet[] = "PermissionID = '$Class'";
    $EditSummary[] = 'class changed to ' . $Class;
    $LightUpdates['PermissionID'] = $Class;
    $DeleteKeys = true;

    $app->dbOld->query("
      SELECT DISTINCT DisplayStaff
      FROM permissions
      WHERE ID = $Class
        OR ID = " . $ClassLevels[$Cur['Class']]['ID']);

    if ($app->dbOld->record_count() === 2) {
        if ($Classes[$Class]['Level'] < $Cur['Class']) {
            $SupportFor = '';
        }
        $ClearStaffIDCache = true;
    }
    $app->cache->delete("donor_info_$UserID");
}

if ($Username != $Cur['Username'] && check_perms('users_edit_usernames', $Cur['Class'] - 1)) {
    $app->dbOld->query("
      SELECT ID
      FROM users_main
      WHERE Username = '$Username'");

    if ($app->dbOld->next_record() > 0) {
        list($UsedUsernameID) = $app->dbOld->next_record();
        error("Username already in use by <a href=\"user.php?id=$UsedUsernameID\">$Username</a>");
        Http::redirect("user.php?id=$UserID");
        error();
    } elseif ($Username == '0' || $Username == '1') {
        error('You cannot set a username of "0" or "1".');
        Http::redirect("user.php?id=$UserID");
        error();
    } else {
        $UpdateSet[] = "Username = '$Username'";
        $EditSummary[] = "username changed from " . $Cur['Username'] . " to $Username";
        $LightUpdates['Username'] = $Username;
    }
}

if ($Title != db_string($Cur['Title']) && check_perms('users_edit_titles')) {
    // Using the unescaped value for the test to avoid confusion
    if (strlen($_POST['Title']) > 1024) {
        error("Custom titles have a maximum length of 1,024 characters.");
        Http::redirect("user.php?id=$UserID");
        error();
    } else {
        $UpdateSet[] = "Title = '$Title'";
        $EditSummary[] = "title changed to [code]{$Title}[/code]";
        $LightUpdates['Title'] = $_POST['Title'];
    }
}

if ($Donor != $Cur['Donor'] && check_perms('users_give_donor')) {
    $UpdateSet[] = "Donor = '$Donor'";
    $EditSummary[] = 'donor status changed';
    $LightUpdates['Donor'] = $Donor;
}

// Secondary classes
$OldClasses = $Cur['SecondaryClasses'] ? explode(',', $Cur['SecondaryClasses']) : [];
$DroppedClasses = array_diff($OldClasses, $SecondaryClasses);
$AddedClasses   = array_diff($SecondaryClasses, $OldClasses);

if (count($DroppedClasses) > 0) {
    $ClassChanges = [];
    foreach ($DroppedClasses as $PermID) {
        $ClassChanges[] = $Classes[$PermID]['Name'];
    }

    $EditSummary[] = 'Secondary classes dropped: ' . implode(', ', $ClassChanges);
    $app->dbOld->query("
      DELETE FROM users_levels
      WHERE UserID = '$UserID'
        AND PermissionID IN (" . implode(',', $DroppedClasses) . ')');

    if (count($SecondaryClasses) > 0) {
        $LightUpdates['ExtraClasses'] = array_fill_keys($SecondaryClasses, 1);
    } else {
        $LightUpdates['ExtraClasses'] = [];
    }
    $DeleteKeys = true;
}

if (count($AddedClasses) > 0) {
    $ClassChanges = [];
    foreach ($AddedClasses as $PermID) {
        $ClassChanges[] = $Classes[$PermID]['Name'];
    }

    $EditSummary[] = "Secondary classes added: " . implode(', ', $ClassChanges);
    $Values = [];

    foreach ($AddedClasses as $PermID) {
        $Values[] = "($UserID, $PermID)";
    }

    $app->dbOld->query("
      INSERT INTO users_levels (UserID, PermissionID)
      VALUES " . implode(', ', $Values));

    //$LightUpdates['ExtraClasses'] = array_fill_keys($SecondaryClasses, 1);
    $DeleteKeys = true;
}

if ($Visible != $Cur['Visible'] && check_perms('users_make_invisible')) {
    $UpdateSet[] = "Visible = '$Visible'";
    $EditSummary[] = 'visibility changed';
    $LightUpdates['Visible'] = $Visible;
    $TrackerUserUpdates['visible'] = $Visible;
}

if ($Uploaded != $Cur['Uploaded'] && $Uploaded != $_POST['OldUploaded'] && (check_perms('users_edit_ratio')
  || (check_perms('users_edit_own_ratio') && $UserID == $app->user->core['id']))) {
    $UpdateSet[] = "Uploaded = '$Uploaded'";
    $EditSummary[] = "uploaded changed from " . Format::get_size($Cur['Uploaded']) . ' to ' . Format::get_size($Uploaded);
    $app->cache->delete("user_stats_$UserID");
}

if ($Downloaded != $Cur['Downloaded'] && $Downloaded != $_POST['OldDownloaded'] && (check_perms('users_edit_ratio')
  || (check_perms('users_edit_own_ratio') && $UserID == $app->user->core['id']))) {
    $UpdateSet[] = "Downloaded = '$Downloaded'";
    $EditSummary[] = "downloaded changed from " . Format::get_size($Cur['Downloaded']) . ' to ' . Format::get_size($Downloaded);
    $app->cache->delete("user_stats_$UserID");
}

if ($BonusPoints != $Cur['BonusPoints'] && (check_perms('users_edit_ratio') || (check_perms('users_edit_own_ratio') && $UserID == $app->user->core['id']))) {
    $UpdateSet[] = "BonusPoints = $BonusPoints";
    $EditSummary[] = "Bonus Points changed from " . $Cur['BonusPoints'] . " to $BonusPoints";
    $HeavyUpdates['BonusPoints'] = $BonusPoints;
}

if ($FLTokens != $Cur['FLTokens'] && (check_perms('users_edit_ratio') || (check_perms('users_edit_own_ratio') && $UserID == $app->user->core['id']))) {
    $UpdateSet[] = "FLTokens = $FLTokens";
    $EditSummary[] = "Freeleech Tokens changed from " . $Cur['FLTokens'] . " to $FLTokens";
    $HeavyUpdates['FLTokens'] = $FLTokens;
}

if ($Invites != $Cur['Invites'] && check_perms('users_edit_invites')) {
    $UpdateSet[] = "invites = '$Invites'";
    $EditSummary[] = "number of invites changed to $Invites";
    $HeavyUpdates['Invites'] = $Invites;
}

if (check_perms('users_edit_badges')) {
    $query = "DELETE FROM users_badges WHERE UserID = $UserID";
    if (!empty($Badges)) {
        $query .= " AND BadgeID NOT IN (" . implode(',', $Badges) . ")";
    }
    $app->dbOld->query($query);

    if (!empty($Badges)) {
        $query = "INSERT IGNORE INTO users_badges (UserID, BadgeID) VALUES ";
        $len = count($Badges);
        foreach ($Badges as $i => $BadgeID) {
            $query .= "($UserID, $BadgeID)";
            if ($i < ($len - 1)) {
                $query .= ", ";
            }
        }
        $app->dbOld->query($query);
    }

    $app->cache->delete("user_badges_" . $UserID);
}

if ($Warned == 1 && !$Cur['Warned'] && check_perms('users_warn')) {
    $Weeks = 'week' . ($WarnLength === 1 ? '' : 's');
    Misc::send_pm($UserID, 0, 'You have received a warning', "You have been [url=" . site_url() . "wiki.php?action=article&amp;name=warnings]warned for $WarnLength {$Weeks}[/url] by [user]" . $app->user->core['username'] . "[/user]. The reason given was:
[quote]{$WarnReason}[/quote]");
    $UpdateSet[] = "Warned = NOW() + INTERVAL $WarnLength WEEK";
    $Msg = "warned for $WarnLength $Weeks";

    if ($WarnReason) {
        $Msg .= " for \"$WarnReason\"";
    }

    $EditSummary[] = db_string($Msg);
    $LightUpdates['Warned'] = time_plus(3600 * 24 * 7 * $WarnLength);
} elseif ($Warned == 0 && $Cur['Warned'] && check_perms('users_warn')) {
    $UpdateSet[] = "Warned = NULL";
    $EditSummary[] = 'warning removed';
    $LightUpdates['Warned'] = null;
} elseif ($Warned == 1 && $ExtendWarning != '---' && check_perms('users_warn')) {
    $Weeks = 'week' . ($ExtendWarning === 1 ? '' : 's');
    Misc::send_pm($UserID, 0, 'Your warning has been extended', "Your warning has been extended by $ExtendWarning $Weeks by [user]" . $app->user->core['username'] . "[/user]. The reason given was:
[quote]{$WarnReason}[/quote]");

    $UpdateSet[] = "Warned = Warned + INTERVAL $ExtendWarning WEEK";
    $app->dbOld->query("
      SELECT Warned + INTERVAL $ExtendWarning WEEK
      FROM users_info
      WHERE UserID = '$UserID'");

    list($WarnedUntil) = $app->dbOld->next_record();
    $Msg = "warning extended by $ExtendWarning $Weeks to $WarnedUntil";

    if ($WarnReason) {
        $Msg .= " for \"$WarnReason\"";
    }

    $EditSummary[] = db_string($Msg);
    $LightUpdates['Warned'] = $WarnedUntil;
} elseif ($Warned == 1 && $ExtendWarning == '---' && $ReduceWarning != '---' && check_perms('users_warn')) {
    $Weeks = 'week' . ($ReduceWarning === 1 ? '' : 's');
    Misc::send_pm($UserID, 0, 'Your warning has been reduced', "Your warning has been reduced by $ReduceWarning $Weeks by [user]" . $app->user->core['username'] . "[/user]. The reason given was:
[quote]{$WarnReason}[/quote]");
    $UpdateSet[] = "Warned = Warned - INTERVAL $ReduceWarning WEEK";
    $app->dbOld->query("
      SELECT Warned - INTERVAL $ReduceWarning WEEK
      FROM users_info
      WHERE UserID = '$UserID'");

    list($WarnedUntil) = $app->dbOld->next_record();
    $Msg = "warning reduced by $ReduceWarning $Weeks to $WarnedUntil";

    if ($WarnReason) {
        $Msg .= " for \"$WarnReason\"";
    }

    $EditSummary[] = db_string($Msg);
    $LightUpdates['Warned'] = $WarnedUntil;
}

if ($SupportFor != db_string($Cur['SupportFor']) && (check_perms('admin_manage_fls') || (check_perms('users_mod') && $UserID == $app->user->core['id']))) {
    $UpdateSet[] = "SupportFor = '$SupportFor'";
    $EditSummary[] = "First-Line Support status changed to \"$SupportFor\"";
}

if ($RestrictedForums != db_string($Cur['RestrictedForums']) && check_perms('users_mod')) {
    $UpdateSet[] = "RestrictedForums = '$RestrictedForums'";
    $EditSummary[] = "restricted forum(s): $RestrictedForums";
    $DeleteKeys = true;
}

if ($PermittedForums != db_string($Cur['PermittedForums']) && check_perms('users_mod')) {
    $ForumSet = explode(',', $PermittedForums);
    $ForumList = [];

    foreach ($ForumSet as $ForumID) {
        if ($Forums[$ForumID]['MinClassCreate'] <= $app->user->extra['EffectiveClass']) {
            $ForumList[] = $ForumID;
        }
    }

    $PermittedForums = implode(',', $ForumSet);
    $UpdateSet[] = "PermittedForums = '$PermittedForums'";
    $EditSummary[] = "permitted forum(s): $PermittedForums";
    $DeleteKeys = true;
}

if ($DisableAvatar != $Cur['DisableAvatar'] && check_perms('users_disable_any')) {
    $UpdateSet[] = "DisableAvatar = '$DisableAvatar'";
    $EditSummary[] = 'avatar privileges ' . ($DisableAvatar ? 'disabled' : 'enabled');
    $HeavyUpdates['DisableAvatar'] = $DisableAvatar;

    if (!empty($UserReason)) {
        Misc::send_pm($UserID, 0, 'Your avatar privileges have been disabled', "Your avatar privileges have been disabled. The reason given was: [quote]{$UserReason}[/quote] If you would like to discuss this, please join " . DISABLED_CHAN . ' on our IRC network. Instructions can be found [url=' . site_url() . 'wiki.php?action=article&amp;name=IRC+-+How+to+join]here[/url].');
    }
}

if ($DisableLeech != $Cur['can_leech'] && check_perms('users_disable_any')) {
    $UpdateSet[] = "can_leech = '$DisableLeech'";
    $EditSummary[] = "leeching status changed (" . translateLeechStatus($Cur['can_leech']) . " -> " . translateLeechStatus($DisableLeech) . ")";
    $HeavyUpdates['DisableLeech'] = $DisableLeech;
    $HeavyUpdates['CanLeech'] = $DisableLeech;

    if (!empty($UserReason)) {
        Misc::send_pm($UserID, 0, 'Your leeching privileges have been disabled', "Your leeching privileges have been disabled. The reason given was: [quote]{$UserReason}[/quote] If you would like to discuss this, please join " . DISABLED_CHAN . ' on our IRC network. Instructions can be found [url=' . site_url() . 'wiki.php?action=article&amp;name=IRC+-+How+to+join]here[/url].');
    }
    $TrackerUserUpdates['can_leech'] = $DisableLeech;
}

if ($DisableInvites != $Cur['DisableInvites'] && check_perms('users_disable_any')) {
    $UpdateSet[] = "DisableInvites = '$DisableInvites'";
    if ($DisableInvites == 1) {
        //$UpdateSet[] = "Invites = '0'";
        if (!empty($UserReason)) {
            Misc::send_pm($UserID, 0, 'Your invite privileges have been disabled', "Your invite privileges have been disabled. The reason given was: [quote]{$UserReason}[/quote] If you would like to discuss this, please join " . DISABLED_CHAN . ' on our IRC network. Instructions can be found [url=' . site_url() . 'wiki.php?action=article&amp;name=IRC+-+How+to+join]here[/url].');
        }
    }

    $EditSummary[] = 'invites privileges ' . ($DisableInvites ? 'disabled' : 'enabled');
    $HeavyUpdates['DisableInvites'] = $DisableInvites;
}

if ($DisablePosting != $Cur['DisablePosting'] && check_perms('users_disable_posts')) {
    $UpdateSet[] = "DisablePosting = '$DisablePosting'";
    $EditSummary[] = 'posting privileges ' . ($DisablePosting ? 'disabled' : 'enabled');
    $HeavyUpdates['DisablePosting'] = $DisablePosting;

    if (!empty($UserReason)) {
        Misc::send_pm($UserID, 0, 'Your forum posting privileges have been disabled', "Your forum posting privileges have been disabled. The reason given was: [quote]{$UserReason}[/quote] If you would like to discuss this, please join " . DISABLED_CHAN . ' on our IRC network. Instructions can be found [url=' . site_url() . 'wiki.php?action=article&amp;name=IRC+-+How+to+join]here[/url].');
    }
}

if ($DisableForums != $Cur['DisableForums'] && check_perms('users_disable_posts')) {
    $UpdateSet[] = "DisableForums = '$DisableForums'";
    $EditSummary[] = 'forums privileges ' . ($DisableForums ? 'disabled' : 'enabled');
    $HeavyUpdates['DisableForums'] = $DisableForums;

    if (!empty($UserReason)) {
        Misc::send_pm($UserID, 0, 'Your forum privileges have been disabled', "Your forum privileges have been disabled. The reason given was: [quote]{$UserReason}[/quote] If you would like to discuss this, please join " . DISABLED_CHAN . ' on our IRC network. Instructions can be found [url=' . site_url() . 'wiki.php?action=article&amp;name=IRC+-+How+to+join]here[/url].');
    }
}

if ($DisableTagging != $Cur['DisableTagging'] && check_perms('users_disable_any')) {
    $UpdateSet[] = "DisableTagging = '$DisableTagging'";
    $EditSummary[] = 'tagging privileges ' . ($DisableTagging ? 'disabled' : 'enabled');
    $HeavyUpdates['DisableTagging'] = $DisableTagging;

    if (!empty($UserReason)) {
        Misc::send_pm($UserID, 0, 'Your tagging privileges have been disabled', "Your tagging privileges have been disabled. The reason given was: [quote]{$UserReason}[/quote] If you would like to discuss this, please join " . DISABLED_CHAN . ' on our IRC network. Instructions can be found [url=' . site_url() . 'wiki.php?action=article&amp;name=IRC+-+How+to+join]here[/url].');
    }
}

if ($DisableUpload != $Cur['DisableUpload'] && check_perms('users_disable_any')) {
    $UpdateSet[] = "DisableUpload = '$DisableUpload'";
    $EditSummary[] = 'upload privileges ' . ($DisableUpload ? 'disabled' : 'enabled');
    $HeavyUpdates['DisableUpload'] = $DisableUpload;

    if ($DisableUpload == 1) {
        Misc::send_pm($UserID, 0, 'Your upload privileges have been disabled', "Your upload privileges have been disabled. The reason given was: [quote]{$UserReason}[/quote] If you would like to discuss this, please join " . DISABLED_CHAN . ' on our IRC network. Instructions can be found [url=' . site_url() . 'wiki.php?action=article&amp;name=IRC+-+How+to+join]here[/url].');
    }
}

if ($DisableWiki != $Cur['DisableWiki'] && check_perms('users_disable_any')) {
    $UpdateSet[] = "DisableWiki = '$DisableWiki'";
    $EditSummary[] = 'wiki privileges ' . ($DisableWiki ? 'disabled' : 'enabled');
    $HeavyUpdates['DisableWiki'] = $DisableWiki;
    $HeavyUpdates['site_edit_wiki'] = 0;

    if (!empty($UserReason)) {
        Misc::send_pm($UserID, 0, 'Your site editing privileges have been disabled', "Your site editing privileges have been disabled. The reason given was: [quote]{$UserReason}[/quote] If you would like to discuss this, please join " . DISABLED_CHAN . ' on our IRC network. Instructions can be found [url=' . site_url() . 'wiki.php?action=article&amp;name=IRC+-+How+to+join]here[/url].');
    }
}

if ($DisablePM != $Cur['DisablePM'] && check_perms('users_disable_any')) {
    $UpdateSet[] = "DisablePM = '$DisablePM'";
    $EditSummary[] = 'PM privileges ' . ($DisablePM ? 'disabled' : 'enabled');
    $HeavyUpdates['DisablePM'] = $DisablePM;

    if (!empty($UserReason)) {
        Misc::send_pm($UserID, 0, 'Your PM privileges have been disabled', "Your PM privileges have been disabled. The reason given was: [quote]{$UserReason}[/quote] If you would like to discuss this, please join " . DISABLED_CHAN . ' on our IRC network. Instructions can be found [url=' . site_url() . 'wiki.php?action=article&amp;name=IRC+-+How+to+join]here[/url].');
    }
}

if ($DisablePoints != $Cur['DisablePoints'] && check_perms('users_disable_any')) {
    $UpdateSet[] = "DisablePoints = '$DisablePoints'";
    $EditSummary[] = bonusPoints . ' earning ' . ($DisablePoints ? 'disabled' : 'enabled');
    $HeavyUpdates['DisablePoints'] = $DisablePoints;

    if (!empty($UserReason)) {
        Misc::send_pm($UserID, 0, 'Your ' . bonusPoints . '-earning ability has been disabled', "Your " . bonusPoints . "-earning ability has been disabled. The reason given was: [quote]{$UserReason}[/quote] If you would like to discuss this, please join " . DISABLED_CHAN . ' on our IRC network. Instructions can be found [url=' . site_url() . 'wiki.php?action=article&amp;name=IRC+-+How+to+join]here[/url].');
    }
}

if ($DisablePromotion != $Cur['DisablePromotion'] && check_perms('users_disable_any')) {
    $UpdateSet[] = "DisablePromotion = '$DisablePromotion'";
    $EditSummary[] = 'Class purchasing ' . ($DisablePromotion ? 'disabled' : 'enabled');
    $HeavyUpdates['DisablePromotion'] = $DisablePromotion;

    if (!empty($UserReason)) {
        Misc::send_pm($UserID, 0, 'Your promotion purchasing ability has been disabled', "Your promotion purchasing ability has been disabled. The reason given was: [quote]{$UserReason}[/quote] If you would like to discuss this, please join " . DISABLED_CHAN . ' on our IRC network. Instructions can be found [url=' . site_url() . 'wiki.php?action=article&amp;name=IRC+-+How+to+join]here[/url].');
    }
}

if ($DisableIRC != $Cur['DisableIRC'] && check_perms('users_disable_any')) {
    $UpdateSet[] = "DisableIRC = '$DisableIRC'";
    $EditSummary[] = 'IRC privileges ' . ($DisableIRC ? 'disabled' : 'enabled');
    $HeavyUpdates['DisableIRC'] = $DisableIRC;

    if (!empty($UserReason)) {
        Misc::send_pm($UserID, 0, 'Your IRC privileges have been disabled', "Your IRC privileges have been disabled. The reason given was: [quote]{$UserReason}[/quote] If you would like to discuss this, please join " . DISABLED_CHAN . ' on our IRC network. Instructions can be found [url=' . site_url() . 'wiki.php?action=article&amp;name=IRC+-+How+to+join]here[/url]. This loss of privileges does not affect the ability to join and talk to staff in ' . DISABLED_CHAN . '.');
    }
}

if ($DisableRequests != $Cur['DisableRequests'] && check_perms('users_disable_any')) {
    $UpdateSet[] = "DisableRequests = '$DisableRequests'";
    $EditSummary[] = 'request privileges ' . ($DisableRequests ? 'disabled' : 'enabled');
    $HeavyUpdates['DisableRequests'] = $DisableRequests;

    if (!empty($UserReason)) {
        Misc::send_pm($UserID, 0, 'Your request privileges have been disabled', "Your request privileges have been disabled. The reason given was: [quote]{$UserReason}[/quote] If you would like to discuss this, please join " . DISABLED_CHAN . ' on our IRC network. Instructions can be found [url=' . site_url() . 'wiki.php?action=article&amp;name=IRC+-+How+to+join]here[/url].');
    }
}

if ($EnableUser != $Cur['Enabled'] && check_perms('users_disable_users')) {
    $EnableStr = 'account ' . translateUserStatus($Cur['Enabled']) . '->' . translateUserStatus($EnableUser);
    if ($EnableUser == '2') {
        Tools::disable_users($UserID, '', 1);
        $TrackerUserUpdates = [];
    } elseif ($EnableUser == '1') {
        $app->cache->increment('stats_user_count');
        $VisibleTrIP = ($Visible && \Gazelle\Crypto::decrypt($Cur['IP']) != '127.0.0.1') ? '1' : '0';
        Tracker::update_tracker('add_user', array('id' => $UserID, 'passkey' => $Cur['torrent_pass'], 'visible' => $VisibleTrIP));

        if (($Cur['Downloaded'] == 0) || ($Cur['Uploaded'] / $Cur['Downloaded'] >= $Cur['RequiredRatio'])) {
            $UpdateSet[] = "i.RatioWatchEnds = NULL";
            $CanLeech = 1;
            $UpdateSet[] = "m.can_leech = '1'";
            $UpdateSet[] = "i.RatioWatchDownload = '0'";
        } else {
            $EnableStr .= ' (Ratio: ' . Format::get_ratio_html($Cur['Uploaded'], $Cur['Downloaded'], false) . ', RR: ' . \Gazelle\Text::float($Cur['RequiredRatio'], 2) . ')';
            if ($Cur['RatioWatchEnds']) {
                $UpdateSet[] = "i.RatioWatchEnds = NOW()";
                $UpdateSet[] = "i.RatioWatchDownload = m.Downloaded";
                $CanLeech = 0;
            }
            $TrackerUserUpdates['can_leech'] = 0;
        }

        $UpdateSet[] = "i.BanReason = '0'";
        $UpdateSet[] = "Enabled = '1'";
        $LightUpdates['Enabled'] = 1;
    }
    $EditSummary[] = $EnableStr;
    $app->cache->set("enabled_$UserID", $EnableUser, 0);
}

if ($ResetPasskey == 1 && check_perms('users_edit_reset_keys')) {
    $Passkey = db_string(\Gazelle\Text::random());
    $UpdateSet[] = "torrent_pass = '$Passkey'";
    $EditSummary[] = 'passkey reset';
    $HeavyUpdates['torrent_pass'] = $Passkey;
    $TrackerUserUpdates['passkey'] = $Passkey;
    $app->cache->delete('user_' . $Cur['torrent_pass']);
    // MUST come after the case for updating can_leech
    Tracker::update_tracker('change_passkey', array('oldpasskey' => $Cur['torrent_pass'], 'newpasskey' => $Passkey));
}

if ($ResetAuthkey == 1 && check_perms('users_edit_reset_keys')) {
    $Authkey = db_string(\Gazelle\Text::random());
    $UpdateSet[] = "AuthKey = '$Authkey'";
    $EditSummary[] = 'authkey reset';
    $HeavyUpdates['AuthKey'] = $Authkey;
}

if ($SendHackedMail && check_perms('users_disable_any')) {
    $EditSummary[] = "hacked account email sent to $HackedEmail";
    $app->email($HackedEmail, "Your $ENV->siteName account", "Your $ENV->siteName account appears to have been compromised. As a security measure, we have disabled your account. To resolve this, please visit us on Slack.");
}

if ($MergeStatsFrom && check_perms('users_edit_ratio')) {
    $app->dbOld->query("
      SELECT ID, Uploaded, Downloaded
      FROM users_main
      WHERE Username LIKE '$MergeStatsFrom'");

    if ($app->dbOld->has_results()) {
        list($MergeID, $MergeUploaded, $MergeDownloaded) = $app->dbOld->next_record();
        $app->dbOld->query("
          UPDATE users_main AS um
            JOIN users_info AS ui ON um.ID = ui.UserID
          SET
            um.Uploaded = 0,
            um.Downloaded = 0,
            ui.AdminComment = CONCAT('" . sqltime() . ' - Stats (Uploaded: ' . Format::get_size($MergeUploaded) . ', Downloaded: ' . Format::get_size($MergeDownloaded) . ', Ratio: ' . Format::get_ratio($MergeUploaded, $MergeDownloaded) . ') merged into ' . site_url() . "user.php?id=$UserID (" . $Cur['Username'] . ') by ' . $app->user->core['username'] . "\n\n', ui.AdminComment)
          WHERE ID = $MergeID");

        $UpdateSet[] = "Uploaded = Uploaded + '$MergeUploaded'";
        $UpdateSet[] = "Downloaded = Downloaded + '$MergeDownloaded'";
        $EditSummary[] = 'stats merged from ' . site_url() . "user.php?id=$MergeID ($MergeStatsFrom) (previous stats: Uploaded: " . Format::get_size($Cur['Uploaded']) . ', Downloaded: ' . Format::get_size($Cur['Downloaded']) . ', Ratio: ' . Format::get_ratio($Cur['Uploaded'], $Cur['Downloaded']) . ')';
        $app->cache->delete("user_stats_$UserID");
        $app->cache->delete("user_stats_$MergeID");
    }
}

if ($Pass && check_perms('users_edit_password')) {
    $UpdateSet[] = "PassHash = '" . db_string(Auth::makeHash($Pass)) . "'";
    $EditSummary[] = 'password reset';

    $app->cache->delete("user_info_$UserID");
    $app->cache->delete("user_info_heavy_$UserID");
    $app->cache->delete("user_stats_$UserID");
    $app->cache->delete("enabled_$UserID");

    $app->dbOld->query("
      SELECT SessionID
      FROM users_sessions
      WHERE UserID = '$UserID'");

    while (list($SessionID) = $app->dbOld->next_record()) {
        $app->cache->delete("session_{$UserID}_$SessionID");
    }

    $app->cache->delete("users_sessions_$UserID");

    $app->dbOld->query("
      DELETE FROM users_sessions
      WHERE UserID = '$UserID'");
}

if (empty($UpdateSet) && empty($EditSummary)) {
    if (!$Reason) {
        if (str_replace("\r", '', $Cur['AdminComment']) != str_replace("\r", '', $AdminComment) && check_perms('users_disable_any')) {
            $UpdateSet[] = "AdminComment = '$AdminComment'";
        } else {
            Http::redirect("user.php?id=$UserID");
            error();
        }
    } else {
        $EditSummary[] = 'notes added';
    }
}

if (count($TrackerUserUpdates) > 1) {
    Tracker::update_tracker('update_user', $TrackerUserUpdates);
}

if ($DeleteKeys) {
    $app->cache->delete("user_info_$UserID");
    $app->cache->delete("user_info_heavy_$UserID");
} else {
    /*
    $app->cacheOld->begin_transaction("user_info_$UserID");
    $app->cacheOld->update_row(false, $LightUpdates);
    $app->cacheOld->commit_transaction(0);

    $app->cacheOld->begin_transaction("user_info_heavy_$UserID");
    $app->cacheOld->update_row(false, $HeavyUpdates);
    $app->cacheOld->commit_transaction(0);
    */
}

$Summary = '';
// Create edit summary
if ($EditSummary) {
    $Summary = implode(', ', $EditSummary) . ' by ' . $app->user->core['username'];
    $Summary = sqltime() . ' - ' . ucfirst($Summary);

    if ($Reason) {
        $Summary .= "\nReason: $Reason";
    }


    $Summary .= "\n\n$AdminComment";
} elseif (empty($UpdateSet) && empty($EditSummary) && $Cur['AdminComment'] == $_POST['AdminComment']) {
    $Summary = sqltime() . ' - Comment added by ' . $app->user->core['username'] . ': ' . "$Reason\n\n";
}

if (!empty($Summary)) {
    $UpdateSet[] = "AdminComment = '$Summary'";
} else {
    $UpdateSet[] = "AdminComment = '$AdminComment'";
}

// Update cache


// Build query

$SET = implode(', ', $UpdateSet);

$SQL = "
  UPDATE users_main AS m
    JOIN users_info AS i ON m.ID = i.UserID
  SET $SET
  WHERE m.ID = '$UserID'";

// Perform update
//die($SQL);
$app->dbOld->query($SQL);

if (isset($ClearStaffIDCache)) {
    $app->cache->delete('staff_ids');
}

// redirect to user page
Http::redirect("user.php?id=$UserID");

function translateUserStatus($Status)
{
    switch ($Status) {
        case 0:
            return 'Unconfirmed';
        case 1:
            return 'Enabled';
        case 2:
            return 'Disabled';
        default:
            return $Status;
    }
}

function translateLeechStatus($Status)
{
    switch ($Status) {
        case 0:
            return 'Disabled';
        case 1:
            return 'Enabled';
        default:
            return $Status;
    }
}
