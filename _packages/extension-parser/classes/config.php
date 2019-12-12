<?php

# Sequencing Formats
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
];

# Imaging Formats
# https://www.ncbi.nlm.nih.gov/pmc/articles/PMC3948928/
$ContainersGames = [
  'Analyze'   => ['hdr', 'img'],
  'Interfile' => ['h33'],
  'Dicom'     => ['dcm', 'dicom'],
  'Nifti'     => ['nii', 'nifti'],
  'Minc'      => ['minc', 'mnc'],
  'JPEG'      => ['jfif', 'jpeg', 'jpg'],
  'JPEG 2000' => ['j2k', 'jp2', 'jpf', 'jpm', 'jpx', 'mj2'],
  'PNG'       => ['png'],
  'TIFF'      => ['tif', 'tiff'],
  'WebP'      => ['webp'],
  'Other'     => [''],
];

# Protein Formats
# https://www.ncbi.nlm.nih.gov/pmc/articles/PMC3518119/
# DO NOT PARSE RAW FILES. TOO MANY COMPETING VENDORS
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
  'OMSSA'          => ['omssa', 'omx'],
  'PEFF'           => ['peff'],
  'Shimadzu'       => ['lcd', 'qgd', 'spc'],
  'Skyline'        => ['sky', 'skyd'],
  'TPP/SPC'        => ['dta'],
  'Tandem'         => ['tandem'],
  'TraML'          => ['traml'],
  'ULVAC-PHI'      => ['tdc'],
  'mzML'           => ['mzml'],
  'mzXML'          => ['mzxml'],
  'mzData'         => ['mzdata'],
  'pepXML'         => ['pepxml'],
  'protXML'        => ['protxml'],
  'Plain'          => ['csv', 'txt'],
];

$Archives = [
  '7z' => ['7z'],
  'bzip2' => ['bz2', 'bzip2'],
  'gzip' => ['gz', 'gzip', 'tgz', 'tpz'],
  'Pickle' => ['pickle', 'pkl'],
  'RAR' => ['rar', 'rev'],
  'ZIP' => ['zip', 'zipx'],
  'None' => [''],
];
