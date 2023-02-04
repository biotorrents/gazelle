<?php

#declare(strict_types=1);


/**
 * Validate
 */

class Validate
{
    # collect the validated fields
    public $fields = [];

    # varchar field limits
    public $varcharFull = 255;
    public $varcharHalf = 128;
    public $varcharQuarter = 64;
    public $varcharEighth = 32;


    /**
     * setField
     *
     * Used basically everywhere to set $this->fields.
     * Simplified version of the old $this->SetFields().
     */
    public function setField(string $fieldName, array $data = []): void
    {
        $app = App::go();

        # empty fieldName
        if (empty($fieldName)) {
            throw new Exception("empty fieldName");
        }

        # set $this->fields
        $this->fields[$fieldName] = [
            "allowComma" => $data["allowComma"] ?? null,
            "allowPeriod" => $data["allowPeriod"] ?? null,
            "compareField" => $data["compareField"] ?? null,
            "errorMessage" => $data["errorMessage"] ?? null,
            "inArray" => $data["inArray"] ?? null,
            "maxLength" => $data["maxLength"] ?? null,
            "minLength" => $data["minLength"] ?? null,
            "regex" => $data["regex"] ?? null,
            "required" => $data["required"] ?? null,
            "type" => $data["type"] ?? null,
        ];
    }


    /**
     * SO I'M GONNA REWRITE MY HALF DONE REWRITE
     */


    /**
     * mirrors
     *
     * @param string $String The raw textarea, e.g., from torrent_form.class.php
     * @param string $Regex  The regex to test against, e.g., from $ENV
     * @return array An array of unique values that match the regex
     */
    public function textarea2array(string $String, string $Regex)
    {
        $ENV = ENV::go();

        $String = array_map(
            'trim',
            explode(
                "\n",
                $String
            )
        );

        return array_unique(
            preg_grep(
                "/^$Regex$/i",
                $String
            )
        );
    }


    /**
     * title
     *
     * Check if a torrent title is valid.
     * If so, return the sanitized title.
     * If not, return an error.
     */
    public function textInput($String)
    {
        # Previously a constant
        $MinLength = 10;
        $MaxLength = 255;

        # Does it exist and is it valid?
        if (!$String || !is_string($String)) {
            error('No or invalid $String parameter.');
        }

        # Is it too long or short?
        if (count($String)) {
        }
    }


    /**
     * Torrent errors
     *
     * Responsible for the red error messages on bad upload attemps.
     * todo: Test $this->TorrentError() on new file checker functions
     */
    public function TorrentError($Suspect)
    {
        global $Err;

        if (!$Suspect) {
            error('No error source :^)');
        }

        switch (false) {
            case $this->HasExtensions($Suspect, 1):
                return $Err = "The torrent has one or more files without extensions:\n" . Text::esc($Suspect);

            case $this->CruftFree($Suspect):
                return $Err = "The torrent has one or more junk files:\n" . Text::esc($Suspect);

            case $this->SafeCharacters($Suspect):
                $BadChars = $this->SafeCharacters('', true);
                return $Err = "One or more files has the forbidden characters $BadChars:\n" . Text::esc($Suspect);

            default:
                return;
        }

        return;
    }

    /**
     * Check if a file has no extension and return false.
     * Otherwise, return an array of the last $x extensions.
     */
    private function HasExtensions($FileName, $x)
    {
        if (!is_int($x) || $x <= 0) {
            error('Requested number of extensions must be <= 0');
        }

        if (!strstr('.', $FileName)) {
            return false;
        }

        $Extensions = array_slice(explode('.', strtolower($FileName)), -$x, $x);
        return (!empty($Extensions)) ? $Extensions : false;
    }

