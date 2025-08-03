<?php
require_once __DIR__ . '/config/db.php'; // Correct for root-level location

// Get token from URL
if (!isset($_GET['token']) || empty($_GET['token'])) {
    die('Invalid or missing token.');
}

$token = $_GET['token'];

// Fetch file info
$stmt = $pdo->prepare("SELECT * FROM files WHERE share_token = ? LIMIT 1");
$stmt->execute([$token]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    die('Invalid or expired sharing link.');
}

$file_path = __DIR__ . '/uploads/' . $file['file_path'];
$file_exists = file_exists($file_path);
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Shared File - <?= htmlspecialchars($file['original_name']) ?></title>
    <?php include __DIR__ . '/includes/tailwind.php'; ?>
    <script defer>
        // Copy share link to clipboard with feedback
        function copyLink() {
            const link = window.location.href;
            navigator.clipboard.writeText(link).then(() => {
                const btn = document.getElementById('copyBtn');
                btn.textContent = 'Copied!';
                setTimeout(() => btn.textContent = 'Copy Link', 2000);
            });
        }
    </script>
</head>
<body class="bg-gradient-to-r from-blue-100 to-indigo-200 min-h-screen flex items-center justify-center px-4">
    <main class="bg-white shadow-lg rounded-lg max-w-md w-full p-8 text-center">
        <span class="material-icons text-8xl text-indigo-600 mb-4 select-none">insert_drive_file</span>
        <h1 class="text-3xl font-extrabold text-gray-900 mb-2 truncate" title="<?= htmlspecialchars($file['original_name']) ?>">
            <?= htmlspecialchars($file['original_name']) ?>
        </h1>
        <p class="text-gray-600 mb-6">Uploaded on <?= date('M d, Y', strtotime($file['uploaded_at'])) ?></p>

        <?php if ($file_exists): ?>
            <a href="download.php?id=<?= $file['id'] ?>"
               class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-8 rounded-lg shadow-md transition duration-300"
               download>
                Download File
            </a>

            <button id="copyBtn"
                onclick="copyLink()"
                class="ml-4 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-3 px-6 rounded-lg shadow-md transition duration-300">
                Copy Link
            </button>
        <?php else: ?>
            <p class="text-red-600 font-semibold text-lg mt-6">⚠️ File not found on server.</p>
        <?php endif; ?>
    </main>
</body>
</html>
