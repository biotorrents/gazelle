<?php
/*
 * Common header linkbox for Reports v2
 */
?>
<div class="linkbox">
  <a href="reportsv2.php" class="brackets">Views</a>
  <a href="reportsv2.php?action=new" class="brackets">New (auto-assigned)</a>
  <a href="reportsv2.php?view=unauto" class="brackets">New (un-auto)</a>
  <a href="reportsv2.php?view=staff&amp;id=<?=$app->user->core['id']?>" class="brackets">View your claimed reports</a>
  <a href="reportsv2.php?view=resolved" class="brackets">View old reports</a>
  <a href="reportsv2.php?action=search" class="brackets tooltip" title="vaporware">Search reports</a>
</div>
