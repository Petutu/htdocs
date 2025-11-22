<?php

const CRYPTO_CIPHER = 'AES-256-CBC';
const CRYPTO_KEY    = 'change_this_to_32_bytes_secret_key!'; // 32 znaků
const CRYPTO_IV_LEN = 16;

function encrypt_field(string $plain): string {
    if ($plain === '') return '';
    $iv = random_bytes(CRYPTO_IV_LEN);
    $cipher = openssl_encrypt($plain, CRYPTO_CIPHER, CRYPTO_KEY, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $cipher);
}

function decrypt_field(?string $stored): string {
    if (!$stored) return '';
    $data = base64_decode($stored, true);
    if ($data === false || strlen($data) <= CRYPTO_IV_LEN) return '';
    $iv = substr($data, 0, CRYPTO_IV_LEN);
    $cipher = substr($data, CRYPTO_IV_LEN);
    $plain = openssl_decrypt($cipher, CRYPTO_CIPHER, CRYPTO_KEY, OPENSSL_RAW_DATA, $iv);
    return $plain === false ? '' : $plain;
}
