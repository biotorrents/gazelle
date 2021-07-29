<?php
#declare(strict_types=1);

/*
...
DESC
LIMIT 1, 12
*/
if (!list($Labels, $InFlow, $OutFlow, $Max) = $Cache->get_value('torrents_timeline')) {
    $DB->prepared_query("
    SELECT
      DATE_FORMAT(`Time`, '%b %Y') AS Month,
      COUNT(`ID`)
    FROM
      `log`
    WHERE
      `Message` LIKE 'Torrent % uploaded %'
    GROUP BY
      `Month`
    ORDER BY
      `Time`
    DESC
    ");
    $TimelineIn = array_reverse($DB->to_array());

    $DB->prepared_query("
    SELECT
      DATE_FORMAT(`Time`, '%b %Y') AS Month,
      COUNT(`ID`)
    FROM
      `log`
    WHERE
      `Message` LIKE 'Torrent % deleted %'
    GROUP BY
      `Month`
    ORDER BY
      `Time`
    DESC
    ");
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
    $DB->prepared_query("
    SELECT
      tg.`category_id`,
      COUNT(t.`ID`) AS Torrents
    FROM
      `torrents` AS t
    JOIN `torrents_group` AS tg
    ON
      tg.`id` = t.`GroupID`
    GROUP BY
      tg.`category_id`
    ORDER BY
      `Torrents`
    DESC
    ");

    $CategoryDistribution = $DB->to_array();
    $Cache->cache_value('category_distribution', $CategoryDistribution, 3600 * 24 * 14);
}

foreach ($CategoryDistribution as $i => $Category) {
    list($CategoryID, $Torrents) = $Category;
    $CategoryDistribution[$i]['CategoryID'] = $Categories[$CategoryID - 1];
}

View::show_header('Detailed torrent statistics', 'vendor/chart.min');
?>

<h2 class="header">
  Torrent Stats
</h2>


<h3>
  Flow
</h3>

<div class="box pad center">
  <canvas class="chart" id="chart_torrents_timeline" width="80%"></canvas>
  <script>
    var ctx = document.getElementById('chart_torrents_timeline').getContext('2d');
    var myChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: <?= ' ["'.implode('","', $Labels).'"]'; ?> ,
        datasets: [{
            label: 'New Torrents',
            backgroundColor: 'rgba(179, 229, 252, 0.8)', // #B3E5FC
            borderColor: 'rgba(1, 87, 155, 0.8)', // #01579B
            data:
            <?= "[".implode(",", $InFlow)."]"; ?>
          },

          {
            label: 'Deleted Torrents',
            backgroundColor: 'rgba(255, 224, 178, 0.8)', // #FFE0B2
            borderColor: 'rgba(230, 81, 0, 0.8)', // #E65100
            data:
            <?= "[".implode(",", $OutFlow)."]"; ?>
          }
        ]
      }
    });
  </script>
</div>


<h3>
  Categories
</h3>

<div class="box pad center">
  <canvas class="chart" id="chart_torrent_categories" width="80%"></canvas>
  <script>
    var ctx = document.getElementById('chart_torrent_categories').getContext('2d');
    var myChart = new Chart(ctx, {
      type: 'pie',
      data: {
        labels: <?= ' ["'.implode('","', array_column($CategoryDistribution, 'CategoryID')).'"]'; ?> ,
        datasets: [{
          data:
          <?= "[".implode(",", array_column($CategoryDistribution, 'Torrents'))."]"; ?>
          ,
          backgroundColor: [
            'rgba(255, 205, 210, 0.8)', // #FFCDD2
            'rgba(225, 190, 231, 0.8)', // #E1BEE7
            'rgba(197, 202, 233, 0.8)', // #C5CAE9
            'rgba(179, 229, 252, 0.8)', // #B3E5FC
            'rgba(178, 223, 219, 0.8)', // #B2DFDB
            'rgba(220, 237, 200, 0.8)', // #DCEDC8
            'rgba(255, 249, 196, 0.8)', // #FFF9C4
            'rgba(255, 224, 178, 0.8)', // #FFE0B2
            'rgba(215, 204, 200, 0.8)', // #D7CCC8
            'rgba(207, 216, 220, 0.8)', // #CFD8DC
            'rgba(158, 158, 158, 0.8)', // #9E9E9E
          ]
        }]
      }
    });
  </script>
</div>
<?php
include SERVER_ROOT.'/sections/tools/data/database_specifics.php';
View::show_footer();
