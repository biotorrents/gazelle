<?php
#declare(strict_types=1);

/**
 * Debug class
 *
 * Converted to a singleton class.
 * Uses DebugBar to handle event tracking.
 *
 * @see http://phpdebugbar.com/docs/
 */

class Debug
{
    # Singleton
    private static $Debug = null;

    /**
     * __functions
     */
    private function __construct()
    {
        return;
    }
  
    private function __clone()
    {
        return trigger_error(
            'clone not allowed',
            E_USER_ERROR
        );
    }
  
    public function __wakeup()
    {
        return trigger_error(
            'wakeup not allowed',
            E_USER_ERROR
        );
    }
  
  
    /**
     * go
     */
    public static function go()
    {
        return (self::$Debug === null)
            ? self::$Debug = Debug::factory()
            : self::$Debug;
    }


    /**
     * factory
     */
    private static function factory()
    {
        $ENV = ENV::go();

        # https://stackify.com/display-php-errors/
        if ($ENV->DEV) {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        }

        $DebugBar = new \DebugBar\StandardDebugBar();

        # Custom collectors
        $DebugBar->addCollector(new \DebugBar\DataCollector\MessagesCollector('upload'));

        # http://phpdebugbar.com/docs/bridge-collectors.html#twig
        /*
        $DebugBar->addCollector(
            new \DebugBar\Bridge\TwigProfileCollector(
                \Twig::go(),
                \DebugBar\DataCollector\TimeDataCollector
            )
        );
        */

        return $DebugBar;
    }


    /**
     *
     * OLD CLASS STARTS HERE
     *
     */


    public $Errors = [];
    public $Flags = [];
    public $Perf = [];
    private $LoggedVars = [];

    /**
     * profile
     */
    public function profile($Automatic = '')
    {
        /*
          $Reason = [];

          if (!empty($Automatic)) {
              $Reason[] = $Automatic;
          }

          $CacheStatus = G::$Cache->server_status();
          if (in_array(0, $CacheStatus) && !G::$Cache->get_value('cache_fail_reported')) {
              // Limit to max one report every 15 minutes to avoid massive debug spam
              G::$Cache->cache_value('cache_fail_reported', true, 900);
              $Reason[] = "Cache server error";
          }

          if (isset($_REQUEST['profile'])) {
              $Reason[] = 'Requested by ' . G::$LoggedUser['Username'];
          }

          $this->Perf['Memory usage'] = (($Ram>>10) / 1024).' MB';
          $this->Perf['Page process time'] = number_format($Micro / 1000, 3).' s';

          if (isset($Reason[0])) {
              $this->log_var($CacheStatus, 'Cache server status');
              $this->analysis(implode(', ', $Reason));
              return true;
          }
          return false;
          */
    }

    /**
     * analysis
     */
    public function analysis($Message, $Report = '', $Time = 43200)
    {
        /*
          global $Document;
          if (empty($Report)) {
              $Report = $Message;
          }
          $Identifier = Users::make_secret(5);
          G::$Cache->cache_value(
              'analysis_'.$Identifier,
              array(
                  'url' => $_SERVER['REQUEST_URI'],
                  'message' => $Report,
                  'errors' => $this->get_errors(true),
                  'queries' => $this->get_queries(),
                  'cache' => $this->get_cache_keys(),
                  'vars' => $this->get_logged_vars(),
                  'ocelot' => $this->get_ocelot_requests()
              ),
              $Time
          );

          $RequestURI = !empty($_SERVER['REQUEST_URI']) ? substr($_SERVER['REQUEST_URI'], 1) : '';
          send_irc(DEBUG_CHAN, "$Message $Document ".site_url()."tools.php?action=analysis&case=$Identifier ".site_url().$RequestURI);
          */
    }


    /**
     * log_var
     */
    public function log_var($Var, $VarName = false)
    {
        /*
          $BackTrace = debug_backtrace();
          $ID = Users::make_secret(5);

          if (!$VarName) {
              $VarName = $ID;
          }

          $File = array('path' => substr($BackTrace[0]['file'], strlen(SERVER_ROOT)), 'line' => $BackTrace[0]['line']);
          $this->LoggedVars[$ID] = array($VarName => array('bt' => $File, 'data' => $Var));
          */
    }


    /*****************
     * Data wrappers *
     *****************/


    /**
     * get_errors
     */
    public function get_errors($Light = false)
    {
        /*
          // Because the cache can't take some of these variables
          if ($Light) {
              foreach ($this->Errors as $Key => $Value) {
                  $this->Errors[$Key][3] = '';
              }
          }
          return $this->Errors;
          */
    }

    /**
     * get_cache_time
     */
    public function get_cache_time()
    {
        #return G::$Cache->Time;
    }

