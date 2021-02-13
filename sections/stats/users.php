<?php


if (!$ClassDistribution = $Cache->get_value('class_distribution')) {
    $DB->query("
      SELECT p.Name, COUNT(m.ID) AS Users
      FROM users_main AS m
        JOIN permissions AS p ON m.PermissionID = p.ID
      WHERE m.Enabled = '1'
      GROUP BY p.Name
      ORDER BY Users DESC");

    $ClassDistribution = $DB->to_array();
    $Cache->cache_value('class_distribution', $ClassDistribution, 3600 * 24 * 14);
}

if (!$PlatformDistribution = $Cache->get_value('platform_distribution')) {
    $DB->query("
      SELECT OperatingSystem, COUNT(DISTINCT UserID) AS Users
      FROM users_sessions
      GROUP BY OperatingSystem
      ORDER BY Users DESC");

    $PlatformDistribution = $DB->to_array();
    $Cache->cache_value('platform_distribution', $PlatformDistribution, 3600 * 24 * 14);
}

if (!$BrowserDistribution = $Cache->get_value('browser_distribution')) {
    $DB->query("
      SELECT Browser, COUNT(DISTINCT UserID) AS Users
      FROM users_sessions
      GROUP BY Browser
      ORDER BY Users DESC");

    $BrowserDistribution = $DB->to_array();
    $Cache->cache_value('browser_distribution', $BrowserDistribution, 3600 * 24 * 14);
}

// Timeline generation
if (!list($Labels, $InFlow, $OutFlow) = $Cache->get_value('users_timeline')) {
    $DB->query("
      SELECT DATE_FORMAT(JoinDate,\"%b %Y\") AS Month, COUNT(UserID)
      FROM users_info
      GROUP BY Month
      ORDER BY JoinDate DESC
      LIMIT 1, 11");
    $TimelineIn = array_reverse($DB->to_array());

    $DB->query("
      SELECT DATE_FORMAT(BanDate,\"%b %Y\") AS Month, COUNT(UserID)
      FROM users_info
      WHERE BanDate > 0
      GROUP BY Month
      ORDER BY BanDate DESC
      LIMIT 1, 11");
    $TimelineOut = array_reverse($DB->to_array());

    $Labels = [];
    foreach ($TimelineIn as $Month) {
        list($Labels[], $InFlow[]) = $Month;
    }

    foreach ($TimelineOut as $Month) {
        list(, $OutFlow[]) = $Month;
    }
    $Cache->cache_value('users_timeline', array($Labels, $InFlow, $OutFlow), mktime(0, 0, 0, date('n') + 1, 2)); //Tested: fine for Dec -> Jan
}
// End timeline generation

View::show_header('User Statistics', $JSIncludes = 'vendor/chart.min');
?>

<h2 class="header">
  User Stats
</h2>


<h3>
  Flow
</h3>

<div class="box pad center">
  <canvas class="chart" id="chart_user_timeline"></canvas>
  <script>
    var ctx = document.getElementById('chart_user_timeline').getContext('2d');
    var myChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: <?= ' ["'.implode('","', $Labels).'"]'; ?> ,
        datasets: [{
            label: 'New Registrations',
            backgroundColor: 'rgba(179, 229, 252, 0.8)', // #B3E5FC
            borderColor: 'rgba(1, 87, 155, 0.8)', // #01579B
            data:
            <?= "[".implode(",", $InFlow)."]"; ?>
          },

          {
            label: 'Disabled Users',
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
  Classes
</h3>

<div class="box pad center">
  <canvas class="chart" id="chart_user_classes"></canvas>
  <script>
    var ctx = document.getElementById('chart_user_classes').getContext('2d');
    var myChart = new Chart(ctx, {
      type: 'pie',
      data: {
        labels: <?= ' ["'.implode('","', array_column($ClassDistribution, 'Name')).'"]'; ?> ,
        datasets: [{
          data:
          <?= "[".implode(",", array_column($ClassDistribution, 'Users'))."]"; ?>
          ,
          backgroundColor: [
            'rgba(241, 248, 233, 0.8)', // #F1F8E9
            'rgba(220, 237, 200, 0.8)', // #DCEDC8
            'rgba(197, 225, 165, 0.8)', // #C5E1A5
            'rgba(174, 213, 129, 0.8)', // #AED581
            'rgba(156, 204, 101, 0.8)', // #9CCC65
            'rgba(139, 195, 74,  0.8)', // #8BC34A
            'rgba(124, 179, 66,  0.8)', // #7CB342
            'rgba(104, 159, 56,  0.8)', // #689F38
            'rgba(85,  139, 47,  0.8)', // #558B2F
            'rgba(51,  105, 30,  0.8)', // #33691E
          ]
        }]
      }
    });
  </script>
</div>


<h3>
  Platforms
</h3>

<div class="box pad center">
  <?php
    $AllPlatforms = array_column($PlatformDistribution, 'OperatingSystem');
    $SlicedPlatforms = (count($AllPlatforms) > 14) ? array_slice($AllPlatforms, 0, 13)+[13=>'Other'] : $AllPlatforms;

    $AllUsers = array_column($PlatformDistribution, 'Users');
    $SlicedUsers = (count($AllUsers) > 14) ? array_slice($AllUsers, 0, 13)+[13=>array_sum(array_slice($AllUsers, 13))] : $AllUsers;
  ?>

  <canvas class="chart" id="chart_user_platforms"></canvas>
  <script>
    var ctx = document.getElementById('chart_user_platforms').getContext('2d');
    var myChart = new Chart(ctx, {
      type: 'pie',
      data: {
        labels: [
          "<?=implode('","', $AllPlatforms)?>"
        ],
        datasets: [{
          data: [ <?=implode(",", $AllUsers)?> ],
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


<h3>
  Browsers
</h3>

<div class="box pad center">
  <?php
    $AllBrowsers = array_column($BrowserDistribution, 'Browser');
    $SlicedBrowsers = (count($AllBrowsers) > 7) ? array_slice($AllBrowsers, 0, 6)+[6=>'Other'] : $AllBrowsers;

    $AllUsers = array_column($BrowserDistribution, 'Users');
    $SlicedUsers = (count($AllUsers) > 7) ? array_slice($AllUsers, 0, 6)+[6=>array_sum(array_slice($AllUsers, 6))] : $AllUsers;
  ?>

  <canvas class="chart" id="chart_user_browsers"></canvas>
  <script>
    var ctx = document.getElementById('chart_user_browsers').getContext('2d');
    var myChart = new Chart(ctx, {
      type: 'pie',
      data: {
        labels: [
          "<?=implode('","', $AllBrowsers)?>"
        ],
        datasets: [{
          data: [ <?=implode(",", $AllUsers)?> ],
          backgroundColor: [
            'rgba(158, 158, 158, 0.8)', // #9E9E9E
            'rgba(207, 216, 220, 0.8)', // #CFD8DC
            'rgba(215, 204, 200, 0.8)', // #D7CCC8
            'rgba(255, 224, 178, 0.8)', // #FFE0B2
            'rgba(255, 249, 196, 0.8)', // #FFF9C4
            'rgba(220, 237, 200, 0.8)', // #DCEDC8
            'rgba(178, 223, 219, 0.8)', // #B2DFDB
            'rgba(179, 229, 252, 0.8)', // #B3E5FC
            'rgba(197, 202, 233, 0.8)', // #C5CAE9
            'rgba(225, 190, 231, 0.8)', // #E1BEE7
            'rgba(255, 205, 210, 0.8)', // #FFCDD2
          ]
        }]
      }
    });
  </script>
</div>
<?php View::show_footer();
