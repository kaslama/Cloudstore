<?php
// utils/encryption.php

function encryptFile($inputPath, $outputPath, $key) {
    $iv = random_bytes(16); // Initialization vector
    $data = file_get_contents($inputPath);
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
    file_put_contents($outputPath, $iv . $encrypted); // prepend IV
}

function decryptFile($inputPath, $outputPath, $key) {
    $data = file_get_contents($inputPath);
    $iv = substr($data, 0, 16);
    $encryptedData = substr($data, 16);
    $decrypted = openssl_decrypt($encryptedData, 'AES-256-CBC', $key, 0, $iv);
    file_put_contents($outputPath, $decrypted);
}