    /**
     * get_cache_keys
     */
    public function get_cache_keys()
    {
        #return array_keys(G::$Cache->CacheHits);
    }

    /**
     * get_sphinxql_queries
     */
    public function get_sphinxql_queries()
    {
        /*
          if (class_exists('Sphinxql')) {
              return Sphinxql::$Queries;
          }
          */
    }

    /**
     * get_sphinxql_time
     */
    public function get_sphinxql_time()
    {
        /*
          if (class_exists('Sphinxql')) {
              return Sphinxql::$Time;
          }
          */
    }

    /**
     * get_queries
     */
    public function get_queries()
    {
        #return G::$DB->Queries;
    }

    /**
     * get_query_time
     */
    public function get_query_time()
    {
        #return G::$DB->Time;
    }

    /**
     * get_logged_vars
     */
    public function get_logged_vars()
    {
        #return $this->LoggedVars;
    }

    /**
     * get_ocelot_requests
     */
    public function get_ocelot_requests()
    {
        /*
          if (class_exists('Tracker')) {
              return Tracker::$Requests;
          }
          */
    }


    /*********************
     * Output formatting *
     *********************/


    /**
     * ocelot_table
     */
    public function ocelot_table($OcelotRequests = false)
    {
        /*
          if (!is_array($OcelotRequests)) {
              $OcelotRequests = $this->get_ocelot_requests();
          }

          if (empty($OcelotRequests)) {
              return;
          } ?>

<table class="layout">
  <tr>
    <td>
        <strong>
          <a data-toggle-target="#debug_ocelot" class="brackets">View</a>
          <?=number_format(count($OcelotRequests))?>
          Ocelot requests:
        </strong>
    </td>
  </tr>
</table>

<table id="debug_ocelot" class="debug_table hidden">
  <?php foreach ($OcelotRequests as $i => $Request) { ?>
  <tr>
    <td class="debug_data debug_ocelot_data">
        <a data-toggle-target="#debug_ocelot_<?=$i?>"><?=esc($Request['path'])?></a>
        <pre id="debug_ocelot_<?=$i?>"
          class="hidden"><?=esc($Request['response'])?></pre>
    </td>

    <td class="debug_info" style="width: 100px;">
        <?=esc($Request['status'])?>
    </td>
    <td class="debug_info debug_timing" style="width: 100px;">
        <?=number_format($Request['time'], 5)?> ms
    </td>
  </tr>
  <?php } ?>
</table>
<?php
*/
    }

    /**
     * cache_table
     */
    public function cache_table($CacheKeys = false)
    {
        /*
          $Header = 'Cache Keys';
          if (!is_array($CacheKeys)) {
              $CacheKeys = $this->get_cache_keys();
              $Header .= ' ('.number_format($this->get_cache_time(), 5).' ms)';
          }

          if (empty($CacheKeys)) {
              return;
          }
          $Header = ' '.number_format(count($CacheKeys))." $Header:"; ?>

<table class="layout">
  <tr>
    <td>
        <strong>
          <a href="#" onclick="$(this).parents('.layout').next('#debug_cache').gtoggle(); return false;"
            class="brackets">View</a>
          <?=$Header?>
        </strong>
    </td>
  </tr>
</table>

<table id="debug_cache" class="debug_table hidden">
  <?php foreach ($CacheKeys as $Key) { ?>
  <tr>
    <td class="label nobr debug_info debug_cache_key">
        <a href="#"
          onclick="$('#debug_cache_<?=$Key?>').gtoggle(); return false;"><?=esc($Key)?></a>
        <a href="tools.php?action=clear_cache&amp;key=<?=$Key?>&amp;type=clear"
          target="_blank" class="brackets tooltip" title="Clear this cache key">Clear</a>
    </td>
    <td class="debug_data debug_cache_data">
        <pre id="debug_cache_<?=$Key?>" class="hidden">
<?=esc(print_r(G::$Cache->get_value($Key, true), true))?>
          </pre>
    </td>
  </tr>
  <?php } ?>
</table>
<?php
*/
    }

    /**
     * error_table
     */
    public function error_table($Errors = false)
    {
        /*
          if (!is_array($Errors)) {
              $Errors = $this->get_errors();
          }

          if (empty($Errors)) {
              return;
          } ?>

<table class="layout">
  <tr>
    <td>
        <strong>
          <a href="#" onclick="$(this).parents('.layout').next('#debug_error').gtoggle(); return false;"
            class="brackets">View</a>
          <?=number_format(count($Errors))?>
          Errors:
        </strong>
    </td>
  </tr>
</table>
<table id="debug_error" class="debug_table hidden">
  <?php
    foreach ($Errors as $Error) {
          list($Error, $Location, $Call, $Args) = $Error; ?>
  <tr class="valign_top">
    <td class="debug_info debug_error_call">
        <?=esc($Call)?>(<?=esc($Args)?>)
    </td>
    <td class="debug_data debug_error_data">
        <?=esc($Error)?>
    </td>
    <td>
        <?=esc($Location)?>
    </td>
  </tr>
  <?php
    } ?>
</table>
<?php
*/
    }

