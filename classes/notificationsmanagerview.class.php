<?php

class NotificationsManagerView
{
    private static $Settings;

    public static function load_js()
    {
        $JSIncludes = array(
            'noty/noty.js',
            'noty/layouts/bottomRight.js',
            'noty/themes/default.js',
            'user_notifications.js');

        foreach ($JSIncludes as $JSInclude) {
            $Path = STATIC_SERVER."functions/$JSInclude"; ?>
<script
  src="<?=$Path?>?v=<?=filemtime(SERVER_ROOT."/$Path")?>"
  type="text/javascript"></script>
<?php
        }
    }

    public static function render_settings($Settings)
    {
        self::$Settings = $Settings; ?>
<tr>
  <td class="label">
    <strong>News Announcements</strong>
  </td>
  <td>
    <?php self::render_checkbox(NotificationsManager::NEWS); ?>
  </td>
</tr>
<tr>
  <td class="label">
    <strong>Blog Announcements</strong>
  </td>
  <td>
    <?php self::render_checkbox(NotificationsManager::BLOG); ?>
  </td>
</tr>
<tr>
  <td class="label">
    <strong>Inbox Messages</strong>
  </td>
  <td>
    <?php self::render_checkbox(NotificationsManager::INBOX, true); ?>
  </td>
</tr>
<tr>
  <td class="label tooltip"
    title="Notify when you receive a new private message from <?=SITE_NAME?> staff">
    <strong>Staff Messages</strong>
  </td>
  <td>
    <?php self::render_checkbox(NotificationsManager::STAFFPM, false, false); ?>
  </td>
</tr>
<tr>
  <td class="label">
    <strong>Thread Subscriptions</strong>
  </td>
  <td>
    <?php self::render_checkbox(NotificationsManager::SUBSCRIPTIONS, false, false); ?>
  </td>
</tr>
<tr>
  <td class="label tooltip" title="Notify whenever someone quotes you in the forums">
    <strong>Quote Notifications</strong>
  </td>
  <td>
    <?php self::render_checkbox(NotificationsManager::QUOTES); ?>
  </td>
</tr>
<?php if (check_perms('site_torrents_notify')) { ?>
<tr>
  <td class="label tooltip" title="Notify when your torrent notification filters are triggered">
    <strong>Torrent Notifications</strong>
  </td>
  <td>
    <?php self::render_checkbox(NotificationsManager::TORRENTS, true, false); ?>
  </td>
</tr>
<?php } ?>

<tr>
  <td class="label tooltip" title="Notify when a torrent is added to a subscribed collage">
    <strong>Collage Subscriptions</strong>
  </td>
  <td>
    <?php self::render_checkbox(NotificationsManager::COLLAGES. false, false); ?>
  </td>
</tr>
<?php
    }

    private static function render_checkbox($Name, $Traditional = false)
    {
        $Checked = self::$Settings[$Name];
        $PopupChecked = $Checked === NotificationsManager::OPT_POPUP || !isset($Checked) ? ' checked="checked"' : '';
        $TraditionalChecked = $Checked === NotificationsManager::OPT_TRADITIONAL ? ' checked="checked"' : ''; ?>
<label>
  <input type="checkbox" name="notifications_<?=$Name?>_popup"
    id="notifications_<?=$Name?>_popup" <?=$PopupChecked?> />
  Pop-up
</label>
<?php if ($Traditional) { ?>
<label>
  <input type="checkbox" name="notifications_<?=$Name?>_traditional"
    id="notifications_<?=$Name?>_traditional" <?=$TraditionalChecked?> />
  Traditional
</label>
<?php
      }
    }

    public static function format_traditional($Contents)
    {
        return "<a href=\"$Contents[url]\">$Contents[message]</a>";
    }
}
