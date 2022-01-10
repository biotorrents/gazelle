<?php
declare(strict_types=1);

class Crypto
{
    /**
     * Encrypts input text for use in database
     *
     * @param string $plaintext
     * @return encrypted string or false if DB key not accessible
     */
    public static function encrypt($plaintext)
    {
        if (apcu_exists('DBKEY')) {
            $iv_size = openssl_cipher_iv_length('aes-256-cbc');
            $iv = openssl_random_pseudo_bytes($iv_size);
            $ret = base64_encode($iv.openssl_encrypt($plaintext, 'aes-256-cbc', apcu_fetch('DBKEY'), OPENSSL_RAW_DATA, $iv));
            return $ret;
        } else {
            return false;
        }
    }

    /**
     * Decrypts input text from database
     *
     * @param string $ciphertext
     * @return decrypted string string or false if DB key not accessible
     */
    public static function decrypt($ciphertext)
    {
        if (apcu_exists('DBKEY')) {
            $iv_size = openssl_cipher_iv_length('aes-256-cbc');
            $iv = substr(base64_decode($ciphertext), 0, $iv_size);
            $ciphertext = substr(base64_decode($ciphertext), $iv_size);
            return openssl_decrypt($ciphertext, 'aes-256-cbc', apcu_fetch('DBKEY'), OPENSSL_RAW_DATA, $iv);
        } else {
            return false;
        }
    }
}
