<?php

declare(strict_types=1);


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
    public $errors = [];

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
        $app = \Gazelle\App::go();

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
            "inArray" => $data["inArray"] ?? [], # array of acceptable values
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
     * @return void $this->errors = [ "fieldName" => ["message one", "message two", "etc."] ]
     */
    public function allFields(array $dataToValidate): void
    {
        $app = \Gazelle\App::go();

        reset($this->fields);
        #!d($this->fields);exit;

        # the big loop
        foreach ($this->fields as $key => $field) {
            # defined field missing from $dataToValidate
            $valueToValidate = $dataToValidate[$key] ?? null;
            if (!$valueToValidate) {
                #$this->errors[$key][] = "server error: the data to validate is missing a value for a defined field";
            }

            # skip iterable
            if (is_iterable($valueToValidate)) {
                continue;
            }

            # allowComma: note double negative
            if ($field["allowComma"]) {
                $good = (!preg_match("/^[0-9,]/", strval($valueToValidate)));
                if (!$good) {
                    $this->errors[$key][] = "the number doesn't allow a comma";
                }
            }

            # allowPeriod: note double negative
            if ($field["allowPeriod"]) {
                $good = (!preg_match("/^[0-9\.]/", strval($valueToValidate)));
                if (!$good) {
                    $this->errors[$key][] = "the number doesn't allow a period";
                }
            }

            # compareField
            if ($field["compareField"]) {
                $good = ($dataToValidate[ $field["compareField"] ] !== $valueToValidate);
                if (!$good) {
                    $this->errors[$key][] = "the value doesn't match the comparison field {$field["compareField"]}";
                }
            }

            # inArray
            if ($field["inArray"]) {
                $good = array_search($valueToValidate, $field["inArray"], true);
                if ($good === false) {
                    $imploded = implode(", ", $field["inArray"]);
                    $this->errors[$key][] = "{$valueToValidate} not in array of {$imploded}";
                }
            }

            # maxLength
            if ($field["maxLength"]) {
                $good = (strlen(strval($valueToValidate)) < $field["maxLength"]);
                if (!$good) {
                    $this->errors[$key][] = "maximum length {$field["maxLength"]} exceeded";
                }
            }

            # minLength
            if ($field["minLength"]) {
                $good = (strlen(strval($valueToValidate)) >= $field["minLength"]);
                if (!$good) {
                    $this->errors[$key][] = "minimum length {$field["minLength"]} not met";
                }
            }

            # regex
            if ($field["regex"]) {
                $good = preg_match($field["regex"], strval($valueToValidate));
                if (!$good) {
                    $this->errors[$key][] = "failed to satisfy regex {$field["regex"]}";
                }
            }

            # required
            if ($field["required"]) {
                $good = !empty($field["required"]);
                if (!$good) {
                    $this->errors[$key][] = "this required field is empty";
                }
            }

            /** */

            # the $field["type"] tests use assertions from \Gazelle\Esc::class
            # this is used to enforce type checking where appropriate

            # email
            if ($field["type"] === "email") {
                $good = (\Gazelle\Esc::email($valueToValidate) === strval($valueToValidate));
                if (!$good) {
                    $this->errors[$key][] = "this field requires a valid email";
                }
            }

            # float
            if ($field["type"] === "float" || $field["type"] === "decimal") {
                $good = (\Gazelle\Esc::float($valueToValidate) === floatval($valueToValidate));
                if (!$good) {
                    $this->errors[$key][] = "this field requires a valid float";
                }
            }

            # int
            if ($field["type"] === "int" || $field["type"] === "integer" || $field["type"] === "number") {
                $good = (\Gazelle\Esc::int($valueToValidate) === intval($valueToValidate));
                if (!$good) {
                    $this->errors[$key][] = "this field requires a valid int";
                }
            }

            # string
            if ($field["type"] === "string") {
                $good = (\Gazelle\Esc::string($valueToValidate) === strval($valueToValidate));
                if (!$good) {
                    $this->errors[$key][] = "this field requires a valid string";
                }
            }

            # url
            if ($field["type"] === "url" || $field["type"] === "uri") {
                $good = (\Gazelle\Esc::url($valueToValidate) === strval($valueToValidate));
                if (!$good) {
                    $this->errors[$key][] = "this field requires a valid url";
                }
            }

            # bool
            if ($field["type"] === "bool" || $field["type"] === "boolean") {
                $good = (\Gazelle\Esc::bool($valueToValidate) === boolval($valueToValidate));
                if (!$good) {
                    $this->errors[$key][] = "this field requires a valid bool";
                }
            }

            # domain
            if ($field["type"] === "domain") {
                $good = (\Gazelle\Esc::domain($valueToValidate) === strval($valueToValidate));
                if (!$good) {
                    $this->errors[$key][] = "this field requires a valid domain";
                }
            }

            # ip
            if ($field["type"] === "ip" || $field["type"] === "ipAddress") {
                $good = (\Gazelle\Esc::ip($valueToValidate) === strval($valueToValidate));
                if (!$good) {
                    $this->errors[$key][] = "this field requires a valid ip";
                }
            }


            # mac
            if ($field["type"] === "mac" || $field["type"] === "macAddress") {
                $good = (\Gazelle\Esc::mac($valueToValidate) === strval($valueToValidate));
                if (!$good) {
                    $this->errors[$key][] = "this field requires a valid mac";
                }
            }

            # regex
            if ($field["type"] === "regex") {
                $good = (\Gazelle\Esc::regex($valueToValidate) === strval($valueToValidate));
                if (!$good) {
                    $this->errors[$key][] = "this field requires a valid regex";
                }
            }


            # username
            if ($field["type"] === "username") {
                $good = (\Gazelle\Esc::username($valueToValidate) === strval($valueToValidate));
                if (!$good) {
                    $this->errors[$key][] = "this field requires a valid username";
                }
            }

            # passphrase
            if ($field["type"] === "passphrase" || $field["type"] === "password") {
                $good = (\Gazelle\Esc::passphrase($valueToValidate) === strval($valueToValidate));
                if (!$good) {
                    $this->errors[$key][] = "this field requires a valid passphrase";
                }
            }

            /** */

            # now for some custom types
            # these are specific to uploads

            # torrentFile
            if ($field["type"] === "torrentFile") {
                $good = ($this->torrentFile($valueToValidate));
                if (!$good) {
                    $this->errors[$key][] = "torrentFile validation failed";
                }
            }

            # literature
            if ($field["type"] === "literature") {
                $good = ($this->literature($valueToValidate));
                if (!$good) {
                    $this->errors[$key][] = "literature validation failed";
                }
            }

            # tagList
            if ($field["type"] === "tagList") {
                $good = ($this->tagList($valueToValidate));
                if (!$good) {
                    $this->errors[$key][] = "tagList validation failed";
                }
            }

            # year
            if ($field["type"] === "year") {
                $good = ($this->year($valueToValidate));
                if (!$good) {
                    $this->errors[$key][] = "year validation failed";
                }
            }

            # mirrors
            if ($field["type"] === "mirrors") {
                $good = ($this->mirrors($valueToValidate));
                if (!$good) {
                    $this->errors[$key][] = "mirrors validation failed";
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
        $this->errors[$key] ??= [];

        # nothing to do
        if (empty($data)) {
            return true;
        }

        # unable to parse
        $parsedData = pathinfo($data["name"] ?? "");
        $parsedData["basename"] ??= null;
        if (!$data["basename"] || empty($data["basename"])) {
            $this->errors[$key][] = "unable to parse";
        }

        $parsedData["filename"] ??= null;
        if (!$data["filename"] || empty($data["filename"])) {
            $this->errors[$key][] = "unable to parse";
        }

        /** $_FILES */

        # an error occurred
        $data["error"] ??= null;
        if (!empty($data["error"])) {
            $this->errors[$key][] = "an error occurred";
        }

        # no filename
        $data["name"] ??= null;
        if (!$data["name"] || empty($data["name"])) {
            $this->errors[$key][] = "no filename";
        }

        # long filename
        if (strlen($data["name"]) > 255) {
            $this->errors[$key][] = "long filename";
        }

        # unsafe filename
        if (\Gazelle\Text::esc($data["name"]) !== $data["name"]) {
            $this->errors[$key][] = "unsafe filename";
        }

        # name, full_name mismatch
        $data["full_name"] ??= null;
        if ($data["name"] !== $data["full_name"]) {
            $this->errors[$key][] = "name, full_name mismatch";
        }

        # no content type
        $data["type"] ??= null;
        if (!$data["type"] || empty($data["type"])) {
            $this->errors[$key][] = "no content type";
        }

        # bad content type
        $contentType = mime_content_type($data["tmp_name"]);
        if ($contentType !== "application/x-bittorrent" || $data["type"] !== "application/x-bittorrent") {
            $this->errors[$key][] = "bad content type";
        }

        # no file extension
        $parsedData["extension"] ??= null;
        if (!$parsedData["extension"] || empty($parsedData["extension"])) {
            $this->errors[$key][] = "no file extension";
        }

        # bad file extension
        if ($parsedData["extension"] !== "torrent") {
            $this->errors[$key][] = "bad file extension";
        }

        # not uploaded by POST
        if (!is_uploaded_file($data["tmp_name"])) {
            $this->errors[$key][] = "not uploaded by POST";
        }

        /** file contents */

        # no temporary filename
        $data["tmp_name"] ??= null;
        if (!$data["tmp_name"] || empty($data["tmp_name"])) {
            $this->errors[$key][] = "no temporary filename";
            return false;
        }

        # unsafe temporary filename
        if (\Gazelle\Text::esc($data["tmp_name"]) !== $data["tmp_name"]) {
            $this->errors[$key][] = "unsafe temporary filename";
        }

        # file doesn't exist
        if (!file_exists($data["tmp_name"])) {
            $this->errors[$key][] = "file doesn't exist";
        }

        # upload path is deceptive
        if (realpath($data["tmp_name"]) !== $data["tmp_name"]) {
            $this->errors[$key][] = "upload path is deceptive";
        }

        # file is empty
        $fileSize = filesize($data["tmp_name"]);
        if (empty($fileSize)) {
            $this->errors[$key][] = "file is empty";
        }

        # file is big
        $fileSizeLimit = 1024 * 1024 * 5; # number of megabytes
        if ($fileSize > $fileSizeLimit) {
            $this->errors[$key][] = "file is big";
        }

        # file size mismatch
        $data["size"] ??= null;
        if ($fileSize !== $data["size"]) {
            $this->errors[$key][] = "file size mismatch";
        }

        # file is executable
        if (is_executable($data["tmp_name"])) {
            $this->errors[$key][] = "file is executable";
        }

        # return
        if (empty($this->errors[$key])) {
            return true;
        } else {
            return false;
        }
    } # function


    /**
     * literature
     */
    public function literature(string $data): bool
    {
        $app = \Gazelle\App::go();

        # error key
        $key = "literature";

        # nothing to do
        if (empty($data)) {
            return true;
        }

        # unable to parse
        $parsedData = explode("\n", $data);
        if (!is_array($parsedData) || empty($parsedData)) {
            $this->errors[$key][] = "unable to parse";
        }

        # invalid doi number
        foreach ($parsedData as $item) {
            $item = trim(strval($item));
            $good = preg_match("/{$app->env->regexDoi}/i", $item);
            if (!$good) {
                $this->errors[$key][] = "invalid doi number {$item}";
            }
        }

        # return
        if (empty($this->errors[$key])) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * tagList
     */
    public function tagList(array $data): bool
    {
        # error key
        $key = "tagList";

        # nothing to do
        if (empty($data)) {
            return true;
        }

        # not enough tags
        if (count($tagList) < 5) {
            $this->errors[$key][] = "not enough tags";
        }

        # return
        if (empty($this->errors[$key])) {
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

        # nothing to do
        if (empty($data)) {
            return true;
        }

        # not numeric
        if (!is_numeric($data)) {
            $this->errors[$key][] = "not numeric";
        }

        # not four digits
        if (strlen(strval($data)) !== 4) {
            $this->errors[$key][] = "not four digits";
        }

        # not a positive number
        if (abs(intval($data)) !== intval($data)) {
            $this->errors[$key][] = "not a positive number";
        }

        # in the future
        if (intval($data) > intval(date("Y"))) {
            $this->errors[$key][] = "in the future";
        }

        # return
        if (empty($this->errors[$key])) {
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

        # nothing to do
        if (empty($data)) {
            return true;
        }

        # unable to parse
        $parsedData = explode("\n", $data);
        if (!is_array($parsedData) || empty($parsedData)) {
            $this->errors[$key][] = "unable to parse";
        }

        # invalid url
        foreach ($parsedData as $item) {
            if (!preg_match("/{$app->env->regexUri}/i", $item)) {
                $this->errors[$key][] = "invalid uri";
            }
        }

        # return
        if (empty($this->errors[$key])) {
            return true;
        } else {
            return false;
        }
    }


    /** bencoded torrent validation */


    /**
     * bencoded
     *
     * Inspects a BencodeTorrent object.
     *
     * @return array torrent object data
     */
    public function bencoded(BencodeTorrent $torrent): array
    {
        $app = \Gazelle\App::go();

        # error key
        $key = "torrentFile";

        # return data
        $data = [];

        # encrypted files
        if (isset($torrent->Dec["encrypted_files"])) {
            $this->errors[$key][] = "the torrent contains an encrypted file list, which is not supported here";
        }

        # file list and size
        list($dataSize, $fileList) = $torrent->file_list();

        $data["dataSize"] = $dataSize;
        $data["fileList"] = $fileList;
        $data["fileCount"] = count($fileList);

        # start validation
        $temporaryFileList = [];
        $tooLongPaths = [];

        $directoryName = (isset($torrent->Dec["info"]["files"]))
            ? \Gazelle\Text::utf8($torrent->get_name())
            : "";
        $data["directoryName"] = $directoryName;

        # garbage directory name
        $good = $this->cruftFree($directoryName);
        if (!$good) {
            $this->errors[$key][] = "garbage directory name";
        }

        # check individual files
        foreach ($fileList as $file) {
            # size and name
            list($size, $name) = $file;

            # garbage filename
            $good = $this->cruftFree($name);
            if (!$good) {
                $this->errors[$key][] = "garbage filename";
            }

            # too long filename
            $fileNameLength = mb_strlen($name, "UTF-8") + mb_strlen($directoryName, "UTF-8") + 1;
            if ($fileNameLength > 255) {
                $tooLongPaths[] = "{$directoryName}/{$name}";
            }

            /*
            # check for extensions
            $good = $this->hasExtensions($name);
            if (!$good) {
                $this->errors[$key][] = "the torrent has one or more files without extensions: \n {$name}";
            }
            */

            # unsafe characters
            $good = $this->safeCharacters($name);
            if (!$good) {
                $imploded = implode(", ", $app->env->illegalCharacters);
                $this->errors[$key][] = "one or more files has the forbidden characters {$imploded}: \n {$name}";
            }

            # add file info to array
            $temporaryFileList[] = Torrents::filelist_format_file($file);
        } # foreach ($fileList as $file)


        # too long paths
        if (count($tooLongPaths) > 0) {
            $names = implode("<br>", $tooLongPaths);
            $this->errors[$key][] = "the torrent contained one or more files with too long a name:<br> {$names}";
        }

        # last $data additions
        $data["filePath"] = $directoryName;
        $data["fileString"] = implode("\n", $temporaryFileList);

        # okay?
        $app->debug["messages"]->info("torrent decoded");
        return $data;
    }


    /**
     * cruftFree
     *
     * Check if a file is junk according to a filename blacklist.
     */
    public function cruftFree(string $fileName): bool
    {
        $keywords = [
            "ahashare.com",
            "demonoid.com",
            "demonoid.me",
            "djtunes.com",
            "h33t",
            "housexclusive.net",
            "limetorrents.com",
            "mixesdb.com",
            "mixfiend.blogstop",
            "mixtapetorrent.blogspot",
            "plixid.com",
            "reggaeme.com",
            "scc.nfo",
            "thepiratebay.org",
            "torrentday",
        ];

        # $keywords match
        foreach ($keywords as &$value) {
            if (strpos(strtolower($fileName), $value) !== false) {
                return false;
            }
        }

        # incomplete data
        if (preg_match("/INCOMPLETE~\*/i", $fileName)) {
            return false;
        }

        # okay
        return true;
    }


    /**
     * hasExtensions
     *
     * Check if a file has no extension and return false.
     * Otherwise, return an array of the last $x extensions.
     */
    private function hasExtensions(string $fileName, int $x = 1)
    {
        # force positive
        $x = abs($x);

        if (!strstr(".", $fileName)) {
            return false;
        }

        $extensions = array_slice(
            explode(
                ".",
                strtolower($fileName)
            ),
            -$x,
            $x
        );

        return (!empty($extensions))
            ? $extensions
            : false;
    }


    /**
     * safeCharacters
     *
     * If no $fileName, return the list of bad characters.
     * If $fileName contains a bad character, return false.
     * Otherwise, return true.
     */
    public function safeCharacters(string $fileName = "")
    {
        $app = \Gazelle\App::go();

        if (empty($fileName)) {
            $imploded = implode(", ", $app->env->illegalCharacters);

            return $imploded;
        }

        $data = [];

        $regex = "/[" . implode("", $app->env->illegalCharacters) . "]/";
        $good = !preg_match($regex, $fileName);

        if (!$good) {
            return false;
        }

        return true;
    }


    /**
     * legacy class methods
     */


    /**
     * SetFields
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


    /**
     * ValidateForm
     */
    public function ValidateForm($ValidateArray)
    {
        $app = \Gazelle\App::go();

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

                    if (!preg_match("/{$app->env->regexEmail}/", $ValidateVar)) {
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

                    if (!preg_match("/{$app->env->regexUri}/i", $ValidateVar)) {
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

                    if (!preg_match("/{$app->env->regexUsername}/iD", $ValidateVar)) {
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
} # class
