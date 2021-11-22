<?php
declare(strict_types=1);

/**
 * Environment
 * Config Loader v2
 *
 * To use the new system, which has significant security benefits,
 * (fine-grained scoping, ephemeral access lifetime, public vs. private, etc.),
 * please follow the example below.
 *
 *   $ENV = ENV::go();
 *   $ENV->PUBLIC_VALUE;
 *   $ENV->getPriv('PRIVATE_VALUE');
 *
 * Using a central static $ENV singleton class has additional benefits.
 * The RecursiveArrayObject class included in env.class.php is a powerful tool:
 *
 *   $LongArray = [];
 *   ENV::setPub(
 *     'CONFIG',
 *     $ENV->convert($LongArray)
 *   );
 *
 *   $ENV = ENV::go();
 *   foreach ($ENV->CATS as $Cat) {
 *     var_dump($Cat->Name);
 *   }
 *
 * One more example using custom RecursiveArrayObject methods:
 * @see https://www.php.net/manual/en/class.arrayobject.php
 *
 *   var_dump(
 *     $ENV->dedupe(
 *       $ENV->META->Formats->Sequences,
 *       $ENV->META->Formats->Proteins->toArray()
 *     )
 *   );
 */

# Initialize
require_once __DIR__.'/env.class.php';
$ENV = ENV::go();

# Basic info
ENV::setPub('DEV', true);


/**
 * Site identity
 */

# Site name
ENV::setPub(
    'SITE_NAME',
    (!$ENV->DEV
        ? 'torrents.bio' # Production
        : 'dev.torrents.bio') # Development
);

# Meta description
ENV::setPub('DESCRIPTION', 'An open platform for libre biology data');

# Navigation glyphs
ENV::setPub('SEP', '-'); # e.g., News - dev.torrents.bio
ENV::setPub('CRUMB', '›'); # e.g., Forums › Board › Thread

# The FQDN of your site, e.g., dev.torrents.bio
( # Old format
    !$ENV->DEV
        ? define('SITE_DOMAIN', 'torrents.bio') # Production
        : define('SITE_DOMAIN', 'dev.torrents.bio') # Development
);

ENV::setPub(
    'SITE_DOMAIN',
    (!$ENV->DEV
        ? 'torrents.bio' # Production
        : 'dev.torrents.bio') # Development
);

# Old domain, to handle the biotorrents.de => torrents.bio migration
# If not needed, simply set to the same values as $ENV->SITE_DOMAIN
ENV::setPub(
    'OLD_SITE_DOMAIN',
    (!$ENV->DEV
        ? 'biotorrents.de' # Production
        : 'dev.torrents.bio') # Development
);

# The FQDN of your image host, e.g., pics.biotorrents.de
ENV::setPub('IMAGE_DOMAIN', 'pics.biotorrents.de');

# Web root. Currently used for Twig but may also include config files
ENV::setPub('WEB_ROOT', '/var/www/');

# The root of the server, used for includes, e.g., /var/www/html/dev.biotorrents.de/
( # Old format
    !$ENV->DEV
        ? define('SERVER_ROOT', '/var/www/html/biotorrents.de/') # Production
        : define('SERVER_ROOT', '/var/www/html/dev.torrents.bio/') # Development
);

ENV::setPub(
    'SERVER_ROOT',
    (!$ENV->DEV
        ? '/var/www/html/biotorrents.de/' # Production
        : '/var/www/html/dev.torrents.bio/') # Development
);

# Where torrent files are stored, e.g., /var/www/torrents-dev/
( # Old format
    !$ENV->DEV
        ? define('TORRENT_STORE', '/var/www/torrents/') # Production
        : define('TORRENT_STORE', '/var/www/torrents-dev/') # Development
);

ENV::setPub(
    'TORRENT_STORE',
    (!$ENV->DEV
        ? '/var/www/torrents/' # Production
        : '/var/www/torrents-dev/') # Development);
);

# Allows you to run static content off another server. Default is usually what you want
define('STATIC_SERVER', '/public/');
ENV::setPub('STATIC_SERVER', '/public/');

# The hashing algorithm used for SRI
ENV::setPub('SRI', 'sha512');


/**
 * App keys
 *
 * Separate keys for development and production.
 * Increased security and protection against config overwrites.
 */

# Pre-shared key for generating hmacs for the image proxy
ENV::setPriv('IMAGE_PSK', '');

 # Production
if (!$ENV->DEV) {
    # Unused in OT Gazelle. Currently used for API token auth
    ENV::setPriv('ENCKEY', '');
  
    # Alphanumeric random key. This key must be the argument to schedule.php for the schedule to work
    ENV::setPriv('SCHEDULE_KEY', '');
  
    # Random key. Used for generating unique RSS auth key
    ENV::setPriv('RSS_HASH', '');

    # System API key. Used for getting resources via Json->fetch()
    ENV::setPriv('SELF_API', '');
}

# Development
else {
    ENV::setPriv('ENCKEY', '');
    ENV::setPriv('SCHEDULE_KEY', '');
    ENV::setPriv('RSS_HASH', '');
    ENV::setPriv('SELF_API', '');
}


/**
 * Database
 */

# Common info
ENV::setPriv('SQLHOST', '10.0.0.3');
ENV::setPriv('SQLPORT', 3306);
#ENV::setPriv('SQLSOCK', '/var/run/mysqld/mysqld.sock');

# TLS client certs
ENV::setPriv('SQL_CERT', "/var/www/tls-keys/client-cert-ohm.pem");
ENV::setPriv('SQL_KEY', "/var/www/tls-keys/client-key-ohm.pem");
ENV::setPriv('SQL_CA', "/var/www/tls-keys/ca.pem");

/*
ENV::setPriv('SQL_CERT', "$ENV->WEB_ROOT/tls-keys/client-cert-ohm.pem");
ENV::setPriv('SQL_KEY', "$ENV->WEB_ROOT/tls-keys/client-key-ohm.pem");
ENV::setPriv('SQL_CA', "$ENV->WEB_ROOT/tls-keys/ca.pem");
*/

 # Production
 if (!$ENV->DEV) {
     ENV::setPriv('SQLDB', 'gazelle_production');
     ENV::setPriv('SQLLOGIN', 'gazelle_production');
     ENV::setPriv('SQLPASS', '');
 }

# Development
else {
    ENV::setPriv('SQLDB', 'gazelle_development');
    ENV::setPriv('SQLLOGIN', 'gazelle_development');
    ENV::setPriv('SQLPASS', '');
}


