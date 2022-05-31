<?php
declare(strict_types=1);


/**
 * regular expressions
 *
 * The Gazelle regex collection.
 * Formerly in classes/regex.php.
 */

# resource_type://username:password@domain:port/path?query_string#anchor
define("regexResource", "(https?|ftps?|dat|ipfs):\/\/");
ENV::setPub(
    "regexResource",
    "/(https?|ftps?|dat|ipfs):\/\//"
);


#define("regexIp", "(\d{1,3}\.){3}\d{1,3}");
ENV::setPub(
    "regexIp",
    "/(\d{1,3}\.){3}\d{1,3}/"
);


#define("regexDomain", "([a-z0-9\-\_]+\.)*[a-z0-9\-\_]+");
ENV::setPub(
    "regexDomain",
    "/([a-z0-9\-\_]+\.)*[a-z0-9\-\_]+/"
);

#define("regexPort", ":\d{1,5}");
ENV::setPub(
    "regexPort",
    "/:\d{1,5}/"
);


#define("regexUri", "(".regexResource.")(".regexIp."|".regexDomain.")(".regexPort.")?(\/\S*)*");
ENV::setPub(
    "regexUri",
    "/^({$env->regexResource})({$env->regexIp}|{$env->regexDomain})({$env->regexPort})?(\/\S*)*/i"
);


#define("regexUsername", "/^[a-z0-9_]{2,20}$/iD");
ENV::setPub(
    "regexUsername",
    "/^[a-z0-9_]{2,20}$/iD"
);

#define("regexEmail", "[_a-z0-9-]+([.+][_a-z0-9-]+)*@".regexDomain);
ENV::setPub(
    "regexEmail",
    "/^[_a-z0-9-]+([.+][_a-z0-9-]+)*@{$env->regexDomain}$/i"
);


#define("regexImage", regexUri."\/\S+\.(jpg|jpeg|tif|tiff|png|gif|bmp)(\?\S*)?");
ENV::setPub(
    "regexImage",
    "/{$env->regexUri}\/\S+\.(jpg|jpeg|tif|tiff|png|gif|bmp)(\?\S*)?/i"
);


#define("regexVideo", regexUri."\/\S+\.(webm)(\?\S*)?");
ENV::setPub(
    "regexVideo",
    "/{$env->regexUri}\/\S+\.(webm)(\?\S*)?/i"
);


#define("regexCss", regexUri."\/\S+\.css(\?\S*)?");
ENV::setPub(
    "regexCss",
    "/{$env->regexUri}\/\S+\.css(\?\S*)?/i"
);


#define("regexSiteLink", regexResource."(www.)?".preg_quote(SITE_DOMAIN, "/"));
ENV::setPub(
    "regexSiteLink",
    "/{$env->regexResource}(www.)?".preg_quote(SITE_DOMAIN, "/")."/"
);


ENV::setPub(
    "regexTorrent",
    "/{$env->regexSiteLink}\/torrents\.php\?(.*&)?torrentid=(\d+)/i"
);


ENV::setPub(
    "regexTorrentGroup",
    "/^{$env->regexSiteLink}\/torrents\.php\?(.*&)?id=(\d+)/i"
);


ENV::setPub(
    "regexArtist",
    "/^{$env->regexSiteLink}\/artist\.php\?(.*&)?id=(\d+)/i"
);


# https://stackoverflow.com/a/3180176
ENV::setPub(
    "regexHtml",
    "/<([\w]+)([^>]*?)(([\s]*\/>)|(>((([^<]*?|<\!\-\-.*?\-\->)|(?R))*)<\/\\1[\s]*>))/s"
);


ENV::setPub(
    "regexBBCode",
    "/\[([\w]+)([^\]]*?)(([\s]*\/\])|(\]((([^\[]*?|\[\!\-\-.*?\-\-\])|(?R))*)\[\/\\1[\s]*\]))/s"
);


# https://www.crossref.org/blog/dois-and-matching-regular-expressions/
ENV::setPub(
    "regexDoi",
    "/10.\d{4,9}\/[-._;()\/:A-Z0-9]+/"
);


# https://www.biostars.org/p/13753/
ENV::setPub(
    "regexEntrez",
    "/\d*/"
);


# https://www.wikidata.org/wiki/Property:P496
ENV::setPub(
    "regexOrcid",
    "/0000-000(1-[5-9]|2-[0-9]|3-[0-4])\d{3}-\d{3}[\dX]/"
);


# https://www.biostars.org/p/13753/
ENV::setPub(
    "regexRefSeq",
    "/\w{2}_\d{1,}\.\d{1,}/"
);


# https://www.uniprot.org/help/accession_numbers
ENV::setPub(
    "regexUniProt",
    "/[OPQ][0-9][A-Z0-9]{3}[0-9]|[A-NR-Z][0-9]([A-Z][A-Z0-9]{2}[0-9]){1,2}/"
);
