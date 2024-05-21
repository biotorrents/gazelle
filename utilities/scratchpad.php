<?php

declare(strict_types=1);


/**
 * scratchpad
 */

$app = Gazelle\App::go();

$c = Gazelle\Models\Torrent::find(1);
!d($c->toJsonApi());
#$collage =  Gazelle\Models\Group::find(1);
#!d($collage);

/*
$semanticScholar = new Gazelle\SemanticScholar([]);
$data = $semanticScholar->search(urlencode("Laurel L Haak"), "authors");
!d($data);
*/
/*
 $maps = [
    "category_id" => 2,
    "title" => "foo",
    "subject" => "bar",
    "object" => "baz",
    "year" => 1991,
    "workgroup" => "workgroup",
    "location" => "location",
    "identifier" => "identifier",
    "tag_list" => "tags",
    "revision_id" => 666,
    "description" => "description",
    "picture" => "picture",
];
$test = Gazelle\Models\Group::updateOrCreate($maps);
!d($test);
*/