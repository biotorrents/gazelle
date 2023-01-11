<?php
declare(strict_types=1);

$app = App::go();

$query = "
    select sql_calc_found_rows torrents_group.id
    from torrents_group where torrents_group.picture = ''
    order by rand() limit 20
";

$ref = $app->dbNew->multi($query) ?? [];

$groups = [];
foreach ($ref as $row) {
    $groups[] = Torrents::get_groups($row["id"]);
}

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
    if (!isset($app->userNew->extra['CoverArt']) || $app->userNew->extra['CoverArt']) {
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
