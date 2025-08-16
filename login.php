<?php
session_start();
require 'config/koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];
            $_SESSION['cabang']   = $user['cabang_id'];

            if ($user['role'] === 'User') {
                header("Location: cabang/dashboard_cabang.php");
            } elseif ($user['role'] === 'Admin') {
                header("Location: admin/dashboard_admin.php");
            }
            exit;
        }
    }

    $error = "Login gagal! Username atau password salah.";
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Login Sistem BPJS</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;

            /* Background gambar */
            background: url('assets/img/bpjs.png') no-repeat center center fixed;
            background-size: cover;
            position: relative;
        }

        /* Overlay gelap supaya teks tetap jelas */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(3px);
            z-index: 0;
        }

        .login-box {
            position: relative;
            z-index: 1;
            background-color: rgba(255, 255, 255, 0.85);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            width: 350px;
            text-align: center;
            animation: fadeIn 0.7s ease-in-out;
        }

        .login-box h2 {
            margin-bottom: 25px;
            color: #2c3e50;
        }

        .login-box input[type="text"],
        .login-box input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 10px;
            font-size: 16px;
            box-sizing: border-box;
        }

        .login-box button {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            margin-top: 15px;
            transition: transform 0.2s ease, background 0.3s ease;
        }

        .login-box button:hover {
            transform: scale(1.03);
            background: linear-gradient(135deg, #2980b9, #1f6391);
        }

        .error {
            color: red;
            margin-bottom: 15px;
            font-size: 14px;
        }

        footer {
            margin-top: 20px;
            color: #555;
            font-size: 14px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <div class="overlay"></div>
    <div class="login-box">
        <h2>Login Aplikasi BPJS</h2>
        <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <footer>
            &copy; <?= date('Y') ?> Aplikasi BPJS - Semua hak dilindungi
        </footer>
    </div>
</body>

</html>