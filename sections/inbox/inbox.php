<?php
#declare(strict_types=1);

$UserID = $user['ID'];

if (empty($_GET['action'])) {
    $Section = 'inbox';
} else {
    $Section = $_GET['action']; // either 'inbox' or 'sentbox'
}

if (!in_array($Section, array('inbox', 'sentbox'))) {
    error(404);
}

list($Page, $Limit) = Format::page_limit(MESSAGES_PER_PAGE);
View::header('Inbox');
?>

<h2 class="header">
  <?=($Section === 'sentbox' ? 'Sentbox' : 'Inbox')?>
</h2>

<div class="linkbox">
  <?php if ($Section === 'inbox') { ?>
  <a href="<?=Inbox::get_inbox_link('sentbox'); ?>"
    class="brackets">Sentbox</a>
  <?php } elseif ($Section === 'sentbox') { ?>
  <a href="<?=Inbox::get_inbox_link(); ?>" class="brackets">Inbox</a>
  <?php
  }

$Sort = empty($_GET['sort']) || $_GET['sort'] !== 'unread' ? 'Date DESC' : "cu.Unread = '1' DESC, DATE DESC";

$sql = "
  SELECT
    SQL_CALC_FOUND_ROWS
    c.ID,
    c.Subject,
    cu.Unread,
    cu.Sticky,
    cu.ForwardedTo,
    cu2.UserID,";
$sql .= $Section === 'sentbox' ? ' cu.SentDate ' : ' cu.ReceivedDate ';
$sql .= "AS Date
  FROM pm_conversations AS c
    LEFT JOIN pm_conversations_users AS cu ON cu.ConvID = c.ID AND cu.UserID = '$UserID'
    LEFT JOIN pm_conversations_users AS cu2 ON cu2.ConvID = c.ID AND cu2.UserID != '$UserID' AND cu2.ForwardedTo = 0
    LEFT JOIN users_main AS um ON um.ID = cu2.UserID";

if (!empty($_GET['search']) && $_GET['searchtype'] === 'message') {
    $sql .= ' JOIN pm_messages AS m ON c.ID = m.ConvID';
}

$sql .= ' WHERE ';
if (!empty($_GET['search'])) {
    $Search = db_string($_GET['search']);
    if ($_GET['searchtype'] === 'user') {
        $sql .= "um.Username LIKE '$Search' AND ";
    } elseif ($_GET['searchtype'] === 'subject') {
        $Words = explode(' ', $Search);
        $sql .= "c.Subject LIKE '%".implode("%' AND c.Subject LIKE '%", $Words)."%' AND ";
    } elseif ($_GET['searchtype'] === 'message') {
        $Words = explode(' ', $Search);
        $sql .= "m.Body LIKE '%".implode("%' AND m.Body LIKE '%", $Words)."%' AND ";
    }
}
$sql .= $Section === 'sentbox' ? ' cu.InSentbox' : ' cu.InInbox';
$sql .= " = '1'";

$sql .= "
  GROUP BY c.ID
  ORDER BY cu.Sticky, $Sort
  LIMIT $Limit";
$Results = $db->query($sql);
$db->query('SELECT FOUND_ROWS()');
list($NumResults) = $db->next_record();
$db->set_query_id($Results);
$Count = $db->record_count();

$Pages = Format::get_pages($Page, $NumResults, MESSAGES_PER_PAGE, 9);
echo $Pages;
?>
</div>

