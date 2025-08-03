<?php
require_once '../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    try {
        $stmt->execute([$name, $email, $password]);
        $_SESSION['success'] = "Registered successfully. Please login.";
        header('Location: login.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Email already registered.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <form method="POST" class="bg-white p-8 rounded shadow-md w-full max-w-md space-y-4">
        <h2 class="text-xl font-bold">Register</h2>
        <input name="name" placeholder="Name" required class="w-full p-2 border rounded">
        <input type="email" name="email" placeholder="Email" required class="w-full p-2 border rounded">
        <input type="password" name="password" placeholder="Password" required class="w-full p-2 border rounded">
        <button class="w-full bg-blue-500 text-white p-2 rounded">Register</button>
        <p class="text-sm">Already have an account? <a href="login.php" class="text-blue-600">Login</a></p>
    </form>
</body>
</html>
