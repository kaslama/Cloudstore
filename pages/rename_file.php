<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$file_id = $data['file_id'] ?? null;
$new_name = trim($data['new_name'] ?? '');

if (!$file_id || $new_name === '') {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Get existing file
$stmt = $pdo->prepare("SELECT * FROM files WHERE id = ? AND user_id = ? AND is_deleted = 0");
$stmt->execute([$file_id, $user_id]);
$file = $stmt->fetch();

if (!$file) {
    echo json_encode(['success' => false, 'error' => 'File not found']);
    exit;
}

$safeName = preg_replace("/[^a-zA-Z0-9\.\-_ ]/", "_", $new_name);

$oldPath = __DIR__ . '/../uploads/' . $file['file_path'];
$extension = pathinfo($file['file_path'], PATHINFO_EXTENSION);
$newFileName = pathinfo($safeName, PATHINFO_FILENAME) . '.' . $extension;
$newFullPath = dirname($oldPath) . '/' . $newFileName;

if (!file_exists($oldPath)) {
    echo json_encode(['success' => false, 'error' => 'Physical file does not exist']);
    exit;
}

if (file_exists($newFullPath)) {
    echo json_encode(['success' => false, 'error' => 'A file with that name already exists']);
    exit;
}

if (!rename($oldPath, $newFullPath)) {
    echo json_encode(['success' => false, 'error' => 'Failed to rename physical file']);
    exit;
}

$relativeNewPath = str_replace(__DIR__ . '/../uploads/', '', $newFullPath);

$stmt = $pdo->prepare("UPDATE files SET original_name = ?, file_path = ? WHERE id = ? AND user_id = ?");
$stmt->execute([$safeName, $relativeNewPath, $file_id, $user_id]);

echo json_encode(['success' => true]);
exit;
