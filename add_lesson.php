<?php
require 'db.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit();
}
$students_stmt = $pdo->prepare("
    SELECT u.id, u.username 
    FROM users u
    JOIN teacher_students ts ON u.id = ts.student_id
    WHERE ts.teacher_id = ?
");
$students_stmt->execute([$_SESSION['user_id']]);
$students = $students_stmt->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lesson_title = $_POST['lesson_title'] ?? null;
    $assignment_description = $_POST['assignment_description'] ?? null;
    $selected_students = $_POST['student_ids'] ?? [];
    if (empty($selected_students)) {
        $selected_students = array_column($students, 'id');
    }
	
    if ($lesson_title) {
        $lesson_title = substr($lesson_title, 0, 3000			);
    }

    if ($lesson_title || $assignment_description) {
        foreach ($selected_students as $student_id) {
            $lesson_id = null;
            if ($lesson_title) {
                $add_lesson = $pdo->prepare("INSERT INTO lessons (teacher_id, student_id, title) VALUES (?, ?, ?)");
                $add_lesson->execute([$_SESSION['user_id'], $student_id, $lesson_title]);
                $lesson_id = $pdo->lastInsertId();
            }
            if ($assignment_description) {
                $lesson_id = $lesson_id ?? null;
                $add_assignment = $pdo->prepare("INSERT INTO assignments (lesson_id, student_id, description) VALUES (?, ?, ?)");
                $add_assignment->execute([$lesson_id, $student_id, $assignment_description]);
            }
        }

        $success_message = "Уроки и/или задания успешно добавлены!";
    } else {
        $error_message = "Заполните хотя бы одно поле (урок или задание).";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавить урок или задание</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 50%;
        }
        h2 {
            color: #333;
        }
        form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        input, select, textarea, button {
            padding: 10px;
            margin: 8px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            width: 80%;
        }
        button {
            background-color: #007BFF;
            color: white;
            border: none;
            cursor: pointer;
            width: 85%;
        }
        button:hover {
            background-color: #0056b3;
        }
        .message {
            color: green;
            margin-bottom: 10px;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
        .back {
            display: inline-block;
            margin-top: 15px;
            text-decoration: none;
            background-color: #FF5733;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
        }
        .back:hover {
            background-color: #C70039;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Добавить урок или задание</h2>
        
        <?php if (isset($success_message)) : ?>
            <p class="message"><?= htmlspecialchars($success_message) ?></p>
        <?php endif; ?>
        
        <?php if (isset($error_message)) : ?>
            <p class="error"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>

        <form method="post">
            <label>Выберите учеников (оставьте пустым для всех):</label><br>
            <?php foreach ($students as $student): ?>
                <input type="checkbox" name="student_ids[]" value="<?= $student['id'] ?>"> <?= htmlspecialchars($student['username']) ?><br>
            <?php endforeach; ?>

            <label>Название урока:</label>
            <input type="text" name="lesson_title" maxlength="255">

            <label>Описание задания:</label>
            <textarea name="assignment_description"></textarea>

            <button type="submit">Добавить</button>
        </form>

        <a class="back" href="dashboard_teacher.php">Вернуться назад</a>
    </div>
</body>
</html>