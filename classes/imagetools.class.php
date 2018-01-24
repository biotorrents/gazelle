<?php

/**
 * ImageTools Class
 * Thumbnail aide, mostly
 */
class ImageTools {

  /**
   * Determine the image URL. This takes care of the image proxy and thumbnailing.
   * @param string $Url
   * @param string $Thumb image proxy scale profile to use
   * @return string
   */
  public static function process($Url = '', $Thumb = false) {
    if (!$Url) return '';
    if (preg_match('/^https:\/\/('.SITE_DOMAIN.'|'.IMAGE_DOMAIN.')\//', $Url) || $Url[0]=='/') {
      if (strpos($Url, '?') === false) $Url .= '?';
      return $Url;
    } else {
      return 'https://'.IMAGE_DOMAIN.($Thumb?"/$Thumb/":'/').'?h='.rawurlencode(base64_encode(hash_hmac('sha256', $Url, IMAGE_PSK, true))).'&i='.urlencode($Url);
    }
  }

  /**
   * Checks if a link's host is (not) good, otherwise displays an error.
   * @param string $Url Link to an image
   * @return boolean
   */
  public static function blacklisted($Url, $ShowError = true) {
    $Blacklist = ['tinypic.com'];
    foreach ($Blacklist as $Value) {
      if (stripos($Url, $Value) !== false) {
        if ($ShowError) {
          error($Value . ' is not an allowed image host. Please use a different host.');
        }
        return true;
      }
    }
    return false;
  }
}
