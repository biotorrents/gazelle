<?php
#declare(strict_types = 1);


$app = \Gazelle\App::go();
?>


<div id="compose"
  class="<?=($Hidden ? 'hidden' : '')?>">

  <form class="send_form" name="staff_message" action="staffpm.php" method="post">
    <input type="hidden" name="action" value="takepost" />

    <h3>
      <label for="subject">Subject</label>
    </h3>

    <input size="95" type="text" name="subject" id="subject" />
    <br />

    <h3>
      <label for="message">Message</label>
    </h3>

    <?php
$TextPrev = new TEXTAREA_PREVIEW(
    $Name = 'message',
    $ID = 'message',
); ?>

    <strong>Send to: </strong>
    <select name="level">
      <?php if (!isset($app->userNew->extra['LockedAccount'])) { ?>
      <option value="0" selected="selected">First Line Support</option>
      <option value="800">Forum Moderators</option>
      <?php } ?>
      <option value="1000">Staff</option>
    </select>

    <input type="button" value="Preview"
      class="hidden button_preview_<?=$TextPrev->getID()?>" />
    <input type="submit" class="button-primary" value="Send message" />
    <input type="button" value="Hide" data-toggle-target="#compose" />
  </form>
</div>