<?php
declare(strict_types=1);

/**
 * The $Types array is the backbone of the reports system and is stored here so it can
 * be included on the pages that need it without clogging up the pages that don't.
 * Important thing to note about the array:
 *   1. When coding for a non music site, you need to ensure that the top level of the
 * array lines up with the $Categories array in your config.php.
 *   2. The first sub array contains resolves that are present on every report type
 * regardless of category.
 *   3. The only part that shouldn't be self-explanatory is that for the tracks field in
 * the report_fields arrays, 0 means not shown, 1 means required, 2 means required but
 * you can't select the 'All' box.
 *   4. The current report_fields that are set up are tracks, sitelink, link and image. If
 * you wanted to add a new one, you'd need to add a field to the reportsv2 table, elements
 * to the relevant report_fields arrays here, add the HTML in ajax_report and add security
 * in takereport.
 */

$Types = array(
  'master' => array(
    
    # dupe
    'dupe' => array(
      'priority' => '10',
      'reason' => '0',
      'title' => 'Dupe',
      'report_messages' => array(
        'Please specify a link to the original torrent.'
      ),

      'report_fields' => array(
        'sitelink' => '1'
      ),

      'resolve_options' => array(
        'upload' => '0',
        'warn' => '0',
        'delete' => '1',
        'pm' => '[rule]r1.1.4[/rule]. Your torrent was reported because it was a duplicate of another torrent.'
      ),
    ),

    # banned
    'banned' => array(
      'priority' => '230',
      'reason' => '14',
      'title' => 'Specifically Banned',
      'report_messages' => array(
        'Note that this report form is for content that is banned by name, not content that breaks a general uploading rule.',
        'Please specify exactly which entry on the Specifically Banned list this is violating.'
      ),

      'report_fields' => array(
      ),

      'resolve_options' => array(
        'upload' =>' 0',
        'warn' => '4',
        'delete' => '1',
        'pm' => '[rule]h1.3[/rule]. You have uploaded material that is currently forbidden.
        Items on the [url='.site_url().'/rules/upload#h1.3]Specifically Banned[/url] portion of the uploading rules cannot be uploaded to the site.
        Your torrent was reported because it contained material from the Specifically Banned section of the rules.'
      ),
    ),

    # urgent
    'urgent' => array(
      'priority' => '280',
      'reason' => '-1',
      'title' => 'Urgent',
      'report_messages' => array(
        'This report type is only for very urgent reports, usually for personal information being found within a torrent.',
        'Abusing the "Urgent" report type could result in a warning or worse.',
        'As this report type gives the staff absolutely no information about the problem, please be as clear as possible in your comments about what the problem is.'
      ),

      'report_fields' => array(
        'sitelink' => '0',
        'link' => '0',
        'image' => '0',
      ),

      'resolve_options' => array(
        'upload' => '0',
        'warn' => '0',
        'delete' => '0',
        'pm' => ''
      ),
    ),

    # trump
    'trump' => array(
      'priority' => '20',
      'reason' => '1',
      'title' => 'Trump',
      'report_messages' => array(
        'Please list the specific reason(s) the newer torrent trumps the older one.',
        'Please make sure you are reporting the torrent <strong class="important_text">which has been trumped</strong> and should be deleted, not the torrent that you think should remain on site.'
      ),

      'report_fields' => array(
        'sitelink' => '1'
      ),

      'resolve_options' => array(
        'upload' => '0',
        'warn' => '0',
        'delete' => '1',
        'pm' => 'Your torrent was reported because it was trumped by another torrent.'
      ),
    ),

    # notporn
    'notporn' => array(
      'priority' => '100',
      'reason' => '-1',
      'title' => 'Not Pornographic',
      'report_messages' => array(
        'This report type is for reporting torrents that are not pornographic.'
      ),

      'report_fields' => array(
      ),

      'resolve_options' => array(
        'upload' => '0',
        'warn' => '0',
        'delete' => '1',
        'pm' => '[rule]r1.1.2[/rule]. Your torrent was reported beacuse it was not pornographic. All torrents on '.site_url().' must be pornographic.'
      ),
    ),

    # lang
    'lang' => array(
      'priority' => '100',
      'reason' => '-1',
      'title' => 'Disallowed Language',
      'report_messages' => array(
        'This report type is for reporting torrent that contain no Japanese or English language content.'
      ),

      'report_fields' => array(
      ),

      'resolve_options' => array(
        'upload' => '0',
        'warn' => '0',
        'delete' => '1',
        'pm' => '[rule]r1.1.7[/rule]. Your torrent was reported because it contained neither Japanese nor English text/audio.'
      ),
    ),

    # metadata
    'metadata' => array(
      'priority' => '100',
      'reason' => '-1',
      'title' => 'Incorrect Metadata',
      'report_messages' => array(
        'This report type is for reporting improperly/incorrectly tagged torrents.',
        'Please describe what changes need to be made to the metadata.'
      ),

      'report_fields' => array(
      ),

      'resolve_options' => array(
        'upload' => '0',
        'warn' => '0',
        'delete' => '0',
        'pm' => 'Your torrent was reported for having incorrect metadata.'
      ),
    ),

    # other
    'other' => array(
      'priority' => '200',
      'reason' => '-1',
      'title' => 'Other',
      'report_messages' => array(
        'Please include as much information as possible to verify the report.'
      ),

      'report_fields' => array(
      ),

      'resolve_options' => array(
        'upload' => '0',
        'warn' => '0',
        'delete' => '0',
        'pm' => ''
      ),
    ),
  ),
);
