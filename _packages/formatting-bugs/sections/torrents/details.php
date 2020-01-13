<div class="spoilerContainer hideContainer">
    <!--
    This must be one line or the "Show/Hide MediaInfo" button fails
    PHP CS Fixer breaks lines between the tags
    -->
    <input type="button" class="spoilerButton" value="Show MediaInfo" /><blockquote class="spoiler hidden">
<?php
        echo Text::full_format($MediaInfo);
?>
    </blockquote>
  </div>
