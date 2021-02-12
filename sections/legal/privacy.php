<?php
declare(strict_types=1);

View::show_header('Privacy'); ?>

<h2>Privacy Policy</h2>

<section class="tldr">

  <p>
    This policy explains how Omics Tools LLC handles the personal data we collect from you when you use our website.
    You grant consent on account registration by checking the box that reads,
    "I consent to the privacy policy and may revoke my consent at any time."
  </p>


  <h3>
    Data collection: what and how
  </h3>

  <p>
    We collect usernames, email addresses, GPG keys,
    passphrases, API keys, site activity and preferences,
    IP addresses, and server error logs.
  </p>

  <p>
    We don't collect access logs or compile personal data for any commercial reason.
    Also, we explicitly deny all known browser features, including not limited:
    camera, microphone, sensors, wake-lock, USB, encrypted media, autoplay, etc.
  </p>

  <p>
    You directly provide us with most of the data we collect.
    We collect and process your personal data when you
  </p>

  <ul>
    <li>
      register online for our services,
    </li>

    <li>
      query the tracker for BitTorrent peers,
    </li>

    <li>
      participate in our forums and chat rooms, and
    </li>

    <li>
      use our website with cookies or API keys.
    </li>
  </ul>
  <br />


  <h3>
    Data use and storage
  </h3>

  <p>
    We only use your data to manage your account and administer the site.
    We never sell or otherwise provide data to third parties, except by authenticated subpoena.
  </p>

  <p>
    All data read, written, or deleted under this policy will only be managed by SQL queries,
    and any data returned will only be provided as raw output (database dumps).
  </p>

  <p>
    We securely store your data on our hardened MariaDB instance.
    Only Unix socket connections are allowed, and certain services like IRC are denied.
    Database tools aren't accessible on the public internet.
  </p>

  <p>
    Email and IP addresses, and private messages between users,
    are encrypted and then decrypted in memory.
    Certain data is hashed before storage and therefore unrecoverable,
    including passphrases and API keys.
    Please don't request ciphertext.
  </p>

  <p>
    We'll keep your data for your account's lifetime.
    When that time expires, we'll delete your data by written request.
  </p>


  <h3>
    GDPR data protection rights
  </h3>

  <p>
    We'd like to make sure you're fully aware of your data protection rights.
    Each user is entitled to GDPR protection regardless of their jurisdiction.
  </p>

  <p>
    Please attach a screenshot of your profile page to prove account ownership for any transaction.
    It's okay to redact sensitive data like email and passkey.
  </p>

  <ul class="p">
    <li>
      <strong>Access.</strong>
      You have the right to request copies of your data.
      We may charge a small fee for this service.
    </li>

    <li>
      <strong>Rectification.</strong>
      You have the right to request that we correct what you believe is inaccurate,
      and to request that we complete what you believe is not.
    </li>

    <li>
      <strong>Erasure.</strong>
      You have the right to request that we erase your data, under certain conditions.
    </li>

    <li>
      <strong>Restrict Processing.</strong>
      You have the right to request that we restrict processing your data,
      under certain conditions.
    </li>

    <li>
      <strong>Object to Processing.</strong>
      You have the right to object to our processing your data, under certain conditions.
    </li>

    <li>
      <strong>Data Portability.</strong>
      You have the right to request that we transfer data we've collected to you or to others,
      under certain conditions.
    </li>
  </ul>

  <p>
    If you make a request, we have one month to respond.
    Please contact us if you'd like to exercise any of these rights.
  </p>


  <h3>
    Cookies: what and how
  </h3>

  <p>
    Cookies are text files placed on your computer to store functional information.
    When you log into our website, we save cookies to your browser's local storage.
  </p>

  <p>
    We strongly encourage you to use an updated browser with sandboxed tabs,
    and to set your browser to deny disk access and wipe transient data on shutdown.
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
    We regularly review our policy and publish updates here.
    Updates will usually describe new security developments.
    We last updated this policy on 2021-02-11.
  </p>


  <h3>
    How to contact us
  </h3>

  <p>
    If you have any questions about our policy,
    the data we hold on you,
    or you'd like to exercise one of your data protection rights,
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
    gdpr at biotorrents dot de
  </p>


  <h3>
    How to contact the authorities
  </h3>

  <p>
    Should you wish to report a complaint,
    or if you feel that we haven't satisfactorily addressed your concerns,
    contact the Information Commissioner's Office.
  </p>


  <h3>
    COPPA
  </h3>

  <p>
    Omics Tools LLC doesn't knowingly collect data from under-thirteens.
    Our terms require that all users be 18 or older.
    If you believe a child gave out personal data on BioTorrents.de,
    please contact us at once.
  </p>

  <p>
    <strong>
      Email
    </strong>
    <br />
    coppa at biotorrents dot de
  </p>


  <h3>
    HIPAA
  </h3>

  <p>
    Omics Tools LLC doesn't knowingly collect data that violates patient privacy.
    We publish guides on how to anonymize data, and our rules restrict unsanitized data.
    If you believe that content on BioTorrents.de compromises a patient's identity,
    please contact us at once.
  </p>

  <p>
    <strong>
      Email
    </strong>
    <br />
    hipaa at biotorrents dot de
  </p>

  <p>
    Please use
    <a href="https://pgp.mit.edu/pks/lookup?op=get&search=0x760EBED7CFE266D7" target="_blank">GPG 760EBED7CFE266D7</a>
    if you wish.
  </p>
</section>

<?php View::show_footer();
