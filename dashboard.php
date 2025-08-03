<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/db.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard - CloudStore</title>
    <?php include 'includes/tailwind.php'; ?>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="bg-gray-100 flex">

    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-1 min-h-screen ml-0 md:ml-64">
        <?php include 'includes/header.php'; ?>

        <main class="p-6">
            <?php include 'pages/my_drive.php'; ?>
        </main>
    </div>
    


</body>
</html>
