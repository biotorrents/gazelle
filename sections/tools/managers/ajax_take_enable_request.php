<?php

if (!check_perms('users_mod')) {
    \Gazelle\Api\Base::failure(403);
}

if (!FEATURE_EMAIL_REENABLE) {
    \Gazelle\Api\Base::failure(400, "This feature is currently disabled.");
}

$Type = $_GET['type'];

if ($Type == "resolve") {
    $IDs = $_GET['ids'];
    $Comment = db_string($_GET['comment']);
    $Status = db_string($_GET['status']);

    // Error check and set things up
    if ($Status == "Approve" || $Status == "Approve Selected") {
        $Status = AutoEnable::APPROVED;
    } elseif ($Status == "Reject" || $Status == "Reject Selected") {
        $Status = AutoEnable::DENIED;
    } elseif ($Status == "Discard" || $Status == "Discard Selected") {
        $Status = AutoEnable::DISCARDED;
    } else {
        \Gazelle\Api\Base::failure(400, "Invalid resolution option");
    }

    if (is_array($IDs) && count($IDs) == 0) {
        \Gazelle\Api\Base::failure(400, "You must select at least one reuqest to use this option");
    } elseif (!is_array($IDs) && !is_numeric($IDs)) {
        \Gazelle\Api\Base::failure(400, "You must select at least 1 request");
    }

    // Handle request
    AutoEnable::handle_requests($IDs, $Status, $Comment);
} elseif ($Type == "unresolve") {
    $ID = (int) $_GET['id'];
    AutoEnable::unresolve_request($ID);
} else {
    \Gazelle\Api\Base::failure(400, "Invalid type");
}

echo json_encode(array("status" => "success"));