    /**
     * Check if a file is junk according to a filename blacklist.
     * todo: Change $Keywords into an array of regexes
     */
    public function CruftFree($FileName)
    {
        $Keywords = [
            'ahashare.com',
            'demonoid.com',
            'demonoid.me',
            'djtunes.com',
            'h33t',
            'housexclusive.net',
            'limetorrents.com',
            'mixesdb.com',
            'mixfiend.blogstop',
            'mixtapetorrent.blogspot',
            'plixid.com',
            'reggaeme.com',
            'scc.nfo',
            'thepiratebay.org',
            'torrentday',
        ];

        # $Keywords match
        foreach ($Keywords as &$Value) {
            if (strpos(strtolower($FileName), $Value) !== false) {
                return false;
            }
        }

        # Incomplete data
        if (preg_match('/INCOMPLETE~\*/i', $FileName)) {
            return false;
        }

        return true;
    }

    /**
     * These characters are invalid on Windows NTFS:
     *   : ? / < > \ * | "
     *
     * If no $FileName, return the list of bad characters.
     * If $FileName contains, a bad character, return false.
     * Otherwise, return true.
     *
     * todo: Add "/" to the blacklist. This causes problems with nested dirs, apparently
     * todo: Make possible preg_match($AllBlockedChars, $Name, $Matches)
     */
    public function SafeCharacters($FileName, $Pretty = false)
    {
        $InvalidChars = ':?<>\*|"';

        if (empty($FileName)) {
            return (!$Pretty) ? $InvalidChars : implode(' ', str_split($InvalidChars));
        }

        # todo: Regain functionality to return the invalid character
        if (preg_match(implode('\\', str_split($InvalidChars)), $Name, $Matches)) {
            return false;
        }

        return true;
    }

    /**
     * Extension Parser
     *
     * Takes an associative array of file types and extension, e.g.,
     * $ENV->META->Archives = [
     *   '7z'     => ['7z'],
     *   'bzip2'  => ['bz2', 'bzip2'],
     *   'gzip'   => ['gz', 'gzip', 'tgz', 'tpz'],
     *   ...
     * ];
     *
     * Then it finds all the extensions in a torrent file list,
     * organizes them by file size, and returns the heaviest match.
     *
     * That way, you can have, e.g., 5 GiB FASTQ sequence data in one file,
     * and 100 other small files, and get the format of the actual data.
     */
    public function ParseExtensions($FileList, $Category, $FileTypes)
    {
        # Sort $Tor->file_list() output by size
        $UnNested = array_values($FileList[1]);
        $Sorted = (usort($UnNested, function ($a, $b) {
            return $b <=> $a;
        })) ? $UnNested : null;  # Ternary wrap because &uarr; returns true

        # Harvest the wheat
        # todo: Entries seem duplicated here
        $Heaviest = array_slice($Sorted, 0, 20);
        $Matches = [];

        # Distill the file format
        $FileTypes = $FileTypes[$Category];
        $FileTypeNames = array_keys($FileTypes);

        foreach ($Heaviest as $Heaviest) {
            # Collect the last 2 period-separated tokens
            $Extensions = array_slice(explode('.', strtolower($Heaviest[1])), -2, 2);
            $Matches = array_merge($Extensions);

            # todo: Reduce nesting by one level
            foreach ($Matches as $Match) {
                $Match = strtolower($Match);

                foreach ($FileTypeNames as $FileTypeName) {
                    $SearchMe = [ $FileTypeName, $FileTypes[$FileTypeName] ];

                    if (in_array($Match, $SearchMe[1])) {
                        return $SearchMe[0];
                        break;
                    }
                }

                # Return the last element (Other or None)
                return array_key_last($FileTypes);
            }
        }
    }


    /**
     * Legacy class
     */
    public function SetFields($FieldName, $Required, $FieldType, $ErrorMessage, $Options = [])
    {
        $this->fields[$FieldName]['Type'] = strtolower($FieldType);
        $this->fields[$FieldName]['Required'] = $Required;
        $this->fields[$FieldName]['ErrorMessage'] = $ErrorMessage;

        if (!empty($Options['maxlength'])) {
            $this->fields[$FieldName]['MaxLength'] = $Options['maxlength'];
        }

        if (!empty($Options['minlength'])) {
            $this->fields[$FieldName]['MinLength'] = $Options['minlength'];
        }

        if (!empty($Options['comparefield'])) {
            $this->fields[$FieldName]['CompareField'] = $Options['comparefield'];
        }

        if (!empty($Options['allowperiod'])) {
            $this->fields[$FieldName]['AllowPeriod'] = $Options['allowperiod'];
        }

        if (!empty($Options['allowcomma'])) {
            $this->fields[$FieldName]['AllowComma'] = $Options['allowcomma'];
        }

        if (!empty($Options['inarray'])) {
            $this->fields[$FieldName]['InArray'] = $Options['inarray'];
        }

        if (!empty($Options['regex'])) {
            $this->fields[$FieldName]['Regex'] = $Options['regex'];
        }
    }

