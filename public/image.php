<?php
declare(strict_types=1);

# Functions and headers needed by the image proxy
error_reporting(E_COMPILE_ERROR|E_RECOVERABLE_ERROR|E_ERROR|E_CORE_ERROR);

if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
    header('HTTP/1.1 304 Not Modified');
    error();
}

header('Expires: '.date('D, d-M-Y H:i:s \U\T\C', time() + 3600 * 24 * 120)); # 120 days
header('Last-Modified: '.date('D, d-M-Y H:i:s \U\T\C', time()));


/**
 * img_error
 */
function img_error($Type)
{
    $ENV = ENV::go();

    header('Content-type: image/gif');
    error(file_get_contents("$ENV->serverRoot/sections/image/err_imgs/$Type.png"));
}


/**
 * invisible
 */
function invisible($Image)
{
    $Count = imagecolorstotal($Image);
    if ($Count === 0) {
        return false;
    }

    $TotalAlpha = 0;
    for ($i = 0; $i < $Count; ++$i) {
        $Color = imagecolorsforindex($Image, $i);
        $TotalAlpha += $Color['alpha'];
    }

    return (($TotalAlpha / $Count) === 127);
}


/**
 * verysmall
 */
function verysmall($Image)
{
    return ((imagesx($Image) * imagesy($Image)) < 25);
}


/**
 * image_type
 */
function image_type($Data)
{
    if (!strncmp($Data, 'GIF', 3)) {
        return 'gif';
    }

    if (!strncmp($Data, pack('H*', '89504E47'), 4)) {
        return 'png';
    }

    if (!strncmp($Data, pack('H*', 'FFD8'), 2)) {
        return 'jpeg';
    }

    if (!strncmp($Data, 'BM', 2)) {
        return 'bmp';
    }

    if (!strncmp($Data, 'II', 2) || !strncmp($Data, 'MM', 2)) {
        return 'tiff';
    }

    if (!substr_compare($Data, 'webm', 31, 4)) {
        return 'webm';
    }
}


/**
 * image_height
 */
function image_height($Type, $Data)
{
    global $URL, $_GET;
    $Length = strlen($Data);

    switch ($Type) {
        case 'jpeg':
            # https://www.geocities.ws/crestwoodsdd/JPEG.htm
            $i = 4;
            $Data = (substr($Data, $i));
            $Block = unpack('nLength', $Data);
            $Data = substr($Data, $Block['Length']);
            $i += $Block['Length'];
            $Str []= 'Started 4, + '.$Block['Length'];

            # Iterate through the blocks until we find the start of frame marker (FFC0)
            while ($Data !== '') {
                # Get info about the block
                $Block = unpack('CBlock/CType/nLength', $Data);

                # We should be at the start of a new block
                if ($Block['Block'] !== '255') {
                    break;
                }

                if ($Block['Type'] !== '192') { # C0
                    $Data = substr($Data, $Block['Length'] + 2); # Next block
                    $Str []= 'Started $i, + '.($Block['Length'] + 2);
                    $i += ($Block['Length'] + 2);
                }
                
                # We're at the FFC0 block
                else {
                    # Skip FF C0 Length(2) precision(1)
                    $Data = substr($Data, 5);
                    $i += 5;
                    $Height = unpack('nHeight', $Data);
                    return $Height['Height'];
                }
            }
            break;
            
        case 'gif':
            $Data = substr($Data, 8);
            $Height = unpack('vHeight', $Data);
            return $Height['Height'];
        
        case 'png':
            $Data = substr($Data, 20);
            $Height = unpack('NHeight', $Data);
            return $Height['Height'];
            
        default:
            return 0;
    }
}


# bootstrap/app.php contains all we need and includes sections/image/index.php
require_once __DIR__.'/../bootstrap/app.php';
