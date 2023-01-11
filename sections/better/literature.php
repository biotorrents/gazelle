<?php
declare(strict_types=1);

$app = App::go();

$query = "
    select sql_calc_found_rows torrents_group.id
    from torrents_group.id
    where torrents_group.id not in
    (select distinct group_id from literature)
    order by rand() limit 20
";

$ref = $app->dbNew->multi($query);
#!d($ref);exit;

$groups = [];
foreach ($ref as $row) {
    $groups[] = Torrents::get_groups($row["id"]);
}

exit;

/** continue */

View::header('Torrent groups with no publications');

$Groups = $app->dbOld->to_array('id', MYSQLI_ASSOC);
$app->dbOld->prepared_query('SELECT FOUND_ROWS()');
list($NumResults) = $app->dbOld->next_record();
$Results = Torrents::get_groups(array_keys($Groups)); ?>

<div class="header">
  <?php if ($All) { ?>
  <h2>
    All groups with no DOI numbers
  </h2>

  <?php } else { ?>
  <h2>
    Torrent groups with no DOI numbers that you have snatched
  </h2>
  <?php } ?>

  <div class="linkbox">
    <a href="better.php" class="brackets">Back to better.php list</a>
    <?php if ($All) { ?>
    <a href="better.php?method=literature" class="brackets">Show only those you have snatched</a>
    <?php } else { ?>
    <a href="better.php?method=literature&amp;filter=all" class="brackets">Show all</a>
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
    $LangName = $title ? $title : ($subject ? $subject : $object);
    $TorrentTags = new Tags($tag_list);

    $DisplayName = "<a href='torrents.php?id=$id' ";
    if (!isset($user['CoverArt']) || $user['CoverArt']) {
        $DisplayName .= 'data-cover="'.ImageTools::process($picture, 'thumb').'" ';
    }
    $DisplayName .= ">$LangName</a>";

    if ($year > 0) {
        $DisplayName .= " [$year]";
    } ?>

    <tr class="torrent">
      <td>
        <div class="<?=Format::css_category($category_id)?>"></div>
      </td>

      <td>
        <?=$DisplayName?>
        <div class="tags"><?=$TorrentTags->format()?>
        </div>
      </td>
    </tr>
    <?php
} ?>
  </table>
</div>
<?php View::footer();
