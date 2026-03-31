<?php
session_start();

require_once '../config/db.php';


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (empty($_POST['email']) || empty($_POST['password'])) {
        $error = "Email и пароль обязательны";
    } else {
        $email = trim($_POST['email']);
        $pass = trim($_POST['password']);

        $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();

            if (password_verify($pass, $row['password'])) {
                $_SESSION['id'] = $row['id'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['name'] = $row['name'];
                $_SESSION['role'] = $row['role'];
                

                switch ($row['role']) {
                    case 'student': header("Location: ../student/dashboard.php"); break;
                    case 'teacher': header("Location: ../teacher/dashboard.php"); break;
                    case 'parent':  header("Location: ../parent/dashboard.php"); break;
                    case 'admin':   header("Location: ../admin/dashboard.php"); break;
                }
                exit();
            } else {
                $error = "Неверный email или пароль";
            }
        } else {
            $error = "Неверный email или пароль";
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aqbobek | Вход</title>
    <link rel="stylesheet" href="https:cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https:fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<div class="bg-blobs">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
</div>

<div class="auth-wrapper">
    <div class="glass-card auth-card">
        <div class="logo-area">
            <div class="logo-icon"><i class="fas fa-bolt"></i></div>
            <span>AQBOBEK</span>
        </div>

        <h2>Вход</h2>

        <?php if (isset($error)): ?>
            <div class="error-box"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required placeholder="example@school.kz">
            </div>

            <div class="form-group">
                <label>Пароль</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn-primary">Войти</button>
        </form>

        <p class="auth-link">
            Нет аккаунта?
            <a href="register.php">Зарегистрироваться</a>
        </p>
    </div>
</div>

</body>
</html>