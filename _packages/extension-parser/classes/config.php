<?php

# Line 222
# Sequencing Formats
#
# https://www.ncbi.nlm.nih.gov/sra/docs/submitformats/
$Containers = [
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
  'HDF5'       => ['bash5', 'baxh5', 'fast5', 'hdf5'],
  'PIR'        => ['pir'],
  'QSeq'       => ['qseq'],
  'SAM'        => ['sam'],
  'SFF'        => ['sff'],
  'SRF'        => ['srf'],
  'SnapGene'   => ['dna', 'seq'],
  'SwissProt'  => ['dat'],
  'VCF'        => ['vcf'],
  'Plain'      => ['csv', 'txt'],
  'Other'      => [],
];

# Imaging Formats
#
# https://www.ncbi.nlm.nih.gov/pmc/articles/PMC3948928/
$ContainersGames = [
  'Analyze'   => ['hdr', 'img'],
  'Interfile' => ['h33'],
  'DICOM'     => ['dcm', 'dicom'],
  'NIfTI'     => ['nii', 'nifti'],
  'MINC'      => ['minc', 'mnc'],
  'JPEG'      => ['jfif', 'jpeg', 'jpg'],
  'JPEG 2000' => ['j2k', 'jp2', 'jpf', 'jpm', 'jpx', 'mj2'],
  'PNG'       => ['png'],
  'TIFF'      => ['tif', 'tiff'],
  'WebP'      => ['webp'],
  'Other'     => [],
];

# Protein Formats
# DON'T PARSE RAW FILES. TOO MANY COMPETING VENDORS
#
# https://www.ncbi.nlm.nih.gov/pmc/articles/PMC3518119/
$ContainersProt = [
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
  'Plain'          => ['csv', 'txt'],
  'Other'          => [],
];

# Extra Formats
# DON'T PARSE IMG OR ISO FILES
#
# https://en.wikipedia.org/wiki/Disk_image#File_formats
# https://en.wikipedia.org/wiki/OpenDocument
# https://en.wikipedia.org/wiki/List_of_Microsoft_Office_filename_extensions
# http://dcjtech.info/topic/python-file-extensions/
$ContainersExtra = [
  'Docker'           => ['dockerfile'],
  'Hard Disk'        => ['fvd', 'dmg', 'esd', 'qcow', 'qcow2', 'qcow3', 'smi', 'swm', 'vdi', 'vhd', 'vhdx', 'vmdk', 'wim'],
  'Optical Disc'     => ['bin', 'ccd', 'cso', 'cue', 'daa', 'isz', 'mdf', 'mds', 'mdx', 'nrg', 'uif'],
  'Python'           => ['pxd', 'py', 'py3', 'pyc', 'pyd', 'pyde', 'pyi', 'pyo', 'pyp', 'pyt', 'pyw', 'pywz', 'pyx', 'pyz', 'rpy', 'xpy'],
  'Jupyter Notebook' => ['ipynb'],
  'Ontology'         => ['cgif', 'cl', 'clif', 'csv', 'htm', 'html', 'kif', 'obo', 'owl', 'rdf', 'rdfa', 'rdfs', 'rif', 'tsv', 'xcl', 'xht', 'xhtml', 'xml'],
  'OpenDocument'     => ['odt', 'fodt', 'ods', 'fods', 'odp', 'fodp', 'odg', 'fodg', 'odf'],
  'Word'             => ['doc', 'dot', 'wbk', 'docx', 'docm', 'dotx', 'dotm', 'docb'],
  'Excel'            => ['xls', 'xlt', 'xlm', 'xlsx', 'xlsm', 'xltx', 'xltm', 'xlsb', 'xla', 'xlam', 'xll', 'xlw'],
  'PowerPoint'       => ['ppt', 'pot', 'pps', 'pptx', 'pptm', 'potx', 'potm', 'ppam', 'ppsx', 'ppsm', 'sldx', 'sldm'],
  'PDF'              => ['pdf', 'fdf', 'xfdf'],
  'Plain'            => ['csv', 'txt'],
  'Other'            => [],
];
# Line 321
