<?php
declare(strict_types=1);

enforce_login();
$ENV = ENV::go();

// Include the header
if (!$UserCount = $Cache->get_value('stats_user_count')) {
    $DB->query("
      SELECT COUNT(ID)
      FROM users_main
      WHERE Enabled = '1'");
    list($UserCount) = $DB->next_record();
    $Cache->cache_value('stats_user_count', $UserCount, 0); // inf cache
}

$DonorPerms = Permissions::get_permissions(DONOR);
View::show_header('Donate');
?>

<div>
  <h2>
    <?= $ENV->SITE_NAME ?> budget breakdown
  </h2>

  <div class="box pad">
    <p>
      <?= $ENV->SITE_NAME ?> has no advertisements, is not sponsored,
      and provides its services free of charge.
      For these reasons, its main income source is voluntary user donations.
      Supporting the site is and will remain voluntary.
      If you're financially able, please help pay its bills by donating.
      We use the donations to cover the costs of running the site and tracker.
    </p>

    <p>
      No staff member or other individual responsible for the site's operation personally profits from user donations.
      As a donor, your financial support is exclusively applied to operating costs.
      By donating to <?= $ENV->SITE_NAME ?>, you're helping to defray
      the
      recurring costs of necessary information services.
    </p>

    <p>
      <?= $ENV->SITE_NAME ?> operates on a shoestring budget.
      The costs of running the site, and all its technical and legal infrastructure, are minimal by design.
      Please find a detailed site budget below.
    </p>

    <ul>
      <li>
        <strong>Server.</strong>
        We use two budget VPSes at 2.50€ per month each.
      </li>

      <li>
        <strong>Domain.</strong>
        The primary domain name (biotorrents.de) is $15 per year.
        The secondary one (torrents.bio) is $80 per year.
        The TLS certificates are gratis.
      </li>

      <li>
        <strong>Company.</strong>
        Omics Tools LLC is <?= $ENV->SITE_NAME ?>'s parent company.
        It's $50 per year for annual reports and $125 for resident agent services.
      </li>

      <li>
        <strong>Legal.</strong>
        Registering a U.S. copyright agent is $6 per year.
        The legal counsel is gratis.
      </li>


      <li>
        <strong>Total.</strong>
        Depending on the exchange rate, it costs about $350 per year to run <?= $ENV->SITE_NAME ?>.
      </li>

    </ul>
  </div>


  <h2>
    How to donate to <?= $ENV->SITE_NAME ?>
  </h2>

  <div class="box pad">
    <p>
      <?= $ENV->SITE_NAME ?> accepts a tactful array of donations.
      We also accept <strong>tax-deductible donations</strong> on behalf of the
      <a href="https://www.boslab.org/donate" target="_blank">Boston Open Science Laboratory (BosLab)</a>,
      a registered 501c3.
      Please use the memo field to credit your account:
      <strong>your username ℅ <?= $ENV->SITE_NAME ?>.</strong>
    </p>

    <p>
      Unlike affiliate donations to BosLab, where the funds are beyond our control,
      direct donations are used exclusively for <?= $ENV->SITE_NAME ?>'s operating costs.
      Please see <a href="https://www.patreon.com/biotorrents" target="_blank"><?= $ENV->SITE_NAME ?>'s Patreon</a>
      for an overview of funding goals.
    </p>

    <figure class="donate_button">
      <a href="https://www.patreon.com/bePatron?u=27142321" target="_blank">
        <img src="<?= $ENV->STATIC_SERVER ?>/images/logos/patreon.png" />
      </a>
    </figure>

    <p>
      There are two donor tiers on Patreon, both with the same benefits:
      <a href="https://docs.biotorrents.de target=" _blank">Unlimited API calls</a>,
      and social features including private forum access and a heart profile badge.
    </p>

    <ul>
      <li>
        <strong>Silver.</strong>
        $2 per month recurring.
      </li>

      <li>
        <strong>Gold.</strong>
        $5 per month recurring.
      </li>
    </ul>

    <p>
      We also accept private donations of cash and cash equivalents,
      including <strong>Bitcoin</strong> and other cryptocurrencies:
      Monero, Litecoin, and Curecoin.
      <strong>PayPal</strong> and <strong>USPS money orders</strong> are also options.
    </p>

    <p>
      Please use
      <a href="/sections/legal/pubkey.txt">GPG A1D095A5DEC74A8B</a>
      if you wish.
    </p>
  </div>


  <h2>
    Donate time and expertise instead
  </h2>

  <div class="box pad">
    <p>
      <?= $ENV->SITE_NAME ?> understands that not everyone who wants
      to help may feel comfortable donating.
      Please consider getting involved with development and the community instead.
      Note that Donor Points are only awarded for monetary transactions and not volunteer work.
      There are many ways to provide alternative support, use your imagination!
    </p>

    <ul>
      <li>
        Contributing issues and pull requests to the
        <a href="https://github.com/biotorrents/gazelle" target="_blank">Git repo</a>
      </li>

      <li>
        Following and retweeting the
        <a href="https://twitter.com/biotorrents" target="_blank">Twitter account</a>
      </li>

      <li>
        Using the Twitter hashtag
        <a href="https://twitter.com/hashtag/P2Pbio" target="_blank">#P2Pbio</a>
      </li>

      <li>
        Making artwork, icons, and media to advertise the site
      </li>

      <li>
        Asking friends in academic, industry, and media to check it out
      </li>

      <li>Citing <?= $ENV->SITE_NAME ?> in your research:<br />
        <pre>
@misc{ BioTorrents.de,
  author = {Omics Tools LLC},
  title  = {Serving P2P biology data on Debian 9 with BitTorrent},
  year   = {2020},
  url    = \href{https://github.com/biotorrents/announcement}{biotorrents/announcement},
  note   = {Online; accessed <?=date('Y-m-d')?>},
}
        </pre>
      </li>

    </ul>
  </div>


  <h2>
    What donating means for your account
  </h2>

  <div class="box pad">
    <p>
      Please remember that when you make a donation, you aren't "purchasing" Donor Ranks, invites, or any <?= $ENV->SITE_NAME ?>-specific benefit.
      When donating, you're helping <?= $ENV->SITE_NAME ?> pay its
      bills,
      and your donation should be made in this spirit.
      The <?= $ENV->SITE_NAME ?> staff does its best to recognize our
      financial supporters in a fair and fun way,
      but all donor perks are subject to change or cancellation at any time, without notice.
    </p>

    <p>
      Any donation or contribution option listed above gives you the opportunity to receive Donor Points.
      Donor Points are awarded at a rate of one point per transaction, regardless of the amount.
      After acquiring your first Donor Point, your account will unlock Donor Rank #1.
      This rank will last forever, and you'll receive the below perks when you unlock it.
    </p>

    <ul>
      <li>Our eternal love, as represented by the heart you get next to your name</li>
      <li><a href="/wiki.php?action=article&amp;id=8">Inactivity</a> timer immunity</li>
      <li>Access to the <a href="/user.php?action=notify">notifications system</a></li>
      <li>Two <a href="/user.php?action=invite">invites</a></li>
      <li><a href="/collages.php">Collage</a> creation privileges</li>
      <li>Personal collage creation privileges</li>
      <li>One additional personal collage</li>
      <li>A warm, fuzzy feeling</li>
    </ul>

    <p>What you won't receive for donating:</p>

    <ul>
      <li>Immunity from the rules</li>
      <li>Additional upload credit</li>
    </ul>
  </div>

</div>
<!-- END Donate -->
<?php View::show_footer();
