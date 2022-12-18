<?php

#declare(strict_types=1);

authorize();

$Val = new Validate();

$P = [];
$P = db_array($_POST);

if ($P['category'] > 0 || check_perms('site_collages_renamepersonal')) {
    $Val->SetFields('name', '1', 'string', 'The name must be between 5 and 255 characters.', array('maxlength' => 255, 'minlength' => 5));
} else {
    // Get a collage name and make sure it's unique
    $name = $user['Username']."'s personal collage";
    $P['name'] = db_string($name);
    $db->query("
    SELECT ID
    FROM collages
    WHERE Name = '".$P['name']."'");
    $i = 2;
    while ($db->has_results()) {
        $P['name'] = db_string("$name no. $i");
        $db->query("
      SELECT ID
      FROM collages
      WHERE Name = '".$P['name']."'");
        $i++;
    }
}
$Val->SetFields('description', '1', 'string', 'The description must be between 10 and 65535 characters.', array('maxlength' => 65535, 'minlength' => 10));

$Err = $Val->ValidateForm($_POST);

if (!$Err && $P['category'] === '0') {
    $db->query("
    SELECT COUNT(ID)
    FROM collages
    WHERE UserID = '$user[ID]'
      AND CategoryID = '0'
      AND Deleted = '0'");
    list($CollageCount) = $db->next_record();
    if (($CollageCount >= $user['Permissions']['MaxCollages']) || !check_perms('site_collages_personal')) {
        $Err = 'You may not create a personal collage.';
    } elseif (check_perms('site_collages_renamepersonal') && !stristr($P['name'], $user['Username'])) {
        $Err = "Your personal collage's title must include your username.";
    }
}

if (!$Err) {
    $db->query("
    SELECT ID, Deleted
    FROM collages
    WHERE Name = '$P[name]'");
    if ($db->has_results()) {
        list($ID, $Deleted) = $db->next_record();
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

$db->query("
  INSERT INTO collages
    (Name, Description, UserID, TagList, CategoryID)
  VALUES
    ('$P[name]', '$P[description]', $user[ID], '$TagList', '$P[category]')");

$CollageID = $db->inserted_id();
$cache->delete_value("collage_$CollageID");
Misc::write_log("Collage $CollageID (".$_POST['name'].') was created by '.$user['Username']);
Http::redirect("collages.php?id=$CollageID");
