<?php
#declare(strict_types=1);

/***********************************************
 * This file displays the list of available tools in the staff toolbox.
 *
 * Because there are various subcontainers and various permissions, it
 * is possible to have empty subcontainers. The $ToolsHTML variable is
 * used to display only non-empty subcontainers.
 *
 ***********************************************
 */

if (!check_perms('users_mod')) {
    error(403);
}

/**
 * Used for rendering a single table row in the staff toolbox. The
 * $ToolsHTML variable is incrementally expanded with each function call
 * in a given subcontainer and gets reset at the beginning of each new
 * subcontainer.
 *
 * @param string $Title - the displayed name of the tool
 * @param string $URL - the relative URL of the tool
 * @param bool $HasPermission - whether the user has permission to view/use the tool
 * @param string $Tooltip - optional tooltip
 *
 */
function create_row($Title, $URL, $HasPermission = false, $Tooltip = false)
{
    if ($HasPermission) {
        global $ToolsHTML;
        $TooltipHTML = $Tooltip !== false ? " class=\"tooltip\" title=\"$Tooltip\"" : "";
        $ToolsHTML .= "\t\t\t\t<tr><td><a href=\"$URL\"$TooltipHTML>$Title</a></td></tr>\n";
    }
}

View::header('Staff Tools');
?>
<div class="permissions">
  <div class="permission_container">
    <!-- begin left column -->
    <?php
  // begin Administration category
  $ToolsHTML = "";
  create_row("Client whitelist", "tools.php?action=whitelist", check_perms("admin_whitelist"));
  create_row("Create user", "tools.php?action=create_user", check_perms("admin_create_users"));
  create_row("Permissions manager", "tools.php?action=permissions", check_perms("admin_manage_permissions"));
  create_row("Special users", "tools.php?action=special_users", check_perms("admin_manage_permissions"));
  create_row("Database key", "tools.php?action=database_key", check_perms("admin_manage_permissions"));

  if ($ToolsHTML) {
      ?>
    <div class="permission_subcontainer">
      <table class="admin-tools skeleton-fix">
        <tr class="colhead">
          <td>Administration</td>
        </tr>
        <?=       $ToolsHTML ?>
      </table>
    </div>
    <?php
  }

  // begin Announcements category
  $ToolsHTML = "";
  create_row("Global notification", "tools.php?action=global_notification", check_perms("users_mod"));
  create_row("Mass PM", "tools.php?action=mass_pm", check_perms("users_mod"));
  create_row("News post", "tools.php?action=news", check_perms("admin_manage_news"));

  if ($ToolsHTML) {
      ?>
    <div class="permission_subcontainer">
      <table class="admin-tools skeleton-fix">
        <tr class="colhead">
          <td>Announcements</td>
        </tr>
        <?=       $ToolsHTML ?>
      </table>
    </div>
    <?php
  }

  // begin Community category
  $ToolsHTML = "";
  create_row("Forum manager", "tools.php?action=forum", check_perms("admin_manage_forums"));

  if ($ToolsHTML) {
      ?>
    <div class="permission_subcontainer">
      <table class="admin-tools skeleton-fix">
        <tr class="colhead">
          <td>Community</td>
        </tr>
        <?=       $ToolsHTML ?>
      </table>
    </div>
    <?php
  } ?>
    <!-- end left column -->
  </div>
  <div class="permission_container">
    <!-- begin middle column -->
    <?php
  // begin Queue category
  $ToolsHTML = "";
  create_row("Auto-Enable requests", "tools.php?action=enable_requests", check_perms("users_mod"));
  create_row("Login watch", "tools.php?action=login_watch", check_perms("admin_login_watch"));

  if ($ToolsHTML) {
      ?>
    <div class="permission_subcontainer">
      <table class="admin-tools skeleton-fix">
        <tr class="colhead">
          <td>Queue</td>
        </tr>
        <?=       $ToolsHTML ?>
      </table>
    </div>
    <?php
  }

  // begin Managers category
  $ToolsHTML = "";
  create_row("Email blacklist", "tools.php?action=email_blacklist", check_perms("users_view_email"));
  create_row("IP address bans", "tools.php?action=ip_ban", check_perms("admin_manage_ipbans"));
  create_row("Manipulate invite tree", "tools.php?action=manipulate_tree", check_perms("users_mod"));

  if ($ToolsHTML) {
      ?>
    <div class="permission_subcontainer">
      <table class="admin-tools skeleton-fix">
        <tr class="colhead">
          <td>Managers</td>
        </tr>
        <?=       $ToolsHTML ?>
      </table>
    </div>
    <?php
  }

  // begin Development category
  $ToolsHTML = "";
  create_row("Clear/view a cache key", "tools.php?action=clear_cache", check_perms("users_mod"));
  create_row("Rerender stylesheet gallery images", "tools.php?action=rerender_gallery", check_perms("site_debug") || check_perms("users_mod"));
  create_row("Schedule", "schedule.php?auth=$user[AuthKey]", check_perms("site_debug"));
  create_row("Service stats", "tools.php?action=service_stats", check_perms("site_debug"));
  create_row("Miscellaneous values", "tools.php?action=misc_values", check_perms('users_mod'));
  create_row("Tracker info", "tools.php?action=ocelot_info", check_perms("users_mod"));

  if ($ToolsHTML) {
      ?>
    <div class="permission_subcontainer">
      <table class="admin-tools skeleton-fix">
        <tr class="colhead">
          <td>Development</td>
        </tr>
        <?= $ToolsHTML ?>
      </table>
    </div>
    <?php
  } ?>

    <!-- end middle column -->
  </div>
  <div class="permission_container">
    <!-- begin right column -->
    <?php
  // begin Site Information category
  $ToolsHTML = "";
  create_row("Invite pool", "tools.php?action=invite_pool", check_perms("users_view_invites"));
  create_row("Registration log", "tools.php?action=registration_log", check_perms("users_view_ips") && check_perms("users_view_email"));
  create_row("Upscale pool", "tools.php?action=upscale_pool", check_perms("site_view_flow"));

  if ($ToolsHTML) {
      ?>
    <div class="permission_subcontainer">
      <table class="admin-tools skeleton-fix">
        <tr class="colhead">
          <td>Site Information</td>
        </tr>
        <?=       $ToolsHTML ?>
      </table>
    </div>
    <?php
  }

  // begin Torrents category
  $ToolsHTML = "";
  create_row("Collage recovery", "collages.php?action=recover", check_perms("site_collages_recover"));
  create_row("Manage freeleech tokens", "tools.php?action=tokens", check_perms("users_mod"));
  create_row("Multiple freeleech", "tools.php?action=multiple_freeleech", check_perms("users_mod"));
  create_row("Tag aliases", "tools.php?action=tag_aliases", check_perms("users_mod"));
  create_row("Batch tag editor", "tools.php?action=edit_tags", check_perms("users_mod"));
  create_row("Official tags manager", "tools.php?action=official_tags", check_perms("users_mod"));
  create_row("Sitewide freeleech manager", "tools.php?action=freeleech", check_perms("users_mod"));

  if ($ToolsHTML) {
      ?>
    <div class="permission_subcontainer">
      <table class="admin-tools skeleton-fix">
        <tr class="colhead">
          <td>Torrents</td>
        </tr>
        <?= $ToolsHTML ?>
      </table>
    </div>
    <?php
  } ?>
    <!-- end right column -->
  </div>
</div>
<?php View::footer();
