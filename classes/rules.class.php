<?php
class Rules
{

  /**
   * Displays the site's "Golden Rules".
   *
   */
    public static function display_golden_rules()
    {
        ?>
<ul>
<li><strong>No personally identifying patient data is allowed anywhere on the site.</strong></li>

  <li>Staff can do anything to anyone for any reason (or no reason). If you take issue with a decision, you must do so
    privately with the staff member who issued the decision or with an administrator of the site.</li>

  <li>One account per person per lifetime.</li>

  <li>Follow proper private BitTorrent practices. Torrent files you download from this site are unique to you and should
    not be shared with others. Torrent files from this site should not be modified by adding additional trackers or
    enabling DHT or PEX under any circumstances.</li>

  <li>Buying <?=SITE_NAME?> invites is not allowed. If staff discover
    you have purchased an invite, you will be banned for life. You will be given amnesty if you approach us before you
    are caught and reveal who your seller was. Waiting until after you are caught will get you nothing.</li>

  <li>Accessing the site from any IP address is permitted, but your account will be linked with other accounts that have
    accessed the site from the same IP as you. As such, it is <em>recommended</em> that you don't use public networks,
    proxies, or VPNs to access the site.</li>

  <li>Attempting to find a bug in the site code is allowed and sometimes even rewarded. Follow proper disclosure
    procedures by contacting staff about the issue well before disclosing it publicly. Do not misuse any bugs you may
    discover. Do not attempt to portray abuse as a bug in the hopes of a reward.</li>

  <li>Don't reveal the criteria for hidden badges or events.</li>
</ul>
<?php
    }

    /**
     * Displays the site's rules for tags.
     *
     * @param boolean $OnUpload - whether it's being displayed on a torrent upload form
     */
    public static function display_site_tag_rules($OnUpload = false)
    {
        ?>
<ul>
  <li><strong>Please use the <strong class="important_text_alt">vanity.house</strong> tag for sequences that you or your
      lab produced.</strong> This helps us promote the DIYbio community's original contributions.</li>

  <li>Tags should be comma-separated, and you should use a period to separate words inside a tag, e.g., <strong
      class="important_text_alt">gram.negative</strong>.</li>

  <li>There is a list of official tags <?=($OnUpload ? 'to the left of the text box' : 'on the <a href="upload.php">upload page</a>')?>.
    Please use these tags instead of "unofficial" tags, e.g., use the official <strong
      class="important_text_alt">fungi</strong> tag instead of an unofficial <strong
      class="important_text">mushrooms</strong> tag.</li>

  <li>Avoid using multiple synonymous tags. Using both <strong class="important_text">cyanobacteria</strong> and <strong
      class="important_text_alt">bacteria</strong> is redundant and stupid &mdash; just use the official <strong
      class="important_text_alt">bacteria</strong>.</li>

  <li>Don't add useless tags that are already covered by other metadata. If a torrent is in the DNA category, please
    don't tag it <strong class="important_text">dna</strong>.</li>

  <li>Only tag information related to the group itself &mdash; <strong>not the individual release</strong>. Tags such as
    <strong class="important_text">apollo.100</strong>, <strong class="important_text">hiseq.2500</strong>, etc., are
    strictly forbidden. Remember that these tags will be used for other releases in the same group.</li>

  <li><strong>Certain tags are strongly encouraged for appropriate uploads:</strong> <strong
      class="important_text_alt">archaea</strong>, <strong class="important_text_alt">bacteria</strong>, <strong
      class="important_text_alt">fungi</strong>, <strong class="important_text_alt">animals</strong>, <strong
      class="important_text_alt">plants</strong>, <strong class="important_text_alt">plasmids</strong>. People search
    for these kinds of things specifically, so tagging them properly will get you more snatches.</li>

  <!--
      <li><strong>Use tag namespaces when appropriate.</strong> BioTorrents.de allows for tag namespaces to aid with searching. For example, you may want to use the tags "<strong class="important_text_alt">masturbation:male</strong>" or "<strong class="important_text_alt">masturbation:female</strong>" instead of just "<strong class="important_text">masturbation</strong>". They can be used to make search queries more specific. Searching for "<strong class="important_texti_alt">masturbation</strong>" will show all torrents tagged with "<strong class="important_text_alt">masturbation</strong>", "<strong class="important_text_alt">masturbation:male</strong>", or "<strong class="important_text_alt">masturbation:female</strong>". However, searching for "<strong class="important_text_alt">masturbation:female</strong>" will ONLY show torrents with that tag. Tags with namespaces will appear differently depending on the namespace used, which include:
        <ul>
          <li><strong>:parody</strong> - Used to denote a parodied work: <strong class="tag_parody">touhou</strong>, <strong class="tag_parody">kantai.collection</strong></li>
          <li><strong>:character</strong> - Used to denote a character in a parodied work: <strong class="tag_character">iori.minase</strong>, <strong class="tag_character">hakurei.reimu</strong></li>
          <li><strong>:male</strong> - Used to denote that the tag refers to a male character: <strong class="tag_male">masturbation</strong>, <strong class="tag_male">teacher</strong></li>
          <li><strong>:female</strong> - Used to denote that the tag refers to a female character: <strong class="tag_female">masturbation</strong>, <strong class="tag_female">shaved</strong></li>
        </ul>
      </li>
      -->

  <li><strong>All uploads require a minimum of 5 tags.</strong> Please don't add unrelated tags just to meet the 5 tag
    requirement. If you can't think of 5 tags for your content, study it again until you can.</li>

  <li><strong>You should be able to build up a list of tags using only the official tags <?=($OnUpload ? 'to the left of the text box' : 'on the <a href="upload.php">upload page</a>')?>.</strong>
    If you doubt whether or not a tag is acceptable, please omit it for now and send a staff PM to request a new
    official tag or an approved alias.</li>
</ul>
<?php
    }

