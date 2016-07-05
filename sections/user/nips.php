<?
$Amount = (int) db_string($_POST['amount']);
$To = (int) db_string($_POST['to']);
$UserID = (int) $LoggedUser['ID'];
$Adjust = isset($_POST['adjust'])?true:false;
$Message = $_POST['message'];

// 10% tax
$Tax = 0.1;

if ($LoggedUser['DisableNips']) {
  $Err = 'You are not allowed to send nips.';
} else {
  if ($Adjust)
    $Amount = $Amount/(1-$Tax);

  $SentAmount = (int) ($Amount*(1-$Tax));

  $Amount = (int) $Amount;

  if ($UserID == $To) {
    $Err = 'If you sent nips to yourself it wouldn\'t even do anything. Stop that.';
  } elseif ($Amount < 0) {
    $Err = 'You can\'t a negative amount you shitter.';
  } elseif ($Amount < 100) {
    $Err = 'You must send at least 100 Nips.';
  } else {
    $DB->query("
      SELECT ui.DisableNips
      FROM users_main AS um
        JOIN users_info AS ui ON um.ID = ui.UserID
      WHERE ID = $To");
    if (!$DB->has_results()) {
      $Err = 'That user doesn\'t exist.';
    } else {
      list($Disabled) = $DB->next_record();
      if ($Disabled) {
        $Err = "This user is not allowed to receive nips.";
      } else {
        $DB->query("
          SELECT BonusPoints
          FROM users_main
          WHERE ID = $UserID");
        if ($DB->has_results()) {
          list($BP) = $DB->next_record();

          if ($BP < $Amount) {
            $Err = 'You don\'t have enough Nips.';
          } else {
            $DB->query("
              UPDATE users_main
              SET BonusPoints = BonusPoints - $Amount
              WHERE ID = $UserID");
            $DB->query("
              UPDATE users_main
              SET BonusPoints = BonusPoints + ".$SentAmount."
              WHERE ID = $To");

            $UserInfo = Users::user_info($UserID);
            $ToInfo = Users::user_info($To);

            $DB->query("
              UPDATE users_info
              SET AdminComment = CONCAT('".sqltime()." - Sent $Amount Nips (".$SentAmount." after tax) to [user]".$ToInfo['Username']."[/user]\n\n', AdminComment)
              WHERE UserID = $UserID");
            $DB->query("
              UPDATE users_info
              SET AdminComment = CONCAT('".sqltime()." - Received ".$SentAmount." Nips from [user]".$UserInfo['Username']."[/user]\n\n', AdminComment)
              WHERE UserID = $To");

            $PM = '[user]'.$UserInfo['Username'].'[/user] has sent you a gift of '.$SentAmount.' Nips!';

            if (!empty($Message)) {
              $PM .= "\n\n".'[quote='.$UserInfo['Username'].']'.$Message.'[/quote]';
            }

            Misc::send_pm($To, 0, 'You\'ve received a gift!', $PM);

            $Cache->delete_value('user_info_heavy_'.$UserID);
            $Cache->delete_value('user_stats_'.$UserID);
            $Cache->delete_value('user_info_heavy_'.$To);
            $Cache->delete_value('user_stats_'.$To);
          }
        } else {
          $Err = 'An unknown error occurred.';
        }
      }
    }
  }
}

View::show_header('Send Nips'); ?>
<div class='thin'>
  <h2 id='general'>Send Nips</h2>
  <div class='box pad' style='padding: 10px 10px 10px 20p;'>
    <p><?=$Err?'Error: '.$Err:'Sent '.$Amount.' Nips ('.$SentAmount.' after tax) to '.$ToInfo['Username'].'.'?></p>
    <p><a href='/user.php?id=<?=$To?>'>Return</a></p>
  </div>
</div>
<? View::show_footer(); ?>
