<?php
#declare(strict_types=1);

// Diff function by Leto of StC
function diff($OldText, $NewText)
{
    $LineArrayOld = explode("\n", $OldText);
    $LineArrayNew = explode("\n", $NewText);
    $LineOffset = 0;
    $Result = [];

    foreach ($LineArrayOld as $OldLine => $OldString) {
        $Key = $OldLine + $LineOffset;
        if ($Key < 0) {
            $Key = 0;
        }
        $Found = -1;

        while ($Key < count($LineArrayNew)) {
            if ($OldString !== $LineArrayNew[$Key]) {
                $Key++;
            } elseif ($OldString === $LineArrayNew[$Key]) {
                $Found = $Key;
                break;
            }
        }

        if ($Found === '-1') { // We never found the old line in the new array
            $Result[] = '<span class="line_deleted">&larr; '.$OldString.'</span><br />';
            $LineOffset = $LineOffset - 1;
        } elseif ($Found === $OldLine + $LineOffset) {
            $Result[] = '<span class="line_unchanged">&#8597; '.$OldString.'</span><br />';
        } elseif ($Found !== $OldLine + $LineOffset) {
            if ($Found < $OldLine + $LineOffset) {
                $Result[] = '<span class="line_moved">&#8676; '.$OldString.'</span><br />';
            } else {
                $Result[] = '<span class="line_moved">&larr; '.$OldString.'</span><br />';
                $Key = $OldLine + $LineOffset;
                while ($Key < $Found) {
                    $Result[] = '<span class="line_new">&rarr; '.$LineArrayNew[$Key].'</span><br />';
                    $Key++;
                }
                $Result[] = '<span class="line_moved">&rarr; '.$OldString.'</span><br />';
            }
            $LineOffset = $Found - $OldLine;
        }
    }

    if (count($LineArrayNew) > count($LineArrayOld) + $LineOffset) {
        $Key = count($LineArrayOld) + $LineOffset;
        while ($Key < count($LineArrayNew)) {
            $Result[] = '<span class="line_new">&rarr; '.$LineArrayNew[$Key].'</span><br />';
            $Key++;
        }
    }
    return $Result;
}

function get_body($ID, $Rev)
{
    # $Rev is a str, $Revision an int
    global $db, $Revision, $Body;
    
    if ((int) $Rev === $Revision) {
        $Str = $Body;
    } else {
        $db->prepared_query("
          SELECT Body
          FROM wiki_revisions
          WHERE ID = '$ID'
            AND Revision = '$Rev'");

        if (!$db->has_results()) {
            error(404);
        }
        list($Str) = $db->next_record();
    }
    return $Str;
}

if (!isset($_GET['old'])
  || !isset($_GET['new'])
  || !isset($_GET['id'])
  || !is_number($_GET['old'])
  || !is_number($_GET['new'])
  || !is_number($_GET['id'])
) {
    error(400);
}

if ($_GET['old'] > $_GET['new']) {
    error('The new revision compared must be newer than the old revision to compare against.');
}

$ArticleID = (int) $_GET['id'];
$Article = Wiki::get_article($ArticleID);
list($Revision, $Title, $Body, $Read, $Edit, $Date, $AuthorID, $AuthorName) = array_shift($Article);

if ($Edit > $user['EffectiveClass']) {
    error(404);
}

View::header('Compare Article Revisions');
$Diff2 = get_body($ArticleID, $_GET['new']);
$Diff1 = get_body($ArticleID, $_GET['old']);
?>

<div>
    <div class="header">
        <h2>Compare <a
                href="wiki.php?action=article&amp;id=<?=$ArticleID?>"><?=$Title?></a> Revisions</h2>
    </div>

    <div class="box center_revision" id="center">
        <div class="body">
            <?php
      foreach (diff($Diff1, $Diff2) as $Line) {
          echo $Line;
      } ?>
        </div>
    </div>
</div>
<?php
View::footer();
