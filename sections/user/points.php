<?php

declare(strict_types=1);

$app = \Gazelle\App::go();

$Amount = (int) db_string($_POST['amount']);
$To = (int) db_string($_POST['to']);
$UserID = (int) $app->userNew->core['id'];
$Adjust = isset($_POST['adjust']) ? true : false;
$Message = $_POST['message'];

// 10% tax
$Tax = 0.1;

if ($app->userNew->extra['DisablePoints']) {
    $Err = 'You are not allowed to send '.bonusPoints.'.';
} else {
    if ($Adjust) {
        $Amount = $Amount/(1-$Tax);
    }

    $SentAmount = (int) ($Amount*(1-$Tax));

    $Amount = (int) $Amount;

    if ($UserID == $To) {
        $Err = 'If you sent '.bonusPoints.' to yourself it wouldn\'t even do anything. Stop that.';
    } elseif ($Amount < 0) {
        $Err = 'You can\'t send a negative amount of '.bonusPoints.'.';
    } elseif ($Amount < 100) {
        $Err = 'You must send at least 100 '.bonusPoints.'.';
    } else {
        $app->dbOld->query("
      SELECT ui.DisablePoints
      FROM users_main AS um
        JOIN users_info AS ui ON um.ID = ui.UserID
      WHERE ID = $To");
        if (!$app->dbOld->has_results()) {
            $Err = 'That user doesn\'t exist.';
        } else {
            list($Disabled) = $app->dbOld->next_record();
            if ($Disabled) {
                $Err = "This user is not allowed to receive ".bonusPoints.".";
            } else {
                $app->dbOld->query("
          SELECT BonusPoints
          FROM users_main
          WHERE ID = $UserID");
                if ($app->dbOld->has_results()) {
                    list($BP) = $app->dbOld->next_record();

                    if ($BP < $Amount) {
                        $Err = 'You don\'t have enough '.bonusPoints.'.';
                    } else {
                        $app->dbOld->query("
              UPDATE users_main
              SET BonusPoints = BonusPoints - $Amount
              WHERE ID = $UserID");
                        $app->dbOld->query("
              UPDATE users_main
              SET BonusPoints = BonusPoints + ".$SentAmount."
              WHERE ID = $To");

                        $UserInfo = User::user_info($UserID);
                        $ToInfo = User::user_info($To);

                        $app->dbOld->query("
              UPDATE users_info
              SET AdminComment = CONCAT('".sqltime()." - Sent $Amount ".bonusPoints." (".$SentAmount." after tax) to [user]".$ToInfo['Username']."[/user]\n\n', AdminComment)
              WHERE UserID = $UserID");
                        $app->dbOld->query("
              UPDATE users_info
              SET AdminComment = CONCAT('".sqltime()." - Received ".$SentAmount." ".bonusPoints." from [user]".$UserInfo['Username']."[/user]\n\n', AdminComment)
              WHERE UserID = $To");

                        $PM = '[user]'.$UserInfo['Username'].'[/user] has sent you a gift of '.$SentAmount.' '.bonusPoints.'!';

                        if (!empty($Message)) {
                            $PM .= "\n\n".'[quote='.$UserInfo['Username'].']'.$Message.'[/quote]';
                        }

                        Misc::send_pm($To, 0, 'You\'ve received a gift!', $PM);

                        $app->cacheOld->delete_value('user_info_heavy_'.$UserID);
                        $app->cacheOld->delete_value('user_stats_'.$UserID);
                        $app->cacheOld->delete_value('user_info_heavy_'.$To);
                        $app->cacheOld->delete_value('user_stats_'.$To);
                    }
                } else {
                    $Err = 'An unknown error occurred.';
                }
            }
        }
    }
}

View::header('Send '.bonusPoints); ?>
<div>
  <h2 id='general'>Send <?=bonusPoints?>
  </h2>
  <div class='box pad' style='padding: 10px 10px 10px 20p;'>
    <p><?=$Err ? 'Error: '.$Err : 'Sent '.$Amount.' '.bonusPoints.' ('.$SentAmount.' after tax) to '.$ToInfo['Username'].'.'?>
    </p>
    <p><a href='/user.php?id=<?=$To?>'>Return</a></p>
  </div>
</div>
<?php View::footer();
