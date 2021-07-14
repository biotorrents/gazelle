<?php
#declare(strict_types=1);

// This class is used in upload.php to display the upload form, and the edit
// section of torrents.php to display a shortened version of the same form
class TorrentForm
{
    public $UploadForm = '';
    public $Categories = [];

    /**
     * This kind of stuff will eventually go away.
     * The goal is to loop through multidimensional $ENV objects,
     * recursively copying parts to arrays in place as needed.
     */
    
    # Platforms
    # See classes/config.php
    public $SeqPlatforms = [];
    public $GraphPlatforms = [];
    public $ImgPlatforms = [];
    public $DocPlatforms = [];
    public $RawPlatforms = [];
    #public $Media = [];
    #public $MediaManga = [];

    # Formats
    # See classes/config.php
    public $SeqFormats = [];
    public $ProtFormats = [];
    public $GraphXmlFormats = [];
    public $GraphTxtFormats = [];
    public $ImgFormats = [];
    public $MapVectorFormats = [];
    public $MapRasterFormats = [];
    public $BinDocFormats = [];
    public $CpuGenFormats = [];
    public $PlainFormats = [];
    #public $Containers = [];
    #public $ContainersGames = [];
    #public $ContainersProt = [];
    #public $ContainersExtra = [];

    # Misc
    public $Codecs = [];
    public $Archives = [];
    public $Resolutions = [];

    # Deprecated
    #public $Formats = [];
    #public $Versions = [];
    #public $Bitrates = [];
    #public $Platform = [];

    # Gazelle
    public $NewTorrent = false;
    public $Torrent = [];
    public $Error = false;
    public $TorrentID = false;
    public $Disabled = '';
    public $DisabledFlag = false;

    public function __construct($Torrent = false, $Error = false, $NewTorrent = true)
    {
        # See classes/config.php
        global $UploadForm, $Categories, $TorrentID, $SeqPlatforms, $GraphPlatforms, $ImgPlatforms, $DocPlatforms, $RawPlatforms, $SeqFormats, $ProtFormats, $GraphXmlFormats, $GraphTxtFormats, $ImgFormats, $MapVectorFormats, $MapRasterFormats, $BinDocFormats, $CpuGenFormats, $PlainFormats, $Codecs, $Archives, $Resolutions;
        #global $UploadForm, $Categories, $Formats, $Bitrates, $Media, $MediaManga, $TorrentID, $Containers, $ContainersGames, $Codecs, $Resolutions, $Platform, $Archives, $ArchivesManga;

        # Gazelle
        $this->NewTorrent = $NewTorrent;
        $this->Torrent = $Torrent;
        $this->Error = $Error;
        
        $this->UploadForm = $UploadForm;
        $this->Categories = $Categories;
        $this->TorrentID = $TorrentID;

        # Platforms
        # See classes/config.php
        $this->SeqPlatforms = $SeqPlatforms;
        $this->GraphPlatforms = $GraphPlatforms;
        $this->ImgPlatforms = $ImgPlatforms;
        $this->DocPlatforms = $DocPlatforms;
        $this->RawPlatforms = $RawPlatforms;
       
        # Formats
        # See classes/config.php
        $this->SeqFormats = $SeqFormats;
        $this->ProtFormats = $ProtFormats;
        $this->GraphXmlFormats = $GraphXmlFormats;
        $this->GraphTxtFormats = $GraphTxtFormats;
        $this->ImgFormats = $ImgFormats;
        $this->MapVectorFormats = $MapVectorFormats;
        $this->MapRasterFormats = $MapRasterFormats;
        $this->BinDocFormats = $BinDocFormats;
        $this->CpuGenFormats = $CpuGenFormats;
        $this->PlainFormats = $PlainFormats;
        
        # Misc
        $this->Codecs = $Codecs;
        $this->Archives = $Archives;
        $this->Resolutions = $Resolutions;

        # Quick constructor test
        if ($this->Torrent && $this->Torrent['GroupID']) {
            $this->Disabled = ' readonly="readonly"';
            $this->DisabledFlag = true;
        }
    }


    /**
     * ========================
     * = New functional class =
     * ========================
     *
     * Contains functions that output discreet torrent form fields.
     * Useful for <?= echoing in skeleton tables in the sections.
     */


    /**
     * Upload notice
     *
     * Broken into multiple NewTorrent tests for sanity.
     * Each if statement should contain one discreet content block.
     */
    public function uploadNotice()
    {
        if ($this->NewTorrent) {
            $Twig  = Twig::go();
            echo $Twig->render('torrent_form/upload_notice.html');
        } # fi NewTorrent
    }


