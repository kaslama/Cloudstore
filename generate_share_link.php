<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['file_id']) || !is_numeric($data['file_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid file ID']);
    exit;
}

$fileId = (int) $data['file_id'];
$userId = $_SESSION['user_id'];

// Check if file exists and belongs to user
$stmt = $pdo->prepare("SELECT * FROM files WHERE id = ? AND user_id = ?");
$stmt->execute([$fileId, $userId]);
$file = $stmt->fetch();

if (!$file) {
    echo json_encode(['success' => false, 'message' => 'File not found']);
    exit;
}

// If token already exists
if (!empty($file['share_token'])) {
    echo json_encode(['success' => true, 'token' => $file['share_token']]);
    exit;
}

// Generate new token
$token = bin2hex(random_bytes(16)); // 32-char token

// Save token to DB
$updateStmt = $pdo->prepare("UPDATE files SET share_token = ?, is_public = 1 WHERE id = ?");
$updateStmt->execute([$token, $fileId]);

echo json_encode(['success' => true, 'token' => $token]);
