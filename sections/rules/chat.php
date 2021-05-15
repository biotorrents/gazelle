<?php
declare(strict_types=1);

# Formerly Rules::display_forum_rules()
# and Rules::display_irc_chat_rules()
$ENV = ENV::go();
View::show_header('Chat Rules');


/**
 * Designated debug area
 */

/*
echo '<pre>';
/*
# https://github.com/J7mbo/twitter-api-php
require_once('TwitterAPIExchange.php');

$settings = array(
  'oauth_access_token' => "YOUR_OAUTH_ACCESS_TOKEN",
  'oauth_access_token_secret' => "YOUR_OAUTH_ACCESS_TOKEN_SECRET",
  'consumer_key' => "YOUR_CONSUMER_KEY",
  'consumer_secret' => "YOUR_CONSUMER_SECRET"
);

$url = 'https://api.twitter.com/1.1/blocks/create.json';
$requestMethod = 'POST';

$postfields = array(
  'screen_name' => 'usernameToBlock',
  'skip_status' => '1'
);

$twitter = new TwitterAPIExchange($settings);
echo $twitter->buildOauth($url, $requestMethod)
    ->setPostfields($postfields)
    ->performRequest();
* /

#var_dump();
#var_dump();
#var_dump();

echo '</pre>';
*/
?>


<div class="box pad">
  <p>
    Anything not allowed on the forums is also not allowed on IRC and vice versa.
    They are separated for convenience only.
  </p>
</div>

<h2>
  Forum rules
</h2>

<div class="box pad rule_summary">
  <ul>
    <li>
      Let's treat the biology boards like how the Shroomery used to be:
      each thread a set of resourceful wisdom worth using permalinks to.
      It's okay if the boards are slow, that's why there are only a few of them.
    </li>

    <li>
      Please discuss site news in the corresponding Announcements thread instead of making a new General thread.
      Discussing science-related news in General is highly encouraged, but discussing political news is much less
      so.
      But don't self-censor, e.g., you can discuss the political and economic factors of the 2019-nCoV outbreak,
      but you can't start a thread about trade deals and hope to steer it toward biology.
      Thank you.
    </li>

    <li>
      No advertising, referrals, cryptocurrency pumps, or calls to action that involve using a financial instrument.
      You'll be banned on the spot.
      The exceptions: discussions about cryptocurrencies that derive their value from work performed on distributed
      science networks, i.e., Curecoin, FoldingCoin, and Gridcoin.
    </li>

    <li>
      All affiliate links must be labelled <code>(affiliate link)</code> and they must be related to the discussion,
      in a context where only you would personally benefit if someone used the link.
    </li>

    <li>
      Feel free to post announcements for your own projects, even and especially if they're commercial ones, in the
      General board.
      Limit all discussion of trading biomaterials, including bulk giveaways, to the Marketplace forum available to
      Power
      Users.
    </li>

    <li>
      Please be modest when talking about your uploads.
      It's unnecessary to announce your uploads because Gazelle logs everything
      (at least this installation's database is encrypted).
      If someone asks for help on his project and your upload fits the bill, go write a post!
    </li>

    <li>
      Use descriptive and specific subject lines.
      This helps others decide whether your particular words of "wisdom" relate to a topic they care about.
    </li>

    <li>
      Don't post comments that don't add anything to the discussion, such as "I agree" or "haha."
      Bottle the trained dopamine response to social media because comment reactions are an unlikely feature.
    </li>

    <li>
      Please refrain from quoting excessively.
      When quoting someone, use only the necessary parts of the quote.
      Avoid quoting more than 3 levels deep.
    </li>

    <li>
      Don't post potentially malicious links without sufficient warning, or post pictures > 2 MiB.
      Please only speak English as stated in the upload rules.
    </li>
  </ul>
</div>

<h2>
  IRC rules
</h2>

<div class="box pad rule_summary">
  <ul>
    <li>
      <?= $ENV->SITE_NAME ?>'s Slack channels are just are
      another quiet hangout you can stack your app with so you look cool at conferences.
    </li>

    <li>
      Please use
      <code>#general</code> for the usual chit-chat,
      <code>#development</code> for questions about the Gazelle software, and
      <code>#support</code> to get help with your account.
    </li>

    <li>
      Don't send mass alerts with
      <code>@channel</code>,
      <code>@everyone</code>, or
      <code>@here</code>.
      It's obnoxious and you should handle anything genuinely important on the boards.
    </li>

    <li>
      Flooding is irritating and you'll get kicked for it.
      This includes "now playing" scripts, large amounts of irrelevant text such as lorem ipsum, and unfunny non
      sequiturs.
    </li>

    <li>
      Impersonating other members, particularly staff members, will not go unpunished.
      Please remember that the Slack channels are publicly accessible.
    </li>

    <li>
      Please use the threaded conversations feature in Slack and avoid replying to threads with new messages or
      crossposting
      replies to the main channel.
    </li>

    <li>
      Announce and bot channels are in development, as standard IRC instead of Slack for obvious reasons.
      Any IRC bots you have must authenticate with your own username and IRC key, and set the <code>+B</code>
      usermode on
      themselves.
    </li>
  </ul>
</div>

<?php include('jump.php'); ?>
</div>
<?php View::show_footer();
