<?php
declare(strict_types=1);

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