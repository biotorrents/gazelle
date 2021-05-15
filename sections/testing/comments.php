<?php
#declare(strict_types=1);

if (!check_perms('users_mod')) {
  error(404);
}

View::show_header("Tests");

?>

<div class="header">
  <h2>Documentation</h2>
  <? TestingView::render_linkbox("comments"); ?>
</div>

<div>
  <? TestingView::render_missing_documentation(Testing::get_classes());?>
</div>

<?
View::show_footer();

