<?php
#declare(strict_types = 1);

$app = \Gazelle\App::go();

include serverRoot.'/sections/reports/array.php';

if (empty($_GET['type']) || empty($_GET['id']) || !is_numeric($_GET['id'])) {
    error(404);
}

if (!array_key_exists($_GET['type'], $Types)) {
    error(403);
}

$Short = $_GET['type'];
$Type = $Types[$Short];
$ID = $_GET['id'];

switch ($Short) {
    case 'user':
        $app->dbOld->query("
      SELECT Username
      FROM users_main
      WHERE ID = $ID");
        if (!$app->dbOld->has_results()) {
            error(404);
        }
        list($Username) = $app->dbOld->next_record();
        break;

    case 'request_update':
        $NoReason = true;
        $app->dbOld->query("
      SELECT Title, Description, TorrentID, CategoryID, Year
      FROM requests
      WHERE ID = $ID");
        if (!$app->dbOld->has_results()) {
            error(404);
        }
        list($Name, $Desc, $Filled, $CategoryID, $Year) = $app->dbOld->next_record();
        if ($Filled || ($CategoryID != 0 && ($Categories[$CategoryID - 1] != 'Music' || $Year != 0))) {
            error(403);
        }
        break;

    case 'request':
        $app->dbOld->query("
      SELECT Title, Description, TorrentID
      FROM requests
      WHERE ID = $ID");
        if (!$app->dbOld->has_results()) {
            error(404);
        }
        list($Name, $Desc, $Filled) = $app->dbOld->next_record();
        break;

    case 'collage':
        $app->dbOld->query("
      SELECT Name, Description
      FROM collages
      WHERE ID = $ID");
        if (!$app->dbOld->has_results()) {
            error(404);
        }
        list($Name, $Desc) = $app->dbOld->next_record();
        break;

    case 'thread':
        $app->dbOld->query("
      SELECT ft.Title, ft.ForumID, um.Username
      FROM forums_topics AS ft
        JOIN users_main AS um ON um.ID = ft.AuthorID
      WHERE ft.ID = $ID");
        if (!$app->dbOld->has_results()) {
            error(404);
        }
        list($Title, $ForumID, $Username) = $app->dbOld->next_record();
        $app->dbOld->query("
      SELECT MinClassRead
      FROM forums
      WHERE ID = $ForumID");
        list($MinClassRead) = $app->dbOld->next_record();
        if (!empty($app->user->extra['DisableForums'])
            || ($MinClassRead > $app->user->extra['EffectiveClass'] && (!isset($app->user->extra['CustomForums'][$ForumID]) || $app->user->extra['CustomForums'][$ForumID] == 0))
            || (isset($app->user->extra['CustomForums'][$ForumID]) && $app->user->extra['CustomForums'][$ForumID] == 0)) {
            error(403);
        }
        break;

    case 'post':
        $app->dbOld->query("
      SELECT fp.Body, fp.TopicID, um.Username
      FROM forums_posts AS fp
        JOIN users_main AS um ON um.ID = fp.AuthorID
      WHERE fp.ID = $ID");
        if (!$app->dbOld->has_results()) {
            error(404);
        }
        list($Body, $TopicID, $Username) = $app->dbOld->next_record();
        $app->dbOld->query("
      SELECT ForumID
      FROM forums_topics
      WHERE ID = $TopicID");
        list($ForumID) = $app->dbOld->next_record();
        $app->dbOld->query("
      SELECT MinClassRead
      FROM forums
      WHERE ID = $ForumID");
        list($MinClassRead) = $app->dbOld->next_record();
        if (!empty($app->user->extra['DisableForums'])
            || ($MinClassRead > $app->user->extra['EffectiveClass'] && (!isset($app->user->extra['CustomForums'][$ForumID]) || $app->user->extra['CustomForums'][$ForumID] == 0))
            || (isset($app->user->extra['CustomForums'][$ForumID]) && $app->user->extra['CustomForums'][$ForumID] == 0)) {
            error(403);
        }
        break;

    case 'comment':
        $app->dbOld->query("
      SELECT c.Body, um.Username
      FROM comments AS c
        JOIN users_main AS um ON um.ID = c.AuthorID
      WHERE c.ID = $ID");
        if (!$app->dbOld->has_results()) {
            error(404);
        }
        list($Body, $Username) = $app->dbOld->next_record();
        break;
}

View::header('Report a '.$Type['title']);
?>
<div class="thin">
  <div class="header">
    <h2>Report <?=$Type['title']?>
    </h2>
  </div>
  <h3>Reporting guidelines</h3>
  <div class="box pad">
    <p>Following these guidelines will help the moderators deal with your report in a timely fashion. </p>
    <ul>
      <?php  foreach ($Type['guidelines'] as $Guideline) { ?>
      <li><?=$Guideline?>
      </li>
      <?php  } ?>
    </ul>
    <p>In short, please include as much detail as possible when reporting. Thank you. </p>
  </div>
  <?php

switch ($Short) {
    case 'user':
        ?>
  <p>You are reporting the user <strong><?=\Gazelle\Text::esc($Username)?></strong></p>
  <?php
            break;
    case 'request_update':
        ?>
  <p>You are reporting the request:</p>
  <table class="box">
    <tr class="colhead">
      <td>Title</td>
      <td>Description</td>
      <td>Filled?</td>
    </tr>
    <tr>
      <td><?=\Gazelle\Text::esc($Name)?>
      </td>
      <td><?=\Gazelle\Text::parse($Desc)?>
      </td>
      <td><strong><?=($Filled == 0 ? 'No' : 'Yes')?></strong>
      </td>
    </tr>
  </table>
  <br />

  <div class="box pad center">
    <p><strong>It will greatly increase the turnover rate of the updates if you can fill in as much of the following
        details as possible.</strong></p>
    <form id="report_form" name="report" action="" method="post">
      <input type="hidden" name="action" value="takereport">
      <input type="hidden" name="auth"
        value="<?=$app->user->extra['AuthKey']?>">
      <input type="hidden" name="id" value="<?=$ID?>">
      <input type="hidden" name="type" value="<?=$Short?>">
      <table class="layout">
        <tr>
          <td class="label">Year (required)</td>
          <td>
            <input type="text" size="4" name="year" class="required">
          </td>
        </tr>
        <tr>
          <td class="label">Release type</td>
          <td>
            <select id="releasetype" name="releasetype">
              <option value="0">---</option>
              <?php    foreach ($ReleaseTypes as $Key => $Val) { ?>
              <option value="<?=$Key?>" <?=(!empty($ReleaseType) ? ($Key == $ReleaseType ? ' selected="selected"' : '') : '')?>><?=$Val?>
              </option>
              <?php    } ?>
            </select>
          </td>
        </tr>
        <tr>
          <td class="label">Comment</td>
          <td>
            <textarea rows="8" cols="80" name="comment" class="required"></textarea>
          </td>
        </tr>
      </table>
      <br />
      <br />
      <input type="submit" value="Submit report">
    </form>
  </div>
  <?php
            break;
    case 'request':
        ?>
  <p>You are reporting the request:</p>
  <table class="box">
    <tr class="colhead">
      <td>Title</td>
      <td>Description</td>
      <td>Filled?</td>
    </tr>
    <tr>
      <td><?=\Gazelle\Text::esc($Name)?>
      </td>
      <td><?=\Gazelle\Text::parse($Desc)?>
      </td>
      <td><strong><?=($Filled == 0 ? 'No' : 'Yes')?></strong>
      </td>
    </tr>
  </table>
  <?php
            break;
    case 'collage':
        ?>
  <p>You are reporting the collection:</p>
  <table class="box">
    <tr class="colhead">
      <td>Title</td>
      <td>Description</td>
    </tr>
    <tr>
      <td><?=\Gazelle\Text::esc($Name)?>
      </td>
      <td><?=\Gazelle\Text::parse($Desc)?>
      </td>
    </tr>
  </table>
  <?php
            break;
    case 'thread':
        ?>
  <p>You are reporting the thread:</p>
  <table>
    <tr class="colhead">
      <td>Username</td>
      <td>Title</td>
    </tr>
    <tr>
      <td><?=\Gazelle\Text::esc($Username)?>
      </td>
      <td><?=\Gazelle\Text::esc($Title)?>
      </td>
    </tr>
  </table>
  <?php
            break;
    case 'post':
        ?>
  <p>You are reporting the post:</p>
  <table>
    <tr class="colhead">
      <td>Username</td>
      <td>Body</td>
    </tr>
    <tr>
      <td><?=\Gazelle\Text::esc($Username)?>
      </td>
      <td><?=\Gazelle\Text::parse($Body)?>
      </td>
    </tr>
  </table>
  <?php
            break;
    case 'comment':
        ?>
  <p>You are reporting the <?=$Types[$Short]['title']?>:</p>
  <table class="box">
    <tr class="colhead">
      <td>Username</td>
      <td>Body</td>
    </tr>
    <tr>
      <td><?=\Gazelle\Text::esc($Username)?>
      </td>
      <td><?=\Gazelle\Text::parse($Body)?>
      </td>
    </tr>
  </table>
  <?php
          break;
}
if (empty($NoReason)) {
    ?>
  <h3>Reason</h3>
  <div class="box pad center">
    <form name="report" id="report_form" action="" method="post">
      <input type="hidden" name="action" value="takereport">
      <input type="hidden" name="auth"
        value="<?=$app->user->extra['AuthKey']?>">
      <input type="hidden" name="id" value="<?=$ID?>">
      <input type="hidden" name="type" value="<?=$Short?>">
      <textarea class="required" rows="10" cols="95" name="reason"></textarea><br /><br />
      <input type="submit" value="Submit report">
    </form>
  </div>
  <?php
}
// close <div class="thin">?>
</div>
<?php
View::footer();
