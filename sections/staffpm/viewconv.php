<?php
#declare(strict_types = 1);

$app = App::go();

if ($ConvID = (int)$_GET['id']) {
    // Get conversation info
    $app->dbOld->query("
    SELECT Subject, UserID, Level, AssignedToUser, Unread, Status
    FROM staff_pm_conversations
    WHERE ID = $ConvID");
    list($Subject, $UserID, $Level, $AssignedToUser, $Unread, $Status) = $app->dbOld->next_record();

    $LevelCap = 1000;
    $PMLevel = $Level;
    $Level = min($Level, $LevelCap);

    if (!(
        ($UserID == $user['ID'])
      || ($AssignedToUser == $user['ID'])
      || (($Level > 0 && $Level <= $user['EffectiveClass']) || ($Level == 0 && $IsFLS))
    )) {
        // User is trying to view someone else's conversation
        error(403);
    }
    // User is trying to view their own unread conversation, set it to read
    if ($UserID == $user['ID'] && $Unread) {
        $app->dbOld->query("
      UPDATE staff_pm_conversations
      SET Unread = false
      WHERE ID = $ConvID");
        // Clear cache for user
        $app->cacheOld->delete_value("staff_pm_new_$user[ID]");
    }

    View::header(
        'Staff PM',
        'staffpm,vendor/easymde.min',
        'vendor/easymde.min'
    );

    $UserInfo = User::user_info($UserID);
    $UserStr = User::format_username($UserID, true, true, true, true);

    $OwnerID = $UserID;
    $OwnerName = $UserInfo['Username']; ?>
<div>
  <div class="header">
    <h2>Staff PM - <?=Text::esc($Subject)?>
    </h2>
    <div class="linkbox">
      <?php
  // Staff only
  if ($IsStaff) {
      ?>
      <a href="staffpm.php" class="brackets">My unanswered</a>
      <?php
  }

    // FLS/Staff
    if ($IsFLS) {
        ?>
      <a href="staffpm.php?view=unanswered" class="brackets">All unanswered</a>
      <a href="staffpm.php?view=open" class="brackets">Open</a>
      <a href="staffpm.php?view=resolved" class="brackets">Resolved</a>
      <?php
    // User
    } else {
        ?>
      <a href="staffpm.php" class="brackets">Back to inbox</a>
      <?php
    } ?>
    </div>
  </div>
  <br />
  <br />
  <div id="inbox">
    <?php
  // Get messages
  $StaffPMs = $app->dbOld->query("
    SELECT UserID, SentDate, Message, ID
    FROM staff_pm_messages
    WHERE ConvID = $ConvID");

    while (list($UserID, $SentDate, $Message, $MessageID) = $app->dbOld->next_record()) {
        // Set user string
        if ($UserID == $OwnerID) {
            // User, use prepared string
            $UserString = $UserStr;
            $Username = $OwnerName;
        } else {
            // Staff/FLS
            $UserInfo = User::user_info($UserID);
            $UserString = User::format_username($UserID, true, true, true, true);
            $Username = $UserInfo['Username'];
        } ?>
    <div class="box vertical_space" id="post<?=$MessageID?>">
      <div class="head">
        <a class="postid"
          href="staffpm.php?action=viewconv&amp;id=<?=$ConvID?>#post<?=$MessageID?>">#<?=$MessageID?></a>
        <strong>
          <?=$UserString?>
        </strong>
        <?=time_diff($SentDate, 2, true)?>
        <?php if ($Status != 'Resolved') { ?>
        - <a href="#quickpost"
          onclick="Quote('<?=$MessageID?>', '<?=$Username?>');"
          class="brackets">Quote</a>
        <?php } ?>
      </div>
      <div class="body"><?=Text::parse($Message)?>
      </div>
    </div>
    <div align="center" style="display: none;"></div>
    <?php
    $app->dbOld->set_query_id($StaffPMs);
    }

    // Common responses
    if ($IsFLS && $Status != 'Resolved') {
        ?>
    <div id="common_answers" class="hidden">
      <div class="box vertical_space">
        <div class="head">
          <strong>Preview</strong>
        </div>
        <div id="common_answers_body" class="body">Select an answer from the drop-down to view it.</div>
      </div>
      <br />
      <div class="center">
        <select id="common_answers_select" onchange="UpdateMessage();">
          <option id="first_common_response">Select a message</option>
          <?php
    // List common responses
    $app->dbOld->query("
      SELECT ID, Name
      FROM staff_pm_responses");
        while (list($ID, $Name) = $app->dbOld->next_record()) {
            ?>
          <option value="<?=$ID?>"><?=$Name?>
          </option>
          <?php
        } ?>
        </select>
        <input type="button" value="Set message" onclick="SetMessage();" />
        <input type="button" value="Create new / Edit"
          onclick="location.href='staffpm.php?action=responses&amp;convid=<?=$ConvID?>';" />
      </div>
    </div>
    <?php
    }

    // Ajax assign response div
    if ($IsStaff) {
        ?>
    <div id="ajax_message" class="hidden center alertbar"></div>
    <?php
    }

    // Reply box and buttons?>
    <h3>Reply</h3>
    <div class="box pad" id="reply_box">
      <div id="buttons" class="center">
        <form class="manage_form" name="staff_messages" action="staffpm.php" method="post" id="messageform">
          <input type="hidden" name="action" value="takepost" />
          <input type="hidden" name="convid" value="<?=$ConvID?>"
            id="convid" />
          <?php
          if ($Status != 'Resolved') {
              $TextPrev = View::textarea(
                  id: 'quickpost',
                  name: 'message',
              );
          } ?>
          <br />
          <?php
  // Assign to
  if ($IsStaff) {
      // Staff assign dropdown
?>
          <select id="assign_to" name="assign">
            <optgroup label="User classes">
              <?php // FLS "class"
    $Selected = ((!$AssignedToUser && $PMLevel == 0) ? ' selected="selected"' : ''); ?>
              <option value="class_0" <?=$Selected?>>First Line
                Support</option>
              <?php // Staff classes
    foreach ($ClassLevels as $Class) {
        // Create one <option> for each staff user class
        if ($Class['Level'] >= 650) {
            $Selected = ((!$AssignedToUser && ($PMLevel == $Class['Level'])) ? ' selected="selected"' : ''); ?>
              <option
                value="class_<?=$Class['Level']?>"
                <?=$Selected?>><?=$Class['Name']?>
              </option>
              <?php
        }
    } ?>
            </optgroup>
            <optgroup label="Staff">
              <?php // Staff members
    $app->dbOld->query(
        "
      SELECT
        m.ID,
        m.Username
      FROM permissions AS p
        JOIN users_main AS m ON m.PermissionID = p.ID
      WHERE p.DisplayStaff = '1'
      ORDER BY p.Level DESC, m.Username ASC"
    );
      while (list($ID, $Name) = $app->dbOld->next_record()) {
          // Create one <option> for each staff member
      $Selected = (($AssignedToUser == $ID) ? ' selected="selected"' : ''); ?>
              <option value="user_<?=$ID?>" <?=$Selected?>><?=$Name?>
              </option>
              <?php
      } ?>
            </optgroup>
            <optgroup label="First Line Support">
              <?php
    // FLS users
    $app->dbOld->query("
      SELECT
        m.ID,
        m.Username
      FROM users_info AS i
        JOIN users_main AS m ON m.ID = i.UserID
        JOIN permissions AS p ON p.ID = m.PermissionID
      WHERE p.DisplayStaff != '1'
        AND i.SupportFor != ''
      ORDER BY m.Username ASC
    ");
      while (list($ID, $Name) = $app->dbOld->next_record()) {
          // Create one <option> for each FLS user
      $Selected = (($AssignedToUser == $ID) ? ' selected="selected"' : ''); ?>
              <option value="user_<?=$ID?>" <?=$Selected?>><?=$Name?>
              </option>
              <?php
      } ?>
            </optgroup>
          </select>
          <input type="button" onclick="Assign();" value="Assign" />
          <?php
  } elseif ($IsFLS) { // FLS assign button?>
          <input type="button" value="Assign to staff"
            onclick="location.href='staffpm.php?action=assign&amp;to=staff&amp;convid=<?=$ConvID?>';" />
          <input type="button" value="Assign to forum staff"
            onclick="location.href='staffpm.php?action=assign&amp;to=forum&amp;convid=<?=$ConvID?>';" />
          <?php
  }

    if ($Status != 'Resolved') { ?>
          <input type="button" value="Resolve"
            onclick="location.href='staffpm.php?action=resolve&amp;id=<?=$ConvID?>';" />
          <?php if ($IsFLS) { //Moved by request?>
          <input type="button" value="Common answers" data-toggle-target="#common_answers" />
          <?php } ?>
          <input type="button" id="previewbtn" value="Preview"
            class="hidden button_preview_<?=$TextPrev->getID()?>" />
          <input type="submit" value="Send message" />
          <?php } else { ?>
          <input type="button" value="Unresolve"
            onclick="location.href='staffpm.php?action=unresolve&amp;id=<?=$ConvID?>';" />
          <?php
  }
    if (check_perms('users_give_donor')) { ?>
          <br />
          <input type="button" value="Make Donor" data-toggle-target="#make_donor_form" />
          <?php } ?>
        </form>
        <?php if (check_perms('users_give_donor')) { ?>
        <div id="make_donor_form" class="hidden">
          <form action="staffpm.php" method="post">
            <input type="hidden" name="action" value="make_donor" />
            <input type="hidden" name="auth"
              value="<?=$user['AuthKey']?>" />
            <input type="hidden" name="id" value="<?=$ConvID?>" />
            <strong>Amount: </strong>
            <input type="text" name="donation_amount" onkeypress="return isNumberKey(event);" />
            <br />
            <strong>Reason: </strong>
            <input type="text" name="donation_reason" />
            <br />
            <select name="donation_source">
              <option value="Flattr">Flattr</option>
            </select>
            <select name="donation_currency">
              <option value="EUR">EUR</option>
            </select>
            <input type="submit" value="Submit" />
          </form>
        </div>
        <?php } ?>
      </div>
    </div>
  </div>
</div>
<?php

  View::footer();
} else {
    // No ID
    Http::redirect("staffpm.php");
}
