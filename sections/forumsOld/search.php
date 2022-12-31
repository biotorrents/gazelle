<?php
#declare(strict_types=1);

// todo: Clean up this fucking mess

/*
Forums search result page
*/

list($Page, $Limit) = Format::page_limit(POSTS_PER_PAGE);

if (isset($_GET['type']) && $_GET['type'] === 'body') {
    $Type = 'body';
} else {
    $Type = 'title';
}

// What are we looking for? Let's make sure it isn't dangerous.
if (isset($_GET['search'])) {
    $Search = trim($_GET['search']);
} else {
    $Search = '';
}

$ThreadAfterDate = db_string($_GET['thread_created_after']);
$ThreadBeforeDate = db_string($_GET['thread_created_before']);

if ((!empty($ThreadAfterDate)) || (!empty($ThreadBeforeDate))) {
    error("Incorrect topic created date");
}

$PostAfterDate = db_string($_GET['post_created_after']);
$PostBeforeDate = db_string($_GET['post_created_before']);

if ((!empty($PostAfterDate)) || (!empty($PostBeforeDate))) {
    error("Incorrect post created date");
}

// Searching for posts by a specific user
if (!empty($_GET['user'])) {
    $User = trim($_GET['user']);
    $db->query("
    SELECT ID
    FROM users_main
    WHERE Username = '".db_string($User)."'");
    list($AuthorID) = $db->next_record();
    if ($AuthorID === null) {
        $AuthorID = 0;
        // This will cause the search to return 0 results
        // Workaround in line 276 to display that the username was wrong
    }
} else {
    $User = '';
}

// Are we looking in individual forums?
if (isset($_GET['forums']) && is_array($_GET['forums'])) {
    $ForumArray = [];
    foreach ($_GET['forums'] as $Forum) {
        if (is_number($Forum)) {
            $ForumArray[] = $Forum;
        }
    }
    if (count($ForumArray) > 0) {
        $SearchForums = implode(', ', $ForumArray);
    }
}

// Searching for posts in a specific thread
if (!empty($_GET['threadid']) && is_number($_GET['threadid'])) {
    $ThreadID = $_GET['threadid'];
    $Type = 'body';
    $SQL = "
    SELECT
      Title
    FROM forums_topics AS t
      JOIN forums AS f ON f.ID = t.ForumID
    WHERE t.ID = $ThreadID
      AND " . Forums::user_forums_sql();
    $db->query($SQL);
    if (list($Title) = $db->next_record()) {
        $Title = " &gt; <a href=\"forums.php?action=viewthread&amp;threadid=$ThreadID\">$Title</a>";
    } else {
        error(404);
    }
} else {
    $ThreadID = '';
}

// Let's hope we got some results - start printing out the content
View::header('Forums &gt; Search', 'forum_search');
?>

<div class="header">
  <h2>
    <a href="forums.php">Forums</a>
    &rsaquo;
    Search<?=$Title?>
  </h2>
</div>

<div class="box">
  <form class="search_form" name="forums" action="" method="get">
    <input type="hidden" name="action" value="search" />
    <table cellpadding="6" cellspacing="1" border="0" class="layout border" width="100%">
      <tr>
        <td><strong>Search Terms</strong></td>
        <td>
          <input type="search" name="search" size="70"
            value="<?=Text::esc($Search)?>" />
        </td>
      </tr>
      <tr>
        <td><strong>Posted By</strong></td>
        <td>
          <input type="search" name="user" placeholder="Username" size="70"
            value="<?=Text::esc($User)?>" />
        </td>
      </tr>
      <tr>
        <td><strong>Topic Created</strong></td>
        <td>
          After
          <input type="text" name="thread_created_after" id="thread_created_after" placeholder="YYYY-MM-DD"
            pattern="[1-2][0-9]{3}-[0-9]{2}-[0-9]{2}"
            value="<?=$ThreadAfterDate?>" />&nbsp;&nbsp;
          Before
          <input type="text" name="thread_created_before" id="thread_created_before" placeholder="YYYY-MM-DD"
            pattern="[1-2][0-9]{3}-[0-9]{2}-[0-9]{2}"
            value="<?=$ThreadBeforeDate?>" />
        </td>
      </tr>
      <?php
if (empty($ThreadID)) {
    ?>
      <tr>
        <td><strong>Search In</strong></td>
        <td>
          <input type="radio" name="type" id="type_title" value="title" <?php if ($Type == 'title') {
        echo ' checked="checked"';
    } ?> />
          <label for="type_title">Titles</label>&nbsp;&nbsp;
          <input type="radio" name="type" id="type_body" value="body" <?php if ($Type == 'body') {
        echo ' checked="checked"';
    } ?> />
          <label for="type_body">Body</label>
        </td>
      </tr>
      <tr id="post_created_row" <?php if ($Type == 'title') {
        echo "class='hidden'";
    } ?>>
        <td><strong>Post created:</strong></td>
        <td>
          After:
          <input type="text" class="date_picker" name="post_created_after" id="post_created_after"
            value="<?=$PostAfterDate?>" />
          Before:
          <input type="text" class="date_picker" name="post_created_before" id="post_created_before"
            value="<?=$PostBeforeDate?>" />
        </td>
      </tr>
      <tr>
        <td><strong>Forums</strong></td>
        <td>
          <table id="forum_search_cat_list" class="cat_list layout">


            <?php
  // List of forums
  $Open = false;
    $LastCategoryID = -1;
    $Columns = 0;
    $i = 0;
    foreach ($Forums as $Forum) {
        if (!Forums::check_forumperm($Forum['ID'])) {
            continue;
        }

        $Columns++;

        if ($Forum['CategoryID'] != $LastCategoryID) {
            $LastCategoryID = $Forum['CategoryID'];
            if ($Open) {
                if ($Columns % 5) { ?>
            <td colspan="<?=(5 - ($Columns % 5))?>"></td>
            <?php
        } ?>
      </tr>
      <?php
            }
            $Columns = 0;
            $Open = true;
            $i++; ?>
      <tr>
        <td colspan="5" class="forum_cat">
          <strong><?=$ForumCats[$Forum['CategoryID']]?></strong>
          <a href="#" class="brackets forum_category"
            id="forum_category_<?=$i?>">Check all</a>
        </td>
      </tr>
      <tr>
        <?php
        } elseif ($Columns % 5 == 0) { ?>
      </tr>
      <tr>
        <?php } ?>
        <td>
          <input type="checkbox" name="forums[]"
            value="<?=$Forum['ID']?>"
            data-category="forum_category_<?=$i?>"
            id="forum_<?=$Forum['ID']?>" <?php if (isset($_GET['forums']) && in_array($Forum['ID'], $_GET['forums'])) {
            echo ' checked="checked"';
        } ?> />
          <label
            for="forum_<?=$Forum['ID']?>"><?=htmlspecialchars($Forum['Name'])?></label>
        </td>
        <?php
    }
    if ($Columns % 5) { ?>
        <td colspan="<?=(5 - ($Columns % 5))?>"></td>
        <?php } ?>
      </tr>
    </table>
    <?php
} else { ?>
    <input type="hidden" name="threadid" value="<?=$ThreadID?>" />
    <?php } ?>
    </td>
    </tr>
    <tr>
      <td colspan="2" class="center">
        <input type="submit" class="button-primary" value="Search" />
      </td>
    </tr>
    </table>
  </form>
  <div class="linkbox">
    <?php

// Break search string down into individual words
$Words = explode(' ', db_string($Search));

if ($Type == 'body') {
    $SQL = "
    SELECT
      SQL_CALC_FOUND_ROWS
      t.ID,
      ".(!empty($ThreadID) ? "SUBSTRING_INDEX(p.Body, ' ', 40)" : 't.Title').",
      t.ForumID,
      f.Name,
      p.AddedTime,
      p.ID,
      p.Body,
      t.CreatedTime
    FROM forums_posts AS p
      JOIN forums_topics AS t ON t.ID = p.TopicID
      JOIN forums AS f ON f.ID = t.ForumID
    WHERE " . Forums::user_forums_sql() . ' AND ';

    // In tests, this is significantly faster than LOCATE
    $SQL .= "p.Body LIKE '%";
    $SQL .= implode("%' AND p.Body LIKE '%", $Words);
    $SQL .= "%' ";

    //$SQL .= "LOCATE('";
    //$SQL .= implode("', p.Body) AND LOCATE('", $Words);
    //$SQL .= "', p.Body) ";

    if (isset($SearchForums)) {
        $SQL .= " AND f.ID IN ($SearchForums)";
    }
    if (isset($AuthorID)) {
        $SQL .= " AND p.AuthorID = '$AuthorID' ";
    }
    if (!empty($ThreadID)) {
        $SQL .= " AND t.ID = '$ThreadID' ";
    }
    if (!empty($ThreadAfterDate)) {
        $SQL .= " AND t.CreatedTime >= '$ThreadAfterDate'";
    }
    if (!empty($ThreadBeforeDate)) {
        $SQL .= " AND t.CreatedTime <= '$ThreadBeforeDate'";
    }
    if (!empty($PostAfterDate)) {
        $SQL .= " AND p.AddedTime >= '$PostAfterDate'";
    }
    if (!empty($PostBeforeDate)) {
        $SQL .= " AND p.AddedTime <= '$PostBeforeDate'";
    }

    $SQL .= "
    ORDER BY p.AddedTime DESC
    LIMIT $Limit";
} else {
    $SQL = "
    SELECT
      SQL_CALC_FOUND_ROWS
      t.ID,
      t.Title,
      t.ForumID,
      f.Name,
      t.LastPostTime,
      '',
      '',
      t.CreatedTime
    FROM forums_topics AS t
      JOIN forums AS f ON f.ID = t.ForumID
    WHERE " . Forums::user_forums_sql() . ' AND ';
    $SQL .= "t.Title LIKE '%";
    $SQL .= implode("%' AND t.Title LIKE '%", $Words);
    $SQL .= "%' ";
    if (isset($SearchForums)) {
        $SQL .= " AND f.ID IN ($SearchForums)";
    }
    if (isset($AuthorID)) {
        $SQL .= " AND t.AuthorID = '$AuthorID' ";
    }
    if (!empty($ThreadAfterDate)) {
        $SQL .= " AND t.CreatedTime >= '$ThreadAfterDate'";
    }
    if (!empty($ThreadBeforeDate)) {
        $SQL .= " AND t.CreatedTime <= '$ThreadBeforeDate'";
    }
    $SQL .= "
    ORDER BY t.LastPostTime DESC
    LIMIT $Limit";
}

// Perform the query
$Records = $db->query($SQL);
$db->query('SELECT FOUND_ROWS()');
list($Results) = $db->next_record();
$db->set_query_id($Records);

$Pages = Format::get_pages($Page, $Results, POSTS_PER_PAGE, 9);
echo $Pages;
?>
  </div>
  <table cellpadding="6" cellspacing="1" border="0" class="forum_list border" width="100%">
    <tr class="colhead">
      <td>Forum</td>
      <td><?=((!empty($ThreadID)) ? 'Post begins' : 'Topic')?>
      </td>
      <td>Topic creation time</td>
      <td>Last post time</td>
    </tr>
    <?php if (!$db->has_results()) { ?>
    <tr>
      <td colspan="4">Nothing found<?=((isset($AuthorID) && $AuthorID == 0) ? ' (unknown username)' : '')?>!
      </td>
    </tr>
    <?php }

while (list($ID, $Title, $ForumID, $ForumName, $LastTime, $PostID, $Body, $ThreadCreatedTime) = $db->next_record()) {
    // Print results?>
    <tr class="row">
      <td>
        <a
          href="forums.php?action=viewforum&amp;forumid=<?=$ForumID?>"><?=$ForumName?></a>
      </td>
      <td>
        <?php if (empty($ThreadID)) { ?>
        <a href="forums.php?action=viewthread&amp;threadid=<?=$ID?>"><?=Format::cut_string($Title, 80); ?></a>
        <?php } else { ?>
        <?=Format::cut_string($Title, 80); ?>
        <?php
  }
    if ($Type == 'body') { ?>
        <a data-toggle-target="#post_<?=$PostID?>_text">(Show)</a>
        <span class="u-pull-right tooltip last_read" title="Jump to post"><a href="forums.php?action=viewthread&amp;threadid=<?=$ID?><?php if (!empty($PostID)) {
        echo "&amp;postid=$PostID#post$PostID";
    } ?>"></a></span>
        <?php } ?>
      </td>
      <td>
        <?=time_diff($ThreadCreatedTime)?>
      </td>
      <td>
        <?=time_diff($LastTime)?>
      </td>
    </tr>
    <?php if ($Type == 'body') { ?>
    <tr class="row hidden" id="post_<?=$PostID?>_text">
      <td colspan="4"><?=Text::parse($Body)?>
      </td>
    </tr>
    <?php }
}
?>
  </table>

  <div class="linkbox">
    <?= $Pages ?>
  </div>
</div>
<?php View::footer();