/**
 * Tracker
 */

# Ocelot connection, e.g., 0.0.0.0
ENV::setPriv('TRACKER_HOST', '0.0.0.0');

 # Production
if (!$ENV->DEV) {
    ENV::setPriv('TRACKER_PORT', 34000);
  
    # Must be 32 alphanumeric characters and match site_password in ocelot.conf
    ENV::setPriv('TRACKER_SECRET', '');

    # Must be 32 alphanumeric characters and match report_password in ocelot.conf
    ENV::setPriv('TRACKER_REPORTKEY', '');
}

# Development
else {
    ENV::setPriv('TRACKER_PORT', 34001);
    ENV::setPriv('TRACKER_SECRET', '');
    ENV::setPriv('TRACKER_REPORTKEY', '');
}


/**
 * Tracker URLs
 *
 * Added to torrents à la http://bittorrent.org/beps/bep_0012.html
 */

 # Production
if (!$ENV->DEV) {
    define('ANNOUNCE_URLS', [
         [ # Tier 1
           'https://track.biotorrents.de:443',
          ], [] # Tier 2
      ]);

    $AnnounceURLs = [
      [ # Tier 1
        'https://track.biotorrents.de:443',
      ],
      [ # Tier 2
        #'udp://tracker.coppersurfer.tk:6969/announce',
        #'udp://tracker.cyberia.is:6969/announce',
        #'udp://tracker.leechers-paradise.org:6969/announce',
      ],
    ];
    ENV::setPub(
        'ANNOUNCE_URLS',
        $ENV->convert($AnnounceURLs)
    );
}

# Development
else {
    define('ANNOUNCE_URLS', [
      [ # Tier 1
        'https://trx.biotorrents.de:443',
      ], [] # Tier 2
    ]);

    $AnnounceURLs = [
      [ # Tier 1
        'https://trx.biotorrents.de:443',
      ], [], # Tier 2
    ];
    ENV::setPub(
        'ANNOUNCE_URLS',
        $ENV->convert($AnnounceURLs)
    );
}


/**
 * Search
 */

# SphinxqlQuery needs constants
# $ENV breaks the torrent and request pages
define('SPHINXQL_HOST', '127.0.0.1');
define('SPHINXQL_PORT', 9306);
define('SPHINXQL_SOCK', false);
define('SPHINX_MAX_MATCHES', 1000); // Must be <= the server's max_matches variable (default 1000)


/**
 * memcached
 *
 * Very important to run two instances,
 * one each for development and production.
 */

 # Production
if (!$ENV->DEV) {
    ENV::setPriv(
        'MEMCACHED_SERVERS',
        [[
          'host' => 'unix:///var/run/memcached/memcached.sock',
          'port' => 0,
          'buckets' => 1
        ]]
    );
}

# Development
else {
    ENV::setPriv(
        'MEMCACHED_SERVERS',
        [[
          'host' => 'unix:///var/run/memcached/memcached-dev.sock',
          'port' => 0,
          'buckets' => 1
        ]]
    );
}


/**
 * IRC/Slack
 */

# IRC server address. Used for onsite chat tool
define('BOT_SERVER', "irc.$ENV->SITE_DOMAIN");
define('SOCKET_LISTEN_ADDRESS', 'localhost');
define('SOCKET_LISTEN_PORT', 51010);
define('BOT_NICK', 'ebooks');

# IRC channels for official business
define('ANNOUNCE_CHAN', '#announce');
define('DEBUG_CHAN', '#debug');
define('REQUEST_CHAN', '#requests');
define('STAFF_CHAN', '#staff');
define('ADMIN_CHAN', '#staff');
define('HELP_CHAN', '#support');
define('DISABLED_CHAN', '#support');
#define('BOT_CHAN', '#userbots');


/**
 * ================
 * =   NO MORE    =
 * = PRIVATE INFO =
 * ================
 */


/**
 * Features
 */

# Enable donation page
ENV::setPub('FEATURE_DONATE', true);

# Send re-enable requests to user's email
define('FEATURE_EMAIL_REENABLE', true);
ENV::setPub('FEATURE_EMAIL_REENABLE', true);

# Require users to verify login from unknown locations
ENV::setPub('FEATURE_ENFORCE_LOCATIONS', false);

# Attempt to send messages to IRC
ENV::setPub('FEATURE_IRC', true);

# Attempt to send email from the site
ENV::setPub('FEATURE_SEND_EMAIL', true);

# Allow the site encryption key to be set without an account
# (should only be used for initial setup)
ENV::setPub('FEATURE_SET_ENC_KEY_PUBLIC', false);

# Attempt to support the BioPHP library
# https://packagist.org/packages/biotorrents/biophp
# https://blog.libredna.org/post/seqhash/
ENV::setPub('FEATURE_BIOPHP', true);


/**
 * Settings
 */

# Production
if (!$ENV->DEV) {
    # Set to false if you don't want everyone to see debug information; can be overriden with 'site_debug'
    define('DEBUG_MODE', false);
    ENV::setPub('DEBUG_MODE', false);
}

# Development
else {
    define('DEBUG_MODE', false);
    ENV::setPub('DEBUG_MODE', false);

    # Gazelle's debug mode is broken, so let's use PHP errors instead
    error_reporting(E_ALL);
}

# Set to false to disable open registration, true to allow anyone to register
ENV::setPub(
    'OPEN_REGISTRATION',
    (!$ENV->DEV
        ? true # Production
        : false) # Development
);

# The maximum number of users the site can have, 0 for no limit
define('USER_LIMIT', 0);
ENV::setPub('USER_LIMIT', 0);

# User perks
ENV::setPub('STARTING_INVITES', 2);
ENV::setPub('STARTING_TOKENS', 2);
ENV::setPub('STARTING_UPLOAD', 5368709120);
ENV::setPub('DONOR_INVITES', 2);

# Bonus Points
define('BONUS_POINTS', 'Bonus Points');
ENV::setPub('BONUS_POINTS', 'Bonus Points');

ENV::setPub('BP_COEFF', 1.5); # OT default 0.5

# Tag namespaces (configurable via CSS selectors)
#define('TAG_NAMESPACES', ['male', 'female', 'parody', 'character']);

# Banned stuff (file characters, browsers, etc.)
ENV::setPub(
    'BAD_CHARS',
    ['"', '*', '/', ':', '<', '>', '?', '\\', '|']
);

