<?php

declare(strict_types=1);

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

/**
 * Debug
 *
 * Converted to a singleton class.
 * Uses DebugBar to handle event tracking.
 *
 * @see http://phpdebugbar.com/docs/
 */

class Debug # extends DebugBar\StandardDebugBar
{
    # singleton
    private static $instance = null;


    /**
     * __functions
     */
    public function __construct()
    {
        return;
    }

    public function __clone()
    {
        return trigger_error(
            "clone not allowed",
            E_USER_ERROR
        );
    }

    public function __wakeup()
    {
        return trigger_error(
            "wakeup not allowed",
            E_USER_ERROR
        );
    }


    /**
     * go
     */
    public static function go(array $options = [])
    {
        return (self::$instance === null)
            ? self::$instance = self::factory()
            : self::$instance;

        /*
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->factory($options);
        }

        return self::$instance;
        */
    }


    /**
     * factory
     */
    private static function factory(array $options = [])
    {
        $app = \Gazelle\App::go();

        # https://stackify.com/display-php-errors/
        if ($app->env->dev) {
            /*
            ini_set("display_errors", 1);
            ini_set("display_startup_errors", 1);
            error_reporting(E_ALL);
            */
        }

        $debugBar = new DebugBar\StandardDebugBar();

        # custom collectors
        $debugBar->addCollector(new DatabaseCollector());
        $debugBar->addCollector(new FilesCollector());

        /*
        # http://phpdebugbar.com/docs/bridge-collectors.html#twig
        $debugBar->addCollector(
            new DebugBar\Bridge\TwigProfileCollector(
                $app->twig,
                DebugBar\DataCollector\TimeDataCollector
            )
        );
        */

        return $debugBar;
    }


    /**
     *
     * OLD CLASS STARTS HERE
     *
     */


    /**
     * log_var
     */
    public function log_var($Var, $VarName = false)
    {
        /*
          $BackTrace = debug_backtrace();
          $ID = Text::random(5);

          if (!$VarName) {
              $VarName = $ID;
          }

          $File = array("path" => substr($BackTrace[0]["file"], strlen(serverRoot)), "line" => $BackTrace[0]["line"]);
          $this->LoggedVars[$ID] = array($VarName => array("bt" => $File, "data" => $Var));
          */
    }


    /*****************
     * Data wrappers *
     *****************/


    /**
     * get_sphinxql_queries
     */
    public function get_sphinxql_queries()
    {
        /*
          if (class_exists("Sphinxql")) {
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
          if (class_exists("Sphinxql")) {
              return Sphinxql::$Time;
          }
          */
    }

    /**
     * get_ocelot_requests
     */
    public function get_ocelot_requests()
    {
        /*
          if (class_exists("Tracker")) {
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
          <?=Text::float(count($OcelotRequests))?>
          Ocelot requests:
        </strong>
    </td>
  </tr>
</table>

<table id="debug_ocelot" class="debug_table hidden">
  <?php foreach ($OcelotRequests as $i => $Request) { ?>
  <tr>
    <td class="debug_data debug_ocelot_data">
        <a data-toggle-target="#debug_ocelot_<?=$i?>"><?=Text::esc($Request["path"])?></a>
        <pre id="debug_ocelot_<?=$i?>"
          class="hidden"><?=Text::esc($Request["response"])?></pre>
    </td>

    <td class="debug_info" style="width: 100px;">
        <?=Text::esc($Request["status"])?>
    </td>
    <td class="debug_info debug_timing" style="width: 100px;">
        <?=Text::float($Request["time"], 5)?> ms
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
          <a href="#" onclick="$(this).parents(".layout").next("#debug_error").gtoggle(); return false;"
            class="brackets">View</a>
          <?=Text::float(count($Errors))?>
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
        <?=Text::esc($Call)?>(<?=Text::esc($Args)?>)
    </td>
    <td class="debug_data debug_error_data">
        <?=Text::esc($Error)?>
    </td>
    <td>
        <?=Text::esc($Location)?>
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
          $Header = "Searches";
          if (!is_array($Queries)) {
              $Queries = $this->get_sphinxql_queries();
              $Header .= " (".Text::float($this->get_sphinxql_time(), 5)." ms)";
          }

          if (empty($Queries)) {
              return;
          }
          $Header = " ".Text::float(count($Queries))." $Header:"; ?>

<table class="layout">
  <tr>
    <td>
        <strong>
          <a href="#" onclick="$(this).parents(".layout").next("#debug_sphinx").gtoggle(); return false;"
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
        <pre><?=str_replace("\t", "  ", $Params)?></pre>
    </td>
    <td class="debug_info debug_sphinx_time" style="width: 130px;"><?=Text::float($Time, 5)?> ms</td>
  </tr>
  <?php
    } ?>
</table>
<?php
*/
    }
} # class


