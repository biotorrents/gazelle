<?php

declare(strict_types=1);


/**
 * site categories and meta
 *
 * THIS IS THE OLD FORMAT AND WILL GO AWAY.
 * PLEASE SEE $env->{DB,META,CATS} BELOW.
 */

# Categories
$Categories = [
  "Sequences",
  "Graphs",
  "Systems",
  "Geometric",
  "Scalars/Vectors",
  "Patterns",
  "Constraints",
  "Images",
  "Spatial",
  "Models",
  "Documents",
  "Machine Data",
];
$GroupedCategories = $Categories;

# Plain Formats
$PlainFormats = [
  "CSV"   => ["csv"],
  "JSON"  => ["json"],
  "Text"  => ["txt"],
  "XML"   => ["xml"],
];

# Sequence Formats
$SeqFormats = [
  "BAM"        => ["bam"],
  "CRAM"       => ["cram"],
  "EMBL"       => ["embl"],
  "FASTA"      => ["fa", "fasta", "fsa"],
  "FASTA+QUAL" => ["qual"],
  "CSFASTA"    => ["csfa", "csfasta", "csfsa"],
  "FASTQ"      => ["fastq", "fq", "sanfastq"],
  "GFF"        => ["gff", "gff2", "gff3"],
  "GTF"        => ["gtf"],
  "GenBank"    => ["gb", "gbk", "genbank"],
  "HDF5"       => ["bash5", "baxh5", "fast5", "h5", "hdf5"],
  "PIR"        => ["pir"],
  "QSeq"       => ["qseq"],
  "SAM"        => ["sam"],
  "SFF"        => ["sff"],
  "SRF"        => ["srf"],
  "SnapGene"   => ["dna", "seq"],
  "SwissProt"  => ["dat"],
  "VCF"        => ["vcf"],
];

# Protein Formats
# DON'T PARSE RAW FILES. TOO MANY COMPETING VENDORS
$ProtFormats = [
  "ABI/Sciex"      => ["t2d", "wiff"],
  "APML"           => ["apml"],
  "ASF"            => ["asf"],
  "Agilent/Bruker" => ["baf", "d", "fid", "tdf", "yep"],
  "BlibBuild"      => ["blib"],
  "Bruker/Varian"  => ["sms", "xms"],
  "Finnigan"       => ["dat", "ms"],
  "ION-TOF"        => ["ita", "itm"],
  "JCAMP-DX"       => ["jdx"],
  "MGF"            => ["mgf"],
  "MS2"            => ["ms2"],
  "MSF"            => ["msf"],
  "mzData"         => ["mzdata"],
  "mzML"           => ["mzml"],
  "mzXML"          => ["mzxml"],
  "OMSSA"          => ["omssa", "omx"],
  "PEFF"           => ["peff"],
  "pepXML"         => ["pepxml"],
  "protXML"        => ["protxml"],
  "Shimadzu"       => ["lcd", "qgd", "spc"],
  "Skyline"        => ["sky", "skyd"],
  "TPP/SPC"        => ["dta"],
  "Tandem"         => ["tandem"],
  "TraML"          => ["traml"],
  "ULVAC-PHI"      => ["tdc"],
];

# XML Graph Formats
$GraphXmlFormats = [
  "DGML"    => ["dgml"],
  "DotML"   => ["dotml"],
  "GEXF"    => ["gexf"],
  "GXL"     => ["gxl"],
  "GraphML" => ["graphml"],
  "XGMML"   => ["xgmml"],
];

# Text Graph Formats
$GraphTxtFormats = [
  "DOT"    => ["gv"],
  "GML"    => ["gml"],
  "LCF"    => ["lcf"],
  "Newick" => ["xsd", "sgf"],
  "SIF"    => ["sif"],
  "TGF"    => ["tgf"],
];