# Password length limits
ENV::setPub('PW_MIN', 15); # Brute force
ENV::setPub('PW_MAX', 10000); # DDoS; default 307200

# Misc stuff like generic reusable snippets
# Example of a variable using heredoc syntax
ENV::setPub(
    'PW_ADVICE',
    <<<HTML
    <p>
      Any password $ENV->PW_MIN characters or longer is accepted, but a strong password
      <ul>
        <li>is a pass<em>phrase</em> of mixed case with many small words,</li>
        <li>that contains complex characters including Unicode and emoji.</li>
      </ul>
    </p>
HTML
);


/**
 * Services
 *
 * Public APIs, domains, etc.
 * Not intended for private API keys.
 */

# Current Sci-Hub domains
# https://lovescihub.wordpress.com
define('SCI_HUB', 'se');
ENV::setPub(
    'SCI_HUB',
    ['ren', 'tw', 'se']
);

# Semantic Scholar
# https://api.semanticscholar.org
ENV::setPub('SS', 'https://api.semanticscholar.org/v1/paper/');

# IP Geolocation
ENV::setPub('IP_GEO', 'https://tools.keycdn.com/geo.json?host=');


/**
 * User class IDs
 *
 * Needed for automatic promotions.
 * Found in the `permissions` table.
 */

#      Name of class     Class ID (not level)
define('ADMIN',          '1');
define('USER',           '2');
define('MEMBER',         '3');
define('POWER',          '4');
define('ELITE',          '5');
define('LEGEND',         '8');
define('MOD',            '11');
define('SYSOP',          '15');
define('ARTIST',         '19');
define('DONOR',          '20');
define('VIP',            '21');
define('TORRENT_MASTER', '23');
define('POWER_TM',       '24');
define('FLS_TEAM',       '33');
define('FORUM_MOD',      '9001');


/**
 * Forums
 */

define('STAFF_FORUM', 3);
define('DONOR_FORUM', 7);

ENV::setPub('TRASH_FORUM', 8);
ENV::setPub('ANNOUNCEMENT_FORUM', 1);
ENV::setPub('SUGGESTIONS_FORUM', 2);

# Pagination
define('TORRENT_COMMENTS_PER_PAGE', 10);
define('POSTS_PER_PAGE', 25);
define('TOPICS_PER_PAGE', 50);
define('TORRENTS_PER_PAGE', 50);
define('REQUESTS_PER_PAGE', 25);
define('MESSAGES_PER_PAGE', 25);
define('LOG_ENTRIES_PER_PAGE', 50);

# Cache catalogues
define('THREAD_CATALOGUE', 500); // Limit to THREAD_CATALOGUE posts per cache key

# Miscellaneous values
define('MAX_RANK', 6);
define('MAX_EXTRA_RANK', 8);
define('MAX_SPECIAL_RANK', 3);

ENV::setPub('DONOR_FORUM_RANK', 6);


/**
 * Ratio and badges
 */

# Ratio requirements, in descending order
define('RATIO_REQUIREMENTS', [
 # Downloaded     Req (0% seed) Req (100% seed)
  [200 * 1024**3, 0.60,         0.60],
  [160 * 1024**3, 0.60,         0.50],
  [120 * 1024**3, 0.50,         0.40],
  [100 * 1024**3, 0.40,         0.30],
  [80  * 1024**3, 0.30,         0.20],
  [60  * 1024**3, 0.20,         0.10],
  [40  * 1024**3, 0.15,         0.00],
  [20  * 1024**3, 0.10,         0.00],
  [10  * 1024**3, 0.05,         0.00],
]);

# God I wish I didn't have to do this but I just don't care anymore
$AutomatedBadgeIDs = [
  'DL' => [
    '8'    => 10,
    '16'   => 11,
    '32'   => 12,
    '64'   => 13,
    '128'  => 14,
    '256'  => 15,
    '512'  => 16,
    '1024' => 17,
    '2048' => 18,
  ],

  'UL' => [
    '16'   => 20,
    '32'   => 21,
    '64'   => 22,
    '128'  => 23,
    '256'  => 24,
    '512'  => 25,
    '1024' => 26,
    '2048' => 27,
    '4096' => 28,
  ],

  'Posts' => [
    '5'    => 30,
    '10'   => 31,
    '25'   => 32,
    '50'   => 33,
    '100'  => 34,
    '250'  => 35,
    '500'  => 36,
    '1000' => 37,
    '2500' => 38,
  ]
];
ENV::setPub(
    'AUTOMATED_BADGE_IDS',
    $ENV->convert($AutomatedBadgeIDs)
);


/**
 * Site categories and meta
 *
 * THIS IS THE OLD FORMAT AND WILL GO AWAY.
 * PLEASE SEE $ENV->{DB,META,CATS} BELOW.
 */

# Categories
$Categories = [
  'Sequences',
  'Graphs',
  'Systems',
  'Geometric',
  'Scalars/Vectors',
  'Patterns',
  'Constraints',
  'Images',
  'Spatial',
  'Models',
  'Documents',
  'Machine Data',
];
$GroupedCategories = $Categories;

# Plain Formats
$PlainFormats = [
  'CSV'   => ['csv'],
  'JSON'  => ['json'],
  'Text'  => ['txt'],
  'XML'   => ['xml'],
];

# Sequence Formats
$SeqFormats = [
  'BAM'        => ['bam'],
  'CRAM'       => ['cram'],
  'EMBL'       => ['embl'],
  'FASTA'      => ['fa', 'fasta', 'fsa'],
  'FASTA+QUAL' => ['qual'],
  'CSFASTA'    => ['csfa', 'csfasta', 'csfsa'],
  'FASTQ'      => ['fastq', 'fq', 'sanfastq'],
  'GFF'        => ['gff', 'gff2', 'gff3'],
  'GTF'        => ['gtf'],
  'GenBank'    => ['gb', 'gbk', 'genbank'],
  'HDF5'       => ['bash5', 'baxh5', 'fast5', 'h5', 'hdf5'],
  'PIR'        => ['pir'],
  'QSeq'       => ['qseq'],
  'SAM'        => ['sam'],
  'SFF'        => ['sff'],
  'SRF'        => ['srf'],
  'SnapGene'   => ['dna', 'seq'],
  'SwissProt'  => ['dat'],
  'VCF'        => ['vcf'],
];

