<?php
#declare(strict_types=1);

function link_users($UserID, $TargetID)
{
    global $db, $user;

    authorize();
    if (!check_perms('users_mod')) {
        error(403);
    }

    if (!is_number($UserID) || !is_number($TargetID)) {
        error(403);
    }

    if ($UserID == $TargetID) {
        return;
    }

    $db->query("
    SELECT 1
    FROM users_main
    WHERE ID IN ($UserID, $TargetID)");
    if ($db->record_count() !== 2) {
        error(403);
    }

    $db->query("
    SELECT GroupID
    FROM users_dupes
    WHERE UserID = $TargetID");
    list($TargetGroupID) = $db->next_record();

    $db->query("
    SELECT u.GroupID, d.Comments
    FROM users_dupes AS u
      JOIN dupe_groups AS d ON d.ID = u.GroupID
    WHERE UserID = $UserID");
    list($UserGroupID, $Comments) = $db->next_record();

    $UserInfo = Users::user_info($UserID);
    $TargetInfo = Users::user_info($TargetID);

    if (!$UserInfo || !$TargetInfo) {
        return;
    }

    if ($TargetGroupID) {
        if ($TargetGroupID == $UserGroupID) {
            return;
        }
        if ($UserGroupID) {
            $db->query("
        UPDATE users_dupes
        SET GroupID = $TargetGroupID
        WHERE GroupID = $UserGroupID");

            $db->query("
        UPDATE dupe_groups
        SET Comments = CONCAT('".db_string($Comments)."\n\n',Comments)
        WHERE ID = $TargetGroupID");
        
            $db->query("DELETE FROM dupe_groups WHERE ID = $UserGroupID");
            $GroupID = $UserGroupID;
        } else {
            $db->query("INSERT INTO users_dupes (UserID, GroupID) VALUES ($UserID, $TargetGroupID)");
            $GroupID = $TargetGroupID;
        }
    } elseif ($UserGroupID) {
        $db->query("INSERT INTO users_dupes (UserID, GroupID) VALUES ($TargetID, $UserGroupID)");
        $GroupID = $UserGroupID;
    } else {
        $db->query("INSERT INTO dupe_groups () VALUES ()");
        $GroupID = $db->inserted_id();
        $db->query("INSERT INTO users_dupes (UserID, GroupID) VALUES ($TargetID, $GroupID)");
        $db->query("INSERT INTO users_dupes (UserID, GroupID) VALUES ($UserID, $GroupID)");
    }

    $AdminComment = sqltime()." - Linked accounts updated: [user]".$UserInfo['Username']."[/user] and [user]".$TargetInfo['Username']."[/user] linked by ".$user['Username'];
    $db->query("
    UPDATE users_info AS i
      JOIN users_dupes AS d ON d.UserID = i.UserID
    SET i.AdminComment = CONCAT('".db_string($AdminComment)."\n\n', i.AdminComment)
    WHERE d.GroupID = $GroupID");
}

function unlink_user($UserID)
{
    global $db, $user;

    authorize();
    if (!check_perms('users_mod')) {
        error(403);
    }

    if (!is_number($UserID)) {
        error(403);
    }

    $UserInfo = Users::user_info($UserID);
    if ($UserInfo === false) {
        return;
    }
    
    $AdminComment = sqltime()." - Linked accounts updated: [user]".$UserInfo['Username']."[/user] unlinked by ".$user['Username'];
    $db->query("
    UPDATE users_info AS i
      JOIN users_dupes AS d1 ON d1.UserID = i.UserID
      JOIN users_dupes AS d2 ON d2.GroupID = d1.GroupID
    SET i.AdminComment = CONCAT('".db_string($AdminComment)."\n\n', i.AdminComment)
    WHERE d2.UserID = $UserID");
    $db->query("DELETE FROM users_dupes WHERE UserID = '$UserID'");
    $db->query("
    DELETE g.*
    FROM dupe_groups AS g
      LEFT JOIN users_dupes AS u ON u.GroupID = g.ID
    WHERE u.GroupID IS NULL");
}

function delete_dupegroup($GroupID)
{
    global $db;

    authorize();
    if (!check_perms('users_mod')) {
        error(403);
    }

    if (!is_number($GroupID)) {
        error(403);
    }

    $db->query("DELETE FROM dupe_groups WHERE ID = '$GroupID'");
}

function dupe_comments($GroupID, $Comments)
{
    global $db, $user;

    authorize();
    if (!check_perms('users_mod')) {
        error(403);
    }

    if (!is_number($GroupID)) {
        error(403);
    }

    $db->query("
    SELECT SHA1(Comments) AS CommentHash
    FROM dupe_groups
    WHERE ID = $GroupID");
    list($OldCommentHash) = $db->next_record();
    if ($OldCommentHash != sha1($Comments)) {
        $AdminComment = sqltime()." - Linked accounts updated: Comments updated by ".$user['Username'];
        if ($_POST['form_comment_hash'] == $OldCommentHash) {
            $db->query("
        UPDATE dupe_groups
        SET Comments = '".db_string($Comments)."'
        WHERE ID = '$GroupID'");
        } else {
            $db->query("
        UPDATE dupe_groups
        SET Comments = CONCAT('".db_string($Comments)."\n\n',Comments)
        WHERE ID = '$GroupID'");
        }

        $db->query("
      UPDATE users_info AS i
        JOIN users_dupes AS d ON d.UserID = i.UserID
      SET i.AdminComment = CONCAT('".db_string($AdminComment)."\n\n', i.AdminComment)
      WHERE d.GroupID = $GroupID");
    }
}

function user_dupes_table($UserID)
{
    global $db, $user;

    if (!check_perms('users_mod')) {
        error(403);
    }

    # todo: Strict equality in is_number() breaks admin view
    if (!is_number($UserID)) {
        error(403);
    }

    $db->query("
    SELECT d.ID, d.Comments, SHA1(d.Comments) AS CommentHash
    FROM dupe_groups AS d
      JOIN users_dupes AS u ON u.GroupID = d.ID
    WHERE u.UserID = $UserID");
    if (list($GroupID, $Comments, $CommentHash) = $db->next_record()) {
        $db->query("
      SELECT m.ID
      FROM users_main AS m
        JOIN users_dupes AS d ON m.ID = d.UserID
      WHERE d.GroupID = $GroupID
      ORDER BY m.ID ASC");
        $DupeCount = $db->record_count();
        $Dupes = $db->to_array();
    } else {
        $DupeCount = 0;
        $Dupes = [];
    } ?>
<form class="manage_form" name="user" method="post" id="linkedform" action="">
  <input type="hidden" name="action" value="dupes" />
  <input type="hidden" name="dupeaction" value="update" />
  <input type="hidden" name="userid" value="<?=$UserID?>" />
  <input type="hidden" id="auth" name="auth"
    value="<?=$user['AuthKey']?>" />
  <input type="hidden" id="form_comment_hash" name="form_comment_hash"
    value="<?=$CommentHash?>" />
  <div class="box" id="l_a_box">
    <div class="head">
      Linked Accounts (<?=max($DupeCount - 1, 0)?>) <span
        class="float_right"><a data-toggle-target=".linkedaccounts" class="brackets">Toggle</a></span>
    </div>
    <table width="100%" class="layout hidden linkedaccounts">
      <?=($DupeCount ? "<tr>\n" : '')?>
      <?php
  $i = 0;
    foreach ($Dupes as $Dupe) {
        $i++;
        list($DupeID) = $Dupe;
        $DupeInfo = Users::user_info($DupeID); ?>
      <td align="left"><?=Users::format_username($DupeID, true, true, true, true)?>
        <a href="user.php?action=dupes&amp;dupeaction=remove&amp;auth=<?=$user['AuthKey']?>&amp;userid=<?=$UserID?>&amp;removeid=<?=$DupeID?>"
          onclick="return confirm('Are you sure you wish to remove <?=$DupeInfo['Username']?> from this group?');"
          class="brackets tooltip" title="Remove linked account">X</a>
      </td>
      <?php
    if ($i == 4) {
        $i = 0;
        echo "\t\t\t\t\t</tr>\n\t\t\t\t\t<tr>\n";
    }
    }
    if ($DupeCount) {
        if ($i !== 0) {
            for ($j = $i; $j < 4; $j++) {
                echo "\t\t\t\t\t\t<td>&nbsp;</td>\n";
            }
        } ?>
      </tr>
      <?php
    } ?>
      <tr>
        <td colspan="5" align="left" style="border-top: thin solid;"><strong>Comments:</strong></td>
      </tr>
      <tr>
        <td colspan="5" align="left">
          <div id="dupecomments"
            class="<?=($DupeCount ? '' : 'hidden')?>">
            <?=Text::parse($Comments); ?>
          </div>
          <div id="editdupecomments"
            class="<?=($DupeCount ? 'hidden' : '')?>">
            <textarea name="dupecomments" onkeyup="resize('dupecommentsbox');" id="dupecommentsbox" cols="65" rows="5"
              style="width: 98%;"><?=esc($Comments)?></textarea>
          </div>
          <span class="float_right"><a href="#"
              onclick="$('#dupecomments').gtoggle(); $('#editdupecomments').gtoggle(); resize('dupecommentsbox'); return false;"
              class="brackets">Edit linked account comments</a></span>
        </td>
      </tr>
    </table>
    <div class="pad hidden linkedaccounts">
      <label for="target">Link this user with: </label>
      <input type="text" name="target" id="target" />
      <input type="submit" value="Update" id="submitlink" />
    </div>
  </div>
</form>
<?php
}
