<?php
session_start();
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../classes/Auth.php';

$auth = new Auth();
$error = '';
$email = ''; // Inisialisasi variabel email
$password = ''; // Inisialisasi variabel password

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($auth->login($email, $password)) {
        // Redirect ke admin dashboard jika admin, ke user dashboard jika user
        if ($auth->isAdmin()) {
            header('Location: admin/index.php');
        } else {
            header('Location: index.php');
        }
        exit;
    } else {
        $error = 'Email atau password salah!';
    }
}

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    if ($auth->isAdmin()) {
        header('Location: admin/index.php');
    } else {
        header('Location: index.php');
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Event Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="login-card p-4">
                    <h2 class="text-center mb-4">Login Event Management</h2>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required 
                                   value="<?= htmlspecialchars($email) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>git
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <p>Belum punya akun? <a href="register.php">Daftar disini</a></p>
                        <p class="text-muted small">
                            <strong>Demo Accounts:</strong><br>
                            👨‍💼 Admin: admin@event.com / password123<br>
                            👨‍🎓 User: budi@student.com / password123
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>