# Protein Formats
# DON'T PARSE RAW FILES. TOO MANY COMPETING VENDORS
$ProtFormats = [
  'ABI/Sciex'      => ['t2d', 'wiff'],
  'APML'           => ['apml'],
  'ASF'            => ['asf'],
  'Agilent/Bruker' => ['baf', 'd', 'fid', 'tdf', 'yep'],
  'BlibBuild'      => ['blib'],
  'Bruker/Varian'  => ['sms', 'xms'],
  'Finnigan'       => ['dat', 'ms'],
  'ION-TOF'        => ['ita', 'itm'],
  'JCAMP-DX'       => ['jdx'],
  'MGF'            => ['mgf'],
  'MS2'            => ['ms2'],
  'MSF'            => ['msf'],
  'mzData'         => ['mzdata'],
  'mzML'           => ['mzml'],
  'mzXML'          => ['mzxml'],
  'OMSSA'          => ['omssa', 'omx'],
  'PEFF'           => ['peff'],
  'pepXML'         => ['pepxml'],
  'protXML'        => ['protxml'],
  'Shimadzu'       => ['lcd', 'qgd', 'spc'],
  'Skyline'        => ['sky', 'skyd'],
  'TPP/SPC'        => ['dta'],
  'Tandem'         => ['tandem'],
  'TraML'          => ['traml'],
  'ULVAC-PHI'      => ['tdc'],
];

# XML Graph Formats
$GraphXmlFormats = [
  'DGML'    => ['dgml'],
  'DotML'   => ['dotml'],
  'GEXF'    => ['gexf'],
  'GXL'     => ['gxl'],
  'GraphML' => ['graphml'],
  'XGMML'   => ['xgmml'],
];

# Text Graph Formats
$GraphTxtFormats = [
  'DOT'    => ['gv'],
  'GML'    => ['gml'],
  'LCF'    => ['lcf'],
  'Newick' => ['xsd', 'sgf'],
  'SIF'    => ['sif'],
  'TGF'    => ['tgf'],
];

# Image Formats
# https://www.ncbi.nlm.nih.gov/pmc/articles/PMC3948928/
$ImgFormats = [
  'Analyze'   => ['hdr', 'img'],
  'Interfile' => ['h33'],
  'DICOM'     => ['dcm', 'dicom'],
  'HDF5'      => ['bash5', 'baxh5', 'fast5', 'h5', 'hdf5'],
  'NIfTI'     => ['nii', 'nifti'],
  'MINC'      => ['minc', 'mnc'],
  'JPEG'      => ['jfif', 'jpeg', 'jpg'],
  'JPEG 2000' => ['j2k', 'jp2', 'jpf', 'jpm', 'jpx', 'mj2'],
  'PNG'       => ['png'],
  'TIFF'      => ['tif', 'tiff'],
  'WebP'      => ['webp'],
];

# Vector Map Formats
$MapVectorFormats = [
  'AutoCAD DXF'       => ['dxf'],
  'Cartesian (XYZ)'   => ['xyz'],
  'DLG'               => ['dlg'],
  'Esri TIN'          => ['adf', 'dbf'],
  'GML'               => ['gml'],
  'GeoJSON'           => ['geojson'],
  'ISFC'              => ['isfc'],
  'KML'               => ['kml', 'kmzv'],
  # DAT omitted
  # https://en.wikipedia.org/wiki/MapInfo_TAB_format
  'MapInfo TAB'       => ['tab', 'ind', 'map', 'id'],
  'Measure Map Pro'   => ['mmp'],
  'NTF'               => ['ntf'],
  # DBF omitted
  # https://en.wikipedia.org/wiki/Shapefile
  'Shapefile'         => ['shp', 'shx'],
  'Spatial Data File' => ['sdf', 'sdf3', 'sif', 'kif'],
  'SOSI'              => ['sosi'],
  'SVG'               => ['svg'],
  'TIGER'             => ['tiger'],
  'VPF'               => ['vpf'],
];

# Raster Map Formats
$MapRasterFormats = [
  'ADRG'      => ['adrg'],
  'Binary'    => ['bsq', 'bip', 'bil'],
  'DRG'       => ['drg'],
  'ECRG'      => ['ecrg'],
  'ECW'       => ['ecw'],
  # DAT and ASC omitted (common)
  # https://support.esri.com/en/technical-article/000008526
  # https://web.archive.org/web/20150128024528/http://docs.codehaus.org/display/GEOTOOLS/ArcInfo+ASCII+Grid+format
  'Esri Grid' => ['adf', 'nit', 'asc', 'grd'],
  'GeoTIFF'   => ['tfw'],
  #'IMG'       => ['img'],
  #'JPEG 2000' => ['j2k', 'jp2', 'jpf', 'jpm', 'jpx', 'mj2'],
  'MrSID'     => ['sid'],
  'netCDF'    => ['nc'],
  'RPF'       => ['cadrg', 'cib'],
];

# Binary Document Formats
# https://en.wikipedia.org/wiki/OpenDocument
# https://en.wikipedia.org/wiki/List_of_Microsoft_Office_filename_extensions
$BinDocFormats = [
  'OpenDocument' => ['odt', 'fodt', 'ods', 'fods', 'odp', 'fodp', 'odg', 'fodg', 'odf'],
  'Word'         => ['doc', 'dot', 'wbk', 'docx', 'docm', 'dotx', 'dotm', 'docb'],
  'PowerPoint'   => ['ppt', 'pot', 'pps', 'pptx', 'pptm', 'potx', 'potm', 'ppam', 'ppsx', 'ppsm', 'sldx', 'sldm'],
  'Excel'        => ['xls', 'xlt', 'xlm', 'xlsx', 'xlsm', 'xltx', 'xltm', 'xlsb', 'xla', 'xlam', 'xll', 'xlw'],
  'PDF'          => ['pdf', 'fdf', 'xfdf'],
];

# Extra Formats
# DON'T PARSE IMG OR ISO FILES
# https://en.wikipedia.org/wiki/Disk_image#File_formats
# http://dcjtech.info/topic/python-file-extensions/
$CpuGenFormats = [
  'Docker'       => ['dockerfile'],
  'Hard Disk'    => ['fvd', 'dmg', 'esd', 'qcow', 'qcow2', 'qcow3', 'smi', 'swm', 'vdi', 'vhd', 'vhdx', 'vmdk', 'wim'],
  'Optical Disc' => ['bin', 'ccd', 'cso', 'cue', 'daa', 'isz', 'mdf', 'mds', 'mdx', 'nrg', 'uif'],
  'Python'       => ['pxd', 'py', 'py3', 'pyc', 'pyd', 'pyde', 'pyi', 'pyo', 'pyp', 'pyt', 'pyw', 'pywz', 'pyx', 'pyz', 'rpy', 'xpy'],
  'Jupyter'      => ['ipynb'],
  'Ontology'     => ['cgif', 'cl', 'clif', 'csv', 'htm', 'html', 'kif', 'obo', 'owl', 'rdf', 'rdfa', 'rdfs', 'rif', 'tsv', 'xcl', 'xht', 'xhtml', 'xml'],
];

