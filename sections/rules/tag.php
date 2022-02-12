<?php
declare(strict_types=1);

# Formerly Rules::display_site_tag_rules()
View::header('Tagging rules');
?>

<div>
  <div class="header">
    <h2>
      Tagging rules
    </h2>
  </div>

  <div class="box pad rule_summary">
    <ul>
      <li>
        <strong>Please use the
          <strong class="important_text_alt">vanity.house</strong>
          tag for data you or your lab produced.</strong>
        This helps us promote the DIYbio community's original contributions.
      </li>

      <li>
        Tags should be comma-separated, and you should use a period to separate words inside a tag, e.g.,
        <strong class="important_text_alt">gram.negative</strong>.
      </li>

      <li>
        There is a list of official tags <?=($OnUpload ? 'to the left of the text box' : 'on the <a href="upload.php">upload page</a>')?>.
        Please use these tags instead of "unofficial" tags, e.g., use the official
        <strong class="important_text_alt">fungi</strong>
        tag instead of an unofficial
        <strong class="important_text">mushrooms</strong>
        tag.
      </li>

      <li>Avoid using multiple synonymous tags.
        Using both
        <strong class="important_text">cyanobacteria</strong>
        and
        <strong class="important_text_alt">bacteria</strong>
        is redundant and stupid &mdash; just use the official
        <strong class="important_text_alt">bacteria</strong>.
      </li>

      <li>
        Don't add useless tags that are already covered by other metadata.
        If a torrent is in the DNA category, please don't tag it
        <strong class="important_text">dna</strong>.
      </li>

      <li>
        Only tag information related to the group itself &mdash;
        <strong>not the individual release</strong>.
        Tags such as
        <strong class="important_text">apollo.100</strong>,
        <strong class="important_text">hiseq.2500</strong>,
        etc., are strictly forbidden.
        Remember that these tags will be used for other releases in the same group.
      </li>

      <li>
        <strong>Certain tags are strongly encouraged for appropriate uploads:</strong>
        <strong class="important_text_alt">archaea</strong>,
        <strong class="important_text_alt">bacteria</strong>,
        <strong class="important_text_alt">fungi</strong>,
        <strong class="important_text_alt">animals</strong>,
        <strong class="important_text_alt">plants</strong>,
        <strong class="important_text_alt">plasmids</strong>.
        People search for these kinds of things specifically, so tagging them properly will get you more snatches.
      </li>

      <!--
      <li>
        <strong>Use tag namespaces when appropriate.</strong>
        BioTorrents.de allows for tag namespaces to aid with searching.
        For example, you may want to use the tags
        <strong class="important_text_alt">masturbation:male</strong>
        or
        <strong class="important_text_alt">masturbation:female</strong>
        instead of just
        <strong class="important_text">masturbation</strong>.
        They can be used to make search queries more specific.
        Searching for
        <strong class="important_texti_alt">masturbation</strong>
        will show all torrents tagged with
        <strong class="important_text_alt">masturbation</strong>,
        <strong class="important_text_alt">masturbation:male</strong>,
        or
        <strong class="important_text_alt">masturbation:female</strong>.
        However, searching for
        <strong class="important_text_alt">masturbation:female</strong>
        will <strong>only</strong> show torrents with that tag.
        Tags with namespaces will appear differently depending on the namespace used, which include:

        <ul>
          <li>
            <strong>:parody</strong> - Used to denote a parodied work:
            <strong class="tag_parody">touhou</strong>,
            <strong class="tag_parody">kantai.collection</strong>
          </li>

          <li>
            <strong>:character</strong> - Used to denote a character in a parodied work:
            <strong class="tag_character">iori.minase</strong>,
            <strong class="tag_character">hakurei.reimu</strong>
          </li>

          <li>
            <strong>:male</strong> - Used to denote that the tag refers to a male character:
            <strong class="tag_male">masturbation</strong>,
            <strong class="tag_male">teacher</strong>
          </li>

          <li>
            <strong>:female</strong> - Used to denote that the tag refers to a female character:
            <strong class="tag_female">masturbation</strong>,
            <strong class="tag_female">shaved</strong>
          </li>
        </ul>
      </li>
      -->

      <li>
        <strong>All uploads require a minimum of 5 tags.</strong>
        Please don't add unrelated tags just to meet the 5 tag requirement.
        If you can't think of 5 tags for your content, study it again until you can.
      </li>

      <li>
        <strong>You should be able to build up a list of tags using only the official tags
          <?=($OnUpload ? 'to the left of the text box' : 'on the <a href="upload.php">upload page</a>')?>.</strong>
        If you doubt whether or not a tag is acceptable, please omit it for now and send a staff PM to request a new
        official tag or an approved alias.
      </li>
    </ul>
  </div>

  <?php include('jump.php'); ?>
</div>
<?php View::footer();
