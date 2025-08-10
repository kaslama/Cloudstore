 <?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$messages = [];
if (isset($_SESSION['message'])) {
    $messages[] = "✅ " . $_SESSION['message'];
    unset($_SESSION['message']);
}
if (isset($_SESSION['error'])) {
    $messages[] = "❌ " . $_SESSION['error'];
    unset($_SESSION['error']);
}
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Show folder deleted message if redirected after deletion
if (isset($_GET['msg']) && $_GET['msg'] === 'folder_deleted') {
    $messages[] = "✅ Folder deleted successfully.";
}

// ===== FOLDER CREATION =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_folder'])) {
    $folderName = trim($_POST['folder_name']);
    if ($folderName !== '') {
        $safeFolderName = preg_replace("/[^a-zA-Z0-9_\- ]/", "_", $folderName);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM folders WHERE user_id = ? AND name = ?");
        $stmt->execute([$user_id, $safeFolderName]);
        $count = $stmt->fetchColumn();

        if ($count == 0) {
            $stmt = $pdo->prepare("INSERT INTO folders (user_id, name, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$user_id, $safeFolderName]);
            $messages[] = "✅ Folder '$safeFolderName' created successfully!";
        } else {
            $messages[] = "❌ Folder with this name already exists.";
        }
    } else {
        $messages[] = "❌ Folder name cannot be empty.";
    }
}

// ===== FILE UPLOAD =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $originalName = $_FILES['file']['name'];
    $tmpName = $_FILES['file']['tmp_name'];
    $fileSize = $_FILES['file']['size'];
    $folder_id = !empty($_POST['folder_id']) ? intval($_POST['folder_id']) : null;

    $fileHash = md5_file($tmpName);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM files WHERE user_id = ? AND file_hash = ? AND is_deleted = 0");
    $stmt->execute([$user_id, $fileHash]);
    $duplicateCount = $stmt->fetchColumn();

    if ($duplicateCount > 0) {
        $messages[] = "⚠️ Duplicate file detected. This file already exists.";
    } else {
        $safeName = preg_replace("/[^a-zA-Z0-9\.\-_]/", "_", $originalName);
        $uploadDir = __DIR__ . '/../uploads/user_' . $user_id . '/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $baseName = pathinfo($safeName, PATHINFO_FILENAME);
        $extension = pathinfo($safeName, PATHINFO_EXTENSION);

        $version = 0;
        do {
            $newFileName = time() . '_' . $baseName . ($version > 0 ? "_v$version" : '') . '.' . $extension;
            $destPath = $uploadDir . $newFileName;

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM files WHERE user_id = ? AND file_path = ?");
            $stmt->execute([$user_id, "user_$user_id/$newFileName"]);
            $count = $stmt->fetchColumn();

            $version++;
        } while ($count > 0);

        if (move_uploaded_file($tmpName, $destPath)) {
            $relativePath = "user_$user_id/$newFileName";

            $stmt = $pdo->prepare("INSERT INTO files (user_id, original_name, file_path, file_size, uploaded_at, version, is_deleted, folder_id, file_hash) VALUES (?, ?, ?, ?, NOW(), ?, 0, ?, ?)");
            $stmt->execute([$user_id, $originalName, $relativePath, $fileSize, 1, $folder_id, $fileHash]);

            $messages[] = "✅ File uploaded successfully!";
        } else {
            $messages[] = "❌ Failed to upload file.";
        }
    }
}

// Fetch folders
$stmt = $pdo->prepare("SELECT * FROM folders WHERE user_id = ?");
$stmt->execute([$user_id]);
$folders = $stmt->fetchAll();

// Fetch root files
$stmt = $pdo->prepare("SELECT * FROM files WHERE user_id = ? AND (folder_id IS NULL OR folder_id = 0) AND is_deleted = 0 ORDER BY uploaded_at DESC");
$stmt->execute([$user_id]);
$files = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>My Drive - CloudStore</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
</head>
<body class="bg-gradient-to-br from-indigo-50 via-white to-indigo-100 p-6 min-h-screen">

<?php foreach ($messages as $message): ?>
    <div class="mb-4 p-3 rounded text-center max-w-3xl mx-auto <?= str_contains($message, '✅') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endforeach; ?>

