<?php
require_once 'config/db.php';

// Select all public files with NULL share_token
$stmt = $pdo->query("SELECT id FROM files WHERE is_public = 1 AND (share_token IS NULL OR share_token = '')");
$files = $stmt->fetchAll();

foreach ($files as $file) {
    $token = bin2hex(random_bytes(16));
    $update = $pdo->prepare("UPDATE files SET share_token = ? WHERE id = ?");
    $update->execute([$token, $file['id']]);
    echo "Generated token for file ID " . $file['id'] . ": " . $token . "<br>";
}
