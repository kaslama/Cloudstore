<?php
require_once '../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        header('Location: ../dashboard.php');
        exit();
    } else {
        $_SESSION['error'] = "Invalid credentials.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <form method="POST" class="bg-white p-8 rounded shadow-md w-full max-w-md space-y-4">
        <h2 class="text-xl font-bold">Login</h2>
        <?php if (isset($_SESSION['error'])): ?>
            <p class="text-red-500 text-sm"><?= $_SESSION['error']; unset($_SESSION['error']); ?></p>
        <?php endif; ?>
        <input type="email" name="email" placeholder="Email" required class="w-full p-2 border rounded">
        <input type="password" name="password" placeholder="Password" required class="w-full p-2 border rounded">
        <button class="w-full bg-green-500 text-white p-2 rounded">Login</button>
        <p class="text-sm">Don't have an account? <a href="register.php" class="text-blue-600">Register</a></p>
    </form>
</body>
</html>
