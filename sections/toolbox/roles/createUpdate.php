<?php

declare(strict_types=1);


/**
 * create or update a role
 */

$app = Gazelle\App::go();

# determine the role
$id ??= "create";
if ($id === "create") {
    $role = new Gazelle\Roles();
    $title = "Create a new role";
} else {
    $role = new Gazelle\Roles($id);
    $title = "Update the {$role->attributes->friendlyName} role";
}

if (!$role) {
    $app->error(404);
}

# get all roles and permissions
$allRoles = $role->getAll();
$allPermissions = Gazelle\Permissions::getAll();

# handle the form
#Gazelle\Http::csrf();
$post = Gazelle\Http::post();

if (!empty($post)) {
    $data = [
        "id" => Gazelle\Escape::int($id ?? $app->dbNew->shortUuid()),
        "friendlyName" => Gazelle\Escape::string($post["friendlyName"] ?? ""),
        "description" => Gazelle\Escape::string($post["description"] ?? ""),
        "isPrimaryRole" => Gazelle\Escape::int($post["primaryOrSecondaryRole"] === "isPrimaryRole"),
        "isSecondaryRole" => Gazelle\Escape::int($post["primaryOrSecondaryRole"] === "isSecondaryRole"),
        "isDefaultRole" => Gazelle\Escape::int($role->attributes->isDefaultRole ?? false),
        "isStaffRole" => Gazelle\Escape::int($post["isStaffRole"] ?? false),
        "maxPersonalCollages" => Gazelle\Escape::int($post["maxPersonalCollages"] ?? 0),
        "permissionsLevel" => Gazelle\Escape::int(count($post["permissionsList"] ?? 0, COUNT_RECURSIVE)), # automatically set
        #"permissionsLevel" => Gazelle\Escape::int($post["permissionsLevel"] ?? 0), # manually set
        "permissionsList" => json_encode($post["permissionsList"] ?? []),
    ];

    # default roles rely on their camelCase names for class promotions
    if (!$role->attributes->isDefaultRole) {
        $data["machineName"] = Gazelle\Text::camel($data["friendlyName"] ?? "");
    }

    # try to upsert the role
    try {
        $role->updateOrCreate($data);
        $successMessage = "Successfully updated the {$data["friendlyName"]} role";
    } catch (Throwable $e) {
        $errorMessage = $e->getMessage();
    }
}

# twig template
$app->twig->display("admin/roles/createUpdate.twig", [
    "title" => $title,
    "pageTitle" => $title,

    "role" => $role,
    "allRoles" => $allRoles,
    "allPermissions" => $allPermissions,

    "successMessage" => $successMessage ?? null,
    "errorMessage" => $errorMessage ?? null,
]);
