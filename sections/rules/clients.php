<?php
declare(strict_types=1);

View::show_header('Client rules');

if (!$WhitelistedClients = $Cache->get_value('whitelisted_clients')) {
    $DB->query("
    SELECT
      `vstring`
    FROM
      `xbt_client_whitelist`
    WHERE
      `vstring` NOT LIKE '//%'
    ORDER BY
      `vstring` ASC
    ");

    $WhitelistedClients = $DB->to_array(false, MYSQLI_NUM, false);
    $Cache->cache_value('whitelisted_clients', $WhitelistedClients, 604800);
}
?>

<div class="header">
  <h2>
    Client rules
  </h2>
</div>

<div class="box pad">
  <p>
    Client rules are how we maintain the integrity of our swarms.
    This allows us to filter out disruptive and dishonest clients that may hurt the performance of either the tracker
    or individual peers.
  </p>
  <br />

  <table>
    <tr class="colhead">
      <td>
        <strong>Allowed Clients</strong>
      </td>
    </tr>

    <?php
  foreach ($WhitelistedClients as $Client) {
      list($ClientName) = $Client; ?>

    <tr class="row">
      <td>
        <?=$ClientName?>
      </td>
    </tr>
    <?php
  } ?>
  </table>
</div>

<h3>
  Further rules
</h3>

<div class="box pad rule_summary">
  <p>
    The modification of clients to bypass our client requirements (spoofing) is explicitly forbidden.
    People caught doing this will be instantly and permanently banned.
    When you leak peers, everyone loses.
    This is your only warning.
  </p>

  <p>
    The use of clients or proxies which have been modified to report incorrect stats to our tracker (cheating) is not
    allowed, and will result in a permanent ban.
  </p>

  <p>
    The testing of unstable clients by developers must first be approved by staff.
  </p>
</div>

<?php include('jump.php'); ?>
</div>
<?php View::show_footer();
