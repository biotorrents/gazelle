<?php

#declare(strict_types=1);


/**
 * Validate
 *
 * @see https://cheatsheetseries.owasp.org/cheatsheets/Input_Validation_Cheat_Sheet.html
 */

class Validate
{
    # collect the validated fields
    public $fields = [];

    # collect the error messages
    public $errorMessages = [];

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

        # set $this->fields with sensible defaults
        # this should be a list of test names and conditions
        $this->fields[$fieldName] = [
            "allowComma" => $data["allowComma"] ?? null,
            "allowPeriod" => $data["allowPeriod"] ?? null,
            "compareField" => $data["compareField"] ?? null,
            "inArray" => $data["inArray"] ?? null, # array of acceptable values
            "maxLength" => $data["maxLength"] ?? 255,
            "minLength" => $data["minLength"] ?? 0,
            "regex" => $data["regex"] ?? null, # full regex, e.g., "/([A-Z])\w+/"
            "required" => $data["required"] ?? false,
            "type" => $data["type"] ?? null, # used to call specific validation types
        ];
    }


    /**
     * allFields
     *
     * Loops through $this->fields and checks various conditions.
     *
     * @return void $this->errorMessages = [ "fieldName" => ["message one", "message two", "etc."] ]
     */
    public function allFields(array $dataToValidate): void
    {
        $app = App::go();

        reset($this->fields);
        #!d($this->fields);exit;

        # the big loop
        foreach ($this->fields as $key => $field) {
            # defined field missing from $dataToValidate
            $valueToValidate = $dataToValidate[$key] ?? null;
            if (!$valueToValidate) {
                $this->errorMessages[$key][] = "server error: the data to validate is missing a value for a defined field";
            }

            # allowComma: note double negative
            if ($field["allowComma"]) {
                $good = (!preg_match("/^[0-9,]/", strval($valueToValidate)));
                if (!$good) {
                    $this->errorMessages[$key][] = "the number doesn't allow a comma";
                }
            }

            # allowPeriod: note double negative
            if ($field["allowPeriod"]) {
                $good = (!preg_match("/^[0-9\.]/", strval($valueToValidate)));
                if (!$good) {
                    $this->errorMessages[$key][] = "the number doesn't allow a period";
                }
            }

            # compareField
            if ($field["compareField"]) {
                $good = ($dataToValidate[ $field["compareField"] ] !== $valueToValidate);
                if (!$good) {
                    $this->errorMessages[$key][] = "the value doesn't match the comparison field {$field["compareField"]}";
                }
            }

            # inArray
            if ($field["inArray"]) {
                $good = array_search($valueToValidate, $field["inArray"]);
                if (!$good) {
                    $imploded = implode(", ", $field["inArray"]);
                    $this->errorMessages[$key][] = "{$valueToValidate} not in array of {$imploded}";
                }
            }

            # maxLength
            if ($field["maxLength"]) {
                $good = (strlen($valueToValidate) < $field["maxLength"]);
                if (!$good) {
                    $this->errorMessages[$key][] = "maximum length {$field["maxLength"]} exceeded";
                }
            }

            # minLength
            if ($field["minLength"]) {
                $good = (strlen($valueToValidate) >= $field["minLength"]);
                if (!$good) {
                    $this->errorMessages[$key][] = "minimum length {$field["minLength"]} not met";
                }
            }

            # regex
            if ($field["regex"]) {
                $good = preg_match($field["regex"], strval($valueToValidate));
                if (!$good) {
                    $this->errorMessages[$key][] = "failed to satisfy regex {$field["regex"]}";
                }
            }

            # required
            if ($field["required"]) {
                $good = !empty($field["required"]);
                if (!$good) {
                    $this->errorMessages[$key][] = "this required field is empty";
                }
            }

            /** */

            # the $field["type"] tests use assertions from Esc::class
            # this is used to enforce type checking where appropriate

            # email
            if ($field["type"] === "email") {
                $good = (Esc::email($valueToValidate) === strval($valueToValidate));
                if (!$good) {
                    $this->errorMessages[$key][] = "this field requires a valid email";
                }
            }

            # float
            if ($field["type"] === "float" || $field["type"] === "decimal") {
                $good = (Esc::float($valueToValidate) === floatval($valueToValidate));
                if (!$good) {
                    $this->errorMessages[$key][] = "this field requires a valid float";
                }
            }

            # int
            if ($field["type"] === "int" || $field["type"] === "integer" || $field["type"] === "number") {
                $good = (Esc::int($valueToValidate) === intval($valueToValidate));
                if (!$good) {
                    $this->errorMessages[$key][] = "this field requires a valid int";
                }
            }

            # string
            if ($field["type"] === "string") {
                $good = (Esc::string($valueToValidate) === strval($valueToValidate));
                if (!$good) {
                    $this->errorMessages[$key][] = "this field requires a valid string";
                }
            }

            # url
            if ($field["type"] === "url" || $field["type"] === "uri") {
                $good = (Esc::url($valueToValidate) === strval($valueToValidate));
                if (!$good) {
                    $this->errorMessages[$key][] = "this field requires a valid url";
                }
            }

            # bool
            if ($field["type"] === "bool" || $field["type"] === "boolean") {
                $good = (Esc::bool($valueToValidate) === boolval($valueToValidate));
                if (!$good) {
                    $this->errorMessages[$key][] = "this field requires a valid bool";
                }
            }

            # domain
            if ($field["type"] === "domain") {
                $good = (Esc::domain($valueToValidate) === strval($valueToValidate));
                if (!$good) {
                    $this->errorMessages[$key][] = "this field requires a valid domain";
                }
            }

            # ip
            if ($field["type"] === "ip" || $field["type"] === "ipAddress") {
                $good = (Esc::ip($valueToValidate) === strval($valueToValidate));
                if (!$good) {
                    $this->errorMessages[$key][] = "this field requires a valid ip";
                }
            }


            # mac
            if ($field["type"] === "mac" || $field["type"] === "macAddress") {
                $good = (Esc::mac($valueToValidate) === strval($valueToValidate));
                if (!$good) {
                    $this->errorMessages[$key][] = "this field requires a valid mac";
                }
            }

            # regex
            if ($field["type"] === "regex") {
                $good = (Esc::regex($valueToValidate) === strval($valueToValidate));
                if (!$good) {
                    $this->errorMessages[$key][] = "this field requires a valid regex";
                }
            }


            # username
            if ($field["type"] === "username") {
                $good = (Esc::username($valueToValidate) === strval($valueToValidate));
                if (!$good) {
                    $this->errorMessages[$key][] = "this field requires a valid username";
                }
            }

            # passphrase
            if ($field["type"] === "passphrase" || $field["type"] === "password") {
                $good = (Esc::passphrase($valueToValidate) === strval($valueToValidate));
                if (!$good) {
                    $this->errorMessages[$key][] = "this field requires a valid passphrase";
                }
            }

            /** */

            # now for some custom types
            # these are specific to uploads

            # torrentFile
            if ($field["type"] === "torrentFile") {
                $good = ($this->torrentFile($valueToValidate));
                if (!$good) {
                    $this->errorMessages[$key][] = "something was wrong with your torrent file";
                }
            }

            # literature
            if ($field["type"] === "literature") {
                $good = ($this->literature($valueToValidate));
                if (!$good) {
                    $this->errorMessages[$key][] = "";
                }
            }

            # year
            if ($field["type"] === "year") {
                $good = ($this->year($valueToValidate));
                if (!$good) {
                    $this->errorMessages[$key][] = "";
                }
            }

            # mirrors
            if ($field["type"] === "mirrors") {
                $good = ($this->mirrors($valueToValidate));
                if (!$good) {
                    $this->errorMessages[$key][] = "";
                }
            }
        } # foreach
    } # function


    /** boolean custom validators */


    /**
     * torrentFile
     *
     * @see https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html
     */
    public function torrentFile(array $data): bool
    {
        # error key
        $key = "torrentFile";
        $this->errorMessages[$key] ??= [];

        # nothing to do
        if (empty($data)) {
            return true;
        }

        # unable to parse
        $parsedData = pathinfo($data["name"] ?? "");
        $parsedData["basename"] ??= null;
        if (!$data["basename"] || empty($data["basename"])) {
            $this->errorMessages[$key][] = "unable to parse";
        }

        $parsedData["filename"] ??= null;
        if (!$data["filename"] || empty($data["filename"])) {
            $this->errorMessages[$key][] = "unable to parse";
        }

        /** $_FILES */

        # an error occurred
        $data["error"] ??= null;
        if (!empty($data["error"])) {
            $this->errorMessages[$key][] = "an error occurred";
        }

        # no filename
        $data["name"] ??= null;
        if (!$data["name"] || empty($data["name"])) {
            $this->errorMessages[$key][] = "no filename";
        }

        # long filename
        if (strlen($data["name"]) > 255) {
            $this->errorMessages[$key][] = "long filename";
        }

        # unsafe filename
        if (Text::esc($data["name"]) !== $data["name"]) {
            $this->errorMessages[$key][] = "unsafe filename";
        }

        # name, full_name mismatch
        $data["full_name"] ??= null;
        if ($data["name"] !== $data["full_name"]) {
            $this->errorMessages[$key][] = "name, full_name mismatch";
        }

        # no content type
        $data["type"] ??= null;
        if (!$data["type"] || empty($data["type"])) {
            $this->errorMessages[$key][] = "no content type";
        }

        # bad content type
        $contentType = mime_content_type($data["tmp_name"]);
        if ($contentType !== "application/x-bittorrent" || $data["type"] !== "application/x-bittorrent") {
            $this->errorMessages[$key][] = "bad content type";
        }

        # no file extension
        $parsedData["extension"] ??= null;
        if (!$parsedData["extension"] || empty($parsedData["extension"])) {
            $this->errorMessages[$key][] = "no file extension";
        }

        # bad file extension
        if ($parsedData["extension"] !== "torrent") {
            $this->errorMessages[$key][] = "bad file extension";
        }

        /** file contents */

        # no temporary filename
        $data["tmp_name"] ??= null;
        if (!$data["tmp_name"] || empty($data["tmp_name"])) {
            $this->errorMessages[$key][] = "no temporary filename";
            return false;
        }

        # unsafe temporary filename
        if (Text::esc($data["tmp_name"]) !== $data["tmp_name"]) {
            $this->errorMessages[$key][] = "unsafe temporary filename";
        }

        # file doesn't exist
        if (!file_exists($data["tmp_name"])) {
            $this->errorMessages[$key][] = "file doesn't exist";
        }

        # upload path is deceptive
        if (realpath($data["tmp_name"]) !== $data["tmp_name"]) {
            $this->errorMessages[$key][] = "upload path is deceptive";
        }

        # file is empty
        $fileSize = filesize($data["tmp_name"]);
        if (empty($fileSize)) {
            $this->errorMessages[$key][] = "file is empty";
        }

        # file is big
        $fileSizeLimit = 1024 * 1024 * 5; # number of megabytes
        if ($fileSize > $fileSizeLimit) {
            $this->errorMessages[$key][] = "file is big";
        }

        # file size mismatch
        $data["size"] ??= null;
        if ($fileSize !== $data["size"]) {
            $this->errorMessages[$key][] = "file size mismatch";
        }

        # file is executable
        if (is_executable($data["tmp_name"])) {
            $this->errorMessages[$key][] = "file is executable";
        }

        # return
        if (empty($this->errorMessages[$key])) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * literature
     */
    public function literature(string $data): bool
    {
        # error key
        $key = "literature";
        $this->errorMessages[$key] ??= [];

        # nothing to do
        if (empty($data)) {
            return true;
        }

        # unable to parse
        $parsedData = explode("\n", $data);
        if (!is_array($parsedData) || empty($parsedData)) {
            $this->errorMessages[$key][] = "unable to parse";
        }

        # invalid doi number
        foreach ($parsedData as $item) {
            if (!preg_match("/{$app->env->regexDoi}/", $item)) {
                $this->errorMessages[$key][] = "invalid doi number";
            }
        }

        # return
        if (empty($this->errorMessages[$key])) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * year
     */
    public function year(int|string $data): bool
    {
        # error key
        $key = "year";
        $this->errorMessages[$key] ??= [];

        # nothing to do
        if (empty($data)) {
            return true;
        }

        # not four digits
        if (strlen($data) !== 4) {
            $this->errorMessages[$key][] = "not four digits";
        }




        # return
        if (empty($this->errorMessages[$key])) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * mirrors
     */
    public function mirrors(): bool
    {
        # error key
        $key = "mirrors";
        $this->errorMessages[$key] ??= [];

        # nothing to do
        if (empty($data)) {
            return true;
        }


        # return
        if (empty($this->errorMessages[$key])) {
            return true;
        } else {
            return false;
        }
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
    public function SetFields($fieldName, $Required, $fieldType, $ErrorMessage, $Options = [])
    {
        $this->fields[$fieldName]['Type'] = strtolower($fieldType);
        $this->fields[$fieldName]['Required'] = $Required;
        $this->fields[$fieldName]['ErrorMessage'] = $ErrorMessage;

        if (!empty($Options['maxlength'])) {
            $this->fields[$fieldName]['MaxLength'] = $Options['maxlength'];
        }

        if (!empty($Options['minlength'])) {
            $this->fields[$fieldName]['MinLength'] = $Options['minlength'];
        }

        if (!empty($Options['comparefield'])) {
            $this->fields[$fieldName]['CompareField'] = $Options['comparefield'];
        }

        if (!empty($Options['allowperiod'])) {
            $this->fields[$fieldName]['AllowPeriod'] = $Options['allowperiod'];
        }

        if (!empty($Options['allowcomma'])) {
            $this->fields[$fieldName]['AllowComma'] = $Options['allowcomma'];
        }

        if (!empty($Options['inarray'])) {
            $this->fields[$fieldName]['InArray'] = $Options['inarray'];
        }

        if (!empty($Options['regex'])) {
            $this->fields[$fieldName]['Regex'] = $Options['regex'];
        }
    }

    public function ValidateForm($ValidateArray)
    {
        $app = App::go();

        reset($this->fields);
        foreach ($this->fields as $fieldKey => $field) {
            $ValidateVar = $ValidateArray[$fieldKey];

            # todo: Change this to a switch statement
            if ($ValidateVar !== '' || !empty($field['Required']) || $field['Type'] === 'date') {
                if ($field['Type'] === 'string') {
                    if (isset($field['MaxLength'])) {
                        $MaxLength = $field['MaxLength'];
                    } else {
                        $MaxLength = 255;
                    }

                    if (isset($field['MinLength'])) {
                        $MinLength = $field['MinLength'];
                    } else {
                        $MinLength = 1;
                    }

                    if (strlen($ValidateVar) > $MaxLength) {
                        return $field['ErrorMessage'];
                    } elseif (strlen($ValidateVar) < $MinLength) {
                        return $field['ErrorMessage'];
                    }
                } elseif ($field['Type'] === 'number') {
                    if (isset($field['MaxLength'])) {
                        $MaxLength = $field['MaxLength'];
                    } else {
                        $MaxLength = '';
                    }

                    if (isset($field['MinLength'])) {
                        $MinLength = $field['MinLength'];
                    } else {
                        $MinLength = 0;
                    }

                    $Match = '0-9';
                    if (isset($field['AllowPeriod'])) {
                        $Match .= '.';
                    }

                    if (isset($field['AllowComma'])) {
                        $Match .= ',';
                    }

                    if (preg_match('/[^'.$Match.']/', $ValidateVar) || strlen($ValidateVar) < 1) {
                        return $field['ErrorMessage'];
                    } elseif ($MaxLength !== '' && $ValidateVar > $MaxLength) {
                        return $field['ErrorMessage'].'!!';
                    } elseif ($ValidateVar < $MinLength) {
                        return $field['ErrorMessage']."$MinLength";
                    }
                } elseif ($field['Type'] === 'email') {
                    if (isset($field['MaxLength'])) {
                        $MaxLength = $field['MaxLength'];
                    } else {
                        $MaxLength = 255;
                    }

                    if (isset($field['MinLength'])) {
                        $MinLength = $field['MinLength'];
                    } else {
                        $MinLength = 6;
                    }

                    if (!preg_match($app->env->regexEmail, $ValidateVar)) {
                        return $field['ErrorMessage'];
                    } elseif (strlen($ValidateVar) > $MaxLength) {
                        return $field['ErrorMessage'];
                    } elseif (strlen($ValidateVar) < $MinLength) {
                        return $field['ErrorMessage'];
                    }
                } elseif ($field['Type'] === 'link') {
                    if (isset($field['MaxLength'])) {
                        $MaxLength = $field['MaxLength'];
                    } else {
                        $MaxLength = 255;
                    }

                    if (isset($field['MinLength'])) {
                        $MinLength = $field['MinLength'];
                    } else {
                        $MinLength = 10;
                    }

                    if (!preg_match($app->env->regexUri, $ValidateVar)) {
                        return $field['ErrorMessage'];
                    } elseif (strlen($ValidateVar) > $MaxLength) {
                        return $field['ErrorMessage'];
                    } elseif (strlen($ValidateVar) < $MinLength) {
                        return $field['ErrorMessage'];
                    }
                } elseif ($field['Type'] === 'username') {
                    if (isset($field['MaxLength'])) {
                        $MaxLength = $field['MaxLength'];
                    } else {
                        $MaxLength = 20;
                    }

                    if (isset($field['MinLength'])) {
                        $MinLength = $field['MinLength'];
                    } else {
                        $MinLength = 1;
                    }

                    if (!preg_match($app->env->regexUsername, $ValidateVar)) {
                        return $field['ErrorMessage'];
                    } elseif (strlen($ValidateVar) > $MaxLength) {
                        return $field['ErrorMessage'];
                    } elseif (strlen($ValidateVar) < $MinLength) {
                        return $field['ErrorMessage'];
                    }
                } elseif ($field['Type'] === 'checkbox') {
                    if (!isset($ValidateArray[$fieldKey])) {
                        return $field['ErrorMessage'];
                    }
                } elseif ($field['Type'] === 'compare') {
                    if ($ValidateArray[$field['CompareField']] !== $ValidateVar) {
                        return $field['ErrorMessage'];
                    }
                } elseif ($field['Type'] === 'inarray') {
                    if (array_search($ValidateVar, $field['InArray']) === false) {
                        return $field['ErrorMessage'];
                    }
                } elseif ($field['Type'] === 'regex') {
                    if (!preg_match($field['Regex'], $ValidateVar)) {
                        return $field['ErrorMessage'];
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
