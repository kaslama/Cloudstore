<?php
$files = [
    'auth/login.php',
    'auth/register.php',
    'dashboard.php',
    'pages/my_drive.php',
    'pages/trash.php',
    'pages/starred.php',
    'pages/shared.php',
    'upload.php',
    'download.php',
    'restore.php',
    'delete_permanent.php',
    'config/db.php'
];

echo "<h2>Project File Status</h2><ul>";
foreach ($files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<li style='color:green;'>$file — Found</li>";
    } else {
        echo "<li style='color:red;'>$file — Missing</li>";
    }
}
echo "</ul>";
?>
