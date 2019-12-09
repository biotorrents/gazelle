<?php

/*
 * Force file downloads
 * Currently used to download BibTeX citations
 * @todo Make this work with POST and display Show/Hide and Download buttons on the same line
 *

if (isset($_POST['file']) && isset($_POST['content'])) {
    // Get parameters
    $file = urldecode($_POST['file']); // Decode URL-encoded string
    $content = urldecode($_POST['file']);

    $filepath = "/tmp/$file.bib";
    file_put_contents($filepath, $content);

    // Process download
    if (file_exists($filepath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        flush(); // Flush system output buffer
        readfile($filepath);
        exit;
    }
}
*/
