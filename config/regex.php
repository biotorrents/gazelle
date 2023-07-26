<?php

declare(strict_types=1);


/**
 * regular expressions
 *
 * the gazelle regex collection
 * formerly in classes/regex.php
 */

# https://ihateregex.io/expr/uuid/
ENV::setPub(
    "regexUuid",
    "^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$" # iD
);


# https://ihateregex.io/expr/semver/
ENV::setPub(
    "regexSemVer",
    "^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-((?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+([0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$"
);


# resource_type://username:password@domain:port/path?query_string#anchor
ENV::setPub(
    "regexResource",
    "(https?|ftps?|dat|ipfs):\/\/"
);


# https://ihateregex.io/expr/ip/
ENV::setPub(
    "regexIp4",
    "(\b25[0-5]|\b2[0-4][0-9]|\b[01]?[0-9][0-9]?)(\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)){3}"
);


# https://ihateregex.io/expr/ipv6/
ENV::setPub(
    "regexIp6",
    "(([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:){0,1}((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])|([0-9a-fA-F]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]))"
);


# domain
ENV::setPub(
    "regexDomain",
    "([a-z0-9\-\_]+\.)*[a-z0-9\-\_]+"
);


# port
ENV::setPub(
    "regexPort",
    ":\d{1,5}"
);


# uri
ENV::setPub(
    "regexUri",
    "^({$env->regexResource})({$env->regexIp4}|{$env->regexDomain})({$env->regexPort})?(\/\S*)*" # i
);


# username
ENV::setPub(
    "regexUsername",
    "^[a-z0-9_]{1,32}$" # iD
    #"^[a-z0-9_]{3,32}$" # iD
);


# email
ENV::setPub(
    "regexEmail",
    "[_a-z0-9-]+([.+][_a-z0-9-]+)*@{$env->regexDomain}"
);


# image
ENV::setPub(
    "regexImage",
    "{$env->regexUri}\/\S+\.(jpg|jpeg|tif|tiff|png|gif|bmp)(\?\S*)?" # i
);


# video
ENV::setPub(
    "regexVideo",
    "{$env->regexUri}\/\S+\.(webm)(\?\S*)?" # i
);


# css
ENV::setPub(
    "regexCss",
    "{$env->regexUri}\/\S+\.css(\?\S*)?" # i
);


# siteLink
ENV::setPub(
    "regexSiteLink",
    "{$env->regexResource}(www.)?" . preg_quote($env->siteDomain, "/")
);


# torrent
ENV::setPub(
    "regexTorrent",
    "{$env->regexSiteLink}\/torrents\.php\?(.*&)?torrentid=(\d+)" # i
);


# torrentGroup
ENV::setPub(
    "regexTorrentGroup",
    "^{$env->regexSiteLink}\/torrents\.php\?(.*&)?id=(\d+)" # i
);


# creator
ENV::setPub(
    "regexCreator",
    "^{$env->regexSiteLink}\/artist\.php\?(.*&)?id=(\d+)" # i
);


# https://stackoverflow.com/a/3180176
ENV::setPub(
    "regexHtml",
    "<([\w]+)([^>]*?)(([\s]*\/>)|(>((([^<]*?|<\!\-\-.*?\-\->)|(?R))*)<\/\\1[\s]*>))" # s
);


# bbCode
ENV::setPub(
    "regexBBCode",
    "\[([\w]+)([^\]]*?)(([\s]*\/\])|(\]((([^\[]*?|\[\!\-\-.*?\-\-\])|(?R))*)\[\/\\1[\s]*\]))" # s
);


# https://www.crossref.org/blog/dois-and-matching-regular-expressions/
ENV::setPub(
    "regexDoi",
    "^10.\d{4,9}\/[-._;()\/:A-Z0-9]+$" # i
);


# https://www.biostars.org/p/13753/
ENV::setPub(
    "regexEntrez",
    "\d*"
);


# https://www.wikidata.org/wiki/Property:P496
ENV::setPub(
    "regexOrcid",
    "0000-000(1-[5-9]|2-[0-9]|3-[0-4])\d{3}-\d{3}[\dX]"
);


# https://www.biostars.org/p/13753/
ENV::setPub(
    "regexRefSeq",
    "\w{2}_\d{1,}\.\d{1,}"
);


# https://www.uniprot.org/help/accession_numbers
ENV::setPub(
    "regexUniProt",
    "[OPQ][0-9][A-Z0-9]{3}[0-9]|[A-NR-Z][0-9]([A-Z][A-Z0-9]{2}[0-9]){1,2}"
);
