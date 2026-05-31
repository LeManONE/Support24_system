<?php
require_once 'includes/auth.php';
require_once 'includes/logger.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';

requireLogin();

$conn = db();
$user = Auth::getCurrentUser();

if (!$user) {
    header('Location: login.php');
    exit();
}

if ($user['password_changed']) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $old = $_POST['old_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if (!password_verify($old, $user['password'])) {
        $error = 'Неверный текущий пароль';
    } elseif (strlen($new) < 6) {
        $error = 'Пароль должен быть минимум 6 символов';
    } elseif ($new !== $confirm) {
        $error = 'Пароли не совпадают';
    } elseif ($old === $new) {
        $error = 'Новый пароль должен отличаться от старого';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ?, password_changed = TRUE WHERE id = ?");
        $stmt->bind_param("si", $hash, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            logAction('PASSWORD_CHANGE', 'Пользователь сменил пароль');
            $success = 'Пароль успешно изменен!';
            header('refresh:2;url=index.php');
        } else {
            $error = 'Ошибка при смене пароля';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Смена пароля</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            width: 400px;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }
        h1 { text-align: center; color: #333; margin-bottom: 10px; }
        .warning {
            text-align: center;
            color: #dc3545;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; color: #555; }
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        input:focus { outline: none; border-color: #667eea; }
        button {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover { background: #764ba2; }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Смена пароля</h1>
        <div class="warning">⚠️ Для безопасности смените пароль по умолчанию</div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Текущий пароль</label>
                <input type="password" name="old_password" required>
            </div>
            <div class="form-group">
                <label>Новый пароль</label>
                <input type="password" name="new_password" required>
            </div>
            <div class="form-group">
                <label>Подтверждение</label>
                <input type="password" name="confirm_password" required>
            </div>
            <button type="submit">Сменить пароль</button>
        </form>
    </div>
</body>
</html>
