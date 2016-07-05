<?php
class Rules {

  /**
   * Displays the site's "Golden Rules".
   *
   */
  public static function display_golden_rules() {
    ?>
    <ol>
      <li>All staff decisions must be respected. If you take issue with a decision, you must do so privately with the staff member who issued the decision or with an administrator of the site. Complaining about staff decisions in public or otherwise disrespecting staff members WILL result in the offender being called a faggot.</li>
      <li>Access to this website is a gift and a privilege, and it can be taken away from you for any reason (or no reason).</li>
      <li>One account per person per lifetime. Anyone creating additional accounts will probably be banned. Additionally, unless your account is immune to <a href="wiki.php?action=article&name=inactivitypruning">inactivity pruning</a>, accounts are automatically disabled if one page load is not made at least once every four months.</li>
      <li>Avatars must not exceed <span class="tooltip" title="524,288 bytes">512 kiB</span> or be vertically longer than 600 pixels. Avatars may contain nudity and offensive imagery, but must not be pictures of Spiderman</li>
      <li>Do not post our torrent files on other sites. Your personal passkey is embedded in every torrent file. The tracker will automatically disable your account if you share your torrent files with others (maybe). You will not get your account back (maybe). This does not prohibit you from sharing the content of the torrents on other sites, but this does prohibit you from sharing the torrent file itself (i.e. the file with a ".torrent" file extension).</li>
      <li>Any torrent you are seeding to this tracker must have <em>only</em> <?=SITE_NAME?>'s tracker URL in it. Adding another BitTorrent tracker's URL will cause incorrect data to be sent to our tracker, and you will be disabled for cheating. Similarly, your client must have DHT and PEX (peer exchange) disabled for all <?=SITE_NAME?> torrents.</li>
      <li>This is a BitTorrent site which promotes sharing amongst the community. If you are not willing to give back to the community what you take from it, this site is not for you. In other words, we expect you to have an acceptable share ratio. If you download a torrent, please seed the copy you have until there are sufficient people seeding the torrent before you stop.</li>
      <li>Feel free to browse the site using proxies or Tor. We reserve the right to scrutinize your activity more than normal in these cases, but no harm, no foul. This includes VPNs with dynamic IP addresses.</li>
      <li>Invites should be offered in the Invites forum and nowhere else.</li>
      <li>Selling <?=SITE_NAME?> invites is strictly prohibited and will result in a permanent ban. Responding to public requests for invites may also jeopardize your account and the accounts of those you invite from a public request if the person you invite turns out to be a total shitfuck.</li>
      <li>Buying <?=SITE_NAME?> invites is discouraged, but if you did buy an invite, tell us who the seller is and we'll let you keep your account. If you don't tell us and we find out, you're banned, kiddo.</li>
      <li>Trading or selling your account is strictly prohibited. If you no longer want your account, send a <a href="staffpm.php">Staff PM</a> requesting that it be disabled. Do not give your account to some asshole.</li>
      <li>You are completely responsible for the people you invite. If your invitees are caught cheating or selling invites, not only will they be banned, so will you (or we'll take away your invite privs). Be careful who you invite. Invites are a precious commodity.</li>
      <li>Be careful when sharing an IP address or a computer with a friend if they have (or have had) an account. We don't really care where you log in from, but from then on, your accounts will be permanently linked because we're using Gazelle, and if one of you violates the rules, both accounts might be disabled along with any other accounts linked by IP address if we get confused. This rule applies to logging into the site.</li>
      <li>Attempting to find a bug in the site code is absolutely fine. Misusing that knowledge is not, but we actively encourage users to try to find bugs and report them so they can be fixed. The discovery of significant bugs may result in a reward at the discretion of the staff. Do not be an asshole and try to flood the tracker or something and then come to us saying "lol I found bug gib reward"</li>
      <li>We're a community. Working together is what makes this place what it is. There are well over a thousand new torrents uploaded every day (pfffff) and, sadly, the staff are only a little psychic. If you come across something that violates a rule, report it, and help us better organize the site for you.</li>
      <li>We respect the wishes of other BitTorrent trackers that we agree with here, as we wish for them to do the same. Please refrain from posting full names or links to sites that do not want to be mentioned.</li>
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
      <li>Tags should be comma-separated, and you should use a period (".") to separate words inside a tag&#8202;&mdash;&#8202;e.g. "<strong class="important_text_alt">big.breasts</strong>".</li>

      <li>There is a list of official tags <?=($OnUpload ? 'to the left of the text box' : 'on <a href="upload.php">the torrent upload page</a>')?>. Please use these tags instead of "unofficial" tags (e.g. use the official "<strong class="important_text_alt">paizuri</strong>" tag, instead of an unofficial "<strong class="important_text">titfuck</strong>" tag).</strong></li>

      <li>Avoid using multiple synonymous tags. Using both "<strong class="important_text">pissing</strong>" and "<strong class="important_text_alt">urination</strong>" is redundant and stupid&#8202;&mdash;&#8202;just use the official "<strong class="important_text_alt">urination</strong>" tag.</li>

      <li>Do not add useless tags.</li>

      <li><strong>If one more person tags something "<strong class="important_text">hentai</strong>" I swear to god I'm gonna go nuclear on your worthless ass.</strong></li>

      <li>Only tag information on the title itself&#8202;&mdash;&#8202;<strong>not the individual release</strong>. Tags such as "<strong class="important_text">mkv</strong>", "<strong class="important_text">windows</strong>", "<strong class="important_text">scan</strong>", "<strong class="important_text">from.dlsite</strong>", etc. are strictly forbidden. Remember that these tags will be used for other versions of the same title.</li>

      <li>Derivative works may be tagged with the name of the parent series or characters within the work. For example, tags such as "<strong class="important_text_alt">touhou</strong>" or "<strong class="important_text_alt">iori.minase</strong>" may be acceptable if they are being used on derivative (parody) works. These kinds of tags should NOT be used if the series is primarily erotic in nature. Characters should be tagged with the form "<strong class="important_text_alt">surname.firstname</strong>" if applicable.</li>

      <li><strong>Tags should reflect significant aspects of a torrent.</strong> Don't tag something with "<strong class="important_text">blowjob</strong>" if there's only 30 seconds of dick-sucking. However, certain tags may be acceptable, such as "<strong class="important_text_alt">stockings</strong>", even if the torrent in question isn't centered around that fetish. Be smart.</li>

      <li><strong>Certain tags are strongly encouraged for appropriate uploads:</strong> "<strong class="important_text_alt">3d</strong>", "<strong class="important_text_alt">anthology</strong>", "<strong class="important_text_alt">yuri</strong>", "<strong class="important_text_alt">yaoi</strong>". People search for these kinds of things specifically, so tagging them properly will get you more snatches.</li>

      <li>Tags for game genres such as "<strong class="important_text_alt">rpg</strong>", "<strong class="important_text_alt">visual.novel></strong>", or "<strong class="important_text_alt">nukige</strong>" are encouraged.</li>

      <li><strong>Certain tags are <strong class="important_text">required</strong> for appropriate uploads:</strong>"<strong class="important_text_alt">lolicon</strong>", "<strong class="important_text_alt">shotacon</strong>", "<strong class="important_text_alt">toddlercon</strong>". Failure to use these tags may result in punishment.</li>

      <li><strong>All uploads require a minimum of 5 tags.</strong></li>

      <li><strong>You should be able to build up a list of tags using only the official tags <?=($OnUpload ? 'to the left of the text box' : 'on <a href="upload.php">the torrent upload page</a>')?>. If you are in any doubt about whether or not a tag is acceptable, do not add it.</strong></li>
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
      <li>
        Many forums have their own set of rules. Make sure you read and take note of these rules before you attempt to post in one of these forums.
      </li>
      <li>
        Don't use all capital letters, excessive !!! (exclamation marks) or ??? (question marks) unless it's funny. Basically just don't type like a disgusting fucking normie.
      </li>
      <li>
        No lame referral schemes. This includes freeipods.com, freepsps.com, or any other similar scheme in which the poster gets personal gain from users clicking a link. This shit is the WORST and you WILL be permanently banned on the spot, no questions asked you douche.
      </li>
      <li>
        No asking for money for any reason whatsoever. We don't know or care about your friend who lost everything, or dying relative who wants to enjoy their last few moments alive by being given lots of money. The only ones allowed to shill around these parts are staff.
      </li>
      <li>
        Do not inappropriately advertise your uploads. In special cases, it is acceptable to mention new uploads in an approved thread, but be sure to carefully read the thread's rules before posting. It is also acceptable to discuss releases you have uploaded when conversing about the title itself. Blatant attempts to advertise your uploads outside of the appropriate forums or threads may result in a warning or being called a faggot.
      </li>
      <li>
        No posting requests in forums. There's a request link at the top of the page for a god damn reason.
      </li>
      <li>
        No flaming unless they <i>really</i> deserve it. Feel free to use offensive language, but don't be confrontational for the sake of confrontation.
      </li>
      <li>
        Don't point out or attack other members' share ratios. A higher ratio totally makes you better than other people, though.
      </li>
      <li>
        Asking stupid questions will probably result in you being made fun of. A stupid question is one that you could have found the answer to yourself with a little research, or one that you're asking in the wrong place. If you do the basic research suggested (i.e., read the rules/wiki) or search the forums and don't find the answer to your question, then go ahead and ask. Staff and First Line Support (FLS) are not here to hand-feed you the answers you could have found on your own with a little bit of effort. Apply yourself. Put <i>some</i> effort in.
      </li>
      <li>
        Be sure you read all the sticky threads in a forum before you post. Let's be honest, we both know you're not going to do this.
      </li>
      <li>
        Use descriptive and specific subject lines. This helps others decide whether your particular words of "wisdom" relate to a topic they care about.
      </li>
      <li>
        Don't post comments that don't add anything to the discussion. If you don't have anything valuable to say, don't say anything. When you're just cruising through a thread in a leisurely manner, it's not too annoying to read through a lot of "hear, hear"'s and "I agree"'s. But if you're actually trying to find information, it's a pain in the ass. So save those one-word responses for threads that have degenerated to the point where none but true aficionados are following them any more.
        <p>
          Or short: NO spamming, unless it's funny.
        </p>
      </li>
      <li>
        Refrain from quoting excessively. When quoting someone, use only the portion of the quote that is absolutely necessary. This includes quoting pictures! Don't quote the entire first post, either. I swear to god if I see you doing this it's over.
      </li>
      <li>
        Feel free to request and discuss cracks for games and software. Links are fine, but if they're found to be malicious, the fire's gonna be under <i>your</i> ass.
      </li>
      <li>
        Political or religious discussions are okay. These types of discussions lead to arguments and flaming users, which can be pretty funny to watch and gives staff a good excuse to ban people.
      </li>
      <li>
        Don't waste other people's bandwidth by posting images with a large file size.
      </li>
      <li>
        Be (a little) patient with newcomers. Once you have become an expert, it is easy to forget that you started out as a dumbass too.
      </li>
      <li>
        Requesting invites to other sites is probably fine unless you're being obnoxious about it. Invites may be formally <strong>offered</strong> in the invite forum, and nowhere else.
      </li>
      <li>
        No language other than English (and Japanese, when relevant) is permitted in the forums. If we can't understand it, we will assume you're calling our mothers whores.
      </li>
      <li>
        Mature and graphic content on the forums and IRC is acceptable. In fact, it's expected. This is a porn site. Pictures of spiderman are prohibited.
      </li>
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
      <li>Staff have the final decision. If a staff member says stop and you continue, expect to be called a faggot.</li>
      <li>Be respectful to IRC Operators and Administrators. These people are site staff who do it for FREE. They are here for the benefit of all and to aid in conflict resolution. They enjoy Hot Pockets.</li>
      <li>Do not link shock sites without a warning (unless it's funny). If in doubt, ask a staff member in <?=(BOT_HELP_CHAN)?> about it.</li>
      <li>Excessive swearing will not get you kicked; practice being a sailor.</li>
      <li>Do not leave Caps Lock enabled all the time. It gets annoying, and you will likely get yourself kicked.</li>
      <li>No arguing. You can't win an argument <s>over the Internet</s> because you're probably a dumbass, so you're just wasting your time trying.</li>
      <li>No opinions, especially related to race, religion, politics, etc are allowed. Failure to comply with a request to cease having an opinion WILL be considered a thoughtcrime and you WILL be reeducated.</li>
      <li>Flooding is irritating and will warrant you a kick if it isn't funny enough or if an admin is cranky. This includes, but is not limited to, automatic "now playing" scripts, pasting large amounts of text, and multiple consecutive lines with no relevance to the conversation at hand.</li>
      <li>Impersonation of other members&#8202;&mdash;&#8202;particularly staff members&#8202;&mdash;&#8202;will not go unpunished. If you are uncertain of a user's identity, check their vhost, and then continue to be uncertain of their identity.</li>
      <li>Spamming is <b>strictly</b> forbidden unless it's funny. This includes, but is not limited to, personal sites, online auctions, and cans of blended meat.</li>
      <li>Obsessive annoyance&#8202;&mdash;&#8202;both to other users and staff&#8202;&mdash;&#8202;will not be tolerated.</li>
      <li>Do not PM, DCC, or Query anyone you don't know or have never talked to without asking first; this applies specifically to staff.</li>
      <li>No language other than English is permitted in the official IRC channels. If you can't use a real language, just stay in your shithole country.</li>
      <li>The offering, selling, trading, and giving away of invites to this or any other site on our IRC network is <strong>strictly whatever</strong>.</li>
      <li>Bots are not permitted in official channels with the exception of <strong>#oppaitime-announce</strong> and <strong>#oppaitime-requests</strong>.</li>
      <li>Any bots you have on IRC should authenticate with Udon using your own username and IRC key</li>
      <li>Bots must identify themselves by setting the +B usermode on themselves.</li>
      <li><strong>Read the channel topic before asking questions.</strong></li>
    </ol>
<?
  }
}