    public function ValidateForm($ValidateArray)
    {
        $app = App::go();

        reset($this->fields);
        foreach ($this->fields as $FieldKey => $Field) {
            $ValidateVar = $ValidateArray[$FieldKey];

            # todo: Change this to a switch statement
            if ($ValidateVar !== '' || !empty($Field['Required']) || $Field['Type'] === 'date') {
                if ($Field['Type'] === 'string') {
                    if (isset($Field['MaxLength'])) {
                        $MaxLength = $Field['MaxLength'];
                    } else {
                        $MaxLength = 255;
                    }

                    if (isset($Field['MinLength'])) {
                        $MinLength = $Field['MinLength'];
                    } else {
                        $MinLength = 1;
                    }

                    if (strlen($ValidateVar) > $MaxLength) {
                        return $Field['ErrorMessage'];
                    } elseif (strlen($ValidateVar) < $MinLength) {
                        return $Field['ErrorMessage'];
                    }
                } elseif ($Field['Type'] === 'number') {
                    if (isset($Field['MaxLength'])) {
                        $MaxLength = $Field['MaxLength'];
                    } else {
                        $MaxLength = '';
                    }

                    if (isset($Field['MinLength'])) {
                        $MinLength = $Field['MinLength'];
                    } else {
                        $MinLength = 0;
                    }

                    $Match = '0-9';
                    if (isset($Field['AllowPeriod'])) {
                        $Match .= '.';
                    }

                    if (isset($Field['AllowComma'])) {
                        $Match .= ',';
                    }

                    if (preg_match('/[^'.$Match.']/', $ValidateVar) || strlen($ValidateVar) < 1) {
                        return $Field['ErrorMessage'];
                    } elseif ($MaxLength !== '' && $ValidateVar > $MaxLength) {
                        return $Field['ErrorMessage'].'!!';
                    } elseif ($ValidateVar < $MinLength) {
                        return $Field['ErrorMessage']."$MinLength";
                    }
                } elseif ($Field['Type'] === 'email') {
                    if (isset($Field['MaxLength'])) {
                        $MaxLength = $Field['MaxLength'];
                    } else {
                        $MaxLength = 255;
                    }

                    if (isset($Field['MinLength'])) {
                        $MinLength = $Field['MinLength'];
                    } else {
                        $MinLength = 6;
                    }

                    if (!preg_match($app->env->regexEmail, $ValidateVar)) {
                        return $Field['ErrorMessage'];
                    } elseif (strlen($ValidateVar) > $MaxLength) {
                        return $Field['ErrorMessage'];
                    } elseif (strlen($ValidateVar) < $MinLength) {
                        return $Field['ErrorMessage'];
                    }
                } elseif ($Field['Type'] === 'link') {
                    if (isset($Field['MaxLength'])) {
                        $MaxLength = $Field['MaxLength'];
                    } else {
                        $MaxLength = 255;
                    }

                    if (isset($Field['MinLength'])) {
                        $MinLength = $Field['MinLength'];
                    } else {
                        $MinLength = 10;
                    }

                    if (!preg_match($app->env->regexUri, $ValidateVar)) {
                        return $Field['ErrorMessage'];
                    } elseif (strlen($ValidateVar) > $MaxLength) {
                        return $Field['ErrorMessage'];
                    } elseif (strlen($ValidateVar) < $MinLength) {
                        return $Field['ErrorMessage'];
                    }
                } elseif ($Field['Type'] === 'username') {
                    if (isset($Field['MaxLength'])) {
                        $MaxLength = $Field['MaxLength'];
                    } else {
                        $MaxLength = 20;
                    }

                    if (isset($Field['MinLength'])) {
                        $MinLength = $Field['MinLength'];
                    } else {
                        $MinLength = 1;
                    }

                    if (!preg_match($app->env->regexUsername, $ValidateVar)) {
                        return $Field['ErrorMessage'];
                    } elseif (strlen($ValidateVar) > $MaxLength) {
                        return $Field['ErrorMessage'];
                    } elseif (strlen($ValidateVar) < $MinLength) {
                        return $Field['ErrorMessage'];
                    }
                } elseif ($Field['Type'] === 'checkbox') {
                    if (!isset($ValidateArray[$FieldKey])) {
                        return $Field['ErrorMessage'];
                    }
                } elseif ($Field['Type'] === 'compare') {
                    if ($ValidateArray[$Field['CompareField']] !== $ValidateVar) {
                        return $Field['ErrorMessage'];
                    }
                } elseif ($Field['Type'] === 'inarray') {
                    if (array_search($ValidateVar, $Field['InArray']) === false) {
                        return $Field['ErrorMessage'];
                    }
                } elseif ($Field['Type'] === 'regex') {
                    if (!preg_match($Field['Regex'], $ValidateVar)) {
                        return $Field['ErrorMessage'];
                    }
                }
            }
        } // while
    } // function
} // class


