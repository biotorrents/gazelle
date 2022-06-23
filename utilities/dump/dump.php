<?php
declare(strict_types = 1);


require_once "../../bootstrap/cli.php";

$ss = new SemanticScholar(["paperId" => "10.1038/nphys1170"]);
!d($ss);
!d($ss->scrape());
