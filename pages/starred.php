<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}
// Fetch files starred by the logged-in user
$stmt = $pdo->prepare("SELECT * FROM files WHERE user_id = ? AND is_starred = 1 AND is_deleted = 0 ORDER BY uploaded_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$starredFiles = $stmt->fetchAll();

$baseURL = '/cloudstore'; // Change to your base URL if different
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Starred Files - CloudStore</title>
    <?php include __DIR__ . '/../includes/tailwind.php'; ?>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="bg-gray-100 flex">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="flex-1 min-h-screen ml-0 md:ml-64">
        <?php include __DIR__ . '/../includes/header.php'; ?>

        <main class="p-6">
            <h2 class="text-xl font-semibold mb-4">Starred Files</h2>

            <?php if (!$starredFiles): ?>
                <p class="text-gray-600">You have no starred files.</p>
            <?php else: ?>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <?php foreach ($starredFiles as $file): ?>
                        <div class="bg-white p-4 rounded shadow hover:shadow-lg transition cursor-pointer">
                            <div class="flex justify-center mb-3">
                                <span class="material-icons text-6xl text-yellow-400">star</span>
                            </div>
                            <p class="truncate font-medium" title="<?= htmlspecialchars($file['original_name'] ?? 'Unnamed file') ?>">
                                <?= htmlspecialchars($file['original_name'] ?? 'Unnamed file') ?>
                            </p>
                            <p class="text-sm text-gray-500">
                                <?= isset($file['uploaded_at']) ? date('M d, Y', strtotime($file['uploaded_at'])) : 'Unknown' ?>
                            </p>
                            <a href="<?= $baseURL ?>/download.php?id=<?= htmlspecialchars($file['id'] ?? 0) ?>" class="text-yellow-600 hover:underline mt-2 block text-center">Download</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

</body>
</html>
