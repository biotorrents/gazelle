<?

class Top10View {

  public static function render_linkbox($Selected) {
?>
    <div class="linkbox">
      <a href="top10.php?type=torrents" class="brackets"><?=self::get_selected_link("Torrents", $Selected == "torrents")?></a>
      <a href="top10.php?type=users" class="brackets"><?=self::get_selected_link("Users", $Selected == "users")?></a>
      <a href="top10.php?type=tags" class="brackets"><?=self::get_selected_link("Tags", $Selected == "tags")?></a>
      <a href="top10.php?type=votes" class="brackets"><?=self::get_selected_link("Favorites", $Selected == "votes")?></a>
      <a href="top10.php?type=donors" class="brackets"><?=self::get_selected_link("Donors", $Selected == "donors")?></a>
    </div>
<?
  }

  private static function get_selected_link($String, $Selected) {
    if ($Selected) {
      return "<strong>$String</strong>";
    } else {
      return $String;
    }
  }

  public static function render_artist_tile($Artist, $Category) {
    if (self::is_valid_artist($Artist)) {
      switch ($Category) {
        case 'weekly':
        case 'hyped':
          self::render_tile("artist.php?artistname=", $Artist['name'], $Artist['image'][3]['#text']);
          break;
        default:
          break;
      }
    }
  }

  private static function render_tile($Url, $Name, $Image) {
    if (!empty($Image)) {
      $Name = display_str($Name);
?>
      <li>
        <a href="<?=$Url?><?=$Name?>">
          <img class="tooltip large_tile" alt="<?=$Name?>" title="<?=$Name?>" src="<?=ImageTools::process($Image)?>" />
        </a>
      </li>
<?
    }
  }


  public static function render_artist_list($Artist, $Category) {
    if (self::is_valid_artist($Artist)) {
      switch ($Category) {

        case 'weekly':
        case 'hyped':
          self::render_list("artist.php?artistname=", $Artist['name'], $Artist['image'][3]['#text']);
          break;
        default:
          break;
      }
    }
  }

  private static function render_list($Url, $Name, $Image) {
    if (!empty($Image)) {
      $Image = ImageTools::process($Image);
      $Title = "title=\"&lt;img class=&quot;large_tile&quot; src=&quot;$Image&quot; alt=&quot;&quot; /&gt;\"";
      $Name = display_str($Name);
?>
      <li>
        <a class="tooltip_image" data-title-plain="<?=$Name?>" <?=$Title?> href="<?=$Url?><?=$Name?>"><?=$Name?></a>
      </li>
<?
    }
  }

  private static function is_valid_artist($Artist) {
    return $Artist['name'] != '[unknown]';
  }

}
