<?
//Include the header
View::show_header('Uploading Rules', 'rules');
?>
<!-- Uploading Rules -->
<div class="thin">
  <div class="header">
    <h2>Uploading Rules</h2>
  </div>
<!-- Uploading Rules Index Links -->
  <br />
  <form class="search_form" name="rules" onsubmit="return false" action="">
    <input type="text" id="search_string" value="Filter (empty to reset)" />
    <span id="Index">Example: The search term <strong>FLAC</strong> returns all rules containing <strong>FLAC</strong>. The search term <strong>FLAC+trump</strong> returns all rules containing both <strong>FLAC</strong> and <strong>trump</strong>.</span>
  </form>
  <br />
  <div class="before_rules">
    <div class="box pad" style="padding: 10px 10px 10px 20px;">
      <ul>
        <li id="Introk"><a href="#Intro"><strong>Introduction</strong></a></li>
        <li id="defk"><a href="#def"><strong>Definitions</strong></a></li>
        <li id="h1k"><a href="#h1">1. <strong>Uploading Rules</strong></a>
          <ul>
            <li id="h1.1k"><a href="#h1.1">1.1. <strong>General</strong></a></li>
            <li id="h1.2k"><a href="#h1.2">1.2. <strong>Formatting</strong></a></li>
            <li id="h1.3k"><a href="#h1.3">1.3. <strong>Specifically Banned</strong></a></li>
            <li id="h1.4k"><a href="#h1.4">1.4. <strong>Screenshots</strong></a></li>
          </ul>
        </li>
        <li id="h2k"><a href="#h2">2. <strong>Movies &amp; Anime</strong></a>
          <ul>
            <li id="h2.1k"><a href="#h2.1">2.1. <strong>General</strong></a></li>
            <li id="h2.2k"><a href="#h2.2">2.2. <strong>Duplicates &amp; Trumping</strong></a></li>
          </ul>
        </li>
        <li id="h3k"><a href="#h3">3. <strong>Manga &amp; Doujin</strong></a>
          <ul>
            <li id="h3.1k"><a href="#h3.1">3.1. <strong>General</strong></a></li>
            <li id="h3.2k"><a href="#h3.2">3.2. <strong>Duplicates &amp; Trumping</strong></a></li>
            <li id="h3.3k"><a href="#h3.3">3.3. <strong>Formatting</strong></a></li>
          </ul>
        </li>
        <li id="h4k"><a href="#h4">4. <strong>Games</strong></a>
          <ul>
            <li id="h4.1k"><a href="#h4.1">4.1. <strong>General</strong></a></li>
            <li id="h4.2k"><a href="#h4.2">4.2. <strong>Duplicates &amp; Trumping</strong></a></li>
          </ul>
        </li>
        <li id="h5k"><a href="#h5">5. <strong>Other</strong></a>
          <ul>
            <li id="h5.1k"><a href="#h5.1">5.1. <strong>General</strong></a></li>
            <li id="h5.2k"><a href="#h5.2">5.2. <strong>Duplicates &amp; Trumping</strong></a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