# Resolutions
$Resolutions = [
  'Contig',
  'Scaffold',
  'Chromosome',
  'Genome',
  'Proteome',
  'Transcriptome',
];


/**
 * $ENV->DB
 *
 * One flat array with all possible torrent/group fields.
 * These are mostly used in Twig templates as {{ db.title }}.
 * Meta abstraction layer for flavor text *around* DB fields.
 * Gazelle's job is to query the right tables, which will shift.
 */

$DB = [
    # torrents_group
    'category_id' => ['name' => 'Category', 'desc' => ''],
    'title' => ['name' => 'Torrent Title', 'desc' => 'Definition line, e.g., Alcohol dehydrogenase ADH1'],
    'subject' => ['name' => 'Organism', 'desc' => 'Organism line binomial, e.g., Saccharomyces cerevisiae', 'icon' => '🦠'],
    'object' => ['name' => 'Strain/Variety', 'desc' => 'Organism line if any, e.g., S288C'],
    'year' => ['name' => 'Year', 'desc' => 'Publication year', 'icon' => '📅'],
    'workgroup' => ['name' => 'Department/Lab', 'desc' => "Last author's institution, e.g., Lawrence Berkeley Laboratory", 'icon' => '🏫'],
    'location' => ['name' => 'Location', 'desc' => 'Physical location, e.g., Berkeley, CA 94720', 'icon' => '📍'],
    'identifier' => ['name' => 'Accession Number', 'desc' => 'RefSeq and UniProt preferred', 'icon' => '🔑'],
    'tag_list' => ['name' => 'Tag List', 'desc' => 'Comma-seperated list of at least 5 tags'],
    'timestamp' => ['name' => 'Uploaded On', 'desc' => ''],
    'revision_id' => ['name' => 'Revision ID', 'desc' => ''],
    'description' => ['name' => 'Group Description', 'desc' => ''],
    'picture' => ['name' => 'Picture', 'desc' => 'A meaningful picture, e.g., the specimen or a thumbnail'],

    # From the non-renamed `torrents` table
    'version' => ['name' => 'Version', 'desc' => 'Start with 0.1.0', 'note' => 'Please see <a href="https://semver.org" target="_blank">Semantic Versioning</a>'],
    'license' => ['name' => 'License', 'desc' => '', 'note' => 'Please see <a href="http://www.dcc.ac.uk/resources/how-guides/license-research-data" target="_blank">How to License Research Data</a>'],
    'mirrors' => ['name' => 'Mirrors', 'desc' => 'Up to two FTP/HTTP addresses that either point directly to a file, or for multi-file torrents, to the enclosing folder'],

    # Original fields
    'seqhash' => ['name' => 'Seqhash', 'desc' => 'Sample genome sequence in FASTA format (GenBank pending)', 'note' => 'Please see <a href="https://blog.libredna.org/post/seqhash/" target="_blank">The Seqhash Algorithm</a>'],
];
ENV::setPub(
    'DB',
    $ENV->convert($DB)
);


/**
 * $ENV->META
 *
 * Main metadata object.
 * Responsible for defining field values.
 * These eventually go into the database,
 * so take care to define them well here.
 * Avoid nesting > 3 levels deep.
 */
