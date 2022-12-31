<?php
declare(strict_types=1);

/**
 * This version of #quickpostform is used in all subsections.
 * Instead of modifying multiple places, just modify this one.
 *
 * To include it in a section use this example.
 *
 * View::parse('generic/reply/quickreply.php', array(
 *   'InputTitle' => 'Post',
 *   'InputName' => 'thread',
 *   'InputID' => $ThreadID,
 *   'ForumID' => $ForumID,
 *   'TextareaCols' => 90
 * ));
 *
 * Note that InputName and InputID are the only required variables.
 * They're used to construct the $_POST.
 *
 * e.g.,
 * <input name="thread" value="123" />
 * <input name="groupid" value="321" />
 *
 * Globals are required as this template is included within a
 * function scope.
 *
 * To add a "Subscribe" box for non-forum pages (like artist/collage/...
 * comments), add a key 'SubscribeBox' to the array passed to View::parse.
 *
 * e.g.,
 * View::parse('generic/reply/quickreply.php', array(
 *   'InputTitle' => 'Comment',
 *   'InputName' => 'groupid',
 *   'InputID' => $GroupID,
 *   'TextareaCols' => 65,
 *   'SubscribeBox' => true
 * ));
 */

$app = App::go();

global $HeavyInfo, $UserSubscriptions, $ThreadInfo, $Document;

if ($app->userNew->extra['DisablePosting']) {
    return;
}

/*
if (!isset($TextareaCols)) {
    $TextareaCols = 70;
}
*/

/*
if (!isset($TextareaRows)) {
    $TextareaRows = 8;
}
*/

if (!isset($InputAction)) {
    $InputAction = 'reply';
}

if (!isset($InputTitle)) {
    $InputTitle = 'Comment';
}
?>

<div id="reply_box">
  <h3>
    <?=$InputTitle?>
  </h3>

  <div class="box pad">
    <form class="send_form" name="reply" id="quickpostform" <?=isset($Action) ? 'action="'.$Action.'"' : ''?>
      method="post"

      <?php if (!check_perms('users_mod')) { ?>
      onsubmit="quickpostform.submit_button.disabled = true;"
      <?php } ?>

      <?php if (!$app->userNew->extra['DisableAutoSave']) { ?>
      data-autosave-text="quickpost"
      <?php } ?>>

      <input type="hidden" name="action" value="<?=$InputAction?>" />

      <input type="hidden" name="auth"
        value="<?=$app->userNew->extra['AuthKey']?>" />

      <input type="hidden" name="<?=$InputName?>"
        data-autosave-id="<?=$InputID?>"
        value="<?=$InputID?>" />

      <div id="quickreplytext">
        <?php
        # Needs to come before, e.g., $ReplyText->getID()
        $ReplyText = new TEXTAREA_PREVIEW(
            $Name = 'body',
            $ID = 'quickpost',
            $Value = '',
            /*
            $Preview = false,
            $Buttons = false,
            $Buffer = true,
            $ExtraAttributes = [
                'tabindex="1"',
                'onkeyup="resize(\'quickpost\')"',
            ]
            */
        ); ?>
        <?=null#$ReplyText->getBuffer()?>
      </div>

      <!--
        Start moved stuff
        $ReplyText->getBuffer() seems broken with EasyMDE
        The new textarea needs to come before, e.g., $ReplyText->getID()
      -->
      <table class="forum_post box vertical_margin hidden preview_wrap"
        id="preview_wrap_<?=$ReplyText->getID()?>">

        <colgroup>
          <?php if (User::hasAvatarsEnabled()) { ?>
          <col class="col_avatar" />
          <?php } ?>

          <col class="col_post_body" />
        </colgroup>

        <tr class="colhead_dark">
          <td colspan="<?=(User::hasAvatarsEnabled() ? 2 : 1)?>">
            <div class="u-pull-left">
              <a href="#quickreplypreview">#xyz</a>
              by <strong>
                <?=User::format_username($app->userNew->core["id"], true, true, true, true)?>
              </strong>
              Just now
            </div>

            <div class="u-pull-right">
              <a href="#quickreplypreview" class="brackets">Report</a>
              &nbsp;
              <a href="#">&uarr;</a>
            </div>
          </td>
        </tr>

        <tr>
          <?php if (User::hasAvatarsEnabled()) { ?>
          <td class="avatar valign_top">
            <?=
          User::displayAvatar(
              $app->userNew->extra['Avatar'],
              $app->userNew->core['Username']
          )
          ?>
          </td>
          <?php } ?>

          <td class="body valign_top">
            <div id="contentpreview" style="text-align: left;">
              <div id="preview_<?=$ReplyText->getID()?>">
              </div>
            </div>
          </td>
        </tr>
      </table>
      <!-- End moved stuff -->

      <div class="preview_submit">
        <?php
  if (isset($SubscribeBox) && !isset($ForumID)
      && Subscriptions::has_subscribed_comments($Document, $InputID) === false) { ?>
        <input id="subscribebox" type="checkbox" name="subscribe" <?=!empty($HeavyInfo['AutoSubscribe']) ? ' checked="checked"' : ''?>
        tabindex="2" />
        <label for="subscribebox">Subscribe</label>
        <?php
  }

  // Forum thread logic
  // This might use some more abstraction
  if (isset($ForumID)) {
      if (!Subscriptions::has_subscribed($InputID)) { ?>
        <input id="subscribebox" type="checkbox" name="subscribe" <?=!empty($HeavyInfo['AutoSubscribe']) ? ' checked="checked"' : ''?>
        tabindex="2" />
        <label for="subscribebox">Subscribe</label>
        <?php
      }

      if ($ThreadInfo['LastPostAuthorID'] === $app->userNew->core["id"]
          && (check_perms('site_forums_double_post'))) { ?>
        <input id="mergebox" type="checkbox" name="merge" tabindex="2" />
        <label for="mergebox">Merge</label>
        <?php
      }
  } ?>

        <input type="button" value="Preview"
          class="hidden button_preview_<?=$ReplyText->getID()?>"
          tabindex="1" />

        <input type="submit" class="button-primary" value="Post" id="submit_button" tabindex="1" />
      </div>
    </form>
  </div>
</div>