<!-- Actual Uploading Rules -->
  <div id="actual_rules">
    <div class="before_rules">
      <h4 id="Intro"><a href="#Introk"><strong>&uarr;</strong></a> Introduction</h4>
        <div class="box pad" style="padding: 10px 10px 10px 20px;">
          <p>The uploading rules below are overwhelmingly long and detailed for a reason. The length is necessary to explain the rules clearly and thoroughly. A summary of each rule is in <span style="font-weight: bold;">bold text</span> before the actual rule for easier reading. You may also find the corresponding rule sections in the <a href="#Index">Index</a>. The corresponding <a href="#">&uarr;</a> (move one level up) and <a href="#Index">rule section links</a> (move down into the document) help provide quick navigation.</p>
          <p>Before you upload anything, if you are still unsure of what a rule means, PLEASE ask your questions at any of the following points of site user support: <a href="staff.php">First-Line Support</a>, <a href="forums.php?action=viewforum&amp;forumid=8">the Help Forum</a>, or <a href="wiki.php?action=article&amp;name=IRC"><?=BOT_HELP_CHAN?> on IRC</a>. Send a <a href="staffpm.php">Staff PM</a> addressed to staff if other support has directed you to a moderator or if support has been unhelpful in your particular case. If you find any dead or broken links in the upload rules, send a <a href="staffpm.php">Staff PM</a> addressed to staff, and include in your message the upload rule number (e.g. <a href="#r2.4.3">2.4.3</a>) and preferably the correct link to replace the broken one.</p>
        </div>
      <h4 id="def"><a href="#defk"><strong>&uarr;</strong></a> Definitions</h4>
      <div class="box pad" style="padding: 10px 10px 10px 20px;">
        <p>There are many terms used in these rules that are generally poorly defined. For the sake of clarity, we will define those words as we mean them in this section.</p>
        <ul>
          <li><b>Torrent</b> - Broadly used as a noun to describe a .torrent file, the files associated with it, and any associated metadata indexed by the site. Used as a verb to describe the act of downloading or uploading data from or to the swarm.</li>
          <li><b>Swarm</b> - All peers associated with a given torrent.</li>
          <li><b>Peer</b> - A client that has announced to the tracker and is part of the swarm.</b>
          <li><b>Seed</b> - When used as a verb, describes the act of uploading torrent content to other peers. When used as a noun, describes a peer who has all of content associated with a torrent as is able to upload to peers. Sometimes referred to as a seeder.</li>
          <li><b>Leech</b> - When used as a verb, describes the act of downloading torrent content from another peer. When used as a noun, describes someone who is downloading or wants to download torrent content from another peer. Sometimes referred to as a leecher.</li>
          <li><b>Metadata</b> - The information we record here on the site for each torrent, such as title, encoding information, and tags.</li>
          <li><b>Pornography</b> - Content containing either nudity or sex acts.</li>
          <li><b>Sex Act</b> - Any action performed for the sexual pleasure of either the person performing or receiving the action. Intercourse, masturbation, etc..</li>
          <li><b>JAV</b> - Japanese Adult Video. Used only to describe published movies, and not things like amateur videos.</li>
          <li><b>Anime</b> - A style of Japanese animation. Hentai anime may be referred to as h-anime.</li>
          <li><b>Manga</b> - A style of Japanese comic books and graphic novels. Hentai manga may be referred to as h-manga.</li>
          <li><b>Doujinshi</b> - Self-published manga. May be shortened to "doujin", which technically describes any self-published work, but is generally understood as referring to manga.</li>
          <li><b>Tankoubon</b> - A manga book that is published as complete in and of itself. Used to refer to published stand-alone works and volumes in a larger manga series.</b></li>
          <li><b>Anthology</b> - A published manga book containing works from several authors.</li>
          <li><b>One-shot</b> - A self-contained manga work. Not part of any series. For our purposes, a work must be officially released by the creator on its own, not as part of an athology, to qualify as a one-shot.</li>
          <li><b>Eroge</b> - Short for "erotic game". Basically, pornographic games. Used exclusively to describe games of Japanese origin. Also referred to as h-games.</li>
          <li><b>Hentai</b> - A subgenre of anime, manga, and games characterized by being pornographic.</li>
        </ul>
      </div>
    </div>
    <h4 id="h1"><a href="#h1k"><strong>&uarr;</strong></a> <a href="#h1">1.</a> Uploading Rules</h4>
      <h5 id="h1.1"><a href="#h1.1k"><strong>&uarr;</strong></a> <a href="#h1.1">1.1.</a> General</h5>
        <div class="box pad" style="padding: 10px 10px 10px 20px;">
          <ul>
            <li id="r1.1.1"><a href="#h1.1"><strong>&uarr;</strong></a> <a href="#r1.1.1">1.1.1.</a>
              <strong>Only movies, anime, manga, and games are allowed on the site.</strong> <?=SITE_NAME?> is a porn site. The only expections to this rule live in the 'Other' category, which is explained in its own section.
            </li>
            <li id="r1.1.2"><a href="#h1.1"><strong>&uarr;</strong></a> <a href="#r1.1.2">1.1.2.</a>
              <strong>Uploads should be pornographic.</strong> Again, this is a porn site. If you're in doubt, ask yourself, "does this make me wanna whack my gack?" If the answer is "no", don't upload it.
            </li>
            <li id="r1.1.3"><a href="#h1.1"><strong>&uarr;</strong></a> <a href="#r1.1.3">1.1.3.</a>
              <strong>Uploads should be Japanese in origin.</strong> Do not upload Western content without express permission from staff.
            </li>
            <li id="r1.1.4"><a href="#h1.1"><strong>&uarr;</strong></a> <a href="#r1.1.4">1.1.4.</a>
              <strong>Duplicate torrents in any category are not allowed.</strong> There are some exceptions to this rule, which are outlined in their relevant sections below.
            </li>
            <li id="r1.1.5"><a href="#h1.1"><strong>&uarr;</strong></a> <a href="#r1.1.5">1.1.5.</a>
              <strong>Seed complete copies of your uploads.</strong> Do not upload a torrent unless you intend to seed until there are at least 1.0 distributed copies. Seeding past this minimum is strongly encouraged.
            </li>
            <li id="r1.1.6"><a href="#h1.1"><strong>&uarr;</strong></a> <a href="#r1.1.6">1.1.6.</a>
              <strong>No advertising or personal credits.</strong> Providing artist, studio, publisher, or retailer information is not considered advertising.
              <ul>
                <li id="r1.1.6.1"><a href="#h1.1.6"><strong>&uarr;</strong></a> <a href="#r1.1.6.1">1.1.6.1</a>
                  <strong>Do not advertise sites, groups, or persons in torrent contents (e.g., folder names, file names, or file tags).</strong>
                </li>
                <li id="r1.1.6.2"><a href="#h1.1.6"><strong>&uarr;</strong></a> <a href="#r1.1.6.2">1.1.6.2</a>
                  <strong>Do not advertise sites, groups, or persons in torrent descriptions.</strong> Exception: torrent source information (e.g. ripper, scene group, crack group, or original uploader credit) is allowed in torrent descriptions.
                </li>
              </ul>
            </li>
            <li id="r1.1.7"><a href="#h1.1"><strong>&uarr;</strong></a> <a href="#r1.1.7">1.1.7.</a>
              <strong>Archived files in uploads are not allowed in Movies and Anime.</strong> Manga, Games, and  "Other" uploads may be archived. Specific archival rules can be found in their respective sections.
            </li>
            <li id="r1.1.8"><a href="#h1.1"><strong>&uarr;</strong></a> <a href="#r1.1.8">1.1.8.</a>
              <strong>English and Japanese are the the languages of <?=SITE_NAME?>.</strong> Torrents with neither English nor Japanese audio or text are forbidden. Torrents with dual audio that contain other languages in addition to English and/or Japanese are acceptable, however.
            </li>
            <li id="r1.1.9"><a href="#h1.1"><strong>&uarr;</strong></a> <a href="#r1.1.9">1.1.9.</a>
              <strong>"Language" refers to the spoken language in a torrent.</strong> If they speak Japanese, use Japanese. If they speak English, use English. The exception is manga, where there is no spoken language. In this case, "Language" refers to the language of the text. Games are a bit more complicated, and are explained in their own section.
            </li>
            <li id="r1.1.10"><a href="#h1.1"><strong>&uarr;</strong></a> <a href="#r1.1.10">1.1.10.</a>
              <strong>Subtitles are assumed to be in English.</strong> If a torrent contains only Japanese or other non-English subtitles, do not tag it as having subtitles. As with languages, torrents with multiple subtitles are fine so long as they contain at least English subtitles.
            </li>
            <li id="r1.1.11"><a href="#h1.1"><strong>&uarr;</strong></a> <a href="r1.1.11">1.1.11.</a>
              <strong>Official English titles win.</strong> Translation can be hard. Some titles may have never been translated into English. We don't expect you to be a translator. If there's no official title, use the best English title you can find. If you find a torrent group with a poor English translation and you think you have a better one, you may replace it, so long as you're not replacing an official title. If you're unsure if your translation is better, ask a staff member for assistance, that way when they say yes and it's a shit translation, we can yell at them instead of you.
            </li>
            <li id="r1.1.12"><a href="#h1.1"><strong>&uarr;</strong></a> <a href="#r1.1.12">1.1.12.</a>
              <strong>Watermarked uploads are allowed</strong>, however they may be trumpable by non-watermarked uploads (see <a href="#r2.2.7">rule 2.2.7.</a>).
            </li>
            <li id="r1.1.13"><a href="#h1.1"><strong>&uarr;</strong></a> <a href="#r1.1.13">1.1.13.</a>
              <strong>Artist and idol names must be formatted with the surname first.</strong> Uehara Ai, not Ai Uehara. Exception: artists and idols whose names are explicitly formatted differently are fine.
            </li>
            <li id="r1.1.14"><a href="#h1.1"><strong>&uarr;</strong></a> <a href="#r1.1.14">1.1.14.</a>
              <strong>No protected archives.</strong> Archived releases must not be password protected.
            </li>
            <li id="r1.1.15"><a href="#h1.1"><strong>&uarr;</strong></a> <a href="#r1.1.15">1.1.15.</a>
              <strong>DO NOT PUT METADATA IN THE TITLE FIELD.</strong> You know what goes in the title field? The god damn title. Not the artist. Not the langauge. Not whether or not its censored. The fucking title. <strong>Metadata should go in the proper fields.</strong> Next person I see putting dumb shit in the title field, especially if that dumb shit is release-specific like language or resolution, I'm banning your dense ass on the spot.
            </li>
          </ul>
        </div>

      <h5 id="h1.2"><a href="#h1.2k"><strong>&uarr;</strong></a> <a href="#h1.2">1.2.</a> General Formatting</h5>
        <div class="box pad" style="padding: 10px 10px 10px 20px;">
          <ul>
            <li id="r1.2.1"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r1.2.1">1.2.1.</a>
              <strong>Releases must be in a directory that contains the files.</strong> They may not be archived. Exception: Single-file torrents may be uploaded on their own, without a directory.
            </li>
            <li id="r1.2.2"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r1.2.2">1.2.2.</a>
              <strong>Directories should have meaningful titles.</strong> E.g., "Title - Catalogue Number" or "Studio - Title". The minimum acceptable is "Title", although it is preferable to include more information. Uploads that do not follow this rule are trumpable.
            </li>
            <li id="r1.2.3"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r1.2.3">1.2.3.</a>
              <strong>Files within directories should have meaningful names.</strong> E.g., for uploads containing multiple parts or episodes, they should include the episode number. Torrents that do not follow this rule are trumpable. Exception: WEB releases may be uploaded with file names as-is.
            </li>
            <li id="r1.2.4"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r2.1.5">2.1.5.</a>
              <strong>Files should sort appropriately.</strong> If your upload contains multiple parts or episodes, they should sort according to the episode number. For example, "<samp>Episode Title - 01.mkv</samp>" and "<samp>Episode Name - 02.mkv</samp>" would sort out of order. Instead, use something like "<samp>01 - Episode Title.mkv</samp>" and "<samp>02 - Episode Name.mkv</samp>" to ensure they sort correctly. Torrents that do not follow this rule are trumpable. Exception: WEB releases may be uploaded with file name as-is.
            </li>
            <li id="r1.2.5"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r1.2.5">1.2.5.</a>
              <strong>Avoid creating unnecessary nested folders (such as an extra folder for the actual video) inside your properly named directory.</strong> A torrent with unnecessary nested folders is trumpable by a torrent with such folders removed. For single-disc videos, all files must be included in the main torrent folder. For multi-disc videos, the main torrent folder may include sub-folders that hold the file contents for each of the discs. Additional folders are unnecessary because they do nothing to improve the organization of the torrent. If you are uncertain about what to do for other cases, PM a staff member for guidance.
            </li>
            <li id="r1.2.6"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r1.2.6">1.2.6.</a>
              <strong>Torrents should never have [REQ] or [REQUEST] in the title or artist name.</strong> If you fill a request using the <a href="requests.php">Requests system</a>, everyone who voted for it will be automatically notified.
            </li>
            <li id="r1.2.7"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r1.2.7">1.2.7.</a> <strong>Torrent titles must accurately reflect the actual titles.</strong> Use proper capitalization when giving titles. Typing titless in all lowercase letters or all capital letters is unacceptable and makes the torrent trumpable. Exceptions: If the album uses special capitalization, then you may follow that convention.</li>
            <li id="r1.2.8"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r1.2.8">1.2.8.</a>
              <strong>The Artist field in the torrent name should contain only the artist name.</strong> Do not add additional information about the artist in the artist field unless the work credits the artist in that manner. It is recommended that you search existing torrents for the artist name so that you can be sure that you name the artist the exact same way. A torrent with a proper artist name will be grouped with the existing torrents for that artist on a common artist page, and thus will be easy to find. Capitalization problems will also make a torrent trumpable. Labeling the artist incorrectly prevents your torrent from being grouped with the other torrents for the same artist.
            </li>
            <li id="r1.2.9"><a href="#h1.2"><strong>&uarr;</strong></a> <a href="#r1.2.9">1.2.9.</a>
              <strong>Torrent contents should be clean.</strong> Torrents should contain <strong>only</strong> relevant files. Do <strong>not</strong> include files such as promotional images or videos, screenshots, thumbs, ripper or encoder information, or urls to other sites. The exceptions are WEB releases, which may be uploaded as-released and games, which may contain files such as READMEs.
            </li>
          </ul>
        </div>
      <h5 id="h1.3"><a href="#h1.3k"><strong>&uarr;</strong></a> <a href="#h1.3">1.3.</a> Specifically Banned</h5>
        <div class="box pad" style="padding: 10px 10px 10px 20px;">
          <ul>
            <li id="r1.3.1"><a href="#h1.3"><strong>&uarr;</strong></a> <a href="#r1.3.1">1.3.1.</a>
              <strong>Anything not specifically allowed below.</strong> If you have any doubts, ask before uploading.
            </li>
            <li id="r1.3.2"><a href="#h1.3"><strong>&uarr;</strong></a> <a href="#r1.3.2">1.3.2.</a>
              <strong>Collections of pictures.</strong> Collections of pictures that do not qualify as manga or art books may be uploaded under "Other" so long as they have been formally released by an artist as a collection. User-made collections of images are not allowed under any circumstance.
            </li>
            <li id="r1.3.3"><a href="#h1.3"><strong>&uarr;</strong></a> <a href="#r1.3.3">1.3.3.</a>
              <strong>Movie packs, site rips, etc. are strictly prohibited.</strong> They may not be uploaded under any circumstance.
            </li>
            <li id="r1.3.4"><a href="#h1.3"><strong>&uarr;</strong></a> <a href="#r1.3.4">1.3.4.</a>
              <strong>DRM-restricted files.</strong> Files must not be encrypted or be in a restricted format that impedes sharing. You are also highly encouraged to remove personal information from any non-DRM protected files.
            </li>
            <li id="r1.3.5"><a href="#h1.3"><strong>&uarr;</strong></a> <a href="#r1.3.5">1.3.5.</a>
              <strong>Protected archives.</strong> In the sections where archives are allowed, these archives must not be protected by passwords or any other method.
            </li>
            <li id="r1.3.6"><a href="#h1.3"><strong>&uarr;</strong></a> <a href="#r1.3.6">1.3.6.</a>
              <strong>Multi-part archives.</strong> No no no no no no no no no no no.
            </li>
            <li id="r1.3.7"><a href="#h1.3"><strong>&uarr;</strong></a> <a href="#r1.3.7">1.3.7.</a>
              <strong>Hentai Music Videos</strong>, or HMVs, are banned.
            </li>
            <li id="r1.3.8"><a href="#h1.3"><strong>&uarr;</strong></a> <a href="#r1.3.8">1.3.8.</a>
              <strong>For Christ's sake don't upload actual child pornography.</strong> It's ridiculous that I need to make this a rule.
            </li>
            <li id="r1.3.9"><a href="#h1.3"><strong>&uarr;</strong></a> <a href="#r1.3.9">1.3.9.</a>
              <strong>Gravure.</strong> It's not porn by the definitions above.
            </li>
            <li id="r1.3.10"><a href="#h1.3"><strong>&uarr;</strong></a> <a href="#r1.3.10">1.3.10.</a>
              <strong>Pictures of Spiderman.</strong> Just don't.
            </li>
          </ul>
        </div>
      <h5 id="h1.4"><a href="#h1.4k"><strong>&uarr;</strong></a> <a href="#h1.4">1.4.</a> Screenshots</h5>
        <div class="box pad" style="padding: 10px 10px 10px 20px;">
          <ul>
            <li id="r1.4.1"><a href="#h1.4"><strong>&uarr;</strong></a> <a href="#r1.4.1">1.4.1.</a>
              <strong>Up to 10 screenshots may be uploaded to a group.</strong> No more than that. You technically shouldn't be able to upload more than that anyway, but this is a rule just in case.
            <li id="r1.4.2"><a href="#h1.4"><strong>&uarr;</strong></a> <a href="#r1.4.2">1.4.2.</a>
              <strong>Images should be individual, full-resolution screenshots.</strong> Preview images consisting of several thumbnails are <strong>not</strong> allowed as screenshots.
            </li>
            <li id="r1.4.3"><a href="#h1.4"><strong>&uarr;</strong></a> <a href="#r1.4.3">1.4.3.</a>
              <strong>For manga uploads, pages of the book are acceptable as samples.</strong>
            </li>
            <li id="r1.4.4"><a href="#h1.4"><strong>&uarr;</strong></a> <a href="#r1.4.4">1.4.4.</a>
              <strong>Do not upload covers as screenshots.</strong> You're more then welcome to add alternative covers using the [Add alternative cover] link, but covers aren't screenshots.
            </li>
          </ul>
        </div>

    <h4 id="h2"><a href="#h2k"><strong>&uarr;</strong></a> <a href="#h2">2.</a> Movies (JAV) and Anime (Hentai)</h4>

      <h5 id="h2.1"><a href="#h2.1k"><strong>&uarr;</strong></a> <a href="#h2.1">2.1.</a> General</h5>
        <div class="box pad" style="padding: 10px 10px 10px 20px;">
          <ul>
            <li id="r2.1.1"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r2.1.1">2.1.1.</a> <strong>The only formats allowed for JAV and hentai are:</strong>
              <ul>
                <li><strong>Containers:</strong>
                  <ul>
                    <? foreach($Containers as $Container) {
                        ?><li><?=$Container?></li><?
                    } ?>
                  </ul>
                </li>
                <li><strong>Video Codecs:</strong>
                  <ul>
                    <? foreach($Codecs as $Codec) {
                        ?><li><?=$Codec?></li><?
                    } ?>
                  </ul>
                </li>
                <li><strong>Audio Codecs:</strong>
                  <ul>
                    <? foreach($AudioFormats as $Audio) {
                        ?><li><?=$Audio?></li><?
                    } ?>
                  </ul>
                </li>
              </ul>
            </li>
            <li id="r2.1.2"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r2.1.2">2.1.2.</a>
              <strong>The WEB media category is for digital downloads only.</strong> Digital downloads released only on the internet from internet sources cannot be given other media labels on the <a href="upload.php">upload page</a>. Scene releases with no source information must be labeled as WEB. If possible, indicate the source of your files (e.g., the specific web store) in the torrent description. You are responsible for determining whether the downloaded files conform to <?=SITE_NAME?>'s rules for quality.
            </li>
            <li id="r2.1.3"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r2.1.3">2.1.3.</a>
              <strong>'Language' refers to the spoken language of the video.</strong> Videos with English subtitles should not be tagged as being English language.
            </li>
            <li id="r2.1.4"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r2.1.4">2.1.4.</a>
              <strong>Subtitles are assumed to be English.</strong> If the video does not contain English subtitles, do not specify subtitles.
            </li>
            <li id="r2.1.5"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r2.1.5">2.1.5.</a>
              <strong>3DCG content is considered to be Anime.</strong> It should not be uploaded under Movies.
            </li>
          </ul>
        </div>
      <h5 id="h2.2"><a href="#h2.2k"><strong>&uarr;</strong></a> <a href="#h2.2">2.2.</a> Duplicates &amp; Trumping</h5>
        <div class="box pad" style="padding: 10px 10px 10px 20px;">
          <ul>
            <li id="r2.2.1"><a href="#h2.2"><strong>&uarr;</strong></a> <a href="#r2.2.1">2.2.1.</a> <strong>Upload an allowed format if it doesn't already exist on the site.</strong> If there is no existing torrent of the title in the format you've chosen, you can upload it, so long as it is an allowed format.</li>
            <li id="r2.2.2"><a href="#h2.2"><strong>&uarr;</strong></a> <a href="#r2.2.2">2.2.2.</a> <strong>Torrents that have the same codecs, formats, translation group, etc. are duplicates.</strong> If a torrent is already present on the site in the format you wanted to upload, you are not allowed to upload it - it's a duplicate (dupe). Exceptions: Different editions and source media do not count as dupes.</li>
            <li id="r2.2.3"><a href="#h2.2"><strong>&uarr;</strong></a> <a href="#r2.2.3">2.2.3.</a> <strong>Report all trumped and duplicated torrents.</strong> If you trump a torrent or notice a duplicate torrent, please use the report link (RP) to notify staff for removal of the old or duplicate torrent. If you are uploading a superior rip to the current one in the same format on the site, report the older torrent and include a link to your new torrent in the report. Your torrent may be deleted as a dupe if the older torrent is not reported. Note: Trump - This occurs when a new torrent is uploaded in a preferred quality (as specified by the rules below) and replaces the older version that exists on the site. Dupe - This occurs when a new torrent is uploaded in a format that is equal to the existing older version on the site. Because the two torrents cannot coexist, the most recent upload is considered a duplicate.</li>
            <li id="r2.2.4"><a href="#h2.2"><strong>&uarr;</strong></a> <a href="#r2.2.4">2.2.4.</a> <strong>Torrents that have been inactive (e.g., not seeded) for two weeks may be trumped by the identical torrent (e.g., reseeded) or by a brand new rip or encode (see <a href="wiki.php?action=article&name=torrentinactivity">this wiki</a> for the torrent inactivity rules) of the title.</strong> If you have the original torrent files for the inactive torrent, it is preferable to reseed those original files instead of uploading a new torrent. Uploading a replacement torrent should be done only when the files from the original torrent cannot be recovered or are unavailable.</li>
            <li id="r2.2.5"><a href="#h2.2"><strong>&uarr;</strong></a> <a href="#r2.2.5">2.2.5.</a> <strong>Higher-resolution non-HD releases trum non-HD releases with lower-resolutions.</strong> If two torrents have the same encoding settings, language, and translation group, then the non-HD upload with the highest resolution maybe trump lower resolutions. The effectively means that only one non-HD release may exist for a given encode at any one time. All HD resolutions (720p, 1080p, 4k, etc.) may co-exist. Note: upscales are <strong>NOT</strong> allowed.</li>
            <li id="r2.2.6"><a href="#h2.2"><strong>&uarr;</strong></a> <a href="#r2.2.6">2.2.6.</a> <strong>Updated translations by the same translation group trump older translations</strong>
            <li id="r2.2.7"><a href="#2.2"><strong>&uarr;</strong></a> <a href="#r2.2.7">2.2.7.</a> <strong>Non-water marked uploads trump watermarked upload</strong>, so long as they are otherwise dupes (identical encoding, translation, etc.).
          </ul>
        </div>
    <h4 id="h3"><a href="#h3k"><strong>&uarr;</strong></a> <a href="#h3">3.</a> Manga & Doujinshi</h4>
      <h5 id="h3.1"><a href="#h3.1k"><strong>&uarr;</strong></a> <a href="#h3.1">3.1.</a> General</h5>
        <div class="box pad" style="padding: 10px 10px 10px 20px;">
          <ul>
            <li id="r3.1.1"><a href="#h3.1"><strong>&uarr;</strong></a> <a href="#r3.1.1">3.1.1.</a>
              <strong>Tankoubons, Anthologies, and One-shots only.</strong> No chapter packs. Volumes of series are acceptable. If it hasn't been released on its own, don't upload it.
            </li>
            <li id="r3.1.2"><a href="#h3.1"><strong>&uarr;</strong></a> <a href="#r3.1.2">3.1.2.</a>
              <strong>Manga and doujinshi may be be uploaded ONLY in the following specified formats, according to descending preference:</strong>
              <ul>
                <li>A directory containing only the images themselves</li>
                <li>A 7zip archive (Perferably with the .cb7 extension)</li>
                <li>A zip archive (Preferably with the .cbz extension)</li>
                <li>A rar archive (Preferably with the .cbr extension)</li>
              </ul>
            </li>
            <li id="r3.1.3"><a href="#h3.1"><strong>&uarr;</strong></a> <a href="#r3.1.3">3.1.3.</a>
              <strong>Pages must be scanned cleanly and be of good quality.</strong> Scans of poor quality will be deleted, especially if the quality is so poor as to render the image difficult to read. Poorer quality scans may be acceptable for very old or rare comics with staff approval.
            </li>
            <li id="r3.1.4"><a href="#h3.1"><strong>&uarr;</strong></a> <a href="#r3.1.4">3.1.4.</a>
              <strong>'Language' refers to the primary language of the text</strong> If the dialogue is English, 'English' should be chosen as the language.
            </li>
          </ul>
        </div>

      <h5 id="h3.2"><a href="#h3.2k"><strong>&uarr;</strong></a> <a href="#h3.2">3.2.</a> Duplicates &amp; Trumping</h5>
        <div class="box pad" style="padding: 10px 10px 10px 20px;">
          <ul>
            <li id="r3.2.1"><a href="#h3.2"><strong>&uarr;</strong></a> <a href="#r3.2.1">3.2.1.</a> <strong>A dupe of a single manga is defined as two scans of the same book by the same scanner, translated by the same group, or identical web releases.</strong> The scanner may be an individual, a release group, or a scanning device. Exceptions: The following examples are NOT dupes:
              <ul>
                <li>Two copies of the same book by the same scanner, but one is a c2c copy and the other is a "no ads" copy</li>
                <li>Two scans of the same book by different scanners</li>
                <li>Two scans of the same book by the same scanner, when the copy you're uploading contains fixes</li>
                <li>Two releases of the same scan, but translated by different groups</li>
              </ul>
              Note: Both loose uploads and archives may co-exist. They are not considered dupes (see below).
            </li>
            <li id="r3.2.2"><a href="#h3.2"><strong>&uarr;</strong></a> <a href="#r3.2.2">3.2.2.</a> <strong>Archived releases (.cb7, .cbz, .cbr, etc.) are always allowed, with preference given to the earliest upload.</strong> In the event of a dupe occurring between archives, the earliest upload remains. E.g., if a .cbz exists on the site, and a .cb7 of the same release is uploaded, the .cb7 will be considered a dupe (despite being the preferred format) and will be deleted.</li>
            <li id="r3.2.3"><a href="#h3.2"><strong>&uarr;</strong></a> <a href="#r3.2.3">3.2.3.</a> <strong>Updated translations by the same translation group trump older translations</strong>
          </ul>
        </div>
      <h5 id="h3.3"><a href="#h3.3k"><strong>&uarr;</strong></a> <a href="#h3.3">3.3.</a> Formatting</h5>
        <div class="box pad" style="padding: 10px 10px 10px 20px;">
          <ul>
            <li id="r3.3.1"><a href="#h3.3"><strong>&uarr;</strong></a> <a href="#r3.3.1">3.3.1.</a> <strong>All manga uploads must have zero-padded numbers and may be archived in .7z (.cb7), .zip (.cbz) or .rar (.cbr) files.</strong> The contents of the archive or directory must be image files (either JPEG or PNG), which are named sequentially so that they display in the correct order by <a href="https://en.wikipedia.org/wiki/Comparison_of_image_viewers" target="_blank">comic reading software</a>. The page numbers and books must be zero-padded for this same reason. For example, this constitutes good numbering: <samp>file01.jpg</samp>, <samp>file02.jpg</samp>,... <samp>file30.jpg</samp>; and this constitutes bad numbering: <samp>file1.jpg</samp>, <samp>file2.jpg</samp>, <samp>file3.jpg</samp>,... <samp>file30.jpg</samp>.</li>
            <li id="r3.3.2"><a href="#h3.3"><strong>&uarr;</strong></a> <a href="#r3.3.2">3.3.2.</a> <strong>Archive and directory names must be informative.</strong> The name should include at least the manga's name (e.g., "Gay Weeaboo Porn Story") and the volume (if there's more than one volume for that manga). Including the cover year and scanner information (to differentiate between different scans of the same book) is strongly recommended.</li>
          </ul>
        </div>

    <h4 id="h4"><a href="#h4k"><strong>&uarr;</strong></a> <a href="#h4">4.</a> Games</h4>
      <h5 id="h4.1"><a href="#h4.1k"><strong>&uarr;</strong></a> <a href="#h4.1">4.1.</a> General</h5>
        <div class="box pad" style="padding: 10px 10px 10px 20px;">
          <ul>
            <li id="r4.1.1"><a href="#h4.1"><strong>&uarr;</strong></a> <a href="#r4.1.1">4.1.1.</a> <strong>The only formats allowed for games are:</strong>
              <ul>
                <li><strong>Platforms:</strong>
                  <ul>
