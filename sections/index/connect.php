<?php
declare(strict_types = 1);

$ID = G::$LoggedUser['ID'];
$RssAuth = G::$LoggedUser['RSS_Auth'];
$PassKey = G::$LoggedUser['torrent_pass'];
$AuthKey = G::$LoggedUser['AuthKey'];

echo $HTML = <<<HTML
<div class="box">
  <div class="head colhead_dark">
    <strong>Connect</strong>
  </div>

  <ul class="nobullet">
    <li>
      <a href="feeds.php?feed=feed_news&amp;user=$ID&amp;auth=$RssAuth&amp;passkey=$PassKey&amp;authkey=$AuthKey" target="_blank">
        <i class="fas fa-rss" aria-hidden="true"></i>
        News
      </a>
    </li>

    <li>
      <a href="feeds.php?feed=feed_blog&amp;user=$ID&amp;auth=$RssAuth&amp;passkey=$PassKey&amp;authkey=$AuthKey" target="_blank">
        <i class="fas fa-rss" aria-hidden="true"></i>
        Blog
      </a>
    </li>

    <li>
      <a href="https://github.com/biotorrents" target="_blank">
        <i class="fab fa-github" aria-hidden="true"></i>
        GitHub
      </a>
    </li>

    <li>
      <a href="https://twitter.com/biotorrents" target="_blank">
        <i class="fab fa-twitter" aria-hidden="true"></i>
        Twitter
      </a>
    </li>
  </ul>
</div>
HTML;
?>

