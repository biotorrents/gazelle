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
        return (!self::$instance)
            ? self::$instance = self::factory($options)
            : self::$instance;

        /*
        # Cannot use object of type Debug as array
        if (!self::$instance) {
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

        # load debugbar
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
     * gitInfo
     *
     * Prints out some information about the current git commit.
     *
     * @return array
     */
    public static function gitInfo(): array
    {
        return [
            "branch" => system("git branch --show-current"),
            "commit" => system("git rev-parse HEAD"),
            "date" => system("git show -s --format=%ci HEAD"),
            "message" => system("git show -s --format=%s HEAD"),
            "author" => system("git show -s --format=%an HEAD"),
            "email" => system("git show -s --format=%ae HEAD"),
        ];
    }


    /** old */


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
          <?=\Gazelle\Text::float(count($OcelotRequests))?>
          Ocelot requests:
        </strong>
    </td>
  </tr>
</table>

<table id="debug_ocelot" class="debug_table hidden">
  <?php foreach ($OcelotRequests as $i => $Request) { ?>
  <tr>
    <td class="debug_data debug_ocelot_data">
        <a data-toggle-target="#debug_ocelot_<?=$i?>"><?=\Gazelle\Text::esc($Request["path"])?></a>
        <pre id="debug_ocelot_<?=$i?>"
          class="hidden"><?=\Gazelle\Text::esc($Request["response"])?></pre>
    </td>

    <td class="debug_info" style="width: 100px;">
        <?=\Gazelle\Text::esc($Request["status"])?>
    </td>
    <td class="debug_info debug_timing" style="width: 100px;">
        <?=\Gazelle\Text::float($Request["time"], 5)?> ms
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
          <?=\Gazelle\Text::float(count($Errors))?>
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
        <?=\Gazelle\Text::esc($Call)?>(<?=\Gazelle\Text::esc($Args)?>)
    </td>
    <td class="debug_data debug_error_data">
        <?=\Gazelle\Text::esc($Error)?>
    </td>
    <td>
        <?=\Gazelle\Text::esc($Location)?>
    </td>
  </tr>
  <?php
    } ?>
</table>
<?php
*/
    }
} # class


/** */


/**
 * FilesCollector
 *
 * Returns includes in reverse order and a file count.
 *
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


/** */


/**
 * DatabaseCollector
 *
 * Basically a simplified version of MessagesCollector.
 *
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


    /**
     * addMessage
     */
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


    /**
     * isHtmlVarDumperUsed
     */
    public function isHtmlVarDumperUsed()
    {
        return false;
    }
} # class
