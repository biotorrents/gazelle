<?
if (!list($Labels, $InFlow, $OutFlow, $Max) = $Cache->get_value('torrents_timeline')) {
  $DB->query("
    SELECT DATE_FORMAT(Time,\"%b %Y\") AS Month, COUNT(ID)
    FROM log
    WHERE Message LIKE 'Torrent % was uploaded by %'
    GROUP BY Month
    ORDER BY Time DESC
    LIMIT 1, 12");
  $TimelineIn = array_reverse($DB->to_array());
  $DB->query("
    SELECT DATE_FORMAT(Time,\"%b %Y\") AS Month, COUNT(ID)
    FROM log
    WHERE Message LIKE 'Torrent % was deleted %'
    GROUP BY Month
    ORDER BY Time DESC
    LIMIT 1, 12");
  $TimelineOut = array_reverse($DB->to_array());

  foreach ($TimelineIn as $Month) {
    list($Labels[], $InFlow[]) = $Month;
  }
  foreach ($TimelineOut as $Month) {
    list(, $OutFlow[]) = $Month;
  }
  $Cache->cache_value('torrents_timeline', array($Labels, $InFlow, $OutFlow, $Max), mktime(0, 0, 0, date('n') + 1, 2)); //Tested: fine for dec -> jan
}

if (!$CategoryDistribution = $Cache->get_value('category_distribution')) {
  $DB->query("
    SELECT tg.CategoryID, COUNT(t.ID) AS Torrents
    FROM torrents AS t
      JOIN torrents_group AS tg ON tg.ID = t.GroupID
    GROUP BY tg.CategoryID
    ORDER BY Torrents DESC");
  $CategoryDistribution = $DB->to_array();
  $Cache->cache_value('category_distribution', $CategoryDistribution, 3600 * 24 * 14);
}
foreach ($CategoryDistribution as $i => $Category) {
  list($CategoryID, $Torrents) = $Category;
  $CategoryDistribution[$i]['CategoryID'] = $Categories[$CategoryID - 1];
}

View::show_header('Detailed torrent statistics', 'chart');
?>

<h3 id="Upload_Flow"><a href="#Upload_Flow">Uploads by month</a></h3>
<div class="box pad center">
  <canvas class="chart" id="chart_torrents_timeline" data-chart='{
    "type": "line",
    "data": {
      "labels": <? print '["'.implode('","',$Labels).'"]'; ?>,
      "datasets": [ {
        "label": "New Torrents",
        "backgroundColor": "rgba(0,0,255,0.2)",
        "borderColor": "rgba(0,0,255,0.8)",
        "data": <? print "[".implode(",",$InFlow)."]"; ?>
      }, {
        "label": "Deleted Torrents",
        "backgroundColor": "rgba(255,0,0,0.2)",
        "borderColor": "rgba(255,0,0,0.8)",
        "data": <? print "[".implode(",",$OutFlow)."]"; ?>
      }]
    }
  }'></canvas>
</div>
<h3 id="Torrent_Categories"><a href="#Torrent_Categories">Torrents by category</a></h3>
<div class="box pad center">
  <canvas class="chart" id="chart_torrent_categories" data-chart='{
    "type": "pie",
    "data": {
      "labels": <? print '["'.implode('","', array_column($CategoryDistribution, 'CategoryID')).'"]'; ?>,
      "datasets": [ {
        "data": <? print "[".implode(",", array_column($CategoryDistribution, 'Torrents'))."]"; ?>,
        "backgroundColor": ["#8a00b8","#a944cb","#be71d8","#e8ccf1","#f3e3f9","#fbf6fd","#ffffff"]
      }]
    }
  }'></canvas>
</div>
<?
View::show_footer();
