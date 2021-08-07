<?php
declare(strict_types=1);

# Formerly Rules::display_golden_rules()
$ENV = ENV::go();
View::show_header('Golden rules');
?>

<div>
  <div class="header">
    <h2 class="center">
      Golden rules
    </h2>
  </div>

  <div class="box pad rule_summary">
    <ul>
      <li>
        <strong>
          <a href="https://www.dol.gov/general/ppii" target="_blank">Personal Identifiable Information (PII)</a>
          isn't allowed anywhere on the site without explicit consent.
        </strong>
      </li>

      <li>
        Staff can do anything to anyone for any reason (or no reason).
        If you take issue with a decision, you must do so privately with the staff member who issued the decision.
      </li>

      <li>
        One account per person per lifetime.
      </li>

      <li>
        Follow proper private BitTorrent practices.
        Torrent files you download from this site are unique to you and should not be shared with others.
        Torrent files from this site should not be modified under any circumstances.
      </li>

      <li>
        Buying <?= $ENV->SITE_NAME ?> invites is not allowed.
        If staff discover you have purchased an invite, you will be banned for life.
        You will be given amnesty if you approach us before you are caught and reveal who your seller was.
        Waiting until after you are caught will get you nothing.
      </li>

      <li>
        Accessing the site from any IP address is permitted.
        <!-- but your account will be linked with other accounts that have accessed the site from the same IP as you. -->
        It is <em>recommended</em> that you don't use public networks, proxies, or VPNs to access the site.
      </li>

      <li>
        Attempting to find a bug in the site code is allowed and sometimes even rewarded.
        Follow proper disclosure procedures by contacting staff about the issue well before disclosing it publicly.
        Do not misuse any bugs you may discover.
        Do not attempt to portray abuse as a bug in the hopes of a reward.
      </li>

      <li>
        Don't reveal the criteria for hidden badges or events.
      </li>
    </ul>
  </div>

  <?php include('jump.php'); ?>
</div>
<?php View::show_footer();
