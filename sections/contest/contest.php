<?
if (!($ContestSettings = $Cache->get_value("contest_settings"))) {
  $DB->query("
  SELECT
    First,
    Second
  FROM misc
  WHERE
    Name='ContestRules'
    OR Name='ContestTimes'
    OR Name='ContestRewards'");
  if ($DB->has_results()) {
    list($QueryPart, $Rules)   = $DB->next_record();
    list($StartTime, $EndTime) = $DB->next_record();
    list($Amount, $Currency)   = $DB->next_record();
  }
  $ContestSettings = array(
    'query'  => html_entity_decode($QueryPart),
    'rules'  => $Rules,
    'start'  => $StartTime,
    'end'    => $EndTime,
    'reward' => ($Amount.' '.$Currency.'/torrent')
  );
  $Cache->cache_value('contest_settings', $ContestSettings);
}

if (!($Scores = $Cache->get_value("contest_scores"))) {
  $DB->query("
  SELECT
    u.Username,
    u.ID,
    COUNT(*) AS Uploads
  FROM torrents AS t
  LEFT JOIN torrents_group AS tg ON t.groupID=tg.ID
  LEFT JOIN users_main AS u ON t.UserID=u.ID
  WHERE
    $ContestSettings[query]
    AND UNIX_TIMESTAMP(t.Time) > $ContestSettings[start]
    AND UNIX_TIMESTAMP(t.Time) < $ContestSettings[end]
  GROUP BY UserID
  ORDER BY Uploads DESC
  LIMIT 50");

  $Scores = $DB->to_array();
  $Cache->cache_value('contest_scores', $Scores);
}

View::show_header('Contest');

if (time() < $ContestSettings['start']) {
  print '<h2>Future Contest (Starts in '.time_diff($ContestSettings['start'],2,false).')</h2>';
} else if (time() > $ContestSettings['end']) {
  print '<h2>Finished Contest</h2>';
} else {
  print '<h2>Ongoing Contest! ('.time_diff($ContestSettings['end'],2,false).' remaining)</h2>';
}
?>

<div class="thin flex">
  <div class="box pad grow">
<? if ($Scores) { ?>
    <h2 id="general">Scoreboard</h2>
    <table width="100%" class="contest_scoreboard">
      <tr class="colhead">
        <td>Place</td>
        <td>User</td>
        <td>Score</td>
      </tr>
<?   foreach ($Scores as $Place => $Score) { ?>
  <tr class="row">
    <td><?=($Place+1)?></td>
    <td><a href="/user.php?id=<?=$Score['ID']?>"><?=$Score['Username']?></a></td>
    <td><?=$Score['Uploads']?></td>
  </tr>
<?   } ?>
    </table>
<? } else { ?>
    <h2>No Scores Yet</h2>
<? } ?>
  </div>
  <div class="shrink flex" style="margin-left: 1em; flex-direction: column;">
    <div class="box pad">
      <h2>Qualifications</h2>
      <ul>
<?
  print '<li>'.str_replace('\n', '</li><li>', $ContestSettings['rules']).'</li>'
?>
      </ul>
    </div>
    <div class="box pad">
      <h2>Rewards</h2>
      <ul>
        <li><?=$ContestSettings['reward']?></li>
      </ul>
    </div>
  </div>
</div>

<? View::show_footer(); ?>