    /**
     * Announce URLs
     *
     * Announce URLs displayed on the form.
     * They're added to torrents in torrentsdl.class.php.
     * Bio Gazelle supports tiered swarms, T1 private and T2 public.
     */
    public function announceSource()
    {
        if ($this->NewTorrent) {
            $HTML = '<aside class="announce_source">';

            $Announces = ANNOUNCE_URLS[0];
            #$Announces = call_user_func_array('array_merge', ANNOUNCE_URLS);
            $TorrentPass = G::$LoggedUser['torrent_pass'];

            foreach ($Announces as $Announce) {
                $HTML .= <<<HTML
                <p>
                  <strong>Announce</strong>
                  <input type="text"
                    value="$Announce/$TorrentPass/announce"
                    size="60" readonly="readonly"
                    onclick="this.select();" />
                </p>
HTML;
            }

            /**
             * Source (randomize infohash)
             */
            $TorrentSource = Users::get_upload_sources()[0];
            $HTML .= <<<HTML
            <p>
              <strong>Source</strong>
              <input type="text"
                value="$TorrentSource"
                size="30" readonly="readonly"
                onclick="this.select();" />
            </p>
HTML;

            $HTML .= '</aside>';
        } # fi NewTorrent
        return $HTML;
    }


    /**
     * Display torrent upload errors
     */
    public function error()
    {
        if ($this->NewTorrent) {
            if ($this->Error) {
                echo <<<HTML
                <aside class="upload_error">
                  <p>$this->Error</p>
                </aside>
HTML;
            }
        } # fi NewTorrent
    }


    /**
     * head
     *
     * Everything until the catalogue number field.
     * Server-side torrent scrubbing admonishment.
     */
    public function head()
    {
        $ENV = ENV::go();

        G::$DB->query(
            "
        SELECT
          COUNT(`ID`)
        FROM
          `torrents`
        WHERE
          `UserID` = ".G::$LoggedUser['ID']
        );
        list($Uploads) = G::$DB->next_record();
        
        # Torrent form hidden values
        $AuthKey = G::$LoggedUser['AuthKey'];
        $HTML = <<<HTML
        <form class="create_form box pad" name="torrent" action="" enctype="multipart/form-data" method="post"
          onsubmit="$('#post').raw().disabled = 'disabled';">

        <input type="hidden" name="submit" value="true" />
        <input type="hidden" name="auth" value="$AuthKey" />
HTML;

        if (!$this->NewTorrent) {
            # Edit form hidden fields
            $TorrentID = display_str($this->TorrentID);
            $CategoryID = display_str($this->Torrent['CategoryID'] - 1);

            $HTML .= <<<HTML
            <input type="hidden" name="action" value="takeedit" />
            <input type="hidden" name="torrentid" value="$TorrentID" />
            <input type="hidden" name="type" value="$CategoryID" />
HTML;
        } # fi !NewTorrent
        else {
            # Torrent upload hidden fields
            if ($this->Torrent && $this->Torrent['GroupID']) {
                $GroupID = display_str($this->Torrent['GroupID']);
                $CategoryID = display_str($this->Torrent['CategoryID'] - 1);

                $HTML .= <<<HTML
                <input type="hidden" name="groupid" value="$GroupID" />
                <input type="hidden" name="type" value="$CategoryID" />
HTML;
            }

            # Request hidden fields (new or edit?)
            if ($this->Torrent && ($this->Torrent['RequestID'] ?? false)) {
                $RequestID = display_str($this->Torrent['RequestID']);
                $HTML .=  <<<HTML
                <input type="hidden" name="requestid"value="$RequestID" />
HTML;
            }
        } # else

        /**
         * Start printing the torrent form
         */
        $HTML .= '<table class="torrent_form">';
        return $HTML;
    }


