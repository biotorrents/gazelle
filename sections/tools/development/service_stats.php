<?php

#declare(strict_types = 1);


/**
 * cache stats admin page
 *
 * todo: rewrite this to show redis data,
 * and fold in the key management page as well
 */

$app = \Gazelle\App::go();

if (!check_perms('site_debug') || !check_perms('admin_clear_cache')) {
    error(403);
}

if (isset($_POST['global_flush'])) {
    authorize();
    $app->cache->flush();
}

$app->dbOld->prepared_query('SHOW GLOBAL STATUS');
$dbStats = $app->dbOld->to_array('Variable_name');
$MemStats = $app->cache->info();

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
          <?=\Gazelle\Text::float($MemStats['threads'])?>
          <span class="u-pull-right">(100.000%)</span>
        </td>
      </tr>

      <tr>
        <td<?php if ($dbStats['Threads_connected']['Value'] / $dbStats['Threads_created']['Value'] > 0.7) {
            echo '
          class="invalid" ';
        } ?>>Database:</td>

          <td>
            <?=\Gazelle\Text::float($dbStats['Threads_created']['Value'])?>
            <span class="u-pull-right">(<?=\Gazelle\Text::float(($dbStats['Threads_connected']['Value'] / $dbStats['Threads_created']['Value']) * 100, 3)?>%)</span>
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
          <?=\Gazelle\Text::float($MemStats['total_connections'])?>
        </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td>
          <?=\Gazelle\Text::float($dbStats['Connections']['Value'])?>
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
          <?=\Gazelle\Text::float($MemStats['curr_items'])?>
        </td>
      </tr>

      <tr>
        <td>Cache Total Index:</td>
        <td>
          <?=\Gazelle\Text::float($MemStats['total_items'])?>
        </td>
      </tr>

      <tr>
        <td<?php if ($MemStats['bytes'] / $MemStats['limit_maxbytes'] > 0.85) {
            echo ' class="tooltip invalid"
          title="Evictions begin when storage exceeds 85%" ';
        } ?>>Cache Storage:</td>

          <td>
            <?=\Gazelle\Format::get_size($MemStats['bytes'])?>
            <span class="u-pull-right">(<?=\Gazelle\Text::float(($MemStats['bytes'] / $MemStats['limit_maxbytes']) * 100, 3);?>%)</span>
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
            <input type="hidden" name="action" value="service_stats">
            <input type="hidden" name="auth"
              value="<?=$app->user->extra['AuthKey']?>">
            <input type="hidden" name="global_flush" value="1">
            <input type="submit" class="button-primary" value="Flush">
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
          <?=\Gazelle\Text::float($MemStats['cmd_get'])?>
        </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td>
          <?=\Gazelle\Text::float($dbStats['Com_select']['Value'])?>
        </td>
      </tr>

      <tr>
        <th colspan="2"><strong>Total Writes</strong></th>
      </tr>
      <tr>
        <td>Cache:</td>
        <td>
          <?=\Gazelle\Text::float($MemStats['cmd_set'])?>
        </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td>
          <?=\Gazelle\Text::float($dbStats['Com_insert']['Value'] + $dbStats['Com_update']['Value'])?>
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
            <?=\Gazelle\Text::float($MemStats['get_hits'])?>
            <span class="u-pull-right">(<?=\Gazelle\Text::float(($MemStats['get_hits'] / $MemStats['cmd_get']) * 100, 3);?>%)</span>
          </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td>
          <?=\Gazelle\Text::float($dbStats['Com_select']['Value'])?>
          <span class="u-pull-right">(100.000%)</span>
        </td>
      </tr>

      <tr>
        <th colspan="2"><strong>Set/Insert (Success)</strong></th>
      </tr>

      <tr>
        <td>Cache:</td>
        <td>
          <?=\Gazelle\Text::float($MemStats['cmd_set'])?>
          <span class="u-pull-right">(100.000%)</span>
        </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td>
          <?=\Gazelle\Text::float($dbStats['Com_insert']['Value'])?>
          <span class="u-pull-right">(100.000%)</span>
        </td>
      </tr>

      <tr>
        <th colspan="2"><strong>Increment/Decrement (Success)</strong></th>
      </tr>

      <tr>
        <td<?php if ($MemStats['incr_hits'] / ($MemStats['incr_hits'] + $MemStats['incr_misses']) < 0.7) {
            echo ' class="invalid" ' ;
        } ?>>Cache Increment:</td>

          <td>
            <?=\Gazelle\Text::float($MemStats['incr_hits'])?>
            <span class="u-pull-right">(<?=\Gazelle\Text::float(($MemStats['incr_hits'] / ($MemStats['incr_hits'] + $MemStats['incr_misses'])) * 100, 3);?>%)</span>
          </td>
      </tr>

      <tr>
        <td<?php if ($MemStats['decr_hits'] / ($MemStats['decr_hits'] + $MemStats['decr_misses']) < 0.7) {
            echo ' class="invalid" ' ;
        } ?>>Cache Decrement:</td>

          <td>
            <?=\Gazelle\Text::float($MemStats['decr_hits'])?>
            <span class="u-pull-right">(<?=\Gazelle\Text::float(($MemStats['decr_hits'] / ($MemStats['decr_hits'] + $MemStats['decr_misses'])) * 100, 3);?>%)</span>
          </td>
      </tr>

      <tr>
        <th colspan="2"><strong>CAS/Update (Success)</strong></th>
      </tr>

      <tr>
        <td<?php if ($MemStats['cas_hits'] > 0 && $MemStats['cas_hits'] / ($MemStats['cas_hits'] + $MemStats['cas_misses'])
                  < 0.7) {
            echo ' class="tooltip invalid" title="More than 30% of the issued CAS commands were unnecessarily wasting time and resources." '
            ;
        } elseif ($MemStats['cas_hits'] == 0) {
            echo ' class="tooltip notice" title="Disable CAS with the -C parameter and save resources since it is not used." '
            ;
        } ?>>Cache:</td>

          <td>
            <?=\Gazelle\Text::float($MemStats['cas_hits'])?>
            <span class="u-pull-right">(
              <?php if ($MemStats['cas_hits'] > 0) {
                  echo \Gazelle\Text::float(($MemStats['cas_hits'] / ($MemStats['cas_hits'] + $MemStats['cas_misses'])) * 100, 3);
              } else {
                  echo '0.000';
              } ?>%)
            </span>
          </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td>
          <?=\Gazelle\Text::float($dbStats['Com_update']['Value'])?>
          <span class="u-pull-right">(100.000%)</span>
        </td>
      </tr>

      <tr>
        <th colspan="2"><strong>Deletes (Success)</strong></th>
      </tr>

      <tr>
        <td<?php if ($MemStats['delete_hits'] / ($MemStats['delete_hits'] + $MemStats['delete_misses']) < 0.7) {
            echo ' class="tooltip invalid" title="More than 30% of the issued delete commands were unnecessary wasting time and resources." '
            ;
        } ?>>Cache:</td>

          <td>
            <?=\Gazelle\Text::float($MemStats['delete_hits'])?>
            <span class="u-pull-right">(<?=\Gazelle\Text::float(($MemStats['delete_hits'] / ($MemStats['delete_hits'] + $MemStats['delete_misses'])) * 100, 3);?>%)</span>
          </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td>
          <?=\Gazelle\Text::float($dbStats['Com_delete']['Value'])?>
          <span class="u-pull-right">(100.000%)</span>
        </td>
      </tr>

      <tr>
        <td colspan="2"></td>
      </tr>

      <tr>
        <th colspan="2"><strong>Special</strong></th>
      </tr>

      <tr>
        <td<?php if ($MemStats['cmd_flush'] > $MemStats['uptime'] / 7 * 24 * 3600) {
            echo ' class="tooltip invalid"
          title="Flushing the cache on a regular basis defeats the benefits of it, look into using cache transactions,
          or deletes instead of global flushing where possible." ';
        } ?>>Cache Flushes:</td>

          <td>
            <?=\Gazelle\Text::float($MemStats['cmd_flush'])?>
          </td>
      </tr>

      <tr>
        <td<?php if ($MemStats['evictions'] > 0) {
            echo ' class="invalid" ';
        } ?>>Cache Evicted:</td>

          <td>
            <?=\Gazelle\Text::float($MemStats['evictions'])?>
          </td>
      </tr>

      <tr>
        <td<?php if ($dbStats['Slow_queries']['Value'] > $dbStats['Questions']['Value'] / 7500) {
            echo ' class="tooltip
          invalid" title="1/7500 queries is allowed to be slow to minimize performance impact." ';
        } ?>>Database Slow:
          </td>

          <td>
            <?=\Gazelle\Text::float($dbStats['Slow_queries']['Value'])?>
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
          <?=\Gazelle\Format::get_size($MemStats['bytes_read'])?>
        </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td>
          <?=\Gazelle\Format::get_size($dbStats['Bytes_received']['Value'])?>
        </td>
      </tr>

      <tr>
        <th colspan="2"><strong>Data Write</strong></th>
      </tr>

      <tr>
        <td>Cache:</td>
        <td>
          <?=\Gazelle\Format::get_size($MemStats['bytes_written'])?>
        </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td>
          <?=\Gazelle\Format::get_size($dbStats['Bytes_sent']['Value'])?>
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
            <?=\Gazelle\Text::float($MemStats['cmd_get'] / $MemStats['uptime'], 5)?>/s
          </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td>
          <?=\Gazelle\Text::float($dbStats['Com_select']['Value'] / $dbStats['Uptime']['Value'], 5)?>/s
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
            <?=\Gazelle\Text::float($MemStats['cmd_set'] / $MemStats['uptime'], 5)?>/s
          </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td>
          <?=\Gazelle\Text::float(($dbStats['Com_insert']['Value'] + $dbStats['Com_update']['Value']) / $dbStats['Uptime']['Value'], 5)?>/s
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
          <?=\Gazelle\Text::float($MemStats['get_hits'] / $MemStats['uptime'], 5)?>/s
        </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td>
          <?=\Gazelle\Text::float($dbStats['Com_select']['Value'] / $dbStats['Uptime']['Value'], 5)?>/s
        </td>
      </tr>

      <tr>
        <th colspan="2"><strong>Set/Insert</strong></th>
      </tr>

      <tr>
        <td>Cache:</td>
        <td>
          <?=\Gazelle\Text::float($MemStats['cmd_set'] / $MemStats['uptime'], 5)?>/s
        </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td>
          <?=\Gazelle\Text::float($dbStats['Com_insert']['Value'] / $dbStats['Uptime']['Value'], 5)?>/s
        </td>
      </tr>

      <tr>
        <th colspan="2"><strong>Increment/Decrement</strong></th>
      </tr>

      <tr>
        <td>Cache Increment:</td>
        <td>
          <?=\Gazelle\Text::float($MemStats['incr_hits'] / $MemStats['uptime'], 5)?>/s
        </td>
      </tr>

      <tr>
        <td>Cache Decrement:</td>
        <td>
          <?=\Gazelle\Text::float($MemStats['decr_hits'] / $MemStats['uptime'], 5)?>/s
        </td>
      </tr>

      <tr>
        <th colspan="2"><strong>CAS/Updates</strong></th>
      </tr>

      <tr>
        <td>Cache:</td>
        <td>
          <?=\Gazelle\Text::float($MemStats['cas_hits'] / $MemStats['uptime'], 5)?>/s
        </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td>
          <?=\Gazelle\Text::float($dbStats['Com_update']['Value'] / $dbStats['Uptime']['Value'], 5)?>/s
        </td>
      </tr>

      <tr>
        <th colspan="2"><strong>Deletes</strong></th>
      </tr>

      <tr>
        <td>Cache:</td>
        <td>
          <?=\Gazelle\Text::float($MemStats['delete_hits'] / $MemStats['uptime'], 5)?>/s
        </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td>
          <?=\Gazelle\Text::float($dbStats['Com_delete']['Value'] / $dbStats['Uptime']['Value'], 5)?>/s
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
          <?=\Gazelle\Text::float($MemStats['cmd_flush'] / $MemStats['uptime'], 5)?>/s
        </td>
      </tr>

      <tr>
        <td>Cache Evicted:</td>
        <td>
          <?=\Gazelle\Text::float($MemStats['evictions'] / $MemStats['uptime'], 5)?>/s
        </td>
      </tr>

      <tr>
        <td>Database Slow:</td>
        <td>
          <?=\Gazelle\Text::float($dbStats['Slow_queries']['Value'] / $dbStats['Uptime']['Value'], 5)?>/s
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
          <?=\Gazelle\Format::get_size($MemStats['bytes_read'] / $MemStats['uptime'])?>/s
        </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td>
          <?=\Gazelle\Format::get_size($dbStats['Bytes_received']['Value'] / $dbStats['Uptime']['Value'])?>/s
        </td>
      </tr>

      <tr>
        <th colspan="2"><strong>Data Write</strong></th>
      </tr>

      <tr>
        <td>Cache:</td>
        <td>
          <?=\Gazelle\Format::get_size($MemStats['bytes_written'] / $MemStats['uptime'])?>/s
        </td>
      </tr>

      <tr>
        <td>Database:</td>
        <td><?=\Gazelle\Format::get_size($dbStats['Bytes_sent']['Value'] / $dbStats['Uptime']['Value'])?>/s
        </td>
      </tr>
    </table>
  </div>
</div>
<?php View::footer();
