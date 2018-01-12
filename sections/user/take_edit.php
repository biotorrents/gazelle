<?
authorize();

$UserID = $_REQUEST['userid'];
if (!is_number($UserID)) {
  error(404);
}

//For this entire page, we should generally be using $UserID not $LoggedUser['ID'] and $U[] not $LoggedUser[]
$U = Users::user_info($UserID);

if (!$U) {
  error(404);
}

$Permissions = Permissions::get_permissions($U['PermissionID']);
if ($UserID != $LoggedUser['ID'] && !check_perms('users_edit_profiles', $Permissions['Class'])) {
  send_irc('PRIVMSG '.ADMIN_CHAN.' :User '.$LoggedUser['Username'].' ('.site_url().'user.php?id='.$LoggedUser['ID'].') just tried to edit the profile of '.site_url().'user.php?id='.$_REQUEST['userid']);
  error(403);
}

$Val->SetFields('stylesheet', 1, "number", "You forgot to select a stylesheet.");
$Val->SetFields('styleurl', 0, "regex", "You did not enter a valid stylesheet URL.", ['regex' => '/^'.CSS_REGEX.'$/i']);
// The next two are commented out because the drop-down menus were replaced with a check box and radio buttons
//$Val->SetFields('disablegrouping', 0, "number", "You forgot to select your torrent grouping option.");
//$Val->SetFields('torrentgrouping', 0, "number", "You forgot to select your torrent grouping option.");
$Val->SetFields('postsperpage', 1, "number", "You forgot to select your posts per page option.", ['inarray' => [25, 50, 100]]);
//$Val->SetFields('hidecollage', 1, "number", "You forgot to select your collage option.", ['minlength' => 0, 'maxlength' => 1]);
$Val->SetFields('collagecovers', 1, "number", "You forgot to select your collage option.");
$Val->SetFields('avatar', 0, "regex", "You did not enter a valid avatar URL.", ['regex' => "/^".IMAGE_REGEX."$/i"]);
$Val->SetFields('email', 1, "email", "You did not enter a valid email address.");
$Val->SetFields('irckey', 0, "string", "You did not enter a valid IRC key. An IRC key must be between 6 and 32 characters long.", ['minlength' => 6, 'maxlength' => 32]);
$Val->SetFields('new_pass_1', 0, "regex", "You did not enter a valid password. A valid password is 6 characters or longer.", ['regex' => '/(?=^.{6,}$).*$/']);
$Val->SetFields('new_pass_2', 1, "compare", "Your passwords do not match.", ['comparefield' => 'new_pass_1']);
if (check_perms('site_advanced_search')) {
  $Val->SetFields('searchtype', 1, "number", "You forgot to select your default search preference.", ['minlength' => 0, 'maxlength' => 1]);
}

$ValErr = $Val->ValidateForm($_POST);
if ($ValErr) {
  error($ValErr);
}

if (!apcu_exists('DBKEY')) {
  error("Cannot edit profile until database fully decrypted");
}

// Begin building $Paranoia
// Reduce the user's input paranoia until it becomes consistent
if (isset($_POST['p_uniquegroups_l'])) {
  $_POST['p_uploads_l'] = 'on';
  $_POST['p_uploads_c'] = 'on';
}

if (isset($_POST['p_uploads_l'])) {
  $_POST['p_uniquegroups_l'] = 'on';
  $_POST['p_uniquegroups_c'] = 'on';
  $_POST['p_perfectflacs_l'] = 'on';
  $_POST['p_perfectflacs_c'] = 'on';
  $_POST['p_artistsadded'] = 'on';
}

if (isset($_POST['p_collagecontribs_l'])) {
  $_POST['p_collages_l'] = 'on';
  $_POST['p_collages_c'] = 'on';
}

if (isset($_POST['p_snatched_c']) && isset($_POST['p_seeding_c']) && isset($_POST['p_downloaded'])) {
  $_POST['p_requiredratio'] = 'on';
}

