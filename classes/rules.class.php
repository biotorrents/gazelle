<?php
class Rules {

  /**
   * Displays the site's "Golden Rules".
   *
   */
  public static function display_golden_rules() {
    ?>
    <ol>
      <li>Staff can do anything to anyone for any reason (or no reason). If you take issue with a decision, you must do so privately with the staff member who issued the decision or with an administrator of the site.</li>
      <li>One account per person per lifetime.</li>
      <li>Follow proper private BitTorrent practices. Torrent files you download from this site are unique to you and should not be shared with others. Torrent files from this site should not be modified by adding additional trackers or enabling DHT or PEX under any circumstances.</li>
      <li>Buying <?=SITE_NAME?> invites is not allowed. If staff discover you have purchased an invite, you will be banned for life. You will be given amnesty if you approach us before you are caught and reveal who your seller was. Waiting until after you are caught will get you nothing.</li>
      <li>Accessing the site from any IP address is permitted, but your account will be linked with other accounts that have accessed the site from the same IP as you. As such, it is <em>recommended</em> that you don't use public networks, proxies, or VPNs to access the site.</li>
      <li>Attempting to find a bug in the site code is allowed and sometimes even rewarded. Follow proper disclosure procedures by contacting staff about the issue well before disclosing it publicly. Do not misuse any bugs you may discover. Do not attempt to portray abuse as a bug in the hopes of a reward.</li>
      <li>Don't reveal the criteria for hidden badges or events.</li>
    </ol>
<?
  }

  /**
   * Displays the site's rules for tags.
   *
   * @param boolean $OnUpload - whether it's being displayed on a torrent upload form
   */
  public static function display_site_tag_rules($OnUpload = false) {
    ?>
    <ul>
      <li>Tags should be comma-separated, and you should use a period (".") to separate words inside a tag &mdash; e.g. "<strong class="important_text_alt">big.breasts</strong>".</li>

      <li>There is a list of official tags <?=($OnUpload ? 'to the left of the text box' : 'on <a href="upload.php">the torrent upload page</a>')?>. Please use these tags instead of "unofficial" tags (e.g. use the official "<strong class="important_text_alt">paizuri</strong>" tag, instead of an unofficial "<strong class="important_text">titfuck</strong>" tag).</strong></li>

      <li>Avoid using multiple synonymous tags. Using both "<strong class="important_text">pissing</strong>" and "<strong class="important_text_alt">urination</strong>" is redundant and stupid &mdash; just use the official "<strong class="important_text_alt">urination</strong>" tag.</li>

      <li>Do not add useless tags that are already covered by other metadata. If a torrent is in the JAV category, it should not be tagged <strong class="important_text">jav</strong>.</li>

      <li>Only tag information related to the group itself &mdash; <strong>not the individual release</strong>. Tags such as "<strong class="important_text">mkv</strong>", "<strong class="important_text">windows</strong>", "<strong class="important_text">scan</strong>", "<strong class="important_text">from.dlsite</strong>", etc. are strictly forbidden. Remember that these tags will be used for other releases in the same group.</li>

      <li>Derivative works may be tagged with the name of the parent series or characters within the work. For example, tags such as "<strong class="tag_parody">touhou</strong>" or "<strong class="tag_character">iori.minase</strong>" may be acceptable if they are being used on derivative (parody) works. These kinds of tags should NOT be used if the series is primarily erotic in nature. Characters should be tagged with the form "<strong class="important_text_alt">surname.firstname</strong>" if applicable.</li>

      <li><strong>Tags should reflect significant aspects of a torrent.</strong> Don't tag something with "<strong class="important_text">blowjob</strong>" if there's only 30 seconds of dick-sucking. However, certain tags may be acceptable, such as "<strong class="important_text_alt">stockings</strong>", even if the torrent in question isn't centered around that fetish. Be smart.</li>

      <li><strong>Certain tags are strongly encouraged for appropriate uploads:</strong> "<strong class="important_text_alt">3d</strong>", "<strong class="important_text_alt">anthology</strong>", "<strong class="important_text_alt">yuri</strong>", "<strong class="important_text_alt">yaoi</strong>". People search for these kinds of things specifically, so tagging them properly will get you more snatches.</li>

      <li>Tags for game genres such as "<strong class="important_text_alt">rpg</strong>", "<strong class="important_text_alt">visual.novel</strong>", or "<strong class="important_text_alt">nukige</strong>" are encouraged.</li>

      <li><strong>Certain tags are <strong class="important_text">required</strong> for appropriate uploads:</strong>"<strong class="important_text_alt">lolicon</strong>", "<strong class="important_text_alt">shotacon</strong>", "<strong class="important_text_alt">toddlercon</strong>". Failure to use these tags may result in punishment.</li>

      <li><strong>Use tag namespaces when appropriate.</strong> Oppaitime allows for tag namespaces to aid with searching. For example, you may want to use the tags "<strong class="important_text_alt">masturbation:male</strong>" or "<strong class="important_text_alt">masturbation:female</strong>" instead of just "<strong class="important_text">masturbation</strong>". They can be used to make search queries more specific. Searching for "<strong class="important_texti_alt">masturbation</strong>" will show all torrents tagged with "<strong class="important_text_alt">masturbation</strong>", "<strong class="important_text_alt">masturbation:male</strong>", or "<strong class="important_text_alt">masturbation:female</strong>". However, searching for "<strong class="important_text_alt">masturbation:female</strong>" will ONLY show torrents with that tag. Tags with namespaces will appear differently depending on the namespace used, which include:
        <ul>
          <li><strong>:parody</strong> - Used to denote a parodied work: <strong class="tag_parody">touhou</strong>, <strong class="tag_parody">kantai.collection</strong></li>
          <li><strong>:character</strong> - Used to denote a character in a parodied work: <strong class="tag_character">iori.minase</strong>, <strong class="tag_character">hakurei.reimu</strong></li>
          <li><strong>:male</strong> - Used to denote that the tag refers to a male character: <strong class="tag_male">masturbation</strong>, <strong class="tag_male">teacher</strong></li>
          <li><strong>:female</strong> - Used to denote that the tag refers to a female character: <strong class="tag_female">masturbation</strong>, <strong class="tag_female">shaved</strong></li>
        </ul>
      </li>

      <li><strong>All uploads require a minimum of 5 tags.</strong> Do not add unrelated tags just to meet the 5 tag requirement. If you can't think of 5 tags for your content, watch/read/play through it again until you can.</li>

      <li><strong>You should be able to build up a list of tags using only the official tags <?=($OnUpload ? 'to the left of the text box' : 'on <a href="upload.php">the torrent upload page</a>')?>.</strong> If you are in any doubt about whether or not a tag is acceptable, do not add it.</li>
    </ul>
<?
  }