    /**
     * New torrent options: file
     */
    public function basicInfo()
    {
        $ENV = ENV::go();
      
        if ($this->NewTorrent) {
            $HTML =  '<h2 class="header">Basic Info</h2>';
            $HTML .= <<<HTML
            <tr>
              <td>
                <label for="file_input" class="required">
                  Torrent File
                </label>
              </td>

              <td>
                <input id="file" type="file" name="file_input" size="50" />

                <p>
                  Set the private flag, e.g.,
                  <code>mktorrent -p -a &lt;announce&gt; &lt;target folder&gt;</code>
                </p>
              </td>
            </tr>
HTML;
        } # fi NewTorrent

        /**
         * New torrent options: category
         */
        if ($this->NewTorrent) {
            $DisabledFlag = ($this->DisabledFlag) ? ' disabled="disabled"' : '';

            $HTML .= <<<HTML
              <tr>
                <td>
                  <label for="type" class="required">
                    Category
                  </label>
                </td>

                <td>
                <select id="categories" name="type" onchange="Categories()" $DisabledFlag>
HTML;

            foreach ($ENV->CATS as $Cat) {
                $Minus1 = $Cat->ID - 1;
                $HTML .= "<option value='$Minus1'";

                if ($Cat->Name === $this->Torrent['CategoryName']) {
                    $HTML .= ' selected="selected"';
                }

                $HTML .= ">$Cat->Name</option>";
            }

            $HTML .= <<<HTML
                  </select>
                  <p id="category_description" class="">
                  <!-- $Cat->Description will live here -->
                  Please see the
                  <a href="/wiki.php?action=article&name=categories">Categories Wiki</a>
                  for details
                  </p>
                </td>
              </tr>
            </table>
HTML;
        } # fi NewTorrent
        
        # Start the dynamic form
        $HTML .= '<div id="dynamic_form">';
        return $HTML;
    } # End head()


    /**
     * foot
     *
     * Make the endmatter.
     */
    public function foot()
    {
        $Torrent = $this->Torrent;
        echo '<table class="torrent_form>';

        /**
         * Freeleech type
         */
        if (!$this->NewTorrent) {
            if (check_perms('torrents_freeleech')) {
                echo <<<HTML
                <tr id="freetorrent">
                  <td>
                    <label for="freeleech">
                      Freeleech
                    </label>
                  </td>
        
                  <td>
                  <select name="freeleech">
HTML;

                $FL = ['Normal', 'Free', 'Neutral'];
                foreach ($FL as $Key => $Name) {
                    $Selected = ($Key === $Torrent['FreeTorrent']) ? ' selected="selected"' : '';

                    echo <<<HTML
                  <option value="$Key" $Selected>
                    $Name
                  </option>
HTML;
                }

                echo <<<HTML
                </select>
                because
                <select name="freeleechtype">
HTML;

                /**
                 * Freeleech reasons
                 */
                $FL = array('N/A', 'Staff Pick', 'Perma-FL', 'Freeleechizer', 'Site-Wide FL');
                foreach ($FL as $Key => $Name) {
                    $Selected = ($Key === $Torrent['FreeLeechType']) ? ' selected="selected"' : '';
                    echo <<<HTML
                    <option value="$Key?>" $Selected>
                      $Name
                    </option>
HTML;
                }

                echo <<<HTML
                  </select>
                </td>
              </tr>
HTML;
            }
        } # fi !NewTorrent

        # For new torrents only
        if ($this->NewTorrent) {
            # Rules notice
            echo <<<HTML
        <tr>
          <td>
            <aside class="torrent_upload">
              <p>
                Be sure that your torrent is approved by the
                <a href="rules.php?p=upload" target="_blank">rules</a>.
                Not doing this will result in a
                <strong class="important_text">warning</strong> or
                <strong class="important_text">worse</strong>.
              </p>
HTML;

            # Request fill notice
            echo <<<HTML
            <p>
              After uploading the torrent, you will have a one hour grace period.
              During this time only you can fill requests with this torrent.
              Make use of it wisely, and
              <a href="requests.php">search the list of requests</a>.
            </p>
HTML;
            echo '</aside></td></tr>';
        }
        

        /**
         * Submit button
         */
        $Value = ($this->NewTorrent) ? 'Upload' : 'Edit';

        echo <<<HTML
              <tr>
                <td>
                  <input id="post" type="submit" value="$Value" />
                </td>
              </tr>
            </table> <!-- torrent_form -->
          </form>
        </div> <!-- dynamic_form -->
HTML;
    } # End foot()


