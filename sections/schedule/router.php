<?php

declare(strict_types=1);

$app = App::go();

/**
 * Flight router
 * @see https://flightphp.com/learn
 */

# endpoints go here

# start the router
#Flight::start();


/** LEGACY ROUTES */


$ENV = ENV::go();

// This page is run every 15 minutes by cron
// todo: See if strict equality will break everything
set_time_limit(50000);
ob_end_flush();
gc_enable();

$ScheduleDebug = false;
$PCount = chop(shell_exec('/usr/bin/pgrep -cf schedule.php'));

// 3 because the cron job starts two processes and pgrep finds itself
if ($PCount > 3) {
    error("schedule.php is already running. Exiting ($PCount)\n", $NoHTML = true);
}

$AS = check_perms('admin_schedule');

function run_all_in($Dir)
{
    $Tasks = array_diff(scandir(serverRoot.'/sections/schedule/'.$Dir, 1), ['.', '..']);
    extract($GLOBALS);

    foreach ($Tasks as $Task) {
        $TimeStart = microtime(true);
        include($Dir.'/'.$Task);

        if ($ScheduleDebug) {
            echo $Dir.'/'.$Task.': '.Text::float(microtime(true)-$TimeStart, 3).($AS ? "<br>" : "\n");
        }
    }
}

if ((!isset($_REQUEST['key']) || $_REQUEST['key'] !== $ENV->getPriv('scheduleKey'))
    #|| (!isset($argv[1]) || $argv[1] !== $ENV->getPriv('scheduleKey'))
    && !$AS) {
    error(403);
}

if ($AS) {
    authorize();
    View::header();
    echo '<div class="box">';
}
$ASBreak = $AS ? '<br />' : "\n";

$app->dbOld->query("
SELECT
  `NextHour`,
  `NextDay`,
  `NextBiWeekly`
FROM
  `schedule`
");
list($Hour, $Day, $BiWeek) = $app->dbOld->next_record();

$NextHour = date('H');
$NextDay = date('d');
$NextBiWeek = (date('d') < 22 && date('d') >= 8) ? 22 : 8;

$app->dbOld->query("
UPDATE
  `schedule`
SET
  `NextHour` = $NextHour,
  `NextDay` = $NextDay,
  `NextBiWeekly` = $NextBiWeek
");

$sqltime = sqltime();
echo "$sqltime $ASBreak";

// Run every time
if (!(isset($_GET['notevery']) && $_GET['notevery'])) {
    run_all_in('every');
    echo "Ran every-time functions $ASBreak";
}

// Run every hour
if ($Hour !== $NextHour || (isset($_GET['runhour']) && $_GET['runhour'])) {
    run_all_in('hourly');
    echo "Ran hourly functions $ASBreak";
}

// Run every day
if ($Day !== $NextDay || (isset($_GET['runday']) && $_GET['runday'])) {
    run_all_in('daily');
    echo "Ran daily functions $ASBreak";
}

// Run every week
if (($Day !== $NextDay && date('w') === 0) || (isset($_GET['runweek']) && $_GET['runweek'])) {
    run_all_in('weekly');
    echo "Ran weekly functions $ASBreak";
}

// Run twice per month
if ($BiWeek !== $NextBiWeek || (isset($_GET['runbiweek']) && $_GET['runbiweek'])) {
    run_all_in('biweekly');
    echo "Ran bi-weekly functions $ASBreak";
}

// Run every month
if (($BiWeek !== $NextBiWeek && $BiWeek === 8) || (isset($_GET['runmonth']) && $_GET['runmonth'])) {
    run_all_in('monthly');
    echo "Ran monthly functions $ASBreak";
}

// Run on request
if (isset($_GET['runmanual']) && $_GET['runmanual']) {
    run_all_in('manually');
    echo "Ran manual functions $ASBreak";
}

if ($AS) {
    echo '</div>';
    View::footer();
} else {
    echo "-------------------------\n\n";
}
