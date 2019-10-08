<?php

class TwoFactorAuth {
  private $algorithm;
  private $period;
  private $digits;
  private $issuer;
  private static $_base32dict = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567=';
  private static $_base32;
  private static $_base32lookup = [];
  private static $_supportedalgos = array('sha1', 'sha256', 'sha512', 'md5');

  function __construct($issuer = null, $digits = 6, $period = 30, $algorithm = 'sha1') {
    $this->issuer = $issuer;
    $this->digits = $digits;
    $this->period = $period;

    $algorithm = strtolower(trim($algorithm));
    if (!in_array($algorithm, self::$_supportedalgos)){
      $algorithm = 'sha1';
    }
    $this->algorithm = $algorithm;

    self::$_base32 = str_split(self::$_base32dict);
    self::$_base32lookup = array_flip(self::$_base32);
  }

  public function createSecret($bits = 210) {
    $secret = '';
    $bytes = ceil($bits / 5); //We use 5 bits of each byte (since we have a 32-character 'alphabet' / BASE32)
    $rnd = random_bytes($bytes);
    for ($i = 0; $i < $bytes; $i++) {
      $secret .= self::$_base32[ord($rnd[$i]) & 31]; //Mask out left 3 bits for 0-31 values
    }
    return $secret;
  }

  // Calculate the code with given secret and point in time
  public function getCode($secret, $time = null) {
    $secretkey = $this->base32Decode($secret);

    $timestamp = "\0\0\0\0" . pack('N*', $this->getTimeSlice($this->getTime($time)));  // Pack time into binary string
    $hashhmac = hash_hmac($this->algorithm, $timestamp, $secretkey, true);             // Hash it with users secret key
    $hashpart = substr($hashhmac, ord(substr($hashhmac, -1)) & 0x0F, 4);               // Use last nibble of result as index/offset and grab 4 bytes of the result
    $value = unpack('N', $hashpart);                                                   // Unpack binary value
    $value = $value[1] & 0x7FFFFFFF;                                                   // Drop MSB, keep only 31 bits

    return str_pad($value % pow(10, $this->digits), $this->digits, '0', STR_PAD_LEFT);
  }

  // Check if the code is correct. This will accept codes starting from now +/- ($discrepancy * period) seconds
  public function verifyCode($secret, $code, $discrepancy = 1, $time = null) {
    $result = false;
    $timetamp = $this->getTime($time);

    // Always iterate all possible codes to prevent timing-attacks
    for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
      $result |= hash_equals($this->getCode($secret, $timetamp + ($i * $this->period)), $code);
    }

    return (bool)$result;
  }

  // Get data-uri of QRCode
  public function getQRCodeImageAsDataUri($label, $secret, $size = 300) {

    if (exec('which qrencode')) {
      $QRCodeImage = shell_exec("qrencode -s ".(int)($size/40)." -m 3 -o - '".$this->getQRText($label, $secret)."'");
    } else {
      $curlhandle = curl_init();

      curl_setopt_array($curlhandle, array(
        CURLOPT_URL => 'https://chart.googleapis.com/chart?cht=qr&chs='.$size.'x'.$size.'&chld=L|1&chl='.rawurlencode($this->getQRText($label, $secret)),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_DNS_CACHE_TIMEOUT => 10,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'TwoFactorAuth'
      ));

      $QRCodeImage = curl_exec($curlhandle);
      curl_close($curlhandle);
    }

    return 'data:image/png;base64,'.base64_encode($QRCodeImage);
  }

  private function getTime($time) {
    return ($time === null) ? time() : $time;
  }

  private function getTimeSlice($time = null, $offset = 0) {
    return (int)floor($time / $this->period) + ($offset * $this->period);
  }

  // Builds a string to be encoded in a QR code
  public function getQRText($label, $secret) {
    $QRText = 'otpauth://totp/'.rawurlencode($label).'?secret='.rawurlencode($secret);
    $QRText .= ($this->issuer) ? '&issuer='.rawurlencode($this->issuer) : '';
    $QRText .= ($this->period != 30) ? '&period='.intval($this->period) : '';
    $QRText .= ($this->algorithm != 'sha1') ? '&algorithm='.rawurlencode(strtoupper($this->algorithm)) : '';
    $QRText .= ($this->digits != 6) ? '&digits='.intval($this->digits) : '';
    return $QRText;
  }

  private function base32Decode($value) {
    if (strlen($value)==0) { return ''; }

    $buffer = '';
    foreach (str_split($value) as $char) {
      if ($char !== '=') {
        $buffer .= str_pad(decbin(self::$_base32lookup[$char]), 5, 0, STR_PAD_LEFT);
      }
    }
    $length = strlen($buffer);
    $blocks = trim(chunk_split(substr($buffer, 0, $length - ($length % 8)), 8, ' '));

    $output = '';
    foreach (explode(' ', $blocks) as $block) {
      $output .= chr(bindec(str_pad($block, 8, 0, STR_PAD_RIGHT)));
    }

    return $output;
  }
}
