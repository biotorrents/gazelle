<?php
declare(strict_types=1);

/**
 * Flight router
 * @see https://flightphp.com/learn
 */

# endpoints go here

# start the router
#Flight::start();


/** LEGACY ROUTES */


enforce_login();
$ENV = ENV::go();

View::header('Blog');

if (check_perms('admin_manage_blog')) {
    if (!empty($_REQUEST['action'])) {
        switch ($_REQUEST['action']) {
      case 'deadthread':
        if (is_number($_GET['id'])) {
            $db->prepared_query("
            UPDATE blog
            SET ThreadID = NULL
            WHERE ID = ".$_GET['id']);
            $cache->delete_value('blog');
            $cache->delete_value('feed_blog');
        }
        header('Location: blog.php');
        break;

      case 'takeeditblog':
        authorize();
        if (is_number($_POST['blogid']) && is_number($_POST['thread'])) {
            $db->prepared_query("
            UPDATE blog
            SET
              Title = '".db_string($_POST['title'])."',
              Body = '".db_string($_POST['body'])."',
              ThreadID = ".$_POST['thread']."
            WHERE ID = '".db_string($_POST['blogid'])."'");
            $cache->delete_value('blog');
            $cache->delete_value('feed_blog');
        }
        header('Location: blog.php');
        break;

      case 'editblog':
        if (is_number($_GET['id'])) {
            $BlogID = $_GET['id'];
            $db->prepared_query("
            SELECT Title, Body, ThreadID
            FROM blog
            WHERE ID = $BlogID");
            list($Title, $Body, $ThreadID) = $db->next_record();
        }
        break;

      case 'deleteblog':
        if (is_number($_GET['id'])) {
            authorize();
            $db->prepared_query("
            DELETE FROM blog
            WHERE ID = '".db_string($_GET['id'])."'");
            $cache->delete_value('blog');
            $cache->delete_value('feed_blog');
        }
        header('Location: blog.php');
        break;

      case 'takenewblog':
        authorize();
        $Title = db_string($_POST['title']);
        $Body = db_string($_POST['body']);
        $ThreadID = $_POST['thread'];
        if ($ThreadID && is_number($ThreadID)) {
            $db->prepared_query("
            SELECT ForumID
            FROM forums_topics
            WHERE ID = $ThreadID");
            if (!$db->has_results()) {
                error('No such thread exists!');
                header('Location: blog.php');
            }
        } else {
            $ThreadID = Misc::create_thread($ENV->ANNOUNCEMENT_FORUM, $user['ID'], $Title, $Body);
            if ($ThreadID < 1) {
                error(0);
            }
        }

        $db->prepared_query("
          INSERT INTO blog
            (UserID, Title, Body, Time, ThreadID, Important)
          VALUES
            ('".$user['ID']."',
            '".db_string($_POST['title'])."',
            '".db_string($_POST['body'])."',
            NOW(),
            $ThreadID,
            '".((isset($_POST['important']) && $_POST['important'] == '1') ? '1' : '0')."')");
        $cache->delete_value('blog');
        if ($_POST['important'] == '1') {
            $cache->delete_value('blog_latest_id');
        }
        if (isset($_POST['subscribe'])) {
            $db->prepared_query("
            INSERT IGNORE INTO users_subscriptions
            VALUES ('$user[ID]', $ThreadID)");
            $cache->delete_value('subscriptions_user_'.$user['ID']);
        }

        header('Location: blog.php');
        break;
    }
    } ?>
<div class="box">
    <div class="head">
        <?=empty($_GET['action']) ? 'Create a blog post' : 'Edit blog post'?>
    </div>
    <form
        class="<?=empty($_GET['action']) ? 'create_form' : 'edit_form'?>"
        name="blog_post" action="blog.php" method="post">
        <div class="pad">
            <input type="hidden" name="action"
                value="<?=empty($_GET['action']) ? 'takenewblog' : 'takeeditblog'?>" />
            <input type="hidden" name="auth"
                value="<?=$user['AuthKey']?>" />
            <?php if (!empty($_GET['action']) && $_GET['action'] == 'editblog') { ?>
            <input type="hidden" name="blogid"
                value="<?=$BlogID; ?>" />
            <?php } ?>
            <h3>Title</h3>
            <input type="text" name="title" size="95" <?=!empty($Title) ? ' value="'.esc($Title).'"' : ''; ?>
            /><br />
            <h3>Body</h3>
            <textarea name="body" cols="95"
                rows="15"><?=!empty($Body) ? esc($Body) : ''; ?></textarea>
            <br />
            <input type="checkbox" value="1" name="important" id="important" checked="checked" /><label
                for="important">Important</label><br />
            <h3>Thread ID</h3>
            <input type="text" name="thread" size="8" <?=!empty($ThreadID) ? ' value="'.esc($ThreadID).'"' : ''; ?>
            />
            (Leave blank to create thread automatically)
            <br /><br />
            <input id="subscribebox" type="checkbox" name="subscribe" <?=!empty($HeavyInfo['AutoSubscribe']) ? ' checked="checked"' : ''; ?>
            tabindex="2" />
            <label for="subscribebox">Subscribe</label>

            <div class="center">
                <input type="submit" class="button-primary"
                    value="<?=!isset($_GET['action']) ? 'Create blog post' : 'Edit blog post'; ?>" />
            </div>
        </div>
    </form>
</div>
<br />
<?php
}
?>
<div>
    <?php
if (!$Blog = $cache->get_value('blog')) {
    $db->prepared_query("
    SELECT
      b.ID,
      um.Username,
      b.UserID,
      b.Title,
      b.Body,
      b.Time,
      b.ThreadID
    FROM blog AS b
      LEFT JOIN users_main AS um ON b.UserID = um.ID
    ORDER BY Time DESC
    LIMIT 20");
    $Blog = $db->to_array();
    $cache->cache_value('blog', $Blog, 1209600);
}

if ($user['LastReadBlog'] < $Blog[0][0]) {
    $cache->begin_transaction('user_info_heavy_'.$user['ID']);
    $cache->update_row(false, array('LastReadBlog' => $Blog[0][0]));
    $cache->commit_transaction(0);
    $db->prepared_query("
    UPDATE users_info
    SET LastReadBlog = '".$Blog[0][0]."'
    WHERE UserID = ".$user['ID']);
    $user['LastReadBlog'] = $Blog[0][0];
}

foreach ($Blog as $BlogItem) {
    list($BlogID, $Author, $AuthorID, $Title, $Body, $BlogTime, $ThreadID) = $BlogItem; ?>
    <div id="blog<?=$BlogID?>" class="box blog_post">
        <div class="head">
            <strong><?=$Title?></strong> - posted <?=time_diff($BlogTime); ?> by <a
                href="user.php?id=<?=$AuthorID?>"><?=$Author?></a>
            <?php if (check_perms('admin_manage_blog')) { ?>
            - <a href="blog.php?action=editblog&amp;id=<?=$BlogID?>"
                class="brackets">Edit</a>
            <a href="blog.php?action=deleteblog&amp;id=<?=$BlogID?>&amp;auth=<?=$user['AuthKey']?>"
                class="brackets">Delete</a>
            <?php } ?>
        </div>
        <div class="pad">
            <?=Text::parse($Body)?>
            <?php if ($ThreadID) { ?>
            <br /><br />
            <em><a
                    href="forums.php?action=viewthread&amp;threadid=<?=$ThreadID?>">Discuss
                    this post here</a></em>
            <?php if (check_perms('admin_manage_blog')) { ?>
            <a href="blog.php?action=deadthread&amp;id=<?=$BlogID?>&amp;auth=<?=$user['AuthKey']?>"
                class="brackets">Remove link</a>
            <?php
    }
  } ?>
        </div>
    </div>
    <br />
    <?php
}
?>
</div>
<?php
View::footer();
