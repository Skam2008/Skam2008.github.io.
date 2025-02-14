<?php
require 'db.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit();
}

// Получаем имя учителя
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$teacher = $stmt->fetch();
$teacher_name = $teacher['username'];

// Получаем список учеников учителя
$students_stmt = $pdo->prepare("
    SELECT u.id, u.username 
    FROM users u
    JOIN teacher_students ts ON u.id = ts.student_id
    WHERE ts.teacher_id = ?
");
$students_stmt->execute([$_SESSION['user_id']]);
$students = $students_stmt->fetchAll();

// Получаем уроки и задания учителя
$lessons_stmt = $pdo->prepare("
    SELECT l.id AS lesson_id, l.title, u.username AS student_name, a.id AS assignment_id, a.description
    FROM lessons l
    JOIN users u ON l.student_id = u.id
    LEFT JOIN assignments a ON l.id = a.lesson_id
    WHERE l.teacher_id = ?
");
$lessons_stmt->execute([$_SESSION['user_id']]);
$lessons = $lessons_stmt->fetchAll();

// Получаем ответы учеников из таблицы student_answers
$answers_stmt = $pdo->prepare("
    SELECT sa.id AS answer_id, sa.lesson_id, sa.student_id, sa.answer_text AS answer, 
           u.username AS student_name, sa.submitted_at, sa.file_path
    FROM student_answers sa
    JOIN users u ON sa.student_id = u.id
    JOIN lessons l ON sa.lesson_id = l.id
    WHERE l.teacher_id = ?
");
$answers_stmt->execute([$_SESSION['user_id']]);
$answers = $answers_stmt->fetchAll();

// Обработка POST-запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_student'])) {
        $stmt = $pdo->prepare("DELETE FROM teacher_students WHERE student_id = ? AND teacher_id = ?");
        $stmt->execute([$_POST['delete_student'], $_SESSION['user_id']]);
    }
    if (isset($_POST['delete_lesson'])) {
        $stmt = $pdo->prepare("DELETE FROM lessons WHERE id = ?");
        $stmt->execute([$_POST['delete_lesson']]);
    }
    if (isset($_POST['delete_assignment'])) {
        $stmt = $pdo->prepare("DELETE FROM assignments WHERE id = ?");
        $stmt->execute([$_POST['delete_assignment']]);
    }
    if (isset($_POST['add_student'])) {
        $student_id = $_POST['student_id'];
        $stmt = $pdo->prepare("INSERT INTO teacher_students (teacher_id, student_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $student_id]);
    }
    header("Location: dashboard_teacher.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель учителя</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f9;
        }
        .logout {
            text-align: right;
        }
        .logout a {
            text-decoration: none;
            color: white;
            background-color: #FF5733;
            padding: 10px 20px;
            border-radius: 5px;
        }
        .logout a:hover {
            background-color: #C70039;
        }
        h1, h2 {
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .add-lesson {
            display: inline-block;
            margin: 20px 0;
            text-decoration: none;
            color: white;
            background-color: #28a745;
            padding: 10px 20px;
            border-radius: 5px;
            text-align: center;
        }
        .add-lesson:hover {
            background-color: #218838;
        }
        .lesson, .student {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .lesson h3 {

            margin: 0;
            color: #007BFF;
        }
        .students-panel {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .students-panel h3 {
            margin-top: 0;
            color: #007BFF;
        }
        .students-panel ul {
            list-style-type: none;
            padding: 0;
        }
        .students-panel li {
            margin: 10px 0;
            color: #333;
        }
        button {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logout">
            <a href="logout.php">Выход</a>
        </div>
        <h1>Добро пожаловать, <?= htmlspecialchars($teacher_name) ?>!</h1>
        <a href="add_lesson.php" class="add-lesson">Добавить урок или задание</a>

        <h2>Добавить ученика:</h2>
        <form method="post">
            <input type="text" name="student_id" placeholder="ID ученика" required>
            <button type="submit" name="add_student">Добавить ученика</button>
        </form>

        <h2>Ученики:</h2>
        <?php if (!empty($students)): ?>
            <div class="students-panel">
                <ul>
                    <?php foreach ($students as $student): ?>
                        <li>
                            <?= htmlspecialchars($student['username']) ?> (ID: <?= htmlspecialchars($student['id']) ?>)
                            <form method="post" style="display:inline;">
                                <button type="submit" name="delete_student" value="<?= $student['id'] ?>">Удалить</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <p>У вас пока нет учеников.</p>
        <?php endif; ?>

        <h2>Уроки и задания:</h2>
        <?php if (!empty($lessons)): ?>
            <?php foreach ($lessons as $lesson): ?>
                <div class="lesson">
                    <h3>Урок: <?= htmlspecialchars($lesson['title']) ?></h3>
                    <p>Ученик: <?= htmlspecialchars($lesson['student_name']) ?></p>
                    <form method="post">
                        <button type="submit" name="delete_lesson" value="<?= $lesson['lesson_id'] ?>">Удалить урок</button>
                    </form>
                    <?php if ($lesson['assignment_id']): ?>
                        <p><strong>Задание:</strong> <?= htmlspecialchars($lesson['description']) ?></p>
                        <form method="post">
                            <button type="submit" name="delete_assignment" value="<?= $lesson['assignment_id'] ?>">Удалить задание</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>У вас пока нет уроков.</p>
        <?php endif; ?>

        <h2>Ответы учеников:</h2>
        <?php if (!empty($answers)): ?>
            <?php foreach ($answers as $answer): ?>
                <div class="lesson">
                    <h3>Ответ от: <?= htmlspecialchars($answer['student_name']) ?></h3>
                    <p><strong>Дата:</strong> <?= htmlspecialchars($answer['submitted_at']) ?></p>
                    <p><strong>Ответ:</strong> <?= htmlspecialchars($answer['answer']) ?></p>
                    <?php if ($answer['file_path']): ?>
                        <p><strong>Файл:</strong> <a href="<?= htmlspecialchars($answer['file_path']) ?>" target="_blank">Скачать</a></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Ответов пока нет.</p>
        <?php endif; ?>
    </div>
</body>
</html>