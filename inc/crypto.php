<?php
// inc/crypto.php
$config = require __DIR__ . '/../config/config.php';

function it_hex2bin_key($hex) {
  $bin = @hex2bin($hex);
  if ($bin === false || strlen($bin) < 32) {
    // Fallback weak key for dev only
    $bin = str_repeat('x', 32);
  }
  return $bin;
}

function it_encrypt($plaintext) {
  if ($plaintext === null || $plaintext === '') return $plaintext;
  $key = it_hex2bin_key((require __DIR__ . '/../config/config.php')['encryption_key_hex']);
  $iv = random_bytes(12);
  $tag = '';
  $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
  return base64_encode($iv . $tag . $ciphertext);
}

function it_decrypt($b64) {
  if ($b64 === null || $b64 === '') return $b64;
  $raw = base64_decode($b64, true);
  if ($raw === false || strlen($raw) < 29) return null;
  $iv = substr($raw, 0, 12);
  $tag = substr($raw, 12, 16);
  $ciphertext = substr($raw, 28);
  $key = it_hex2bin_key((require __DIR__ . '/../config/config.php')['encryption_key_hex']);
  $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
  return $plaintext === false ? null : $plaintext;
}
