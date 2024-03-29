<?php

#declare(strict_types=1);

# TODO: TURN THIS INTO A NORMAL DATABASE SEARCH

/*
$app = \Gazelle\App::go();

$debug = Debug::go();

if (!empty($_GET['page']) && is_numeric($_GET['page'])) {
    $Page = min(SPHINX_MAX_MATCHES / LOG_ENTRIES_PER_PAGE, $_GET['page']);
    $Offset = ($Page - 1) * LOG_ENTRIES_PER_PAGE;
} else {
    $Page = 1;
    $Offset = 0;
}

if (empty($_GET['search']) || trim($_GET['search']) === '') {
    $Log = $app->dbOld->query(
        "
    SELECT
      `ID`,
      `Message`,
      `Time`
    FROM
      `log`
    ORDER BY
      `ID`
    DESC
    LIMIT $Offset, ".LOG_ENTRIES_PER_PAGE
    );

    $NumResults = $app->dbOld->record_count();
    if (!$NumResults) {
        $TotalMatches = 0;
    } elseif ($NumResults === LOG_ENTRIES_PER_PAGE) {
        // This is a lot faster than SQL_CALC_FOUND_ROWS
        $SphQL = new SphinxqlQuery();
        $Result = $SphQL->select('id')->from('log, log_delta')->limit(0, 1, 1)->query();
        $TotalMatches = min(SPHINX_MAX_MATCHES, $Result->get_meta('total_found'));
    } else {
        $TotalMatches = $NumResults + $Offset;
    }
    $QueryStatus = 0;
} else {
    $Page = min(SPHINX_MAX_MATCHES / TORRENTS_PER_PAGE, $Page);
    $SphQL = new SphinxqlQuery();
    $SphQL->select('id')
    ->from('log, log_delta')
    ->where_match($_GET['search'], 'message')
    ->order_by('id', 'DESC')
    ->limit($Offset, LOG_ENTRIES_PER_PAGE, $Offset + LOG_ENTRIES_PER_PAGE);

    $Result = $SphQL->query();
    $debug['messages']->info('finished sphinxql query');

    if ($QueryStatus = $Result->Errno) {
        $QueryError = $Result->Error;
    }

    $NumResults = $Result->get_result_info('num_rows');
    $TotalMatches = min(SPHINX_MAX_MATCHES, $Result->get_meta('total_found'));

    if ($NumResults > 0) {
        $LogIDs = $Result->collect('id');
        $Log = $app->dbOld->query("
        SELECT
          `ID`,
          `Message`,
          `Time`
        FROM
          `log`
        WHERE
          `ID` IN(".implode(',', $LogIDs).")
        ORDER BY
          `ID`
        DESC
        ");
    } else {
        $Log = $app->dbOld->query("SET @nothing = 0");
    }
}
*/
