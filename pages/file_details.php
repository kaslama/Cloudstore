<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$file_id = $_GET['id'] ?? null;

if (!$file_id) {
    die("Invalid file ID.");
}

// Fetch file details
$stmt = $pdo->prepare("SELECT * FROM files WHERE id = ? AND user_id = ? AND is_deleted = 0");
$stmt->execute([$file_id, $user_id]);
$file = $stmt->fetch();

if (!$file) {
    die("File not found or access denied.");
}

// Handle rename form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_name'])) {
    $newName = trim($_POST['new_name']);
    if ($newName === '') {
        $error = "File name cannot be empty.";
    } else {
        // Sanitize filename (basic)
        $newNameSafe = preg_replace("/[^a-zA-Z0-9\.\-_ ]/", "_", $newName);

        // Optional: rename the physical file on disk (if you want)
        $oldPath = __DIR__ . '/../uploads/' . $file['file_path'];
        $newPathDir = dirname($oldPath);
        $extension = pathinfo($file['file_path'], PATHINFO_EXTENSION);
        $newFileName = pathinfo($newNameSafe, PATHINFO_FILENAME) . '.' . $extension;
        $newFullPath = $newPathDir . '/' . $newFileName;

        if (file_exists($oldPath)) {
            if (!rename($oldPath, $newFullPath)) {
                $error = "Failed to rename the physical file.";
            } else {
                // Update database record with new name and new path
                $relativeNewPath = str_replace(__DIR__ . '/../uploads/', '', $newFullPath);
                $stmt = $pdo->prepare("UPDATE files SET original_name = ?, file_path = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$newNameSafe, $relativeNewPath, $file_id, $user_id]);

                // Refresh page to show updated info
                header("Location: file_details.php?id=$file_id&msg=renamed");
                exit();
            }
        } else {
            $error = "Physical file does not exist.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>File Details - <?= htmlspecialchars($file['original_name']) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">

<div class="max-w-xl mx-auto bg-white p-6 rounded shadow">

<h1 class="text-2xl font-bold mb-4">File Details</h1>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'renamed'): ?>
    <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">✅ File renamed successfully!</div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="mb-4 p-3 bg-red-100 text-red-700 rounded"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<table class="mb-6 w-full text-left">
    <tr>
        <th class="py-2">Name:</th>
        <td><?= htmlspecialchars($file['original_name']) ?></td>
    </tr>
    <tr>
        <th class="py-2">Size:</th>
        <td><?= number_format($file['file_size'] / 1024, 2) ?> KB</td>
    </tr>
    <tr>
        <th class="py-2">Uploaded At:</th>
        <td><?= date('M d, Y H:i', strtotime($file['uploaded_at'])) ?></td>
    </tr>
    <tr>
        <th class="py-2">File Path:</th>
        <td><?= htmlspecialchars($file['file_path']) ?></td>
    </tr>
</table>

<form method="POST" class="mb-4">
    <label class="block mb-2 font-semibold" for="new_name">Rename File</label>
    <input
        type="text"
        name="new_name"
        id="new_name"
        value="<?= htmlspecialchars($file['original_name']) ?>"
        class="border p-2 rounded w-full mb-4"
        required
    />
    <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">Rename</button>
</form>

<a href="my_drive.php" class="text-blue-600 hover:underline">← Back to My Drive</a>
</div>

</body>
</html>