# Image Formats
# https://www.ncbi.nlm.nih.gov/pmc/articles/PMC3948928/
$ImgFormats = [
  "Analyze"   => ["hdr", "img"],
  "Interfile" => ["h33"],
  "DICOM"     => ["dcm", "dicom"],
  "HDF5"      => ["bash5", "baxh5", "fast5", "h5", "hdf5"],
  "NIfTI"     => ["nii", "nifti"],
  "MINC"      => ["minc", "mnc"],
  "JPEG"      => ["jfif", "jpeg", "jpg"],
  "JPEG 2000" => ["j2k", "jp2", "jpf", "jpm", "jpx", "mj2"],
  "PNG"       => ["png"],
  "TIFF"      => ["tif", "tiff"],
  "WebP"      => ["webp"],
];

# Vector Map Formats
$MapVectorFormats = [
  "AutoCAD DXF"       => ["dxf"],
  "Cartesian (XYZ)"   => ["xyz"],
  "DLG"               => ["dlg"],
  "Esri TIN"          => ["adf", "dbf"],
  "GML"               => ["gml"],
  "GeoJSON"           => ["geojson"],
  "ISFC"              => ["isfc"],
  "KML"               => ["kml", "kmzv"],
  # DAT omitted
  # https://en.wikipedia.org/wiki/MapInfo_TAB_format
  "MapInfo TAB"       => ["tab", "ind", "map", "id"],
  "Measure Map Pro"   => ["mmp"],
  "NTF"               => ["ntf"],
  # DBF omitted
  # https://en.wikipedia.org/wiki/Shapefile
  "Shapefile"         => ["shp", "shx"],
  "Spatial Data File" => ["sdf", "sdf3", "sif", "kif"],
  "SOSI"              => ["sosi"],
  "SVG"               => ["svg"],
  "TIGER"             => ["tiger"],
  "VPF"               => ["vpf"],
];

# Raster Map Formats
$MapRasterFormats = [
  "ADRG"      => ["adrg"],
  "Binary"    => ["bsq", "bip", "bil"],
  "DRG"       => ["drg"],
  "ECRG"      => ["ecrg"],
  "ECW"       => ["ecw"],
  # DAT and ASC omitted (common)
  # https://support.esri.com/en/technical-article/000008526
  # https://web.archive.org/web/20150128024528/http://docs.codehaus.org/display/GEOTOOLS/ArcInfo+ASCII+Grid+format
  "Esri Grid" => ["adf", "nit", "asc", "grd"],
  "GeoTIFF"   => ["tfw"],
  #"IMG"       => ["img"],
  #"JPEG 2000" => ["j2k", "jp2", "jpf", "jpm", "jpx", "mj2"],
  "MrSID"     => ["sid"],
  "netCDF"    => ["nc"],
  "RPF"       => ["cadrg", "cib"],
];

# Binary Document Formats
# https://en.wikipedia.org/wiki/OpenDocument
# https://en.wikipedia.org/wiki/List_of_Microsoft_Office_filename_extensions
$BinDocFormats = [
  "OpenDocument" => ["odt", "fodt", "ods", "fods", "odp", "fodp", "odg", "fodg", "odf"],
  "Word"         => ["doc", "dot", "wbk", "docx", "docm", "dotx", "dotm", "docb"],
  "PowerPoint"   => ["ppt", "pot", "pps", "pptx", "pptm", "potx", "potm", "ppam", "ppsx", "ppsm", "sldx", "sldm"],
  "Excel"        => ["xls", "xlt", "xlm", "xlsx", "xlsm", "xltx", "xltm", "xlsb", "xla", "xlam", "xll", "xlw"],
  "PDF"          => ["pdf", "fdf", "xfdf"],
];

# Extra Formats
# DON'T PARSE IMG OR ISO FILES
# https://en.wikipedia.org/wiki/Disk_image#File_formats
# http://dcjtech.info/topic/python-file-extensions/
$CpuGenFormats = [
  "Docker"       => ["dockerfile"],
  "Hard Disk"    => ["fvd", "dmg", "esd", "qcow", "qcow2", "qcow3", "smi", "swm", "vdi", "vhd", "vhdx", "vmdk", "wim"],
  "Optical Disc" => ["bin", "ccd", "cso", "cue", "daa", "isz", "mdf", "mds", "mdx", "nrg", "uif"],
  "Python"       => ["pxd", "py", "py3", "pyc", "pyd", "pyde", "pyi", "pyo", "pyp", "pyt", "pyw", "pywz", "pyx", "pyz", "rpy", "xpy"],
  "Jupyter"      => ["ipynb"],
  "Ontology"     => ["cgif", "cl", "clif", "csv", "htm", "html", "kif", "obo", "owl", "rdf", "rdfa", "rdfs", "rif", "tsv", "xcl", "xht", "xhtml", "xml"],
];

