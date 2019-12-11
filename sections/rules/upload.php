<?php
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
    <span id="Index">Searching for <strong>upload</strong> returns all rules containing that term.
      Searching for <strong>upload+trump</strong> returns all rules containing both terms</span>
  </form>
  <br />
  <div class="before_rules">
    <div class="box pad" style="padding: 10px 10px 10px 20px;">
      <ul>
        <li id="Introk"><a href="#Intro"><strong>Introduction</strong></a></li>
        <li id="defk"><a href="#def"><strong>Definitions</strong></a></li>
        <li id="hUk"><a href="#hU"><strong>Upload Rules</strong></a>
          <ul>
            <li id="h1.1k"><a href="#h1.1">1.1 <strong>General and Formatting</strong></a></li>
            <li id="h2.2k"><a href="#h1.2">1.2 <strong>Duplicates and Trumping</strong></a></li>
            <li id="h2.1k"><a href="#h1.3">1.3 <strong>Upload Form Walkthrough</strong></a></li>
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
          <?= SITE_NAME ?> is the global DIYbio community's
          distributed data repository.
          The content includes richly annotated and searchable biological sequence and medical imaging data.
          It follows the example of private BitTorrent sites to
          <a href="https://www.cambridge.org/core/services/aop-cambridge-core/content/view/2F379FE0CB50DF502F0075119FD3E060/S1744137417000650a.pdf/institutional_solutions_to_freeriding_in_peertopeer_networks_a_case_study_of_online_pirate_communities.pdf"
            target="_blank">address the free-rider problem</a>
          without recourse to institutional funding.
        </p>

        <p>
          Please read this entire page carefully because it explains how the tracker organizes the content.
          Referring to this page often will help you search faster and upload smarter.
          I'll also go line-by-line through <a href="upload.php">the upload form</a>.
        </p>

        <p>
          Thanks for taking an interest in this project and contributing to its success.
          Please note that <?= SITE_NAME ?> isn't a pirate website.
        </p>
      </div>

      <h4 id="def"><a href="#defk"><strong>&uarr;</strong></a> Definitions</h4>
      <div class="box pad" style="padding: 10px 10px 10px 20px;">
        <ul>
          <li><b>Torrent.</b> Broadly used as a noun to describe a <code>.torrent</code> file, the files associated with
            it, and any associated metadata indexed by the site. Used as a verb to describe the act of downloading or
            uploading data from or to the swarm.</li>
          <li><b>Swarm.</b> All peers associated with a given torrent.</li>
          <li><b>Peer.</b> A client that has announced to the tracker and is part of the swarm.</b>
          <li><b>Seed.</b> When used as a verb, describes the act of uploading torrent content to other peers. When used
            as a noun, describes a peer who has all of content associated with a torrent as is able to upload to peers.
            Sometimes referred to as a seeder.</li>
          <li><b>Leech.</b> When used as a verb, describes the act of downloading torrent content from another peer.
            When used as a noun, describes someone who is downloading or wants to download torrent content from another
            peer. Sometimes referred to as a leecher.</li>
          <li><b>Metadata.</b> The information we record here on the site for each torrent, such as title, encoding
            information, and tags.</li>
          <li><b>Hentai.</b> A subgenre of anime, manga, and games characterized by being pornographic.</li>
        </ul>
      </div>
    </div>

    <h4 id="hU"><a href="#hUk"><strong>&uarr;</strong></a> <a href="#hU"></a> Upload Rules</h4>
    <h5 id="h1"><a href="#h1k"><strong>&uarr;</strong></a> <a href="#h1">1</a> General and Formatting</h5>
    <div class="box pad" style="padding: 10px 10px 10px 20px;">
      <ul>
        <li id="r1.1"><a href="#h1"><strong>&uarr;</strong></a> <a href="#r1.1">1.1</a>
          <strong>Biology Only.</strong>
          <?=SITE_NAME?> is an annotated repository of biology data
          and a bioinformatics learning community.
          Gazelle in its current state requires lots of hardcoded metadata.
          I can help you adapt the design, e.g., for physics or astronomy data.
          A generalized science tracker is in development.
        </li>

        <li id="r1.2"><a href="#h1"><strong>&uarr;</strong></a> <a href="#r1.2">1.2</a>
          <strong>Seed Forever.</strong>
          Private torrent trackers succeed when they offer quality niche content and a comfy interface.
          This isn't an NCBI data dump but a library of annotated info hashes that tomorrow's networks can ingest.
          Do not upload a torrent unless you intend to seed until there are at least 3 copies.
          Three is a good minimum swarm size.
        </li>

        <li id="r1.3"><a href="#h1"><strong>&uarr;</strong></a> <a href="#r1.3">1.3</a>
          <strong>No Advertising.</strong>
          Please don't "tag" torrents, include ASCII art, or make your torrents look like they came from the Pirate Bay.
          These kinds of additions are allowed if they serve a relevant purpose.
          Enclosing a GPG-signed hash of your data isn't a bad idea at all.
          <ul>
          </ul>
        </li>

        <li id="r1.4"><a href="#h1"><strong>&uarr;</strong></a> <a href="#r1.4">1.4</a>
          <strong>Speak English.</strong>
          <?=SITE_NAME?> is an Anglophone site.
          Everything but private messages, and especially torrents and the forums, should be in English.
        </li>

        <li id="r1.5"><a href="#h1"><strong>&uarr;</strong></a> <a href="#r1.5">1.5</a>
          <strong>Good Data.</strong>
          Strive to release complete collections of the highest fidelity data in the most sensible format.
          Sometimes I wonder whether certain kinds of people are drawn to private torrent trackers, or if the site
          design encourages otherwise disinterested people to do well.
        </li>

        <li id="r1.6"><a href="#h1"><strong>&uarr;</strong></a> <a href="#r1.6">1.6</a>
          <strong>No DRM.</strong>
          Archived releases must not be password protected.
          DRM of any kind isn't allowed.
        </li>

        <hr style="margin: 2em auto; opacity: 0.3; width: 50%;" />

        <li id="r1.7"><a href="#h1"><strong>&uarr;</strong></a> <a href="#r1.7">1.7</a>
          <strong>Folder Structure.</strong>
          Each torrent should be a single folder so we can manage them easier.
          Please avoid unnecessary nested folders inside your torrent.
          Use one of the examples below for your main folder.

          <ul>
            <li>One-Shot Project Name</li>
            <li>Torrent Title - Accession Number</li>
            <li>Department/Lab - Project Name</li>
            <li>After the Name - Extra comments as necessary</li>
          </ul>

          I also strongly recommend you compress only large files or long image series, and not simply compress the
          entire dataset.
          This makes it easier to partially seed large datasets, work with discrete parts of the data, and know what's
          on disk.
        </li>

        <li id="r1.8"><a href="#h1"><strong>&uarr;</strong></a> <a href="#r1.8">1.8</a>
          <strong>File Organization.</strong>
          Please either keep the original filenames from the processing service, or consistently use a legible naming
          scheme.
          Remove all .DS_Store, Thumbs.db, nfo files, and other junk files before making the torrent.
          It should be "clean."
          You're encouraged to keep Git repos, structured data reports, readmes, and other useful annotations.
          Files should sort appropriately: use leading zeroes.
        </li>

        <li id="r1.9"><a href="#h1"><strong>&uarr;</strong></a> <a href="#r1.9">1.9</a>
          <strong>Compression.</strong>
          "10 GiB or 10,000 files."
          Compression is required if your torrent is > 10 GiB or if it contains > 10,000 files.
          Otherwise, please compress text files if it reduces the torrent size by > 30%.
          Never compress torrents < 2 GiB or binary files < 10 GiB. Multipart archives are only allowed for torrents> 10
            GiB.
        </li>

        <li id="r1.10"><a href="#h1"><strong>&uarr;</strong></a> <a href="#r1.10">1.10</a>
          <strong>Metadata.</strong>
          Avoid matching folder names to <?=SITE_NAME?> metadata.
          The site design will change but the torrents are evergreen.
          When What.cd went down, it was possible to seed most of your old torrents at Redacted.ch because the info
          hashes matched.
          The <code>.torrent</code> points to cryptographically verified folders and files that are tracked for
          convenience.
          On the flipside, please add enough metadata so that people can pick it out of a list.
        </li>

        <li id="r1.11"><a href="#h1"><strong>&uarr;</strong></a> <a href="#r1.11">1.11</a>
          <strong>Supplemental Packs.</strong>
          I strongly recommend <a href="https://semver.org/" target="_blank">Semantic Versioning</a> for your original
          data.
          Supplemental packs may include a collection of documents, utilities, protocols, metadata, etc., specifically
          prepared for release.
          The collection should be a separate torrent if the collection constitutes a project in its own right.
          But if you have, e.g., 100 MiB of XML and no other metadata for 5 GB of photos, please include it in the main
          torrent.
        </li>
      </ul>
    </div>

    <h5 id="h2"><a href="#h2k"><strong>&uarr;</strong></a> <a href="#h2">2</a> Duplicates and Trumping</h5>
    <div class="box pad" style="padding: 10px 10px 10px 20px;">
      <ul>
        <li id="r2.1"><a href="#h2"><strong>&uarr;</strong></a> <a href="#r2.1">2.1</a>
          <strong>Multiple Formats Allowed.</strong>
          It's fine if there's an EMBL and a FASTA of the same data. If you need to convert a dataset for your analysis,
          please upload a quality conversion with supplemetal info.
          Remember that small, one-shot metadata are better included with the data, and collections of docs and utils
          are better separate from it.
          If only the header is different and it follows the "10 GiB or 10,000 files" rule, uncompressed torrents trump
          compressed ones.
          It should be easy for others with the same data to change a line and check out the new torrent.
        </li>

        <li id="r2.2"><a href="#h2"><strong>&uarr;</strong></a> <a href="#r2.2">2.2</a>
          <strong>SemVer Trumps.</strong>
          Versioned data can be trumped at the patch level.
          Major and minor releases can coexist.
          Please add a link in the old data's Torrent Group Description to the new data.
          Git repos can be trumped at the commit and patch levels.
          Then normal SemVer rules take effect for properly tagged releases.
        </li>

        <li id="r2.3"><a href="#h2"><strong>&uarr;</strong></a> <a href="#r2.3">2.3</a>
          <strong>Report Trumps and Dupes.</strong>
          If you trump a torrent or notice a duplicate torrent, please use the report link [RP] to notify staff to
          remove it.
          If you are uploading a superior version, e.g., without watermarks, report the older torrent and include a link
          to your new torrent.
          Your torrent may be deleted as a dupe if the older torrent is not reported.
        </li>

        <li id="r2.4"><a href="#h2"><strong>&uarr;</strong></a> <a href="#r2.4">2.4</a>
          <strong>Two Weeks Unseeded.</strong>
          If you have the original torrent files for the inactive torrent, reseed those original files instead of
          uploading a new torrent.
          Uploading a replacement torrent should be done only when the original files are unavailable.
        </li>

        <li id="r2.5"><a href="#h2"><strong>&uarr;</strong></a> <a href="#r2.5">2.5</a>
          <strong>Watermarks.</strong>
          Data without watermarks trumps watermarked data.
      </ul>
    </div>

    <h5 id="h3"><a href="#h3k"><strong>&uarr;</strong></a> <a href="#h3">3</a> Upload Form Walkthrough</h5>
    <div class="box pad" style="padding: 10px 10px 10px 20px;">
      <ul>
        <li id="r3.1"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.1">3.1</a>
          <strong>Torrent File.</strong>
          Add the announce URL as the only item on the tracker list and click the checkbox marked private.
          Please don't share <code>.torrent</code> files because they have your passkey embedded in them.
          This passkey lets the tracker know who's uploading and downloading, and leaking it will nuke your ratio.
          <br /><br />
        </li>

        <li id="r3.2"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.2">3.2</a>
          <strong>Type.</strong>
          The categories loosely follow the central dogma.
          It depends on what alphabet the sequence uses, e.g., ACGT vs. ACGU.
          All medical imaging data goes in the Imaging category.
          All extra documentation, custom scripts, etc., goes in Documents.
          Plasmids and things that don't quite fit go in Other.
          <br /><br />
        </li>

        <li id="r3.3"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.3">3.3</a>
          <strong>Accession Number.</strong>
          Please add accession numbers when the data source issued them.
          The number can be any format but it must correspond to the actual torrent data.
          Don't add accession numbers just because the metadata matches.
          RefSeq and UniProt integration is in development.
          <br /><br />
        </li>

        <li id="r3.4"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.4">3.4</a>
          <strong>Torrent Title.</strong>
          A short description of the torrent contents.
          It doesn't need to match the folders but it should tell you what the data is at a glance.
          Please avoid adding other metadata such as Strain, Platform, etc., with a dedicated field.
          <br /><br />
        </li>

        <li id="r3.5"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.5">3.5</a>
          <strong>Organism.</strong>
          The relevant organism's binomial name and optional subspecies.
          Please use <em>Genus species subspecies</em> and no terms such as var. and subsp.
          Multiple organisms and a FASTA/GenBank parser are both in development.
          <br /><br />
        </li>

        <li id="r3.6"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.6">3.6</a>
          <strong>Strain/Variety.</strong>
          The strain's name if known.
          This should correspond to a specific cell line or breed.
          Otherwise, please omit it.
          <br /><br />
        </li>

        <li id="r3.7"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.7">3.7</a>
          <strong>Authors(s).</strong>
          The Author field should contain only the author name.
          The upload form supports multiple authors.
          Do a search before uploading to get their names right.
          Consistent author naming makes browsing easier because it groups torrents on a common page.
          ORCiD integration is in development.
          <br /><br />
        </li>

        <li id="r3.8"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.8">3.8</a>
          <strong>Department/Lab.</strong>
          The lab that did the experiments or the last author's home lab.
          Please use "Unaffiliated" for anonymous or unknown labs.
          <br /><br />
        </li>

        <li id="r3.9"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.9">3.9</a>
          <strong>Location.</strong>
          The lab's physical location in one of the below formats.
          <ul>
            <li>{City}, {State} {Postal Code}</li>
            <li>{Postal Code} {City}, {Country}</li>
          </ul>
          For example, Berkeley, CA 94720 or 10117 Berlin, Germany.
          It's okay to use the American style if the foreign address uses the same format.
          Please use "Unknown" for anonymous or unknown labs.
          <br /><br />
        </li>

        <li id="r3.10"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.10">3.10</a>
          <strong>Year.</strong>
          The year the data was first published.
          The publication that announced the data.
          <br /><br />
        </li>

        <li id="r3.11"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.11">3.11</a>
          <strong>Platform.</strong>
          The class of technology the data comes from.
          What sequencing or imaging technique is it the output of?
          <a
            href="forums.php?action=viewforum&forumid=<?= SUGGESTIONS_FORUM_ID ?>">Please
            post in the suggestions forum</a>
          if you'd like to request a new platform.
          Note that the platforms change for the Imaging category.
          <br /><br />
        </li>

        <li id="r3.12"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.12">3.12</a>
          <strong>Format.</strong>
          The file format of the data.
          What programs do you need to work with the data?
          <a
            href="forums.php?action=viewforum&forumid=<?= SUGGESTIONS_FORUM_ID ?>">Please
            post in the suggestions forum</a>
          if you'd like to request a new format.
          Note that the formats change for the Imaging category.
          A file extension parser is in development.
          <br /><br />
        </li>

        <li id="r3.13"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.13">3.13</a>
          <strong>Assembly Level.</strong>
          The resolution of the data.
          How much information about the organism does it represent?
          The options correspond in higher conceptual language to:
          a single piece of information, structural information, especially deep or broad information, and an exhaustive
          source.
          Please use the Other option if you'd like to enter a resolution such as "420 subjects."
          <br /><br />
        </li>

        <li id="r3.14"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.14">3.14</a>
          <strong>License.</strong>
          <?= SITE_NAME ?> only allows permissive licenses.
          If your data is original, please consider licensing it under one of the available options.
          The "Unspecified" option is for compatibility with existing releases.
          <br /><br />
        </li>

        <li id="r3.15"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.15">3.15</a>
          <strong>Tags.</strong>
          Please select at least five appropriate tags.
          Don't use irrelevant tags, and consider making new tags as a last resort.
          <a
            href="forums.php?action=viewforum&forumid=<?= SUGGESTIONS_FORUM_ID ?>">Please
            post in the suggestions forum</a>
          if you'd like to request a new official tag.
          <br /><br />
        </li>

        <li id="r3.16"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.16">3.16</a>
          <strong>Picture.</strong>
          Please upload a meaningful picture, especially if you plan to add the torrent to a collection.
          A photo of the sequence sample or a representative photo of the organism; an example (preferably not a
          thumbnail collection) from an imaging dataset;
          a screenshot of a useful table from the publication; or another similarly informative picture.
          <br /><br />
        </li>

        <li id="r3.17"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.17">3.17</a>
          <strong>Publications.</strong>
          DOI numbers should be well-formed, one per line.
          The system currently discards malformed DOI numbers instead of extracting them from arbitrary strings.
          An auto-extract feature is in development.
          If your research is a URI, please use the Torrent Group Description field.
          <br /><br />
        </li>

        <li id="r3.18"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.18">3.18</a>
          <strong>Torrent Group Description.</strong>
          General info about the object of study's function or significance.
          This is the main body text on a torrent's page.
          Please limit the contents of this field to concise and interesting knowledge.
          <br /><br />
        </li>

        <li id="r3.19"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.19">3.19</a>
          <strong>Torrent Description.</strong>
          Specific info about the protocols and equipment relevant to <em>this</em> data.
          This text is hidden by default.
          It displays when you click the Torrent Title next to [ DL | ... | PL ].
          Please discuss materials and methods here.
          <br /><br />
        </li>

        <li id="r3.20"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.20">3.20</a>
          <strong>Aligned Sequence.</strong>
          Does the data come with any metadata of an analytical nature, such as alignment data (mandatory)?
          If so, does the torrent folder contain the scripts used to generate the metadata (optional)?
          <br /><br />
        </li>

        <li id="r3.21"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.21">3.21</a>
          <strong>Upload Anonymously.</strong>
          You'll still get upload credit even if you hide your username from the torrent details.
          I believe it's still visible to sysops.
        </li>
      </ul>

    </div>
    <!-- END Other Sections -->
    <?php include('jump.php'); ?>
  </div>
  <?php
View::show_footer();
