<?php
#declare(strict_types=1);

if (!check_perms('site_view_flow')) {
    error(403);
}

// Timeline generation
if (!isset($_GET['page'])) {
    if (!list($Labels, $InFlow, $OutFlow) = $cache->get_value('users_timeline')) {
        $db->query("
          SELECT DATE_FORMAT(JoinDate, \"%b %y\") AS Month, COUNT(UserID)
          FROM users_info
          GROUP BY Month
          ORDER BY JoinDate DESC
          LIMIT 1, 11");
        $TimelineIn = array_reverse($db->to_array());

        $db->query("
          SELECT DATE_FORMAT(BanDate, \"%b %y\") AS Month, COUNT(UserID)
          FROM users_info
          WHERE BanDate > 0
          GROUP BY Month
          ORDER BY BanDate DESC
          LIMIT 1, 11");
        $TimelineOut = array_reverse($db->to_array());

        $Labels = [];
        foreach ($TimelineIn as $Month) {
            list($Labels[], $InFlow[]) = $Month;
        }

        foreach ($TimelineOut as $Month) {
            list(, $OutFlow[]) = $Month;
        }
        $cache->cache_value('users_timeline', array($Labels, $InFlow, $OutFlow), mktime(0, 0, 0, date('n') + 1, 2));
    }
}
// End timeline generation

define('DAYS_PER_PAGE', 100);
list($Page, $Limit) = Format::page_limit(DAYS_PER_PAGE);

# wtf
$RS = $db->query("
  SELECT
    SQL_CALC_FOUND_ROWS
    j.Date,
    DATE_FORMAT(j.Date, '%Y-%m') AS Month,
    CASE ISNULL(j.Flow)
      WHEN 0 THEN j.Flow
      ELSE '0'
    END AS Joined,
    CASE ISNULL(m.Flow)
      WHEN 0 THEN m.Flow
      ELSE '0'
    END AS Manual,
    CASE ISNULL(r.Flow)
      WHEN 0 THEN r.Flow
      ELSE '0'
    END AS Ratio,
    CASE ISNULL(i.Flow)
      WHEN 0 THEN i.Flow
      ELSE '0'
    END AS Inactivity
  FROM (
    SELECT
      DATE_FORMAT(JoinDate, '%Y-%m-%d') AS Date,
      COUNT(UserID) AS Flow
    FROM users_info
      WHERE JoinDate IS NOT NULL
      GROUP BY Date
  ) AS j
    LEFT JOIN (
      SELECT
        DATE_FORMAT(BanDate, '%Y-%m-%d') AS Date,
        COUNT(UserID) AS Flow
      FROM users_info
      WHERE BanDate IS NOT NULL
        AND BanReason = '1'
      GROUP BY Date
    ) AS m ON j.Date = m.Date
    LEFT JOIN (
      SELECT
        DATE_FORMAT(BanDate, '%Y-%m-%d') AS Date,
        COUNT(UserID) AS Flow
      FROM users_info
      WHERE BanDate IS NOT NULL
        AND BanReason = '2'
      GROUP BY Date
    ) AS r ON j.Date = r.Date
    LEFT JOIN (
      SELECT
        DATE_FORMAT(BanDate, '%Y-%m-%d') AS Date,
        COUNT(UserID) AS Flow
      FROM users_info
      WHERE BanDate IS NOT NULL
        AND BanReason = '3'
      GROUP BY Date
    ) AS i ON j.Date = i.Date
  ORDER BY j.Date DESC
  LIMIT $Limit");

$db->query('SELECT FOUND_ROWS()');
list($Results) = $db->next_record();
View::header('User Flow', 'chart');
$db->set_query_id($RS);
?>

<div class="linkbox">
  <?php
$Pages = Format::get_pages($Page, $Results, DAYS_PER_PAGE, 11);
echo $Pages;
?>
</div>

<div class="box">
  <table width="100%">
    <tr class="colhead">
      <td>Date</td>
      <td>+ Joined</td>
      <td>− Manual</td>
      <td>− Ratio</td>
      <td>− Inactivity</td>
      <td>− Total</td>
      <td>Net Growth</td>
    </tr>

    <?php
  while (list($Date, $Month, $Joined, $Manual, $Ratio, $Inactivity) = $db->next_record()) {
      $TotalOut = $Ratio + $Inactivity + $Manual;
      $TotalGrowth = $Joined - $TotalOut; ?>
    <tr class="row">
      <td>
        <?=$Date?>
      </td>

      <td>
        <?=number_format($Joined)?>
      </td>

      <td>
        <?=number_format($Manual)?>
      </td>

      <td>
        <?=number_format((float)$Ratio)?>
      </td>

      <td>
        <?=number_format($Inactivity)?>
      </td>

      <td>
        <?=number_format($TotalOut)?>
      </td>

      <td>
        <?=number_format($TotalGrowth)?>
      </td>
    </tr>
    <?php
  } ?>
  </table>
</div>

<div class="linkbox">
  <?=$Pages?>
</div>
</div>
<?php
View::footer();
