<?php

declare(strict_types=1);


/**
 * pictures
 */

$app = \Gazelle\App::go();

$get = Http::query("get");
$snatchedOnly = $get["snatches"] ?? null;

# snatched vs. all
$allTorrents = true;
if ($snatchedOnly) {
    $allTorrents = false;
    $subQuery = "
        join torrents on torrents.groupId = torrents_group.id
        join xbt_snatched on xbt_snatched.fid = torrents.id
        and xbt_snatched.uid = {$app->user->core["id"]}
    ";
} else {
    $subQuery = "";
}

$query = "
    select sql_calc_found_rows torrents_group.id from torrents_group
    {$subQuery}
    where torrents_group.picture = ''
    order by rand() limit 20
";

$ref = $app->dbNew->multi($query) ?? [];
$groupIds = array_column($ref, "id");
$torrentGroups = Torrents::get_groups($groupIds);
#!d($torrentGroups);exit;

# twig template
$app->twig->display("better/list.twig", [
  "title" => "Better",
  "header" => "Torrent groups with no picture",
  "sidebar" => true,
  "torrentGroups" => $torrentGroups,
]);

exit;


/** continue */


$Groups = $app->dbOld->to_array('id', MYSQLI_ASSOC);
$app->dbOld->prepared_query('SELECT FOUND_ROWS()');
list($NumResults) = $app->dbOld->next_record();
$Results = Torrents::get_groups(array_keys($Groups));

View::header('Torrent groups with no picture');
?>

<div class="header">
  <?php if ($All) { ?>
  <h2>
    All torrent groups with no picture
  </h2>
  <?php } else { ?>
  <h2>
    Torrent groups with no picture that you have snatched
  </h2>
  <?php } ?>

  <div class="linkbox">
    <a href="better.php" class="brackets">Back to better.php list</a>
    <?php if ($All) { ?>
    <a href="better.php?method=covers" class="brackets">Show only those you have snatched</a>
    <?php } else { ?>
    <a href="better.php?method=covers&amp;filter=all" class="brackets">Show all</a>
    <?php } ?>
  </div>
</div>

<div class="box pad">
  <h3>
    There are <?=Text::float($NumResults)?> groups remaining
  </h3>

  <table class="torrent_table">
    <?php
foreach ($Results as $Result) {
    extract($Result);
    $TorrentTags = new Tags($tag_list);

    $DisplayName = "<a href='torrents.php?id=$id' ";
    if (!isset($app->user->extra['CoverArt']) || $app->user->extra['CoverArt']) {
        $DisplayName .= 'data-cover="'.ImageTools::process($picture, 'thumb').'" ';
    }

    $DisplayName .= ">$title</a>";
    if ($published) {
        $DisplayName .= " [$published]";
    } ?>

    <tr class="torrent">
      <td>
        <div class="<?=Format::css_category($category_id)?>"></div>
      </td>

      <td>
        <?=$DisplayName?>
        <div class="tags">
          <?=$TorrentTags->format()?>
        </div>
      </td>
    </tr>
    <?php
} ?>
  </table>
</div>

<?php View::footer();
