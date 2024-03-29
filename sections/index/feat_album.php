<?php

$app = \Gazelle\App::go();

$FeaturedAlbum = $app->cache->get('featured_album');
if ($FeaturedAlbum === false) {
    $app->dbOld->query('
    SELECT
      fa.GroupID,
      tg.Name,
      tg.WikiImage,
      fa.ThreadID,
      fa.Title
    FROM featured_albums AS fa
      JOIN torrents_group AS tg ON tg.ID = fa.GroupID
    WHERE Ended = 0');
    $FeaturedAlbum = $app->dbOld->next_record();
    $app->cache->set('featured_album', $FeaturedAlbum, 0);
}
if (is_numeric($FeaturedAlbum['GroupID'])) {
    $Artists = Artists::get_artist($FeaturedAlbum['GroupID']); ?>
    <div class="box">
      <div class="head colhead_dark"><strong>Featured Album</strong></div>
      <div class="center pad">
        <?=Artists::display_artists($Artists, true, true)?><a href="torrents.php?id=<?=$FeaturedAlbum['GroupID']?>"><?=$FeaturedAlbum['Name']?></a>
      </div>
      <div class="center pad">
        <a href="torrents.php?id=<?=$FeaturedAlbum['GroupID']?>" class="tooltip" title="<?=Artists::display_artists($Artists, false, false)?> - <?=$FeaturedAlbum['Name']?>">
          <img src="<?=\Gazelle\Images::process($FeaturedAlbum['WikiImage'])?>" alt="<?=Artists::display_artists($Artists, false, false)?> - <?=$FeaturedAlbum['Name']?>" width="100%">
        </a>
      </div>
      <div class="center pad">
        <a href="forums.php?action=viewthread&amp;threadid=<?=$FeaturedAlbum['ThreadID']?>"><em>Read the interview with the artist, discuss here</em></a>
      </div>
    </div>
<?php
}
?>
