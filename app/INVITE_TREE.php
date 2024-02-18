<?php
#declare(strict_types=1);


/**
 * INVITE_TREE
 */
class INVITE_TREE
{
    public $UserID = 0;
    public $Visible = true;


    /**
     * INVITE_TREE
     */
    // Set things up
    public function INVITE_TREE($UserID, $Options = [])
    {
        $this->UserID = $UserID;
        if ($Options['visible'] === false) {
            $this->Visible = false;
        }
    }


    /**
     * make_tree
     */
    public function make_tree()
    {
        $app = \Gazelle\App::go();

        $QueryID = $app->dbOld->get_query_id();
        $UserID = $this->UserID; ?>

<div class="invitetree pad">
    <?php
    $app->dbOld->query("
    SELECT
      `TreePosition`,
      `TreeID`,
      `TreeLevel`
    FROM
      `invite_tree`
    WHERE
      `UserID` = $UserID
    ");

        list($TreePosition, $TreeID, $TreeLevel) = $app->dbOld->next_record(MYSQLI_NUM, false);
        if (!$TreeID) {
            return;
        }

        $app->dbOld->query("
        SELECT
          `TreePosition`
        FROM
          `invite_tree`
        WHERE
          `TreeID` = $TreeID AND `TreeLevel` = $TreeLevel AND `TreePosition` > $TreePosition
        ORDER BY
          `TreePosition` ASC
        LIMIT 1
        ");

        if ($app->dbOld->has_results()) {
            list($MaxPosition) = $app->dbOld->next_record(MYSQLI_NUM, false);
        } else {
            $MaxPosition = false;
        }

        $TreeQuery = $app->dbOld->query("
        SELECT
          it.`UserID`,
          `Enabled`,
          `PermissionID`,
          `Donor`,
          `Uploaded`,
          `Downloaded`,
          `Paranoia`,
          `TreePosition`,
          `TreeLevel`
        FROM
          `invite_tree` AS it
        JOIN `users_main` AS um
        ON
          um.`ID` = it.`UserID`
        JOIN `users_info` AS ui
        ON
          ui.`UserID` = it.`UserID`
        WHERE
          `TreeID` = $TreeID AND `TreePosition` > $TreePosition " .
          ($MaxPosition ? " AND `TreePosition` < $MaxPosition " : '') . "
          AND `TreeLevel` > $TreeLevel
        ORDER BY
          `TreePosition`
        ");
        $PreviousTreeLevel = $TreeLevel;

        // Stats for the summary
        $MaxTreeLevel = $TreeLevel; // The deepest level (this changes)
        $OriginalTreeLevel = $TreeLevel; // The level of the user we're viewing
        $BaseTreeLevel = $TreeLevel + 1; // The level of users invited by our user
        $Count = 0;
        $Branches = 0;
        $DisabledCount = 0;
        $DonorCount = 0;
        $ParanoidCount = 0;
        $TotalUpload = 0;
        $TotalDownload = 0;
        $TopLevelUpload = 0;
        $TopLevelDownload = 0;

        $ClassSummary = [];
        global $Classes;
        foreach ($Classes as $ClassID => $Val) {
            $ClassSummary[$ClassID] = 0;
        }

        // We store this in an output buffer, so we can show the summary at the top without having to loop through twice
        ob_start();
        while (list($ID, $Enabled, $Class, $Donor, $Uploaded, $Downloaded, $Paranoia, $TreePosition, $TreeLevel) = $app->dbOld->next_record(MYSQLI_NUM, false)) {

            // Do stats
            $Count++;

            if ($TreeLevel > $MaxTreeLevel) {
                $MaxTreeLevel = $TreeLevel;
            }

            if ($TreeLevel === $BaseTreeLevel) {
                $Branches++;
                $TopLevelUpload += $Uploaded;
                $TopLevelDownload += $Downloaded;
            }

            $ClassSummary[$Class]++;
            if ($Enabled === 2) {
                $DisabledCount++;
            }

            if ($Donor) {
                $DonorCount++;
            }

            // Manage tree depth
            if ($TreeLevel > $PreviousTreeLevel) {
                for ($i = 0; $i < $TreeLevel - $PreviousTreeLevel; $i++) {
                    echo "\n\n<ul class=\"invitetree\">\n\t<li>\n";
                }
            } elseif ($TreeLevel < $PreviousTreeLevel) {
                for ($i = 0; $i < $PreviousTreeLevel - $TreeLevel; $i++) {
                    echo "\t</li>\n</ul>\n";
                }
                echo "\t</li>\n\t<li>\n";
            } else {
                echo "\t</li>\n\t<li>\n";
            }
            $UserClass = $Classes[$Class]['Level']; ?>

    <strong>
        <?=User::format_username($ID, true, true, ($Enabled !== 2 ? false : true), true)?>
    </strong>

    <?php
      if (check_paranoia(array('uploaded', 'downloaded'), $Paranoia, $UserClass)) {
          $TotalUpload += $Uploaded;
          $TotalDownload += $Downloaded; ?>

    &nbsp;Uploaded: <strong><?=\Gazelle\Format::get_size($Uploaded)?></strong>
    &nbsp;Downloaded: <strong><?=\Gazelle\Format::get_size($Downloaded)?></strong>
    &nbsp;Ratio: <strong><?=\Gazelle\Format::get_ratio_html($Uploaded, $Downloaded)?></strong>
    <?php
      } else {
          $ParanoidCount++; ?>
    &nbsp;Hidden
    <?php
      } ?>

    <?php
      $PreviousTreeLevel = $TreeLevel;
            $app->dbOld->set_query_id($TreeQuery);
        }

        $Tree = ob_get_clean();
        for ($i = 0; $i < $PreviousTreeLevel - $OriginalTreeLevel; $i++) {
            $Tree .= "\t</li>\n</ul>\n";
        }

        if ($Count) {
            ?>
    <p style="font-weight: bold;">
        This tree has <?=\Gazelle\Text::float($Count)?> entries,
        <?=\Gazelle\Text::float($Branches)?> branches,
        and a depth of <?=\Gazelle\Text::float($MaxTreeLevel - $OriginalTreeLevel)?>.
        It has
        <?php
      $ClassStrings = [];
            foreach ($ClassSummary as $ClassID => $ClassCount) {
                if ($ClassCount == 0) {
                    continue;
                }

                $LastClass = $ClassID;
                if ($ClassCount > 1) {
                    if ($LastClass === 'Torrent Celebrity') {
                        $LastClass = 'Torrent Celebrities';
                    } else {
                        // Prevent duplicate letterss
                        if (substr($LastClass, -1) !== 's') {
                            $LastClass .= 's';
                        }
                    }
                }

                $LastClass = "$ClassCount $LastClass (" . \Gazelle\Text::float(($ClassCount / $Count) * 100) . '%)';
                $ClassStrings[] = $LastClass;
            }

            if (count($ClassStrings) > 1) {
                array_pop($ClassStrings);
                echo implode(', ', $ClassStrings);
                echo ' and ' . $LastClass;
            } else {
                echo $LastClass;
            }

            echo '. ';
            echo $DisabledCount;
            echo ($DisabledCount === 1) ? ' user is' : ' users are';
            echo ' disabled (';

            if ($DisabledCount === 0) {
                echo '0%)';
            } else {
                echo \Gazelle\Text::float(($DisabledCount / $Count) * 100) . '%)';
            }

            echo ', and ';
            echo $DonorCount;
            echo ($DonorCount === 1) ? ' user has' : ' users have';
            echo ' donated (';

            if ($DonorCount === 0) {
                echo '0%)';
            } else {
                echo \Gazelle\Text::float(($DonorCount / $Count) * 100) . '%)';
            }
            echo '. </p>';

            echo '<p style="font-weight: bold;">';
            echo 'The total amount uploaded by the entire tree was ' . \Gazelle\Format::get_size($TotalUpload);
            echo '; the total amount downloaded was ' . \Gazelle\Format::get_size($TotalDownload);
            echo '; and the total ratio is ' . \Gazelle\Format::get_ratio_html($TotalUpload, $TotalDownload) . '. ';
            echo '</p>';

            echo '<p style="font-weight: bold;">';
            echo 'The total amount uploaded by direct invitees (the top level) was ' . \Gazelle\Format::get_size($TopLevelUpload);
            echo '; the total amount downloaded was ' . \Gazelle\Format::get_size($TopLevelDownload);
            echo '; and the total ratio is ' . \Gazelle\Format::get_ratio_html($TopLevelUpload, $TopLevelDownload) . '. ';

            echo "These numbers include the stats of paranoid users and will be factored into the invitation giving script.\n\t\t</p>\n";

            if ($ParanoidCount) {
                echo '<p style="font-weight: bold;">';
                echo $ParanoidCount;
                echo ($ParanoidCount === 1) ? ' user (' : ' users (';
                echo \Gazelle\Text::float(($ParanoidCount / $Count) * 100);
                echo '%) ';
                echo ($ParanoidCount === 1) ? ' is' : ' are';
                echo ' too paranoid to have their stats shown here, and ';
                echo ($ParanoidCount === 1) ? ' was' : ' were';
                echo ' not factored into the stats for the total tree.';
                echo '</p>';
            }
        } ?>
        <br>
        <?=$Tree?>
</div>

<?php
    $app->dbOld->set_query_id($QueryID);
    }
} # class
