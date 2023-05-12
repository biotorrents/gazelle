<?php

$app = \Gazelle\App::go();

if (!check_perms('users_mod')) {
    error(403);
}

View::header('Multiple freeleech');

if (isset($_POST['torrents'])) {
    $GroupIDs = [];
    $Elements = explode("\r\n", $_POST['torrents']);
    foreach ($Elements as $Element) {
        // Get all of the torrent IDs
        if (strpos($Element, "torrents.php") !== false) {
            $Data = explode("id=", $Element);
            if (!empty($Data[1])) {
                $GroupIDs[] = (int) $Data[1];
            }
        } elseif (strpos($Element, "collages.php") !== false) {
            $Data = explode("id=", $Element);
            if (!empty($Data[1])) {
                $CollageID = (int) $Data[1];
                $app->dbOld->query("
                    SELECT GroupID
                    FROM collages_torrents
                    WHERE CollageID = '$CollageID'");
                while (list($GroupID) = $app->dbOld->next_record()) {
                    $GroupIDs[] = (int) $GroupID;
                }
            }
        }
    }

    if (sizeof($GroupIDs) == 0) {
        $Err = 'Please enter properly formatted URLs';
    } else {
        $FreeLeechType = (int) $_POST['freeleechtype'];
        $FreeLeechReason = (int) $_POST['freeleechreason'];

        if (!in_array($FreeLeechType, array(0, 1, 2)) || !in_array($FreeLeechReason, array(0, 1, 2, 3))) {
            $Err = 'Invalid freeleech type or freeleech reason';
        } else {
            // Get the torrent IDs
            $app->dbOld->query("
                SELECT ID
                FROM torrents
                WHERE GroupID IN (".implode(', ', $GroupIDs).")");
            $TorrentIDs = $app->dbOld->collect('ID');

            if (sizeof($TorrentIDs) == 0) {
                $Err = 'Invalid group IDs';
            } else {
                if (isset($_POST['NLOver']) && $FreeLeechType == 1) {
                    // Only use this checkbox if freeleech is selected
                    $Size = (int) $_POST['size'];
                    $Units = db_string($_POST['scale']);

                    if (empty($Size) || !in_array($Units, array('k', 'm', 'g'))) {
                        $Err = 'Invalid size or units';
                    } else {
                        $Bytes = Format::get_bytes($Size . $Units);

                        $app->dbOld->query("
                            SELECT ID
                            FROM torrents
                            WHERE ID IN (".implode(', ', $TorrentIDs).")
                              AND Size > '$Bytes'");
                        $LargeTorrents = $app->dbOld->collect('ID');
                        $TorrentIDs = array_diff($TorrentIDs, $LargeTorrents);
                    }
                }

                if (sizeof($TorrentIDs) > 0) {
                    Torrents::freeleech_torrents($TorrentIDs, $FreeLeechType, $FreeLeechReason);
                }

                if (isset($LargeTorrents) && sizeof($LargeTorrents) > 0) {
                    Torrents::freeleech_torrents($LargeTorrents, 2, $FreeLeechReason);
                }

                $Err = 'Done!';
            }
        }
    }
}
?>
<div>
    <div class="box pad box">
<?php if (isset($Err)) { ?>
        <strong class="important_text"><?=$Err?></strong><br>
<?php } ?>
        Paste a list of collage or torrent group URLs
    </div>
    <div class="box pad">
        <form class="send_form" action="" method="post">
            <input type="hidden" name="auth" value="<?=$app->user->extra['AuthKey']?>">
            <textarea name="torrents" style="width: 95%; height: 200px;"><?=$_POST['torrents']?></textarea><br><br>
            Mark torrents as:&nbsp;
            <select name="freeleechtype">
                <option value="1" <?=$_POST['freeleechtype'] == '1' ? 'selected' : ''?>>FL</option>
                <option value="2" <?=$_POST['freeleechtype'] == '2' ? 'selected' : ''?>>NL</option>
                <option value="0" <?=$_POST['freeleechtype'] == '0' ? 'selected' : ''?>>Normal</option>
            </select>
            &nbsp;for reason&nbsp;<select name="freeleechreason">
<?php $FL = array('N/A', 'Staff Pick', 'Perma-FL', 'Vanity House');
foreach ($FL as $Key => $FLType) { ?>
                            <option value="<?=$Key?>" <?=$_POST['freeleechreason'] == $Key ? 'selected' : ''?>><?=$FLType?></option>
<?php } ?>
            </select><br><br>
            <input type="checkbox" name="NLOver" checked />&nbsp;NL Torrents over <input type="text" name="size" value="<?=isset($_POST['size']) ? $_POST['size'] : '1'?>" size=1>
            <select name="scale">
                <option value="k" <?=$_POST['scale'] == 'k' ? 'selected' : ''?>>KB</option>
                <option value="m" <?=$_POST['scale'] == 'm' ? 'selected' : ''?>>MB</option>
                <option value="g" <?=!isset($_POST['scale']) || $_POST['scale'] == 'g' ? 'selected' : ''?>>GB</option>
            </select><br><br>
            <input type="submit" class="button-primary" value="Submit">
        </form>
    </div>
</div>
<?php
View::footer();
