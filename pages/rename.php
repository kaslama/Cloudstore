<?php
session_start();
require_once __DIR__ . '/../config/db.php';  // Fixed path

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

if (!isset($_GET['file_id']) || !is_numeric($_GET['file_id'])) {
    die('Invalid file ID.');
}

$file_id = (int)$_GET['file_id'];
$user_id = $_SESSION['user_id'];

// Fetch existing file info
$stmt = $pdo->prepare("SELECT original_name, file_path FROM files WHERE id = ? AND user_id = ? AND is_deleted = 0");
$stmt->execute([$file_id, $user_id]);
$file = $stmt->fetch();

if (!$file) {
    die('File not found or access denied.');
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_name'])) {
    $newName = trim($_POST['new_name']);
    if ($newName === '') {
        $error = 'New name cannot be empty.';
    } else {
        // Get file extension to preserve it
        $extension = pathinfo($file['original_name'], PATHINFO_EXTENSION);
        
        // Sanitize new name, remove extension if user added it
        $newName = pathinfo($newName, PATHINFO_FILENAME);

        // Build new filename with extension
        $newOriginalName = $newName . ($extension ? '.' . $extension : '');

        // Prepare new file path (keep folder path)
        $folderPath = dirname($file['file_path']);
        $oldFullPath = __DIR__ . '/../uploads/' . $file['file_path'];
        $newFileName = uniqid('file_') . '_' . $newOriginalName;
        $newFullPath = __DIR__ . '/../uploads/' . $folderPath . '/' . $newFileName;

        // Rename file on disk
        if (rename($oldFullPath, $newFullPath)) {
            // Update database record with new original name and file path
            $stmt = $pdo->prepare("UPDATE files SET original_name = ?, file_path = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$newOriginalName, $folderPath . '/' . $newFileName, $file_id, $user_id]);

            $_SESSION['message'] = '✅ File renamed successfully.';
            header('Location: ../dashboard.php');
            exit();
        } else {
            $error = 'Failed to rename the file on server.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="bg-gray-50">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Rename File</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-md w-full">
        <h2 class="text-2xl font-semibold mb-6 text-center text-gray-800">Rename File</h2>

        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 px-4 py-3 rounded mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" id="renameForm" class="space-y-4">
            <label for="new_name" class="block text-gray-700 font-medium">New File Name (without extension)</label>
            <input 
                type="text" 
                id="new_name" 
                name="new_name" 
                value="<?= htmlspecialchars(pathinfo($file['original_name'], PATHINFO_FILENAME)) ?>" 
                required
                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500"
                maxlength="255"
            />
            
            <button 
                type="submit" 
                class="w-full bg-indigo-600 text-white py-2 rounded hover:bg-indigo-700 transition"
                id="submitBtn"
            >Rename</button>
        </form>

        <button
            onclick="window.history.back()"
            class="mt-4 w-full text-indigo-600 hover:text-indigo-800 font-medium underline"
            type="button"
        >Cancel</button>
    </div>

    <script>
        const form = document.getElementById('renameForm');
        const submitBtn = document.getElementById('submitBtn');
        form.addEventListener('submit', () => {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Renaming...';
        });
    </script>
</body>
</html>
