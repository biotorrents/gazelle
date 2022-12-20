<?php

#declare(strict_types = 1);


/**
 * user settings handler
 */

$app = App::go();

$ENV = ENV::go(); # legacy

$UserID = (int) $_REQUEST['userid'];
Security::int($UserID);

$app->dbOld->query("
  SELECT
    m.Username,
    m.TwoFactor,
    m.PublicKey,
    m.Email,
    m.IRCKey,
    m.Paranoia,
    i.Info,
    i.Avatar,
    i.StyleID,
    i.StyleURL,
    i.SiteOptions,
    i.UnseededAlerts,
    p.Level AS Class,
    i.InfoTitle
  FROM users_main AS m
    JOIN users_info AS i ON i.UserID = m.ID
    LEFT JOIN permissions AS p ON p.ID = m.PermissionID
  WHERE m.ID = ?", $UserID);
list($Username, $TwoFactor, $PublicKey, $Email, $IRCKey, $Paranoia, $Info, $Avatar, $StyleID, $StyleURL, $SiteOptions, $UnseededAlerts, $Class, $InfoTitle) = $app->dbOld->next_record(MYSQLI_NUM, [5, 10]);

$TwoFA = new RobThree\Auth\TwoFactorAuth($ENV->siteName);
$Email = apcu_exists('DBKEY') ? Crypto::decrypt($Email) : '[Encrypted]';

if ((int) $UserID !== $user['ID'] && !check_perms('users_edit_profiles', $Class)) {
    error(403);
}

$Paranoia = json_decode($Paranoia, true);
if (!is_array($Paranoia)) {
    $Paranoia = [];
}

function paranoia_level($Setting)
{
    global $Paranoia;
    // 0: very paranoid; 1: stats allowed, list disallowed; 2: not paranoid
    return (in_array($Setting . '+', $Paranoia)) ? 0 : (in_array($Setting, $Paranoia) ? 1 : 2);
}

function display_paranoia($FieldName)
{
    $Level = paranoia_level($FieldName);
    echo "<label><input type='checkbox' name='p_{$FieldName}_c'" . checked($Level >= 1) . " onchange='AlterParanoia()' /> Show count</label>&nbsp;";
    echo "<label><input type='checkbox' name='p_{$FieldName}_l'" . checked($Level >= 2) . " onchange='AlterParanoia()' /> Show list</label>&nbsp;";
}

function checked($Checked)
{
    return ($Checked ? ' checked="checked"' : '');
}

if ($SiteOptions) {
    $SiteOptions = json_decode($SiteOptions, true) ?? [];
} else {
    $SiteOptions = [];
}

/**
 * Show header
 */
View::header(
    "$Username $ENV->crumb Settings",
    'user,cssgallery,preview_paranoia,user_settings,vendor/easymde.min',
    'vendor/easymde.min'
);

$DonorRank = null;
$DonorIsVisible = null;

if ($DonorIsVisible === null) {
    $DonorIsVisible = true;
}

$Rewards = null;
$ProfileRewards = null;
?>

<div>
  <div class="header">
    <h2>
      <?=Users::format_username($UserID, false, false, false)?>
      <?=$ENV->crumb?> Settings
    </h2>
  </div>

  <!-- Side menu / settings filter -->
  <form class="edit_form" name="user" id="userform" method="post" autocomplete="off">
    <div class="sidebar one-third column">
      <div class="box" id="settings_sections">

        <div class="head">
          <strong>Sections</strong>
        </div>

        <ul class="nobullet">
          <li data-gazelle-section-id="all_settings">
            <h2>
              <a href="#">All Settings</a>
            </h2>
          </li>

          <li data-gazelle-section-id="site_appearance">
            <h2>
              <a href="#">Site Appearance</a>
            </h2>
          </li>

          <li data-gazelle-section-id="torrent_settings">
            <h2>
              <a href="#">Torrents</a>
            </h2>
          </li>

          <li data-gazelle-section-id="community_settings">
            <h2>
              <a href="#">Community</a>
            </h2>
          </li>

          <li data-gazelle-section-id="notification_settings">
            <h2>
              <a href="#">Notifications</a>
            </h2>
          </li>

          <li data-gazelle-section-id="profile_settings">
            <h2>
              <a href="#">Profile</a>
            </h2>
          </li>

          <li data-gazelle-section-id="paranoia_settings">
            <h2>
              <a href="#">Paranoia</a>
            </h2>
          </li>

          <li data-gazelle-section-id="security_settings">
            <h2>
              <a href="#">Security</a>
            </h2>
          </li>

          <li data-gazelle-section-id="live_search">
            <input type="text" id="settings_search" placeholder="Filter settings" />
          </li>

          <li>
            <input type="submit" id="submit" class="button-primary" value="Save profile" />
          </li>

        </ul>
      </div>
    </div>

    <div class="main_column two-thirds column">
      <div>
        <input type="hidden" name="action" value="take_edit" />
        <input type="hidden" name="userid" value="<?=$UserID?>" />
        <input type="hidden" name="auth"
          value="<?=$user['AuthKey']?>" />
      </div>

      <!-- Site Appearance -->
      <table cellpadding="6" cellspacing="1" border="0" width="100%" class="layout border user_options"
        id="site_appearance">
        <tr class="colhead_dark">
          <td colspan="2">
            <strong>Site Appearance</strong>
          </td>
        </tr>

        <!-- Stylesheet -->
        <tr id="site_style_tr">
          <td class="label">
            <strong>Stylesheet</strong>
          </td>

          <td>
            <select name="stylesheet" id="stylesheet">
              <?php foreach ($Stylesheets as $Style) { ?>

              <option value="<?=($Style['ID'])?>"
                <?=(int) $Style['ID'] === $StyleID ? ' selected="selected"' : ''?>><?=($Style['ProperName'])?>
              </option>
              <?php } ?>
            </select>
            &ensp;
            <a data-toggle-target="#css_gallery" class="brackets">Show gallery</a>
            <div id="css_gallery" class="hidden">
              <?php foreach ($Stylesheets as $Style) { ?>
              <div class="preview_wrapper">
                <div class="preview_image"
                  name="<?=($Style['Name'])?>">
                  <img
                    src="<?=staticServer.'css/preview/thumb_'.$Style['Name'].'.png'?>"
                    alt="<?=$Style['Name']?>" />
                  <p class="preview_name">
                    <label><input type="radio" name="stylesheet_gallery"
                        value="<?=($Style['ID'])?>" />
                      <?=($Style['ProperName'])?></label>
                  </p>
                </div>
              </div>
              <?php } ?>
            </div>
          </td>
        </tr>

        <!-- Stylesheet additions -->
        <tr id="style_additions_tr"
          class="<?=($Stylesheets[$user['StyleID']]['Additions'][0] ?? false) ? '' : 'hidden'?>">

          <td class="label">
            <strong>Stylesheet additions</strong>
          </td>

          <td>
            <?php
          foreach ($Stylesheets as $Style) {

              # Main section
              echo '<section class="style_additions'; # open quote
              echo ($Style['ID'] === $Stylesheets[$user['StyleID']]['ID'])
                ? '"' # close quote
                : ' hidden"'; # hide
              echo ' id="style_additions_' . $Style['Name'] . '">';

              # For each style addition
              $StyleAdditions = explode(';', $Style['Additions']);
              $Select = ['default_font'];
              $Checkbox = [];

              foreach ($StyleAdditions as $i => $Addition) {
                  $Types = explode('=', $Addition);

                  switch ($Types[0]) {
                  case 'select':
                      array_push($Select, $Types[1]);
                      break;

                  case 'checkbox':
                      array_push($Checkbox, $Types[1]);
                      break;

                  default:
                      break;

                  }
              } # foreach $Addition

              # Fix to prevent multiple font entries
              if ($Style['ID'] === $Stylesheets[$user['StyleID']]['ID']) {
                  # Select options, e.g., fonts
                  echo "<select class='style_additions' name='style_additions[]'>";

                  foreach ($Select as $Option) {
                      $Selected = (in_array($Option, $SiteOptions['StyleAdditions'])
                        ? 'selected'
                        : '');
                      echo "<option value='$Option' id='addition_$Option' $Selected>$Option</option>";
                  }
                  echo '</select>';
              }

              # Checkbox options, e.g., pink and haze
              foreach ($Checkbox as $Option) {
                  $Checked = (in_array($Option, $SiteOptions['StyleAdditions'])
                  ? 'checked'
                  : '');

                  echo <<<HTML
                  <input type="checkbox" name="style_additions[]" value="$Option"
                    id="addition_$Option" $Checked />
                  <label for="addition_$Option">$Option</label>
HTML;
              }
              echo '</section>';
          } # foreach $Style
          ?>
          </td>
        </tr>

        <!-- External stylesheet URL -->
        <tr id="site_extstyle_tr">
          <td class="label">
            <strong>External stylesheet URL</strong>
          </td>

          <td>
            <input type="text" size="40" name="styleurl" id="styleurl"
              value="<?=Text::esc($StyleURL)?>" />
          </td>
        </tr>

        <!-- Profile stats -->
        <?php if (check_perms('users_mod')) { ?>
        <tr id="site_autostats_tr">
          <td class="label tooltip" title="Staff Only">
            <strong>Profile stats</strong>
          </td>

          <td>
            <label>
              <input type="checkbox" name="autoload_comm_stats" <?Format::selected(
              'AutoloadCommStats',
              1,
              'checked',
              $SiteOptions
          ); ?>
              />
              Automatically fetch the snatch and peer stats on profile pages
            </label>
          </td>
        </tr>
        <?php } ?>
      </table>

      <!-- Torrents -->
      <table cellpadding="6" cellspacing="1" border="0" width="100%" class="layout border user_options"
        id="torrent_settings">
        <tr class="colhead_dark">
          <td colspan="2">
            <strong>Torrents</strong>
          </td>
        </tr>

        <!-- Default search type -->
        <?php if (check_perms('site_advanced_search')) { ?>
        <tr id="tor_searchtype_tr">
          <td class="label">
            <strong>Default search type</strong>
          </td>

          <td>
            <ul class="options_list nobullet">
              <li>
                <input type="radio" name="searchtype" id="search_type_simple" value="0" <?=(int)$SiteOptions['SearchType']===0 ? ' checked="checked"' : ''?>
                />
                <label for="search_type_simple">Simple</label>
              </li>

              <li>
                <input type="radio" name="searchtype" id="search_type_advanced" value="1" <?=(int)$SiteOptions['SearchType']===1 ? ' checked="checked"' : ''?>
                />
                <label for="search_type_advanced">Advanced</label>
              </li>
            </ul>
          </td>
        </tr>
        <?php } ?>

        <!-- Torrent grouping -->
        <tr id="tor_group_tr">
          <td class="label">
            <strong>Torrent grouping</strong>
          </td>

          <td>
            <div class="option_group">
              <input type="checkbox" name="disablegrouping" id="disablegrouping" <?=$SiteOptions['DisableGrouping2'] === 0 ? ' checked="checked"' : ''?>
              />
              <label for="disablegrouping">Enable torrent grouping</label>
            </div>
          </td>
        </tr>

        <!-- Torrent group display -->
        <tr id="tor_gdisp_search_tr">
          <td class="label">
            <strong>Torrent group display</strong>
          </td>

          <td>
            <div class="option_group">
              <ul class="options_list nobullet">
                <li>
                  <input type="radio" name="torrentgrouping" id="torrent_grouping_open" value="0" <?=$SiteOptions['TorrentGrouping'] === 0 ? ' checked="checked"' : ''?>
                  />
                  <label for="torrent_grouping_open">Open</label>
                </li>

                <li>
                  <input type="radio" name="torrentgrouping" id="torrent_grouping_closed" value="1" <?=$SiteOptions['TorrentGrouping'] === 1 ? ' checked="checked"' : ''?>
                  />
                  <label for="torrent_grouping_closed">Closed</label>
                </li>
              </ul>
            </div>
          </td>
        </tr>

        <!-- Snatched torrents indicator -->
        <tr id="tor_snatched_tr">
          <td class="label">
            <strong>Snatched torrents indicator</strong>
          </td>

          <td>
            <input type="checkbox" name="showsnatched" id="showsnatched" <?=!empty($SiteOptions['ShowSnatched']) ? ' checked="checked"' : ''?>
            />
            <label for="showsnatched">Enable snatched torrents indicator</label>
          </td>
        </tr>

        <!-- Cover art (torrents) -->
        <tr id="tor_cover_tor_tr">
          <td class="label">
            <strong>Cover art (torrents)</strong>
          </td>

          <td>
            <ul class="options_list nobullet">
              <li>
                <input type="hidden" name="coverart" value="" />
                <input type="checkbox" name="coverart" id="coverart" <?=!isset($SiteOptions['CoverArt']) || $SiteOptions['CoverArt'] ? ' checked="checked"' : ''?>
                />
                <label for="coverart">Enable cover artwork</label>
              </li>

              <li>
                <input type="checkbox" name="show_extra_covers" id="show_extra_covers" <?=$SiteOptions['ShowExtraCovers'] ? ' checked="checked"' : ''?>
                />
                <label for="show_extra_covers">Enable additional cover artwork</label>
              </li>
            </ul>
          </td>
        </tr>

        <!-- Cover art (collections) -->
        <tr id="tor_cover_coll_tr">
          <td class="label">
            <strong>Cover art (collections)</strong>
          </td>

          <td>
            <select name="collagecovers" id="collagecovers">
              <option value="10" <?=$SiteOptions['CollageCovers'] === 10 ? ' selected="selected"' : ''?>>10
              </option>

              <option value="25" <?=($SiteOptions['CollageCovers'] === 25 || !isset($SiteOptions['CollageCovers'])) ? ' selected="selected"' : ''?>>25
                (default)</option>

              <option value="50" <?=$SiteOptions['CollageCovers'] === 50 ? ' selected="selected"' : ''?>>50
              </option>

              <option value="100" <?=$SiteOptions['CollageCovers'] === 100 ? ' selected="selected"' : ''?>>100
              </option>

              <option value="1000000" <?=$SiteOptions['CollageCovers'] === 1000000 ? ' selected="selected"' : ''?>>All
              </option>

              <option value="0" <?=($SiteOptions['CollageCovers'] === 0 || (!isset($SiteOptions['CollageCovers']) && $SiteOptions['HideCollage'])) ? ' selected="selected"' : ''?>>None
              </option>
            </select>
            covers per page
          </td>
        </tr>

        <!-- Torrent search filters -->
        <tr id="tor_showfilt_tr">
          <td class="label">
            <strong>Torrent search filters</strong>
          </td>

          <td>
            <ul class="options_list nobullet">
              <li>
                <input type="checkbox" name="showtfilter" id="showtfilter" <?=(!isset($SiteOptions['ShowTorFilter']) || $SiteOptions['ShowTorFilter'] ? ' checked="checked"' : '')?>
                />
                <label for="showtfilter">Display filter controls</label>
              </li>

              <li>
                <input type="checkbox" name="showtags" id="showtags" <?php Format::selected('ShowTags', 1, 'checked', $SiteOptions); ?>
                />
                <label for="showtags">Display official tag filters</label>
              </li>
            </ul>
          </td>
        </tr>
      </table>

      <!-- Community -->
      <table cellpadding="6" cellspacing="1" border="0" width="100%" class="layout border user_options"
        id="community_settings">
        <tr class="colhead_dark">
          <td colspan="2">
            <strong>Community</strong>
          </td>
        </tr>

        <!-- Posts per page (forums) -->
        <tr id="comm_ppp_tr">
          <td class="label">
            <strong>Posts per page (forums)</strong>
          </td>

          <td>
            <select name="postsperpage" id="postsperpage">
              <option value="25" <?=$SiteOptions['PostsPerPage'] === 25 ? ' selected="selected"' : ''?>>25
                (default)</option>

              <option value="50" <?=$SiteOptions['PostsPerPage'] === 50 ? ' selected="selected"' : ''?>>50
              </option>

              <option value="100" <?=$SiteOptions['PostsPerPage'] === 100 ? ' selected="selected"' : ''?>>100
              </option>
            </select>
            posts per page
          </td>
        </tr>

        <!-- Inbox sorting -->
        <tr id="comm_inbsort_tr">
          <td class="label">
            <strong>Inbox sorting</strong>
          </td>

          <td>
            <input type="checkbox" name="list_unread_pms_first" id="list_unread_pms_first" <?=!empty($SiteOptions['ListUnreadPMsFirst']) ? ' checked="checked"' : ''?>
            />
            <label for="list_unread_pms_first">List unread private messages first</label>
          </td>
        </tr>

        <!-- Avatar display (posts) -->
        <tr id="comm_avatars_tr">
          <td class="label">
            <strong>Avatar display (posts)</strong>
          </td>

          <td>
            <select name="disableavatars" id="disableavatars">
              <option value="1" <?=(int)$SiteOptions['DisableAvatars'] === 1 ? ' selected="selected"' : ''?>>Disable
                avatars</option>
              <option value="0" <?=(int)$SiteOptions['DisableAvatars'] === 0 ? ' selected="selected"' : ''?>>Show
                avatars</option>
            </select>
          </td>
        </tr>

        <!-- Auto-save reply text -->
        <tr id="comm_autosave_tr">
          <td class="label">
            <strong>Auto-save reply text</strong>
          </td>

          <td>
            <input type="checkbox" name="disableautosave" id="disableautosave" <?=!empty($SiteOptions['DisableAutoSave']) ? ' checked="checked"' : ''?>
            />
            <label for="disableautosave">Disable text auto-saving</label>
          </td>
        </tr>

        <!-- Displayed badges -->
        <tr id="comm_badge_tr">
          <td class="label">
            <strong>Displayed badges</strong>
          </td>

          <td>
            <?php
            $Badges = Badges::get_badges($UserID);
    if (empty($Badges)) {
        ?><span>You have no badges :(</span><?php
    } else {
        $Count = 0;
        foreach ($Badges as $BadgeID => $Displayed) { ?>
            <input type="checkbox" name="badges[]" class="badge_checkbox"
              value="<?=$BadgeID?>" <?=($Displayed) ? "checked " : ""?>/>
            <?=Badges::display_badge($BadgeID, true)?>
            <?php
        $Count++;
            echo ($Count % 8) ? '' : '<br>';
        }
    } ?>
          </td>
        </tr>
      </table>

      <!-- Notifications -->
      <table cellpadding="6" cellspacing="1" border="0" width="100%" class="layout border user_options"
        id="notification_settings">
        <tr class="colhead_dark">
          <td colspan="2">
            <strong>Notifications</strong>
          </td>
        </tr>

        <!-- Automatic thread subscriptions -->
        <tr id="notif_autosubscribe_tr">
          <td class="label">
            <strong>Automatic thread subscriptions</strong>
          </td>

          <td>
            <input type="checkbox" name="autosubscribe" id="autosubscribe" <?=!empty($SiteOptions['AutoSubscribe']) ? ' checked="checked"' : ''?>
            />
            <label for="autosubscribe">Enable automatic thread subscriptions</label>
          </td>
        </tr>

        <!-- Unseeded torrent alerts -->
        <tr id="notif_unseeded_tr">
          <td class="label">
            <strong>Unseeded torrent alerts</strong>
          </td>

          <td>
            <input type="checkbox" name="unseededalerts" id="unseededalerts" <?=checked($UnseededAlerts)?> />
            <label for="unseededalerts">Enable unseeded torrent alerts</label>
          </td>
        </tr>
        <?php NotificationsManagerView::render_settings(NotificationsManager::get_settings($UserID)); ?>
      </table>

      <!-- Profile -->
      <table cellpadding="6" cellspacing="1" border="0" width="100%" class="layout border user_options"
        id="profile_settings">
        <tr class="colhead_dark">
          <td colspan="2">
            <strong>Profile</strong>
          </td>
        </tr>

        <!-- Avatar URL -->
        <tr id="pers_avatar_tr">
          <td class="label tooltip" title="512 KiB max size / 600 px max height">
            <strong>Avatar URL</strong>
          </td>

          <td>
            <input type="text" size="50" name="avatar" id="avatar"
              value="<?=Text::esc($Avatar)?>" />
          </td>
        </tr>

        <!-- Second avatar URL -->
        <?php if ($HasSecondAvatar) { ?>
        <tr id="pers_avatar2_tr">
          <td class="label">
            <strong>Second avatar URL</strong>
          </td>

          <td>
            <input type="text" size="50" name="second_avatar" id="second_avatar"
              value="<?=$Rewards['SecondAvatar']?>" />
          </td>
        </tr>
        <?php }

# Avatar mouseover text
  if ($HasAvatarMouseOverText) { ?>
        <tr id="pers_avatarhover_tr">
          <td class="label">
            <strong>Avatar mouseover text</strong>
          </td>

          <td>
            <input type="text" size="50" name="avatar_mouse_over_text" id="avatar_mouse_over_text"
              value="<?=$Rewards['AvatarMouseOverText']?>" />
          </td>
        </tr>
        <?php }

# Donor icon mouseover text
  if ($HasDonorIconMouseOverText) { ?>
        <tr id="pers_donorhover_tr">
          <td class="label">
            <strong>Donor icon mouseover text</strong>
          </td>

          <td>
            <input type="text" size="50" name="donor_icon_mouse_over_text" id="donor_icon_mouse_over_text"
              value="<?=$Rewards['IconMouseOverText']?>" />
          </td>
        </tr>
        <?php }

# Donor icon link
  if ($HasDonorIconLink) { ?>
        <tr id="pers_donorlink_tr">
          <td class="label">
            <strong>Donor icon link</strong>
          </td>

          <td>
            <input type="text" size="50" name="donor_icon_link" id="donor_icon_link"
              value="<?=$Rewards['CustomIconLink']?>" />
          </td>
        </tr>
        <?php }

# Custom donor icon URL
  if ($HasCustomDonorIcon) { ?>
        <tr id="pers_donoricon_tr">
          <td class="label">
            <strong>Custom donor icon URL</strong>
          </td>

          <td>
            <input type="text" size="50" name="donor_icon_custom_url" id="donor_icon_custom_url"
              value="<?=$Rewards['CustomIcon']?>" />
          </td>
        </tr>
        <?php } ?>

        <!-- Profile title 1 -->
        <tr id="pers_proftitle_tr">
          <td class="label">
            <strong>Profile title 1</strong>
          </td>

          <td>
            <input type="text" size="50" name="profile_title" id="profile_title"
              value="<?=Text::esc($InfoTitle)?>" />
          </td>
        </tr>

        <!-- Profile info 1 -->
        <tr id="pers_profinfo_tr">
          <td class="label">
            <strong>Profile info 1</strong>
          </td>

          <td>
            <?php
  $textarea = View::textarea(
      id: 'info',
      value: Text::esc($Info) ?? '',
  ); ?>
          </td>
        </tr>

        <!-- Excuse this numbering confusion, we start numbering our profile info/titles at 1 in the donor_rewards table -->
        <?php if ($HasProfileInfo1) { ?>
        <tr id="pers_proftitle2_tr">
          <td class="label">
            <strong>Profile title 2</strong>
          </td>

          <td>
            <input type="text" size="50" name="profile_title_1" id="profile_title_1"
              value="<?=Text::esc($ProfileRewards['ProfileInfoTitle1'])?>" />
          </td>
        </tr>

        <!-- 2 -->
        <tr id="pers_profinfo2_tr">
          <td class="label">
            <strong>Profile info 2</strong>
          </td>

          <td>
            <?php
  $textarea = View::textarea(
      id: 'profile_info_1',
      value: Text::esc($ProfileRewards['ProfileInfo1']) ?? '',
  ); ?>
          </td>
        </tr>
        <?php } ?>

      </table>

      <!-- Paranoia -->
      <table cellpadding="6" cellspacing="1" border="0" width="100%" class="layout border user_options"
        id="paranoia_settings">
        <tr class="colhead_dark">
          <td colspan="2">
            <strong>Paranoia</strong>
          </td>
        </tr>

        <tr>
          <td class="label">&nbsp;</td>
          <td>
            <p>
              <strong>Select the profile elements you wish to display to other users.</strong>
            </p>

            <p>
              For example, if you select "Show count" for "Requests (filled)," the number of requests you have filled
              will be visible.
              If you select "Show bounty," the amount of request bounty you have received will be visible.
              If you select "Show list," the full list of requests you have filled will be visible.
            </p>

            <p>
              <span class="warning">
                Note: Paranoia has nothing to do with your security on this site.
                These settings only determine if others can view your site activity.
                Some information will remain available in the site log.
              </span>
            </p>
          </td>
        </tr>

        <!-- Recent activity -->
        <tr id="para_lastseen_tr">
          <td class="label">
            <strong>Recent activity</strong>
          </td>

          <td>
            <label>
              <input type="checkbox" name="p_lastseen" <?=checked(!in_array('lastseen', $Paranoia))?>
              />
              Last seen
            </label>
          </td>
        </tr>

        <!-- Presets -->
        <tr id="para_presets_tr">
          <td class="label">
            <strong>Presets</strong>
          </td>

          <td>
            <input type="button" onclick="ParanoiaResetOff();" value="Everything" />
            <input type="button" onclick="ParanoiaResetStats();" value="Stats Only" />
            <input type="button" onclick="ParanoiaResetOn();" value="Nothing" />
          </td>
        </tr>


        <!-- Statistics -->
        <tr id="para_stats_tr">
          <td class="label">
            <strong>Statistics</strong>
          </td>

          <td>
            <?php
$UploadChecked = checked(!in_array('uploaded', $Paranoia));
$DownloadChecked = checked(!in_array('downloaded', $Paranoia));
$RatioChecked = checked(!in_array('ratio', $Paranoia));
?>

            <label><input type="checkbox" name="p_uploaded" onchange="AlterParanoia();" <?=$UploadChecked?> /> Uploaded</label>&ensp;
            <label><input type="checkbox" name="p_downloaded" onchange="AlterParanoia();" <?=$DownloadChecked?> /> Downloaded</label>&ensp;
            <label><input type="checkbox" name="p_ratio" onchange="AlterParanoia();" <?=$RatioChecked?> /> Ratio</label>
          </td>
        </tr>

        <!-- Required Ratio -->
        <tr id="para_reqratio_tr">
          <td class="label">
            <strong>Required Ratio</strong>
          </td>

          <td>
            <label>
              <input type="checkbox" name="p_requiredratio" <?=checked(!in_array('requiredratio', $Paranoia))?>
              /> Required Ratio
            </label>
          </td>
        </tr>

        <!-- Comments (torrents) -->
        <tr id="para_comments_tr">
          <td class="label">
            <strong>Comments (torrents)</strong>
          </td>

          <td>
            <?php display_paranoia('torrentcomments'); ?>
          </td>
        </tr>

        <!-- Collections (started) -->
        <tr id="para_collstart_tr">
          <td class="label">
            <strong>Collections (started)</strong>
          </td>

          <td>
            <?php display_paranoia('collages'); ?>
          </td>
        </tr>

        <!-- Collections (contributed to) -->
        <tr id="para_collcontr_tr">
          <td class="label">
            <strong>Collections (contributed to)</strong>
          </td>

          <td>
            <?php display_paranoia('collagecontribs'); ?>
          </td>
        </tr>

        <!-- Requests (filled) -->
        <tr id="para_reqfill_tr">
          <td class="label">
            <strong>Requests (filled)</strong>
          </td>

          <td>
            <?php
$RequestsFilledCountChecked = checked(!in_array('requestsfilled_count', $Paranoia));
$RequestsFilledBountyChecked = checked(!in_array('requestsfilled_bounty', $Paranoia));
$RequestsFilledListChecked = checked(!in_array('requestsfilled_list', $Paranoia));
?>

            <label><input type="checkbox" name="p_requestsfilled_count" onchange="AlterParanoia();" <?=$RequestsFilledCountChecked?> /> Show
              count</label>&nbsp;
            <label><input type="checkbox" name="p_requestsfilled_bounty" onchange="AlterParanoia();" <?=$RequestsFilledBountyChecked?> /> Show
              bounty</label>&nbsp;
            <label><input type="checkbox" name="p_requestsfilled_list" onchange="AlterParanoia();" <?=$RequestsFilledListChecked?> /> Show list</label>
          </td>
        </tr>

        <!-- Requests (voted for) -->
        <tr id="para_reqvote_tr">
          <td class="label">
            <strong>Requests (voted for)</strong>
          </td>

          <td>
            <?php
$RequestsVotedCountChecked = checked(!in_array('requestsvoted_count', $Paranoia));
$RequestsVotedBountyChecked = checked(!in_array('requestsvoted_bounty', $Paranoia));
$RequestsVotedListChecked = checked(!in_array('requestsvoted_list', $Paranoia));
?>

            <label><input type="checkbox" name="p_requestsvoted_count" onchange="AlterParanoia();" <?=$RequestsVotedCountChecked?> /> Show
              count</label>&nbsp;
            <label><input type="checkbox" name="p_requestsvoted_bounty" onchange="AlterParanoia();" <?=$RequestsVotedBountyChecked?> /> Show
              bounty</label>&nbsp;
            <label><input type="checkbox" name="p_requestsvoted_list" onchange="AlterParanoia();" <?=$RequestsVotedListChecked?> /> Show list</label>
          </td>
        </tr>

        <!-- Uploaded torrents -->
        <tr id="para_upltor_tr">
          <td class="label">
            <strong>Uploaded torrents</strong>
          </td>

          <td>
            <?php display_paranoia('uploads'); ?>
          </td>
        </tr>

        <!-- Uploaded torrents (unique groups) -->
        <tr id="para_uplunique_tr">
          <td class="label">
            <strong>Uploaded torrents (unique groups)</strong>
          </td>

          <td>
            <?php display_paranoia('uniquegroups'); ?>
          </td>
        </tr>

        <!-- Torrents (seeding) -->
        <tr id="para_torseed_tr">
          <td class="label">
            <strong>Torrents (seeding)</strong>
          </td>

          <td>
            <?php display_paranoia('seeding'); ?>
          </td>
        </tr>

        <!-- Torrents (leeching) -->
        <tr id="para_torleech_tr">
          <td class="label">
            <strong>Torrents (leeching)</strong>
          </td>

          <td>
            <?php display_paranoia('leeching'); ?>
          </td>
        </tr>

        <!-- Torrents (snatched) -->
        <tr id="para_torsnatch_tr">
          <td class="label">
            <strong>Torrents (snatched)</strong>
          </td>

          <td>
            <?php display_paranoia('snatched'); ?>
          </td>
        </tr>

        <!-- Torrents (upload subscriptions) -->
        <tr id="para_torsubscr_tr">
          <td class="label tooltip" title="Can others subscribe to your uploads?">
            <strong>Torrents (upload subscriptions)</strong>
          </td>

          <td>
            <label>
              <input type="checkbox" name="p_notifications" <?=checked(!in_array('notifications', $Paranoia))?>
              /> Allow torrent upload subscriptions
            </label>
          </td>
        </tr>

        <?php
$app->dbOld->query("
  SELECT COUNT(UserID)
  FROM users_info
  WHERE Inviter = ?", $UserID);
list($Invited) = $app->dbOld->next_record();
?>

        <!-- Invitees -->
        <tr id="para_invited_tr">
          <td class="label">
            <strong>Invitees</strong>
          </td>

          <td>
            <label>
              <input type="checkbox" name="p_invitedcount" <?=checked(!in_array('invitedcount', $Paranoia))?>
              /> Show count
            </label>
          </td>
        </tr>

        <?php
$app->dbOld->query("
  SELECT COUNT(ArtistID)
  FROM torrents_artists
  WHERE UserID = ?", $UserID);
list($ArtistsAdded) = $app->dbOld->next_record();
?>

        <!-- Artists added -->
        <tr id="para_artistsadded_tr">
          <td class="label">
            <strong>Artists added</strong>
          </td>

          <td>
            <label>
              <input type="checkbox" name="p_artistsadded" <?=checked(!in_array('artistsadded', $Paranoia))?>
              /> Show count
            </label>
          </td>
        </tr>

        <!-- Preview paranoia -->
        <tr id="para_preview_tr">
          <td></td>
          <td><a href="#" id="preview_paranoia" class="brackets">Preview paranoia</a></td>
        </tr>
      </table>

      <!-- Security -->
      <table cellpadding="6" cellspacing="1" border="0" width="100%" class="layout border user_options"
        id="security_settings">
        <tr class="colhead_dark">
          <td colspan="2">
            <strong>Security</strong>
          </td>
        </tr>

        <!-- 2FA, U2F, and PGP -->
        <tr id="acc_2fa_tr">
          <td class="label">
            <strong>2FA, U2F, and PGP</strong>
          </td>

          <td>
            <a href="user.php?action=2fa">Click here to view additional account security options</a>
          </td>
        </tr>

        <!-- Current password -->
        <tr id="acc_currentpassword_tr">
          <td class="label">
            <strong>Current password</strong>
          </td>

          <td>
            <div>
              <input type="password" size="40" name="cur_pass" id="cur_pass" maxlength="307200" value="" />
            </div>

            <strong class="important_text">
              When changing any of the settings below, you must enter your current password here
            </strong>
          </td>
        </tr>

        <tr id="acc_resetpk_tr">
          <td class="label">
            <strong>Reset passkey</strong>
          </td>

          <td>
            <div>
              <label>
                <input type="checkbox" name="resetpasskey" id="resetpasskey" />
                Reset your passkey?
              </label>
            </div>

            <p class="setting_description">
              Any active torrents must be downloaded again to continue leeching/seeding
            </p>
          </td>
        </tr>

        <!-- IRC key -->
        <tr id="acc_irckey_tr">
          <td class="label">
            <strong>IRC key</strong>
          </td>

          <td>
            <div>
              <input type="text" size="50" name="irckey" id="irckey"
                value="<?=Text::esc($IRCKey)?>" />
            </div>

            <p class="setting_description">
              This key will be used when authenticating with <?=BOT_NICK?> on the
              <a href="wiki.php?action=article&name=IRC">IRC network</a>.

            <ul>
              <li>This value is stored in plaintext and should not be your password</li>
              <li>IRC keys must be between 6 and 32 characters</li>
            </ul>
          </td>
        </tr>


        <!-- API keys -->
        <tr id="acc_api_keys_tr">
          <td class="label">
            <strong>API Keys</strong>
          </td>

          <td>
            <p>
              API keys can be generated to access our
              <a href="https://docs.biotorrents.de" target="_blank">JSON API</a>.
              Please rememeber to revoke tokens you no longer use.
            </p>

            <p>
              <strong class="important_text">
                Treat your tokens like passwords and keep them secret.
              </strong>
            </p>

            <table class="api_keys">
              <tr class="colhead">
                <th>Name</th>
                <th>Created</th>
                <th>Revoke</th>
              </tr>

              <?php
              $app->dbOld->query("
              SELECT
                `ID`,
                `Name`,
                `Token`,
                `Created`
              FROM
                `api_user_tokens`
              WHERE
                `UserID` = '$UserID'
                AND `Revoked` = '0'
              ORDER BY
                `Created`
                DESC
              ");

              foreach ($app->dbOld->to_array(false, MYSQLI_ASSOC, false) as $row) { ?>
              <tr>
                <td>
                  <?= $row['Name'] ?>
                </td>

                <td>
                  <?= time_diff($row['Created']) ?>
                </td>

                <td style='text-align: center'>
                  <a
                    href='user.php?action=token&amp;do=revoke&amp;user_id=<?=$user['ID'] ?>&amp;token_id=<?= $row['ID'] ?>'>Revoke</a>
              </tr>
              <?php
              } /* foreach */ ?>
            </table>

            <p>
              <a
                href='user.php?action=token&amp;user_id=<?= $user['ID'] ?>'>Click
                here to create a new token</a>
            </p>
          </td>
        </tr>


        <!-- Email address -->
        <tr id="acc_email_tr">
          <td class="label">
            <strong>Email address</strong>
          </td>

          <td>
            <div>
              <input type="email" size="50" name="email" id="email"
                value="<?=Text::esc($Email)?>" />
            </div>
          </td>
        </tr>

        <!-- Password -->
        <tr id="acc_password_tr">
          <td class="label">
            <strong>Password</strong>
          </td>

          <td>
            <div>
              <label>
                <input type="password" minlength="15" size="40" name="new_pass_1" id="new_pass_1" maxlength="307200"
                  value="" placeholder="New password" />
                <strong id="pass_strength"></strong>
              </label>
            </div>

            <div>
              <label>
                <input type="password" minlength="15" size="40" name="new_pass_2" id="new_pass_2" maxlength="307200"
                  value="" placeholder="Confirm new password" />
                <strong id="pass_match"></strong>
              </label>
            </div>

            <div>
              <textarea id="password_display" name="password_display" rows="2" cols="50" onclick="this.select();"
                readonly></textarea>
              <button type="button" id="password_create" onclick="pwgen('password_display');">Generate</button>
            </div>

            <p class="setting_description">
            </p>
          </td>
        </tr>
      </table>
    </div>
  </form>
</div>
<?php View::footer();