# Resolutions
$Resolutions = [
  "Contig",
  "Scaffold",
  "Chromosome",
  "Genome",
  "Proteome",
  "Transcriptome",
];


/** */


/**
 * $env->database
 *
 * One flat array with all possible torrent/group fields.
 * These are mostly used in Twig templates as {{ env.db.title }}.
 * Meta abstraction layer for flavor text *around* DB fields.
 * Gazelle"s job is to query the right tables, which will shift.
 */

$database = [
    # torrents_group
    "category_id" => ["name" => "Category", "desc" => ""],
    "title" => ["name" => "Torrent title", "desc" => "Definition line, e.g., Alcohol dehydrogenase ADH1"],
    "subject" => ["name" => "Organism", "desc" => "Organism line binomial, e.g., Saccharomyces cerevisiae", "icon" => "🦠"],
    "object" => ["name" => "Strain or variety", "desc" => "Organism line if any, e.g., S288C"],
    "year" => ["name" => "Year", "desc" => "Publication year", "icon" => "📅"],
    "workgroup" => ["name" => "Department or lab", "desc" => "Last author's institution, e.g., Lawrence Berkeley Laboratory", "icon" => "🏫"],
    "location" => ["name" => "Location", "desc" => "Physical location, e.g., Berkeley, CA 94720", "icon" => "📍"],
    "identifier" => ["name" => "Accession number", "desc" => "RefSeq and UniProt preferred", "icon" => "🔑"],
    "tag_list" => ["name" => "Tag list", "desc" => "Please select at least 5 tags"],
    "timestamp" => ["name" => "Uploaded on", "desc" => ""],
    "revision_id" => ["name" => "Revision ID", "desc" => ""],
    "description" => ["name" => "Group description", "desc" => "General info about the study's function or significance"],
    "picture" => ["name" => "Picture", "desc" => "A picture, e.g., of the specimen or a figure"],

    # from the non-renamed torrents table
    "version" => ["name" => "Version", "desc" => "Start with 0.1.0", "note" => "Please see <a href='https://semver.org' target='_blank' class='external'>Semantic Versioning</a>"],
    "license" => ["name" => "License", "desc" => "Only libre licenses are supported ;)", "note" => "Please see <a href='http://www.dcc.ac.uk/resources/how-guides/license-research-data' target='_blank' class='external'>How to License Research Data</a>"],
    "mirrors" => ["name" => "Mirrors", "desc" => "Two HTTP/FTP addresses, one per line, that point to the enclosing folder"],

    # original fields
    "seqhash" => ["name" => "Seqhash", "desc" => "One sample genome sequence in FASTA format (GenBank pending)", "note" => "Please see <a href='https://pkg.go.dev/github.com/TimothyStiles/poly/seqhash' target='_blank' class='external'>the reference implementation</a>"],
];

ENV::setPub(
    "DB",
    $env->convert($database)
);


/** */


/**
 * $env->metadata
 *
 * Main metadata object.
 * Responsible for defining field values.
 * These eventually go into the database,
 * so take care to define them well here.
 * Avoid nesting > 3 levels deep.
 */