<? foreach ($Platform as $Plat) { ?>
<li><?=$Plat?></li>
<? } ?>
                  </ul>
                </li>
                <li><strong>Containers:</strong>
                  <ul>
<? foreach ($ContainersGames as $Cont) { ?>
  <li><?=$Cont?></li>
<? } ?>
                  </ul>
                </li>
              </ul>
            </li>
            <li id="r4.1.1"><a href="#h4.1"><strong>&uarr;</strong></a> <a href="#r4.1.1">4.1.1.</a> <strong>Game releases can be either a torrent of a directory or a single archive.</strong></li>
            <li id="r4.1.2"><a href="#h4.1"><strong>&uarr;</strong></a> <a href="#r4.1.2">4.1.2.</a> <strong>If a game has an installer or comes as a disk image, it may NOT be uploaded as a pre-install.</strong></li>
            <li id="r4.1.3"><a href="#h4.1"><strong>&uarr;</strong></a> <a href="#r4.1.3">4.1.3.</a> <strong>Games may have included cracks, keygens, or other DRM bypass methods.</strong> Games with keygens, cracks, or patches that do not work or torrents missing clear installation instructions will be deleted if reported. Games including malicious applications posing as cracks will lead to the uploader being harshly punished. No exceptions.</li>
            <li id="r4.1.4"><a href="#h4.1"><strong>&uarr;</strong></a> <a href="#r4.1.4">4.1.4.</a> <strong>Games may have included English patches and may be pre-patched.</strong> Note: pre-patched uploads are NOT exempt from <a href="#r4.1.2">4.1.2.</a></li>
            <li id="r4.1.5"><a href="#h4.1"><strong>&uarr;</strong></a> <a href="#r4.1.5">4.1.5.</a> <strong>Games should include installation instructions if not loose executables.</strong> You should either have the instructions in the release description or have the instructions included in a README.</li>
            <li id="r4.1.6"><a href="#h4.1"><strong>&uarr;</strong></a> <a href="#r4.1.6">4.1.6.</a> <strong>The torrent title must have a descriptive name.</strong> The torrent title should at least include the name of the game. Optionally, you may include additional labels for operating system, Developer, Publisher, DLSite ID (if applicable), and method of circumvention (e.g., crack, patch, keygen, or serial).</li>
            <li id="r4.1.7"><a href="#h4.1"><strong>&uarr;</strong></a> <a href="#r4.1.7">4.1.7.</a> <strong>Game components such as plug-ins, add-ons, expansions, mods, and so forth may be uploaded in a collection if they correspond to a particular game.</strong> You may upload plug-ins, expansions, add-ons, mods, and other game components as collections provided they are compatible to a particular game and version.</li>
            <li id="r4.1.8"><a href="#h4.1"><strong>&uarr;</strong></a> <a href="#r4.1.8">4.1.8.</a> <strong>Collections of cracks, keygens or serials are not allowed.</strong> The crack, keygen, or serial for a game  must be in a torrent with its corresponding game. It cannot be uploaded separately from the game.</li>
            <li id="r4.1.9"><a href="#h4.1"><strong>&uarr;</strong></a> <a href="#r4.1.9">4.1.9.</a> <strong>Never post serial numbers in torrent descriptions.</strong> Serial numbers should be in a text file contained within the torrent. If a serial number is posted in the torrent description and not included as a text file in the torrent folder, the torrent will be removed when reported. No exceptions.</li>
            <li id="r4.1.10"><a href="#h4.1"><strong>&uarr;</strong></a> <a href="#r4.1.10">4.1.10.</a> <strong>All games must be complete.</strong> If a game consists of multiple CDs or DVDs, these should all be uploaded as one torrent and not as separate torrents. This also applies to scene uploads where multiple CDs or DVDs were released separately.</li>
            <li id="r4.1.11"><a href="#h4.1"><strong>&uarr;</strong></a> <a href="#r4.1.11">4.1.11.</a>
              <strong>'Language' refers to the primary language of the game.</strong> If the vocals are Japanese, but the dialog and UI is English, tag the game as English. Use your best judgement here.
            </li>
          </ul>
        </div>

      <h5 id="h4.2"><a href="#h4.2k"><strong>&uarr;</strong></a> <a href="#h4.2">4.2.</a> Duplicates &amp; Trumping</h5>
        <div class="box pad" style="padding: 10px 10px 10px 20px;">
          <ul>
            <li id="r4.2.1"><a href="#h4.2"><strong>&uarr;</strong></a> <a href="#r4.2.1">4.2.1.</a>
              <strong>Games having the same version number, platform, and release group are dupes.</strong> An application may have older versions than those already uploaded. Those are not dupes. Only identical versions are duplicates. Note: Not everyone has updated to the latest operating system. In such cases, older versions of applications may still be useful for a number of users.</li>
            <li id="r4.2.3"><a href="#h4.2"><strong>&uarr;</strong></a> <a href="#r4.2.3">4.2.3.</a> <strong>Different language editions of the same game and version are unique.</strong> Multi-language versions and single language versions of different languages are not considered dupes.</li>
            <li id="r4.2.4"><a href="#h4.2"><strong>&uarr;</strong></a> <a href="#r4.2.4">4.2.4.</a> <strong>Existing games can be trumped by other torrents with better install methods.</strong> Games with serial keys may be trumped by crack/patch versions or torrents with keygens. Once an games with either a crack/patch or keygen is uploaded to the site, no other identical games with a different installation method is allowed. Report the older torrent when you are trumping it with a torrent of the same game containing an improved method of installation.</li>
            <li id="r4.2.5"><a href="#h4.2"><strong>&uarr;</strong></a> <a href="#r4.2.5">4.2.5.</a> <strong>Games that do not or no longer work will be deleted when reported.</strong> If the install method for an application no longer works (e.g., the developer has coded a patch, the install method is no longer supported, etc.) the torrent will be removed. Games that flat-out do not work will be removed, and the uploader may be warned.</li>
            <li id="r4.3.6"><a href="#h4.2"><strong>&uarr;</strong></a> <a href="#r4.3.6">4.3.6.</a> <strong>Updated translations by the same translation group trump older translations</strong>
          </ul>
        </div>

    <h4 id="h5"><a href="#h5k"><strong>&uarr;</strong></a> <a href="#h5">5.</a> Other</h4>
      <h5 id="h5.1"><a href="#h5.1k"><strong>&uarr;</strong></a> <a href="#h5.1">5.1.</a> General</h5>
        <div class="box pad" style="padding: 10px 10px 10px 20px;">
          <ul>
            <li id="r5.1.1"><a href="#5.1"><strong>&uarr;</strong></a> <a href="#5.1.1">5.1.1.</a>
              <strong>Only uploads not belonging in other categories are allowed</strong> Do not upload movies, anime, manga, or games under 'Other'.
            </li>
            <li id="r5.1.2"><a href="#5.1"><strong>&uarr;</strong></a> <a href="#5.1.2">5.1.2.</a>
              <strong>Some examples of acceptable content are:</strong>
              <ul>
                <li>Erotic Audio</li>
                <li>Official Image Packs</li>
                <li>Sex Manuals</li>
              </ul>
            </li>
            <li id="r5.1.3"><a href="#5.1"><strong>&uarr;</strong></a> <a href="#5.1.3">5.1.3.</a>
              <strong>Non-pornographic uploads still aren't allowed</strong>
            </li>
          </ul>
        </div>

      <h5 id="h5.3"><a href="#h5.3k"><strong>&uarr;</strong></a> <a href="#h5.3">5.3.</a> Duplicates &amp; Trumping</h5>
        <div class="box pad" style="padding: 10px 10px 10px 20px;">
          <ul>
            <li id="r5.2.1"><a href="#5.2"><strong>&uarr;</strong></a> <a href="#5.2.1">5.2.1.</a>
              <strong>Trumps will be decided on a case-by-case basis.</strong> Generally, a torrent with sufficiently higher quality will be considered to trump a lower-quality one. If you are reporting a torrent as trumped, be sure to explain why you believe it should be trumped.
            </li>
          </ul>
        </div>
  </div>
<!-- END Other Sections -->
<? include('jump.php'); ?>
</div>
<?
View::show_footer();
?>
