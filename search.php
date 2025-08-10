<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/auth.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$query = trim($_GET['q'] ?? '');

$folders = [];
$files = [];

if ($query !== '') {
    $likeQuery = '%' . $query . '%';

    // Search folders by name
    $stmt = $pdo->prepare("SELECT id, name FROM folders WHERE user_id = ? AND name LIKE ? ORDER BY created_at DESC");
    $stmt->execute([$user_id, $likeQuery]);
    $folders = $stmt->fetchAll();

    // Search files by original_name with folder info
    $stmt = $pdo->prepare("SELECT f.id, f.original_name, f.folder_id, f.uploaded_at, fo.name AS folder_name 
                           FROM files f
                           LEFT JOIN folders fo ON f.folder_id = fo.id
                           WHERE f.user_id = ? AND f.is_deleted = 0 AND f.original_name LIKE ?
                           ORDER BY f.uploaded_at DESC");
    $stmt->execute([$user_id, $likeQuery]);
    $files = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Search results for <?= htmlspecialchars($query) ?> - CloudStore</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
</head>
<body class="bg-indigo-50 p-6 min-h-screen">

<div class="max-w-5xl mx-auto bg-white p-6 rounded shadow-lg">

  <h1 class="text-2xl font-bold mb-4">Search results for: <span class="italic"><?= htmlspecialchars($query) ?></span></h1>

  <?php if (empty($folders) && empty($files)): ?>
    <p class="text-gray-600">No results found.</p>
  <?php else: ?>
    
    <?php if (!empty($folders)): ?>
      <section class="mb-8">
        <h2 class="text-xl font-semibold mb-2">Folders</h2>
        <ul class="list-disc list-inside text-indigo-800">
          <?php foreach ($folders as $folder): ?>
            <li>
              <a href="folder_view.php?id=<?= htmlspecialchars($folder['id']) ?>" class="hover:underline font-medium">
                <?= htmlspecialchars($folder['name']) ?>
              </a>
              <span class="text-gray-500 ml-2">(Folder)</span>
            </li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endif; ?>

    <?php if (!empty($files)): ?>
      <section>
        <h2 class="text-xl font-semibold mb-2">Files</h2>
        <ul class="space-y-3">
          <?php foreach ($files as $file): ?>
            <li class="p-3 bg-indigo-100 rounded flex justify-between items-center">
              <div>
                <span class="material-icons align-middle text-indigo-600 mr-2">insert_drive_file</span>
                <a href="download.php?id=<?= htmlspecialchars($file['id']) ?>" class="font-medium hover:underline">
                  <?= htmlspecialchars($file['original_name']) ?>
                </a>
                <div class="text-sm text-gray-600">
                  Location: 
                  <strong>
                    <?= $file['folder_name'] ? htmlspecialchars($file['folder_name']) : '<em>Root</em>' ?>
                  </strong>
                </div>
              </div>
              <div class="text-sm text-gray-500">
                <?= date('M d, Y', strtotime($file['uploaded_at'])) ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endif; ?>

  <?php endif; ?>

  <div class="mt-6">
    <a href="mydrive.php" class="text-indigo-600 hover:underline">← Back to My Drive</a>
  </div>

</div>

</body>
</html>
