<?php
session_start();
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$folderId = (int) $_GET['id'];
$userId = $_SESSION['user_id'];

// Get folder details
$folderStmt = $pdo->prepare("SELECT name FROM folders WHERE id = ? AND user_id = ?");
$folderStmt->execute([$folderId, $userId]);
$folder = $folderStmt->fetch();

if (!$folder) {
    echo "<p class='text-red-600 text-center mt-10'>Folder not found.</p>";
    exit();
}

// Get files in this folder
$fileStmt = $pdo->prepare("SELECT * FROM files WHERE folder_id = ? AND user_id = ? AND is_deleted = 0 ORDER BY uploaded_at DESC");
$fileStmt->execute([$folderId, $userId]);
$files = $fileStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title><?= htmlspecialchars($folder['name']) ?> - CloudStore</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <?php include __DIR__ . '/includes/tailwind.php'; ?>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
</head>
<body class="bg-gray-100 flex min-h-screen">

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="flex-1 ml-0 md:ml-64 p-6">
    <?php include __DIR__ . '/includes/header.php'; ?>

    <h2 class="text-3xl font-bold mb-6 text-center text-gray-800">
        <?= htmlspecialchars($folder['name']) ?>
    </h2>

    <?php if (!$files): ?>
        <p class="text-gray-500 text-center mt-10">No files found in this folder.</p>
    <?php else: ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-6 max-w-7xl mx-auto">
            <?php foreach ($files as $file): ?>
                <div class="file-card relative bg-white rounded-xl shadow-md hover:shadow-lg p-6 flex flex-col items-center group max-w-full">
                    <!-- 3-dots dropdown button -->
                    <div class="relative w-full">
                        <button onclick="toggleDropdown(this)" 
                            class="absolute top-3 right-3 p-1 rounded hover:bg-indigo-100 text-indigo-600 opacity-0 group-hover:opacity-100 transition"
                            aria-haspopup="true" aria-expanded="false" aria-label="Options">
                            <span class="material-icons text-lg">more_vert</span>
                        </button>
                        <div class="dropdown-menu hidden absolute right-0 z-10 mt-8 w-52 rounded-md bg-white shadow-lg border border-gray-200 p-1 space-y-1">
                            <a href="pages/rename.php?file_id=<?= $file['id'] ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Rename</a>
                            <button onclick="toggleProperties(this, <?= $file['id'] ?>)" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Properties
                            </button>
                            <div class="properties-container hidden px-4 pb-2 text-sm text-gray-600"></div>

                            <button 
                                onclick="generateShareLink(<?= $file['id'] ?>)" 
                                class="block w-full text-left px-4 py-2 text-sm text-blue-600 hover:bg-gray-100 hover:text-blue-800 transition duration-150"
                            >
                                🔗 Share
                            </button>
                            <form method="POST" action="/cloudstore/delete.php" onsubmit="return confirm('Are you sure you want to delete this file?')" class="m-0 p-0">
                                <input type="hidden" name="file_id" value="<?= (int)$file['id'] ?>">
                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100 hover:text-red-800 transition duration-150 border-none bg-transparent cursor-pointer">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="text-blue-500 text-5xl mb-2 mt-6">
                        <span class="material-icons">insert_drive_file</span>
                    </div>
                    <p class="font-semibold text-center w-40 truncate" title="<?= htmlspecialchars($file['original_name']) ?>">
                        <?= htmlspecialchars($file['original_name']) ?>
                    </p>
                    <p class="text-sm text-gray-500 mt-1 mb-3">
                        <?= date('M d, Y', strtotime($file['uploaded_at'])) ?>
                    </p>

                    <div class="flex gap-2 w-full justify-center">
                        <a href="/cloudstore/download.php?id=<?= $file['id'] ?>"
                           class="text-white bg-green-500 hover:bg-green-600 px-3 py-1 rounded text-center flex-1">
                            Download
                        </a>
                        <button
                            onclick="previewFile(<?= $file['id'] ?>)"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded flex-1"
                        >
                            Preview
                        </button>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Preview Modal -->
<div id="previewModal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center hidden z-50 p-4">
    <div class="bg-white rounded-lg shadow-lg max-w-4xl w-full max-h-[80vh] overflow-auto relative p-6">
        <button onclick="closePreview()" aria-label="Close preview" class="absolute top-3 right-3 text-gray-600 hover:text-gray-900 text-3xl font-bold">&times;</button>
        <div id="previewContent" class="text-center">
            <p class="text-gray-600">Loading preview...</p>
        </div>
    </div>
</div>

<script>
function toggleDropdown(button) {
    const menu = button.nextElementSibling;
    menu.classList.toggle('hidden');

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
            const tempInput = document.createElement("input");
            tempInput.value = shareUrl;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand("copy");
            document.body.removeChild(tempInput);
            alert("Share link copied to clipboard:\n" + shareUrl);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(() => alert('Network error while generating share link.'));
}

function toggleProperties(button, fileId) {
    const dropdown = button.closest('.dropdown-menu');
    const container = dropdown.querySelector('.properties-container');

    if (!container.classList.contains('hidden')) {
        container.classList.add('hidden');
        button.textContent = 'Properties';
        return;
    }

    container.classList.remove('hidden');
    container.textContent = 'Loading...';
    button.textContent = 'Hide Properties';

    fetch(`/cloudstore/pages/properties.php?file_id=${fileId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const f = data.file;
                container.innerHTML = `
                  <p><strong>Name:</strong> ${f.original_name}</p>
                  <p><strong>Size:</strong> ${formatBytes(f.file_size)}</p>
                  <p><strong>Uploaded:</strong> ${f.uploaded_at}</p>
                  <p><strong>Folder:</strong> ${f.folder || 'Root'}</p>
                  <p><strong>Hash:</strong> ${f.file_hash}</p>
                  <p><strong>Public:</strong> ${f.is_public ? 'Yes' : 'No'}</p>
                `;
            } else {
                container.textContent = 'Could not load properties.';
            }
        })
        .catch(() => {
            container.textContent = 'Error loading file properties.';
        });
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024,
        sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'],
        i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Preview function and modal handlers
function previewFile(fileId) {
    const modal = document.getElementById('previewModal');
    const content = document.getElementById('previewContent');
    modal.classList.remove('hidden');
    content.innerHTML = '<p class="text-gray-600">Loading preview...</p>';

    fetch(`/cloudstore/preview.php?id=${fileId}`)
        .then(res => res.text())
        .then(html => {
            content.innerHTML = html;
        })
        .catch(() => {
            content.innerHTML = '<p class="text-red-600">Failed to load preview.</p>';
        });
}

function closePreview() {
    const modal = document.getElementById('previewModal');
    modal.classList.add('hidden');
}
</script>

</body>
</html>
