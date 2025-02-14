<?php
session_start();
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'student') {
        header("Location: dashboard_student.php");
        exit();
    } elseif ($_SESSION['role'] === 'teacher') {
        header("Location: dashboard_teacher.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главная страница</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 50px;
        }
        a {
            display: inline-block;
            margin: 10px;
            padding: 10px 20px;
            text-decoration: none;
            color: white;
            background-color: #007BFF;
            border-radius: 5px;
        }
        a:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <h1>Добро пожаловать на сайт по обучению LibreOffice</h1>
    <p>Выберите действие:</p>
    <a href="register.php">Регистрация</a>
    <a href="login.php">Вход</a>
	<h2>Доступные уроки:</h2>
    <p>
    <a href="Документы в LibreOffice.php">Документы в LibreOffice</a></li>
    <a href="Презентаций в LibreOffice.php">Презентации в LibreOffice</a></li>
    <a href="Таблицы в LibreOffice.php">Таблицы в LibreOffice</a></li>
    </ul>
</body>
</html>