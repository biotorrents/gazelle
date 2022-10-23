<?php

declare(strict_types=1);


/**
 * View
 *
 * THIS IS GOING AWAY
 */

class View
{
    /**
     * @var string Path relative to where (P)HTML templates reside
     */
    public const IncludePath = './design/views/';


    /**
     * HTTP/2 Server Push headers for Cloudflare
     * @see https://blog.cloudflare.com/using-http-2-server-push-with-php/
     */
    public static function pushAsset(string $uri, string $type)
    {
        $ENV = ENV::go();

        $uri = preg_replace(".$ENV->staticServer.", '', $uri);
        #$integrity = base64_encode(hash_file($ENV->SRI, "$ENV->SERVER_ROOT/$uri", true));

        switch ($type) {
            case 'script':
                $HTML = "<script src='$uri' crossorigin='anonymous'></script>";
                #$HTML = "<script defer src='$uri' integrity='$ENV->SRI-$integrity' crossorigin='anonymous'></script>";
                break;

            case 'style':
            $HTML = "<link rel='stylesheet' href='$uri' crossorigin='anonymous' />";
            #$HTML = "<link rel='stylesheet' href='$uri' integrity='$ENV->SRI-$integrity' crossorigin='anonymous' />";
            #$HTML = "<link rel='preload' as='style' href='$uri' integrity='$ENV->SRI-$integrity' crossorigin='anonymous' />";
                break;

            case 'font':
                $HTML = "<link rel='preload' as='font' href='$uri' crossorigin='anonymous' />";
                #$HTML = "<link rel='preload' as='font' href='$uri' integrity='$ENV->SRI-$integrity' crossorigin='anonymous' />";
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
    public static function header($PageTitle = '', $JSIncludes = '', $CSSIncludes = '')
    {
        $ENV = ENV::go();
        global $Document, $Mobile, $Classes;

        if ($PageTitle !== '') {
            $PageTitle .= " $ENV->separator ";
        }

        $PageTitle .= $ENV->siteName;
        $PageID = array(
            $Document, // Document
            empty($_REQUEST['action']) ? false : $_REQUEST['action'], // Action
            empty($_REQUEST['type']) ? false : $_REQUEST['type'] // Type
        );

        # hardcode private (public already twig'd)
        require_once "$ENV->SERVER_ROOT/design/privateheader.php";
    }


    /**
     * This function is to include the footer file on a page.
     *
     * @param $Options an optional array that you can pass information to the
     *                 header through as well as setup certain limitations
     *                 Here is a list of parameters that work in the $Options array:
     *                 ['disclaimer'] = [boolean] (False) Displays the disclaimer in the footer
     */
    public static function footer($Options = [])
    {
        $ENV = ENV::go();
        global $SessionID, $UserSessions, $Time, $Mobile;
        #global $ScriptStartTime, $SessionID, $UserSessions, $debug, $Time, $Mobile;

        # hardcode private (public already twig'd)
        require_once "$ENV->SERVER_ROOT/design/privatefooter.php";
    }


    /**
     * textarea
     *
     * Formerly in the TEXTAREA_PREVIEW class.
     * Now it's just a call to Twig and EasyMDE.
     *
     * @param $id Required ID for attaching EasyMDE
     * @param $name If not defined, it's the same as $id
     * @param $placeholder Some helpful text, maybe
     * @param $value The pre-populated textarea
     */
    public static function textarea(
        string $id,
        string $name = '',
        string $placeholder = '',
        string $value = '',
    ) {
        $app = App::go();

        $name = (empty($name)) ?? $id;
        $uuid = uniqid(); # autosave

        $app->twig->display(
            '_base/textarea.twig',
            [
              'id' => $id,
              'name' => $name,
              'placeholder' => $placeholder,
              'value' => $value,
              'uuid' => $uuid,
            ]
        );
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
