<?php
declare(strict_types=1);

/**
 * Regular expressions
 *
 * The Gazelle regex collection.
 * Formerly in classes/regex.php.
 */

// resource_type://username:password@domain:port/path?query_string#anchor
define('RESOURCE_REGEX', '(https?|ftps?|dat|ipfs):\/\/');
ENV::setPub(
    'RESOURCE_REGEX',
    '(https?|ftps?|dat|ipfs):\/\/'
);

define('IP_REGEX', '(\d{1,3}\.){3}\d{1,3}');
ENV::setPub(
    'IP_REGEX',
    '(\d{1,3}\.){3}\d{1,3}'
);

define('DOMAIN_REGEX', '([a-z0-9\-\_]+\.)*[a-z0-9\-\_]+');
ENV::setPub(
    'DOMAIN_REGEX',
    '([a-z0-9\-\_]+\.)*[a-z0-9\-\_]+'
);

define('PORT_REGEX', ':\d{1,5}');
ENV::setPub(
    'PORT_REGEX',
    ':\d{1,5}'
);

define('URL_REGEX', '('.RESOURCE_REGEX.')('.IP_REGEX.'|'.DOMAIN_REGEX.')('.PORT_REGEX.')?(\/\S*)*');
ENV::setPub(
    'URL_REGEX',
    "($env->RESOURCE_REGEX)($env->IP_REGEX|$env->DOMAIN_REGEX)($env->PORT_REGEX)?(\/\S*)*"
);

define('USERNAME_REGEX', '/^[a-z0-9_]{2,20}$/iD');
ENV::setPub(
    'USERNAME_REGEX',
    '/^[a-z0-9_]{2,20}$/iD'
);

define('EMAIL_REGEX', '[_a-z0-9-]+([.+][_a-z0-9-]+)*@'.DOMAIN_REGEX);
ENV::setPub(
    'EMAIL_REGEX',
    "[_a-z0-9-]+([.+][_a-z0-9-]+)*@$env->DOMAIN_REGEX"
);

define('IMAGE_REGEX', URL_REGEX.'\/\S+\.(jpg|jpeg|tif|tiff|png|gif|bmp)(\?\S*)?');
ENV::setPub(
    'IMAGE_REGEX',
    "$env->URL_REGEX\/\S+\.(jpg|jpeg|tif|tiff|png|gif|bmp)(\?\S*)?"
);

define('VIDEO_REGEX', URL_REGEX.'\/\S+\.(webm)(\?\S*)?');
ENV::setPub(
    'VIDEO_REGEX',
    "$env->URL_REGEX\/\S+\.(webm)(\?\S*)?"
);

define('CSS_REGEX', URL_REGEX.'\/\S+\.css(\?\S*)?');
ENV::setPub(
    'CSS_REGEX',
    "$env->URL_REGEX\/\S+\.css(\?\S*)?"
);

define('SITELINK_REGEX', RESOURCE_REGEX.'(www.)?'.preg_quote(SITE_DOMAIN, '/'));
ENV::setPub(
    'SITELINK_REGEX',
    "$env->RESOURCE_REGEX(www.)?".preg_quote(SITE_DOMAIN, '/')
);

define('TORRENT_REGEX', SITELINK_REGEX.'\/torrents\.php\?(.*&)?torrentid=(\d+)'); // torrentid = group 4
ENV::setPub(
    'TORRENT_REGEX',
    "$env->SITELINK_REGEX\/torrents\.php\?(.*&)?torrentid=(\d+)"
);

define('TORRENT_GROUP_REGEX', SITELINK_REGEX.'\/torrents\.php\?(.*&)?id=(\d+)'); // id = group 4
ENV::setPub(
    'TORRENT_GROUP_REGEX',
    "$env->SITELINK_REGEX\/torrents\.php\?(.*&)?id=(\d+)"
);

define('ARTIST_REGEX', SITELINK_REGEX.'\/artist\.php\?(.*&)?id=(\d+)'); // id = group 4
ENV::setPub(
    'ARTIST_REGEX',
    "$env->SITELINK_REGEX\/artist\.php\?(.*&)?id=(\d+)"
);

# https://stackoverflow.com/a/3180176
ENV::setPub(
    'HTML_REGEX',
    '<([\w]+)([^>]*?)(([\s]*\/>)|(>((([^<]*?|<\!\-\-.*?\-\->)|(?R))*)<\/\\1[\s]*>))'
);

ENV::setPub(
    'BBCODE_REGEX',
    '\[([\w]+)([^\]]*?)(([\s]*\/\])|(\]((([^\[]*?|\[\!\-\-.*?\-\-\])|(?R))*)\[\/\\1[\s]*\]))'
);

# https://www.crossref.org/blog/dois-and-matching-regular-expressions/
ENV::setPub(
    'DOI_REGEX',
    '10.\d{4,9}\/[-._;()\/:A-Z0-9]+'
);

# https://www.biostars.org/p/13753/
ENV::setPub(
    'ENTREZ_REGEX',
    '\d*'
);

# https://www.wikidata.org/wiki/Property:P496
ENV::setPub(
    'ORCID_REGEX',
    '0000-000(1-[5-9]|2-[0-9]|3-[0-4])\d{3}-\d{3}[\dX]'
);

# https://www.biostars.org/p/13753/
ENV::setPub(
    'REFSEQ_REGEX',
    '\w{2}_\d{1,}\.\d{1,}'
);

# https://www.uniprot.org/help/accession_numbers
ENV::setPub(
    'UNIPROT_REGEX',
    '[OPQ][0-9][A-Z0-9]{3}[0-9]|[A-NR-Z][0-9]([A-Z][A-Z0-9]{2}[0-9]){1,2}'
);