/**
 * Simple included files collector
 *
 * Returns includes in reverse order and a file count.
 * @see https://github.com/barryvdh/laravel-debugbar/blob/master/src/DataCollector/FilesCollector.php
 */

class FilesCollector extends DataCollector implements Renderable
{
    /**
     * collect
     */
    public function collect()
    {
        $includes = [];
        $files = get_included_files();

        foreach ($files as $file) {
            # Skip the files from Composer
            if (strpos($file, "/vendor/") !== false) {
                continue;
            } else {
                $includes[] = [
                    "message" => $file,
                    "is_string" => true,
                ];
            }
        }

        return [
          "count" => count($includes),
          "messages" => array_reverse($includes),
        ];
    }

    /**
     * getWidgets
     */
    public function getWidgets()
    {
        $name = $this->getName();

        return [
            $name => [
                "icon" => "folder-open",
                "widget" => "PhpDebugBar.Widgets.MessagesWidget",
                "map" => "{$name}.messages",
                "default" => "{}"
            ],

            "{$name}:badge" => [
                "map" => "{$name}.count",
                "default" => "null"
            ]
        ];
    }

    /**
     * getName
     */
    public function getName()
    {
        return "files";
    }
} # class


/**
 * Database query collector
 *
 * Basically a simplified version of MessagesCollector.
 * @see https://github.com/maximebf/php-debugbar/blob/master/src/DebugBar/DataCollector/MessagesCollector.php
 */

class DatabaseCollector extends DataCollector implements Renderable
{
    # collectors
    private $messages = [];


    /**
     * collect
     */
    public function collect()
    {
        $messages = $this->getMessages();

        return [
          "count" => count($messages),
          "messages" => $messages
        ];
    }

    /**
     * getMessages
     */
    public function getMessages()
    {
        $messages = $this->messages;

        // sort messages by their timestamp
        usort($messages, function ($a, $b) {
            if ($a["time"] === $b["time"]) {
                return 0;
            }

            return $a["time"] < $b["time"] ? -1 : 1;
        });

        return $messages;
    }

    /**
     * log
     */
    public function log($message)
    {
        return $this->addMessage($message);
    }

    public function addMessage($message, $isString = true)
    {
        $messageText = $message;
        $messageHtml = null;

        if (!is_string($message)) {
            // Send both text and HTML representations; the text version is used for searches
            $messageText = $this->getDataFormatter()->formatVar($message);

            if ($this->isHtmlVarDumperUsed()) {
                $messageHtml = $this->getVarDumper()->renderVar($message);
            }

            $isString = false;
        }

        $this->messages[] = [
          "message" => $messageText,
          "message_html" => $messageHtml,
          "is_string" => $isString,
          "time" => microtime(true)
        ];
    }

    /**
     * getWidgets
     */
    public function getWidgets()
    {
        $name = $this->getName();

        return [
            $name => [
                "icon" => "database",
                "widget" => "PhpDebugBar.Widgets.MessagesWidget",
                "map" => "{$name}.messages",
                "default" => "[]"
            ],

            "{$name}:badge" => [
                "map" => "{$name}.count",
                "default" => "null"
            ]
        ];
    }

    /**
     * getName
     */
    public function getName()
    {
        return "database";
    }
} # class