// if showing exactly 2 of stats, show all 3 of stats
$StatsShown = 0;
$Stats = ['downloaded', 'uploaded', 'ratio'];
foreach ($Stats as $S) {
  if (isset($_POST["p_$S"])) {
    $StatsShown++;
  }
}

if ($StatsShown == 2) {
  foreach ($Stats as $S) {
    $_POST["p_$S"] = 'on';
  }
}

$Paranoia = [];
$Checkboxes = ['downloaded', 'uploaded', 'ratio', 'lastseen', 'requiredratio', 'invitedcount', 'artistsadded', 'notifications'];
foreach ($Checkboxes as $C) {
  if (!isset($_POST["p_$C"])) {
    $Paranoia[] = $C;
  }
}

$SimpleSelects = ['torrentcomments', 'collages', 'collagecontribs', 'uploads', 'uniquegroups', 'perfectflacs', 'seeding', 'leeching', 'snatched'];
foreach ($SimpleSelects as $S) {
  if (!isset($_POST["p_$S".'_c']) && !isset($_POST["p_$S".'_l'])) {
    // Very paranoid - don't show count or list
    $Paranoia[] = "$S+";
  } elseif (!isset($_POST["p_$S".'_l'])) {
    // A little paranoid - show count, don't show list
    $Paranoia[] = $S;
  }
}

$Bounties = ['requestsfilled', 'requestsvoted'];
foreach ($Bounties as $B) {
  if (isset($_POST["p_$B".'_list'])) {
    $_POST["p_$B".'_count'] = 'on';
    $_POST["p_$B".'_bounty'] = 'on';
  }
  if (!isset($_POST["p_$B".'_list'])) {
    $Paranoia[] = $B.'_list';
  }
  if (!isset($_POST["p_$B".'_count'])) {
    $Paranoia[] = $B.'_count';
  }
  if (!isset($_POST["p_$B".'_bounty'])) {
    $Paranoia[] = $B.'_bounty';
  }
}

if (!isset($_POST['p_donor_heart'])) {
  $Paranoia[] = 'hide_donor_heart';
}

if (isset($_POST['p_donor_stats'])) {
  Donations::show_stats($UserID);
} else {
  Donations::hide_stats($UserID);
}

// End building $Paranoia