<div class="box pad">
  <?php if ($Count === 0 && empty($_GET['search'])) { ?>
  <h2>Your <?=($Section === 'sentbox' ? 'sentbox' : 'inbox')?>
    is empty.</h2>
  <?php } else { ?>
  <form class="search_form"
    name="<?=($Section === 'sentbox' ? 'sentbox' : 'inbox')?>"
    action="inbox.php" method="get" id="searchbox">
    <div>
      <input type="hidden" name="action" value="<?=$Section?>" />
      <input type="radio" name="searchtype" value="user" <?=(empty($_GET['searchtype']) || $_GET['searchtype'] === 'user' ? ' checked="checked"' : '')?>
      /> User
      <input type="radio" name="searchtype" value="subject" <?=(!empty($_GET['searchtype']) && $_GET['searchtype'] === 'subject' ? ' checked="checked"' : '')?>
      /> Subject
      <input type="radio" name="searchtype" value="message" <?=(!empty($_GET['searchtype']) && $_GET['searchtype'] === 'message' ? ' checked="checked"' : '')?>
      /> Message
      <span class="float_right">
        <?php // provide a temporary toggle for sorting PMs
    $ToggleTitle = 'Temporary toggle switch for sorting PMs. To permanently change the sorting behavior, edit the setting in your profile.';
    $BaseURL = 'inbox.php';

    if (isset($_GET['sort']) && $_GET['sort'] === 'unread') { ?>
        <a href="<?=$BaseURL?>" class="brackets tooltip"
          title="<?=$ToggleTitle?>">List latest first</a>
        <?php } else { ?>
        <a href="<?=$BaseURL?>?sort=unread" class="brackets tooltip"
          title="<?=$ToggleTitle?>">List
          unread first</a>
        <?php } ?>
      </span>
      <br />
      <input type="search" name="search"
        placeholder="<?=(!empty($_GET['search']) ? Text::esc($_GET['search']) : 'Search '.($Section === 'sentbox' ? 'sentbox' : 'inbox'))?>" />
    </div>
  </form>
  <form class="manage_form" name="messages" action="inbox.php" method="post" id="messageform">
    <input type="hidden" name="action" value="masschange" />
    <input type="hidden" name="auth"
      value="<?=$user['AuthKey']?>" />
    <input type="submit" name="read" class="button-primary" value="Mark as read" />
    <input type="submit" name="unread" value="Mark as unread" />
    <input type="submit" name="delete" value="Delete message(s)" />

    <table class="message_table checkboxes">
      <tr class="colhead">
        <td width="10"><input type="checkbox" onclick="toggleChecks('messageform', this);" /></td>
        <td width="50%">Subject</td>
        <td><?=($Section === 'sentbox' ? 'Receiver' : 'Sender')?>
        </td>
        <td>Date</td>
        <?php if (check_perms('users_mod')) { ?>
        <td>Forwarded to</td>
        <?php } ?>
      </tr>
      <?php
  if ($Count === 0) { ?>
      <tr class="a">
        <td colspan="5">No results.</td>
      </tr>
      <?php } else {
      while (list($ConvID, $Subject, $Unread, $Sticky, $ForwardedID, $SenderID, $Date) = $db->next_record()) {
          if ($Unread === '1') {
              $RowClass = 'unreadpm';
          } else {
              $RowClass = "row";
          } ?>
      <tr class="<?=$RowClass?>">
        <td class="center"><input type="checkbox" name="messages[]="
            value="<?=$ConvID?>" /></td>
        <td>
          <?php
          if ($Unread) {
              echo '<strong>';
          }
          if ($Sticky) {
              echo 'Sticky: ';
          }
          echo "\n"; ?>
          <a href="inbox.php?action=viewconv&amp;id=<?=$ConvID?>"><?=$Subject?></a>
          <?php
          if ($Unread) {
              echo "</strong>\n";
          } ?>
        </td>
        <td><?=Users::format_username($SenderID, true, true, true, true)?>
        </td>
        <td><?=time_diff($Date)?>
        </td>
        <?php if (check_perms('users_mod')) { ?>
        <td><?=(($ForwardedID && $ForwardedID != $user['ID']) ? Users::format_username($ForwardedID, false, false, false) : '')?>
        </td>
        <?php } ?>
      </tr>
      <?php
    $db->set_query_id($Results);
      }
  } ?>
    </table>
    <?php
    $MsgLimit = ($user['PostsPerPage']) ? $user['PostsPerPage'] : MESSAGES_PER_PAGE;
    if ($Count > $MsgLimit) { ?>
    <input type="submit" name="read" class="button-primary" value="Mark as read" />
    <input type="submit" name="unread" value="Mark as unread" />
    <input type="submit" name="delete" value="Delete message(s)" />
    <?php } ?>
  </form>
  <?php } ?>
</div>

<div class="linkbox">
  <?= $Pages ?>
</div>
<?php View::footer();
