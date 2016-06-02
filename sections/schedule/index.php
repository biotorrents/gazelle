<?
//--------------Schedule page -------------------------------------------//
// This page is run every 15 minutes by cron.

set_time_limit(50000);
ob_end_flush();
gc_enable();

$ScheduleDebug = false;

$PCount = chop(shell_exec("/usr/bin/pgrep -cf schedule.php"));
if ($PCount > 3) {  // 3 because the cron job starts two processes and pgrep finds itself
	die("schedule.php is already running. Exiting ($PCount)\n");
}

$AS = check_perms('admin_schedule');

function run_all_in($Dir) {
  $Tasks = array_diff(scandir(SERVER_ROOT.'/sections/schedule/'.$Dir, 1), array('.', '..'));
  extract($GLOBALS);
  foreach ($Tasks as $Task) {
    $TimeStart = microtime(true);
    include($Dir.'/'.$Task);
    if ($ScheduleDebug) {
      echo $Dir.'/'.$Task.': '.number_format(microtime(true)-$TimeStart,3).($AS?"<br>":"\n");
    }
  }
}

if ((!isset($_REQUEST['key']) || $_REQUEST['key'] != SCHEDULE_KEY) && !$AS) {
	error(403);
}

if ($AS) {
	authorize();
	View::show_header();
	echo '<div class="box">';
}

$DB->query("
	SELECT NextHour, NextDay, NextBiWeekly
	FROM schedule");
list($Hour, $Day, $BiWeek) = $DB->next_record();

$NextHour = date('H', time(date('H') + 1, 0, 0, date('m'), date('d'), date('Y')));
$NextDay = date('d', time(0, 0, 0, date('m'), date('d') + 1, date('Y')));
$NextBiWeek = (date('d') < 22 && date('d') >= 8) ? 22 : 8;

$DB->query("
	UPDATE schedule
	SET NextHour = $NextHour, NextDay = $NextDay, NextBiWeekly = $NextBiWeek");

$sqltime = sqltime();

echo "$sqltime".($AS?"<br>":"\n");

//-------------- Run every time ------------------------------------------//
if (!(isset($_GET['notevery']) && $_GET['notevery'])) {
  run_all_in('every');
  echo "Ran every-time functions".($AS?'<br>':"\n");
}

//-------------- Run every hour ------------------------------------------//
if ($Hour != $NextHour || (isset($_GET['runhour']) && $_GET['runhour'])) {
  run_all_in('hourly');
	echo "Ran hourly functions".($AS?'<br>':"\n");
}

//-------------- Run every day -------------------------------------------//
if ($Day != $NextDay || (isset($_GET['runday']) && $_GET['runday'])) {
  run_all_in('daily');
	echo "Ran daily functions".($AS?'<br>':"\n");
}

//-------------- Run every week -------------------------------------------//
if (($Day != $NextDay && date('w') == 0) || (isset($_GET['runweek']) && $_GET['runweek'])) {
  run_all_in('weekly');
  echo "Ran weekly functions".($AS?'<br>':"\n");
}

//--------------- Run twice per month -------------------------------------//
if ($BiWeek != $NextBiWeek || (isset($_GET['runbiweek']) && $_GET['runbiweek'])) {
  run_all_in('biweekly');
	echo "Ran bi-weekly functions".($AS?'<br>':"\n");
}

//---------------- Run every month -----------------------------------------//
if (($BiWeek != $NextBiWeek && $BiWeek == 8) || (isset($_GET['runmonth']) && $_GET['runmonth'])) {
  run_all_in('monthly');
	echo "Ran monthly functions".($AS?'<br>':"\n");
}

//---------------- Run on request ------------------------------------------//
if (isset($_GET['runmanual']) && $_GET['runmanual']) {
  run_all_in('manually');
	echo "Ran manual functions".($AS?'<br>':"\n");
}

if ($AS) {
	echo '</div>';
	View::show_footer();
} else {
  echo "-------------------------\n\n";
}
?>
