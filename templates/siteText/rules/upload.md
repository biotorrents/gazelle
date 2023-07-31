## Introduction

BioTorrents.de is the global DIYbio community's distributed data repository.
The content includes richly annotated and searchable biological sequence and medical imaging data.
It follows the example of private BitTorrent sites to
<a href="https://www.cambridge.org/core/services/aop-cambridge-core/content/view/2F379FE0CB50DF502F0075119FD3E060/S1744137417000650a.pdf/institutional_solutions_to_freeriding_in_peertopeer_networks_a_case_study_of_online_pirate_communities.pdf"target="_blank">address the free-rider problem</a>
without recourse to institutional funding.

Please read this entire page carefully because it explains how the tracker organizes the content.
Referring to this page often will help you search faster and upload smarter.
I'll also go line-by-line through the <a href="/upload.php">upload form</a>.

Thanks for taking an interest in this project and contributing to its success.
Please note that BioTorrents.de isn't a pirate website.

## Definitions

- **Torrent.** Broadly used as a noun to describe a `.torrent`` file, the files associated with it, and any associated metadata indexed by the site. Used as a verb to describe the act of downloading or uploading data from or to the swarm.

- **Swarm.** All peers associated with a given torrent.

- **Peer.** A client that has announced to the tracker and is part of the swarm.

- **Seed.** When used as a verb, describes the act of uploading torrent content to other peers. When used as a noun, describes a peer who has all of content associated with a torrent as is able to upload to peers. Sometimes referred to as a seeder.

- **Leech.** When used as a verb, describes the act of downloading torrent content from another peer. When used as a noun, describes someone who is downloading or wants to download torrent content from another peer. Sometimes referred to as a leecher.

- **Metadata.** The information we record here on the site for each torrent, such as title, encoding information, and tags.

- **Hentai.** A subgenre of anime, manga, and games characterized by being pornographic.

## Upload Rules

### General and formatting</h5>

- **Biology only.** BioTorrents.de is an annotated repository of biology data and a bioinformatics learning community. Gazelle in its current state requires lots of hardcoded metadata. I can help you adapt the design, e.g., for physics or astronomy data. A generalized science tracker is in development.

- **Seed forever.** Private torrent trackers succeed when they offer quality niche content and a comfy interface. This isn't an NCBI data dump but a library of annotated info hashes that tomorrow's networks can ingest. Do not upload a torrent unless you intend to seed until there are at least 3 copies. Three is a good minimum swarm size.

- **No advertising.** Please don't "tag" torrents, include ASCII art, or make your torrents look like they came from the Pirate Bay. These kinds of additions are allowed if they serve a relevant purpose. Enclosing a GPG-signed hash of your data isn't a bad idea at all.

- **Speak English.** BioTorrents.de is an Anglophone site. Everything but private messages, and especially torrents and the forums, should be in English.

- **Good Data.** Strive to release complete collections of the highest fidelity data in the most sensible format. Sometimes I wonder whether certain kinds of people are drawn to private torrent trackers, or if the site design encourages otherwise disinterested people to do well.


- **No DRM.** Archived releases must not be password protected. DRM of any kind isn't allowed.
- **Sane Permissions.** Files should have 644 permissions, folders 755. Use `find . -type f -print0 | xargs -0 chmod 0644` and `find . -type d -print0 | xargs -0 chmod 0755`.

- **Folder Structure.** Each torrent should be a single folder so we can manage them easier. Please avoid unnecessary nested folders inside your torrent. Use one of the examples below for your main folder.

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
          "10 GB or 10,000 files."
          Compression is required if your torrent is > 10 GiB or if it contains > 10,000 files.
          Otherwise, please compress text files only if it reduces the torrent size by > 30% and avoid compressing
          binary files.
          Multipart archives are only allowed for torrents > 10 GiB.
        </li>

        <li id="r1.10"><a href="#h1"><strong>&uarr;</strong></a> <a href="#r1.10">1.10</a>
          <strong>Metadata.</strong>
          Avoid matching folder names to BioTorrents.de
          metadata.
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
          Supplemental packs may include a collection of citations, documents, utilities, protocols, metadata, disk
          images, etc., specifically prepared for release.
          The collection should be a separate torrent if the collection constitutes a project in its own right.
          If you only have metadata generated in the data processing workflow, please include it in the main torrent.
        </li>
      </ul>
    </div>

    <!-- Upload Rules: Duplicates and Trumping -->
    <h5 id="h2"><a href="#h2k"><strong>&uarr;</strong></a> <a href="#h2">2</a> Duplicates and Trumping</h5>
    <div class="box">
      <ul>
        <li id="r2.1"><a href="#h2"><strong>&uarr;</strong></a> <a href="#r2.1">2.1</a>
          <strong>Multiple Formats Allowed.</strong>
          It's fine if there's an EMBL and a FASTA of the same data. If you need to convert a dataset for your own
          analysis,
          please upload a quality conversion with supplemetal info.
          Remember that small, one-shot metadata are better included with the data, and collections of docs and utils
          are better separate from it.
          If only the header is different and it follows the "10 GB or 10,000 files" rule, uncompressed torrents trump
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
          In an ideal world, only releases tagged with a specific version number would be allowed because this is
          BitTorrent's main strength.
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
          <strong>One Month Unseeded.</strong>
          Please try requesting a reseed before anything.
          This sends a mass PM to all snatchers asking them to resume seeding the files.
          If you have the original torrent files for the inactive torrent, reseed those instead of uploading a new
          torrent.
          Uploading a replacement torrent should be done only when the original files are unavailable and/or the torrent
          hasn't been seeded for a month.
          The site automatically deletes dead torrents after 6 months.
        </li>

        <li id="r2.5"><a href="#h2"><strong>&uarr;</strong></a> <a href="#r2.5">2.5</a>
          <strong>Watermarks.</strong>
          Data without watermarks trumps watermarked data.
        </li>

        <li id="r2.6"><a href="#h2"><strong>&uarr;</strong></a> <a href="#r2.6">2.6</a>
          <strong>Stupid Compression.</strong>
          If someone uploads, e.g., a gzip of 1000 images, you can trump the torrent with an uncompressed version.
          Likewise, a compressed torrent with folders of 10,000 reads each is trumpable by one where the file structure
          is visible, even if the data itself is compressed.
        </li>
      </ul>
    </div>

    <!-- Upload Form Walkthrough -->
    <h5 id="h3"><a href="#h3k"><strong>&uarr;</strong></a> <a href="#h3">3</a> Upload Form Walkthrough</h5>
    <div class="box">
      <ul>
        <li id="r3.1"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.1">3.1</a>
          <strong>Torrent File.</strong>
          Please click the checkbox marked private and optionally add the announce URL when you make the torrent.
          The site will force torrent privacy and dynamically construct <code>.torrent</code> files with your passkey
          embedded.
          This passkey lets the tracker know who's uploading and downloading, and leaking it will nuke your ratio.
          Please don't share any <code>.torrent</code> files you download for this reason.
          <br><br>
        </li>

        <li id="r3.2"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.2">3.2</a>
          <strong>Category.</strong>
          Please see the
          <a href="/wiki.php?action=article&id=29">Site Categories wiki page</a>
          for detailed info about the top-level organization.
          <br><br>
        </li>

        <li id="r3.3"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.3">3.3</a>
          <strong>Accession Number.</strong>
          Please add accession numbers if they come with the data or if you acquired them for your own data.
          The number can be any format but it must correspond to the actual nucleotide or amino acid sequences
          represented on disk.
          Don't add accession numbers just because the metadata matches.
          RefSeq and UniProt integration, including autofill, is in development.
          <br><br>
        </li>

        <li id="r3.4"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.4">3.4</a>
          <strong>Version.</strong>
          Similar to the accession number field, version information should only exist if the original data is
          versioned, or if you versioned your own data (recommended).
          Any schema is acceptable but Semantic Versioning is strongly encouraged.
          You must use x.y.z numbering as with SemVer.
          <br><br>
        </li>

        <li id="r3.5"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.5">3.5</a>
          <strong>Torrent Title.</strong>
          A short description of the torrent contents such as a FASTA definition line or publication title.
          It doesn't need to match the folders but it should tell you what the data is at a glance.
          Please avoid adding other metadata such as strain, platform, etc., with a dedicated field.
          <br><br>
        </li>

        <li id="r3.6"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.6">3.6</a>
          <strong>Organism.</strong>
          The relevant organism's binomial name and optional subspecies.
          Please use <em>Genus species subspecies</em> and not terms such as var. and subsp.
          Multiple organisms and a way to autofill from FASTA/GenBank headers are both in development.
          <br><br>
        </li>

        <li id="r3.7"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.7">3.7</a>
          <strong>Strain/Variety.</strong>
          The strain's name if known.
          This should correspond to a specific cell line, cultivar, or breed.
          If the species is <em>H. sapiens</em> and the subject's ethnicity is known and relevant, e.g., as in a torrent
          of gene sequences related to sickle cell anemia, please add it here.
          Otherwise, please omit it.
          Do not put any identifying patient data here or anywhere else.
          <br><br>
        </li>

        <li id="r3.8"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.8">3.8</a>
          <strong>Authors(s).</strong>
          The Author field should contain only the author name and no titles.
          The upload form supports multiple authors, which will autocomplete.
          Consistent author naming makes browsing easier because it groups torrents on a common page.
          ORCiD integration is in development.
          <br><br>
        </li>

        <li id="r3.9"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.9">3.9</a>
          <strong>Department/Lab.</strong>
          The lab that did the experiments or the last author's home lab.
          Please use "Unaffiliated" for anonymous or unknown labs.
          <br><br>
        </li>

        <li id="r3.10"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.10">3.10</a>
          <strong>Location.</strong>
          The lab's physical location in one of the below formats.
          <ul>
            <li>{City}, {State} {Postal Code}</li>
            <li>{Postal Code} {City}, {Country}</li>
          </ul>
          For example, Berkeley, CA 94720 or 10117 Berlin, Germany.
          It's okay to use the American style if the foreign address uses the same format.
          Please use "Unknown" for anonymous or unknown labs.
          <br><br>
        </li>

        <li id="r3.11"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.11">3.11</a>
          <strong>Year.</strong>
          The year the data was first published.
          The publication that announced the data.
          <br><br>
        </li>

        <li id="r3.12"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.12">3.12</a>
          <strong>License.</strong>
          BioTorrents.de only allows permissive licenses.
          If your data is original, please consider licensing it under one of the available options.
          The "Unspecified" option is for compatibility with existing releases of variable metadata completeness.
          <br><br>
        </li>

        <li id="r3.13"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.13">3.13</a>
          <strong>Platform.</strong>
          The class of technology the data comes from.
          What sequencing or imaging technique is it the output of?
          <a
            href="forums.php?action=viewforum&forumid=<?= $ENV->SUGGESTIONS_FORUM ?>">Please
            post in the suggestions forum</a>
          if you'd like to request a new platform.
          Note that the platforms change for each category.
          <br><br>
        </li>

        <li id="r3.14"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.14">3.14</a>
          <strong>Format.</strong>
          The file format of the data.
          What programs do you need to work with the data?
          <a
            href="forums.php?action=viewforum&forumid=<?= $ENV->SUGGESTIONS_FORUM ?>">Please
            post in the suggestions forum</a>
          if you'd like to request a new format.
          Note that the formats change for each category.
          You can elect to have the site detect the data format by its file extension.
          <br><br>
        </li>

        <li id="r3.15"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.15">3.15</a>
          <strong>Archive.</strong>
          The compression algorithm used, if any.
          You can elect to have the site detect the archive format by its file extension.
          <br><br>
        </li>

        <li id="r3.16"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.16">3.16</a>
          <strong>Scope.</strong>
          The resolution of the data.
          How much information about the organism does it represent?
          The options correspond in higher conceptual language to:
          a single piece of information, structural information, especially deep or broad information, and an exhaustive
          source.
          Please use the Other option if you'd like to enter a specific resolution such as "420 subjects from Boston."
          <br><br>
        </li>

        <li id="r3.17"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.17">3.17</a>
          <strong>Tags.</strong>
          Please select at least five appropriate tags.
          Don't use irrelevant tags, and consider making new tags as a last resort.
          <a
            href="forums.php?action=viewforum&forumid=<?= $ENV->SUGGESTIONS_FORUM ?>">Please
            post in the suggestions forum</a>
          if you'd like to request a new official tag.
          <br><br>
        </li>

        <li id="r3.18"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.18">3.18</a>
          <strong>Picture.</strong>
          Please upload a meaningful picture, especially if you plan to add the torrent to a collection.
          A photo of the sequence sample or a representative photo of the organism; an example (preferably not a
          thumbnail collection) from an imaging dataset;
          a screenshot of a useful table from the publication; or another similarly informative picture.
          No picture is better than an irrelevant one.
          <br><br>
        </li>

        <li id="r3.19"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.19">3.19</a>
          <strong>Mirrors.</strong>
          BioTorrents.de supports up to two FTP/HTTP web
          seeds on
          an experimental basis according to
          <a href="https://www.bittorrent.org/beps/bep_0019.html" target="_blank">BEP 19 (GetRight style)</a>.
          Please note that not all clients support web seeds, and of those that do, having too many may cause problems
          for you.
          The web seeds must be unencrypted.
          The site automatically rewrites <code>ftps://</code> and <code>https://</code> web addresses.
          Additionally, the contents of the FTP/HTTP folder must correspond exactly to the contents of the
          <code>.torrent</code> file.
          Given these caveats, it's worth documenting the data source for accuracy's sake and to let people save ratio
          here.

          <br><br>
        </li>

        <li id="r3.20"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.20">3.20</a>
          <strong>Publications.</strong>
          DOI numbers should be well-formed, one per line.
          The site currently discards malformed DOI numbers instead of extracting them from arbitrary strings.
          An auto-extract and metadata fetching are in development.
          If your research is a normal URI, please use the Torrent Group Description field.
          <br><br>
        </li>

        <li id="r3.21"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.21">3.21</a>
          <strong>Torrent Group Description.</strong>
          General info about the object of study's function or significance.
          This is the main body text on a torrent's page.
          Please limit the contents of this field to concise and interesting knowledge.
          <br><br>
        </li>

        <li id="r3.22"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.22">3.22</a>
          <strong>Torrent Description.</strong>
          Specific info about the protocols and equipment relevant to <em>this</em> data.
          This text is hidden by default.
          It displays when you click the Torrent Title next to [ DL | RP ].
          Please discuss materials and methods here.
          <br><br>
        </li>

        <li id="r3.23"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.23">3.23</a>
          <strong>Aligned/Annotated.</strong>
          Does the data come with any metadata of an analytical nature, such as alignment data (mandatory if checked)?
          If so, does the torrent folder contain the scripts used to generate the metadata (optional)?
          <br><br>
        </li>

        <li id="r3.24"><a href="#h3"><strong>&uarr;</strong></a> <a href="#r3.24">3.24</a>
          <strong>Upload Anonymously.</strong>
          You'll still get upload credit even if you hide your username from the torrent details.
          I believe it's still visible to sysops.
        </li>
      </ul>
    </div>
    <!-- End rules -->

  </div>
</div>
<?php View::footer();








Anything not allowed on the forums is also not allowed on Slack and vice versa.
They are separated for convenience only.

## Forum rules

- Let's treat the biology boards like how the Shroomery used to be:
  each thread a set of resourceful wisdom worth using permalinks to.
  It's okay if the boards are slow, that's why there are only a few of them.

- Please discuss site news in the corresponding Announcements thread instead of making a new General thread.
  Discussing science-related news in General is highly encouraged, but discussing political news is much less so.
  But don't self-censor, e.g., you can discuss the political and economic factors of the 2019-nCoV outbreak,
  but you can't start a thread about trade deals and hope to steer it toward biology.
  Thank you.

- No advertising, referrals, cryptocurrency pumps, or calls to action that involve using a financial instrument.
  You'll be banned on the spot.
  The exceptions: discussions about cryptocurrencies that derive their value from work performed on distributed science networks,
  i.e., Curecoin, FoldingCoin, and Gridcoin.

- All affiliate links must be labelled `(affiliate link)` and they must be related to the discussion,
  in a context where only you would personally benefit if someone used the link.

- Feel free to post announcements for your own projects, even and especially if they're commercial ones, in the General board.
  Limit all discussion of trading biomaterials, including bulk giveaways, to the Marketplace forum available to Power Users.

- Please be modest when talking about your uploads.
  It's unnecessary to announce your uploads because Gazelle logs everything
  (at least this installation's database is encrypted).
  If someone asks for help on his project and your upload fits the bill, go write a post!

- Use descriptive and specific subject lines.
  This helps others decide whether your particular words of "wisdom" relate to a topic they care about.

- Don't post comments that don't add anything to the discussion, such as "I agree" or "haha."
  Bottle the trained dopamine response to social media because comment reactions are an unlikely feature.

- Please refrain from quoting excessively.
  When quoting someone, use only the necessary parts of the quote.
  Avoid quoting more than 3 levels deep.

- Don't post potentially malicious links without sufficient warning, or post pictures > 2 MiB.
  Please only speak English as stated in the upload rules.

## Slack rules

- Our Slack channels are just are another quiet hangout you can stack your app with so you look cool at conferences.

- Please use
  `#general` for the usual chit-chat,
  `#development` for questions about the Gazelle software, and
  `#support` to get help with your account.

- Don't send mass alerts with `@channel`, `@everyone`, or `@here`.
  It's obnoxious and you should handle anything genuinely important on the boards.

- Flooding is irritating and you'll get kicked for it.
  This includes "now playing" scripts, large amounts of irrelevant text such as lorem ipsum, and unfunny non sequiturs.

- Impersonating other members, particularly staff members, will not go unpunished.
  Please remember that the Slack channels are publicly accessible.

- Please use the threaded conversations feature in Slack and avoid replying to threads with new messages or crossposting replies to the main channel.

- Announce and bot channels are in development, as standard IRC instead of Slack for obvious reasons.
  Any IRC bots you have must authenticate with your own username and IRC key, and set the `+B` usermode on themselves.
