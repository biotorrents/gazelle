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
    public static function render_linkbox($selected)
    {
        $ENV = ENV::go(); ?>
<div class="linkbox">
  <a href="/top10" class="brackets"><?=self::get_selected_link("Torrents", $selected === "torrents")?></a>
  <a href="/top10/users" class="brackets"><?=self::get_selected_link("Users", $selected === "users")?></a>
  <a href="/top10/tags" class="brackets"><?=self::get_selected_link("Tags", $selected === "tags")?></a>
  <?php if ($ENV->enableDonations) { ?>
  <a href="/top10/donors" class="brackets"><?=self::get_selected_link("Donors", $selected === "donors")?></a>
  <?php } ?>
</div>
<?php
    }


    /**
     * get_selected_link
     */
    private static function get_selected_link($string, $selected)
    {
        if ($selected) {
            return "<strong>$string</strong>";
        } else {
            return $string;
        }
    }


    /**
     * render_artist_tile
     */
    public static function render_artist_tile($artist, $category)
    {
        if (self::is_valid_artist($artist)) {
            switch ($category) {
                case "weekly":
                case "hyped":
                    self::render_tile("artist.php?artistname=", $artist["name"], $artist["image"][3]["#text"]);
                    break;
                default:
                    break;
            }
        }
    }


    /**
     * render_tile
     */
    private static function render_tile($url, $name, $image)
    {
        if (!empty($image)) {
            $name = Text::esc($name); ?>
<li>
  <a
    href="<?=$url?><?=$name?>">
    <img class="tooltip large_tile" alt="<?=$name?>"
      title="<?=$name?>"
      src="<?=ImageTools::process($image)?>" />
  </a>
</li>
<?php
        }
    }


    /**
     * render_artist_list
     */
    public static function render_artist_list($artist, $category)
    {
        if (self::is_valid_artist($artist)) {
            switch ($category) {
                case "weekly":
                case "hyped":
                    self::render_list("artist.php?artistname=", $artist["name"], $artist["image"][3]["#text"]);
                    break;
                default:
                    break;
            }
        }
    }


    /**
     * render_list
     */
    private static function render_list($url, $name, $image)
    {
        if (!empty($image)) {
            $image = ImageTools::process($image);
            $title = "title=\"&lt;img class=&quot;large_tile&quot; src=&quot;$image&quot; alt=&quot;&quot; /&gt;\"";
            $name = Text::esc($name); ?>

<li>
  <a class="tooltip_image" data-title-plain="<?=$name?>" <?=$title?> href="<?=$url?><?=$name?>"><?=$name?></a>
</li>
<?php
        }
    }


    /**
     * is_valid_artist
     */
    private static function is_valid_artist($artist)
    {
        return $artist["name"] !== "[unknown]";
    }
}