$metadata = [

    /**
     * 1.
     * PLATFORMS
     */

    "Platforms" => [

        /**
         * 2.
         * Sequences
         */
        "Sequences" => [
            # DNA
            "Complete Genomics",
            "cPAS-BGI/MGI",
            "Helicos",
            "Illumina HiSeq",
            "Illumina MiSeq",
            "Ion Torrent",
            "Microfluidics",
            "Nanopore",
            "PacBio",
            "Roche 454",
            "Sanger",
            "SOLiD",

            # RNA, protein, etc.
            "De Novo",
            "HPLC",
            "Mass Spec",
            "RNA-Seq",
        ],

        /**
         * 2.
         * Graphs
         * https://en.wikipedia.org/wiki/Graph_drawing#Software
         */
        "Graphs" => [
            "BioFabric",
            "BioTapestry",
            "Cytoscape",
            "Edraw Max",
            "GenMAPP",
            "Gephi",
            "graph-tool",
            "Graphviz",
            "InCroMAP",
            "LaNet-vi",
            "Linkurious",
            "MATLAB",
            "MEGA",
            "Maple",
            "Mathematica",
            #"Microsoft Automatic Graph Layout",
            "NetworkX",
            "PGF/TikZ",
            "PathVisio",
            "Pathview",
            "R",
            "Systrip",
            "Tom Sawyer Software",
            "Tulip",
            "yEd",
        ],

        /**
         * 2.
         * Images
         */
        "Images" => [
            "CT/CAT",
            "ECG",
            "Elastography",
            "FNIR/NIRS",
            "MPI",
            "MRI/NMR",
            "Microscopy",
            "Photoacoustic",
            "Photography",
            "Scint/SPECT/PET",
            "Ultrasound",
            "X-Rays",
        ],

        /**
         * 2.
         * Documents
         */
        "Documents" => [
            # composed
            "Literature",
            "Software",

            # generated
            "Kernel",
            "Metadata",
            "Notebook",
        ],

        /**
         * 2.
         * Machine Data
         */
        "Raw" => [
            "Binary",
            "Text",
        ],
    ], # end $env->metadata->platforms

    /**
     * 1.
     * FORMATS
     */

    "Formats" => [

        /**
         * 2.
         * Plain
         */
        "Plain" => [
            "CSV"   => ["csv"], # 3
            "JSON"  => ["json"], # 3
            "Text"  => ["txt", "asc"], # 3
            "XML"   => ["xml"], # etc.
        ],

        /**
         * 2.
         * Databases
         */
        "Databases" => [
            "MS SQL"   => ["mdf", "ndf", "ldf"],
            "MySQL"   => ["sql", "mysql"],
            "Oracle" => ["dbf", "ora", "oraenv"],
            "IBM Db2" => ["ixf", "del", "cursor"],
            "Postgres" => ["sql"]
        ],


        /**
         * 2.
         * Archives
         */
        "Archives" => [
            "7z"       => ["7z"],
            "bzip2"    => ["bz2", "bzip2"],
            "gzip"     => ["gz", "gzip", "tgz", "tpz"],
            "Pickle"   => ["pickle", "pkl"],
            "RAR"      => ["rar", "rev"],
            "tar"      => ["tar"],
            "ZIP"      => ["zip", "zipx"],
            "None"     => [""],
        ],

        /**
         * 2.
         * Sequences
         * https://www.ncbi.nlm.nih.gov/sra/docs/submitformats/
         */
        "Sequences" => [
            "BAM"        => ["bam"],
            "CRAM"       => ["cram"],
            "EMBL"       => ["embl"],
            "FASTA"      => ["fa", "fasta", "fsa"],
            "FASTA+QUAL" => ["qual"],
            "CSFASTA"    => ["csfa", "csfasta", "csfsa"],
            "FASTQ"      => ["fastq", "fq", "sanfastq"],
            "GFF"        => ["gff", "gff2", "gff3"],
            "GTF"        => ["gtf"],
            "GenBank"    => ["gb", "gbk", "genbank"],
            "HDF5"       => ["bash5", "baxh5", "fast5", "h5", "hdf5"],
            "PIR"        => ["pir"],
            "QSeq"       => ["qseq"],
            "SAM"        => ["sam"],
            "SFF"        => ["sff"],
            "SRF"        => ["srf"],
            "SnapGene"   => ["dna", "seq"],
            "SwissProt"  => ["dat"],
            "VCF"        => ["vcf"],
        ],

        /**
         * 2.
         * Proteins
         * https://www.ncbi.nlm.nih.gov/pmc/articles/PMC3518119/
         */
        "Proteins" => [
            "ABI/Sciex"      => ["t2d", "wiff"],
            "APML"           => ["apml"],
            "ASF"            => ["asf"],
            "Agilent/Bruker" => ["baf", "d", "fid", "tdf", "yep"],
            "BlibBuild"      => ["blib"],
            "Bruker/Varian"  => ["sms", "xms"],
            "Finnigan"       => ["dat", "ms"],
            "ION-TOF"        => ["ita", "itm"],
            "JCAMP-DX"       => ["jdx"],
            "MGF"            => ["mgf"],
            "MS2"            => ["ms2"],
            "MSF"            => ["msf"],
            "mzData"         => ["mzdata"],
            "mzML"           => ["mzml"],
            "mzXML"          => ["mzxml"],
            "OMSSA"          => ["omssa", "omx"],
            "PEFF"           => ["peff"],
            "pepXML"         => ["pepxml"],
            "protXML"        => ["protxml"],
            "Shimadzu"       => ["lcd", "qgd", "spc"],
            "Skyline"        => ["sky", "skyd"],
            "TPP/SPC"        => ["dta"],
            "Tandem"         => ["tandem"],
            "TraML"          => ["traml"],
            "ULVAC-PHI"      => ["tdc"],
        ],

        /**
         * 2.
         * Graph XML
         */
        "GraphXml" => [
            "DGML"    => ["dgml"],
            "DotML"   => ["dotml"],
            "GEXF"    => ["gexf"],
            "GXL"     => ["gxl"],
            "GraphML" => ["graphml"],
            "XGMML"   => ["xgmml"],
        ],

        /**
         * 2.
         * Graph plain
         */
        "GraphTxt" => [
            "DOT"    => ["gv"],
            "GML"    => ["gml"],
            "LCF"    => ["lcf"],
            "Newick" => ["xsd", "sgf"],
            "SIF"    => ["sif"],
            "TGF"    => ["tgf"],
        ],

        /**
         * 2.
         * Image vector
         */
        "ImgVector" => [
            "AI"        => ["ai"],
            "CorelDRAW" => ["cdr"],
            "EPS"       => ["eps", "epsf", "epsi"],
            "SVG"       => ["svg"],
            "WMF"       => ["emf", "emz", "wmf", "wmz"],
        ],

        /**
         * 2.
         * Image raster
         */
        "ImgRaster" => [
            "Analyze"   => ["hdr", "img"],
            "Interfile" => ["h33"],
            "DICOM"     => ["dcm", "dicom"],
            "HDF5"      => ["bash5", "baxh5", "fast5", "h5", "hdf5"],
            "NIfTI"     => ["nii", "nifti"],
            "MINC"      => ["minc", "mnc"],
            "JPEG"      => ["jfif", "jpeg", "jpg"],
            "JPEG 2000" => ["j2k", "jp2", "jpf", "jpm", "jpx", "mj2"],
            "PNG"       => ["png"],
            "TIFF"      => ["tif", "tiff"],
            "WebP"      => ["webp"],
        ],

        /**
         * 2.
         * Map vector
         */
        "MapVector" => [
            "AutoCAD DXF"       => ["dxf"],
            "Cartesian (XYZ)"   => ["xyz"],
            "DLG"               => ["dlg"],
            "Esri TIN"          => ["adf", "dbf"],
            "GML"               => ["gml"],
            "GeoJSON"           => ["geojson"],
            "ISFC"              => ["isfc"],
            "KML"               => ["kml", "kmzv"],
            # DAT omitted
            # https://en.wikipedia.org/wiki/MapInfo_TAB_format
            "MapInfo TAB"       => ["tab", "ind", "map", "id"],
            "Measure Map Pro"   => ["mmp"],
            "NTF"               => ["ntf"],
            # DBF omitted
            # https://en.wikipedia.org/wiki/Shapefile
            "Shapefile"         => ["shp", "shx"],
            "Spatial Data File" => ["sdf", "sdf3", "sif", "kif"],
            "SOSI"              => ["sosi"],
            "SVG"               => ["svg"],
            "TIGER"             => ["tiger"],
            "VPF"               => ["vpf"],
        ],

        /**
         * 2.
         * Map raster
         */
        "MapRaster" => [
            "ADRG"      => ["adrg"],
            "Binary"    => ["bsq", "bip", "bil"],
            "DRG"       => ["drg"],
            "ECRG"      => ["ecrg"],
            "ECW"       => ["ecw"],
            # DAT and ASC omitted (common)
            # https://support.esri.com/en/technical-article/000008526
            # https://web.archive.org/web/20150128024528/http://docs.codehaus.org/display/GEOTOOLS/ArcInfo+ASCII+Grid+format
            "Esri Grid" => ["adf", "nit", "asc", "grd"],
            "GeoTIFF"   => ["tfw"],
            #"IMG"       => ["img"],
            #"JPEG 2000" => ["j2k", "jp2", "jpf", "jpm", "jpx", "mj2"],
            "MrSID"     => ["sid"],
            "netCDF"    => ["nc"],
            "RPF"       => ["cadrg", "cib"],
        ],

        /**
         * 2.
         * Binary documents
         */
        "BinDoc" => [
            "OpenDocument" => ["odt", "fodt", "ods", "fods", "odp", "fodp", "odg", "fodg", "odf"],
            "Word"         => ["doc", "dot", "wbk", "docx", "docm", "dotx", "dotm", "docb"],
            "PowerPoint"   => ["ppt", "pot", "pps", "pptx", "pptm", "potx", "potm", "ppam", "ppsx", "ppsm", "sldx", "sldm"],
            "Excel"        => ["xls", "xlt", "xlm", "xlsx", "xlsm", "xltx", "xltm", "xlsb", "xla", "xlam", "xll", "xlw"],
            "PDF"          => ["pdf", "fdf", "xfdf"],
        ],

        /**
         * 2.
         * Extra formats
         */
        "CpuGen" => [
            "Docker"       => ["dockerfile"],
            "Hard Disk"    => ["fvd", "dmg", "esd", "qcow", "qcow2", "qcow3", "smi", "swm", "vdi", "vhd", "vhdx", "vmdk", "wim"],
            "Optical Disc" => ["bin", "ccd", "cso", "cue", "daa", "isz", "mdf", "mds", "mdx", "nrg", "uif"],
            "Python"       => ["pxd", "py", "py3", "pyc", "pyd", "pyde", "pyi", "pyo", "pyp", "pyt", "pyw", "pywz", "pyx", "pyz", "rpy", "xpy"],
            "Jupyter"      => ["ipynb"],
            "Ontology"     => ["cgif", "cl", "clif", "csv", "htm", "html", "kif", "obo", "owl", "rdf", "rdfa", "rdfs", "rif", "tsv", "xcl", "xht", "xhtml", "xml"],
        ],
    ], # end $env->metadata->formats


    /**
     * 1.
     * SCOPES
     */

    "Scopes" => [

        /**
         * 2.
         * SI
         */
        "SI" => [
            "Nanounit",  # 10 ^ -9
            "Microunit", # 10 ^ -6
            "Milliunit", # 10 ^ -3
            "Centiunit", # 10 ^ -2
            "Deciunit",  # 10 ^ -1
            "Decaunit",  # 10 ^  1
            "Hectounit", # 10 ^  2
            "Kilounit",  # 10 ^  3
            "Megaunit",  # 10 ^  6
            "Gigaunit",  # 10 ^  9
        ],

        /*
        "SI" => [
            "Nano",  # 10 ^ -9
            "Micro", # 10 ^ -6
            "Milli", # 10 ^ -3
            "Centi", # 10 ^ -2
            "Deci",  # 10 ^ -1
            "Deca",  # 10 ^  1
            "Hecto", # 10 ^  2
            "Kilo",  # 10 ^  3
            "Mega",  # 10 ^  6
            "Giga",  # 10 ^  9
        ],
        */

        /**
         * 2.
         * Sequences
         */
        "Sequences" => [
            "Contig",
            "Scaffold",
            "Chromosome",
            "Genome",
            "Proteome",
            "Transcriptome",
        ],

        /**
         * 2.
         * Locations
         */
        "Locations" => [
            "Organization",
            "Locality",
            "State",
            "Province",
            "Country",
            "Continent",
            "World",
        ],

        /**
         * 2.
         * XML
         */
        "XML" => [
            "Value",
            "Attribute",
            "Group",
            "Element",
            "Schema",
        ],

        /**
         * 2.
         * Scalar
         */
        "Scalar" => [
            "Area",
            "Density",
            "Distance",
            "Energy",
            "Mass",
            "Speed",
            "Temperature",
            "Time",
            "Volume",
            "Work",
        ],

        /**
         * 2.
         * Vector
         */
        "Vector" => [
            "Acceleration",
            "Displacement",
            "Force",
            "Polarization",
            "Momentum",
            "Position",
            "Thrust",
            "Velocity",
            "Weight",
        ],
    ], # end $env->metadata->scopes

    /**
     * 1.
     * LICENSES
     */

    "Licenses" => [
        "BSD-2",
        "BSD-3",
        "CC BY",
        "CC BY-SA",
        "CC BY-ND",
        "CC BY-NC",
        "CC BY-NC-SA",
        "CC BY-NC-ND",
        "GNU GPL",
        "GNU LGPL",
        "GNU AGPL",
        "GNU FDL",
        "MIT",
        "ODC-By",
        "ODC-ODbL",
        "OpenMTA",
        "Public Domain",
        "Unspecified",
    ], # end $env->metadata->licenses
];