<!-- Upload File Toggle -->
<div class="max-w-lg mx-auto mb-6">
  <button id="uploadToggleBtn" 
    class="w-full bg-indigo-600 text-white rounded-lg px-6 py-3 font-semibold hover:bg-indigo-700 transition shadow-md hover:shadow-lg focus:outline-none">
    Upload File
  </button>
  
  <form id="uploadForm" method="POST" enctype="multipart/form-data" class="hidden mt-4 bg-white p-6 rounded-lg shadow-lg flex flex-wrap gap-4 items-center justify-center">
      <input type="file" name="file" required
        class="flex-grow border border-gray-300 rounded-lg px-4 py-2 transition focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
      <select name="folder_id" class="border border-gray-300 rounded-lg px-4 py-2 text-gray-700 transition focus:ring-2 focus:ring-indigo-500 focus:outline-none">
          <option value="">No Folder (Root)</option>
          <?php foreach ($folders as $folder): ?>
              <option value="<?= htmlspecialchars($folder['id']) ?>"><?= htmlspecialchars($folder['name']) ?></option>
          <?php endforeach; ?>
      </select>
      <button type="submit" class="bg-indigo-600 text-white rounded-lg px-6 py-3 font-semibold hover:bg-indigo-700 transition shadow-md hover:shadow-lg">
          Upload
      </button>
  </form>
</div>

<!-- Create Folder Toggle -->
<div class="max-w-lg mx-auto mb-14">
  <button id="folderToggleBtn" 
    class="w-full bg-green-600 text-white rounded-lg px-6 py-3 font-semibold hover:bg-green-700 transition shadow-md hover:shadow-lg focus:outline-none">
    Create Folder
  </button>

  <form id="folderForm" method="POST" class="hidden mt-4 bg-white p-6 rounded-lg shadow-lg flex gap-4 items-center">
      <input type="text" name="folder_name" placeholder="New folder name" required
          class="flex-grow border border-gray-300 rounded-lg px-4 py-2 text-gray-700 transition focus:ring-2 focus:ring-green-500 focus:outline-none" />
      <button type="submit" name="create_folder" class="bg-green-600 text-white rounded-lg px-6 py-3 font-semibold hover:bg-green-700 transition shadow-md hover:shadow-lg">
          Create Folder
      </button>
  </form>
</div>

<section class="max-w-7xl mx-auto">

<h2 class="text-3xl font-extrabold mb-6 text-center text-indigo-900">My Drive</h2>

<h3 class="text-2xl font-bold mb-6 text-indigo-800">Folders</h3>
<div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-10">
    <?php foreach ($folders as $folder): ?>
        <a href="folder_view.php?id=<?= $folder['id'] ?>" 
           class="bg-yellow-100 p-6 rounded-xl shadow-md hover:shadow-xl flex flex-col items-center transition transform hover:scale-[1.05]">
            <span class="material-icons text-yellow-600 text-6xl mb-4 select-none">folder</span>
            <span class="font-semibold text-center text-yellow-900 truncate max-w-full"><?= htmlspecialchars($folder['name']) ?></span>
        </a>
    <?php endforeach; ?>
</div>

<?php if (!$files): ?>
    <p class="text-gray-600 text-center max-w-7xl mx-auto">No files found in root. Upload some or add folders!</p>
<?php else: ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-8 max-w-7xl mx-auto">
        <?php foreach ($files as $file): ?>
            <div class="file-card bg-white p-6 rounded-xl shadow-md hover:shadow-xl transition flex flex-col items-center relative group max-w-full" data-file-id="<?= htmlspecialchars($file['id']) ?>">
                <span class="material-icons text-7xl text-indigo-500 mb-4 select-none">insert_drive_file</span>
                <p class="font-medium text-center truncate max-w-full"><?= htmlspecialchars($file['original_name'] ?? 'Unnamed file') ?></p>
                <p class="text-sm text-gray-500 mb-3">
                    <?= isset($file['uploaded_at']) ? date('M d, Y', strtotime($file['uploaded_at'])) : 'Unknown' ?>
                </p>
                <a href="download.php?id=<?= htmlspecialchars($file['id']) ?>" class="text-indigo-600 hover:underline mb-3">Download</a>

                <!-- New Preview Button -->
                <button onclick="previewFile(<?= $file['id'] ?>)"
                  class="mb-3 bg-indigo-500 text-white px-4 py-2 rounded hover:bg-indigo-600 transition">
                  Preview
                </button>

                <!-- Dropdown Menu Trigger -->
                <button onclick="toggleDropdown(this)" aria-haspopup="true" aria-expanded="false" aria-label="Options"
                    class="p-2 rounded hover:bg-indigo-100 absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition z-30">
                    <span class="material-icons text-indigo-600">more_vert</span>
                </button>

                <!-- Dropdown Menu -->
                <div
                    class="hidden absolute right-0 mt-10 w-44 rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 z-40">
                    <div class="py-1">
                        <a href="pages/rename.php?file_id=<?= $file['id'] ?>"
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-100 hover:text-indigo-900 transition cursor-pointer">
                            Rename
                        </a>
                        <button
                            type="button"
                            onclick="toggleProperties(this, <?= $file['id'] ?>)"
                            class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-indigo-100 hover:text-indigo-900 transition bg-transparent border-none cursor-pointer"
                        >
                            Properties
                        </button>
                        <div class="properties-container hidden bg-indigo-50 p-3 text-xs text-indigo-900 rounded-b max-h-48 overflow-auto"></div>
                        <button
                            onclick="generateShareLink(<?= $file['id'] ?>)"
                            class="w-full text-left px-4 py-2 text-sm text-indigo-600 hover:bg-indigo-100 hover:text-indigo-900 transition cursor-pointer"
                        >
                            🔗 Share
                        </button>
                    </div>
                    <div class="py-1">
                        <form method="POST" action="/cloudstore/delete.php" onsubmit="return confirm('Are you sure you want to delete this file?')" class="m-0 p-0">
                            <input type="hidden" name="file_id" value="<?= (int)$file['id'] ?>">
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-100 hover:text-red-800 transition cursor-pointer bg-transparent border-none">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

