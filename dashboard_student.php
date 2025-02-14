<?php
require 'db.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit();
}

// Получаем данные ученика
$stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();
$student_name = $student['username'];
$student_id = $student['id'];

// Получаем уроки и задания для ученика
$lessons_stmt = $pdo->prepare("
    SELECT l.id AS lesson_id, l.title, a.id AS assignment_id, a.description, u.username AS teacher_name
    FROM lessons l
    JOIN users u ON l.teacher_id = u.id
    LEFT JOIN assignments a ON l.id = a.lesson_id
    WHERE l.student_id = ?
");
$lessons_stmt->execute([$_SESSION['user_id']]);
$lessons = $lessons_stmt->fetchAll();

// Получаем ответы ученика из таблицы student_answers
$answers_stmt = $pdo->prepare("
    SELECT sa.id AS answer_id, sa.lesson_id, sa.answer_text AS answer, sa.submitted_at, sa.file_path
    FROM student_answers sa
    WHERE sa.student_id = ?
");
$answers_stmt->execute([$_SESSION['user_id']]);
$answers = $answers_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель ученика</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f9;
            text-align: center;
        }
        .logout {
            text-align: right;
            margin-bottom: 20px;
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
        .lesson {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .lesson h3 {
            margin: 0;
            color: #007BFF;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        ul li {
            margin: 10px 0;
        }
        ul li a {
            text-decoration: none;
            color: #007BFF;
            font-size: 18px;
        }
        ul li a:hover {
            color: #0056b3;
        }
        .id-button {
            display: inline-block;
            margin-top: 10px;
            padding: 10px 20px;
            text-decoration: none;
            color: white;
            background-color: #007BFF;
            border-radius: 5px;
            cursor: pointer;
        }
        .id-button:hover {
            background-color: #0056b3;
        }
        /* Модальное окно */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
        }
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #ccc;
            width: 300px;
            text-align: center;
            border-radius: 5px;
        }
        .modal-content p {
            font-size: 18px;
            margin: 0;
            color: #333;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: black;
        }
    </style>
</head>
<body>
    <div class="logout">
        <a href="logout.php">Выход</a>
    </div>

    <h1>Добро пожаловать, <?= htmlspecialchars($student_name) ?>!</h1>
    <button class="id-button" onclick="showModal()">Узнать ID</button>

    <!-- Блок с уроками и заданиями -->
    <div class="container">
        <h2>Ваши уроки и задания:</h2>
        <?php if (!empty($lessons)): ?>
            <?php foreach ($lessons as $lesson): ?>
                <div class="lesson">
                    <h3><?= htmlspecialchars($lesson['title']) ?></h3>
                    <p><strong>Учитель:</strong> <?= htmlspecialchars($lesson['teacher_name']) ?></p>
                    <?php if ($lesson['assignment_id']): ?>
                        <p><strong>Задание:</strong> <?= htmlspecialchars($lesson['description']) ?></p>
                        <!-- Кнопка "Сдать задание" -->
                        <a href="submit_answer.php?lesson_id=<?= $lesson['lesson_id'] ?>" class="id-button">Сдать задание</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>У вас пока нет назначенных уроков.</p>
        <?php endif; ?>
    </div>

    <!-- Блок с отправленными ответами -->
    <div class="container">
        <h2>Ваши отправленные ответы:</h2>
        <?php if (!empty($answers)): ?>
            <?php foreach ($answers as $answer): ?>
                <div class="lesson">
                    <p><strong>Ответ:</strong> <?= htmlspecialchars($answer['answer']) ?></p>
                    <p><strong>Дата отправки:</strong> <?= htmlspecialchars($answer['submitted_at']) ?></p>
                    <?php if ($answer['file_path']): ?>
                        <p><strong>Файл:</strong> <a href="<?= htmlspecialchars($answer['file_path']) ?>" target="_blank">Скачать</a></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Вы пока не отправили ни одного ответа.</p>
        <?php endif; ?>
    </div>

    <h2>Основные уроки:</h2>
    <ul>
        <li><a href="Документы в LibreOffice.php">Документы в LibreOffice</a></li>
        <li><a href="Презентаций в LibreOffice.php">Презентации в LibreOffice</a></li>
        <li><a href="Таблицы в LibreOffice.php">Таблицы в LibreOffice</a></li>
    </ul>

    <!-- Модальное окно для отображения ID ученика -->
    <div id="idModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <p>Ваш ID: <?= htmlspecialchars($student_id) ?></p>
        </div>
    </div>

    <script>
        function showModal() {
            document.getElementById('idModal').style.display = 'block';
        }
        function closeModal() {
            document.getElementById('idModal').style.display = 'none';
        }
        window.onclick = function(event) {
            const modal = document.getElementById('idModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>