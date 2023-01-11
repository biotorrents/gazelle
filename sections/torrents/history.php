<?php
#declare(strict_types = 1);

$app = App::go();

if (!isset($_GET['groupid']) || !is_number($_GET['groupid'])) {
    error(0);
}
$GroupID = (int)$_GET['groupid'];

$app->dbOld->query("
  SELECT `title`
  FROM `torrents_group`
  WHERE `id` = $GroupID");
if (!$app->dbOld->has_results()) {
    error(404);
}
list($Name) = $app->dbOld->next_record();

View::header("Revision history for $Name");
?>
<div>
  <div class="header">
    <h2>Revision history for <a href="torrents.php?id=<?=$GroupID?>"><?=$Name?></a></h2>
  </div>
<?php
RevisionHistoryView::render_revision_history(RevisionHistory::get_revision_history('torrents', $GroupID), "torrents.php?id=$GroupID");
?>
</div>
<?php
View::footer();
