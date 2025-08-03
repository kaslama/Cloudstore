<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM files WHERE user_id = ? AND is_deleted = 1 ORDER BY uploaded_at DESC");
$stmt->execute([$user_id]);
$files = $stmt->fetchAll();

$baseURL = '/cloudstore'; // Update according to your project
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Trash - CloudStore</title>
    <?php include __DIR__ . '/../includes/tailwind.php'; ?>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="bg-gray-100 flex">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="flex-1 min-h-screen ml-0 md:ml-64">
        <?php include __DIR__ . '/../includes/header.php'; ?>

        <main class="p-6">
            <h2 class="text-xl font-semibold mb-4">Trash</h2>
            <div class="flex space-x-4 mb-4">
    <form method="post" action="../restore_all.php" onsubmit="return confirm('Are you sure you want to restore all files?');">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Restore All
        </button>
    </form>
    <form method="post" action="../delete_all_permanent.php" onsubmit="return confirm('Permanently delete all files in trash? This cannot be undone.');">
        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
            Delete All
        </button>
    </form>
</div>


            <?php if (count($files) === 0): ?>
                <p class="text-gray-600">Trash is empty.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto border-collapse border border-gray-300">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="border border-gray-300 px-4 py-2 text-left">File Name</th>
                                <th class="border border-gray-300 px-4 py-2 text-left">Uploaded At</th>
                                <th class="border border-gray-300 px-4 py-2 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($files as $file): ?>
                                <tr class="hover:bg-gray-100">
                                    <td class="border border-gray-300 px-4 py-2"><?= htmlspecialchars($file['original_name']) ?></td>
                                    <td class="border border-gray-300 px-4 py-2"><?= date('M d, Y', strtotime($file['uploaded_at'])) ?></td>
                                    <td class="border border-gray-300 px-4 py-2 text-center space-x-2">
                                        <a href="<?= $baseURL ?>/restore.php?file_id=<?= $file['id'] ?>" 
                                           class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded" 
                                           onclick="return confirm('Restore this file?');">
                                           Restore
                                        </a>
                                        <a href="<?= $baseURL ?>/delete_permanent.php?file_id=<?= $file['id'] ?>" 
                                           class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded" 
                                           onclick="return confirm('Permanently delete this file? This action cannot be undone.');">
                                           Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
