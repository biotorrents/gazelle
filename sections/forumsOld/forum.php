<?php
#declare(strict_types=1);

$app = \Gazelle\App::go();

/**********|| Page to show individual forums || ********************************\

Things to expect in $_GET:
  ForumID: ID of the forum curently being browsed
  page: The page the user's on.
  page = 1 is the same as no page

********************************************************************************/

//---------- Things to sort out before it can start printing/generating content

// Check for lame SQL injection attempts
$ForumID = $_GET['forumid'];
if (!is_numeric($ForumID)) {
    error(0);
}

$Tooltip = "tooltip";

if (isset($app->userNew->extra['PostsPerPage'])) {
    $PerPage = $app->userNew->extra['PostsPerPage'];
} else {
    $PerPage = POSTS_PER_PAGE;
}

list($Page, $Limit) = Format::page_limit(TOPICS_PER_PAGE);

//---------- Get some data to start processing

// Caching anything beyond the first page of any given forum is just wasting RAM.
// Users are more likely to search than to browse to page 2.
if ($Page === 1) {
    list($Forum, , , $Stickies) = $app->cacheNew->get("forums_$ForumID");
}

if (!isset($Forum) || !is_array($Forum)) {
    $app->dbOld->query("
    SELECT
      ID,
      Title,
      AuthorID,
      IsLocked,
      IsSticky,
      NumPosts,
      LastPostID,
      LastPostTime,
      LastPostAuthorID
    FROM forums_topics
    WHERE ForumID = '$ForumID'
    ORDER BY IsSticky DESC, Ranking ASC, LastPostTime DESC
    LIMIT $Limit"); // Can be cached until someone makes a new post
    $Forum = $app->dbOld->to_array('ID', MYSQLI_ASSOC, false);

    if ($Page === 1) {
        $app->dbOld->query("
      SELECT COUNT(ID)
      FROM forums_topics
      WHERE ForumID = '$ForumID'
        AND IsSticky = '1'");
        list($Stickies) = $app->dbOld->next_record();
        $app->cacheNew->set("forums_$ForumID", array($Forum, '', 0, $Stickies), 0);
    }
}

if (!isset($Forums[$ForumID])) {
    error(404);
}

// Make sure they're allowed to look at the page
if (!check_perms('site_moderate_forums')) {
    if (isset($app->userNew->extra['CustomForums'][$ForumID]) && $app->userNew->extra['CustomForums'][$ForumID] === 0) {
        error(403);
    }
}

$ForumName = Text::esc($Forums[$ForumID]['Name']);
if (!Forums::check_forumperm($ForumID)) {
    #error(403);
}

// Start printing
$ENV = ENV::go();
View::header("Forums $ENV->crumb ".$Forums[$ForumID]['Name']);
?>

<div class="header">
  <h2>
    <a href="forums.php">Forums</a>
    <?=$ENV->crumb?>
    <?=$ForumName?>
  </h2>

  <div class="linkbox">
    <?php if (Forums::check_forumperm($ForumID, 'Write') && Forums::check_forumperm($ForumID, 'Create')) { ?>
    <a href="forums.php?action=new&amp;forumid=<?=$ForumID?>"
      class="brackets">New thread</a>
    <?php } ?>

    <a data-toggle-target="#searchforum" data-toggle-replace="Hide search" class="brackets">Search this forum</a>

    <div id="searchforum" class="hidden center">
      <div style="display: inline-block;">
        <h3>
          Search this forum
        </h3>

        <form class="search_form" name="forum" action="forums.php" method="get">
          <table cellpadding="6" cellspacing="1" border="0" class="layout border">
            <tr>
              <td>
                <input type="hidden" name="action" value="search" />
                <input type="hidden" name="forums[]"
                  value="<?=$ForumID?>" />
              </td>

              <td>
                <input type="search" id="searchbox" name="search" size="60" placeholder="Search terms" />
              </td>
            </tr>

            <tr>
              <td>
                <strong>Search In</strong>
              </td>

              <td>
                <input type="radio" name="type" id="type_title" value="title" checked="checked" />
                <label for="type_title">Title</label>&ensp;

                <input type="radio" name="type" id="type_body" value="body" />
                <label for="type_body">Body</label>
              </td>
            </tr>

            <tr>
              <td></td>

              <td>
                <input type="search" id="username" name="user" placeholder="Posted By" size="60" />
              </td>
            </tr>

            <tr>
              <td colspan="2" style="text-align: center;">
                <input type="submit" name="submit" class="button-primary" value="Search" />
              </td>
            </tr>
          </table>
        </form>
      </div>
    </div>
  </div>

  <?php if (check_perms('site_moderate_forums')) { ?>
  <div class="linkbox">
    <a href="forums.php?action=edit_rules&amp;forumid=<?=$ForumID?>"
      class="brackets">Change specific rules</a>
  </div>
  <?php } ?>

  <?php if (!empty($Forums[$ForumID]['SpecificRules'])) { ?>
  <div class="linkbox">
    <strong>Forum Specific Rules</strong>
    <?php foreach ($Forums[$ForumID]['SpecificRules'] as $ThreadIDs) {
    $Thread = Forums::get_thread_info($ThreadIDs);
    if ($Thread === null) {
        error(404);
    } ?>
    <br />

    <a href="forums.php?action=viewthread&amp;threadid=<?=$ThreadIDs?>"
      class="brackets"><?=Text::esc($Thread['Title'])?></a>
    <?php
} ?>
  </div>
  <?php } ?>

  <div class="linkbox pager">
    <?php
$Pages = Format::get_pages($Page, $Forums[$ForumID]['NumTopics'], TOPICS_PER_PAGE, 9);
echo $Pages;
?>
  </div>
</div>

<table class="forum_index skeletonFix">
  <tr class="colhead">
    <td style="width: 2%;"></td>
    <td>Latest</td>
    <td style="width: 7%;">Replies</td>
    <td style="width: 14%;">Author</td>
  </tr>
  <?php
// Check that we have content to process
if (count($Forum) === 0) {
    ?>
  <tr>
    <td colspan="4">
      No threads to display in this forum!
    </td>
  </tr>
  <?php
} else {
        // forums_last_read_topics is a record of the last post a user read in a topic, and what page that was on
        $app->dbOld->query("
    SELECT
      l.TopicID,
      l.PostID,
      CEIL((
          SELECT COUNT(p.ID)
          FROM forums_posts AS p
          WHERE p.TopicID = l.TopicID
            AND p.ID <= l.PostID
        ) / $PerPage
      ) AS Page
    FROM forums_last_read_topics AS l
    WHERE l.TopicID IN (".implode(', ', array_keys($Forum)).')
      AND l.UserID = \''.$app->userNew->core['id'].'\'');

        // Turns the result set into a multi-dimensional array, with
        // forums_last_read_topics.TopicID as the key.
        // This is done here so we get the benefit of the caching, and we
        // don't have to make a database query for each topic on the page
        $LastRead = $app->dbOld->to_array('TopicID');

        //---------- Begin printing

        foreach ($Forum as $Topic) {
            list($TopicID, $Title, $AuthorID, $Locked, $Sticky, $PostCount, $LastID, $LastTime, $LastAuthorID) = array_values($Topic);
            // Build list of page links
            // Only do this if there is more than one page
            $PageLinks = [];
            $ShownEllipses = false;
            $PagesText = '';
            $TopicPages = ceil($PostCount / $PerPage);

            if ($TopicPages > 1) {
                $PagesText = ' (';
                for ($i = 1; $i <= $TopicPages; $i++) {
                    if ($TopicPages > 4 && ($i > 2 && $i <= $TopicPages - 2)) {
                        if (!$ShownEllipses) {
                            $PageLinks[] = '-';
                            $ShownEllipses = true;
                        }
                        continue;
                    }
                    $PageLinks[] = "<a href=\"forums.php?action=viewthread&amp;threadid=$TopicID&amp;page=$i\">$i</a>";
                }
                $PagesText .= implode(' ', $PageLinks);
                $PagesText .= ')';
            }

            // handle read/unread posts - the reason we can't cache the whole page
            if ((!$Locked || $Sticky) && ((empty($LastRead[$TopicID]) || $LastRead[$TopicID]['PostID'] < $LastID) && strtotime($LastTime) > $app->userNew->extra['CatchupTime'])) {
                $Read = 'unread';
            } else {
                $Read = 'read';
            }
            if ($Locked) {
                $Read .= '_locked';
            }
            if ($Sticky) {
                $Read .= '_sticky';
            } ?>
  <tr class="row">
    <td
      class="<?=$Read?> <?=$Tooltip?>"
      title="<?=ucwords(str_replace('_', ' ', $Read))?>">
    </td>
    <td>
      <span class="u-pull-left last_topic">
        <?php
    $TopicLength = 75 - (2 * count($PageLinks));
            unset($PageLinks);
            $Title = Text::esc($Title);
            $DisplayTitle = $Title; ?>
        <strong>
          <a href="forums.php?action=viewthread&amp;threadid=<?=$TopicID?>"
            class="tooltip" data-title-plain="<?=$Title?>"><?=Format::cut_string($DisplayTitle, $TopicLength) ?></a>
        </strong>
        <?=$PagesText?>
      </span>
      <?php if (!empty($LastRead[$TopicID])) { ?>
      <a class="<?=$Tooltip?> last_read" title="Jump to last read"
        href="forums.php?action=viewthread&amp;threadid=<?=$TopicID?>&amp;page=<?=$LastRead[$TopicID]['Page']?>#post<?=$LastRead[$TopicID]['PostID']?>">
        &rarr;
        <!--
          <svg width="15" height="11">
            <polygon points="0,3 0,8 8,8 8,11 15,5.5 8,0 8,3" /></svg>
          -->
      </a>
      <?php } ?>
      <span class="u-pull-right last_poster">
        by <?=User::format_username($LastAuthorID, false, false, false, false, false)?>
        <?=time_diff($LastTime, 1)?>
      </span>
    </td>
    <td class="number_column"><?=Text::float($PostCount - 1)?>
    </td>
    <td><?=User::format_username($AuthorID, false, false, false, false, false)?>
    </td>
  </tr>
  <?php
        }
    } ?>
</table>

<div class="breadcrumbs">
  <p>
    <a href="forums.php">Forums</a> <?=$ENV->crumb?> <?=$ForumName?>
  </p>
</div>

<div class="linkbox pager">
  <?=$Pages?>
</div>

<div class="linkbox"><a
    href="forums.php?action=catchup&amp;forumid=<?=$ForumID?>&amp;auth=<?=$app->userNew->extra['AuthKey']?>"
    class="brackets">Catch up</a></div>
</div>

<?php View::footer();