    /**
     * upload_form
     *
     * Finally the "real" upload form.
     * Contains all the field you'd expect.
     *
     * This is currently one enormous function.
     * It has sub-functions, variables, and everything.
     * It continues to the end of the class.
     */
    public function upload_form()
    {
        $ENV = ENV::go();

        $QueryID = G::$DB->get_query_id();
        $Torrent = $this->Torrent;

        # Moved to their own functions
        #echo $this->head();
        #echo $this->basicInfo();

        # Start printing the form
        echo '<h2 class="header">Torrent Form</h2>';
        echo '<table class="torrent_form">';

        
        /**
         * Accession Number
         *
         * The headings below refer to a new generic input schema.
         * The HTML labels and various user-visible text should come from $ENV.
         *
         * RecursiveArrayObject->toArray() returns arrays from, e.g., $ENV->A->B->C.
         * This makes it easy to get and program with any subset of config objects.
         */
        $CatalogueNumber = display_str($Torrent['CatalogueNumber']);
        $Disabled = $this->Disabled;

        # DOI
        echo <<<HTML
        <tr id="javdb_tr">
          <td>
            <label for="catalogue">
              Accession Number
            </label>
          </td>

          <td>
            <input type="text"
              id="catalogue" name="catalogue" size="30"
              placeholder="RefSeq and UniProt preferred"
              value="$CatalogueNumber" />

            <input type="button" autofill="jav" value="Autofill"
              style="pointer-events: none; opacity: 0.5;">
            </input>
          </td>
        </tr>
HTML;

        # RefSeq
        $DisabledFlagInput = (!$this->DisabledFlag)
            ? '<input type="button" autofill="anime" value="Autofill" />'
            : null;

        echo <<<HTML
        <tr id="anidb_tr" class="hidden">
          <td>
            <label for="anidb">
              AniDB Autofill (optional)
            </label>
          </td>
          
          <td>
            <input type="text" id="anidb" size="10" $Disabled />
            $DisabledFlagInput
          </td>
        </tr>
HTML;
        
        # UniProt
        $DisabledFlagInput = (!$this->DisabledFlag)
            ? '<input type="button" autofill="anime" value="Autofill" />'
            : null;

        echo <<<HTML
        <tr id="anidb_tr" class="hidden">
          <td>
            <label for="douj">
              e-hentai URL (optional)
            </label>
          </td>
          
          <td>
            <input type="text" id="douj" size="10" $Disabled />
            $DisabledFlagInput
          </td>
        </tr>
HTML;


        /**
         * Semantic Version
         */
        
        $Version = display_str($Torrent['Version']);
        echo <<<HTML
        <tr id="audio_tr">
          <td>
            <label for="version">
              Version
            </label>
          </td>

          <td>
            <input type="text"
              id="version" name="version"
              size="12" pattern="\d+\.*\d*\.*\d*"
              placeholder="Start with 0.1.0"
              value="$Version" />
            
            <p>
              Please see
              <a href="https://semver.org target=" _blank">Semantic Versioning</a>
            </p>
          </td>
        </tr>
HTML;


        /**
         * Title fields
         *
         * Gazelle has three title fields available, regrettably hardcoded.
         * Ideally we could rank them in importance in the site ontology,
         * then update one config file to apply custom metadata across the board.
         */

        # New torrent upload
        if ($this->NewTorrent) {
            $Disabled = $this->Disabled;


            /**
             * Title 1
             */
            $Title1 = display_str($Torrent['Title']);
            echo <<<HTML
              <tr id="title_tr">
                <td>
                  <label for="title" class="required">
                    Torrent Title
                  </label>
                </td>
              
                <td>
                  <input type="text" id="title" name="title" size="60"
                    placeholder="Definition line, e.g., Alcohol dehydrogenase ADH1"
                    value="$Title1" $Disabled />
                </td>
              </tr>
HTML;


            /**
             * Title 2
             */
            $Title2 = display_str($Torrent['Title2']);
            echo <<<HTML
              <tr id="title_rj_tr">
                <td>
                  <label for="title_rj">
                  Organism
                  </label>
                </td>
              
              <td>
                <input type="text" id="title_rj" name="title_rj" size="60"
                  placeholder="Organism line binomial, e.g., Saccharomyces cerevisiae"
                  value="$Title2" $Disabled />
              </td>
            </tr>
HTML;


            /**
             * Title 3
             */
            $Title3 = display_str($Torrent['TitleJP']);
            echo <<<HTML
            <tr id="title_jp_tr">
              <td>
                <label for="title_jp">
                Strain/Variety
                </label>
              </td>
              
              <td>
                <input type="text" id="title_jp" name="title_jp" size="60"
                  placeholder="Organism line if any, e.g., S288C"
                  value="$Title3" $Disabled />
              </td>
            </tr>
HTML;
        } # fi NewTorrent
        
        
        /**
         * Creator(s)
         * CURRENTLY BROKEN
         *
         * Gazelle supports multiple creators per torrent.
         * One day I want to integrate the creator DB to join:
         *  - DOI publication info in `torrents_screenshots`
         *  - Attributions listed on the creator pages
         *  - Stats about creator vs. total DOI citations
         */
        if ($this->NewTorrent) {
            # Useful variables
            $Disabled = $this->Disabled;
            $AutocompleteOption = Users::has_autocomplete_enabled('other');
          
            $AddRemoveBrackets = <<<HTML
            <a class="add_artist_button brackets" onclick="AddArtistField()">+</a>
            <a class="remove_artist_button brackets" onclick="RemoveArtistField()">&minus;</a>
HTML;

            echo <<<HTML
            <tr id="artists_tr">
              <td>
                <label for="artistfields" class="required">
                  Authors(s)
                </label>
              </td>

              <td id="artistfields">
                <p>
                  One per field, e.g., Robert K. Mortimer [+] David Schild
                </p>
HTML;

            # If there are already creators listed
            if (!empty($Torrent['Artists'])) {
                foreach ($Torrent['Artists'] as $Num => $Artist) {
                    $ArtistName = display_str($Artist['name']);
                    $AddRemoveBrackets = ($Num === 0) ?: null;

                    echo <<<HTML
                    <input type="text" id="artist_$Num" name="artists[]" size="45"
                      value="$ArtistName" $AutocompleteOption $Disabled />
                    $AddRemoveBrackets
HTML;
                }
            } else {
                echo <<<HTML
                <input type="text" id="artist_0" name="artists[]" size="45"
                  value="" $AutocompleteOption $Disabled />
                $AddRemoveBrackets
HTML;
            }
        
            echo '</td></tr>';
        } # fi $NewTorrent


        /**
         * Affiliation
         *
         * The company, studio, lab, etc., that did the work.
         * todo: Add creator affiliation and pick a predetermined one
         * (in our case, last author's institution).
         */
        if ($this->NewTorrent) {
            $Affiliation = display_str($Torrent['Studio']);
            echo <<<HTML
            <tr id="studio_tr">
              <td>
                <label for="studio" class="required">
                  Department/Lab
                </label>
              </td>
              
              <td>
                <input type="text" id="studio" name="studio" size="60"
                  placeholder="Last author's institution, e.g., Lawrence Berkeley Laboratory"
                  value="$Affiliation" $Disabled />
              </td>
            </tr>
HTML;
        }


        /**
         * Location
         *
         * The location of the studio, lab, etc.
         * Currently not sanitized to a standard format.
         */
        if ($this->NewTorrent) {
            $TorrentLocation = display_str($Torrent['Series']);
            echo <<<HTML
            <tr id="series_tr">
              <td>
                <label for="series">
                  Location
                </label>
              </td>
            
              <td>
                <input type="text" id="series" name="series" size="60"
                  placeholder="Physical location, e.g., Berkeley, CA 94720"
                  value="$TorrentLocation" $Disabled />
              </td>
            </tr>
HTML;
        } # fi NewTorrent


        /**
         * ============================
         * = End if NewTorrent fields =
         * ============================
         */


        /**
         * Year
         */
        $TorrentYear = display_str($Torrent['Year']);
        echo <<<HTML
        <tr id="year_tr">
          <td>
            <label for="year" class="required">
              Year
            </label>
          </td>
          
          <td>
            <input type="text" id="year" name="year"
              maxlength="4" size="15" placeholder="Publication year"
              value="$TorrentYear" />
          </td>
        </tr>
HTML;


        /**
         * Misc meta
         *
         * Used in OT Gazelle as Codec.
         * Used in Bio Gazelle as License.
         *
         * Unsure what to call the final field.
         * Some essential, specific one-off info.
         */
        echo <<<HTML
        <tr id="codec_tr">
          <td>
            <label for="codec" class="required">
              License
            </label>
          </td>

          <td>
            <select name="codec">
              <option>---</option>
HTML;

        foreach ($this->Codecs as $Codec) {
            echo "<option value='$Codec'";

            if ($Codec === ($Torrent['Codec'] ?? false)) {
                echo " selected";
            }
            
            echo ">$Codec</option>\n";
        }

        echo <<<HTML
            </select>
            <p>
              Please see
              <a href="http://www.dcc.ac.uk/resources/how-guides/license-research-data" target="_blank">How to License Research Data</a>
            </p>
          </td>
        </tr>
HTML;


        /**
         * ====================================
         * = Begin if NewTorrent fields again =
         * ====================================
         */
        
        
        /**
         * Media
         *
         * The class of technology associated with the data.
         * Answers the question: "Where does the data come from?"
         *
         * This could be the data genesis platform or program,
         * or a genre of physical media (e.g., vinyl record).
         */
        

        /**
         * Make select element
         *
         * Takes an ID, label, torrent, and media list.
         * Returns a media select option as on upload.php.
         */
        function mediaSelect($trID = '', $Label = '', $Torrent = [], $Media = [], $Desc = '')
        {
            echo <<<HTML
                <tr id="$trID">
                  <td>
                    <label for="media" class="required">
                      $Label
                    </label>
                  </td>
  
                  <td>
                    <select name="media">
                      <option>---</option>
  HTML;
  
            foreach ($Media as $Media) {
                echo "<option value='$Media'";
  
                if ($Media === ($Torrent['Media'] ?? false)) {
                    echo ' selected';
                }
  
                echo ">$Media</option>\n";
            }
  
            echo <<<HTML
                    </select>
                    <p>
                      The class of technology used
                    </p>
                  </td>
                </tr>
  HTML;
        } # End mediaSelect()
  

        /**
         * Platform: Sequences
         */
        if ($this->NewTorrent) {
            mediaSelect(
                $trID = 'media_tr',
                $Label = 'Platform',
                $Torrent = $Torrent,
                $Media = $this->SeqPlatforms
            );
            

            /**
             * Platform: Graphs
             */
            mediaSelect(
                $trID = 'media_graphs_tr',
                $Label = 'Platform',
                $Torrent = $Torrent,
                $Media = array_merge($this->GraphPlatforms, $this->SeqPlatforms)
            );
            

            /**
             * Platform: Scalars/Vectors
             */
            mediaSelect(
                $trID = 'media_scalars_vectors_tr',
                $Label = 'Platform',
                $Torrent = $Torrent,
                $Media = array_merge($this->GraphPlatforms, $this->ImgPlatforms)
            );


            /**
             * Platform: Scalars/Vectors
             */
            mediaSelect(
                $trID = 'media_images_tr',
                $Label = 'Platform',
                $Torrent = $Torrent,
                $Media = $this->ImgPlatforms
            );


            /**
             * Platform: Documents
             */
            mediaSelect(
                $trID = 'media_documents_tr',
                $Label = 'Platform',
                $Torrent = $Torrent,
                $Media = $this->DocPlatforms
            );


            /**
             * Platform: Machine Data
             */
            mediaSelect(
                $trID = 'media_machine_data_tr',
                $Label = 'Platform',
                $Torrent = $Torrent,
                $Media = $this->RawPlatforms
            );
        } # fi NewTorrent
        else {
            $TorrentMedia = $Torrent['Media'];
            echo <<<HTML
          <input type="hidden" name="media" value="$TorrentMedia" />
HTML;
        }
        
        
        /**
         * Format
         *
         * Simple: the data's file format.
         * Called Container in OT Gazelle, same diff.
         * In the future, $ENV will automagically set this.
         */
        function formatSelect($trID = '', $Label = '', $Torrent = [], $FileTypes = [])
        {
            echo <<<HTML
            <tr id="$trID">
              <td>
                <label for="container" class="required">
                  $Label
                <label>
              </td>
              
              <td>
                <select id="container" name="container">
                  <option value="Autofill">Autofill</option>
HTML;

            foreach ($FileTypes as $Type => $Extensions) {
                echo "<option value='$Type'";

                if ($Type === ($Torrent['Container'] ?? false)) {
                    echo ' selected';
                }

                echo ">$Type</option>\n";
            }

            echo <<<HTML
                </select>
                <p>
                  File format, or detect from file list
                  <!--
                    todo: Make work with config.php metadata
                    Data file format, or detect from file list
                    Compression algorithm, or detect from file list
                  -->
                </p>
              </td>
            </tr>
HTML;
        } # End formatSelect()


        /**
         * Format: Sequences
         */
        formatSelect(
            $trID = 'container_tr',
            $Label = 'Format',
            $Torrent = $Torrent,
            $FileTypes = array_merge($this->SeqFormats, $this->ProtFormats, $this->PlainFormats)
        );
        

        /**
         * Format: Graphs
         */
        formatSelect(
            $trID = 'container_graphs_tr',
            $Label = 'Format',
            $Torrent = $Torrent,
            $FileTypes = array_merge($this->GraphXmlFormats, $this->GraphTxtFormats, $this->SeqFormats, $this->ProtFormats, $this->PlainFormats)
        );


        /**
         * Format: Scalars/Vectors
         */
        formatSelect(
            $trID = 'container_scalars_vectors_tr',
            $Label = 'Format',
            $Torrent = $Torrent,
            $FileTypes = array_merge($this->ImgFormats, $this->SeqFormats, $this->ProtFormats, $this->PlainFormats)
        );


        /**
         * Format: Images
         */
        formatSelect(
            $trID = 'container_images_tr',
            $Label = 'Format',
            $Torrent = $Torrent,
            $FileTypes = array_merge($this->ImgFormats, $this->PlainFormats)
        );


        /**
         * Format: Spatial
         */
        formatSelect(
            $trID = 'container_spatial_tr',
            $Label = 'Format',
            $Torrent = $Torrent,
            $FileTypes = array_merge($this->MapVectorFormats, $this->MapRasterFormats, $this->ImgFormats, $this->PlainFormats)
        );


        /**
         * Format: Documents
         */
        formatSelect(
            $trID = 'container_documents_tr',
            $Label = 'Format',
            $Torrent = $Torrent,
            $FileTypes = array_merge($this->BinDocFormats, $this->CpuGenFormats, $this->PlainFormats)
        );


        /**
         * Format: Compression
         */
        formatSelect(
            $trID = 'archive_tr',
            $Label = 'Archive',
            $Torrent = $Torrent,
            $FileTypes = $this->Archives
        );


        /**
         * Scope
         *
         * How complete the data are.
         * Relatively, how much information does it contain?
         */
        $TorrentResolution = ($Torrent['Resolution']) ?? '';
        echo <<<HTML
        <tr id="resolution_tr">
          <td>
            <label for="ressel" class="required">
              Scope
            </label>
          </td>

          <td>
            <select id="ressel" name="ressel" onchange="SetResolution()">
              <option>---</option>
HTML;

        foreach ($this->Resolutions as $Res) {
            echo "<option value='$Res'";

            if ($Res === ($Torrent['Resolution'] ?? false)
              || (!isset($FoundRes) && ($Torrent['Resolution'] ?? false)
              && $Res === 'Other')) {
                echo " selected";
                $FoundRes = true;
            }

            echo ">$Res</option>\n";
        }
        
        echo <<<HTML
            </select>
            <!-- Enter your own -->
            <input type="text" id="resolution" name="resolution" size="15" maxlength="20"
              class="hidden" value="$TorrentResolution" readonly>
            </input>
        
            <script>
            if ($('#ressel').raw().value === 'Other') {
              $('#resolution').raw().readOnly = false
              $('#resolution').gshow()
            }
            </script>
        
            <p>
              How complete the data is, specifically or conceptually
            </p>
          </td>
        </tr>
HTML;


        /**
         * ====================================
         * = Begin if NewTorrent fields again =
         * ====================================
         */


        /**
         * Tags
         *
         * Simple enough.
         * I won't rehash tag management.
         */
        if ($this->NewTorrent) {
            echo <<<HTML
            <tr id="tags_tr">
              <td>
                <label for="tags" class="required">
                  Tags
                </label>
              </td>
              <td>
HTML;

            $GenreTags = G::$Cache->get_value('genre_tags');
            if (!$GenreTags) {
                G::$DB->query("
                SELECT
                  `Name`
                FROM
                  `tags`
                WHERE
                  `TagType` = 'genre'
                ORDER BY
                  `Name`
                ");

                $GenreTags = G::$DB->collect('Name');
                G::$Cache->cache_value('genre_tags', $GenreTags, 3600*6);
            }
          
            # todo: Find a better place for these
            $Disabled = ($this->DisabledFlag) ? ' disabled="disabled"' : null;
            $TorrentTagList = display_str(implode(', ', explode(',', $Torrent['TagList'])));
            $AutocompleteOption = Users::has_autocomplete_enabled('other');

            echo <<<HTML
            <select id="genre_tags" name="genre_tags" onchange="add_tag(); return false;" $Disabled>
              <option>---</option>
HTML;

            foreach (Misc::display_array($GenreTags) as $Genre) {
                echo <<<HTML
                <option value="$Genre">
                  $Genre
                </option>
HTML;
            }

            echo <<<HTML
                </select>
                <input type="text" id="tags" name="tags" size="60"
                  placeholder="Comma-seperated list of at least 5 tags"
                  value="$TorrentTagList" $AutocompleteOption />
              </td>
            </tr>
HTML;
        } # fi NewTorrent


        /**
         * Picture
         *
         * Another obvious field.
         */
        if ($this->NewTorrent) {
            $TorrentImage = display_str($Torrent['Image']);
            $Disabled = $this->Disabled;

            echo <<<HTML
            <tr id="cover_tr">
            <td>
              <label for="image">
                Picture
              </label>
            </td>
            
            <td>
              <input type="text" id="image" name="image" size="60"
                placeholder="A meaningful picture, e.g., the specimen or a thumbnail"
                value="$TorrentImage" $Disabled? />
            </td>
          </tr>
HTML;
        }


        /**
         * Mirrors
         *
         * This should be in the `torrents` table not `torrents_group.`
         * The intended use is for web seeds, Dat mirrors, etc.
         */
        if (!$this->DisabledFlag && $this->NewTorrent) {
            $TorrentMirrors = display_str($Torrent['Mirrors']);
            echo <<<HTML
            <tr id="mirrors_tr">
              <td>
                <label for="mirrors">
                  Mirrors
                </label>
              </td>
              
              <td>
                <!-- Needs to be all on one line -->
                <textarea rows="2" name="mirrors" id="mirrors"
                  placeholder="Up to two FTP/HTTP addresses that either point directly to a file, or for multi-file torrents, to the enclosing folder">$TorrentMirrors</textarea>
              </td>
            </tr>
HTML;
        }


        /**
         * Samples
         *
         * Called Screenshots in OT Gazelle.
         * Called Publication in Bio Gazelle.
         * Eventually this will be a proper database in itself,
         * pulling info from DOI to populate the schema.
         */
        if (!$this->DisabledFlag && $this->NewTorrent) {
            $TorrentSamples = display_str($Torrent['Screenshots']);

            echo <<<HTML
            <tr id="screenshots_tr">
              <td>
                <label for="screenshots">
                  Publications
                </label>
              </td>
              
              <td>
                <!-- Needs to be all on one line -->
                <textarea rows="8" name="screenshots" id="screenshots"
                  placeholder="Up to ten DOI numbers, one per line">$TorrentSamples</textarea>
              </td>
            </tr>
HTML;
        }


        /**
         * Torrent group description
         *
         * The text on the main torrent pages,
         * between torrent info and torrent comments,
         * visible even if individual torrents are collapsed.
         */
        if ($this->NewTorrent) {
            echo <<<HTML
            <tr id="group_desc_tr">
              <td>
                <label for="album_desc" class="required">
                  Torrent Group Description
                </label>
              </td>
              <td>
HTML;

            new TEXTAREA_PREVIEW(
                $Name = 'album_desc',
                $ID = 'album_desc',
                $Value = display_str($Torrent['GroupDescription']) ?? '',
                $Placeholder = "General info about the torrent subject's function or significance",
            );

            echo '</td></tr>';
        } # fi NewTorrent


        /**
         * ============================
         * = End if NewTorrent fields =
         * ============================
         */


        /**
         * Torrent description
         *
         * The test displayed when torrent info is expanded.
         * It should describe the specific torrent, not the group.
         */
        echo <<<HTML
        <tr id="release_desc_tr">
          <td>
            <label for="release_desc">
              Torrent Description
            </label>
          </td>
          <td>
HTML;

        new TEXTAREA_PREVIEW(
            $Name = 'release_desc',
            $ID = 'release_desc',
            $Value = display_str($Torrent['TorrentDescription'] ?? ''),
            $Placeholder = 'Specific info about the protocols and equipment used to produce the data',
        );

        echo '</td></tr>';


        /**
         * Boolean options
         *
         * Simple checkboxes that do stuff.
         * Currently checks for data annotations and anonymous uploads.
         * More fields could be created as the need arises.
         */

         
        /**
         * Aligned/Annontated
         *
         * Called Censored in OT Gazelle.
         */
        $TorrentAnnotated = ($Torrent['Censored'] ?? 0) ? ' checked' : '';
        echo <<<HTML
        <tr id="censored_tr">
          <td>
            <label for="censored">
              Aligned/Annotated
            </label>
          </td>
          
          <td>
            <input type="checkbox" name="censored" value="1" $TorrentAnnotated />
            &ensp;
            Whether the torrent contains alignments, annotations, or other structural metadata
          </td>
        </tr>
HTML;


        /**
         * Upload Anonymously
         */
        $TorrentAnonymous = ($Torrent['Anonymous'] ?? false) ? ' checked' : '';
        echo <<<HTML
        <tr id="anon_tr">
          <td>
            <label for="anonymous">
              Upload Anonymously
            </label>
          </td>
          
          <td>
            <input type="checkbox" name="anonymous" value="1" $TorrentAnonymous />
            &ensp;
            Hide your username from other users on the torrent details page
          </td>
        </tr>
HTML;

        # End the giant dynamic form table
        echo '</table>';

        # Drink a stiff one
        $this->foot();
        G::$DB->set_query_id($QueryID);
    } # End upload_form()
} # End TorrentForm()
