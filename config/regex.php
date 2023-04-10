<?php

declare(strict_types=1);


/**
 * regular expressions
 *
 * the gazelle regex collection
 * formerly in classes/regex.php
 */

# resource_type://username:password@domain:port/path?query_string#anchor
ENV::setPub(
    "regexResource",
    "(https?|ftps?|dat|ipfs):\/\/"
);


# ip
ENV::setPub(
    "regexIp",
    "(\d{1,3}\.){3}\d{1,3}"
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
    "^({$env->regexResource})({$env->regexIp}|{$env->regexDomain})({$env->regexPort})?(\/\S*)*" # i
);


# username
ENV::setPub(
    "regexUsername",
    "^[a-z0-9_]{3,32}$" # iD
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