</section>

<!-- PREVIEW MODAL -->
<div id="previewModal" class="fixed inset-0 z-50 bg-black bg-opacity-50 hidden items-center justify-center">
  <div class="bg-white w-[90%] max-w-3xl p-4 rounded-lg relative shadow-lg max-h-[90%] overflow-auto">
    <button onclick="closePreview()" class="absolute top-2 right-2 text-gray-500 hover:text-black text-xl">&times;</button>
    <div id="previewContent" class="text-center">
      <p class="text-gray-500">Loading preview...</p>
    </div>
  </div>
</div>

<script>
function toggleDropdown(button) {
    const menu = button.nextElementSibling;
    menu.classList.toggle('hidden');

    // Close dropdown when clicking outside
    document.addEventListener('click', function handler(event) {
        if (!button.contains(event.target) && !menu.contains(event.target)) {
            menu.classList.add('hidden');
            document.removeEventListener('click', handler);
        }
    });
}

function generateShareLink(fileId) {
    fetch('/cloudstore/generate_share_link.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({file_id: fileId})
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const shareUrl = window.location.origin + '/cloudstore/shared.php?token=' + data.token;

            // Copy to clipboard
            navigator.clipboard.writeText(shareUrl).then(() => {
                alert("Share link copied to clipboard:\n" + shareUrl);
            }, () => {
                alert("Failed to copy share link to clipboard. Here's the link:\n" + shareUrl);
            });
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(() => alert('Network error while generating share link.'));
}

function toggleProperties(button, fileId) {
    const dropdown = button.parentElement;
    const container = dropdown.querySelector('.properties-container');

    if (!container.classList.contains('hidden')) {
        container.classList.add('hidden');
        button.textContent = 'Properties';
        return;
    }

    container.classList.remove('hidden');
    container.textContent = 'Loading properties...';
    button.textContent = 'Hide Properties';

    fetch(`/cloudstore/pages/properties.php?file_id=${fileId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const file = data.file;
                container.innerHTML = `
                  <p><strong>Name:</strong> ${file.original_name}</p>
                  <p><strong>Size:</strong> ${formatBytes(file.file_size)}</p>
                  <p><strong>Uploaded:</strong> ${file.uploaded_at}</p>
                  <p><strong>Folder:</strong> ${file.folder || 'Root'}</p>
                  <p><strong>File Hash:</strong> ${file.file_hash}</p>
                  <p><strong>Public:</strong> ${file.is_public ? 'Yes' : 'No'}</p>
                `;
            } else {
                container.textContent = 'Failed to load properties.';
            }
        })
        .catch(() => {
            container.textContent = 'Network error while fetching properties.';
        });
}

function formatBytes(bytes) {
  if (bytes === 0) return '0 Bytes';
  const k = 1024,
        sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'],
        i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Toggle Upload Form
document.getElementById('uploadToggleBtn').addEventListener('click', () => {
  const uploadForm = document.getElementById('uploadForm');
  uploadForm.classList.toggle('hidden');
  if (!uploadForm.classList.contains('hidden')) {
    uploadForm.querySelector('input[type="file"]').focus();
  }
});

// Toggle Folder Creation Form
document.getElementById('folderToggleBtn').addEventListener('click', () => {
  const folderForm = document.getElementById('folderForm');
  folderForm.classList.toggle('hidden');
  if (!folderForm.classList.contains('hidden')) {
    folderForm.querySelector('input[name="folder_name"]').focus();
  }
});

// File Preview Modal Functions
function previewFile(fileId) {
  document.getElementById('previewModal').classList.remove('hidden');
  const previewContent = document.getElementById('previewContent');
  previewContent.innerHTML = '<p class="text-gray-500">Loading preview...</p>';

  fetch(`preview.php?id=${fileId}`)
    .then(res => res.text())
    .then(html => {
      previewContent.innerHTML = html;
    })
    .catch(() => {
      previewContent.innerHTML = '<p class="text-red-500">Preview failed.</p>';
    });
}

function closePreview() {
  document.getElementById('previewModal').classList.add('hidden');
}
</script>

</body>
</html>
