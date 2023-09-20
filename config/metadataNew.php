<?php

declare(strict_types=1);


/**
 * $env->database
 *
 * One flat array with all possible torrent/group fields.
 * These are mostly used in Twig templates as {{ env.database.title }}.
 * Gazelle's job is to query the right tables, which will shift.
 */

$env->database = $env->convert([

    /**
     * torrents_group
     */
    "category_id" => ["name" => "Category", "description" => ""],
    "title" => ["name" => "Torrent title", "description" => "Definition line, e.g., Alcohol dehydrogenase ADH1"],
    "subject" => ["name" => "Organism", "description" => "Organism line binomial, e.g., Saccharomyces cerevisiae", "icon" => "🦠"],
    "object" => ["name" => "Strain or variety", "description" => "Organism line if any, e.g., S288C"],
    "year" => ["name" => "Year", "description" => "Publication year", "icon" => "📅"],
    "workgroup" => ["name" => "Department or lab", "description" => "Last author's institution, e.g., Lawrence Berkeley Laboratory", "icon" => "🏫"],
    "location" => ["name" => "Location", "description" => "Physical location, e.g., Berkeley, CA 94720", "icon" => "📍"],
    "identifier" => ["name" => "Accession number", "description" => "RefSeq and UniProt preferred", "icon" => "🔑"],
    "tag_list" => ["name" => "Tag list", "description" => "Please select at least 5 tags"],
    "timestamp" => ["name" => "Uploaded on", "description" => ""],
    "revision_id" => ["name" => "Revision ID", "description" => ""],
    "description" => ["name" => "Group description", "description" => "General info about the study's function or significance"],
    "picture" => ["name" => "Picture", "description" => "A picture, e.g., of the specimen or a figure"],

    /**
     * from the non-renamed torrents table
     */
    "version" => ["name" => "Version", "description" => "Start with 0.1.0", "note" => "Please see <a href='https://semver.org' target='_blank' class='external'>Semantic Versioning</a>"],
    "license" => ["name" => "License", "description" => "Only libre licenses are supported ;)", "note" => "Please see <a href='http://www.dcc.ac.uk/resources/how-guides/license-research-data' target='_blank' class='external'>How to License Research Data</a>"],
    "mirrors" => ["name" => "Mirrors", "description" => "Two HTTP/FTP addresses, one per line, that point to the enclosing folder"],

    /**
     * original fields
     */
    "seqhash" => ["name" => "Seqhash", "description" => "One sample genome sequence in FASTA format (GenBank pending)", "note" => "Please see <a href='https://pkg.go.dev/github.com/TimothyStiles/poly/seqhash' target='_blank' class='external'>the reference implementation</a>"],

]);


/** */


/**
 * $env->metadata
 *
 * Main metadata object responsible for defining field values.
 * These eventually go into the database, so take care to define them well here.
 */