ENV::setPub(
    "META",
    $env->convert($metadata)
);


/** */


/**
 * categories
 *
 * @see https://www.ncbi.nlm.nih.gov/books/NBK25464/
 */

$CollageCats = [
  0 => "Personal",
  1 => "Theme",
  2 => "Staff Picks",
  3 => "Group Picks",
];

$categories = [
    1 => [
        "ID" => 1,
        "Name" => "Sequences",
        "Description" => "For data that's ACGT, ACGU, or amino acid letters on disk",
        "Platforms" => $env->META->Platforms->Sequences,
        "Formats" => [
            "Nucleotides" => $env->META->Formats->Sequences,
            "Proteins" => $env->META->Formats->Proteins,
            "Plaintext" => $env->META->Formats->Plain,
        ],
        "scopes" => [
            "Sequences" => $env->META->Scopes->Sequences,
            "Metric" => $env->META->Scopes->SI,
        ],
    ],

    2 => [
        "ID" => 2,
        "Name" => "Graphs",
        "Description" => "For pathway and regulatory network data, structured taxonomies, etc.",
        "Platforms" => $env->META->Platforms->Graphs,
        "Formats" => [
            "Graph XML" => $env->META->Formats->GraphXml,
            "Graph text" => $env->META->Formats->GraphTxt,
            "Plaintext" => $env->META->Formats->Plain,
        ],
        "scopes" => [
            "XML" => $env->META->Scopes->XML,
            "Metric" => $env->META->Scopes->SI,
        ],
        ],

    3 => [
        "ID" => 3,
        "Name" => "Systems",
        "Description" => "For data that examines one facet broadly, not one subject deeply",
        "Platforms" => $env->META->Platforms->Graphs,
        "Formats" => [
            "Graph XML" => $env->META->Formats->GraphXml,
            "Graph text" => $env->META->Formats->GraphTxt,
            "Plaintext" => $env->META->Formats->Plain,
        ],
        "scopes" => [
            "XML" => $env->META->Scopes->XML,
            "Metric" => $env->META->Scopes->SI,
        ],
    ],

    4 => [
        "ID" => 4,
        "Name" => "Geometric",
        "Description" => "For structured data (XML, etc.) that describes the subject's orientation in space",
        "Platforms" => $env->META->Platforms->Graphs,
        "Formats" => [
            "Graph XML" => $env->META->Formats->GraphXml,
            "Graph text" => $env->META->Formats->GraphTxt,
            "Plaintext" => $env->META->Formats->Plain,
        ],
        "scopes" => [
            "XML" => $env->META->Scopes->XML,
            "Metric" => $env->META->Scopes->SI,
        ],
    ],

    5 => [
        "ID" => 5,
        "Name" => "Scalars/Vectors",
        "Description" => "For data that describes observations over time or space",
        "Platforms" => $env->META->Platforms->Graphs,
        "Formats" => [
            "Graph XML" => $env->META->Formats->GraphXml,
            "Graph text" => $env->META->Formats->GraphTxt,
            "Plaintext" => $env->META->Formats->Plain,
        ],
        "scopes" => [
            "Scalar" => $env->META->Scopes->Scalar,
            "Vector" => $env->META->Scopes->Vector
        ],
    ],

    6 => [
        "ID" => 6,
        "Name" => "Patterns",
        "Description" => "For data that describes recurring structures in nature such as common pathways or motifs in the proteome or metabolome",
        "Platforms" => $env->META->Platforms->Graphs,
        "Formats" => [
            "Graph XML" => $env->META->Formats->GraphXml,
            "Graph text" => $env->META->Formats->GraphTxt,
            "Plaintext" => $env->META->Formats->Plain,
        ],
        "scopes" => [
            "XML" => $env->META->Scopes->XML,
            "Metric" => $env->META->Scopes->SI,
        ],
    ],

    7 => [
        "ID" => 7,
        "Name" => "Constraints",
        "Description" => "For data that records experimental control behavior, checks readings against known physical constants, tracks the thermodynamic limits of reactions, etc.",
        "Platforms" => $env->META->Platforms->Graphs,
        "Formats" => [
            "Graph XML" => $env->META->Formats->GraphXml,
            "Graph text" => $env->META->Formats->GraphTxt,
            "Plaintext" => $env->META->Formats->Plain,
        ],
        "scopes" => [
            "XML" => $env->META->Scopes->XML,
            "Metric" => $env->META->Scopes->SI,
        ],
    ],

    8 => [
        "ID" => 8,
        "Name" => "Images",
        "Description" => "For data you can look at!",
        "Platforms" => $env->META->Platforms->Images,
        "Formats" => [
            "Raster images" => $env->META->Formats->ImgRaster,
            "Vector images" => $env->META->Formats->ImgVector,
        ],
        "scopes" => [
            "Metric" => $env->META->Scopes->SI,
        ],
    ],

    9 => [
        "ID" => 9,
        "Name" => "Spatial",
        "Description" => "For data that's limited to specific locations or otherwise describes macroscopic space",
        "Platforms" => $env->META->Platforms->Graphs,
        "Formats" => [
            "Raster images" => $env->META->Formats->ImgRaster,
            "Raster maps" => $env->META->Formats->MapRaster,
            "Vector images" => $env->META->Formats->ImgVector,
            "Vector maps" => $env->META->Formats->MapVector,
        ],
        "scopes" => [
            "Locations" => $env->META->Scopes->Locations,
            "Metric" => $env->META->Scopes->SI,
        ],
    ],

    10 => [
        "ID" => 10,
        "Name" => "Models",
        "Description" => "For projections, simulations, and other hypothetical or computer-generated data",
        "Platforms" => $env->META->Platforms->Graphs,
        "Formats" => [
            "Raster images" => $env->META->Formats->ImgRaster,
            "Raster maps" => $env->META->Formats->MapRaster,
            "Vector images" => $env->META->Formats->ImgVector,
            "Vector maps" => $env->META->Formats->MapVector,
        ],
        "scopes" => [
            "XML" => $env->META->Scopes->XML,
            "Metric" => $env->META->Scopes->SI,
        ],

    ],

    11 => [
        "ID" => 11,
        "Name" => "Documents",
        "Description" => "For documentation, software, disk images, and literature datasets",
        "Platforms" => $env->META->Platforms->Documents,
        "Formats" => [
            "Binary documents" => $env->META->Formats->BinDoc,
            "Computer-generated" => $env->META->Formats->CpuGen,
            "Plaintext" => $env->META->Formats->Plain,
        ],
        "scopes" => [
            "XML" => $env->META->Scopes->XML,
            "Metric" => $env->META->Scopes->SI,
        ],
    ],

    12 => [
        "ID" => 12,
        "Name" => "Machine Data",
        "Description" => "For raw reads and machine data of any category",
        "Platforms" => $env->META->Platforms->Raw,
        "Formats" => [
            "Plaintext" => $env->META->Formats->Plain,
        ],
        "scopes" => [
            "Metric" => $env->META->Scopes->SI,
        ],
    ],
];

ENV::setPub(
    "CATS",
    $env->convert($categories)
);
