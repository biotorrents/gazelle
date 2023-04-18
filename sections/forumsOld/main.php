<?php
$LastRead = Forums::get_last_read($Forums);
View::header('Forums');
?>

<div class="header">
  <h2>Forums</h2>
</div>

<div class="forum_list">
  <?php
$LastCategoryID = 0;
$OpenTable = false;

foreach ($Forums as $Forum) {
    list($ForumID, $CategoryID, $ForumName, $ForumDescription, $MinRead, $MinWrite, $MinCreate, $NumTopics, $NumPosts, $LastPostID, $LastAuthorID, $LastTopicID, $LastTime, $SpecificRules, $LastTopic, $Locked, $Sticky) = array_values($Forum);

    if (!Forums::check_forumperm($ForumID)) {
        #continue;
    }

    $ForumDescription = \Gazelle\Text::esc($ForumDescription);

    if ($CategoryID != $LastCategoryID) {
        $LastCategoryID = $CategoryID;

        if ($OpenTable) { ?>
  </table>
  <?php } ?>

  <h3>
    <?=$ForumCats[$CategoryID]?>
  </h3>

  <table class="forum_index alternate_rows">
    <tr class="colhead">
      <td style="width: 2%;"></td>
      <td style="max-width: 25%;">Forum</td>
      <td>Last Post</td>
      <td style="width: 7%;">Topics</td>
      <td style="width: 7%;">Posts</td>
    </tr>
    <?php
    $OpenTable = true;
    }

    $Read = Forums::is_unread($Locked, $Sticky, $LastPostID, $LastRead, $LastTopicID, $LastTime) ? 'unread' : 'read'; ?>
    <tr class="row">
      <td class="<?=$Read?> tooltip"
        title="<?=ucfirst($Read)?>">
      </td>

      <td>
        <h4>
          <a class="tooltip"
            href="forums.php?action=viewforum&amp;forumid=<?=$ForumID?>"
            title="<?=\Gazelle\Text::esc($ForumDescription)?>"><?=\Gazelle\Text::esc($ForumName)?></a>
        </h4>
      </td>

      <?php if ($NumPosts === 0) { ?>
      <td>
        There are no topics here.
        <?= (($MinCreate <= $app->user->extra['Class'])
            ? ' <a href="forums.php?action=new&amp;forumid='.$ForumID.'">Create one!</a>'
            : '') ?>
      </td>

      <td class="number_column">0</td>
      <td class="number_column">0</td>
      <?php } else { ?>
      <td>
        <span class="u-pull-left last_topic">
          <a href="forums.php?action=viewthread&amp;threadid=<?=$LastTopicID?>"
            class="tooltip"
            data-title-plain="<?=\Gazelle\Text::esc($LastTopic)?>"><?=\Gazelle\Text::esc(\Gazelle\Text::limit($LastTopic, 50))?></a>
        </span>

        <?php if (!empty($LastRead[$LastTopicID])) { ?>
        <a class="tooltip last_read" aria-label="Jump to last read" title="Jump to last read"
          href="forums.php?action=viewthread&amp;threadid=<?=$LastTopicID?>&amp;page=<?=$LastRead[$LastTopicID]['Page']?>#post<?=$LastRead[$LastTopicID]['PostID']?>">
          &rarr;
        </a>
        <?php } ?>

        <span class="u-pull-right last_poster">
          by <?=User::format_username($LastAuthorID, false, false, false)?>
          <?=time_diff($LastTime, 1)?>
        </span>
      </td>

      <td class="number_column">
        <?=\Gazelle\Text::float($NumTopics)?>
      </td>

      <td class="number_column">
        <?=\Gazelle\Text::float($NumPosts)?>
      </td>
      <?php } ?>
    </tr>
    <?php
} ?>
  </table>
</div>

<div class="linkbox">
  <a href="forums.php?action=catchup&amp;forumid=all&amp;auth=<?=$app->user->extra['AuthKey']?>"
    class="brackets">Catch up</a>
</div>
<?php View::footer();
