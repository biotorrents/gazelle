<?php
enforce_login();

// Include the header
if (!$UserCount = $Cache->get_value('stats_user_count')) {
    $DB->query("
    SELECT COUNT(ID)
    FROM users_main
    WHERE Enabled = '1'");
    list($UserCount) = $DB->next_record();
    $Cache->cache_value('stats_user_count', $UserCount, 0); // Infinite cache
}

$DonorPerms = Permissions::get_permissions(DONOR);

View::show_header('Donate');
?>

<div class="thin">
  <span class="donation_info_title">Why donate?</span>
  <div class="box pad donation_info">
    <p>
      <?= SITE_NAME ?> has no advertisements, is not sponsored, and
      provides its services free of charge.
      For these reasons, its financial obligations can
      only be met with the help of voluntary user donations.
      Supporting <?= SITE_NAME ?> is and will always remain
      voluntary.
      If you are financially able, please help pay its
      bills by donating.
    </p>

    <p>
      <?= SITE_NAME ?> uses all voluntary donations to cover the
      costs of running the site, tracker, and IRC network.
      These costs represent the hardware the site runs on (e.g., servers, upgrades, fixes),
      and recurring operating expenses (e.g., hosting, bandwidth, power).
    </p>

    <p>
      No staff member or other individual responsible for the site's operation personally profits from user donations.
      As a donor, your financial support is exclusively applied to operating costs.
      When you donate you are paying <?= SITE_NAME ?>'s bills.
    </p>

    <p>
    If you prefer to make a tax-deductible donation to an organization beyond my financial grasp, donations to the
    <a href="https://www.boslab.org/donate" target="_blank">Boston Open Science Laboratory (BosLab)</a>
    are honored the same as direct donations.
    Please note in the comment field that your donation is on <?= SITE_NAME ?>'s behalf and to credit your username.
    </p>
  </div>

  <span class="donation_info_title">What you will receive for donating</span>
  <div class="box pad donation_info">
    <p>
      Please see <a href="https://www.patreon.com/biotorrents" target="_blank"><?= SITE_NAME ?>'s Patreon</a> for a detailed overview of funding
      goals.
      There are some benefits to donating that culmulate at each tier pledged.
      Each tier's awards include those already listed, and the tier system only applies to recurring donations.
      Bitcoin donations are privately negotiable.

      <ul>
        <li><strong>Bronze.</strong> A donor badge and forum access for your user account on the BioTorrents.de website
        </li>
        <li><strong>Silver.</strong> Unlimited API calls to the BioTorrents.de database</li>
        <li><strong>Gold.</strong> Monthly Skype calls to discuss wetlab and software ideas.
          My expertise includes fungal biotechnology, nonprofit administration, and LEMP+ development</li>
        <li><strong>Sponsor a Seedbox.</strong> Shell access to, and share ratio credit from, a seedbox that I manage on
          your behalf.
          Please understand that network services beyond the scope of seedbox administration should be negotiated
          separately</li>
      </ul>

      <!-- Any donation or contribution option listed above gives you the opportunity to receive Donor Points.
      After acquiring your first Donor Point, your account will unlock Donor Rank #1.
      This rank will last forever, and you'll receive the following perks upon unlocking it:</p>

    <ul>
      <li>Our eternal love, as represented by the heart you get next to your name</li>
      <li><a href="/wiki.php?action=article&amp;id=8">Inactivity</a> timer immunity</li>
      <li>Access to the <a href="/user.php?action=notify">notifications system</a></li>
      <li>Two <a href="/user.php?action=invite">invites</a></li>
      <li><a href="/collages.php">Collage</a> creation privileges</li>
      <li>Personal collage creation privileges</li>
      <li>One additional personal collage</li>
      <li>A warm, fuzzy feeling</li>
      <li>Absolutely nothing else</li>
    </ul> -->

      <p>
        Be reminded that when you make a donation, you aren't "purchasing" Donor Ranks, invites, or any <?= SITE_NAME ?>-specific benefit.
        When donating, you are helping <?= SITE_NAME ?> pay its
        bills, and your donation should be made in this spirit.
        The <?= SITE_NAME ?> staff does its best to recognize <?= SITE_NAME ?>'s financial supporters in a fair and fun way,
        but all Donor Perks are subject to change or cancellation at any time, without notice.
      </p>
  </div>

  <span class="donation_info_title">What you won't receive for donating</span>
  <div class="box pad donation_info">
    <ul>
      <li>Immunity from the rules</li>
      <li>Additional upload credit</li>
    </ul>
  </div>
</div>
<!-- END Donate -->
<?php View::show_footer();
