<?php
declare(strict_types=1);

/**
 * time_ago
 */
function time_ago($TimeStamp)
{
    if (!$TimeStamp) {
        return false;
    }
    if (!is_number($TimeStamp)) { // Assume that $TimeStamp is SQL timestamp
        $TimeStamp = strtotime($TimeStamp);
    }
    return time() - $TimeStamp;
}

/**
 * Returns a <span> by default but can optionally return the raw time
 * difference in text (e.g., "16 hours and 28 minutes", "1 day, 18 hours").
 */
function time_diff($TimeStamp, $Levels = 2, $Span = true, $Lowercase = false)
{
    if (!$TimeStamp) {
        return 'Never';
    }
    if (!is_number($TimeStamp)) { // Assume that $TimeStamp is SQL timestamp
        $TimeStamp = strtotime($TimeStamp);
    }
    $Time = time() - $TimeStamp;

    // If the time is negative, then it expires in the future.
    if ($Time < 0) {
        $Time = -$Time;
        $HideAgo = true;
    }

    $Years = floor($Time / 31556926); // seconds in one year
    $Remain = $Time - $Years * 31556926;

    $Months = floor($Remain / 2629744); // seconds in one month
    $Remain = $Remain - $Months * 2629744;

    $Weeks = floor($Remain / 604800); // seconds in one week
    $Remain = $Remain - $Weeks * 604800;

    $Days = floor($Remain / 86400); // seconds in one day
    $Remain = $Remain - $Days * 86400;

    $Hours=floor($Remain / 3600); // seconds in one hour
    $Remain = $Remain - $Hours * 3600;

    $Minutes = floor($Remain / 60); // seconds in one minute
    $Remain = $Remain - $Minutes * 60;

    $Seconds = $Remain;

    $Return = '';

    if ($Years > 0 && $Levels > 0) {
        $Return .= "$Years year".(($Years > 1) ? 's' : '');
        $Levels--;
    }

    if ($Months > 0 && $Levels > 0) {
        $Return .= ($Return != '') ? ', ' : '';
        $Return .= "$Months month".(($Months > 1) ? 's' : '');
        $Levels--;
    }

    if ($Weeks > 0 && $Levels > 0) {
        $Return .= ($Return != '') ? ', ' : '';
        $Return .= "$Weeks week".(($Weeks > 1) ? 's' : '');
        $Levels--;
    }

    if ($Days > 0 && $Levels > 0) {
        $Return .= ($Return != '') ? ', ' : '';
        $Return .= "$Days day".(($Days > 1) ? 's' : '');
        $Levels--;
    }

    if ($Hours > 0 && $Levels > 0) {
        $Return .= ($Return != '') ? ', ' : '';
        $Return .= "$Hours hour".(($Hours > 1) ? 's' : '');
        $Levels--;
    }

    if ($Minutes > 0 && $Levels > 0) {
        $Return .= ($Return != '') ? ' and ' : '';
        $Return .= "$Minutes min".(($Minutes > 1) ? 's' : '');
    }

    if ($Return == '') {
        $Return = 'Just now';
    } elseif (!isset($HideAgo)) {
        $Return .= ' ago';
    }

    if ($Lowercase) {
        $Return = strtolower($Return);
    }

    if ($Span) {
        return '<span class="time tooltip" title="'.date('M d Y, H:i', $TimeStamp).'">'.$Return.'</span>';
    } else {
        return $Return;
    }
}


/*************************
 * SQL utility functions *
 *************************/


/**
 * time_plus
 */
function time_plus($Offset)
{
    return date('Y-m-d H:i:s', time() + $Offset);
}

/**
 * time_minus
 */
function time_minus($Offset, $Fuzzy = false)
{
    if ($Fuzzy) {
        return date('Y-m-d 00:00:00', time() - $Offset);
    } else {
        return date('Y-m-d H:i:s', time() - $Offset);
    }
}

// This is never used anywhere with $timestamp set
// todo: Why don't we just use NOW() in the sql queries?
function sqltime($timestamp = null)
{
    return date('Y-m-d H:i:s', ($timestamp ?? time()));
}

/**
 * validDate
 */
function validDate($DateString)
{
    $DateTime = explode(' ', $DateString);
    if (count($DateTime) != 2) {
        return false;
    }

    list($Date, $Time) = $DateTime;
    $SplitTime = explode(':', $Time);
    if (count($SplitTime) != 3) {
        return false;
    }

    list($H, $M, $S) = $SplitTime;
    if ($H != 0 && !(is_number($H) && $H < 24 && $H >= 0)) {
        return false;
    }

    if ($M != 0 && !(is_number($M) && $M < 60 && $M >= 0)) {
        return false;
    }

    if ($S != 0 && !(is_number($S) && $S < 60 && $S >= 0)) {
        return false;
    }

    $SplitDate = explode('-', $Date);
    if (count($SplitDate) != 3) {
        return false;
    }

    list($Y, $M, $D) = $SplitDate;
    return checkDate($M, $D, $Y);
}

/**
 * is_valid_date
 */
function is_valid_date($Date)
{
    return is_valid_datetime($Date, 'Y-m-d');
}

/**
 * is_valid_time
 */
function is_valid_time($Time)
{
    return is_valid_datetime($Time, 'H:i');
}

/**
 * is_valid_datetime
 */
function is_valid_datetime($DateTime, $Format = 'Y-m-d H:i')
{
    $FormattedDateTime = DateTime::createFromFormat($Format, $DateTime);
    return $FormattedDateTime && $FormattedDateTime->format($Format) == $DateTime;
}
