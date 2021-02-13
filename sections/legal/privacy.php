<?php
declare(strict_types=1);

View::show_header('Privacy'); ?>

<h2>Privacy Policy</h2>

<section class="tldr">
  <p>
    Omics Tools LLC safeguards the personal data we collect from you on our website.
    You consent on account registration by checking the box labelled,
    "I consent to the privacy policy."
  </p>


  <h3>
    Data collection: what and how
  </h3>

  <p>
    We collect and use personal data defined as
  </p>

  <ul>
    <li>
      usernames, email addresses, passphrases, and 2FA seeds;
    </li>

    <li>
      GPG keys, IRC keys, API keys, passkeys, and authkeys;
    </li>

    <li>
      IP addresses, and login and access timestamps;
    </li>

    <li>
      account preferences, activity, and history;
    </li>

    <li>
      and server error logs.
    </li>
  </ul>

  <p>
    We don't collect cross-origin data.
    Also, we don't access
    <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Feature-Policy#directives">browser features</a>
    such as camera, microphone, and sensors.
  </p>

  <p>
    You directly provide us with most personal data.
    We collect data when you
  </p>

  <ul>
    <li>
      register online for our services,
    </li>

    <li>
      query the tracker for BitTorrent peers,
    </li>

    <li>
      participate in our forums and chat rooms,
    </li>

    <li>
      and use our website with cookies or API keys.
    </li>
  </ul>
  <br />


  <h3>
    Data use and storage
  </h3>

  <p>
    We use your personal data to manage your account and administer the site.
    We don't sell or provide data to third parties, except as required by law.
  </p>

  <p>
    We store your personal data on our own servers.
    Sensitive data is encrypted or hashed, defined as:
    email and IP addresses, private messages, passphrases, and API keys.
  </p>

  <p>
    We'll keep your personal data until account termination.
    Please contact us to terminate your account.
    Termination deletes your personal data and revokes your passkey.
  </p>

  <p>
    Note that we may need to keep data for archiving purposes.
  </p>


  <h3>
    GDPR: European Union residents
  </h3>

  <p>
    <a href="https://gdpr-info.eu/art-14-gdpr/">Art. 14 GDPR ¶ 5(a) and (b)</a>, and
    <a href="https://gdpr-info.eu/art-27-gdpr/">Art. 27 ¶ 2</a> apply.
    Accordingly, we haven't designated an EU representative.
    Generally, we don't collect any personal data as defined in
    <a href="https://gdpr-info.eu/art-4-gdpr/">Art. 4</a>
    except what you voluntarily provide us.
  </p>

  <p>
    We don't pass information to any 3rd-party processors or share it with anyone.
    We only use it to communicate with you for our mutual business purposes,
    and we'll be happy to delete your information
    to the extent you don't enter into a business relationship with us.
  </p>


  <h3>
    CCPA: California residents
  </h3>

  <p>
    We're exempt from the California Consumer Privacy Act pursuant to
    <a href="https://ccpa-info.com/home/1798-140-definitions/">CIV 1798.140(c)(1)(A)</a>.
    Nonetheless, we'll be happy to delete your information
    to the extent you don't enter into a business relationship with us.
  </p>


  <h3>
    Cookies: what and how
  </h3>

  <p>
    Cookies are text files placed on your computer to store functional information.
    When you log into our website, we save cookies to your browser's local storage.
  </p>

  <p>
    We use cookies to keep you signed in.
    Our secure session cookie parameters include:
  </p>

  <ul>
    <li>
      one-day expiry time,
    </li>

    <li>
      scoped to https://biotorrents.de,
    </li>

    <li>
      TLS 1.2+ transmission only,
    </li>

    <li>
      unavailable to JavaScript APIs, and
    </li>

    <li>
      strict same-origin policy.
    </li>
  </ul>

  <p>
    You can set your browser to deny cookies
    but our website won't function as intended.
  </p>


  <h3>
    Other websites' policies
  </h3>

  <p>
    BioTorrents.de links to other websites.
    Our privacy policy only applies to our website.
    If you click an external link, please read their privacy policy.
  </p>


  <h3>
    Changes to our policy
  </h3>

  <p>
    We last updated this policy on 2021-02-13.
  </p>


  <h3>
    How to contact us
  </h3>

  <p>
    If you have questions about our policy,
    the personal data we hold on you,
    or you'd like to exercise your data protection rights,
    please don't hesitate to contact us.
  </p>

  <p>
    <strong>
      Address
    </strong>
    <br />

    Data Protection Officer<br />
    Omics Tools LLC<br />
    30 N Gould St Ste 4000<br />
    Sheridan, WY 82801
  </p>

  <p>
    <strong>
      Email
    </strong>
    <br />
    privacy at biotorrents dot de
  </p>

  <p>
    Please use
    <a href="/sections/legal/pubkey.txt">GPG A1D095A5DEC74A8B</a>
    if you wish.
  </p>
</section>

<?php View::show_footer();
