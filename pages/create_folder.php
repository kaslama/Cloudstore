<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $folderName = trim($_POST['folder_name']);

    if (!empty($folderName)) {
        $stmt = $pdo->prepare("INSERT INTO folders (name, user_id) VALUES (?, ?)");
        $stmt->execute([$folderName, $_SESSION['user_id']]);
        header("Location: my_drive.php"); // Redirect back
        exit();
    }
}
?>
<form method="POST" class="p-4 bg-white shadow-md rounded w-full max-w-sm">
    <label class="block mb-2 font-semibold">Folder Name:</label>
    <input type="text" name="folder_name" required class="w-full px-3 py-2 border rounded mb-4" />
    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Create Folder</button>
</form>
