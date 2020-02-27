<?php

# Line 44
# todo: Associate containers with categories beforehand
# It may have to happen structurally in config.php, e.g.,
# $Categories = [
#   'GazelleName' => [$Name, $Icon, $Description, $Containers],
#    ...
#  ];
$Properties['Archives'] = $Archives;
$Properties['Containers'] = [
    'DNA'      => $Containers,
    'RNA'      => $Containers,
    'Proteins' => $ContainersProt,
    'Imaging'  => $ContainersGames,
    'Extras'   => $ContainersExtra
];
# Line 57

# Line 421
//******************************************************************************//
//--------------- Autofill format and archive ----------------------------------//

if ($T['Container'] === 'Autofill') {
    # torrents.Container
    $Validate->ParseExtensions(
        # $FileList
        $Tor->file_list(),

        # $Category
        $T['CategoryName'],

        # $FileTypes
        $T['FileTypes'],
    );
}

if ($T['Archive'] === 'Autofill') {
    /*
    # torrents.Archive
    $Validate->ParseExtensions(
        # $FileList
        $Tor->file_list(),

        # $Category
        $T['CategoryName'],

        # $FileTypes
        $T['FileTypes'],
    );
    */
}
# Line 452
