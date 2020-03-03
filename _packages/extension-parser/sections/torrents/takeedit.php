<?php

# Line 28
# todo: Associate containers with categories beforehand
# It may have to happen structurally in config.php, e.g.,
# $Categories = [
#   'GazelleName' => [$Name, $Icon, $Description, $Containers],
#    ...
#  ];
$Properties['CategoryID'] = $TypeID;
$Properties['CategoryName'] = $Type;
$Properties['FileTypes'] = [
  'DNA'      => $Containers,
  'RNA'      => $Containers,
  'Proteins' => $ContainersProt,
  'Imaging'  => $ContainersGames,
  'Extras'   => $ContainersExtra
];
$Properties['ArchiveTypes'] = [
  'DNA'      => $Archives,
  'RNA'      => $Archives,
  'Proteins' => $Archives,
  'Imaging'  => $Archives,
  'Extras'   => $Archives
];
# Line 49

# Line 313
$T['FileTypes'] = $Properties['FileTypes'];
$T['ArchiveTypes'] = $Properties['FileTypes'];

//******************************************************************************//
//--------------- Autofill format and archive ----------------------------------//

if ($T['Container'] === 'Autofill') {
    # torrents.Container
    $T['Container'] = $Validate->ParseExtensions(

        # $FileList
        $Tor->file_list(),

        # $Category
        $T['CategoryName'],

        # $FileTypes
        $T['FileTypes'],
    );
}

if ($T['Archive'] === 'Autofill') {
    # torrents.Archive
    $T['Archive'] = $Validate->ParseExtensions(

        # $FileList
        $Tor->file_list(),

        # $Category
        $T['CategoryName'],

        # $FileTypes
        $T['ArchiveTypes'],
    );
}
# Line 347
