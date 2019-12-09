<?
//Include the header
View::show_header('Uploading Rules', 'rules');
?>
<!-- Upload -->
<div class="thin">
  <div class="header">
    <h2>Upload</h2>
  </div>
<!-- Uploading Rules Index Links -->
  <br />
  <form class="search_form" name="rules" onsubmit="return false" action="">
    <input type="text" id="search_string" value="Filter (empty to reset)" />
    <span id="Index">Searching for <strong>upload</strong> returns all rules containing the term.
    Searching for <strong>upload+trump</strong> returns all rules containing both terms</span>
  </form>
  <br />
  <div class="before_rules">
    <div class="box pad" style="padding: 10px 10px 10px 20px;">
      <ul>
        <li id="Introk"><a href="#Intro"><strong>Introduction</strong></a></li>
        <li id="defk"><a href="#def"><strong>Definitions</strong></a></li>
        <li id="h1k"><a href="#h1">1. <strong>Content Rules</strong></a>
          <ul>
            <li id="h1.1k"><a href="#h1.1">1.1. <strong>General</strong></a></li>
            <li id="h1.2k"><a href="#h1.2">1.2. <strong>Formatting</strong></a></li>
            <li id="h1.3k"><a href="#h1.3">1.3. <strong>Specifically Banned</strong></a></li>
          </ul>
        </li>
        <li id="h2k"><a href="#h2">2. <strong>Site Rules</strong></a>
          <ul>
            <li id="h2.1k"><a href="#h2.1">2.1. <strong>Upload Form Walkthrough</strong></a></li>
            <li id="h2.2k"><a href="#h2.2">2.2. <strong>Duplicates and Trumping</strong></a></li>
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
        <p>
          <?= SITE_NAME ?> is the global DIYbio community's distrubuted data repository.
          The content includes richly annotated and searchable biological sequence and medical imaging data.
          It follows the example of private BitTorrent sites to
          <a href="https://www.cambridge.org/core/services/aop-cambridge-core/content/view/2F379FE0CB50DF502F0075119FD3E060/S1744137417000650a.pdf/institutional_solutions_to_freeriding_in_peertopeer_networks_a_case_study_of_online_pirate_communities.pdf" target="_blank">address the free-rider problem</a>
          without recourse to institutional funding.
        </p>

        <p>
          Please read this entire page carefully because it explains how the tracker organizes the content.
          Referring to this page often will help you search faster and upload smarter.
          I'll go line-by-line through <a href="upload.php">the upload form</a>.
        </p>

        <p>
          Thanks for taking an interest in this project and contributing to its success.
          Please note that <?= SITE_NAME ?> isn't a piracy site and only allows permissively licensed datasets.
        </p>
        </div>

      <h4 id="def"><a href="#defk"><strong>&uarr;</strong></a> Definitions</h4>
      <div class="box pad" style="padding: 10px 10px 10px 20px;">
        <ul>
          <li><b>Torrent</b> - Broadly used as a noun to describe a .torrent file, the files associated with it, and any associated metadata indexed by the site. Used as a verb to describe the act of downloading or uploading data from or to the swarm.</li>
          <li><b>Swarm</b> - All peers associated with a given torrent.</li>
          <li><b>Peer</b> - A client that has announced to the tracker and is part of the swarm.</b>
          <li><b>Seed</b> - When used as a verb, describes the act of uploading torrent content to other peers. When used as a noun, describes a peer who has all of content associated with a torrent as is able to upload to peers. Sometimes referred to as a seeder.</li>
          <li><b>Leech</b> - When used as a verb, describes the act of downloading torrent content from another peer. When used as a noun, describes someone who is downloading or wants to download torrent content from another peer. Sometimes referred to as a leecher.</li>
          <li><b>Metadata</b> - The information we record here on the site for each torrent, such as title, encoding information, and tags.</li>
          <li><b>Hentai</b> - A subgenre of anime, manga, and games characterized by being pornographic.</li>
        </ul>
      </div>
    </div>

    <h4 id="h1"><a href="#h1k"><strong>&uarr;</strong></a> <a href="#h1">1.</a> Content Rules</h4>
      <h5 id="h1.1"><a href="#h1.1k"><strong>&uarr;</strong></a> <a href="#h1.1">1.1.</a> General</h5>
        <div class="box pad" style="padding: 10px 10px 10px 20px;">
          <ul>
            <li id="r1.1.1"><a href="#h1.1"><strong>&uarr;</strong></a> <a href="#r1.1.1">1.1.1.</a>
              <strong>Only biological sequences and medical imaging data are allowed on the site.</strong>
              <?=SITE_NAME?> is an annotated repository.
              The only expections to this rule live in the Other category, which is explained in its own section.
            </li>

            <li id="r1.1.2"><a href="#h1.1"><strong>&uarr;</strong></a> <a href="#r1.1.2">1.1.2.</a>
              <strong>Duplicate torrents in any category are not allowed.</strong>
              There are some exceptions to this rule, which are outlined in their relevant sections below.
            </li>

            <li id="r1.1.3"><a href="#h1.1"><strong>&uarr;</strong></a> <a href="#r1.1.3">1.1.3.</a>
              <strong>Seed complete copies of your uploads.</strong>
              Do not upload a torrent unless you intend to seed until there are at least 1.0 distributed copies. Seeding past this minimum is strongly encouraged.
            </li>

            <li id="r1.1.4"><a href="#h1.1"><strong>&uarr;</strong></a> <a href="#r1.1.4">1.1.4.</a>
              <strong>No advertising or personal credits.</strong>
              Providing author, lab, or other information is not considered advertising.
              <ul>

                <li id="r1.1.4.1"><a href="#h1.1.4"><strong>&uarr;</strong></a> <a href="#r1.1.4.1">1.1.4.1</a>
                  <strong>Do not advertise sites, groups, or persons in torrent contents (e.g., folder names, file names, or file tags).</strong>
                </li>

                <li id="r1.1.4.2"><a href="#h1.1.4"><strong>&uarr;</strong></a> <a href="#r1.1.4.2">1.1.4.2</a>
                  <strong>Do not advertise sites, groups, or persons in torrent descriptions.</strong>
                </li>
              </ul>
            </li>

            <li id="r1.1.5"><a href="#h1.1"><strong>&uarr;</strong></a> <a href="#r1.1.5">1.1.5.</a>
              <strong>Archived files in uploads under 5 GiB are not allowed.</strong>
              Specific archival rules can be found in their respective sections.
            </li>

            <li id="r1.1.6"><a href="#h1.1"><strong>&uarr;</strong></a> <a href="#r1.1.6">1.1.6.</a>
              <strong>English is the language of <?=SITE_NAME?>.</strong>
              The data folder structure should correspond to its English labels on <?= SITE_NAME ?> whenever possible.
            </li>

            <li id="r1.1.7"><a href="#h1.1"><strong>&uarr;</strong></a> <a href="#r1.1.7">1.1.7.</a>
              <strong>Watermarked uploads are allowed,</strong>
              however they may be trumpable by non-watermarked uploads (see <a href="#r2.2.5">rule 2.2.5.</a>).
            </li>

            <li id="r1.1.8"><a href="#h1.1"><strong>&uarr;</strong></a> <a href="#r1.1.8">1.1.8.</a>
              <strong>No protected archives.</strong>
              Archived releases must not be password protected.
            </li>

            <li id="r1.1.9"><a href="#h1.1"><strong>&uarr;</strong></a> <a href="#r1.1.9">1.1.9.</a>
              <strong>DO NOT PUT METADATA IN THE TITLE FIELD.</strong>
              Metadata should go in the proper fields.
            </li>
          </ul>
        </div>

      <h5 id="h1.2"><a href="#h1.2k"><strong>&uarr;</strong></a> <a href="#h1.2">1.2.</a> Formatting</h5>
        <div class="box pad" style="padding: 10px 10px 10px 20px;">
          <ul>
            <li id="r1.2.1"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r1.2.1">1.2.1.</a>
              <strong>Torrents must be in a directory that contains the files</strong>
              if there are multiple files.
              Single-file torrents may be uploaded on their own, without a directory.
            </li>

            <li id="r1.2.2"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r1.2.2">1.2.2.</a>
              <strong>Directories should have meaningful titles.</strong>
              "Title - Accession Number" or "Lab - Title."
              The minimum acceptable is "Title," although it is preferable to include more information.
              Uploads that do not follow this rule are trumpable.
            </li>

            <li id="r1.2.3"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r1.2.3">1.2.3.</a>
              <strong>Files within directories should have meaningful names.</strong>
              For uploads containing multiple parts, they should include the part number.
              Torrents that do not follow this rule are trumpable.
            </li>

            <li id="r1.2.4"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r2.1.4">2.1.4.</a>
              <strong>Files should sort appropriately.</strong>
              If your upload contains multiple parts, they should sort in the file manager according to the part number (use leading zeroes).
              Torrents that do not follow this rule are trumpable.
            </li>

            <li id="r1.2.5"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r1.2.5">1.2.5.</a>
              <strong>Avoid creating unnecessary nested folders inside your properly named directory.</strong>
              A torrent with unnecessary nested folders is trumpable by a torrent with such folders removed.
              For single-part torrents, all files must be included in the main torrent folder.
              For multi-part torrents, the main torrent folder may include sub-folders that hold the file contents for each of the parts.
            </li>

            <li id="r1.2.6"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r1.2.6">1.2.6.</a>
              <strong>Torrents should never have [Req] or [Request] in the title or artist name.</strong>
              If you fill a request using the <a href="requests.php">requests system</a>, everyone who voted for it will be automatically notified.
            </li>

            <li id="r1.2.7"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r1.2.7">1.2.7.</a>
            <strong>Torrent titles must accurately reflect the actual titles.</strong>
            Use proper capitalization when giving titles.
            Typing titles in all lowercase letters or all capital letters is unacceptable and makes the torrent trumpable.
            Natural sentence language is best for long titles.
          </li>

            <li id="r1.2.8"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r1.2.8">1.2.8.</a>
              <strong>The Author field in the torrent name should contain only the author name.</strong>
              Do not add additional information about the author in the author field unless the work credits the author in that manner.
              It is recommended that you search existing torrents for the author name so that you can be sure that you name the author the exact same way.
              A torrent with a proper artist name will be grouped with the existing torrents for that artist on a common artist page, and thus will be easy to find.
              Capitalization problems will also make a torrent trumpable.
              Labeling the author incorrectly prevents your torrent from being grouped with the other torrents for the same author.
            </li>

            <li id="r1.2.9"><a href="#h1.2"><strong>&uarr;</strong></a> <a href="#r1.2.9">1.2.9.</a>
              <strong>Torrent contents should be clean.</strong>
              Torrents should contain <strong>only</strong> relevant files.
              <strong>Do</strong> include files such as pictures and videos, source code, etc., as long as it belongs to the experimental source of the data or any publications that cite it.
              The exceptions are web releases, which may be uploaded as-released and may contain nfo files and readmes.
              <strong>Don't</strong> put these kinds of files, or anything likely to change independently of the data, in new torrents.
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
              <strong>Collections of audio files.</strong>
              <?= SITE_NAME ?> doesn't yet support collections of audio files, e.g., non-visual ultrasound data.
            </li>

            <li id="r1.3.3"><a href="#h1.3"><strong>&uarr;</strong></a> <a href="#r1.3.3">1.3.3.</a>
              <strong>Torrent packs, database rips, etc., are strictly prohibited.</strong>
              They may not be uploaded under any circumstance, e.g., Wikipedia English Official Offline Edition 2014-07-07.
              Torrent "packs" containing mixed samples are allowed if under 10 GiB and corresponding to one finished experiment.
            </li>

            <li id="r1.3.4"><a href="#h1.3"><strong>&uarr;</strong></a> <a href="#r1.3.4">1.3.4.</a>
              <strong>DRM-restricted files.</strong>
              Files must not be encrypted or be in a restricted format that impedes sharing.
              You are also highly encouraged to remove personal information from any non-DRM protected files.
            </li>

            <li id="r1.3.5"><a href="#h1.3"><strong>&uarr;</strong></a> <a href="#r1.3.5">1.3.5.</a>
              <strong>Multi-part archives</strong>
              for torrents under 10 GiB.
            </li>
          </ul>
        </div>

    <h4 id="h2"><a href="#h2k"><strong>&uarr;</strong></a> <a href="#h2">2.</a> Site Rules</h4>
      <h5 id="h2.1"><a href="#h2.1k"><strong>&uarr;</strong></a> <a href="#h2.1">2.1.</a> Upload Form Walkthrough</h5>
        <div class="box pad" style="padding: 10px 10px 10px 20px;">
          <ul>
            <li id="r2.1.1"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r2.1.1">2.1.1.</a>
              <strong>Torrent File.</strong>
              Add the announce URL to the tracker list (the only item) and click the checkbox marked private.
