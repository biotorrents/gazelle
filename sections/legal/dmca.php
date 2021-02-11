<?php
declare(strict_types=1);

View::show_header('DMCA');
?>

<h2>DMCA Information</h2>

<section class="tldr">
  <p>
    <em>If</em> you're a copyright owner or an agent thereof,
    <em>and</em> you believe that user-generated content (UGC) on the domain https://biotorrents.de infringes your
    copyrights:
    <em>then</em> you may notify our Digital Millennium Copyright Act (DMCA) agent in writing.
  </p>

  <ul class="p">

    <li>
      Identification of the copyrighted work claimed to have been infringed.
      <em>Please include your copyright registration number or proof of status pending.</em>
      Copyright infringement claims for U.S. works require registration.
      Requests without a registration number will be ignored.
    </li>

    <li>
      Identification of the material that is claimed to be infringing.
      To speed up request processing, please include:
      (1) the torrent's permalink <code>[PL]</code> URI exactly as it appears, <em>and</em>
      (2) the corresponding BitTorrent <code>info_hash</code>.
    </li>

    <li>
      A statement that you have a good faith belief that use of the material in the manner complained of is not
      authorized by the copyright owner, its agent, or the law.
    </li>

    <li>
      A statement that the information in the notification is accurate, and under penalty of perjury,
      that you're authorized to act on behalf of the owner of an exclusive right that is allegedly infringed.
    </li>


    <li>
      Your physical or electronic signature, or of someone authorized to act on your behalf.
    </li>

    <li>
      Information reasonably sufficient to permit BioTorrents.de to contact you,
      such as an address, telephone number, and email.
    </li>
  </ul>

  <p>
    Because a high percentage of DMCA takedown notices are invalid or abusive,
    BioTorrents.de reserves the right to ignore requests for unregistered works.
  </p>

  <p>
    BioTorrents.de authenticates all valid requests.
    As a stopgap pending investigation,
    access to the targets of valid requests will be expeditiously disabled.
  </p>

  <p>
    All relevant parties will be notified and updated during the investigation.
    The targets of successful claims will then be deleted.
  </p>

  <p>
    Circumstances that may delay request processing, including not limited:
  </p>

  <ul class="p">

    <li>
      URI formulations that violate BioTorrents.de's normal access rules,
      e.g., unsecured HTTP or the <code>www</code> subdomain,
      <em>or</em> requests that fail to identify a specific piece of UGC.
    </li>

    <li>
      Generic or boilerplate statements.
      Neither statement should contain passages with quoted online search results.
    </li>

    <li>
      Requests signed by other means than 256-bit Ed25519 or 4096-bit RSA,
      or encoded in other formats than UTF-8 or ASCII plaintext.
    </li>


    <li>
      PO boxes, addresses outside the U.S., or addresses that can't accept USPS Certified Mail.
      VoIP telephone numbers or numbers without a <code>+1</code> country code.
    </li>

    <li>
      Email servers that don't comply with at least two of:
      <a href="https://tools.ietf.org/html/rfc7208">RFC 7208 (SPF)</a>,
      <a href="https://tools.ietf.org/html/rfc8463">RFC 8463 (DKIM)</a>, and
      <a href="https://tools.ietf.org/html/rfc7489">RFC 7489 (DMARC)</a>.
      Requests from free mailboxes such as Gmail, ProtonMail, Yahoo, etc.
      Any email in violation of
      <a href="https://www.law.cornell.edu/uscode/text/15/7704">15 USC 7704(a)</a>.
    </li>

  </ul>

  <p>
    Our agent to receive notifications of claimed infringement is:
  </p>

  <p>
    <strong>
      Address
    </strong>
    <br />

    Copyright Manager<br />
    Omics Tools LLC<br />
    30 N Gould St Ste 4000<br />
    Sheridan, WY 82801
  </p>

  <p>
    <strong>
      Email
    </strong>
    <br />
    dmca at biotorrents dot de
  </p>

  <p>
    Please remember that under
    <a href="https://www.law.cornell.edu/uscode/text/17/512">17 USC 512(f)</a>,
    any person who knowingly materially misrepresents infringement may be subject to liability.
  </p>

  <p>
    Consult your legal counsel or see 17 USC 512(c)(3) to confirm these requirements.
    Please also see
    <a href="https://www.law.cornell.edu/uscode/text/17/108">17 USC 108</a>.
  </p>

</section>

<?php View::show_footer();
