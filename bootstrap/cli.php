<?php
declare(strict_types=1);


/**
 * bootstrap from cli
 */

if (php_sapi_name() === "cli") {
    # load the dependencies
    require_once __DIR__."/../vendor/autoload.php";
    require_once __DIR__."/../config/app.php";
    require_once __DIR__."/utilities.php";

    # load the app
    $app = App::go();

    # start time
    $startTime = microtime(true);

    # start debug info
    Text::figlet($_SERVER["SCRIPT_FILENAME"], "green");

    # basic
    echo "\n" . php_uname();
    echo "\n" . date("r");
    echo "\n\n"; # clear

    # https://github.com/phplucidframe/console-table
    $table = new LucidFrame\Console\ConsoleTable();
    $table
        ->addHeader("php version")
        ->addHeader("zend version")
        ->addHeader("pid")

        ->addRow()
            ->addColumn(phpversion())
            ->addColumn(zend_version())
            ->addColumn(getmypid())

        ->display()
    ;


    # includes
    echo "\n"; # clear
    Text::figlet("includes", "yellow");

    $includes = get_included_files();
    foreach ($includes as $include) {
        if (!str_starts_with($include, "{$app->env->SERVER_ROOT}/vendor")) {
            echo "\n" . $include;
        }
    }

    # done
    echo "\n\n"; # clear
    foreach (range(1, 80) as $foo) {
        echo "=";
    }
    echo "\n\n"; # clear
}
