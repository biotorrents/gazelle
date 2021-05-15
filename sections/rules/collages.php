<?php
#declare(strict_types=1);

View::show_header('Collection rules');
?>

<div>
  <div class="header">
    <h2>
      Collection rules
    </h2>
  </div>

  <div class="box pad rule_summary" style="padding: 10px 10px 10px 20px;">
    <ul>
      <li>
        Collection vandalism is taken very seriously, resulting in loss of collection editing privileges at a minimum.
      </li>

      <li>
        A well-defined group of people, for instance Counter Culture Labs members, may create a Group Picks collection
        with one pick per person, after having gained permission for the collection from staff.
        Please avoid making Group Picks without an affirmative staff PM reply to your request.
      </li>

      <li>
        There may only be one collection per Theme.
        Duplicate collections will be deleted.
        The collection allowed to stay is the best maintained and oldest collection.
        In a word, the most established one.
      </li>

      <li>
        Theme collections must be sensible and reasonably broad.
        Those that don't fit this description will be deleted.
        Note the conceptual space this rule allows.
      </li>

      <li>
        Collections are <strong>not</strong> an alternative to the tagging system.
        A collection such as Fungi Torrents wouldn't be allowed because we have the <strong
          class="important_text_alt">fungi</strong> tag.
        Naturally a Fungi rRNA Barcodes collection is different.
      </li>

      <li>
        Every collection must have at least 3 torrent groups in it except for Personal ones.
      </li>

      <li>
        Please check to see that a similar collection doesn't already exist.
        If one does, please contribute to that.
      </li>

      <li>
        If you must make a new collection, please give it an appropriate title and a decent description explaining its
        purpose, and attempt to add a cover image to every torrent in it.
      </li>
    </ul>
  </div>

  <?php include('jump.php'); ?>
</div>
<?php View::show_footer();