$META = [

    /**
     * 1.
     * PLATFORMS
     */

    'Platforms' => [

        /**
         * 2.
         * Sequences
         */
        'Sequences' => [
            # DNA
            'Complete Genomics',
            'cPAS-BGI/MGI',
            'Helicos',
            'Illumina HiSeq',
            'Illumina MiSeq',
            'Ion Torrent',
            'Microfluidics',
            'Nanopore',
            'PacBio',
            'Roche 454',
            'Sanger',
            'SOLiD',
            # RNA, Protein, etc.
            'De Novo',
            'HPLC',
            'Mass Spec',
            'RNA-Seq',
        ],

        /**
         * 2.
         * Graphs
         * https://en.wikipedia.org/wiki/Graph_drawing#Software
         */
        'Graphs' => [
            'BioFabric',
            'BioTapestry',
            'Cytoscape',
            'Edraw Max',
            'GenMAPP',
            'Gephi',
            'graph-tool',
            'Graphviz',
            'InCroMAP',
            'LaNet-vi',
            'Linkurious',
            'MATLAB',
            'MEGA',
            'Maple',
            'Mathematica',
            #'Microsoft Automatic Graph Layout',
            'NetworkX',
            'PGF/TikZ',
            'PathVisio',
            'Pathview',
            'R',
            'Systrip',
            'Tom Sawyer Software',
            'Tulip',
            'yEd',
        ],

        /**
         * 2.
         * Images
         */
        'Images' => [
            'CT/CAT',
            'ECG',
            'Elastography',
            'FNIR/NIRS',
            'MPI',
            'MRI/NMR',
            'Microscopy',
            'Photoacoustic',
            'Photography',
            'Scint/SPECT/PET',
            'Ultrasound',
            'X-Rays',
        ],

        /**
         * 2.
         * Documents
         */
        'Documents' => [
            # Composed
            'Literature',
            'Software',
            # Generated
            'Kernel',
            'Metadata',
            'Notebook',
        ],
    
        /**
         * 2.
         * Machine Data
         */
        'Raw' => [
            'Binary',
            'Text',
        ],
    ], # End $ENV->META->Platforms

    /**
     * 1.
     * FORMATS
     */

    'Formats' => [

        /**
         * 2.
         * Plain
         */
        'Plain' => [
            'CSV'   => ['csv'], # 3
            'JSON'  => ['json'], # 3
            'Text'  => ['txt', 'asc'], # 3
            'XML'   => ['xml'], # etc.
        ],

        /**
         * 2.
         * Databases
         */
        'Databases' => [
            'MS SQL'   => ['mdf', 'ndf', 'ldf'],
            'MySQL'   => ['sql', 'mysql'],
            'Oracle' => ['dbf', 'ora', 'oraenv'],
            'IBM Db2' => ['ixf', 'del', 'cursor'],
            'Postgres' => ['sql']
        ],


        /**
         * 2.
         * Archives
         */
        'Archives' => [
            '7z'       => ['7z'],
            'bzip2'    => ['bz2', 'bzip2'],
            'gzip'     => ['gz', 'gzip', 'tgz', 'tpz'],
            'Pickle'   => ['pickle', 'pkl'],
            'RAR'      => ['rar', 'rev'],
            'tar'      => ['tar'],
            'ZIP'      => ['zip', 'zipx'],
            'None'     => [''],
        ],

        /**
         * 2.
         * Sequences
         * https://www.ncbi.nlm.nih.gov/sra/docs/submitformats/
         */
        'Sequences' => [
            'BAM'        => ['bam'],
            'CRAM'       => ['cram'],
            'EMBL'       => ['embl'],
            'FASTA'      => ['fa', 'fasta', 'fsa'],
            'FASTA+QUAL' => ['qual'],
            'CSFASTA'    => ['csfa', 'csfasta', 'csfsa'],
            'FASTQ'      => ['fastq', 'fq', 'sanfastq'],
            'GFF'        => ['gff', 'gff2', 'gff3'],
            'GTF'        => ['gtf'],
            'GenBank'    => ['gb', 'gbk', 'genbank'],
            'HDF5'       => ['bash5', 'baxh5', 'fast5', 'h5', 'hdf5'],
            'PIR'        => ['pir'],
            'QSeq'       => ['qseq'],
            'SAM'        => ['sam'],
            'SFF'        => ['sff'],
            'SRF'        => ['srf'],
            'SnapGene'   => ['dna', 'seq'],
            'SwissProt'  => ['dat'],
            'VCF'        => ['vcf'],
        ],

        /**
         * 2.
         * Proteins
         * https://www.ncbi.nlm.nih.gov/pmc/articles/PMC3518119/
         */
        'Proteins' => [
            'ABI/Sciex'      => ['t2d', 'wiff'],
            'APML'           => ['apml'],
            'ASF'            => ['asf'],
            'Agilent/Bruker' => ['baf', 'd', 'fid', 'tdf', 'yep'],
            'BlibBuild'      => ['blib'],
            'Bruker/Varian'  => ['sms', 'xms'],
            'Finnigan'       => ['dat', 'ms'],
            'ION-TOF'        => ['ita', 'itm'],
            'JCAMP-DX'       => ['jdx'],
            'MGF'            => ['mgf'],
            'MS2'            => ['ms2'],
            'MSF'            => ['msf'],
            'mzData'         => ['mzdata'],
            'mzML'           => ['mzml'],
            'mzXML'          => ['mzxml'],
            'OMSSA'          => ['omssa', 'omx'],
            'PEFF'           => ['peff'],
            'pepXML'         => ['pepxml'],
            'protXML'        => ['protxml'],
            'Shimadzu'       => ['lcd', 'qgd', 'spc'],
            'Skyline'        => ['sky', 'skyd'],
            'TPP/SPC'        => ['dta'],
            'Tandem'         => ['tandem'],
            'TraML'          => ['traml'],
            'ULVAC-PHI'      => ['tdc'],
        ],

        /**
         * 2.
         * Graph XML
         */
        'GraphXml' => [
            'DGML'    => ['dgml'],
            'DotML'   => ['dotml'],
            'GEXF'    => ['gexf'],
            'GXL'     => ['gxl'],
            'GraphML' => ['graphml'],
            'XGMML'   => ['xgmml'],
        ],

        /**
         * 2.
         * Graph plain
         */
        'GraphTxt' => [
            'DOT'    => ['gv'],
            'GML'    => ['gml'],
            'LCF'    => ['lcf'],
            'Newick' => ['xsd', 'sgf'],
            'SIF'    => ['sif'],
            'TGF'    => ['tgf'],
        ],
        
        /**
         * 2.
         * Image vector
         */
        'ImgVector' => [
            'AI'        => ['ai'],
            'CorelDRAW' => ['cdr'],
            'EPS'       => ['eps', 'epsf', 'epsi'],
            'SVG'       => ['svg'],
            'WMF'       => ['emf', 'emz', 'wmf', 'wmz'],
        ],

        /**
         * 2.
         * Image raster
         */
        'ImgRaster' => [
            'Analyze'   => ['hdr', 'img'],
            'Interfile' => ['h33'],
            'DICOM'     => ['dcm', 'dicom'],
            'HDF5'      => ['bash5', 'baxh5', 'fast5', 'h5', 'hdf5'],
            'NIfTI'     => ['nii', 'nifti'],
            'MINC'      => ['minc', 'mnc'],
            'JPEG'      => ['jfif', 'jpeg', 'jpg'],
            'JPEG 2000' => ['j2k', 'jp2', 'jpf', 'jpm', 'jpx', 'mj2'],
            'PNG'       => ['png'],
            'TIFF'      => ['tif', 'tiff'],
            'WebP'      => ['webp'],
        ],
    
        /**
         * 2.
         * Map vector
         */
        'MapVector' => [
            'AutoCAD DXF'       => ['dxf'],
            'Cartesian (XYZ)'   => ['xyz'],
            'DLG'               => ['dlg'],
            'Esri TIN'          => ['adf', 'dbf'],
            'GML'               => ['gml'],
            'GeoJSON'           => ['geojson'],
            'ISFC'              => ['isfc'],
            'KML'               => ['kml', 'kmzv'],
            # DAT omitted
            # https://en.wikipedia.org/wiki/MapInfo_TAB_format
            'MapInfo TAB'       => ['tab', 'ind', 'map', 'id'],
            'Measure Map Pro'   => ['mmp'],
            'NTF'               => ['ntf'],
            # DBF omitted
            # https://en.wikipedia.org/wiki/Shapefile
            'Shapefile'         => ['shp', 'shx'],
            'Spatial Data File' => ['sdf', 'sdf3', 'sif', 'kif'],
            'SOSI'              => ['sosi'],
            'SVG'               => ['svg'],
            'TIGER'             => ['tiger'],
            'VPF'               => ['vpf'],
        ],

        /**
         * 2.
         * Map raster
         */
        'MapRaster' => [
            'ADRG'      => ['adrg'],
            'Binary'    => ['bsq', 'bip', 'bil'],
            'DRG'       => ['drg'],
            'ECRG'      => ['ecrg'],
            'ECW'       => ['ecw'],
            # DAT and ASC omitted (common)
            # https://support.esri.com/en/technical-article/000008526
            # https://web.archive.org/web/20150128024528/http://docs.codehaus.org/display/GEOTOOLS/ArcInfo+ASCII+Grid+format
            'Esri Grid' => ['adf', 'nit', 'asc', 'grd'],
            'GeoTIFF'   => ['tfw'],
            #'IMG'       => ['img'],
            #'JPEG 2000' => ['j2k', 'jp2', 'jpf', 'jpm', 'jpx', 'mj2'],
            'MrSID'     => ['sid'],
            'netCDF'    => ['nc'],
            'RPF'       => ['cadrg', 'cib'],
        ],

        /**
         * 2.
         * Binary documents
         */
        'BinDoc' => [
            'OpenDocument' => ['odt', 'fodt', 'ods', 'fods', 'odp', 'fodp', 'odg', 'fodg', 'odf'],
            'Word'         => ['doc', 'dot', 'wbk', 'docx', 'docm', 'dotx', 'dotm', 'docb'],
            'PowerPoint'   => ['ppt', 'pot', 'pps', 'pptx', 'pptm', 'potx', 'potm', 'ppam', 'ppsx', 'ppsm', 'sldx', 'sldm'],
            'Excel'        => ['xls', 'xlt', 'xlm', 'xlsx', 'xlsm', 'xltx', 'xltm', 'xlsb', 'xla', 'xlam', 'xll', 'xlw'],
            'PDF'          => ['pdf', 'fdf', 'xfdf'],
        ],
    
        /**
         * 2.
         * Extra formats
         */
        'CpuGen' => [
            'Docker'       => ['dockerfile'],
            'Hard Disk'    => ['fvd', 'dmg', 'esd', 'qcow', 'qcow2', 'qcow3', 'smi', 'swm', 'vdi', 'vhd', 'vhdx', 'vmdk', 'wim'],
            'Optical Disc' => ['bin', 'ccd', 'cso', 'cue', 'daa', 'isz', 'mdf', 'mds', 'mdx', 'nrg', 'uif'],
            'Python'       => ['pxd', 'py', 'py3', 'pyc', 'pyd', 'pyde', 'pyi', 'pyo', 'pyp', 'pyt', 'pyw', 'pywz', 'pyx', 'pyz', 'rpy', 'xpy'],
            'Jupyter'      => ['ipynb'],
            'Ontology'     => ['cgif', 'cl', 'clif', 'csv', 'htm', 'html', 'kif', 'obo', 'owl', 'rdf', 'rdfa', 'rdfs', 'rif', 'tsv', 'xcl', 'xht', 'xhtml', 'xml'],
        ],
    ], # End $ENV->META->Formats


    /**
     * 1.
     * SCOPES
     */

    'Scopes' => [

        /**
         * 2.
         * SI
         */
        'SI' => [
            'Nano',
            'Micro',
            'Milli',
            'Centi',
            'Kilo',
            'Mega',
            'Giga',
            'Tera',
        ],

        /**
         * 2.
         * Sequences
         */
        'Sequences' => [
            'Contig',
            'Scaffold',
            'Chromosome',
            'Genome',
            'Proteome',
            'Transcriptome',
        ],

        /**
         * 2.
         * Locations
         */
        'Locations' => [
            'Organization',
            'Locality',
            'State',
            'Province',
            'Country',
            'Continent',
            'World',
        ],

        /**
         * 2.
         * XML
         */
        'XML' => [
            'Value',
            'Attribute',
            'Group',
            'Element',
            'Schema',
        ],
    
        /**
         * 2.
         * Scalar
         */
        'Scalar' => [
            'Area',
            'Density',
            'Distance',
            'Energy',
            'Mass',
            'Speed',
            'Temperature',
            'Time',
            'Volume',
            'Work',
        ],
    
        /**
         * 2.
         * Vector
         */
        'Vector' => [
            'Acceleration',
            'Displacement',
            'Force',
            'Polarization',
            'Momentum',
            'Position',
            'Thrust',
            'Velocity',
            'Weight',
        ],
    ], # End $ENV->META->Scopes

    /**
     * 1.
     * LICENSES
     */

    'Licenses' => [
        'BSD-2',
        'BSD-3',
        'CC BY',
        'CC BY-SA',
        'CC BY-ND',
        'CC BY-NC',
        'CC BY-NC-SA',
        'CC BY-NC-ND',
        'GNU GPL',
        'GNU LGPL',
        'GNU AGPL',
        'GNU FDL',
        'MIT',
        'ODC-By',
        'ODC-ODbL',
        'OpenMTA',
        'Public Domain',
        'Unspecified',
    ], # End $ENV->META->Licenses
];
ENV::setPub(
    'META',
    $ENV->convert($META)
);


