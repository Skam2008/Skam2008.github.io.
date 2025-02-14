<?php
require 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role'];
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->execute([$username, $password, $role]);
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f4f4f9;
        }

        .container {
            background: #fff;
            padding: 40px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        h1 {
            margin-bottom: 20px;
            color: #333;
        }

        form input, form select, form button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        form button {
            background-color: #007BFF;
            color: white;
            border: none;
            cursor: pointer;
        }

        form button:hover {
            background-color: #0056b3;
        }

        .link {
            margin-top: 10px;
        }

        .link a {
            text-decoration: none;
            color: #007BFF;
        }

        .link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Регистрация</h1>
        <form method="post">
            <input type="text" name="username" placeholder="Имя пользователя" required>
            <input type="password" name="password" placeholder="Пароль" required>
            <select name="role" required>
                <option value="" disabled selected>Выберите роль</option>
                <option value="student">Ученик</option>
                <option value="teacher">Учитель</option>
            </select>
            <button type="submit">Зарегистрироваться</button>
        </form>
        <div class="link">
            <p>Уже есть аккаунт? <a href="login.php">Войти</a></p>
        </div>
    </div>
</body>
</html>