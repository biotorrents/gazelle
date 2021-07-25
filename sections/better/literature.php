<?php
declare(strict_types=1);

$All = (!empty($_GET['filter']) && $_GET['filter'] === 'all');
$Join = $All
    ? ''
    : ("
        JOIN `torrents` AS t ON t.`GroupID` = tg.`id`
        JOIN `xbt_snatched` AS x ON x.`fid` = t.`ID`
        AND x.`uid` = '$LoggedUser[ID]'
    ");

View::show_header('Torrent groups with no publications');

$DB->query("
SELECT SQL_CALC_FOUND_ROWS
  tg.`id`
FROM
  `torrents_group` AS tg
$Join
WHERE
  tg.`id` NOT IN(
  SELECT DISTINCT
    `group_id`
  FROM
    `literature`
  )
ORDER BY
  RAND()
LIMIT 20
");

$Groups = $DB->to_array('id', MYSQLI_ASSOC);
$DB->query('SELECT FOUND_ROWS()');
list($NumResults) = $DB->next_record();
$Results = Torrents::get_groups(array_keys($Groups)); ?>

<div class="header">
  <?php if ($All) { ?>
  <h2>
    All groups with no publications
  </h2>

  <?php } else { ?>
  <h2>
    Torrent groups with no publications that you have snatched
  </h2>
  <?php } ?>

  <div class="linkbox">
    <a href="better.php" class="brackets">Back to better.php list</a>
    <?php if ($All) { ?>
    <a href="better.php?method=screenshots" class="brackets">Show only those you have snatched</a>
    <?php } else { ?>
    <a href="better.php?method=screenshots&amp;filter=all" class="brackets">Show all</a>
    <?php } ?>
  </div>
</div>

<div class="box pad">
  <h3>
    There are <?=number_format($NumResults)?> groups remaining
  </h3>

  <table class="torrent_table">
    <?php
foreach ($Results as $Result) {
    extract($Result);
    $LangName = $title ? $title : ($subject ? $subject : $object);
    $TorrentTags = new Tags($tag_list);

    $DisplayName = "<a href='torrents.php?id=$id' ";
    if (!isset($LoggedUser['CoverArt']) || $LoggedUser['CoverArt']) {
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
<?php View::show_footer();
