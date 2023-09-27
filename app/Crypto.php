<?php

declare(strict_types=1);


/**
 * Crypto
 */

class Crypto
{
    # databse crypto cipher
    private static string $cipher = "aes-256-cbc";

    # the apcu key to use
    private static string $key = "DBKEY";


    /**
     * encrypt
     *
     * Encrypts input text for use in database.
     *
     * @param string $plaintext
     * @return encrypted string or false if DB key not accessible
     */
    public static function encrypt(mixed $plaintext): string|bool
    {
        if (!self::apcuExists()) {
            return false;
        }

        # fix null error: missing value
        $plaintext = strval($plaintext);

        $iv_size = openssl_cipher_iv_length(self::$cipher);
        $iv = openssl_random_pseudo_bytes($iv_size);

        return base64_encode(
            $iv . openssl_encrypt(
                $plaintext,
                self::$cipher,
                apcu_fetch(self::$key),
                OPENSSL_RAW_DATA,
                $iv
            )
        );
    }


    /**
     * decrypt
     *
     * Decrypts input text from database.
     *
     * @param string $ciphertext
     * @return decrypted string or false if DB key not accessible
     */
    public static function decrypt(mixed $ciphertext): string|bool
    {
        if (!self::apcuExists()) {
            return false;
        }

        # fix null error: missing value
        $ciphertext = strval($ciphertext);

        $iv_size = openssl_cipher_iv_length(self::$cipher);
        $iv = substr(base64_decode($ciphertext), 0, $iv_size);
        $ciphertext = substr(base64_decode($ciphertext), $iv_size);

        return openssl_decrypt(
            $ciphertext,
            self::$cipher,
            apcu_fetch(self::$key),
            OPENSSL_RAW_DATA,
            $iv
        );
    }


    /**
     * apcuExists
     *
     * Is self::$key present?
     *
     * @return bool
     */
    public static function apcuExists(): bool
    {
        return apcu_exists(self::$key);
    }
} # class
