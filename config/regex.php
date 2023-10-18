<?php

declare(strict_types=1);


/**
 * regular expressions
 *
 * the gazelle regex collection
 * formerly in classes/regex.php
 */

# https://ihateregex.io/expr/uuid/
# flags: iD
$env->regexUuid = "^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$";


# https://ihateregex.io/expr/semver/
# flags: none
$env->regexSemVer = "^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-((?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+([0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$";


# resource_type://username:password@domain:port/path?query_string#anchor
# flags: none
$env->regexResource = "(https?|ftps?|dat|ipfs):\/\/";


# https://ihateregex.io/expr/ip/
# flags: none
$env->regexIp4 = "(\b25[0-5]|\b2[0-4][0-9]|\b[01]?[0-9][0-9]?)(\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)){3}";


# https://ihateregex.io/expr/ipv6/
# flags: none
$env->regexIp6 = "(([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:){0,1}((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])|([0-9a-fA-F]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]))";


# domain
# flags: none
$env->regexDomain = "([a-z0-9\-\_]+\.)*[a-z0-9\-\_]+";


# port
# flags: none
$env->regexPort = ":\d{1,5}";


# uri
# flags: i
$env->regexUri = "^({$env->regexResource})({$env->regexIp4}|{$env->regexDomain})({$env->regexPort})?(\/\S*)*";

# username
# flags: iD
$env->regexUsername = "^[a-z0-9_]{1,32}$";


# email
# flags: none
$env->regexEmail = "[_a-z0-9-]+([.+][_a-z0-9-]+)*@{$env->regexDomain}";


# image
# flags: i
$env->regexImage = "{$env->regexUri}\/\S+\.(jpg|jpeg|tif|tiff|png|gif|bmp)(\?\S*)?";


# video
# flags: i
$env->regexVideo = "{$env->regexUri}\/\S+\.(webm)(\?\S*)?";


# css
# flags: i
$env->regexCss = "{$env->regexUri}\/\S+\.css(\?\S*)?";


# siteLink
# flags: none
$env->regexSiteLink = "{$env->regexResource}(www.)?" . preg_quote($env->siteDomain, "/");


# torrent
# flags: i
$env->regexTorrent = "{$env->regexSiteLink}\/torrents\.php\?(.*&)?torrentid=(\d+)";


# torrentGroup
# flags: i
$env->regexTorrentGroup = "^{$env->regexSiteLink}\/torrents\.php\?(.*&)?id=(\d+)";


# creator
# flags: i
$env->regexCreator = "^{$env->regexSiteLink}\/artist\.php\?(.*&)?id=(\d+)";


# https://stackoverflow.com/a/3180176
# flags: s
$env->regexHtml = "<([\w]+)([^>]*?)(([\s]*\/>)|(>((([^<]*?|<\!\-\-.*?\-\->)|(?R))*)<\/\\1[\s]*>))";


# bbCode
# flags: s
$env->regexBBCode = "\[([\w]+)([^\]]*?)(([\s]*\/\])|(\]((([^\[]*?|\[\!\-\-.*?\-\-\])|(?R))*)\[\/\\1[\s]*\]))";


# https://www.crossref.org/blog/dois-and-matching-regular-expressions/
# flags: i
$env->regexDoi = "^10.\d{4,9}\/[-._;()\/:A-Z0-9]+$";


# https://www.biostars.org/p/13753/
# flags: none
$env->regexEntrez = "\d*";


# https://www.wikidata.org/wiki/Property:P496
# flags: none
$env->regexOrcid = "0000-000(1-[5-9]|2-[0-9]|3-[0-4])\d{3}-\d{3}[\dX]";


# https://www.biostars.org/p/13753/
# flags: none
$env->regexRefSeq = "\w{2}_\d{1,}\.\d{1,}";


# https://www.uniprot.org/help/accession_numbers
# flags: none
$env->regexUniProt = "[OPQ][0-9][A-Z0-9]{3}[0-9]|[A-NR-Z][0-9]([A-Z][A-Z0-9]{2}[0-9]){1,2}";


# https://github.com/gzuidhof/starboard-notebook/blob/master/docs/format.md
# flags: none
$env->regexStarboard = "^(#|\/\/)\s*%{2,}-*";
