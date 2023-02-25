<?php

declare(strict_types=1);


/**
 * Top10
 *
 * Generates stats for top torrents, tags, users, donors, etc.
 */

class Top10
{
    /**
     * render_linkbox
     */
    public static function render_linkbox($Selected)
    {
        $ENV = ENV::go(); ?>
<div class="linkbox">
  <a href="/top10" class="brackets"><?=self::get_selected_link("Torrents", $Selected === "torrents")?></a>
  <a href="/top10/users" class="brackets"><?=self::get_selected_link("Users", $Selected === "users")?></a>
  <a href="/top10/tags" class="brackets"><?=self::get_selected_link("Tags", $Selected === "tags")?></a>
  <?php if ($ENV->enableDonations) { ?>
  <a href="/top10/donors" class="brackets"><?=self::get_selected_link("Donors", $Selected === "donors")?></a>
  <?php } ?>
</div>
<?php
    }


    /**
     * get_selected_link
     */
    private static function get_selected_link($String, $Selected)
    {
        if ($Selected) {
            return "<strong>$String</strong>";
        } else {
            return $String;
        }
    }


    /**
     * render_artist_tile
     */
    public static function render_artist_tile($Artist, $Category)
    {
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


    /**
     * render_tile
     */
    private static function render_tile($Url, $Name, $Image)
    {
        if (!empty($Image)) {
            $Name = Text::esc($Name); ?>
<li>
  <a
    href="<?=$Url?><?=$Name?>">
    <img class="tooltip large_tile" alt="<?=$Name?>"
      title="<?=$Name?>"
      src="<?=ImageTools::process($Image)?>" />
  </a>
</li>
<?php
        }
    }


    /**
     * render_artist_list
     */
    public static function render_artist_list($Artist, $Category)
    {
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


    /**
     * render_list
     */
    private static function render_list($Url, $Name, $Image)
    {
        if (!empty($Image)) {
            $Image = ImageTools::process($Image);
            $Title = "title=\"&lt;img class=&quot;large_tile&quot; src=&quot;$Image&quot; alt=&quot;&quot; /&gt;\"";
            $Name = Text::esc($Name); ?>

<li>
  <a class="tooltip_image" data-title-plain="<?=$Name?>" <?=$Title?> href="<?=$Url?><?=$Name?>"><?=$Name?></a>
</li>
<?php
        }
    }


    /**
     * is_valid_artist
     */
    private static function is_valid_artist($Artist)
    {
        return $Artist['name'] !== '[unknown]';
    }
}
