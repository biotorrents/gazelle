<?php
declare(strict_types = 1);

class View
{
    /**
     * @var string Path relative to where (P)HTML templates reside
     */
    const IncludePath = './design/views/';


    /**
     * HTTP/2 Server Push headers for Cloudflare
     * @see https://blog.cloudflare.com/using-http-2-server-push-with-php/
     */
    public function pushAsset($uri, $type)
    {
        $ENV = ENV::go();

        # Bad URI or type
        if ((!$uri || !is_string($uri))
         || (!$type || !is_string($type))) {
            return error(404);
        }

        $integrity = base64_encode(hash_file($ENV->SRI, "$ENV->SERVER_ROOT/$uri", true));

        # Send raw HTTP headers - preloading this way is bloat
        # 26 requests, 1.58 MB, 584ms
        #    vs.
        # 10 requests, 730.8 KB, 460ms
        #header("Link: <$uri?v=$filemtime>; rel=preload; as=$type", false);

        switch ($type) {
            case 'script':
                $HTML = "<script src='$uri' integrity='$ENV->SRI-$integrity' crossorigin='anonymous'></script>";
                break;

            case 'style':
                $HTML = "<link rel='stylesheet' href='$uri' integrity='$ENV->SRI-$integrity' crossorigin='anonymous' />";
                break;

            case 'font':
                $HTML = "<link rel='preload' as='font' href='$uri' integrity='$ENV->SRI-$integrity' crossorigin='anonymous' />";
                break;

            default:
                break;
        }

        # Needs to echo into the page
        return $HTML;
    }


    /**
     * This function is to include the header file on a page.
     *
     * @param $PageTitle the title of the page
     * @param $JSIncludes is a comma-separated list of JS files to be included on
     *                    the page. ONLY PUT THE RELATIVE LOCATION WITHOUT '.js'
     *                    example: 'somefile,somedir/somefile'
     */
    public static function show_header($PageTitle = '', $JSIncludes = '', $CSSIncludes = '')
    {
        $ENV = ENV::go();
        global $Document, $Mobile, $Classes;

        if ($PageTitle !== '') {
            $PageTitle .= " $ENV->SEP ";
        }

        $PageTitle .= $ENV->SITE_NAME;
        $PageID = array(
            $Document, // Document
            empty($_REQUEST['action']) ? false : $_REQUEST['action'], // Action
            empty($_REQUEST['type']) ? false : $_REQUEST['type'] // Type
        );

        if (!is_array(G::$LoggedUser)
          || empty(G::$LoggedUser['ID'])
          || (isset($Options['recover']) && $Options['recover'] === true)) {
            require_once "$ENV->SERVER_ROOT/design/publicheader.php";
        } else {
            require_once "$ENV->SERVER_ROOT/design/privateheader.php";
        }
    }
    

    /**
     * This function is to include the footer file on a page.
     *
     * @param $Options an optional array that you can pass information to the
     *                 header through as well as setup certain limitations
     *                 Here is a list of parameters that work in the $Options array:
     *                 ['disclaimer'] = [boolean] (False) Displays the disclaimer in the footer
     */
    public static function show_footer($Options = [])
    {
        $ENV = ENV::go();
        global $ScriptStartTime, $SessionID, $UserSessions, $Debug, $Time, $Mobile;

        if (!is_array(G::$LoggedUser)
          || empty(G::$LoggedUser['ID'])
          || (isset($Options['recover']) && $Options['recover'] === true)) {
            require_once "$ENV->SERVER_ROOT/design/publicfooter.php";
        } else {
            require_once "$ENV->SERVER_ROOT/design/privatefooter.php";
        }
    }


    /**
     * This is a generic function to load a template fromm /design and render it.
     * The template should be in /design/my_template_name.php, and have a class
     * in it called MyTemplateNameTemplate (my_template_name transformed to
     * MixedCase, with the word 'Template' appended).
     * This class should have a public static function render($Args), where
     * $Args is an associative array of the template variables.
     * You should note that by "Template", we mean "php file that outputs stuff".
     *
     * This function loads /design/$TemplateName.php, and then calls
     * render($Args) on the class.
     *
     * @param string $TemplateName The name of the template, in underscore_format
     * @param array $Args the arguments passed to the template.
     */
    public static function render_template($TemplateName, $Args)
    {
        $ENV = ENV::go();
        static $LoadedTemplates; // Keep track of templates we've already loaded.
        $ClassName = '';

        if (isset($LoadedTemplates[$TemplateName])) {
            $ClassName = $LoadedTemplates[$TemplateName];
        } else {
            include "$ENV->SERVER_ROOT/design/$TemplateName.php";

            // Turn template_name into TemplateName
            $ClassNameParts = explode('_', $TemplateName);
            foreach ($ClassNameParts as $Index => $Part) {
                $ClassNameParts[$Index] = ucfirst($Part);
            }
            
            $ClassName = implode($ClassNameParts) . 'Template';
            $LoadedTemplates[$TemplateName] = $ClassName;
        }
        $ClassName::render($Args);
    }


    /**
     * This method is similar to render_template, but does not require a
     * template class.
     *
     * Instead, this method simply renders a PHP file (PHTML) with the supplied
     * variables.
     *
     * All files must be placed within {self::IncludePath}. Create and organize
     * new paths and files. (e.g.: /design/views/artist/, design/view/forums/, etc.)
     *
     * @static
     * @param string  $TemplateFile A relative path to a PHTML file
     * @param array   $Variables Assoc. array of variables to extract for the template
     * @param boolean $Buffer enables Output Buffer
     * @return boolean|string
     *
     * @example <pre><?php
     *  // box.phtml
     *  <p id="<?=$id?>">Data</p>
     *
     *  // The variable $id within box.phtml will be filled by $some_id
     *  View::parse('section/box.phtml', array('id' => $some_id));
     *
     *  // Parse a template without outputing it
     *  $SavedTemplate = View::parse('sec/tion/eg.php', $DataArray, true);
     *  // later . . .
     *  echo $SavedTemplate; // Output the buffer
     * </pre>
     */
    public static function parse($TemplateFile, array $Variables = [], $Buffer = false)
    {
        $Template = self::IncludePath . $TemplateFile;
        if (file_exists($Template)) {
            extract($Variables);
            if ($Buffer) {
                ob_start();
                include $Template;
                $Content = ob_get_contents();
                ob_end_clean();
                return $Content;
            }
            return include $Template;
        }
    }
}
