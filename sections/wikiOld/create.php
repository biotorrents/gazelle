<?php
declare(strict_types=1);

View::header(
    'Create an article',
    'vendor/easymde.min',
    'vendor/easymde.min'
);
?>

<div>
  <div class="box pad">

    <form name="wiki_article" action="wiki.php" method="post">
      <input type="hidden" name="action" value="create" />
      <input type="hidden" name="auth"
        value="<?=$app->user->extra['AuthKey']?>" />

      <div>
        <h3>Title</h3>
        <input type="text" name="title" size="92" maxlength="100" />

        <h3>Body</h3>
        <?php
$ReplyText = View::textarea(
    id: 'body',
);

  if (check_perms('admin_manage_wiki')) { ?>
        <h3>Access</h3>
        <p>There are some situations in which the viewing or editing of an article should be restricted to a certain
          class.</p>
        <strong>Restrict read:</strong> <select name="minclassread"><?=class_list()?></select>
        <strong>Restrict edit:</strong> <select name="minclassedit"><?=class_list()?></select>
        <?php } ?>

        <div style="text-align: center;">
          <input type="button" value="Preview"
            class="hidden button_preview_<?=$ReplyText->getID()?>"
            tabindex="1" />
          <input type="submit" class="button-primary" value="Submit" />
        </div>
      </div>
    </form>
  </div>
</div>
<?php
View::footer();
