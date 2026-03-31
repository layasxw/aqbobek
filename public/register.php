<?php
session_start();

require_once '../config/db.php';


$classes = $conn->query("SELECT id, name FROM classes ORDER BY name")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirmPassword']);
    $role = $_POST['role'];
    $class_id = (!empty($_POST['class_id'])) ? (int)$_POST['class_id'] : null;
    
    if ($password !== $confirmPassword) {
        $error = "Пароли не совпадают";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $error = "Пользователь с таким email уже существует";
        } else {
            $checkStmt->close();
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, class_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $name, $email, $hash, $role, $class_id);

            if ($stmt->execute()) {
                header("Location: login.php");
                exit();
            } else {
                $error = "Ошибка: " . $conn->error;
            }
            $stmt->close();
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aqbobek | Регистрация</title>
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

        <h2>Регистрация</h2>

        <?php if (isset($error)): ?>
            <div class="error-box"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label>Полное имя</label>
                <input type="text" name="name" required placeholder="Арман Даулетов">
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required placeholder="example@school.kz">
            </div>

            <div class="form-group">
                <label>Роль</label>
                <select name="role" id="roleSelect" required>
                    <option value="" disabled selected>— Выбери роль —</option>
                    <option value="student">Ученик</option>
                    <option value="teacher">Учитель</option>
                    <option value="parent">Родитель</option>
                </select>
            </div>

            <div class="form-group" id="classField" style="display:none;">
                <label>Класс</label>
                <select name="class_id">
                    <option value="">— Выбери класс —</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?= $class['id'] ?>">
                            <?= htmlspecialchars($class['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Пароль</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>

            <div class="form-group">
                <label>Повтори пароль</label>
                <input type="password" name="confirmPassword" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn-primary">Зарегистрироваться</button>

        </form>

        <p class="auth-link">
            Уже есть аккаунт?
            <a href="login.php">Войти</a>
        </p>
    </div>
</div>

<script>
document.getElementById('roleSelect').addEventListener('change', function() {
    document.getElementById('classField').style.display =
        this.value === 'student' ? 'block' : 'none';
});
</script>
</body>
</html>