$DB->query("
  SELECT Email, PassHash, IRCKey
  FROM users_main
  WHERE ID = ?", $UserID);
list($CurEmail, $CurPassHash, $CurIRCKey) = $DB->next_record();

function require_password($Setting = false) {
  global $CurPassHash;
  if (empty($_POST['cur_pass'])) {
    error('A setting you changed requires you to enter your current password'.($Setting ? ' (Setting: '.$Setting.')' : ''));
  }
  if (!Users::check_password($_POST['cur_pass'], $CurPassHash)) {
    error('The password you entered was incorrect'.($Setting ? ' (Required by setting: '.$Setting.')' : ''));
  }
}

// Email change
$CurEmail = Crypto::decrypt($CurEmail);
if ($CurEmail != $_POST['email']) {

  // Non-admins have to authenticate to change email
  if (!check_perms('users_edit_profiles')) {
    require_password("Change Email");
  }

  // Update the time of their last email change to the current time *not* the current change.
  $DB->query("
    UPDATE users_history_emails
    SET Time = NOW()
    WHERE UserID = ?
      AND Time IS NULL", $UserID);
  $DB->query("
    INSERT INTO users_history_emails
      (UserID, Email, Time, IP)
    VALUES
      (?, ?, NULL, ?)", $UserID, Crypto::encrypt($_POST['email']), Crypto::encrypt($_SERVER['REMOTE_ADDR']));

}

if (!empty($_POST['new_pass_1']) && !empty($_POST['new_pass_2'])) {
  require_password("Change Password");
  $ResetPassword = true;
}

if ($CurIRCKey != $_POST['irckey']) {
  require_password("Change IRC Key");
}

if (isset($_POST['resetpasskey'])) {
  require_password("Reset Passkey");
}

if ($LoggedUser['DisableAvatar'] && $_POST['avatar'] != $U['Avatar']) {
  error('Your avatar privileges have been revoked.');
}

if (!empty($LoggedUser['DefaultSearch'])) {
  $Options['DefaultSearch'] = $LoggedUser['DefaultSearch'];
}
$Options['DisableGrouping2']    = (!empty($_POST['disablegrouping']) ? 0 : 1);
$Options['TorrentGrouping']     = (!empty($_POST['torrentgrouping']) ? 1 : 0);
$Options['PostsPerPage']        = (int)$_POST['postsperpage'];
$Options['CollageCovers']       = (empty($_POST['collagecovers']) ? 0 : $_POST['collagecovers']);
$Options['ShowTorFilter']       = (empty($_POST['showtfilter']) ? 0 : 1);
$Options['ShowTags']            = (!empty($_POST['showtags']) ? 1 : 0);
$Options['AutoSubscribe']       = (!empty($_POST['autosubscribe']) ? 1 : 0);
$Options['DisableSmileys']      = (!empty($_POST['disablesmileys']) ? 1 : 0);
$Options['AutoloadCommStats']   = (check_perms('users_mod') && !empty($_POST['autoload_comm_stats']) ? 1 : 0);
$Options['DisableAvatars']      = db_string($_POST['disableavatars']);
$Options['Identicons']          = (!empty($_POST['identicons']) ? (int)$_POST['identicons'] : 0);
$Options['DisablePMAvatars']    = (!empty($_POST['disablepmavatars']) ? 1 : 0);
$Options['NotifyOnQuote']       = (!empty($_POST['notifications_Quotes_popup']) ? 1 : 0);
$Options['ListUnreadPMsFirst']  = (!empty($_POST['list_unread_pms_first']) ? 1 : 0);
$Options['ShowSnatched']        = (!empty($_POST['showsnatched']) ? 1 : 0);
$Options['ShowMagnets']         = (!empty($_POST['showmagnets']) ? 1 : 0);
$Options['DisableAutoSave']     = (!empty($_POST['disableautosave']) ? 1 : 0);
$Options['CoverArt']            = (int)!empty($_POST['coverart']);
$Options['ShowExtraCovers']     = (int)!empty($_POST['show_extra_covers']);
$Options['HideLolicon']         = (int)!empty($_POST['hide_lolicon']);
$Options['HideScat']            = (int)!empty($_POST['hide_scat']);
$Options['HideSnuff']           = (int)!empty($_POST['hide_snuff']);
$Options['AutoComplete']        = (int)$_POST['autocomplete'];
$Options['StyleAdditions']      = $_POST['style_additions'] ?? [];

if (isset($LoggedUser['DisableFreeTorrentTop10'])) {
  $Options['DisableFreeTorrentTop10'] = $LoggedUser['DisableFreeTorrentTop10'];
}

if (!empty($_POST['sorthide'])) {
  $JSON = json_decode($_POST['sorthide']);
  foreach ($JSON as $J) {
    $E = explode('_', $J);
    $Options['SortHide'][$E[0]] = $E[1];
  }
} else {
  $Options['SortHide'] = [];
}

if (check_perms('site_advanced_search')) {
  $Options['SearchType'] = $_POST['searchtype'];
} else {
  unset($Options['SearchType']);
}

//TODO: Remove the following after a significant amount of time
unset($Options['ArtistNoRedirect']);
unset($Options['ShowQueryList']);
unset($Options['ShowCacheList']);

$UnseededAlerts = isset($_POST['unseededalerts']) ? 1 : 0;

Donations::update_rewards($UserID);
NotificationsManager::save_settings($UserID);

// Begin Badge settings
if (!empty($_POST['badges'])) {
  $BadgeIDs = array_slice($_POST['badges'], 0, 5);
} else {
  $BadgeIDs = [];
}

$NewBadges = [];
$BadgesChanged = false;
$Badges = Users::user_info($UserID)['Badges'];
foreach ($Badges as $BadgeID => $OldDisplayed) {
  if (in_array($BadgeID, $BadgeIDs)) { // Is the current badge in the list of badges the user wants to display?
    $Displayed = true;
    $DisplayedBadgeIDs[] = $BadgeID;
    if ($OldDisplayed == 0) { // The user wants to display a badge that wasn't displayed before
      $BadgesChanged = true;
    }
  } else { // The user no longer wants to display a badge that was displayed before
    $Displayed = false;
    $BadgesChanged = true;
  }
  $NewBadges[$BadgeID] = $Displayed?'1':'0';
}
// End Badge settings

$Cache->begin_transaction("user_info_$UserID");
$Cache->update_row(false, [
  'Avatar' => display_str($_POST['avatar']),
	'Paranoia' => $Paranoia,
	'Badges' => $NewBadges
]);
$Cache->commit_transaction(0);

$Cache->begin_transaction("user_info_heavy_$UserID");
$Cache->update_row(false, [
  'StyleID' => $_POST['stylesheet'],
  'StyleURL' => display_str($_POST['styleurl'])
]);
$Cache->update_row(false, $Options);
$Cache->commit_transaction(0);


$SQL = "
  UPDATE users_main AS m
    JOIN users_info AS i ON m.ID = i.UserID
  SET
    i.StyleID = '".db_string($_POST['stylesheet'])."',
    i.StyleURL = '".db_string($_POST['styleurl'])."',
    i.Avatar = '".db_string($_POST['avatar'])."',
    i.SiteOptions = '".db_string(json_encode($Options))."',
    i.NotifyOnQuote = '".db_string($Options['NotifyOnQuote'])."',
    i.Info = '".db_string($_POST['info'])."',
    i.InfoTitle = '".db_string($_POST['profile_title'])."',
    i.UnseededAlerts = '$UnseededAlerts',
    m.Email = '".Crypto::encrypt($_POST['email'])."',
    m.IRCKey = '".db_string($_POST['irckey'])."',
    m.Paranoia = '".db_string(json_encode($Paranoia))."'";

if ($ResetPassword) {
  $ChangerIP = Crypto::encrypt($LoggedUser['IP']);
  $PassHash = Users::make_sec_hash($_POST['new_pass_1']);
  $SQL.= ",m.PassHash = '".db_string($PassHash)."'";
  $DB->query("
    INSERT INTO users_history_passwords
      (UserID, ChangerIP, ChangeTime)
    VALUES
      (?, ?, NOW())", $UserID, $ChangerIP);
}

if (isset($_POST['resetpasskey'])) {

  $UserInfo = Users::user_heavy_info($UserID);
  $OldPassKey = $UserInfo['torrent_pass'];
  $NewPassKey = Users::make_secret();
  $ChangerIP = Crypto::encrypt($LoggedUser['IP']);
  $SQL .= ",m.torrent_pass = '$NewPassKey'";
  $DB->query("
    INSERT INTO users_history_passkeys
      (UserID, OldPassKey, NewPassKey, ChangerIP, ChangeTime)
    VALUES
      (?, ?, ?, ?, NOW())",
      $USerID, $OldPassKey, $NewPassKey, $ChangerIP);
  $Cache->begin_transaction("user_info_heavy_$UserID");
  $Cache->update_row(false, ['torrent_pass' => $NewPassKey]);
  $Cache->commit_transaction(0);
  $Cache->delete_value("user_$OldPassKey");

  Tracker::update_tracker('change_passkey', ['oldpasskey' => $OldPassKey, 'newpasskey' => $NewPassKey]);
}

$SQL .= "WHERE m.ID = '".db_string($UserID)."'";
$DB->query($SQL);

if ($BadgesChanged) {
  $DB->query("
    UPDATE users_badges
    SET Displayed = 0
    WHERE UserID = ?", $UserID);
  if (!empty($BadgeIDs)) {
    $DB->query("
      UPDATE users_badges
      SET Displayed = 1
      WHERE UserID = $UserID
        AND BadgeID IN (".db_string(implode(',', $BadgeIDs)).")");
  }
}

if ($ResetPassword) {
  logout_all_sessions();
}

header("Location: user.php?action=edit&userid=$UserID");

?>
