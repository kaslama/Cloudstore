<?php
session_start();

// Enable error reporting (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database config
$host = 'localhost';
$dbname = 'cloudstore';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}

// Initialize CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = '';
$showRegisterForm = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'register') {
        $showRegisterForm = true;

        $username = trim($_POST['username'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (empty($username)) {
            $errors[] = "Username is required.";
        }
        if (!$email) {
            $errors[] = "Valid email is required.";
        }
        if (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters.";
        }
        if ($password !== $confirm) {
            $errors[] = "Passwords do not match.";
        }

        if (empty($errors)) {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $errors[] = "Username or email already registered.";
            } else {
                try {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$username, $email, $hash]);
                    $success = "Registration successful! You can now login.";
                    $showRegisterForm = false; // Switch to login form after success
                } catch (PDOException $e) {
                    $errors[] = "Database error: " . $e->getMessage();
                }
            }
        }
    } elseif ($action === 'login') {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            $errors[] = "Email and password are required.";
        } else {
            $stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                header("Location: /cloudstore/dashboard.php");
                exit;
            } else {
                $errors[] = "Invalid email or password.";
            }
        }
    } else {
        $errors[] = "Invalid form action.";
    }
} else {
    $showRegisterForm = false;
}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <title>CloudStore | Auth</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>.hidden { display: none; }</style>
</head>
<body class="bg-gradient-to-br from-blue-600 to-indigo-700 min-h-screen flex items-center justify-center font-sans">
<div class="bg-white rounded-lg shadow-lg w-full max-w-md p-8 relative">
    <h1 class="text-4xl font-bold text-center mb-6 text-indigo-700">CloudStore</h1>

    <!-- Toast -->
    <div id="toast" 
         class="fixed top-5 right-5 bg-green-500 text-white px-4 py-2 rounded shadow-lg opacity-0 pointer-events-none transition-opacity duration-500 z-50">
        Registration successful! You can now login.
    </div>

    <?php if (!empty($errors)): ?>
        <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
            <ul class="list-disc list-inside">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="flex justify-center mb-6 space-x-4">
        <button id="loginBtn" class="py-2 px-6 font-semibold rounded border-b-4 transition
            <?= !$showRegisterForm ? 'border-indigo-700 text-indigo-700' : 'border-transparent text-gray-700 hover:border-indigo-700 hover:text-indigo-700 hover:bg-indigo-100' ?>">
            Login
        </button>
        <button id="registerBtn" class="py-2 px-6 font-semibold rounded border-b-4 transition
            <?= $showRegisterForm ? 'border-indigo-700 text-indigo-700' : 'border-transparent text-gray-700 hover:border-indigo-700 hover:text-indigo-700 hover:bg-indigo-100' ?>">
            Sign Up
        </button>
    </div>

    <!-- Login Form -->
    <form id="loginForm" method="POST" class="space-y-5 <?= $showRegisterForm ? 'hidden' : '' ?>">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>" />
        <input type="hidden" name="action" value="login" />
        <input type="email" name="email" placeholder="Email" required
               class="w-full p-3 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-600" />
        <input type="password" name="password" placeholder="Password" required
               class="w-full p-3 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-600" />
        <button type="submit" class="w-full bg-indigo-700 hover:bg-indigo-800 text-white py-3 rounded font-semibold transition">Login</button>
    </form>

    <!-- Register Form -->
    <form id="registerForm" method="POST" class="space-y-5 <?= $showRegisterForm ? '' : 'hidden' ?>">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>" />
        <input type="hidden" name="action" value="register" />
        <input type="text" name="username" placeholder="Username" required
               class="w-full p-3 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-600" />
        <input type="email" name="email" placeholder="Email" required
               class="w-full p-3 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-600" />
        <input type="password" name="password" placeholder="Password" required
               class="w-full p-3 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-600" />
        <input type="password" name="confirm_password" placeholder="Confirm Password" required
               class="w-full p-3 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-600" />
        <button type="submit" class="w-full bg-indigo-700 hover:bg-indigo-800 text-white py-3 rounded font-semibold transition">Sign Up</button>
    </form>
</div>

<script>
    const loginBtn = document.getElementById('loginBtn');
    const registerBtn = document.getElementById('registerBtn');
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const toast = document.getElementById('toast');

    loginBtn.addEventListener('click', () => {
        loginForm.classList.remove('hidden');
        registerForm.classList.add('hidden');
        loginBtn.classList.add('border-indigo-700', 'text-indigo-700');
        registerBtn.classList.remove('border-indigo-700', 'text-indigo-700');
    });

    registerBtn.addEventListener('click', () => {
        registerForm.classList.remove('hidden');
        loginForm.classList.add('hidden');
        registerBtn.classList.add('border-indigo-700', 'text-indigo-700');
        loginBtn.classList.remove('border-indigo-700', 'text-indigo-700');
    });

    // Show toast only if registration was successful
    <?php if ($success): ?>
        toast.classList.remove('opacity-0', 'pointer-events-none');
        setTimeout(() => {
            toast.classList.add('opacity-0', 'pointer-events-none');
        }, 4000);
    <?php endif; ?>
</script>
</body>
</html>
