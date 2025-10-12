<?php

namespace services\core;

class EncryptionService
{
    private string $key;
    private string $cipher = 'aes-256-gcm';

    public function __construct()
    {
        $key = $_ENV['ENCRYPTION_KEY'] ?? '';
        if (empty($key)) {
            throw new \Exception('A chave de criptografia (ENCRYPTION_KEY) não está definida no arquivo .env.');
        }
        $this->key = substr(hash('sha256', $key, true), 0, 32);
    }

    public function encrypt(string $plaintext): string
    {
        $ivlen = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $tag = '';
        $tag_length = 16;

        $ciphertext = openssl_encrypt($plaintext, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv, $tag, '', $tag_length);

        return base64_encode($iv . $tag . $ciphertext);
    }

    public function decrypt(string $encrypted_b64): ?string
    {
        $decoded = base64_decode($encrypted_b64);
        if ($decoded === false) {
            return null;
        }

        $ivlen = openssl_cipher_iv_length($this->cipher);
        $tag_length = 16;
        
        $iv = substr($decoded, 0, $ivlen);
        $tag = substr($decoded, $ivlen, $tag_length);
        $ciphertext = substr($decoded, $ivlen + $tag_length);

        $decrypted = openssl_decrypt($ciphertext, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv, $tag);

        return $decrypted === false ? null : $decrypted;
    }
}