/**
 * Categories
 * https://www.ncbi.nlm.nih.gov/books/NBK25464/
 */

$CollageCats = [
  0 => 'Personal',
  1 => 'Theme',
  2 => 'Staff Picks',
  3 => 'Group Picks',
];

$CATS = [
    1 => [
        'ID' => 1,
        'Name' => 'Sequences',
        'Description' => "For data that's ACGT, ACGU, amino acid letters on disk.",
        'Platforms' => $ENV->META->Platforms->Sequences,
        'Formats' => [
            $ENV->META->Formats->Sequences,
            $ENV->META->Formats->Proteins,
            $ENV->META->Formats->Plain,
        ],
    ],

    2 => [
        'ID' => 2,
        'Name' => 'Graphs',
        'Description' => 'For pathway and regulatory network data, structured taxonomies, etc.',
        'Platforms' => $ENV->META->Platforms->Graphs,
        'Formats' => [
            $ENV->META->Formats->GraphXml,
            $ENV->META->Formats->GraphTxt,
            $ENV->META->Formats->Plain,
        ],
    ],

    3 => [
        'ID' => 3,
        'Name' => 'Systems',
        'Description' => 'For data that examines one facet broadly, not one subject deeply.',
        'Platforms' => $ENV->META->Platforms->Graphs,
        'Formats' => [
            $ENV->META->Formats->GraphXml,
            $ENV->META->Formats->GraphTxt,
            $ENV->META->Formats->Plain,
        ],
    ],

    4 => [
        'ID' => 4,
        'Name' => 'Geometric',
        'Description' => "For structured data (XML, etc.) that describes the subject's orientation in space.",
        'Platforms' => $ENV->META->Platforms->Graphs,
        'Formats' => [
            $ENV->META->Formats->GraphXml,
            $ENV->META->Formats->GraphTxt,
            $ENV->META->Formats->Plain,
        ],
    ],

    5 => [
        'ID' => 5,
        'Name' => 'Scalars/Vectors',
        'Description' => 'For data that describes observations over time and/or space.',
        'Platforms' => $ENV->META->Platforms->Graphs,
        'Formats' => [
            $ENV->META->Formats->GraphXml,
            $ENV->META->Formats->GraphTxt,
            $ENV->META->Formats->Plain,
        ],
    ],

    6 => [
        'ID' => 6,
        'Name' => 'Patterns',
        'Description' => 'For data that describes recurring structures in nature such as common pathways or motifs in the proteome or metabolome.',
        'Platforms' => $ENV->META->Platforms->Graphs,
        'Formats' => [
            $ENV->META->Formats->GraphXml,
            $ENV->META->Formats->GraphTxt,
            $ENV->META->Formats->Plain,
        ],
    ],

    7 => [
        'ID' => 7,
        'Name' => 'Constraints',
        'Description' => 'For data that records experimental control behavior, checks readings against known physical constants, tracks the thermodynamic limits of reactions, etc.',
        'Platforms' => $ENV->META->Platforms->Graphs,
        'Formats' => [
            $ENV->META->Formats->GraphXml,
            $ENV->META->Formats->GraphTxt,
            $ENV->META->Formats->Plain,
        ],
    ],

    8 => [
        'ID' => 8,
        'Name' => 'Images',
        'Description' => 'For data you can look at!',
        'Platforms' => $ENV->META->Platforms->Images,
        'Formats' => [
            $ENV->META->Formats->ImgRaster,
            $ENV->META->Formats->ImgVector,
        ],
    ],

    9 => [
        'ID' => 9,
        'Name' => 'Spatial',
        'Description' => "For data that's limited to specific locations or otherwise describes macroscopic space.",
        'Platforms' => $ENV->META->Platforms->Graphs,
        'Formats' => [
            $ENV->META->Formats->MapRaster,
            $ENV->META->Formats->MapVector,
            $ENV->META->Formats->ImgRaster,
            $ENV->META->Formats->ImgVector,
        ],
    ],

    10 => [
        'ID' => 10,
        'Name' => 'Models',
        'Description' => 'For projections, simulations, and other hypothetical or computer-generated data.',
        'Platforms' => $ENV->META->Platforms->Graphs,
        'Formats' => [
            $ENV->META->Formats->MapRaster,
            $ENV->META->Formats->MapVector,
            $ENV->META->Formats->ImgRaster,
            $ENV->META->Formats->ImgVector,
        ],
    ],

    11 => [
        'ID' => 11,
        'Name' => 'Documents',
        'Description' => 'For documentation, software, disk images, and literature datasets.',
        'Platforms' => $ENV->META->Platforms->Documents,
        'Formats' => [
            $ENV->META->Formats->BinDoc,
            $ENV->META->Formats->CpuGen,
            $ENV->META->Formats->Plain,
        ],
    ],

    12 => [
        'ID' => 12,
        'Name' => 'Machine Data',
        'Description' => 'For raw reads and machine data of any category.',
        'Platforms' => $ENV->META->Platforms->Raw,
        'Formats' => [
            $ENV->META->Formats->Plain,
        ],
    ],
];
ENV::setPub(
    'CATS',
    $ENV->convert($CATS)
);


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
    "($ENV->RESOURCE_REGEX)($ENV->IP_REGEX|$ENV->DOMAIN_REGEX)($ENV->PORT_REGEX)?(\/\S*)*"
);

