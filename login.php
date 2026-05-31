<?php
require_once 'includes/auth.php';
require_once 'includes/logger.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $agree = $_POST['agree'] ?? '';
    
    if (!$agree) {
        $error = 'Необходимо согласие с политикой обработки данных';
    } elseif (Auth::login($username, $password)) {
        logAction('LOGIN_SUCCESS', "Пользователь $username вошел в систему");
        redirect('index.php');
    } else {
        $error = 'Неверный логин или пароль';
        logAction('LOGIN_FAILED', "Неудачная попытка входа для $username");
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход в систему</title>
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
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            width: 350px;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }
        h1 { text-align: center; color: #333; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; color: #555; }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .checkbox-group { margin: 15px 0; }
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            color: #555;
        }
        .checkbox-label a {
            color: #667eea;
            text-decoration: none;
        }
        .checkbox-label a:hover { text-decoration: underline; }
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
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Техническая поддержка</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Логин</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Пароль</label>
                <input type="password" name="password" required>
            </div>
            
            <div class="checkbox-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="agree" required>
                    <span>Я согласен с <a href="/policy.html" target="_blank">политикой обработки персональных данных</a></span>
                </label>
            </div>
            
            <button type="submit">Войти</button>
        </form>
    </div>
</body>
</html>
