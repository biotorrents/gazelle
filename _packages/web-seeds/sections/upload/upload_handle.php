<?php

# Line 88
$Properties['Screenshots'] = isset($_POST['screenshots']) ? $_POST['screenshots'] : '';
$Properties['Mirrors'] = isset($_POST['mirrors']) ? $_POST['mirrors'] : '';
# Line 89

# Line 512
if (!isset($GroupID) || !$GroupID) {
    // Create torrent group
    $DB->query(
        "
    INSERT INTO torrents_group
      (CategoryID, Name, NameRJ, NameJP, Year,
      Series, Studio, CatalogueNumber, Pages, Time,
      WikiBody, WikiImage, DLsiteID)
    VALUES
      ( ?, ?, ?, ?, ?,
        ?, ?, ?, ?, NOW(),
        ?, ?, ? )",
        $TypeID,
        $T['Title'],
        $T['TitleRJ'],
        $T['TitleJP'],
        $T['Year'],
        $T['Series'],
        $T['Studio'],
        $T['CatalogueNumber'],
        $T['Pages'],
        $Body,
        $T['Image'],
        $T['DLsiteID']
    );
    $GroupID = $DB->inserted_id();
    foreach ($ArtistForm as $Num => $Artist) {
        $DB->query("
      INSERT IGNORE INTO torrents_artists (GroupID, ArtistID, UserID)
      VALUES ( ?, ?, ? )", $GroupID, $Artist['id'], $LoggedUser['ID']);
        $Cache->increment('stats_album_count');
        $Cache->delete_value('artist_groups_'.$Artist['id']);
    }
    $Cache->increment('stats_group_count');

    // Add screenshots
    // todo: Clear DB_MYSQL::exec_prepared_query() errors
    $Screenshots = explode("\n", $T['Screenshots']);
    $Screenshots = array_map('trim', $Screenshots);

    $Screenshots = array_filter($Screenshots, function ($s) {
        return preg_match('/^'.DOI_REGEX.'$/i', $s);
    });

    $Screenshots = array_unique($Screenshots);
    $Screenshots = array_slice($Screenshots, 0, 10);

    # Add optional web seeds similar to screenshots
    # Support an arbitrary and limited number of sources
    $Mirrors = explode("\n", $T['Mirrors']);
    $Mirrors = array_map('trim', $Mirrors);

    $Mirrors = array_filter($Mirrors, function ($s) {
        return preg_match('/^'.URL_REGEX.'$/i', $s);
    });

    $Mirrors = array_unique($Mirrors);
    $Mirrors = array_slice($Mirrors, 0, 2);

    # Downgrade TLS on resource URIs
    # Required for BEP 19 compatibility
    $Mirrors = str_ireplace('tps://', 'tp://', $Mirrors);

    # Perform the DB inserts here
    # Screenshots (publications)
    if (!empty($Screenshots)) {
        $Screenshot = '';
        $DB->prepare_query("
      INSERT INTO torrents_screenshots
        (GroupID, UserID, Time, Image)
      VALUES (?, ?, NOW(), ?)", $GroupID, $LoggedUser['ID'], $Screenshot);
        foreach ($Screenshots as $Screenshot) {
            $DB->exec_prepared_query();
        }
    }

    # Mirrors
    if (!empty($Mirrors)) {
        $Mirror = '';
        $DB->prepare_query("
  INSERT INTO torrents_mirrors
    (GroupID, UserID, Time, Resource)
  VALUES (?, ?, NOW(), ?)", $GroupID, $LoggedUser['ID'], $Mirror);
        foreach ($Mirrors as $Mirror) {
            $DB->exec_prepared_query();
        }
    }

# Main if/else
} else {
    $DB->query("
    UPDATE torrents_group
    SET Time = NOW()
    WHERE ID = ?", $GroupID);
    $Cache->delete_value("torrent_group_$GroupID");
    $Cache->delete_value("torrents_details_$GroupID");
    $Cache->delete_value("detail_files_$GroupID");
}
# Line 609