/**
 * File checker stub class
 *
 * Not technically part of the Validate class (yet).
 * Useful torrent file functions such as finding disallowed characters.
 * This will eventually move inside Validate for upload_handle.php.
 */

$Keywords = array(
  'ahashare.com', 'demonoid.com', 'demonoid.me', 'djtunes.com', 'h33t', 'housexclusive.net',
  'limetorrents.com', 'mixesdb.com', 'mixfiend.blogstop', 'mixtapetorrent.blogspot',
  'plixid.com', 'reggaeme.com' , 'scc.nfo', 'thepiratebay.org', 'torrentday');

function check_file($Type, $Name)
{
    check_name($Name);
    check_extensions($Type, $Name);
}

function check_name($Name)
{
    global $Keywords;
    $NameLC = strtolower($Name);

    foreach ($Keywords as &$Value) {
        if (strpos($NameLC, $Value) !== false) {
            forbidden_error($Name);
        }
    }

    if (preg_match('/INCOMPLETE~\*/i', $Name)) {
        forbidden_error($Name);
    }

    /*
     * These characters are invalid in NTFS on Windows systems:
     *    : ? / < > \ * | "
     *
     * todo: Add "/" to the blacklist. This causes problems with nested dirs, apparently
     * todo: Make possible preg_match($AllBlockedChars, $Name, $Matches)
     *
     * Only the following characters need to be escaped (see the link below):
     *    \ - ^ ]
     *
     * http://www.php.net/manual/en/regexp.reference.character-classes.php
     */
    $AllBlockedChars = ' : ? < > \ * | " ';
    if (preg_match('/[\\:?<>*|"]/', $Name, $Matches)) {
        character_error($Matches[0], $AllBlockedChars);
    }
}

function check_extensions($Type, $Name)
{
    # todo: Make generic or subsume into Validate->ParseExtensions()
    /*
    if (!isset($MusicExtensions[get_file_extension($Name)])) {
        invalid_error($Name);
    }
    */
}

function get_file_extension($FileName)
{
    return strtolower(substr(strrchr($FileName, '.'), 1));
}

/**
 * Error functions
 *
 * Responsible for the red error messages on bad upload attemps.
 * todo: Make one function, e.g., Validate->error($type)
 */


function forbidden_error($Name)
{
    global $Err;
    $Err = 'The torrent contained one or more forbidden files (' . Text::esc($Name) . ')';
}

function character_error($Character, $AllBlockedChars)
{
    global $Err;
    $Err = "One or more of the files or folders in the torrent has a name that contains the forbidden character '$Character'. Please rename the files as necessary and recreate the torrent.<br /><br />\nNote: The complete list of characters that are disallowed are shown below:<br />\n\t\t$AllBlockedChars";
}
