<?php

class Validate
{
    # Line 171
    /**
     * Extension Parser
     *
     * Takes an associative array of file types and extension, e.g.,
     * $Archives = [
     *   '7z'     => ['7z'],
     *   'bzip2'  => ['bz2', 'bzip2'],
     *   'gzip'   => ['gz', 'gzip', 'tgz', 'tpz'],
     *   ...
     * ];
     *
     * Then it finds all the extensions in a torrent file list,
     * organizes them by file size, and returns the "heaviest" match.
     *
     * That way, you can have, e.g., 5 GiB FASTQ sequence data in one file,
     * and 100 other small files, and get the format of the actual data.
     *
     * todo: Incorporate into the main function (remove if statements first)
     */
    public function ParseExtensions($FileList, $Category, $FileTypes)
    {
        # Make $Tor->file_list() output manageable
        $UnNested = array_values($FileList[1]);
        $Sorted = (usort($UnNested, function ($a, $b) {
            return $b <=> $a; # Workaround because ↑ returns true
        }) === true) ? array_values($UnNested) : null;
        
        # Harvest the wheat
        $TopTen = array_slice($Sorted, 0, 10);
        $Result = [];

        foreach ($TopTen as $TopTen) {
            # How many extensions to keep
            $Extensions = array_slice(explode('.', strtolower($TopTen[1])), -2, 2);
    
            print_r('<pre>');
            var_dump($FileTypes);
            print_r('</pre>');

            $Result = array_filter($Extensions, function ($a) {
                foreach ($FileTypes as $FileType) {
                    in_array($a, $FileType);
                }
            });

            /*
            foreach ($FileTypes as $k => $FileType) {
                var_dump(array_intersect($Extensions, $FileTypes));
            }
            */
        }

        print_r('<pre>');
        print_r('===== RESULTS =====');
        print_r($Result);
        print_r('</pre>');

        # To be continued
    }
    # Line 229