$env->metadata = $env->convert([

    /**
     * 1. platforms
     */
    "platforms" => [

        /**
         * 2. sequences
         */
        "sequences" => [
            # dna
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

            # rna, protein, etc.
            "De Novo",
            "HPLC",
            "Mass Spec",
            "RNA-Seq",
        ],

        /**
         * 2. graphs
         *
         * @see https://en.wikipedia.org/wiki/Graph_drawing#Software
         */
        "graphs" => [
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
            "Microsoft Automatic Graph Layout",
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
         * 2. images
         */
        "images" => [
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
         * 2. documents
         */
        "documents" => [
            # composed
            "Literature",
            "Software",

            # generated
            "Kernel",
            "Metadata",
            "Notebook",
        ],

        /**
         * 2. machineData
         */
        "machineData" => [
            "Binary",
            "Text",
        ],
    ], # end $env->metadata->platforms


    /**
     * 1. formats
     */
    "formats" => [

        /**
         * 2. plainText
         */
        "plainText" => [
            "CSV"  => ["csv"], # 3
            "JSON" => ["json"], # 3
            "Text" => ["txt", "asc"], # 3
            "XML"  => ["xml"], # etc.
        ],

        /**
         * 2. databases
         */
        "databases" => [
            "MS SQL"   => ["mdf", "ndf", "ldf"],
            "MySQL"    => ["sql", "mysql"],
            "Oracle"   => ["dbf", "ora", "oraenv"],
            "IBM Db2"  => ["ixf", "del", "cursor"],
            "Postgres" => ["sql"]
        ],


        /**
         * 2. archives
         */
        "archives" => [
            "7z"     => ["7z"],
            "bzip2"  => ["bz2", "bzip2"],
            "gzip"   => ["gz", "gzip", "tgz", "tpz"],
            "Pickle" => ["pickle", "pkl"],
            "RAR"    => ["rar", "rev"],
            "tar"    => ["tar"],
            "ZIP"    => ["zip", "zipx"],
            "none"   => [null],
        ],

        /**
         * 2. nucleotides
         *
         * @see https://www.ncbi.nlm.nih.gov/sra/docs/submitformats/
         */
        "nucleotides" => [
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
         * 2. proteins
         *
         * @see https://www.ncbi.nlm.nih.gov/pmc/articles/PMC3518119/
         */
        "proteins" => [
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
         * 2. graphStructured
         */
        "graphStructured" => [
            "DGML"    => ["dgml"],
            "DotML"   => ["dotml"],
            "GEXF"    => ["gexf"],
            "GXL"     => ["gxl"],
            "GraphML" => ["graphml"],
            "XGMML"   => ["xgmml"],
        ],

        /**
         * 2. graphPlainText
         */
        "graphPlainText" => [
            "DOT"    => ["gv"],
            "GML"    => ["gml"],
            "LCF"    => ["lcf"],
            "Newick" => ["xsd", "sgf"],
            "SIF"    => ["sif"],
            "TGF"    => ["tgf"],
        ],

        /**
         * 2. imageVector
         */
        "imageVector" => [
            "AI"        => ["ai"],
            "CorelDRAW" => ["cdr"],
            "EPS"       => ["eps", "epsf", "epsi"],
            "SVG"       => ["svg"],
            "WMF"       => ["emf", "emz", "wmf", "wmz"],
        ],

        /**
         * 2. imageRaster
         */
        "imageRaster" => [
            "Analyze"   => ["hdr", "img"],
            "Interfile"  => ["h33"],
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
         * 2. mapVector
         */
        "mapVector" => [
            "AutoCAD DXF"       => ["dxf"],
            "Cartesian (XYZ)"   => ["xyz"],
            "DLG"               => ["dlg"],
            "Esri TIN"          => ["adf", "dbf"],
            "GML"               => ["gml"],
            "GeoJSON"           => ["geojson"],
            "ISFC"              => ["isfc"],
            "KML"               => ["kml", "kmzv"],
            # dat omitted
            # https://en.wikipedia.org/wiki/MapInfo_TAB_format
            "MapInfo TAB"       => ["tab", "ind", "map", "id"],
            "Measure Map Pro"   => ["mmp"],
            "NTF"               => ["ntf"],
            # dbf omitted
            # https://en.wikipedia.org/wiki/Shapefile
            "Shapefile"          => ["shp", "shx"],
            "Spatial Data File" => ["sdf", "sdf3", "sif", "kif"],
            "SOSI"              => ["sosi"],
            "SVG"               => ["svg"],
            "TIGER"             => ["tiger"],
            "VPF"               => ["vpf"],
        ],

        /**
         * 2. mapRaster
         */
        "mapRaster" => [
            "ADRG"      => ["adrg"],
            "Binary"    => ["bsq", "bip", "bil"],
            "DRG"       => ["drg"],
            "ECRG"      => ["ecrg"],
            "ECW"       => ["ecw"],
            # dat and asc omitted (common)
            # https://support.esri.com/en/technical-article/000008526
            # https://web.archive.org/web/20150128024528/http://docs.codehaus.org/display/GEOTOOLS/ArcInfo+ASCII+Grid+format
            "Esri Grid" => ["adf", "nit", "asc", "grd"],
            "GeoTIFF"   => ["tfw"],
            "IMG"       => ["img"],
            "JPEG 2000" => ["j2k", "jp2", "jpf", "jpm", "jpx", "mj2"],
            "MrSID"     => ["sid"],
            "netCDF"    => ["nc"],
            "RPF"       => ["cadrg", "cib"],
        ],

        /**
         * 2. binaryDocuments
         */
        "binaryDocuments" => [
            "OpenDocument" => ["odt", "fodt", "ods", "fods", "odp", "fodp", "odg", "fodg", "odf"],
            "Word"         => ["doc", "dot", "wbk", "docx", "docm", "dotx", "dotm", "docb"],
            "PowerPoint"   => ["ppt", "pot", "pps", "pptx", "pptm", "potx", "potm", "ppam", "ppsx", "ppsm", "sldx", "sldm"],
            "Excel"        => ["xls", "xlt", "xlm", "xlsx", "xlsm", "xltx", "xltm", "xlsb", "xla", "xlam", "xll", "xlw"],
            "PDF"          => ["pdf", "fdf", "xfdf"],
        ],

        /**
         * 2. computerGenerated
         */
        "computerGenerated" => [
            "Docker"       => ["dockerfile"],
            "Hard Disk"    => ["fvd", "dmg", "esd", "qcow", "qcow2", "qcow3", "smi", "swm", "vdi", "vhd", "vhdx", "vmdk", "wim"],
            "Optical Disc" => ["bin", "ccd", "cso", "cue", "daa", "isz", "mdf", "mds", "mdx", "nrg", "uif"],
            "Python"       => ["pxd", "py", "py3", "pyc", "pyd", "pyde", "pyi", "pyo", "pyp", "pyt", "pyw", "pywz", "pyx", "pyz", "rpy", "xpy"],
            "Jupyter"      => ["ipynb"],
            "Ontology"     => ["cgif", "cl", "clif", "csv", "htm", "html", "kif", "obo", "owl", "rdf", "rdfa", "rdfs", "rif", "tsv", "xcl", "xht", "xhtml", "xml"],
        ],
    ], # end $env->metadata->formats


    /**
     * 1. scopes
     */
    "scopes" => [

        /**
         * 2. metric
         */
        "metric" => [
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

        /**
         * 2. sequences
         */
        "sequences" => [
            "Contig",
            "Scaffold",
            "Chromosome",
            "Genome",
            "Proteome",
            "Transcriptome",
        ],

        /**
         * 2. locations
         */
        "locations" => [
            "Organization",
            "Locality",
            "State",
            "Province",
            "Country",
            "Continent",
            "World",
        ],

        /**
         * 2. xml
         */
        "xml" => [
            "Value",
            "Attribute",
            "Group",
            "Element",
            "Schema",
        ],

        /**
         * 2. scalar
         */
        "scalar" => [
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
         * 2. vector
         */
        "vector" => [
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
     * 1. licenses
     */
    "licenses" => [
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

]);


/** */


/**
 * categories
 *
 * @see https://www.ncbi.nlm.nih.gov/books/NBK25464/
 */

$env->collageCategories = $env->convert([
    0 => "Personal",
    1 => "Theme",
    2 => "Staff Picks",
    3 => "Group Picks",
]);


# main torrent categories
$env->categories = $env->convert([

    /**
     * sequences
     */
    "sequences" => [
        "id" => 1,
        "title" => "Sequences",
        "description" => "For data that's ACGT, ACGU, or amino acid letters on disk",
        "platforms" => $env->metadata->platforms->sequences,
        "formats" => [
            "nucleotides" => $env->metadata->formats->nucleotides,
            "proteins" => $env->metadata->formats->proteins,
            "plainText" => $env->metadata->formats->plainText,
        ],
        "scopes" => [
            "sequences" => $env->metadata->scopes->sequences,
            "metric" => $env->metadata->scopes->metric,
        ],
    ],

    /**
     * graphs
     */
    "graphs" => [
        "id" => 2,
        "title" => "Graphs",
        "description" => "For pathway and regulatory network data, structured taxonomies, etc.",
        "platforms" => $env->metadata->platforms->graphs,
        "formats" => [
            "graphStructured" => $env->metadata->formats->graphStructured,
            "graphPlainText" => $env->metadata->formats->graphPlainText,
            "plainText" => $env->metadata->formats->plainText,
        ],
        "scopes" => [
            "xml" => $env->metadata->scopes->xml,
            "metric" => $env->metadata->scopes->metric,
        ],
    ],

    /**
     * systems
     */
    "systems" => [
        "id" => 3,
        "title" => "Systems",
        "description" => "For data that examines one facet broadly, not one subject deeply",
        "platforms" => $env->metadata->platforms->graphs,
        "formats" => [
            "graphStructured" => $env->metadata->formats->graphStructured,
            "graphPlainText" => $env->metadata->formats->graphPlainText,
            "plainText" => $env->metadata->formats->plainText,
        ],
        "scopes" => [
            "xml" => $env->metadata->scopes->xml,
            "metric" => $env->metadata->scopes->metric,
        ],
    ],

    /**
     * geometric
     */
    "geometric" => [
        "id" => 4,
        "title" => "Geometric",
        "description" => "For structured data (XML, etc.) that describes the subject's orientation in space",
        "platforms" => $env->metadata->platforms->graphs,
        "formats" => [
            "graphStructured" => $env->metadata->formats->graphStructured,
            "graphPlainText" => $env->metadata->formats->graphPlainText,
            "plainText" => $env->metadata->formats->plainText,
        ],
        "scopes" => [
            "xml" => $env->metadata->scopes->xml,
            "metric" => $env->metadata->scopes->metric,
        ],
    ],

    /**
     * scalarsVectors
     */
    "scalarsVectors" => [
        "id" => 5,
        "title" => "Scalars/Vectors",
        "description" => "For data that describes observations over time or space",
        "platforms" => $env->metadata->platforms->graphs,
        "formats" => [
            "graphStructured" => $env->metadata->formats->graphStructured,
            "graphPlainText" => $env->metadata->formats->graphPlainText,
            "plainText" => $env->metadata->formats->plainText,
        ],
        "scopes" => [
            "scalar" => $env->metadata->scopes->scalar,
            "vector" => $env->metadata->scopes->vector
        ],
    ],

    /**
     * patterns
     */
    "patterns" => [
        "id" => 6,
        "title" => "Patterns",
        "description" => "For data that describes recurring structures in nature such as common pathways or motifs in the proteome or metabolome",
        "platforms" => $env->metadata->platforms->graphs,
        "formats" => [
            "graphStructured" => $env->metadata->formats->graphStructured,
            "graphPlainText" => $env->metadata->formats->graphPlainText,
            "plainText" => $env->metadata->formats->plainText,
        ],
        "scopes" => [
            "xml" => $env->metadata->scopes->xml,
            "metric" => $env->metadata->scopes->metric,
        ],
    ],

    /**
     * constraints
     */
    "constraints" => [
        "id" => 7,
        "title" => "Constraints",
        "description" => "For data that records experimental control behavior, checks readings against known physical constants, tracks the thermodynamic limits of reactions, etc.",
        "platforms" => $env->metadata->platforms->graphs,
        "formats" => [
            "graphStructured" => $env->metadata->formats->graphStructured,
            "graphPlainText" => $env->metadata->formats->graphPlainText,
            "plainText" => $env->metadata->formats->plainText,
        ],
        "scopes" => [
            "xml" => $env->metadata->scopes->xml,
            "metric" => $env->metadata->scopes->metric,
        ],
    ],

    /**
     * images
     */
    "images" => [
        "id" => 8,
        "title" => "Images",
        "description" => "For data you can look at!",
        "platforms" => $env->metadata->platforms->images,
        "formats" => [
            "imageRaster" => $env->metadata->formats->imageRaster,
            "imageVector" => $env->metadata->formats->imageVector,
        ],
        "scopes" => [
            "metric" => $env->metadata->scopes->metric,
        ],
    ],

    /**
     * spatial
     */
    "spatial" => [
        "id" => 9,
        "title" => "Spatial",
        "description" => "For data that's limited to specific locations or otherwise describes macroscopic space",
        "platforms" => $env->metadata->platforms->graphs,
        "formats" => [
            "imageRaster" => $env->metadata->formats->imageRaster,
            "mapRaster" => $env->metadata->formats->mapRaster,
            "imageVector" => $env->metadata->formats->imageVector,
            "mapVector" => $env->metadata->formats->mapVector,
        ],
        "scopes" => [
            "locations" => $env->metadata->scopes->locations,
            "metric" => $env->metadata->scopes->metric,
        ],
    ],

    /**
     * models
     */
    "models" => [
        "id" => 10,
        "title" => "Models",
        "description" => "For projections, simulations, and other hypothetical or computer-generated data",
        "platforms" => $env->metadata->platforms->graphs,
        "formats" => [
            "imageRaster" => $env->metadata->formats->imageRaster,
            "mapRaster" => $env->metadata->formats->mapRaster,
            "imageVector" => $env->metadata->formats->imageVector,
            "mapVector" => $env->metadata->formats->mapVector,
        ],
        "scopes" => [
            "xml" => $env->metadata->scopes->xml,
            "metric" => $env->metadata->scopes->metric,
        ],

    ],

    /**
     * documents
     */
    "documents" => [
        "id" => 11,
        "title" => "Documents",
        "description" => "For documentation, software, disk images, and literature datasets",
        "platforms" => $env->metadata->platforms->documents,
        "formats" => [
            "binaryDocuments" => $env->metadata->formats->binaryDocuments,
            "computerGenerated" => $env->metadata->formats->computerGenerated,
            "plainText" => $env->metadata->formats->plainText,
        ],
        "scopes" => [
            "xml" => $env->metadata->scopes->xml,
            "metric" => $env->metadata->scopes->metric,
        ],
    ],

    /**
     * machineData
     */
    "machineData" => [
        "id" => 12,
        "title" => "Machine Data",
        "description" => "For raw reads and machine data of any category",
        "platforms" => $env->metadata->platforms->machineData,
        "formats" => [
            "plainText" => $env->metadata->formats->plainText,
        ],
        "scopes" => [
            "metric" => $env->metadata->scopes->metric,
        ],
    ],

]);
