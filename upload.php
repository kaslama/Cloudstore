<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

$message = "";

// Handle folder creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_folder']) && !empty(trim($_POST['folder_name']))) {
    $folderName = trim($_POST['folder_name']);
    $uploadDir = __DIR__ . "/uploads/$folderName";

    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
        $message = "✅ Folder '$folderName' created successfully.";
    } else {
        $message = "⚠️ Folder already exists.";
    }
}

// Handle file upload with hash check
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $user_id = $_SESSION['user_id'];
    $folder = isset($_POST['target_folder']) ? trim($_POST['target_folder']) : '';
    $uploadDir = __DIR__ . '/uploads/' . $folder;

    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $file = $_FILES['file'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $originalName = basename($file['name']);
        $tempPath = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileHash = md5_file($tempPath); // ← Generate file hash

        // Check if file with same hash already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM files WHERE file_hash = ?");
        $stmt->execute([$fileHash]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            $message = "⚠️ Duplicate file. A file with the same content already exists.";
        } else {
            $newFileName = uniqid('file_') . '_' . $originalName;
            $destPath = $uploadDir . '/' . $newFileName;

            if (move_uploaded_file($tempPath, $destPath)) {
                // Insert file data with hash
                $stmt = $pdo->prepare("INSERT INTO files (user_id, original_name, file_path, file_size, uploaded_at, folder, file_hash) VALUES (?, ?, ?, ?, NOW(), ?, ?)");
                $stmt->execute([$user_id, $originalName, "$folder/$newFileName", $fileSize, $folder, $fileHash]);

                $_SESSION['message'] = "✅ File uploaded to '$folder'!";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $message = "❌ Failed to move uploaded file.";
            }
        }
    } else {
        $message = "❌ Upload error: " . $file['error'];
    }
}

// Show session message
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// List folders in /uploads
$uploadBase = __DIR__ . '/uploads';
$folders = array_filter(glob($uploadBase . '/*'), 'is_dir');
$folderNames = array_map('basename', $folders);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Upload File to Folder</title>
</head>
<body>
    <h2>📂 Upload File to Folder</h2>

    <?php if ($message): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="uploadForm">
        <label>Select Folder:</label>
        <select name="target_folder" required>
            <option value="" disabled selected>-- Choose a folder --</option>
            <?php foreach ($folderNames as $fname): ?>
                <option value="<?= htmlspecialchars($fname) ?>"><?= htmlspecialchars($fname) ?></option>
            <?php endforeach; ?>
        </select><br><br>

        <input type="file" name="file" required />
        <button type="submit" id="uploadBtn">Upload</button>
    </form>

    <h3>📁 Create New Folder</h3>
    <form method="POST">
        <input type="text" name="folder_name" placeholder="New folder name" required />
        <button type="submit" name="create_folder">Create Folder</button>
    </form>

    <br><a href="dashboard.php">🔙 Go to Dashboard</a>

    <script>
        document.getElementById('uploadForm').addEventListener('submit', function () {
            document.getElementById('uploadBtn').disabled = true;
        });
    </script>
</body>
</html>