  /**
   * Displays the site's rules for the forum
   *
   */
  public static function display_forum_rules() {
    ?>
    <ol>
      <li>Many forums have their own set of rules. Make sure you read and take note of these rules before you attempt to post in one of these forums.</li>
      <li>No commercial advertising or referral schemes. This includes any scheme in which the poster gets personal gain from users clicking a link. You will be immediately banned for this, no questions asked.</li>
      <li>No asking for money for any reason whatsoever. We don't know or care about your friend who lost everything, or dying relative who wants to enjoy their last few moments alive by being given lots of money.</li>
      <li>Do not inappropriately advertise your uploads. In special cases, it is acceptable to mention new uploads in an approved thread, but be sure to carefully read the thread's rules before posting. It is also acceptable to discuss releases you have uploaded when conversing about the title itself. Blatant attempts to advertise your uploads outside of the appropriate forums or threads may result in a warning or loss of privileges.</li>
      <li>No posting requests in forums. There's a requests link at the top of the page for a reason.</li>
      <li>Be sure you read all the sticky threads in a forum before you post.</li>
      <li>Use descriptive and specific subject lines. This helps others decide whether your particular words of "wisdom" relate to a topic they care about.</li>
      <li>Don't post comments that don't add anything to the discussion. This generally includes any post without its own substance, such as "I agree" or "haha". These kinds of posts are annoying to people who are trying to actually find some information in a thread.</li>
      <li>Refrain from quoting excessively. When quoting someone, use only the portion of the quote that is absolutely necessary.</li>
      <li>Do not post any potentially malicious links without sufficient warning.</li>
      <li>Don't waste other people's bandwidth by posting images with a large file size.</li>
      <li>Only offer or request invites to other trackers in designated forums. If you can't find the invite forum, it's because you don't have access to it and should not post about it.</li>
      <li>No language other than English (and Japanese, when relevant) is permitted in the forums.</li>
      <li>Some things that <em>are</em> allowed include mature and graphic content, political and religious discussions, and insults. You are welcome to express whatever inflammatory opinions you want as long as you don't go overboard with it.</li>
    </ol>
<?
  }

  /**
   * Displays the site's rules for conversing on its IRC network
   *
   */
  public static function display_irc_chat_rules() {
    ?>
    <ol>
      <li>Staff have the final decision. If a staff member says stop and you continue, expect repercussions.</li>
      <li>Do not leave Caps Lock enabled all the time. It gets annoying, and you will likely get yourself kicked.</li>
      <li>No opinions, especially related to race, religion, politics, etc are allowed. Failure to comply with a request to cease having an opinion WILL be considered a thoughtcrime and you WILL be reeducated.</li>
      <li>Flooding is irritating and will warrant you a kick if it isn't funny enough or if an admin is cranky. This includes, but is not limited to, automatic "now playing" scripts, pasting large amounts of irrelevant text, and multiple consecutive lines with no relevance to the conversation at hand.</li>
      <li>Impersonation of other members &mdash; particularly staff members &mdash; will not go unpunished. If you are uncertain of a user's identity, check their vhost</li>
      <li>Spamming is <b>strictly</b> forbidden unless it's funny. This includes, but is not limited to, personal sites, online auctions, and cans of blended meat.</li>
      <li>Obsessive annoyance &mdash; both to other users and staff &mdash; will not be tolerated.</li>
      <li>Do not PM, DCC, or Query anyone you don't know or have never talked to without asking first; this applies specifically to staff.</li>
      <li>No language other than English is permitted in the official IRC channels.</li>
      <li>Bots are not permitted in official channels with the exception of <strong>#oppaitime-announce</strong> and <strong>#oppaitime-requests</strong>.</li>
      <li>Any bots you have on IRC should authenticate with Udon using your own username and IRC key</li>
      <li>Bots must identify themselves by setting the +B usermode on themselves.</li>
      <li>Unofficial channels are <em>not</em> policed by staff. Any content or discussion that goes on in unofficial channels shoud be treated as part of an unrelated public irc network.</li>
      <li><strong>Read the channel topic before asking questions.</strong></li>
    </ol>
<?
  }
}
