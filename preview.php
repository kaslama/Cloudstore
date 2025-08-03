<?php
session_start();
require_once __DIR__ . '/config/db.php';

// Escape helper
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo "Unauthorized";
    exit;
}

$user_id = $_SESSION['user_id'];
$file_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($file_id <= 0) {
    echo "Invalid file ID.";
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM files WHERE id = ? AND user_id = ? AND is_deleted = 0");
$stmt->execute([$file_id, $user_id]);
$file = $stmt->fetch();

if (!$file) {
    echo "File not found or access denied.";
    exit;
}

$filepath = $_SERVER['DOCUMENT_ROOT'] . '/cloudstore/uploads/' . $file['file_path'];

if (!file_exists($filepath)) {
    echo "File missing on server.";
    exit;
}

// Detect mime type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $filepath);
finfo_close($finfo);
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <title>Preview - <?= e($file['original_name']) ?> | CloudStore</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-indigo-50 min-h-screen flex flex-col">

<header class="max-w-4xl mx-auto w-full mb-6 flex justify-between items-center border-b border-gray-300 pb-3 px-4 md:px-0 mt-4">
  <a href="/cloudstore/dashboard.php" class="text-indigo-600 hover:text-indigo-800 flex items-center gap-2 font-medium">
    <!-- Back Arrow SVG Icon -->
    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
    </svg>
    Back
  </a>
  
  <h1 class="text-xl font-semibold text-indigo-900 truncate max-w-[60%] text-center" title="<?= e($file['original_name']) ?>">
    <?= e($file['original_name']) ?>
  </h1>
  
  <a href="/cloudstore/download.php?id=<?= e($file['id']) ?>" 
     class="text-indigo-600 hover:text-indigo-800 font-medium border border-indigo-600 px-4 py-2 rounded transition whitespace-nowrap">
     Download
  </a>
</header>

<main class="flex-grow max-w-4xl mx-auto w-full px-4 md:px-0 mb-12">

<?php
switch (true) {
    // Images
    case str_starts_with($mime_type, 'image/'):
        echo '<img src="/cloudstore/uploads/' . e($file['file_path']) . '" alt="' . e($file['original_name']) . '" class="max-w-full max-h-[70vh] mx-auto rounded shadow-lg" />';
        break;

    // PDFs
    case $mime_type === 'application/pdf':
        echo '<embed src="/cloudstore/uploads/' . e($file['file_path']) . '" type="application/pdf" width="100%" height="600px" class="rounded shadow-lg" />';
        break;

    // Text files (plain/text, markdown, csv, log)
    case str_starts_with($mime_type, 'text/'):
    case in_array($mime_type, ['application/json', 'application/xml', 'application/javascript']):
        $content = file_get_contents($filepath);
        $snippet = e(substr($content, 0, 5000));
        echo '<pre class="whitespace-pre-wrap text-left bg-white p-6 rounded-lg max-h-[600px] overflow-auto shadow-lg font-mono">' . $snippet . '</pre>';
        break;

    // Audio files
    case str_starts_with($mime_type, 'audio/'):
        echo '<audio controls class="w-full rounded-lg shadow-lg" preload="metadata">';
        echo '<source src="/cloudstore/uploads/' . e($file['file_path']) . '" type="' . e($mime_type) . '">';
        echo 'Your browser does not support the audio element.';
        echo '</audio>';
        break;

    // Video files
    case str_starts_with($mime_type, 'video/'):
        echo '<video controls class="w-full rounded-lg shadow-lg max-h-[600px]" preload="metadata">';
        echo '<source src="/cloudstore/uploads/' . e($file['file_path']) . '" type="' . e($mime_type) . '">';
        echo 'Your browser does not support the video tag.';
        echo '</video>';
        break;

    default:
        echo '<p class="text-center text-gray-600 font-semibold mt-20">Preview not available for this file type.</p>';
        break;
}
?>

</main>

<footer class="max-w-4xl mx-auto w-full px-4 md:px-0 text-center py-6 text-indigo-700 text-sm">
    &copy; <?= date('Y') ?> CloudStore. All rights reserved.
</footer>

</body>
</html>
