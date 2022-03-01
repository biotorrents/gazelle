<?php
#declare(strict_types = 1);

if (!check_perms('site_debug') || !check_perms('admin_clear_cache')) {
    error(403);
}

if (isset($_POST['global_flush'])) {
    authorize();
    $cache->flush();
}

$db->prepared_query('SHOW GLOBAL STATUS');
$dbStats = $db->to_array('Variable_name');
$MemStats = $cache->getStats();

View::header("Service Stats"); ?>

<div class="permissions">
  <div class="permission_container">
    <table>
      <tr class="colhead">
        <th colspan="2">Service</th>
      </tr>

      <tr>
        <th colspan="2"><strong>Threads (Active)</strong></th>
      </tr>

      <tr>
        <td>Cache:</td>
        <td>
          <?=Text::number_format($MemStats['threads'])?>
          <span class="float_right">(100.000%)</span>
        </td>
      </tr>

      <tr>
        <td<?php if ($dbStats['Threads_connected']['Value'] / $dbStats['Threads_created']['Value']> 0.7) {
    echo '
          class="invalid" ';
} ?>>Database:</td>

          <td>
            <?=Text::number_format($dbStats['Threads_created']['Value'])?>
            <span class="float_right">(<?=Text::number_format(($dbStats['Threads_connected']['Value'] / $dbStats['Threads_created']['Value']) * 100, 3)?>%)</span>
          </td>
      </tr>

      <tr>
        <td colspan="2"></td>
      </tr>

      <tr>
        <th colspan="2"><strong>Connections</strong></th>
      </tr>

      <tr>
        <td>Cache:</td>
        <td>
          <?=Text::number_format($MemStats['total_connections'])?>
        </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td>
          <?=Text::number_format($dbStats['Connections']['Value'])?>
        </td>
      </tr>

      <tr>
        <td colspan="2"></td>
      </tr>

      <tr>
        <th colspan="2"><strong>Special</strong></th>
      </tr>

      <tr>
        <td>Cache Current Index:</td>
        <td>
          <?=Text::number_format($MemStats['curr_items'])?>
        </td>
      </tr>

      <tr>
        <td>Cache Total Index:</td>
        <td>
          <?=Text::number_format($MemStats['total_items'])?>
        </td>
      </tr>

      <tr>
        <td<?php if ($MemStats['bytes'] / $MemStats['limit_maxbytes']> 0.85) {
    echo ' class="tooltip invalid"
          title="Evictions begin when storage exceeds 85%" ';
} ?>>Cache Storage:</td>

          <td>
            <?=Format::get_size($MemStats['bytes'])?>
            <span class="float_right">(<?=Text::number_format(($MemStats['bytes'] / $MemStats['limit_maxbytes']) * 100, 3);?>%)</span>
          </td>
      </tr>

      <tr>
        <td colspan="2"></td>
      </tr>

      <tr>
        <th colspan="2"><strong>Utilities</strong></th>
      </tr>

      <tr>
        <td>Cache:</td>
        <td>
          <form class="delete_form" name="cache" action="" method="post">
            <input type="hidden" name="action" value="service_stats" />
            <input type="hidden" name="auth"
              value="<?=$user['AuthKey']?>" />
            <input type="hidden" name="global_flush" value="1" />
            <input type="submit" class="button-primary" value="Flush" />
          </form>
        </td>
      </tr>
    </table>
  </div>

  <div class="permission_container">
    <table>
      <tr class="colhead">
        <th colspan="2">Activity</th>
      </tr>

      <tr>
        <th colspan="2"><strong>Total Reads</strong></th>
      </tr>

      <tr>
        <td>Cache:</td>
        <td>
          <?=Text::number_format($MemStats['cmd_get'])?>
        </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td>
          <?=Text::number_format($dbStats['Com_select']['Value'])?>
        </td>
      </tr>

      <tr>
        <th colspan="2"><strong>Total Writes</strong></th>
      </tr>
      <tr>
        <td>Cache:</td>
        <td>
          <?=Text::number_format($MemStats['cmd_set'])?>
        </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td>
          <?=Text::number_format($dbStats['Com_insert']['Value'] + $dbStats['Com_update']['Value'])?>
        </td>
      </tr>

      <tr>
        <td colspan="2"></td>
      </tr>

      <tr>
        <th colspan="2"><strong>Get/Select (Success)</strong></th>
      </tr>

      <tr>
        <td<?php if ($MemStats['get_hits'] / $MemStats['cmd_get'] < 0.7) {
    echo ' class="invalid" ' ;
} ?>>Cache:</td>

          <td>
            <?=Text::number_format($MemStats['get_hits'])?>
            <span class="float_right">(<?=Text::number_format(($MemStats['get_hits'] / $MemStats['cmd_get']) * 100, 3);?>%)</span>
          </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td>
          <?=Text::number_format($dbStats['Com_select']['Value'])?>
          <span class="float_right">(100.000%)</span>
        </td>
      </tr>

      <tr>
        <th colspan="2"><strong>Set/Insert (Success)</strong></th>
      </tr>

      <tr>
        <td>Cache:</td>
        <td>
          <?=Text::number_format($MemStats['cmd_set'])?>
          <span class="float_right">(100.000%)</span>
        </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td>
          <?=Text::number_format($dbStats['Com_insert']['Value'])?>
          <span class="float_right">(100.000%)</span>
        </td>
      </tr>

      <tr>
        <th colspan="2"><strong>Increment/Decrement (Success)</strong></th>
      </tr>

      <tr>
        <td<?php if ($MemStats['incr_hits']/($MemStats['incr_hits'] + $MemStats['incr_misses']) < 0.7) {
    echo ' class="invalid" ' ;
} ?>>Cache Increment:</td>

          <td>
            <?=Text::number_format($MemStats['incr_hits'])?>
            <span class="float_right">(<?=Text::number_format(($MemStats['incr_hits'] / ($MemStats['incr_hits'] + $MemStats['incr_misses'])) * 100, 3);?>%)</span>
          </td>
      </tr>

      <tr>
        <td<?php if ($MemStats['decr_hits'] / ($MemStats['decr_hits'] + $MemStats['decr_misses']) < 0.7) {
    echo ' class="invalid" ' ;
} ?>>Cache Decrement:</td>

          <td>
            <?=Text::number_format($MemStats['decr_hits'])?>
            <span class="float_right">(<?=Text::number_format(($MemStats['decr_hits'] / ($MemStats['decr_hits'] + $MemStats['decr_misses'])) * 100, 3);?>%)</span>
          </td>
      </tr>

      <tr>
        <th colspan="2"><strong>CAS/Update (Success)</strong></th>
      </tr>

      <tr>
        <td<?php if ($MemStats['cas_hits']> 0 && $MemStats['cas_hits'] / ($MemStats['cas_hits'] + $MemStats['cas_misses'])
          < 0.7) {
    echo ' class="tooltip invalid" title="More than 30% of the issued CAS commands were unnecessarily wasting time and resources." '
            ;
} elseif ($MemStats['cas_hits']==0) {
    echo ' class="tooltip notice" title="Disable CAS with the -C parameter and save resources since it is not used." '
            ;
} ?>>Cache:</td>

          <td>
            <?=Text::number_format($MemStats['cas_hits'])?>
            <span class="float_right">(
              <?php if ($MemStats['cas_hits'] > 0) {
    echo Text::number_format(($MemStats['cas_hits'] / ($MemStats['cas_hits'] + $MemStats['cas_misses'])) * 100, 3);
} else {
    echo '0.000';
} ?>%)
            </span>
          </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td>
          <?=Text::number_format($dbStats['Com_update']['Value'])?>
          <span class="float_right">(100.000%)</span>
        </td>
      </tr>

      <tr>
        <th colspan="2"><strong>Deletes (Success)</strong></th>
      </tr>

      <tr>
        <td<?php if ($MemStats['delete_hits'] / ($MemStats['delete_hits']+$MemStats['delete_misses']) < 0.7) {
    echo ' class="tooltip invalid" title="More than 30% of the issued delete commands were unnecessary wasting time and resources." '
          ;
} ?>>Cache:</td>

          <td>
            <?=Text::number_format($MemStats['delete_hits'])?>
            <span class="float_right">(<?=Text::number_format(($MemStats['delete_hits'] / ($MemStats['delete_hits'] + $MemStats['delete_misses'])) * 100, 3);?>%)</span>
          </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td>
          <?=Text::number_format($dbStats['Com_delete']['Value'])?>
          <span class="float_right">(100.000%)</span>
        </td>
      </tr>

      <tr>
        <td colspan="2"></td>
      </tr>

      <tr>
        <th colspan="2"><strong>Special</strong></th>
      </tr>

      <tr>
        <td<?php if ($MemStats['cmd_flush']> $MemStats['uptime'] / 7 * 24 * 3600) {
    echo ' class="tooltip invalid"
          title="Flushing the cache on a regular basis defeats the benefits of it, look into using cache transactions,
          or deletes instead of global flushing where possible." ';
} ?>>Cache Flushes:</td>

          <td>
            <?=Text::number_format($MemStats['cmd_flush'])?>
          </td>
      </tr>

      <tr>
        <td<?php if ($MemStats['evictions']> 0) {
    echo ' class="invalid" ';
} ?>>Cache Evicted:</td>

          <td>
            <?=Text::number_format($MemStats['evictions'])?>
          </td>
      </tr>

      <tr>
        <td<?php if ($dbStats['Slow_queries']['Value']> $dbStats['Questions']['Value'] / 7500) {
    echo ' class="tooltip
          invalid" title="1/7500 queries is allowed to be slow to minimize performance impact." ';
} ?>>Database Slow:
          </td>

          <td>
            <?=Text::number_format($dbStats['Slow_queries']['Value'])?>
          </td>
      </tr>

      <tr>
        <td colspan="2"></td>
      </tr>

      <tr>
        <th colspan="2"><strong>Data Read</strong></th>
      </tr>

      <tr>
        <td>Cache:</td>
        <td>
          <?=Format::get_size($MemStats['bytes_read'])?>
        </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td>
          <?=Format::get_size($dbStats['Bytes_received']['Value'])?>
        </td>
      </tr>

      <tr>
        <th colspan="2"><strong>Data Write</strong></th>
      </tr>

      <tr>
        <td>Cache:</td>
        <td>
          <?=Format::get_size($MemStats['bytes_written'])?>
        </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td>
          <?=Format::get_size($dbStats['Bytes_sent']['Value'])?>
        </td>
      </tr>
    </table>
  </div>

  <div class="permission_container">
    <table>
      <tr class="colhead">
        <th colspan="2">Concurrency</th>
      </tr>

      <tr>
        <th colspan="2"><strong>Total Reads</strong></th>
      </tr>

      <tr>
        <td<?php if (($MemStats['cmd_get'] / $MemStats['uptime']) * 5 < $dbStats['Com_select']['Value'] /
          $dbStats['Uptime']['Value']) {
    echo ' class="invalid" ' ;
} ?>>Cache:</td>

          <td>
            <?=Text::number_format($MemStats['cmd_get'] / $MemStats['uptime'], 5)?>/s
          </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td>
          <?=Text::number_format($dbStats['Com_select']['Value'] / $dbStats['Uptime']['Value'], 5)?>/s
        </td>
      </tr>

      <tr>
        <th colspan="2"><strong>Total Writes</strong></th>
      </tr>

      <tr>
        <td<?php if (($MemStats['cmd_set'] / $MemStats['uptime']) * 5 < ($dbStats['Com_insert']['Value'] +
          $dbStats['Com_update']['Value']) / $dbStats['Uptime']['Value']) {
    echo ' class="invalid" ' ;
} ?>>Cache:</td>

          <td>
            <?=Text::number_format($MemStats['cmd_set'] / $MemStats['uptime'], 5)?>/s
          </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td>
          <?=Text::number_format(($dbStats['Com_insert']['Value'] + $dbStats['Com_update']['Value']) / $dbStats['Uptime']['Value'], 5)?>/s
        </td>
      </tr>

      <tr>
        <td colspan="2"></td>
      </tr>

      <tr>
        <th colspan="2"><strong>Get/Select</strong></th>
      </tr>

      <tr>
        <td>Cache:</td>
        <td>
          <?=Text::number_format($MemStats['get_hits'] / $MemStats['uptime'], 5)?>/s
        </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td>
          <?=Text::number_format($dbStats['Com_select']['Value'] / $dbStats['Uptime']['Value'], 5)?>/s
        </td>
      </tr>

      <tr>
        <th colspan="2"><strong>Set/Insert</strong></th>
      </tr>

      <tr>
        <td>Cache:</td>
        <td>
          <?=Text::number_format($MemStats['cmd_set'] / $MemStats['uptime'], 5)?>/s
        </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td>
          <?=Text::number_format($dbStats['Com_insert']['Value'] / $dbStats['Uptime']['Value'], 5)?>/s
        </td>
      </tr>

      <tr>
        <th colspan="2"><strong>Increment/Decrement</strong></th>
      </tr>

      <tr>
        <td>Cache Increment:</td>
        <td>
          <?=Text::number_format($MemStats['incr_hits'] / $MemStats['uptime'], 5)?>/s
        </td>
      </tr>

      <tr>
        <td>Cache Decrement:</td>
        <td>
          <?=Text::number_format($MemStats['decr_hits'] / $MemStats['uptime'], 5)?>/s
        </td>
      </tr>

      <tr>
        <th colspan="2"><strong>CAS/Updates</strong></th>
      </tr>

      <tr>
        <td>Cache:</td>
        <td>
          <?=Text::number_format($MemStats['cas_hits'] / $MemStats['uptime'], 5)?>/s
        </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td>
          <?=Text::number_format($dbStats['Com_update']['Value'] / $dbStats['Uptime']['Value'], 5)?>/s
        </td>
      </tr>

      <tr>
        <th colspan="2"><strong>Deletes</strong></th>
      </tr>

      <tr>
        <td>Cache:</td>
        <td>
          <?=Text::number_format($MemStats['delete_hits'] / $MemStats['uptime'], 5)?>/s
        </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td>
          <?=Text::number_format($dbStats['Com_delete']['Value'] / $dbStats['Uptime']['Value'], 5)?>/s
        </td>
      </tr>

      <tr>
        <td colspan="2"></td>
      </tr>

      <tr>
        <th colspan="2"><strong>Special</strong></th>
      </tr>

      <tr>
        <td>Cache Flushes:</td>
        <td>
          <?=Text::number_format($MemStats['cmd_flush'] / $MemStats['uptime'], 5)?>/s
        </td>
      </tr>

      <tr>
        <td>Cache Evicted:</td>
        <td>
          <?=Text::number_format($MemStats['evictions'] / $MemStats['uptime'], 5)?>/s
        </td>
      </tr>

      <tr>
        <td>Database Slow:</td>
        <td>
          <?=Text::number_format($dbStats['Slow_queries']['Value'] / $dbStats['Uptime']['Value'], 5)?>/s
        </td>
      </tr>

      <tr>
        <td colspan="2"></td>
      </tr>

      <tr>
        <th colspan="2"><strong>Data Read</strong></th>
      </tr>

      <tr>
        <td>Cache:</td>
        <td>
          <?=Format::get_size($MemStats['bytes_read'] / $MemStats['uptime'])?>/s
        </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td>
          <?=Format::get_size($dbStats['Bytes_received']['Value'] / $dbStats['Uptime']['Value'])?>/s
        </td>
      </tr>

      <tr>
        <th colspan="2"><strong>Data Write</strong></th>
      </tr>

      <tr>
        <td>Cache:</td>
        <td>
          <?=Format::get_size($MemStats['bytes_written'] / $MemStats['uptime'])?>/s
        </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td><?=Format::get_size($dbStats['Bytes_sent']['Value'] / $dbStats['Uptime']['Value'])?>/s
        </td>
      </tr>
    </table>
  </div>
</div>
<?php View::footer();
