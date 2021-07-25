<?php
declare(strict_types=1);

$ENV = ENV::go();
View::show_header('Better');
?>

<h2 class="header">
  Pursuit of Perfection
</h2>

<div class="box pad">
  <p>
    Here at <?= $ENV->SITE_NAME ?>, we believe that there's always
    room for improvement.
    To aid our effort in the pursuit of perfection, we've put together a few simple lists that can help you build
    ratio or something and help us improve our overall quality.
    Most lists feature 20 random torrents selected upon page load.
  </p>
</div>

<h3 id="lists">
  Lists
</h3>

<div class="box pad">
  <table class="better_list">
    <tr class="colhead">
      <td style="width: 150px;">Method</td>
      <td style="width: 400px;">Additional Information</td>
    </tr>

    <tr class="row">
      <td class="nobr">
        <a href="better.php?method=single">Single seeder</a>
      </td>

      <td class="nobr">
        When a torrent only has one seeder
      </td>
    </tr>

    <tr class="row">
      <td class="nobr">
        <a href="better.php?method=literature&filter=all">DOI numbers</a>
      </td>

      <td class="nobr">
        Torrent groups without citations, for enhanced metadata support
      </td>
    </tr>

    <tr class="row">
      <td class="nobr">
        <a href="better.php?method=covers&filter=all">Pictures</a>
      </td>
      <td class="nobr">
        Torrent groups without pictures, for at-a-glance context
      </td>
    </tr>

    <tr class="row">
      <td class="nobr">
        <a href="better.php?method=tags&amp;filter=all">Tags</a>
      </td>

      <td class="nobr">
        Torrents marked as having bad or no tags
      </td>
    </tr>

    <tr class="row">
      <td class="nobr">
        <a href="better.php?method=folders&filter=all">Folder names</a>
      </td>

      <td class="nobr">
        Torrents marked as having bad or no folder names
      </td>
    </tr>
  </table>
</div>
<?php View::show_footer();
