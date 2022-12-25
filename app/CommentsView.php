<?php
#declare(strict_types=1);


/**
 * THIS IS GOING AWAY
 */

class CommentsView
{
    /**
     * Render a thread of comments
     * @param array $Thread An array as returned by Comments::load
     * @param int $LastRead PostID of the last read post
     * @param string $Baselink Link to the site these comments are on
     */
    public static function render_comments($Thread, $LastRead, $Baselink)
    {
        foreach ($Thread as $Post) {
            list($PostID, $AuthorID, $AddedTime, $CommentBody, $EditedUserID, $EditedTime, $EditedUsername) = array_values($Post);
            self::render_comment($AuthorID, $PostID, $CommentBody, $AddedTime, $EditedUserID, $EditedTime, $Baselink . "&amp;postid=$PostID#post$PostID", ($PostID > $LastRead));
        }
    }

    /**
     * Render one comment
     * @param int $AuthorID
     * @param int $PostID
     * @param string $Body
     * @param string $AddedTime
     * @param int $EditedUserID
     * @param string $EditedTime
     * @param string $Link The link to the post elsewhere on the site
     * @param string $Header The header used in the post
     * @param bool $Tools Whether or not to show [Edit], [Report] etc.
     *
     * todo: Find a better way to pass the page (artist, collages, requests, torrents) to this function than extracting it from $Link
     */
    public static function render_comment($AuthorID, $PostID, $Body, $AddedTime, $EditedUserID, $EditedTime, $Link, $Unread = false, $Header = '', $Tools = true)
    {
        $app = App::go();

        $UserInfo = Users::user_info($AuthorID);
        $Header = Users::format_username($AuthorID, true, true, true, true, true) . time_diff($AddedTime) . $Header; ?>
<table
  class="forum_post box vertical_margin<?=(!Users::hasAvatarsEnabled() ? ' noavatar' : '') . ($Unread ? ' forum_unread' : '')?>"
  id="post<?=$PostID?>">
  <colgroup>
    <?php if (Users::hasAvatarsEnabled()) { ?>
    <col class="col_avatar" />
    <?php } ?>
    <col class="col_post_body" />
  </colgroup>
  <tr class="colhead_dark">
    <td colspan="<?=(Users::hasAvatarsEnabled() ? 2 : 1)?>">
      <div class="u-pull-left"><a class="post_id"
          href="<?=$Link?>">#<?=$PostID?></a>
        <?=$Header?>
        <?php if ($Tools) { ?>
        - <a href="#quickpost"
          onclick="Quote('<?=$PostID?>','<?=$UserInfo['Username']?>', true);"
          class="brackets">Quote</a>
        <?php if ($AuthorID == $app->userNew->core["id"] || check_perms('site_moderate_forums')) { ?>
        - <a href="#post<?=$PostID?>"
          onclick="Edit_Form('<?=$PostID?>','');"
          class="brackets">Edit</a>
        <?php }
      if (check_perms('site_moderate_forums')) { ?>
        - <a href="#post<?=$PostID?>"
          onclick="Delete('<?=$PostID?>');"
          class="brackets">Delete</a>
        <?php } ?>
      </div>
      <div id="bar<?=$PostID?>" class="u-pull-right">
        <a href="reports.php?action=report&amp;type=comment&amp;id=<?=$PostID?>"
          class="brackets">Report</a>
        <?php
      if (check_perms('users_warn') && $AuthorID != $app->userNew->core["id"] && $app->userNew->extra['Class'] >= $UserInfo['Class']) {
          ?>
        <form class="manage_form hidden" name="user"
          id="warn<?=$PostID?>" action="comments.php" method="post">
          <input type="hidden" name="action" value="warn" />
          <input type="hidden" name="postid" value="<?=$PostID?>" />
        </form>
        - <a href="#"
          onclick="$('#warn<?=$PostID?>').raw().submit(); return false;"
          class="brackets">Warn</a>
        <?php
      } ?>
        &nbsp;
        <a href="#">&uarr;</a>
        <?php } ?>
      </div>
    </td>
  </tr>
  <tr>
    <?php if (Users::hasAvatarsEnabled()) { ?>
    <td class="avatar" valign="top">
      <?=Users::show_avatar($UserInfo['Avatar'], $AuthorID, $UserInfo['Username'], $app->userNew->extra['DisableAvatars'])?>
    </td>
    <?php } ?>
    <td class="body" valign="top">
      <div id="content<?=$PostID?>">
        <?=Text::parse($Body)?>
        <?php if ($EditedUserID) { ?>
        <br />
        <br />
        <div class="last_edited">
          <?php if (check_perms('site_admin_forums')) { ?>
          <a href="#content<?=$PostID?>"
            onclick="LoadEdit('<?=substr($Link, 0, strcspn($Link, '.'))?>', <?=$PostID?>, 1); return false;">&laquo;</a>
          <?php } ?>
          Last edited by
          <?=Users::format_username($EditedUserID, false, false, false) ?>
          <?=time_diff($EditedTime, 2, true, true)?>
          <?php } ?>
        </div>
      </div>
    </td>
  </tr>
</table>
<?php
    }
}
