<?php

#declare(strict_types=1);

$app = \Gazelle\App::go();

authorize();

$Val = new Validate();

$P = [];
$P = db_array($_POST);

if ($P['category'] > 0 || check_perms('site_collages_renamepersonal')) {
    $Val->SetFields('name', '1', 'string', 'The name must be between 5 and 255 characters.', array('maxlength' => 255, 'minlength' => 5));
} else {
    // Get a collage name and make sure it's unique
    $name = $app->userNew->core['username']."'s personal collage";
    $P['name'] = db_string($name);
    $app->dbOld->query("
    SELECT ID
    FROM collages
    WHERE Name = '".$P['name']."'");
    $i = 2;
    while ($app->dbOld->has_results()) {
        $P['name'] = db_string("$name no. $i");
        $app->dbOld->query("
      SELECT ID
      FROM collages
      WHERE Name = '".$P['name']."'");
        $i++;
    }
}
$Val->SetFields('description', '1', 'string', 'The description must be between 10 and 65535 characters.', array('maxlength' => 65535, 'minlength' => 10));

$Err = $Val->ValidateForm($_POST);

if (!$Err && $P['category'] === '0') {
    $app->dbOld->query("
    SELECT COUNT(ID)
    FROM collages
    WHERE UserID = '{$app->userNew->core['id']}'
      AND CategoryID = '0'
      AND Deleted = '0'");
    list($CollageCount) = $app->dbOld->next_record();
    if (($CollageCount >= $app->userNew->extra['Permissions']['MaxCollages']) || !check_perms('site_collages_personal')) {
        $Err = 'You may not create a personal collage.';
    } elseif (check_perms('site_collages_renamepersonal') && !stristr($P['name'], $app->userNew->core['username'])) {
        $Err = "Your personal collage's title must include your username.";
    }
}

if (!$Err) {
    $app->dbOld->query("
    SELECT ID, Deleted
    FROM collages
    WHERE Name = '$P[name]'");
    if ($app->dbOld->has_results()) {
        list($ID, $Deleted) = $app->dbOld->next_record();
        if ($Deleted) {
            $Err = 'That collection already exists but needs to be recovered. Please <a href="staffpm.php">contact</a> the staff team.';
        } else {
            $Err = "That collection already exists: <a href='/collages.php?id=$ID'>$ID</a>.";
        }
    }
}

if (!$Err) {
    if (empty($CollageCats[$P['category']])) {
        $Err = 'Please select a category';
    }
}

if ($Err) {
    $Name = $_POST['name'];
    $Category = $_POST['category'];
    $Tags = $_POST['tags'];
    $Description = $_POST['description'];
    include(serverRoot.'/sections/collages/new.php');
    error();
}

$TagList = explode(',', $_POST['tags']);
foreach ($TagList as $ID => $Tag) {
    $TagList[$ID] = Misc::sanitize_tag($Tag);
}
$TagList = implode(' ', $TagList);

$app->dbOld->query("
  INSERT INTO collages
    (Name, Description, UserID, TagList, CategoryID)
  VALUES
    ('$P[name]', '$P[description]', {$app->userNew->core['id']}, '$TagList', '$P[category]')");

$CollageID = $app->dbOld->inserted_id();
$app->cacheNew->delete("collage_$CollageID");
Misc::write_log("Collage $CollageID (".$_POST['name'].') was created by '.$app->userNew->core['username']);
Http::redirect("collages.php?id=$CollageID");
