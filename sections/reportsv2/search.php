<?php
#declare(strict_types=1);

/*
 * todo: I'm not writing documentation for this page until I write this page >.>
 */

if (!check_perms('admin_reports')) {
    error(403);
}

View::header('Reports V2!', 'reportsv2');
?>

<div class="header">
  <h2>Search</h2>
  <?php include('header.php'); ?>
</div>
<div class="box pad">
  On hold until someone fixes the main torrents search.
</div>
<?php
View::footer();