</li>

            <li id="r2.1.2"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r2.1.2">2.1.2.</a>
              <strong>Type.</strong>
              The categories loosely follow the central dogma.
              It depends on what alphabet the sequence uses, e.g., ACGT vs. ACGU.
              All medical imaging data goes in the Imaging category.
              Plasmids and things that don't quite fit go in the Other category.
              Only uploads not belonging in other categories are allowed there.
            </li>

            <li id="r2.1.3"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r2.1.3">2.1.3.</a>
              <strong>Accession Number.</strong>
              For nucleotide, amino acid sequences or whatever number the source repository uses.
              RefSeq and UniProt integration is in development.
              You're encouraged to add an accession number whenever the <?= SITE_NAME ?> and repo data hashes match.
            </li>

            <li id="r2.1.4"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r2.1.4">2.1.4.</a>
              <strong>Torrent Title.</strong>
              A short description of the torrent contents.
              It doesn't need to match the folders but it should tell you what the data <em>is</em> at a glance.
            </li>

            <li id="r2.1.5"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r2.1.5">2.1.5.</a>
              <strong>Organism.</strong>
              The subject of study's binomial name.
              Multiple organisms and FASTA header parser are in development.
            </li>

            <li id="r2.1.6"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r2.1.6">2.1.6.</a>
              <strong>Strain/Variety.</strong>
              The strain's name if known.
            </li>

            <li id="r2.1.7"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r2.1.7">2.1.7.</a>
              <strong>Authors(s).</strong>
              The upload form supports multiple authors per torrent.
              Please limit yourself to the top four authors on the paper for the release.
              Do an author search before uploading to get their names right.
              ORCiD integration is in development.
            </li>

            <li id="r2.1.8"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r2.1.8">2.1.8.</a>
              <strong>Department/Lab.</strong>
              The lab that did the experiments or the last author's home lab.
              Please use "Unaffiliated" for anonymous or unknown labs.
            </li>

            <li id="r2.1.9"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r2.1.9">2.1.9.</a>
              <strong>Location.</strong>
              The lab's physical location as City, (State || Country) Postal Code.
              For example, Berkeley, CA 94720 or Berlin, Germany 10117.
              Please use "Unknown" if needed.
            </li>

            <li id="r2.1.10"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r2.1.10">2.1.10.</a>
              <strong>Year.</strong>
              The year the data was first published.
              The publication that announced the data.
            </li>

            <li id="r2.1.11"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r2.1.11">2.1.11.</a>
              <strong>Platform.</strong>
              The class of technology the data comes from.
              What sequencing or imaging technique is it the output of?
              <a href="forums.php?action=viewforum&forumid=<?= SUGGESTIONS_FORUM_ID ?>">Please post in the suggestions forum</a>
              if you'd like to request a new platform.
              Note that the platforms change for the Imaging category.
            </li>

            <li id="r2.1.12"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r2.1.12">2.1.12.</a>
              <strong>Format.</strong>
              The file format of the data itself.
              What programs do you need to work with the data?
              <a href="forums.php?action=viewforum&forumid=<?= SUGGESTIONS_FORUM_ID ?>">Please post in the suggestions forum</a>
              if you'd like to request a new format.
              Note that the formats change for the Imaging category.
            </li>

            <li id="r2.1.13"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r2.1.13">2.1.13.</a>
              <strong>Assembly Level.</strong>
              The resolution of the data itself.
              How much information about the organism does it represent?
              Please use the Other selection if you'd like to enter a resolution.
              20 character limit.
            </li>

            <li id="r2.1.14"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r2.1.14">2.1.14.</a>
              <strong>License.</strong>
              <?= SITE_NAME ?> only allows permissive licenses.
              If your data is original, please consider licensing it under the available options.
              The "Unknown" option is for compatibility with existing web releases.
            </li>

            <li id="r2.1.15"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r2.1.15">2.1.15.</a>
              <strong>Tags.</strong>
              Please select at least five appropriate tags.
              Don't use irrelevant tags, and consider making new tags a last resort.
              <a href="forums.php?action=viewforum&forumid=<?= SUGGESTIONS_FORUM_ID ?>">Please post in the suggestions forum</a>
              if you'd like to request a new official tag.
            </li>

            <li id="r2.1.16"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r2.1.16">2.1.16.</a>
              <strong>Picture.</strong>
              Please upload a meaningful picture, especially if you plan to add the torrent to a collection.
              A photo of the specimen sequenced or a representative photo of the organism; a sample from an imaging dataset;
              a screenshot of a useful table from the publication; or another similarly informative picture.
            </li>

            <li id="r2.1.17"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r2.1.17">2.1.17.</a>
              <strong>Publications.</strong>
              DOI numbers should be well-formed, one per line.
              The upload system currently discards malformed DOI numbers instead of extracting them from arbitrary strings.
              If your research is a URI, please use the Torrent Group Description field.
              10 publication limit.
            </li>

            <li id="r2.1.18"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r2.1.18">2.1.18.</a>
              <strong>Torrent Group Description.</strong>
              General info about the object of study's function or significance.
              Please limit the contents of this field to the object of study.
            </li>

            <li id="r2.1.19"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r2.1.19">2.1.19.</a>
              <strong>Torrent Description.</strong>
              Specific info about the protocols and equipment relevant to <em>this</em> data.
              Please discuss the technical details of getting the data in this torrent-specific field.
            </li>

            <li id="r2.1.20"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r2.1.20">2.1.20.</a>
              <strong>Aligned Sequence.</strong>
              Does the data come with any metadata of an analytical nature, such as alignment data?
              If so, does the torrent folder contain the scripts used to generate the metadata?
            </li>

            <li id="r2.1.21"><a href="#h2.1"><strong>&uarr;</strong></a> <a href="#r2.1.21">2.1.21.</a>
              <strong>Upload Anonymously.</strong>
              You'll still get upload credit even if you hide your username from the torrent details.
              I believe it's still visible to sysops.
            </li>
          </ul>
        </div>

      <h5 id="h2.2"><a href="#h2.2k"><strong>&uarr;</strong></a> <a href="#h2.2">2.2.</a> Duplicates and Trumping</h5>
        <div class="box pad" style="padding: 10px 10px 10px 20px;">
          <ul>
            <li id="r2.2.1"><a href="#h2.2"><strong>&uarr;</strong></a> <a href="#r2.2.1">2.2.1.</a>
            <strong>Upload an allowed format if it doesn't already exist on the site.</strong>
            If there is no existing torrent of the title in the format you've chosen, you can upload it, so long as it is an allowed format.
          </li>
            
          <li id="r2.2.2"><a href="#h2.2"><strong>&uarr;</strong></a> <a href="#r2.2.2">2.2.2.</a>
            <strong>Torrents that have the same platform, formats, assembly level, etc., are duplicates.</strong>
            If a torrent is already present on the site in the format you wanted to upload, you are not allowed to upload it.
            Different editions and sources do not count as dupes.
          </li>
            
            <li id="r2.2.3"><a href="#h2.2"><strong>&uarr;</strong></a> <a href="#r2.2.3">2.2.3.</a>
            <strong>Report all trumped and duplicated torrents.</strong>
            If you trump a torrent or notice a duplicate torrent, please use the report link [RP] to notify staff for removal of the old or duplicate torrent.
            If you are uploading a superior version of the current one in the same format on the site, report the older torrent and include a link to your new torrent.
            Your torrent may be deleted as a dupe if the older torrent is not reported because the most recent upload is considered a duplicate.
          </li>
            
            <li id="r2.2.4"><a href="#h2.2"><strong>&uarr;</strong></a> <a href="#r2.2.4">2.2.4.</a>
            <strong>Torrents that have been inactive (unseeded) for two weeks may be trumped by the identical torrent (reuploaded).</strong>
            If you have the original torrent files for the inactive torrent, it is preferable to reseed those original files instead of uploading a new torrent.
            Uploading a replacement torrent should be done only when the files from the original torrent cannot be recovered or are unavailable.
          </li>
                        
            <li id="r2.2.5"><a href="#2.2"><strong>&uarr;</strong></a> <a href="#r2.2.5">2.2.5.</a>
            <strong>Uploads without watermarks trump those with them.</strong>
          </ul>
        </div>
  </div>
<!-- END Other Sections -->
<? include('jump.php'); ?>
</div>
<?
View::show_footer();
?>