    /**
     * Displays the site's rules for the forum
     *
     */
    public static function display_forum_rules()
    {
        ?>
<ul>
  <li>Let's treat the biology boards like how the Shroomery used to be: each thread a set of resourceful diverse wisdom
    worth using permalinks to. It's okay if the boards are slow, that's why there are only a few of them.</li>

  <li>Please discuss site news in the corresponding Announcements thread instead of making a new General thread.
    Discussing science-related news in General is highly encouraged, but discussing political news is much less so.
    Thank you.</li>

  <li>No advertising, referrals, affiliate links, cryptocurrency pumps, or calls to action that involve using a
    financial instrument. You'll be banned on the spot. The exceptions: discussions about cryptocurrencies that derive their value from
    work performed on distributed science networks, i.e., Curecoin, FoldingCoin, and Gridcoin.</li>

  <li>Feel free to post announcements for your own projects, even and especially if they're commercial ones, in the
    General board. Limit all discussion of trading biomaterials, including bulk giveaways, to the Marketplace forum
    available to Power Users.</li>

  <li>Please be modest when talking about your uploads. It's unnecessary to announce your uploads because Gazelle logs
    everything (at least this installation's database is encrypted). If someone asks for help on his project and your
    upload fits the bill, go write a post!</li>

  <li>Use descriptive and specific subject lines. This helps others decide whether your particular words of "wisdom"
    relate to a topic they care about.</li>

  <li>Don't post comments that don't add anything to the discussion, such as "I agree" or "haha." Bottle the trained
    dopamine response to social media because comment reactions are an unlikely feature.</li>

  <li>Please refrain from quoting excessively. When quoting someone, use only the necessary parts of the quote. Avoid
    quoting more than 3 levels deep.</li>

  <li>Don't post potentially malicious links without sufficient warning, or post pictures > 2 MiB. Please only speak
    English as stated in the upload rules.</li>
</ul>
<?php
    }

    /**
     * Displays the site's rules for conversing on its IRC network
     *
     */
    public static function display_irc_chat_rules()
    {
        ?>
<li>BioTorrents.de's Slack channels are just are another quiet hangout you can stack your app with so you look cool at
  conferences.</li>

<li>Please use <code>#general</code> for the usual chit-chat, <code>#development</code> for questions about the Gazelle
  software, and <code>#support</code> to get help with your account.</li>

<li>Don't send mass alerts with <code>@channel</code>, <code>@everyone</code>, or <code>@here</code>. It's obnoxious and
  you should handle anything genuinely important on the boards.</li>

<li>Flooding is irritating and you'll get kicked for it. This includes "now playing" scripts, large amounts of
  irrelevant text such as lorem ipsum, and unfunny non sequiturs.</li>

<li>Impersonating other members &mdash; particularly staff members &mdash; will not go unpunished. Please remember that
  the Slack channels are publicly accessible.</li>

<li>Please use the threaded conversations feature in Slack and avoid replying to threads with new messages or
  crossposting replies to the main channel.</li>

<li>Announce and bot channels are in development, as standard IRC instead of Slack for obvious reasons. Any IRC bots you
  have must authenticate with your own username and IRC key, and set the <code>+B</code> usermode on themselves.</li>
<?php
    }
}
