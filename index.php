<?php
require 'vendor/autoload.php';

use App\User;

session_start();

$userModel = new User();

// Обработка выхода
if (isset($_GET['logout'])) {
    if (isset($_SESSION['user_id'])) {
        $userModel->setRememberToken($_SESSION['user_id'], null);
    }
    session_destroy();
    setcookie('remember_token', '', time() - 3600, '/');
    header('Location: index.php');
    exit;
}

// Автоматическая авторизация по cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $user = $userModel->getUserByToken($token);
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['login']   = $user['login'];
        setcookie('bg_color',   $user['bg_color'],   time() + 3600 * 24 * 30, '/');
        setcookie('font_color', $user['font_color'], time() + 3600 * 24 * 30, '/');
    }
}

// Регистрация
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $login    = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $bgColor  = $_POST['bg_color'] ?? '#ffffff';
    $fontColor= $_POST['font_color'] ?? '#000000';

    if ($login && $password) {
        if ($userModel->register($login, $password, $bgColor, $fontColor)) {
            header('Location: index.php?registered=1');
            exit;
        } else {
            $error = 'Ошибка регистрации. Возможно, логин уже занят.';
        }
    } else {
        $error = 'Заполните все поля.';
    }
}

// Авторизация
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login']) && !isset($_POST['register'])) {
    $login    = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    $user = $userModel->login($login, $password);
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['login']   = $user['login'];

        setcookie('bg_color',   $user['bg_color'],   time() + 3600 * 24 * 30, '/');
        setcookie('font_color', $user['font_color'], time() + 3600 * 24 * 30, '/');

        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $userModel->setRememberToken($user['id'], $token);
            setcookie('remember_token', $token, time() + 3600 * 24 * 30, '/');
        }
        header('Location: index.php');
        exit;
    } else {
        $error = 'Неверный логин или пароль.';
    }
}

// Изменение настроек (только для авторизованных)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings']) && isset($_SESSION['user_id'])) {
    $bgColor   = $_POST['bg_color'] ?? '#ffffff';
    $fontColor = $_POST['font_color'] ?? '#000000';

    $userModel->updateSettings($_SESSION['user_id'], $bgColor, $fontColor);
    setcookie('bg_color',   $bgColor,   time() + 3600 * 24 * 30, '/');
    setcookie('font_color', $fontColor, time() + 3600 * 24 * 30, '/');

    header('Location: index.php');
    exit;
}

// Текущие цвета (из cookie или по умолчанию)
$bgColor   = $_COOKIE['bg_color']   ?? '#ffffff';
$fontColor = $_COOKIE['font_color'] ?? '#000000';

$isLoggedIn = isset($_SESSION['user_id']);
$login      = $_SESSION['login'] ?? '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>ЛР3 — Регистрация и авторизация</title>
    <style>
        body {
            background-color: <?= htmlspecialchars($bgColor) ?>;
            color: <?= htmlspecialchars($fontColor) ?>;
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .container {
            max-width: 500px;
            margin: 0 auto;
            background: rgba(255,255,255,0.85);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.15);
        }
        input, button {
            margin: 5px 0;
            padding: 8px;
            width: 100%;
            box-sizing: border-box;
        }
        .error   { color: red; }
        .success { color: green; }
        h1, h2   { margin-top: 0; }
    </style>
</head>
<body>
<div class="container">
    <h1>Лабораторная работа №3</h1>

    <?php if (isset($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if (isset($_GET['registered'])): ?>
        <p class="success">Регистрация успешна! Теперь войдите.</p>
    <?php endif; ?>

    <?php if ($isLoggedIn): ?>
        <h2>Добро пожаловать, <?= htmlspecialchars($login) ?>!</h2>
        <form method="post">
            <input type="hidden" name="update_settings" value="1">
            <label>Цвет фона:
                <input type="color" name="bg_color" value="<?= htmlspecialchars($bgColor) ?>">
            </label>
            <label>Цвет шрифта:
                <input type="color" name="font_color" value="<?= htmlspecialchars($fontColor) ?>">
            </label>
            <button type="submit">Сохранить настройки</button>
        </form>
        <p><a href="?logout=1">Выйти</a></p>
    <?php else: ?>
        <h2>Авторизация</h2>
        <form method="post">
            <input type="text"     name="login"    placeholder="Логин"  required>
            <input type="password" name="password" placeholder="Пароль" required>
            <label>
                <input type="checkbox" name="remember" style="width:auto;"> Запомнить меня
            </label>
            <button type="submit">Войти</button>
        </form>

        <h2>Регистрация</h2>
        <form method="post">
            <input type="hidden"   name="register" value="1">
            <input type="text"     name="login"    placeholder="Логин"  required>
            <input type="password" name="password" placeholder="Пароль" required>
            <label>Цвет фона:
                <input type="color" name="bg_color" value="#ffffff">
            </label>
            <label>Цвет шрифта:
                <input type="color" name="font_color" value="#000000">
            </label>
            <button type="submit">Зарегистрироваться</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>