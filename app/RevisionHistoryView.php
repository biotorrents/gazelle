<?php
#declare(strict_types=1);

class RevisionHistoryView
{
    /**
     * Render the revision history
     * @param array $RevisionHistory see RevisionHistory::get_revision_history
     * @param string $BaseURL
     */
    public static function render_revision_history($RevisionHistory, $BaseURL)
    {
        $app = \Gazelle\App::go();

        ?>
<table cellpadding="6" cellspacing="1" border="0" width="100%" class="box">
  <tr class="colhead">
    <td>Revision</td>
    <td>Date</td>
    <?php if ($app->user->can(["admin" => "moderateUsers"])) { ?>
    <td>User</td>
    <?php } ?>
    <td>Summary</td>
  </tr>
  <?php
    foreach ($RevisionHistory as $Entry) {
        list($RevisionID, $Summary, $Time, $UserID) = $Entry; ?>
  <tr class="row">
    <td>
      <?= "<a href=\"$BaseURL&amp;revisionid=$RevisionID\">#$RevisionID</a>" ?>
    </td>
    <td>
      <?=$Time?>
    </td>
    <?php if ($app->user->can(["admin" => "moderateUsers"])) { ?>
    <td>
      <?=User::format_username($UserID, false, false, false)?>
    </td>
    <?php } ?>
    <td>
      <?=($Summary ? $Summary : '(empty)')?>
    </td>
  </tr>
  <?php
    } ?>
</table>
<?php
    }
}
