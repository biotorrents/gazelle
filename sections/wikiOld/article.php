<?php
#declare(strict_types=1);

$ENV = ENV::go();
$twig = Twig::go();

$ArticleID = false;

# Resolve article by ID or alias
if (!empty($_GET['id'])) {
    $ArticleID = (int) $_GET['id'];
} elseif ($_GET['name'] !== '') {
    $ArticleID = Wiki::alias_to_id($_GET['name']);
} else {
    error('Unknown article: '.Text::esc($_GET['id']));
}

Security::int($ArticleID);

if (!$ArticleID) { // No article found
    View::header('No article found'); ?>
<div>
  <div class="header">
    <h2>No article found</h2>
  </div>

  <div class="box">
    There is no article matching the name you requested.

    <ul>
      <li><a
          href="wiki.php?action=search&amp;search=<?=Text::esc($_GET['name'])?>">Search</a>
        for an article similar to this.</li>

      <li><a
          href="wiki.php?action=create&amp;alias=<?=Text::esc(Wiki::normalize_alias($_GET['name']))?>">Create</a>
        an article in its place.</li>
    </ul>
  </div>
</div>
<?php
  View::footer();
    error();
}

$Article = Wiki::get_article($ArticleID);
list($Revision, $Title, $Body, $Read, $Edit, $Date, $AuthorID, $AuthorName, $Aliases, $UserIDs) = array_shift($Article);

if ($Read > $user['EffectiveClass']) {
    error('You must be a higher user class to view this wiki article');
}

$TextBody = Text::parse($Body, false);

View::header($Title, 'wiki');
?>

<div>
  <div class="header">
    <h2>
      <?=$Title?>
    </h2>

    <div class="linkbox">
      <a href="wiki.php?action=create" class="brackets">Create</a>

      <?php if ($Edit <= $user['EffectiveClass']) { ?>
      <a href="wiki.php?action=edit&amp;id=<?=$ArticleID?>"
        class="brackets">Edit</a>
      <a href="wiki.php?action=revisions&amp;id=<?=$ArticleID?>"
        class="brackets">History</a>
      <?php } ?>

      <?php if (check_perms('admin_manage_wiki') && $_GET['id'] !== INDEX_ARTICLE) { ?>
      <a href="wiki.php?action=delete&amp;id=<?=$ArticleID?>&amp;authkey=<?=$user['AuthKey']?>"
        class="brackets" onclick="return confirm('Are you sure you want to delete?')">Delete</a>
      <?php } ?>
    </div>
  </div>

  <div class="sidebar one-third column">
    <div class="box">
      <div class="head">Search</div>
      <div class="pad">

        <form class="search_form" name="articles" action="wiki.php" method="get">
          <input type="hidden" name="action" value="search" />
          <input type="search" placeholder="Search articles" name="search" size="20" />
          <input value="Search" type="submit" class="hidden button-primary" />
        </form>

        <br style="line-height: 10px;" />
        <a href="wiki.php?action=browse" class="brackets">Browse articles</a>
      </div>
    </div>

    <div class="box box_info pad">
      <ul>
        <li>
          <strong>Protection:</strong>
          <ul>
            <li>Read: <?=$ClassLevels[$Read]['Name']?>
            </li>
            <li>Edit: <?=$ClassLevels[$Edit]['Name']?>
            </li>
          </ul>
        </li>

        <li>
          <strong>Details:</strong>
          <ul>
            <li>Version: r<?=$Revision?>
            </li>
            <li>Last edited by: <?=Users::format_username($AuthorID, false, false, false)?>
            </li>
            <li>Last updated: <?=time_diff($Date)?>
            </li>
          </ul>
        </li>

        <li>
          <strong>Aliases:</strong>
          <ul>
            <?php
if ($Aliases !== $Title) {
    $AliasArray = explode(',', $Aliases);
    $UserArray = explode(',', $UserIDs);
    $i = 0;

    foreach ($AliasArray as $AliasItem) {
        ?>
            <li id="alias_<?=$AliasItem?>"><a
                href="wiki.php?action=article&amp;name=<?=$AliasItem?>"><?=Format::cut_string($AliasItem, 20, 1)?></a>

              <?php
              if (check_perms('admin_manage_wiki')) { ?>
              <a href="#"
                onclick="Remove_Alias('<?=$AliasItem?>'); return false;"
                class="brackets tooltip" title="Delete alias">X</a> <a
                href="user.php?id=<?=$UserArray[$i]?>"
                class="brackets tooltip" title="View user">U</a>
              <?php
            } ?>
            </li>
            <?php
            $i++;
    }
}
?>
          </ul>
        </li>
      </ul>
    </div>

    <?php if ($Edit <= $user['EffectiveClass']) { ?>
    <div class="box box_addalias">
      <div style="padding: 5px;">

        <form class="add_form" name="aliases" action="wiki.php" method="post">
          <input type="hidden" name="action" value="add_alias" />
          <input type="hidden" name="auth"
            value="<?=$user['AuthKey']?>" />
          <input type="hidden" name="article"
            value="<?=$ArticleID?>" />
          <input onfocus="if (this.value == 'Add alias') this.value='';"
            onblur="if (this.value == '') this.value='Add alias';" value="Add alias" type="text" name="alias"
            size="20" />
          <input type="submit" value="+" />
        </form>

      </div>
    </div>
    <?php } ?>
  </div>

  <div class="main_column two-thirds column">
    <div class="box wiki_article">
      <div class="pad"><?=$TextBody?>
      </div>
    </div>
  </div>
</div>
<?php View::footer();