define('USERNAME_REGEX', '/^[a-z0-9_]{2,20}$/iD');
ENV::setPub(
    'USERNAME_REGEX',
    '/^[a-z0-9_]{2,20}$/iD'
);

define('EMAIL_REGEX', '[_a-z0-9-]+([.+][_a-z0-9-]+)*@'.DOMAIN_REGEX);
ENV::setPub(
    'EMAIL_REGEX',
    "[_a-z0-9-]+([.+][_a-z0-9-]+)*@$ENV->DOMAIN_REGEX"
);

define('IMAGE_REGEX', URL_REGEX.'\/\S+\.(jpg|jpeg|tif|tiff|png|gif|bmp)(\?\S*)?');
ENV::setPub(
    'IMAGE_REGEX',
    "$ENV->URL_REGEX\/\S+\.(jpg|jpeg|tif|tiff|png|gif|bmp)(\?\S*)?"
);

define('VIDEO_REGEX', URL_REGEX.'\/\S+\.(webm)(\?\S*)?');
ENV::setPub(
    'VIDEO_REGEX',
    "$ENV->URL_REGEX\/\S+\.(webm)(\?\S*)?"
);

define('CSS_REGEX', URL_REGEX.'\/\S+\.css(\?\S*)?');
ENV::setPub(
    'CSS_REGEX',
    "$ENV->URL_REGEX\/\S+\.css(\?\S*)?"
);

define('SITELINK_REGEX', RESOURCE_REGEX.'(www.)?'.preg_quote(SITE_DOMAIN, '/'));
ENV::setPub(
    'SITELINK_REGEX',
    "$ENV->RESOURCE_REGEX(www.)?".preg_quote(SITE_DOMAIN, '/')
);

define('TORRENT_REGEX', SITELINK_REGEX.'\/torrents\.php\?(.*&)?torrentid=(\d+)'); // torrentid = group 4
ENV::setPub(
    'TORRENT_REGEX',
    "$ENV->SITELINK_REGEX\/torrents\.php\?(.*&)?torrentid=(\d+)"
);

define('TORRENT_GROUP_REGEX', SITELINK_REGEX.'\/torrents\.php\?(.*&)?id=(\d+)'); // id = group 4
ENV::setPub(
    'TORRENT_GROUP_REGEX',
    "$ENV->SITELINK_REGEX\/torrents\.php\?(.*&)?id=(\d+)"
);

define('ARTIST_REGEX', SITELINK_REGEX.'\/artist\.php\?(.*&)?id=(\d+)'); // id = group 4
ENV::setPub(
    'ARTIST_REGEX',
    "$ENV->SITELINK_REGEX\/artist\.php\?(.*&)?id=(\d+)"
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