    /**
     * query_table
     */
    public function query_table($Queries=false)
    {
        /*
          $Header = 'Queries';
          if (!is_array($Queries)) {
              $Queries = $this->get_queries();
              $Header .= ' ('.number_format($this->get_query_time(), 5).' ms)';
          }

          if (empty($Queries)) {
              return;
          }
          $Header = ' '.number_format(count($Queries))." $Header:"; ?>

<table class="layout">
  <tr>
    <td>
        <strong>
          <a href="#" onclick="$(this).parents('.layout').next('#debug_database').gtoggle(); return false;"
            class="brackets">View</a>
          <?=$Header?>
        </strong>
    </td>
  </tr>
</table>

<table id="debug_database" class="debug_table hidden">
  <?php
    foreach ($Queries as $Query) {
          $SQL = $Query[0] ?? null;
          $Time = $Query[1] ?? null;
          $Warnings = $Query[2] ?? null;

          if ($Warnings !== null) {
              $Warnings = implode('<br />', $Warnings);
          } ?>

  <tr class="valign_top">
    <td class="debug_data debug_query_data">
        <div><?=str_replace("\t", '&nbsp;&nbsp;', nl2br(esc(trim($SQL))))?>
        </div>
    </td>

    <td class="debug_info debug_query_time" style="width: 130px;"><?=number_format($Time, 5)?> ms</td>
    <td class="debug_info debug_query_warnings"><?=$Warnings?>
    </td>
  </tr>
  <?php
    } ?>
</table>
<?php
*/
    }

    /**
     * sphinx_table
     */
    public function sphinx_table($Queries = false)
    {
        /*
          $Header = 'Searches';
          if (!is_array($Queries)) {
              $Queries = $this->get_sphinxql_queries();
              $Header .= ' ('.number_format($this->get_sphinxql_time(), 5).' ms)';
          }

          if (empty($Queries)) {
              return;
          }
          $Header = ' '.number_format(count($Queries))." $Header:"; ?>

<table class="layout">
  <tr>
    <td>
        <strong>
          <a href="#" onclick="$(this).parents('.layout').next('#debug_sphinx').gtoggle(); return false;"
            class="brackets">View</a>
          <?=$Header?>
        </strong>
    </td>
  </tr>
</table>
<table id="debug_sphinx" class="debug_table hidden">
  <?php
    foreach ($Queries as $Query) {
          list($Params, $Time) = $Query; ?>
  <tr class="valign_top">
    <td class="debug_data debug_sphinx_data">
        <pre><?=str_replace("\t", '  ', $Params)?></pre>
    </td>
    <td class="debug_info debug_sphinx_time" style="width: 130px;"><?=number_format($Time, 5)?> ms</td>
  </tr>
  <?php
    } ?>
</table>
<?php
*/
    }

    /**
     * vars_table
     */
    public function vars_table($Vars = false)
    {
        /*
          $Header = 'Logged Variables';
          if (empty($Vars)) {
              if (empty($this->LoggedVars)) {
                  return;
              }
              $Vars = $this->LoggedVars;
          }
          $Header = ' '.number_format(count($Vars))." $Header:"; ?>

<table class="layout">
  <tr>
    <td>
        <strong>
          <a href="#" onclick="$(this).parents('.layout').next('#debug_loggedvars').gtoggle(); return false;"
            class="brackets">View</a>
          <?=$Header?>
        </strong>
    </td>
  </tr>
</table>

<table id="debug_loggedvars" class="debug_table hidden">
  <?php
    foreach ($Vars as $ID => $Var) {
          $Key = key($Var);
          $Data = current($Var);
          $Size = count($Data['data']); ?>
  <tr>
    <td class="debug_info debug_loggedvars_name">
        <a href="#"
          onclick="$('#debug_loggedvars_<?=$ID?>').gtoggle(); return false;"><?=esc($Key)?></a>
        (<?=$Size . ($Size == 1 ? ' element' : ' elements')?>)
        <div>
          <?=$Data['bt']['path'].':'.$Data['bt']['line']; ?>
        </div>
    </td>
    <td class="debug_data debug_loggedvars_data">
        <pre id="debug_loggedvars_<?=$ID?>" class="hidden">
<?=esc(print_r($Data['data'], true))?>
          </pre>
    </td>
  </tr>
  <?php
    } ?>
</table>
<?php
*/
    }
}
